<?php
/**
 * Public Status Page with CVE RSS Feed Integration
 *
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Public_Status_Page {

    public function __construct() {
        add_action( 'init', array( $this, 'register_status_page' ) );
        add_action( 'template_redirect', array( $this, 'handle_status_page' ) );
        add_shortcode( 'esc_public_status', array( $this, 'render_status_shortcode' ) );
        add_action( 'wp_ajax_nopriv_esc_get_cve_feeds', array( $this, 'ajax_get_cve_feeds' ) );
        add_action( 'wp_ajax_esc_get_cve_feeds', array( $this, 'ajax_get_cve_feeds' ) );
    }

    /**
     * Register virtual status page
     */
    public function register_status_page() {
        $enabled = get_option( 'esc_public_status_enabled', false );
        
        if ( ! $enabled ) {
            return;
        }
        
        $slug = get_option( 'esc_public_status_slug', 'status' );
        
        add_rewrite_rule(
            '^' . $slug . '/?$',
            'index.php?esc_status_page=1',
            'top'
        );
        
        add_rewrite_tag( '%esc_status_page%', '([^&]+)' );
    }

    /**
     * Handle status page display
     */
    public function handle_status_page() {
        if ( ! get_query_var( 'esc_status_page' ) ) {
            return;
        }
        
        $this->render_public_status_page();
        exit;
    }

    /**
     * Render public status page
     */
    public function render_public_status_page() {
        $show_cve = get_option( 'esc_public_status_show_cve', true );
        $cve_feeds = get_option( 'esc_cve_feeds', array() );
        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html( get_option( 'esc_public_status_title', __( 'System Status', 'easy-status-check' ) ) ); ?></title>
            <?php wp_head(); ?>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: #f5f7fa; color: #333; line-height: 1.6; }
                .status-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
                .status-header { text-align: center; margin-bottom: 50px; }
                .status-header h1 { font-size: 36px; margin-bottom: 10px; color: #1a1a1a; }
                .status-header p { color: #666; font-size: 18px; }
                .overall-status { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 40px; text-align: center; }
                .overall-status.operational { border-top: 4px solid #28a745; }
                .overall-status.issues { border-top: 4px solid #ffc107; }
                .overall-status.down { border-top: 4px solid #dc3545; }
                .overall-status h2 { font-size: 28px; margin-bottom: 10px; }
                .overall-status .status-icon { font-size: 48px; margin-bottom: 15px; }
                .services-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-bottom: 40px; }
                .service-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #ddd; }
                .service-card.online { border-left-color: #28a745; }
                .service-card.offline { border-left-color: #dc3545; }
                .service-card.warning { border-left-color: #ffc107; }
                .service-card h3 { font-size: 18px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
                .service-card .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
                .service-card .status-badge.online { background: #d4edda; color: #155724; }
                .service-card .status-badge.offline { background: #f8d7da; color: #721c24; }
                .service-card .status-badge.warning { background: #fff3cd; color: #856404; }
                .service-meta { font-size: 14px; color: #666; margin-top: 10px; }
                .cve-section { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 40px; }
                .cve-section h2 { font-size: 24px; margin-bottom: 20px; color: #1a1a1a; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
                .cve-feed { margin-bottom: 30px; }
                .cve-feed h3 { font-size: 18px; margin-bottom: 15px; color: #495057; }
                .cve-item { padding: 15px; background: #f8f9fa; border-left: 3px solid #dc3545; margin-bottom: 10px; border-radius: 4px; }
                .cve-item h4 { font-size: 16px; margin-bottom: 5px; color: #dc3545; }
                .cve-item .cve-date { font-size: 12px; color: #6c757d; margin-bottom: 8px; }
                .cve-item p { font-size: 14px; color: #495057; }
                .footer { text-align: center; margin-top: 60px; padding-top: 30px; border-top: 1px solid #e9ecef; color: #6c757d; font-size: 14px; }
                @media (max-width: 768px) { .services-grid { grid-template-columns: 1fr; } }
            </style>
        </head>
        <body>
            <div class="status-container">
                <div class="status-header">
                    <h1><?php echo esc_html( get_option( 'esc_public_status_title', __( 'System Status', 'easy-status-check' ) ) ); ?></h1>
                    <p><?php echo esc_html( get_option( 'esc_public_status_description', __( 'Aktuelle Status-Informationen unserer Services', 'easy-status-check' ) ) ); ?></p>
                </div>

                <?php
                global $wpdb;
                $services_table = $wpdb->prefix . 'esc_services';
                $logs_table = $wpdb->prefix . 'esc_status_logs';
                
                $services = $wpdb->get_results( "
                    SELECT 
                        s.*,
                        l.status as current_status,
                        l.response_time,
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
                
                $total = count( $services );
                $online = 0;
                $offline = 0;
                $warning = 0;
                
                foreach ( $services as $service ) {
                    if ( $service->current_status === 'online' ) $online++;
                    elseif ( $service->current_status === 'offline' ) $offline++;
                    elseif ( $service->current_status === 'warning' ) $warning++;
                }
                
                $overall_class = 'operational';
                $overall_icon = '✓';
                $overall_text = __( 'Alle Systeme funktionieren', 'easy-status-check' );
                
                if ( $offline > 0 ) {
                    $overall_class = 'down';
                    $overall_icon = '✗';
                    $overall_text = sprintf( __( '%d Service(s) ausgefallen', 'easy-status-check' ), $offline );
                } elseif ( $warning > 0 ) {
                    $overall_class = 'issues';
                    $overall_icon = '⚠';
                    $overall_text = sprintf( __( '%d Service(s) mit Problemen', 'easy-status-check' ), $warning );
                }
                ?>

                <div class="overall-status <?php echo esc_attr( $overall_class ); ?>">
                    <div class="status-icon"><?php echo $overall_icon; ?></div>
                    <h2><?php echo esc_html( $overall_text ); ?></h2>
                    <p><?php printf( __( '%d von %d Services online', 'easy-status-check' ), $online, $total ); ?></p>
                </div>

                <div class="services-grid">
                    <?php foreach ( $services as $service ) : ?>
                        <?php
                        $status_class = $service->current_status ?: 'unknown';
                        $status_labels = array(
                            'online' => __( 'Online', 'easy-status-check' ),
                            'offline' => __( 'Offline', 'easy-status-check' ),
                            'warning' => __( 'Warnung', 'easy-status-check' ),
                            'unknown' => __( 'Unbekannt', 'easy-status-check' ),
                        );
                        ?>
                        <div class="service-card <?php echo esc_attr( $status_class ); ?>">
                            <h3>
                                <?php echo esc_html( $service->name ); ?>
                                <span class="status-badge <?php echo esc_attr( $status_class ); ?>">
                                    <?php echo esc_html( $status_labels[ $status_class ] ); ?>
                                </span>
                            </h3>
                            <div class="service-meta">
                                <?php if ( $service->response_time ) : ?>
                                    <div><?php printf( __( 'Antwortzeit: %s ms', 'easy-status-check' ), esc_html( round( $service->response_time, 2 ) ) ); ?></div>
                                <?php endif; ?>
                                <?php if ( $service->last_checked ) : ?>
                                    <div><?php printf( __( 'Letzte Prüfung: %s', 'easy-status-check' ), esc_html( human_time_diff( strtotime( $service->last_checked ), current_time( 'timestamp' ) ) ) . ' ' . __( 'her', 'easy-status-check' ) ); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ( $show_cve && ! empty( $cve_feeds ) ) : ?>
                    <div class="cve-section">
                        <h2><?php _e( 'Sicherheitswarnungen (CVE)', 'easy-status-check' ); ?></h2>
                        <?php foreach ( $cve_feeds as $feed ) : ?>
                            <?php $items = $this->fetch_cve_feed( $feed['url'] ); ?>
                            <?php if ( ! empty( $items ) ) : ?>
                                <div class="cve-feed">
                                    <h3><?php echo esc_html( $feed['name'] ); ?></h3>
                                    <?php foreach ( array_slice( $items, 0, 5 ) as $item ) : ?>
                                        <div class="cve-item">
                                            <h4><?php echo esc_html( $item['title'] ); ?></h4>
                                            <div class="cve-date"><?php echo esc_html( $item['date'] ); ?></div>
                                            <p><?php echo esc_html( wp_trim_words( $item['description'], 30 ) ); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="footer">
                    <p><?php printf( __( 'Letzte Aktualisierung: %s', 'easy-status-check' ), current_time( 'Y-m-d H:i:s' ) ); ?></p>
                    <p><?php printf( __( 'Powered by %s', 'easy-status-check' ), '<strong>easySTATUSCheck</strong>' ); ?></p>
                </div>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * Fetch CVE RSS feed
     */
    private function fetch_cve_feed( $url ) {
        $cache_key = 'esc_cve_feed_' . md5( $url );
        $cached = get_transient( $cache_key );
        
        if ( $cached !== false ) {
            return $cached;
        }
        
        $response = wp_remote_get( $url, array( 'timeout' => 15 ) );
        
        if ( is_wp_error( $response ) ) {
            return array();
        }
        
        $body = wp_remote_retrieve_body( $response );
        
        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $body );
        
        if ( $xml === false ) {
            return array();
        }
        
        $items = array();
        
        foreach ( $xml->channel->item as $item ) {
            $items[] = array(
                'title' => (string) $item->title,
                'description' => (string) $item->description,
                'link' => (string) $item->link,
                'date' => (string) $item->pubDate,
            );
        }
        
        set_transient( $cache_key, $items, HOUR_IN_SECONDS );
        
        return $items;
    }

    /**
     * Render status shortcode
     */
    public function render_status_shortcode( $atts ) {
        ob_start();
        $this->render_public_status_page();
        return ob_get_clean();
    }

    /**
     * AJAX: Get CVE feeds
     */
    public function ajax_get_cve_feeds() {
        $feeds = get_option( 'esc_cve_feeds', array() );
        $results = array();
        
        foreach ( $feeds as $feed ) {
            $items = $this->fetch_cve_feed( $feed['url'] );
            $results[ $feed['name'] ] = array_slice( $items, 0, 5 );
        }
        
        wp_send_json_success( $results );
    }
}
