<?php
/**
 * Incident Tracker - Complete Version
 *
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Incident_Tracker {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_incident_pages' ), 25 ); // Position 5
        add_action( 'esc_status_changed', array( $this, 'handle_status_change' ), 10, 3 );
        add_action( 'wp_ajax_esc_resolve_incident', array( $this, 'ajax_resolve_incident' ) );
        add_action( 'wp_ajax_esc_create_incident', array( $this, 'ajax_create_incident' ) );
    }

    public function add_incident_pages() {
        add_submenu_page(
            'easy-status-check',
            __( 'Incident-Tracking & Public Status', 'easy-status-check' ),
            __( 'Incidents', 'easy-status-check' ),
            'manage_options',
            'easy-status-check-incidents',
            array( $this, 'render_incidents_page' )
        );
    }

    public function handle_status_change( $service_id, $old_status, $new_status ) {
        if ( $old_status === $new_status ) {
            return;
        }

        if ( $new_status === 'offline' || $new_status === 'warning' ) {
            $this->create_incident( $service_id, $new_status );
        } elseif ( $new_status === 'online' && ( $old_status === 'offline' || $old_status === 'warning' ) ) {
            $this->resolve_open_incidents( $service_id );
        }
    }

    public function create_incident( $service_id, $severity = 'minor' ) {
        global $wpdb;

        $active_incident = $this->get_active_incident( $service_id );
        if ( $active_incident ) {
            return $active_incident->id;
        }

        $services_table = $wpdb->prefix . 'esc_services';
        $service = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $services_table WHERE id = %d",
            $service_id
        ) );
        
        if ( ! $service ) {
            return false;
        }

        $severity_map = array(
            'warning' => 'minor',
            'offline' => 'major',
        );

        $incident_severity = isset( $severity_map[ $severity ] ) ? $severity_map[ $severity ] : 'minor';

        $title = sprintf(
            __( '%s ist nicht erreichbar', 'easy-status-check' ),
            $service->name
        );

        $description = sprintf(
            __( 'Der Service %s ist seit %s nicht mehr erreichbar.', 'easy-status-check' ),
            $service->name,
            current_time( 'mysql' )
        );

        $incidents_table = $wpdb->prefix . 'esc_incidents';
        $result = $wpdb->insert(
            $incidents_table,
            array(
                'service_id' => $service_id,
                'severity' => $incident_severity,
                'title' => $title,
                'description' => $description,
                'started_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );

        if ( $result ) {
            $incident_id = $wpdb->insert_id;
            do_action( 'esc_incident_created', $incident_id, $service_id, $incident_severity );
            $this->send_incident_notification( $incident_id, 'created' );
            return $incident_id;
        }

        return false;
    }

    public function resolve_open_incidents( $service_id ) {
        global $wpdb;

        $incidents_table = $wpdb->prefix . 'esc_incidents';
        $open_incidents = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $incidents_table WHERE service_id = %d AND resolved_at IS NULL",
            $service_id
        ) );

        foreach ( $open_incidents as $incident ) {
            $this->resolve_incident( $incident->id );
        }
    }

    public function resolve_incident( $incident_id, $notes = '' ) {
        global $wpdb;

        $incidents_table = $wpdb->prefix . 'esc_incidents';
        $incident = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $incidents_table WHERE id = %d",
            $incident_id
        ) );

        if ( ! $incident || $incident->resolved_at ) {
            return false;
        }

        $started = strtotime( $incident->started_at );
        $resolved = current_time( 'timestamp' );
        $duration = $resolved - $started;

        $result = $wpdb->update(
            $incidents_table,
            array(
                'resolved_at' => current_time( 'mysql' ),
                'duration' => $duration,
                'notes' => $notes,
            ),
            array( 'id' => $incident_id ),
            array( '%s', '%d', '%s' ),
            array( '%d' )
        );

        if ( $result !== false ) {
            do_action( 'esc_incident_resolved', $incident_id, $incident->service_id, $duration );
            $this->send_incident_notification( $incident_id, 'resolved' );
            return true;
        }

        return false;
    }

    public function get_active_incident( $service_id ) {
        global $wpdb;

        $incidents_table = $wpdb->prefix . 'esc_incidents';
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $incidents_table WHERE service_id = %d AND resolved_at IS NULL ORDER BY started_at DESC LIMIT 1",
            $service_id
        ) );
    }

    private function send_incident_notification( $incident_id, $type = 'created' ) {
        global $wpdb;

        $incidents_table = $wpdb->prefix . 'esc_incidents';
        $incident = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $incidents_table WHERE id = %d",
            $incident_id
        ) );

        if ( ! $incident ) {
            return;
        }

        $services_table = $wpdb->prefix . 'esc_services';
        $service = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $services_table WHERE id = %d",
            $incident->service_id
        ) );
        
        if ( ! $service ) {
            return;
        }

        $admin_email = get_option( 'admin_email' );
        
        if ( $type === 'created' ) {
            $subject = sprintf(
                __( '[INCIDENT] %s ist ausgefallen', 'easy-status-check' ),
                $service->name
            );
            
            $message = sprintf(
                __( "Ein neuer Incident wurde erstellt:\n\nService: %s\nSchweregrad: %s\nBeginn: %s\n\nBeschreibung:\n%s", 'easy-status-check' ),
                $service->name,
                strtoupper( $incident->severity ),
                $incident->started_at,
                $incident->description
            );
        } else {
            $duration_formatted = $this->format_duration( $incident->duration );
            
            $subject = sprintf(
                __( '[RESOLVED] %s ist wieder online', 'easy-status-check' ),
                $service->name
            );
            
            $message = sprintf(
                __( "Ein Incident wurde behoben:\n\nService: %s\nDauer: %s\nBeginn: %s\nEnde: %s\n\nNotizen:\n%s", 'easy-status-check' ),
                $service->name,
                $duration_formatted,
                $incident->started_at,
                $incident->resolved_at,
                $incident->notes ?: __( 'Keine Notizen', 'easy-status-check' )
            );
        }

        wp_mail( $admin_email, $subject, $message );
    }

    private function format_duration( $seconds ) {
        if ( ! $seconds ) {
            return __( 'Unbekannt', 'easy-status-check' );
        }

        $days = floor( $seconds / 86400 );
        $hours = floor( ( $seconds % 86400 ) / 3600 );
        $minutes = floor( ( $seconds % 3600 ) / 60 );

        $parts = array();
        if ( $days > 0 ) {
            $parts[] = sprintf( _n( '%d Tag', '%d Tage', $days, 'easy-status-check' ), $days );
        }
        if ( $hours > 0 ) {
            $parts[] = sprintf( _n( '%d Stunde', '%d Stunden', $hours, 'easy-status-check' ), $hours );
        }
        if ( $minutes > 0 ) {
            $parts[] = sprintf( _n( '%d Minute', '%d Minuten', $minutes, 'easy-status-check' ), $minutes );
        }

        return implode( ', ', $parts );
    }

    public function render_incidents_page() {
        // Handle settings save
        if ( isset( $_POST['esc_save_public_incidents'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'esc_public_incidents_settings' ) ) {
            // Save CVE feeds
            $cve_feeds = array();
            if ( isset( $_POST['cve_feed_name'] ) && is_array( $_POST['cve_feed_name'] ) ) {
                foreach ( $_POST['cve_feed_name'] as $index => $name ) {
                    if ( ! empty( $name ) && ! empty( $_POST['cve_feed_url'][ $index ] ) ) {
                        $cve_feeds[] = array(
                            'name' => sanitize_text_field( $name ),
                            'url' => esc_url_raw( $_POST['cve_feed_url'][ $index ] ),
                        );
                    }
                }
            }
            update_option( 'esc_cve_feeds', $cve_feeds );
            update_option( 'esc_public_cve_max_items', intval( $_POST['cve_max_items'] ?? 10 ) );
            
            echo '<div class="notice notice-success"><p>' . __( 'Einstellungen gespeichert.', 'easy-status-check' ) . '</p></div>';
        }
        
        // Get settings
        $public_enabled = get_option( 'esc_public_status_enabled', false );
        $public_slug = get_option( 'esc_public_status_slug', 'status' );
        $cve_feeds = get_option( 'esc_cve_feeds', array() );
        $cve_max_items = get_option( 'esc_public_cve_max_items', 10 );
        
        ?>
        <div class="wrap">
            <h1><?php _e( 'Public Incidents Page - Einstellungen', 'easy-status-check' ); ?></h1>
            <p class="description"><?php _e( 'Konfigurieren Sie die öffentliche Incidents-Seite mit CVE RSS Feeds für Sicherheitswarnungen.', 'easy-status-check' ); ?></p>
            
            <?php if ( $public_enabled ) : ?>
                <div class="notice notice-info">
                    <p>
                        <strong><?php _e( 'Ihre Public Incidents Page:', 'easy-status-check' ); ?></strong>
                        <a href="<?php echo home_url( '/' . $public_slug . '/incidents' ); ?>" target="_blank" class="button button-secondary">
                            <span class="dashicons dashicons-external" style="margin-top: 3px;"></span>
                            <?php echo home_url( '/' . $public_slug . '/incidents' ); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="esc-incidents-settings">
                <form method="post" action="">
                    <?php wp_nonce_field( 'esc_public_incidents_settings' ); ?>
                    <input type="hidden" name="esc_save_public_incidents" value="1">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Maximale Anzahl CVE-Items', 'easy-status-check' ); ?></th>
                            <td>
                                <input type="number" name="cve_max_items" value="<?php echo esc_attr( $cve_max_items ); ?>" min="5" max="50" class="small-text">
                                <p class="description"><?php _e( 'Wie viele CVE-Items pro Feed auf der öffentlichen Incidents-Seite angezeigt werden sollen (5-50)', 'easy-status-check' ); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e( 'CVE RSS Feeds', 'easy-status-check' ); ?></h3>
                    <p class="description"><?php _e( 'Fügen Sie RSS-Feeds für Sicherheitswarnungen hinzu (z.B. von CERT-Bund, NVD, etc.)', 'easy-status-check' ); ?></p>
                    
                    <table class="wp-list-table widefat fixed striped" id="esc-cve-feeds-table">
                        <thead>
                            <tr>
                                <th><?php _e( 'Name', 'easy-status-check' ); ?></th>
                                <th><?php _e( 'RSS Feed URL', 'easy-status-check' ); ?></th>
                                <th><?php _e( 'Aktionen', 'easy-status-check' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $cve_feeds ) ) : ?>
                                <?php foreach ( $cve_feeds as $index => $feed ) : ?>
                                    <tr>
                                        <td><input type="text" name="cve_feed_name[]" value="<?php echo esc_attr( $feed['name'] ); ?>" class="regular-text"></td>
                                        <td><input type="url" name="cve_feed_url[]" value="<?php echo esc_url( $feed['url'] ); ?>" class="large-text"></td>
                                        <td><button type="button" class="button esc-remove-feed"><?php _e( 'Entfernen', 'easy-status-check' ); ?></button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td><input type="text" name="cve_feed_name[]" placeholder="CERT-Bund" class="regular-text"></td>
                                    <td><input type="url" name="cve_feed_url[]" placeholder="https://www.cert-bund.de/rss" class="large-text"></td>
                                    <td><button type="button" class="button esc-remove-feed"><?php _e( 'Entfernen', 'easy-status-check' ); ?></button></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <p>
                        <button type="button" class="button" id="esc-add-cve-feed"><?php _e( 'Feed hinzufügen', 'easy-status-check' ); ?></button>
                    </p>
                    
                    <?php submit_button( __( 'Einstellungen speichern', 'easy-status-check' ) ); ?>
                </form>
            </div>
        </div>
        
        <style>
            .esc-incidents-settings { background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-top: 20px; }
            #esc-cve-feeds-table input { width: 100%; }
            .notice.notice-info { margin: 20px 0; }
            .notice.notice-info .button { margin-left: 10px; vertical-align: middle; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            
            // Add CVE feed
            $('#esc-add-cve-feed').on('click', function() {
                var row = '<tr>' +
                    '<td><input type="text" name="cve_feed_name[]" placeholder="Feed Name" class="regular-text"></td>' +
                    '<td><input type="url" name="cve_feed_url[]" placeholder="https://..." class="large-text"></td>' +
                    '<td><button type="button" class="button esc-remove-feed"><?php _e( 'Entfernen', 'easy-status-check' ); ?></button></td>' +
                    '</tr>';
                $('#esc-cve-feeds-table tbody').append(row);
            });
            
            // Remove CVE feed
            $(document).on('click', '.esc-remove-feed', function() {
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }

    public function ajax_resolve_incident() {
        check_ajax_referer( 'esc_resolve_incident', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }
        
        $incident_id = isset( $_POST['incident_id'] ) ? intval( $_POST['incident_id'] ) : 0;
        
        if ( ! $incident_id ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige Incident-ID', 'easy-status-check' ) ) );
        }
        
        $result = $this->resolve_incident( $incident_id );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Incident wurde behoben', 'easy-status-check' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Fehler beim Beheben des Incidents', 'easy-status-check' ) ) );
        }
    }

    public function ajax_create_incident() {
        check_ajax_referer( 'esc_create_incident', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }
        
        $service_id = isset( $_POST['service_id'] ) ? intval( $_POST['service_id'] ) : 0;
        $severity = isset( $_POST['severity'] ) ? sanitize_text_field( $_POST['severity'] ) : 'minor';
        
        if ( ! $service_id ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige Service-ID', 'easy-status-check' ) ) );
        }
        
        $incident_id = $this->create_incident( $service_id, $severity );
        
        if ( $incident_id ) {
            wp_send_json_success( array( 
                'message' => __( 'Incident wurde erstellt', 'easy-status-check' ),
                'incident_id' => $incident_id
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Fehler beim Erstellen des Incidents', 'easy-status-check' ) ) );
        }
    }
}
