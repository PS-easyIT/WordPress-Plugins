<?php
/**
 * Public Pages - Services, Incidents, History
 *
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Public_Pages {

    public function __construct() {
        add_action( 'init', array( $this, 'register_public_pages' ) );
        add_action( 'template_redirect', array( $this, 'handle_public_pages' ) );
    }

    /**
     * Register all public pages
     */
    public function register_public_pages() {
        $enabled = get_option( 'esc_public_status_enabled', false );
        
        if ( ! $enabled ) {
            return;
        }
        
        $base_slug = get_option( 'esc_public_status_slug', 'status' );
        
        // Public Services Status Page
        add_rewrite_rule(
            '^' . $base_slug . '/services/?$',
            'index.php?esc_public_page=services',
            'top'
        );
        
        // Public Incidents/CVE Page
        add_rewrite_rule(
            '^' . $base_slug . '/incidents/?$',
            'index.php?esc_public_page=incidents',
            'top'
        );
        
        // Public History Page with service parameter
        add_rewrite_rule(
            '^' . $base_slug . '/history/([0-9]+)/?$',
            'index.php?esc_public_page=history&service_id=$matches[1]',
            'top'
        );
        
        // Main status page (redirect to services)
        add_rewrite_rule(
            '^' . $base_slug . '/?$',
            'index.php?esc_public_page=services',
            'top'
        );
        
        add_rewrite_tag( '%esc_public_page%', '([^&]+)' );
        add_rewrite_tag( '%service_id%', '([0-9]+)' );
    }

    /**
     * Handle public pages display
     */
    public function handle_public_pages() {
        $page = get_query_var( 'esc_public_page' );
        
        if ( ! $page ) {
            return;
        }
        
        // Check if public status is enabled
        $enabled = get_option( 'esc_public_status_enabled', false );
        if ( ! $enabled ) {
            return;
        }
        
        // Load template file
        $template_file = '';
        
        switch ( $page ) {
            case 'services':
                $template_file = 'public-services.php';
                break;
            case 'incidents':
                $template_file = 'public-incidents.php';
                break;
            case 'history':
                $template_file = 'public-history.php';
                break;
            default:
                return;
        }
        
        // Load the template
        $template_path = EASY_STATUS_CHECK_DIR . 'templates/' . $template_file;
        
        if ( file_exists( $template_path ) ) {
            include $template_path;
            exit;
        }
        
        // Fallback if template not found
        wp_die( __( 'Template nicht gefunden.', 'easy-status-check' ) );
    }

    /**
     * Legacy method - now using template files
     * @deprecated Use templates/public-services.php instead
     */
    private function render_services_page_legacy() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        
        // Get public settings
        $public_settings = get_option( 'esc_public_settings', array(
            'primary_color' => '#2271b1',
            'success_color' => '#00a32a',
            'warning_color' => '#f0b849',
            'error_color' => '#d63638',
            'background_color' => '#f0f0f1',
            'text_color' => '#1d2327',
            'show_response_time' => true,
            'show_uptime' => true,
            'columns' => 3
        ) );
        
        $columns = get_option( 'esc_public_services_columns', 3 );
        
        // Get services with latest status
        $services = $wpdb->get_results( "
            SELECT 
                s.*,
                l.status as current_status,
                l.response_time,
                l.http_code,
                l.checked_at as last_checked
            FROM $services_table s
            LEFT JOIN $logs_table l ON s.id = l.service_id
            LEFT JOIN (
                SELECT service_id, MAX(checked_at) as max_checked
                FROM $logs_table
                GROUP BY service_id
            ) latest ON s.id = latest.service_id AND l.checked_at = latest.max_checked
            WHERE s.enabled = 1
            ORDER BY s.category, s.name
        " );
        
        $base_slug = get_option( 'esc_public_status_slug', 'status' );
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html( get_option( 'esc_public_status_title', __( 'Service Status', 'easy-status-check' ) ) ); ?></title>
            <?php wp_head(); ?>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; 
                    background: <?php echo esc_attr( $public_settings['background_color'] ); ?>; 
                    color: <?php echo esc_attr( $public_settings['text_color'] ); ?>; 
                    line-height: 1.6; 
                }
                .status-container { max-width: 1400px; margin: 0 auto; padding: 40px 20px; }
                .status-header { text-align: center; margin-bottom: 40px; }
                .status-header h1 { font-size: 36px; margin-bottom: 10px; }
                .status-nav { display: flex; justify-content: center; gap: 20px; margin-bottom: 40px; }
                .status-nav a { 
                    padding: 10px 20px; 
                    background: #fff; 
                    border-radius: 6px; 
                    text-decoration: none; 
                    color: <?php echo esc_attr( $public_settings['text_color'] ); ?>; 
                    border: 2px solid transparent;
                }
                .status-nav a.active { 
                    border-color: <?php echo esc_attr( $public_settings['primary_color'] ); ?>; 
                    background: <?php echo esc_attr( $public_settings['primary_color'] ); ?>; 
                    color: #fff; 
                }
                .services-grid { 
                    display: grid; 
                    grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr); 
                    gap: 20px; 
                }
                .service-card { 
                    background: #fff; 
                    padding: 25px; 
                    border-radius: 8px; 
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
                    border-left: 5px solid #ddd;
                    transition: transform 0.2s, box-shadow 0.2s;
                }
                .service-card:hover { 
                    transform: translateY(-2px); 
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
                }
                .service-card.online { border-left-color: <?php echo esc_attr( $public_settings['success_color'] ); ?>; }
                .service-card.offline { border-left-color: <?php echo esc_attr( $public_settings['error_color'] ); ?>; }
                .service-card.warning { border-left-color: <?php echo esc_attr( $public_settings['warning_color'] ); ?>; }
                .service-card h3 { font-size: 20px; margin-bottom: 15px; }
                .service-status { 
                    display: inline-flex; 
                    align-items: center; 
                    gap: 8px; 
                    padding: 6px 12px; 
                    border-radius: 4px; 
                    font-size: 14px; 
                    font-weight: 600; 
                    margin-bottom: 15px;
                }
                .service-status.online { background: <?php echo esc_attr( $public_settings['success_color'] ); ?>22; color: <?php echo esc_attr( $public_settings['success_color'] ); ?>; }
                .service-status.offline { background: <?php echo esc_attr( $public_settings['error_color'] ); ?>22; color: <?php echo esc_attr( $public_settings['error_color'] ); ?>; }
                .service-status.warning { background: <?php echo esc_attr( $public_settings['warning_color'] ); ?>22; color: <?php echo esc_attr( $public_settings['warning_color'] ); ?>; }
                .service-status-dot { 
                    width: 10px; 
                    height: 10px; 
                    border-radius: 50%; 
                    background: currentColor; 
                }
                .service-meta { 
                    display: flex; 
                    gap: 20px; 
                    font-size: 14px; 
                    color: #666; 
                    margin-bottom: 15px;
                }
                .service-meta-item { display: flex; align-items: center; gap: 5px; }
                .service-url { 
                    font-size: 13px; 
                    color: #999; 
                    margin-bottom: 15px; 
                    word-break: break-all;
                }
                .service-actions { display: flex; gap: 10px; }
                .service-actions a { 
                    padding: 8px 16px; 
                    background: <?php echo esc_attr( $public_settings['primary_color'] ); ?>; 
                    color: #fff; 
                    text-decoration: none; 
                    border-radius: 4px; 
                    font-size: 14px;
                    transition: opacity 0.2s;
                }
                .service-actions a:hover { opacity: 0.8; }
                @media (max-width: 1200px) { .services-grid { grid-template-columns: repeat(2, 1fr); } }
                @media (max-width: 768px) { .services-grid { grid-template-columns: 1fr; } }
            </style>
        </head>
        <body>
            <div class="status-container">
                <div class="status-header">
                    <h1><?php echo esc_html( get_option( 'esc_public_status_title', __( 'Service Status', 'easy-status-check' ) ) ); ?></h1>
                    <p><?php echo esc_html( get_option( 'esc_public_status_description', __( 'Aktuelle Status-Informationen unserer Services', 'easy-status-check' ) ) ); ?></p>
                </div>

                <div class="status-nav">
                    <a href="<?php echo home_url( '/' . $base_slug . '/services' ); ?>" class="active"><?php _e( 'Services', 'easy-status-check' ); ?></a>
                    <a href="<?php echo home_url( '/' . $base_slug . '/incidents' ); ?>"><?php _e( 'Incidents', 'easy-status-check' ); ?></a>
                </div>

                <div class="services-grid">
                    <?php foreach ( $services as $service ) : 
                        $status = $service->current_status ?? 'unknown';
                        $status_text = array(
                            'online' => __( 'Online', 'easy-status-check' ),
                            'offline' => __( 'Offline', 'easy-status-check' ),
                            'warning' => __( 'Warnung', 'easy-status-check' ),
                            'unknown' => __( 'Unbekannt', 'easy-status-check' )
                        );
                    ?>
                        <div class="service-card <?php echo esc_attr( $status ); ?>">
                            <h3><?php echo esc_html( $service->name ); ?></h3>
                            
                            <div class="service-status <?php echo esc_attr( $status ); ?>">
                                <span class="service-status-dot"></span>
                                <?php echo esc_html( $status_text[$status] ); ?>
                            </div>
                            
                            <?php if ( $public_settings['show_response_time'] || $public_settings['show_uptime'] ) : ?>
                                <div class="service-meta">
                                    <?php if ( $public_settings['show_response_time'] && $service->response_time ) : ?>
                                        <div class="service-meta-item">
                                            <span>⚡</span>
                                            <span><?php echo round( $service->response_time ); ?>ms</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ( $service->last_checked ) : ?>
                                        <div class="service-meta-item">
                                            <span>🕐</span>
                                            <span><?php echo human_time_diff( strtotime( $service->last_checked ), current_time( 'timestamp' ) ); ?> <?php _e( 'her', 'easy-status-check' ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="service-url"><?php echo esc_html( $service->url ); ?></div>
                            
                            <div class="service-actions">
                                <a href="<?php echo home_url( '/' . $base_slug . '/history/' . $service->id ); ?>"><?php _e( 'History', 'easy-status-check' ); ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * Render Public Incidents/CVE Page
     */
    private function render_incidents_page() {
        $cve_feeds = get_option( 'esc_cve_feeds', array() );
        $max_items = get_option( 'esc_public_cve_max_items', 10 );
        $base_slug = get_option( 'esc_public_status_slug', 'status' );
        
        $public_settings = get_option( 'esc_public_settings', array(
            'primary_color' => '#2271b1',
            'error_color' => '#d63638',
            'background_color' => '#f0f0f1',
            'text_color' => '#1d2327'
        ) );
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php _e( 'Security Incidents', 'easy-status-check' ); ?> - <?php bloginfo( 'name' ); ?></title>
            <?php wp_head(); ?>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; 
                    background: <?php echo esc_attr( $public_settings['background_color'] ); ?>; 
                    color: <?php echo esc_attr( $public_settings['text_color'] ); ?>; 
                    line-height: 1.6; 
                }
                .status-container { max-width: 1400px; margin: 0 auto; padding: 40px 20px; }
                .status-header { text-align: center; margin-bottom: 40px; }
                .status-header h1 { font-size: 36px; margin-bottom: 10px; }
                .status-nav { display: flex; justify-content: center; gap: 20px; margin-bottom: 40px; }
                .status-nav a { 
                    padding: 10px 20px; 
                    background: #fff; 
                    border-radius: 6px; 
                    text-decoration: none; 
                    color: <?php echo esc_attr( $public_settings['text_color'] ); ?>; 
                    border: 2px solid transparent;
                }
                .status-nav a.active { 
                    border-color: <?php echo esc_attr( $public_settings['primary_color'] ); ?>; 
                    background: <?php echo esc_attr( $public_settings['primary_color'] ); ?>; 
                    color: #fff; 
                }
                .cve-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
                .cve-feed-section { margin-bottom: 40px; }
                .cve-feed-section h2 { margin-bottom: 20px; font-size: 24px; }
                .cve-card { 
                    background: #fff; 
                    padding: 20px; 
                    border-radius: 8px; 
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
                    border-left: 4px solid <?php echo esc_attr( $public_settings['error_color'] ); ?>;
                }
                .cve-card h3 { font-size: 18px; margin-bottom: 10px; color: <?php echo esc_attr( $public_settings['error_color'] ); ?>; }
                .cve-date { font-size: 13px; color: #999; margin-bottom: 10px; }
                .cve-description { font-size: 14px; color: #666; }
                @media (max-width: 768px) { .cve-grid { grid-template-columns: 1fr; } }
            </style>
        </head>
        <body>
            <div class="status-container">
                <div class="status-header">
                    <h1><?php _e( 'Security Incidents & CVE Feeds', 'easy-status-check' ); ?></h1>
                    <p><?php _e( 'Aktuelle Sicherheitsvorfälle und Schwachstellen', 'easy-status-check' ); ?></p>
                </div>

                <div class="status-nav">
                    <a href="<?php echo home_url( '/' . $base_slug . '/services' ); ?>"><?php _e( 'Services', 'easy-status-check' ); ?></a>
                    <a href="<?php echo home_url( '/' . $base_slug . '/incidents' ); ?>" class="active"><?php _e( 'Incidents', 'easy-status-check' ); ?></a>
                </div>

                <?php foreach ( $cve_feeds as $feed ) : 
                    $feed_data = $this->fetch_cve_feed( $feed['url'], $max_items );
                ?>
                    <div class="cve-feed-section">
                        <h2><?php echo esc_html( $feed['name'] ); ?></h2>
                        <div class="cve-grid">
                            <?php foreach ( $feed_data as $item ) : ?>
                                <div class="cve-card">
                                    <h3><?php echo esc_html( $item['title'] ); ?></h3>
                                    <div class="cve-date"><?php echo esc_html( $item['date'] ); ?></div>
                                    <div class="cve-description"><?php echo esc_html( wp_trim_words( $item['description'], 30 ) ); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * Render Public History Page
     */
    private function render_history_page() {
        global $wpdb;
        
        $service_id = get_query_var( 'service_id' );
        
        if ( ! $service_id ) {
            wp_die( __( 'Service nicht gefunden.', 'easy-status-check' ) );
        }
        
        $services_table = $wpdb->prefix . 'esc_services';
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        
        $service = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $services_table WHERE id = %d", $service_id ) );
        
        if ( ! $service ) {
            wp_die( __( 'Service nicht gefunden.', 'easy-status-check' ) );
        }
        
        // Get last 100 logs
        $logs = $wpdb->get_results( $wpdb->prepare( "
            SELECT * FROM $logs_table 
            WHERE service_id = %d 
            ORDER BY checked_at DESC 
            LIMIT 100
        ", $service_id ) );
        
        $base_slug = get_option( 'esc_public_status_slug', 'status' );
        
        $public_settings = get_option( 'esc_public_settings', array(
            'primary_color' => '#2271b1',
            'success_color' => '#00a32a',
            'warning_color' => '#f0b849',
            'error_color' => '#d63638',
            'background_color' => '#f0f0f1',
            'text_color' => '#1d2327'
        ) );
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html( $service->name ); ?> - History</title>
            <?php wp_head(); ?>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; 
                    background: <?php echo esc_attr( $public_settings['background_color'] ); ?>; 
                    color: <?php echo esc_attr( $public_settings['text_color'] ); ?>; 
                    line-height: 1.6; 
                }
                .status-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
                .status-header { margin-bottom: 30px; }
                .status-header h1 { font-size: 32px; margin-bottom: 10px; }
                .back-link { 
                    display: inline-block; 
                    margin-bottom: 20px; 
                    color: <?php echo esc_attr( $public_settings['primary_color'] ); ?>; 
                    text-decoration: none;
                }
                .history-table { 
                    background: #fff; 
                    border-radius: 8px; 
                    overflow: hidden; 
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                .history-table table { width: 100%; border-collapse: collapse; }
                .history-table th { 
                    background: #f5f5f5; 
                    padding: 15px; 
                    text-align: left; 
                    font-weight: 600;
                    border-bottom: 2px solid #ddd;
                }
                .history-table td { padding: 12px 15px; border-bottom: 1px solid #eee; }
                .status-badge { 
                    display: inline-block; 
                    padding: 4px 10px; 
                    border-radius: 4px; 
                    font-size: 12px; 
                    font-weight: 600;
                }
                .status-badge.online { background: <?php echo esc_attr( $public_settings['success_color'] ); ?>22; color: <?php echo esc_attr( $public_settings['success_color'] ); ?>; }
                .status-badge.offline { background: <?php echo esc_attr( $public_settings['error_color'] ); ?>22; color: <?php echo esc_attr( $public_settings['error_color'] ); ?>; }
                .status-badge.warning { background: <?php echo esc_attr( $public_settings['warning_color'] ); ?>22; color: <?php echo esc_attr( $public_settings['warning_color'] ); ?>; }
            </style>
        </head>
        <body>
            <div class="status-container">
                <a href="<?php echo home_url( '/' . $base_slug . '/services' ); ?>" class="back-link">← <?php _e( 'Zurück zu Services', 'easy-status-check' ); ?></a>
                
                <div class="status-header">
                    <h1><?php echo esc_html( $service->name ); ?> - History</h1>
                    <p><?php echo esc_html( $service->url ); ?></p>
                </div>

                <div class="history-table">
                    <table>
                        <thead>
                            <tr>
                                <th><?php _e( 'Zeitpunkt', 'easy-status-check' ); ?></th>
                                <th><?php _e( 'Status', 'easy-status-check' ); ?></th>
                                <th><?php _e( 'HTTP-Code', 'easy-status-check' ); ?></th>
                                <th><?php _e( 'Antwortzeit', 'easy-status-check' ); ?></th>
                                <th><?php _e( 'Fehler', 'easy-status-check' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $logs as $log ) : ?>
                                <tr>
                                    <td><?php echo esc_html( date_i18n( 'd.m.Y H:i:s', strtotime( $log->checked_at ) ) ); ?></td>
                                    <td><span class="status-badge <?php echo esc_attr( $log->status ); ?>"><?php echo esc_html( ucfirst( $log->status ) ); ?></span></td>
                                    <td><?php echo esc_html( $log->http_code ?? '-' ); ?></td>
                                    <td><?php echo $log->response_time ? round( $log->response_time ) . 'ms' : '-'; ?></td>
                                    <td><?php echo esc_html( $log->error_message ?? '-' ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * Fetch CVE feed data
     */
    private function fetch_cve_feed( $url, $max_items = 10 ) {
        $transient_key = 'esc_cve_feed_' . md5( $url );
        $cached = get_transient( $transient_key );
        
        if ( $cached !== false ) {
            return array_slice( $cached, 0, $max_items );
        }
        
        $response = wp_remote_get( $url, array( 'timeout' => 15 ) );
        
        if ( is_wp_error( $response ) ) {
            return array();
        }
        
        $body = wp_remote_retrieve_body( $response );
        $xml = simplexml_load_string( $body );
        
        if ( ! $xml ) {
            return array();
        }
        
        $items = array();
        
        foreach ( $xml->channel->item as $item ) {
            $items[] = array(
                'title' => (string) $item->title,
                'description' => strip_tags( (string) $item->description ),
                'date' => date_i18n( 'd.m.Y H:i', strtotime( (string) $item->pubDate ) ),
                'link' => (string) $item->link
            );
            
            if ( count( $items ) >= $max_items ) {
                break;
            }
        }
        
        set_transient( $transient_key, $items, HOUR_IN_SECONDS );
        
        return $items;
    }
}
