<?php
/**
 * Settings Page with Tabs
 *
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current tab
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

// Handle settings save
if ( isset( $_POST['esc_save_settings'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'esc_settings_' . $active_tab ) ) {
    switch ( $active_tab ) {
        case 'general':
            update_option( 'esc_public_status_enabled', isset( $_POST['esc_public_status_enabled'] ) ? 1 : 0 );
            update_option( 'esc_public_status_slug', sanitize_title( $_POST['esc_public_status_slug'] ) );
            update_option( 'esc_general_settings', array(
                'default_interval' => intval( $_POST['default_interval'] ?? 300 ),
                'default_timeout' => intval( $_POST['default_timeout'] ?? 10 )
            ) );
            flush_rewrite_rules();
            break;
            
        case 'notifications':
            update_option( 'esc_notification_settings', array(
                'enabled' => isset( $_POST['notifications_enabled'] ),
                'email' => sanitize_email( $_POST['notification_email'] ?? get_option( 'admin_email' ) )
            ) );
            break;
            
        case 'design':
            $public_settings = array(
                'primary_color' => sanitize_hex_color( $_POST['primary_color'] ?? '#2271b1' ),
                'success_color' => sanitize_hex_color( $_POST['success_color'] ?? '#00a32a' ),
                'warning_color' => sanitize_hex_color( $_POST['warning_color'] ?? '#f0b849' ),
                'error_color' => sanitize_hex_color( $_POST['error_color'] ?? '#d63638' ),
                'background_color' => sanitize_hex_color( $_POST['background_color'] ?? '#f0f0f1' ),
                'text_color' => sanitize_hex_color( $_POST['text_color'] ?? '#1d2327' ),
                'show_response_time' => isset( $_POST['show_response_time'] ),
                'show_uptime' => isset( $_POST['show_uptime'] ),
                'refresh_interval' => intval( $_POST['refresh_interval'] ?? 300 )
            );
            update_option( 'esc_public_settings', $public_settings );
            break;
    }
    
    echo '<div class="notice notice-success"><p>' . __( 'Einstellungen gespeichert.', 'easy-status-check' ) . '</p></div>';
}

// Get settings
$general_settings = get_option( 'esc_general_settings', array( 'default_interval' => 300, 'default_timeout' => 10 ) );
$notification_settings = get_option( 'esc_notification_settings', array( 'enabled' => 1, 'email' => get_option( 'admin_email' ) ) );
$public_settings = get_option( 'esc_public_settings', array(
    'primary_color' => '#2271b1',
    'success_color' => '#00a32a',
    'warning_color' => '#f0b849',
    'error_color' => '#d63638',
    'background_color' => '#f0f0f1',
    'text_color' => '#1d2327',
    'show_response_time' => true,
    'show_uptime' => true,
    'refresh_interval' => 300
) );
$public_status_enabled = get_option( 'esc_public_status_enabled', false );
$public_status_slug = get_option( 'esc_public_status_slug', 'status' );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Einstellungen', 'easy-status-check' ); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=easy-status-check-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Allgemein', 'easy-status-check' ); ?>
        </a>
        <a href="?page=easy-status-check-settings&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Benachrichtigungen', 'easy-status-check' ); ?>
        </a>
        <a href="?page=easy-status-check-settings&tab=design" class="nav-tab <?php echo $active_tab === 'design' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Design', 'easy-status-check' ); ?>
        </a>
        <a href="?page=easy-status-check-settings&tab=support" class="nav-tab <?php echo $active_tab === 'support' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Support', 'easy-status-check' ); ?>
        </a>
    </h2>
    
    <form method="post" action="">
        <?php wp_nonce_field( 'esc_settings_' . $active_tab ); ?>
        <input type="hidden" name="esc_save_settings" value="1">
        
        <?php if ( $active_tab === 'general' ) : ?>
            <!-- ALLGEMEIN TAB -->
            <h2><?php esc_html_e( 'Allgemeine Einstellungen', 'easy-status-check' ); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Public Pages aktivieren', 'easy-status-check' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="esc_public_status_enabled" value="1" <?php checked( $public_status_enabled ); ?>>
                            <?php esc_html_e( 'Öffentliche Seiten aktivieren (Services, Incidents, History)', 'easy-status-check' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Aktiviert alle öffentlichen Status-Seiten', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Basis URL-Slug', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="text" name="esc_public_status_slug" value="<?php echo esc_attr( $public_status_slug ); ?>" class="regular-text">
                        <p class="description">
                            <?php printf( __( 'Basis-URL: %s (Unterseiten: /services, /incidents, /history/ID)', 'easy-status-check' ), '<code>' . home_url( '/' ) . esc_html( $public_status_slug ) . '</code>' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Links zu Public Pages', 'easy-status-check' ); ?></th>
                    <td>
                        <?php if ( $public_status_enabled ) : ?>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <a href="<?php echo home_url( '/' . $public_status_slug . '/services' ); ?>" class="button button-secondary" target="_blank">
                                    <span class="dashicons dashicons-external" style="margin-top: 3px;"></span>
                                    <?php esc_html_e( 'Services', 'easy-status-check' ); ?>
                                </a>
                                <a href="<?php echo home_url( '/' . $public_status_slug . '/incidents' ); ?>" class="button button-secondary" target="_blank">
                                    <span class="dashicons dashicons-external" style="margin-top: 3px;"></span>
                                    <?php esc_html_e( 'Incidents', 'easy-status-check' ); ?>
                                </a>
                            </div>
                        <?php else : ?>
                            <span class="description"><?php esc_html_e( 'Public Pages sind deaktiviert. Aktivieren Sie sie oben.', 'easy-status-check' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Standard Prüfintervall', 'easy-status-check' ); ?></th>
                    <td>
                        <select name="default_interval" class="regular-text">
                            <option value="300" <?php selected( $general_settings['default_interval'], 300 ); ?>>5 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                            <option value="600" <?php selected( $general_settings['default_interval'], 600 ); ?>>10 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                            <option value="1800" <?php selected( $general_settings['default_interval'], 1800 ); ?>>30 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                            <option value="3600" <?php selected( $general_settings['default_interval'], 3600 ); ?>>60 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Wie oft sollen Services standardmäßig geprüft werden?', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Standard Timeout', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="number" name="default_timeout" value="<?php echo esc_attr( $general_settings['default_timeout'] ); ?>" min="1" max="60" class="small-text"> <?php esc_html_e( 'Sekunden', 'easy-status-check' ); ?>
                        <p class="description"><?php esc_html_e( 'Maximale Wartezeit für HTTP-Anfragen (1-60 Sekunden)', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
            </table>
            
        <?php elseif ( $active_tab === 'notifications' ) : ?>
            <!-- BENACHRICHTIGUNGEN TAB -->
            <h2><?php esc_html_e( 'Benachrichtigungs-Einstellungen', 'easy-status-check' ); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'E-Mail Benachrichtigungen', 'easy-status-check' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="notifications_enabled" value="1" <?php checked( $notification_settings['enabled'] ); ?>>
                            <?php esc_html_e( 'E-Mail Benachrichtigungen bei Statusänderungen aktivieren', 'easy-status-check' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Erhalten Sie E-Mails wenn Services offline gehen oder wieder online kommen', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Benachrichtigungs E-Mail', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="email" name="notification_email" value="<?php echo esc_attr( $notification_settings['email'] ); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e( 'E-Mail Adresse für Benachrichtigungen', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
            </table>
            
        <?php elseif ( $active_tab === 'design' ) : ?>
            <!-- DESIGN TAB -->
            <h2><?php esc_html_e( 'Design-Einstellungen für Public Pages', 'easy-status-check' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Passen Sie das Aussehen der öffentlichen Status-Seiten an.', 'easy-status-check' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Primärfarbe', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="color" name="primary_color" value="<?php echo esc_attr( $public_settings['primary_color'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Hauptfarbe für Buttons und Akzente', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Erfolgsfarbe (Online)', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="color" name="success_color" value="<?php echo esc_attr( $public_settings['success_color'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Farbe für Online-Status', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Warnfarbe', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="color" name="warning_color" value="<?php echo esc_attr( $public_settings['warning_color'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Farbe für Warnungen', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Fehlerfarbe (Offline)', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="color" name="error_color" value="<?php echo esc_attr( $public_settings['error_color'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Farbe für Offline-Status', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Hintergrundfarbe', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="color" name="background_color" value="<?php echo esc_attr( $public_settings['background_color'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Hintergrundfarbe der Seite', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Textfarbe', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="color" name="text_color" value="<?php echo esc_attr( $public_settings['text_color'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Haupttextfarbe', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Anzeigeoptionen', 'easy-status-check' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="show_response_time" value="1" <?php checked( $public_settings['show_response_time'] ); ?>>
                            <?php esc_html_e( 'Antwortzeiten anzeigen', 'easy-status-check' ); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="show_uptime" value="1" <?php checked( $public_settings['show_uptime'] ); ?>>
                            <?php esc_html_e( 'Uptime-Statistiken anzeigen', 'easy-status-check' ); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Auto-Refresh Intervall', 'easy-status-check' ); ?></th>
                    <td>
                        <select name="refresh_interval" class="regular-text">
                            <option value="0" <?php selected( $public_settings['refresh_interval'], 0 ); ?>><?php esc_html_e( 'Deaktiviert', 'easy-status-check' ); ?></option>
                            <option value="60" <?php selected( $public_settings['refresh_interval'], 60 ); ?>>1 <?php esc_html_e( 'Minute', 'easy-status-check' ); ?></option>
                            <option value="300" <?php selected( $public_settings['refresh_interval'], 300 ); ?>>5 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                            <option value="600" <?php selected( $public_settings['refresh_interval'], 600 ); ?>>10 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Wie oft soll die öffentliche Seite automatisch aktualisiert werden?', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
            </table>
            
        <?php elseif ( $active_tab === 'support' ) : ?>
            <!-- SUPPORT TAB -->
            <h2><?php esc_html_e( 'Support & System-Status', 'easy-status-check' ); ?></h2>
            
            <style>
                .esc-support-grid {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 20px;
                    margin: 20px 0;
                }
                .esc-support-card {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    border-radius: 8px;
                    padding: 20px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                }
                .esc-support-card h3 {
                    margin: 0 0 15px 0;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #2271b1;
                    color: #2271b1;
                }
                .esc-support-card .button {
                    margin: 5px 5px 5px 0;
                }
                .esc-status-item {
                    padding: 8px 0;
                    border-bottom: 1px solid #f0f0f1;
                }
                .esc-status-item:last-child {
                    border-bottom: none;
                }
                .esc-status-ok { color: #00a32a; font-weight: bold; }
                .esc-status-warning { color: #f0b849; font-weight: bold; }
                .esc-status-error { color: #d63638; font-weight: bold; }
                @media (max-width: 1200px) {
                    .esc-support-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
            
            <div class="esc-support-grid">
                <!-- SYSTEM CARD -->
                <div class="esc-support-card">
                    <h3><?php esc_html_e( 'System-Status', 'easy-status-check' ); ?></h3>
                    
                    <div class="esc-status-item">
                        <strong>WordPress:</strong> <?php echo get_bloginfo( 'version' ); ?>
                    </div>
                    <div class="esc-status-item">
                        <strong>PHP:</strong> <?php echo PHP_VERSION; ?>
                    </div>
                    <div class="esc-status-item">
                        <strong>MySQL:</strong> <?php global $wpdb; echo $wpdb->db_version(); ?>
                    </div>
                    <div class="esc-status-item">
                        <strong>cURL:</strong> 
                        <?php if ( function_exists( 'curl_version' ) ) : ?>
                            <span class="esc-status-ok">✓ Aktiv</span>
                        <?php else : ?>
                            <span class="esc-status-error">✗ Nicht verfügbar</span>
                        <?php endif; ?>
                    </div>
                    <div class="esc-status-item">
                        <strong>Plugin Version:</strong> 1.0.0
                    </div>
                </div>
            
                <!-- DATENBANK CARD -->
                <div class="esc-support-card">
                    <h3><?php esc_html_e( 'Datenbank-Status', 'easy-status-check' ); ?></h3>
                    
                    <?php
                    $tables = array(
                        'esc_services' => __( 'Services', 'easy-status-check' ),
                        'esc_status_logs' => __( 'Status-Logs', 'easy-status-check' ),
                        'esc_incidents' => __( 'Incidents', 'easy-status-check' ),
                        'esc_notifications' => __( 'Benachrichtigungen', 'easy-status-check' )
                    );
                    $all_exist = true;
                    $total_entries = 0;
                    foreach ( $tables as $table => $label ) :
                        $table_name = $wpdb->prefix . $table;
                        $exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;
                        $count = $exists ? $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ) : 0;
                        $total_entries += $count;
                        if ( ! $exists ) $all_exist = false;
                    ?>
                        <div class="esc-status-item">
                            <strong><?php echo esc_html( $label ); ?>:</strong>
                            <?php if ( $exists ) : ?>
                                <span class="esc-status-ok">✓</span> <?php echo number_format_i18n( $count ); ?> Einträge
                            <?php else : ?>
                                <span class="esc-status-error">✗ Fehlt</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f1;">
                        <strong><?php esc_html_e( 'Aktionen:', 'easy-status-check' ); ?></strong><br>
                        <button type="button" class="button" id="esc-db-check">
                            <span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Prüfen', 'easy-status-check' ); ?>
                        </button>
                        <button type="button" class="button" id="esc-db-create">
                            <span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Erstellen', 'easy-status-check' ); ?>
                        </button>
                        <button type="button" class="button" id="esc-db-optimize">
                            <span class="dashicons dashicons-performance"></span> <?php esc_html_e( 'Optimieren', 'easy-status-check' ); ?>
                        </button>
                        <button type="button" class="button" id="esc-db-repair">
                            <span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Reparieren', 'easy-status-check' ); ?>
                        </button>
                    </div>
                    <div id="esc-db-result" style="margin-top: 10px;"></div>
                </div>
            
                <!-- CRON CARD -->
                <div class="esc-support-card">
                    <h3><?php esc_html_e( 'Cron-Status', 'easy-status-check' ); ?></h3>
                    
                    <div class="esc-status-item">
                        <strong>WordPress Cron:</strong>
                        <?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) : ?>
                            <span class="esc-status-warning">⚠ Deaktiviert</span>
                        <?php else : ?>
                            <span class="esc-status-ok">✓ Aktiv</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php
                    $cron_jobs = _get_cron_array();
                    $esc_crons = array();
                    foreach ( $cron_jobs as $timestamp => $cron ) {
                        foreach ( $cron as $hook => $details ) {
                            if ( strpos( $hook, 'esc_' ) === 0 ) {
                                $esc_crons[] = array(
                                    'hook' => $hook,
                                    'timestamp' => $timestamp,
                                    'next_run' => human_time_diff( $timestamp )
                                );
                            }
                        }
                    }
                    ?>
                    
                    <div class="esc-status-item">
                        <strong>Geplante Jobs:</strong> <?php echo count( $esc_crons ); ?>
                    </div>
                    
                    <?php if ( ! empty( $esc_crons ) ) : ?>
                        <?php foreach ( array_slice( $esc_crons, 0, 3 ) as $cron ) : ?>
                            <div class="esc-status-item">
                                <code style="font-size: 11px;"><?php echo esc_html( $cron['hook'] ); ?></code><br>
                                <small>in <?php echo esc_html( $cron['next_run'] ); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f1;">
                        <strong><?php esc_html_e( 'Aktionen:', 'easy-status-check' ); ?></strong><br>
                        <button type="button" class="button" id="esc-cron-check">
                            <span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Prüfen', 'easy-status-check' ); ?>
                        </button>
                        <button type="button" class="button" id="esc-cron-run">
                            <span class="dashicons dashicons-controls-play"></span> <?php esc_html_e( 'Manuell ausführen', 'easy-status-check' ); ?>
                        </button>
                    </div>
                    <div id="esc-cron-result" style="margin-top: 10px;"></div>
                </div>
            </div>
            
        <?php endif; ?>
        
        <?php if ( $active_tab !== 'support' ) : ?>
            <?php submit_button( __( 'Einstellungen speichern', 'easy-status-check' ) ); ?>
        <?php else : ?>
            <!-- JavaScript für Support-Tab Buttons -->
            <script>
            jQuery(document).ready(function($) {
                // Datenbank Prüfen
                $('#esc-db-check').on('click', function() {
                    var btn = $(this);
                    btn.prop('disabled', true);
                    $('#esc-db-result').html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.post(ajaxurl, {
                        action: 'esc_db_check',
                        nonce: '<?php echo wp_create_nonce( 'esc_db_action' ); ?>'
                    }, function(response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            $('#esc-db-result').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#esc-db-result').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                        setTimeout(function() { location.reload(); }, 2000);
                    });
                });
                
                // Datenbank Erstellen
                $('#esc-db-create').on('click', function() {
                    if (!confirm('<?php esc_html_e( 'Möchten Sie fehlende Datenbank-Tabellen erstellen?', 'easy-status-check' ); ?>')) return;
                    
                    var btn = $(this);
                    btn.prop('disabled', true);
                    $('#esc-db-result').html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.post(ajaxurl, {
                        action: 'esc_db_create',
                        nonce: '<?php echo wp_create_nonce( 'esc_db_action' ); ?>'
                    }, function(response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            $('#esc-db-result').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#esc-db-result').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                        setTimeout(function() { location.reload(); }, 2000);
                    });
                });
                
                // Datenbank Optimieren
                $('#esc-db-optimize').on('click', function() {
                    var btn = $(this);
                    btn.prop('disabled', true);
                    $('#esc-db-result').html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.post(ajaxurl, {
                        action: 'esc_db_optimize',
                        nonce: '<?php echo wp_create_nonce( 'esc_db_action' ); ?>'
                    }, function(response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            $('#esc-db-result').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#esc-db-result').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    });
                });
                
                // Datenbank Reparieren
                $('#esc-db-repair').on('click', function() {
                    if (!confirm('<?php esc_html_e( 'Möchten Sie die Datenbank-Tabellen reparieren?', 'easy-status-check' ); ?>')) return;
                    
                    var btn = $(this);
                    btn.prop('disabled', true);
                    $('#esc-db-result').html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.post(ajaxurl, {
                        action: 'esc_db_repair',
                        nonce: '<?php echo wp_create_nonce( 'esc_db_action' ); ?>'
                    }, function(response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            $('#esc-db-result').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#esc-db-result').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    });
                });
                
                // Cron Prüfen
                $('#esc-cron-check').on('click', function() {
                    var btn = $(this);
                    btn.prop('disabled', true);
                    $('#esc-cron-result').html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.post(ajaxurl, {
                        action: 'esc_cron_check',
                        nonce: '<?php echo wp_create_nonce( 'esc_cron_action' ); ?>'
                    }, function(response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            $('#esc-cron-result').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#esc-cron-result').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                        setTimeout(function() { location.reload(); }, 2000);
                    });
                });
                
                // Cron Manuell ausführen
                $('#esc-cron-run').on('click', function() {
                    if (!confirm('<?php esc_html_e( 'Möchten Sie alle Service-Checks jetzt manuell ausführen?', 'easy-status-check' ); ?>')) return;
                    
                    var btn = $(this);
                    btn.prop('disabled', true);
                    $('#esc-cron-result').html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.post(ajaxurl, {
                        action: 'esc_cron_run',
                        nonce: '<?php echo wp_create_nonce( 'esc_cron_action' ); ?>'
                    }, function(response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            $('#esc-cron-result').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#esc-cron-result').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    });
                });
            });
            </script>
        <?php endif; ?>
    </form>
</div>
