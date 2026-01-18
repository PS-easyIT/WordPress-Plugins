<?php
/**
 * Frontend display for easySTATUSCheck
 *
 * @package Easy_Status_Check
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Frontend_Display {

    public function __construct() {
        add_action( 'init', array( $this, 'register_conditional_assets' ) );
        add_action( 'wp_ajax_esc_get_status_data', array( $this, 'ajax_get_status_data' ) );
        add_action( 'wp_ajax_nopriv_esc_get_status_data', array( $this, 'ajax_get_status_data' ) );
    }

    /**
     * Registriert Assets für conditional loading
     */
    public function register_conditional_assets() {
        // Asset Manager verfügbar?
        if ( ! function_exists( 'easy_asset_manager' ) ) {
            // Fallback für ältere Installationen
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts_fallback' ) );
            return;
        }
        
        $asset_manager = easy_asset_manager();
        
        // Design System (nur wenn easySTATUSCheck Shortcode verwendet wird)
        $asset_manager->register_conditional_asset( 'easy-design-system-status', array(
            'type' => 'style',
            'url' => EASY_STATUS_CHECK_URL . '../easy-design-system/easy-design-system.css',
            'deps' => array(),
            'version' => EASY_STATUS_CHECK_VERSION,
            'conditions' => array(
                'shortcodes' => array( 'easy_status_check', 'esc_status_display' ),
                'has_content' => '[easy_status_check',
                'callback' => array( $this, 'should_load_status_assets' )
            ),
            'critical' => true
        ) );
        
        $asset_manager->register_conditional_asset( 'easy-design-system-status-js', array(
            'type' => 'script',
            'url' => EASY_STATUS_CHECK_URL . '../easy-design-system/easy-design-system.js',
            'deps' => array( 'jquery' ),
            'version' => EASY_STATUS_CHECK_VERSION,
            'conditions' => array(
                'shortcodes' => array( 'easy_status_check', 'esc_status_display' ),
                'has_content' => '[easy_status_check',
                'callback' => array( $this, 'should_load_status_assets' )
            ),
            'defer' => true
        ) );
        
        // StatusCheck Frontend Assets
        $asset_manager->register_conditional_asset( 'esc-frontend-css', array(
            'type' => 'style',
            'url' => EASY_STATUS_CHECK_URL . 'assets/css/frontend.css',
            'deps' => array( 'easy-design-system-status' ),
            'version' => EASY_STATUS_CHECK_VERSION,
            'conditions' => array(
                'shortcodes' => array( 'easy_status_check', 'esc_status_display' ),
                'has_content' => '[easy_status_check',
                'callback' => array( $this, 'should_load_status_assets' )
            )
        ) );
        
        $asset_manager->register_conditional_asset( 'esc-frontend-js', array(
            'type' => 'script',
            'url' => EASY_STATUS_CHECK_URL . 'assets/js/frontend.js',
            'deps' => array( 'jquery', 'easy-design-system-status-js' ),
            'version' => EASY_STATUS_CHECK_VERSION,
            'conditions' => array(
                'shortcodes' => array( 'easy_status_check', 'esc_status_display' ),
                'has_content' => '[easy_status_check',
                'callback' => array( $this, 'should_load_status_assets' )
            ),
            'defer' => true
        ) );
    }
    
    /**
     * Prüft, ob Status-Assets geladen werden sollen
     */
    public function should_load_status_assets() {
        // Prüfe aktuelle Seite auf Status-relevante Inhalte
        global $post;
        
        if ( ! $post ) {
            return false;
        }
        
        // Shortcode-Erkennung
        if ( has_shortcode( $post->post_content, 'easy_status_check' ) || 
             has_shortcode( $post->post_content, 'esc_status_display' ) ) {
            return true;
        }
        
        // Widget-Erkennung (falls als Widget verwendet)
        if ( is_active_widget( false, false, 'esc_status_widget' ) ) {
            return true;
        }
        
        // Spezielle Status-Seiten
        if ( $post->post_name === 'server-status' || $post->post_name === 'system-status' ) {
            return true;
        }
        
        return false;
    }

    /**
     * Fallback für ältere Installationen ohne Asset Manager
     */
    public function enqueue_frontend_scripts_fallback() {
        // Nur laden, wenn tatsächlich benötigt
        if ( ! $this->should_load_status_assets() ) {
            return;
        }
        
        wp_enqueue_style(
            'easy-design-system',
            EASY_STATUS_CHECK_URL . '../easy-design-system/easy-design-system.css',
            array(),
            EASY_STATUS_CHECK_VERSION
        );

        wp_enqueue_script(
            'easy-design-system',
            EASY_STATUS_CHECK_URL . '../easy-design-system/easy-design-system.js',
            array( 'jquery' ),
            EASY_STATUS_CHECK_VERSION,
            true
        );

        wp_enqueue_script(
            'esc-frontend-js',
            EASY_STATUS_CHECK_URL . 'assets/js/frontend.js',
            array( 'jquery', 'easy-design-system' ),
            EASY_STATUS_CHECK_VERSION,
            true
        );

        wp_enqueue_style(
            'esc-frontend-css',
            EASY_STATUS_CHECK_URL . 'assets/css/frontend.css',
            array( 'easy-design-system' ),
            EASY_STATUS_CHECK_VERSION
        );

        wp_localize_script( 'esc-frontend-js', 'escFrontend', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'esc_frontend_nonce' ),
            'strings' => array(
                'online' => __( 'Online', 'easy-status-check' ),
                'offline' => __( 'Offline', 'easy-status-check' ),
                'warning' => __( 'Warnung', 'easy-status-check' ),
                'lastChecked' => __( 'Zuletzt geprüft:', 'easy-status-check' ),
                'responseTime' => __( 'Antwortzeit:', 'easy-status-check' ),
                'uptime' => __( 'Verfügbarkeit:', 'easy-status-check' ),
                'error' => __( 'Fehler beim Laden der Daten', 'easy-status-check' ),
                'loading' => __( 'Lade...', 'easy-status-check' ),
                'refresh' => __( 'Aktualisieren', 'easy-status-check' ),
                'details' => __( 'Details', 'easy-status-check' ),
                'hide' => __( 'Verbergen', 'easy-status-check' )
            )
        ));
    }

    /**
     * Render status display
     */
    public function render_status_display( $atts = array() ) {
        $atts = wp_parse_args( $atts, array(
            'category' => 'all',
            'layout' => 'grid',
            'refresh' => '300',
            'show_uptime' => 'true',
            'show_response_time' => 'true',
            'columns' => '3'
        ) );

        $unique_id = 'esc-display-' . uniqid();
        
        ob_start();
        ?>
        <div id="<?php echo esc_attr( $unique_id ); ?>" class="esc-status-display esc-layout-<?php echo esc_attr( $atts['layout'] ); ?>" 
             data-category="<?php echo esc_attr( $atts['category'] ); ?>"
             data-refresh="<?php echo esc_attr( $atts['refresh'] ); ?>"
             data-show-uptime="<?php echo esc_attr( $atts['show_uptime'] ); ?>"
             data-show-response-time="<?php echo esc_attr( $atts['show_response_time'] ); ?>"
             data-columns="<?php echo esc_attr( $atts['columns'] ); ?>">
            
            <div class="esc-status-header">
                <h3 class="esc-status-title"><?php esc_html_e( 'Service Status', 'easy-status-check' ); ?></h3>
                <div class="esc-status-controls">
                    <button type="button" class="esc-refresh-btn">
                        <span class="esc-refresh-icon">↻</span>
                        <?php esc_html_e( 'Aktualisieren', 'easy-status-check' ); ?>
                    </button>
                    <div class="esc-last-updated">
                        <span><?php esc_html_e( 'Zuletzt aktualisiert:', 'easy-status-check' ); ?></span>
                        <time class="esc-timestamp">--</time>
                    </div>
                </div>
            </div>

            <div class="esc-services-container">
                <div class="esc-loading">
                    <div class="esc-loading-spinner"></div>
                    <span><?php esc_html_e( 'Lade Service-Status...', 'easy-status-check' ); ?></span>
                </div>
            </div>

            <?php if ( $atts['layout'] === 'admin' ) : ?>
                <div class="esc-status-legend">
                    <div class="esc-legend-item">
                        <span class="esc-status-indicator esc-status-online"></span>
                        <span><?php esc_html_e( 'Online', 'easy-status-check' ); ?></span>
                    </div>
                    <div class="esc-legend-item">
                        <span class="esc-status-indicator esc-status-warning"></span>
                        <span><?php esc_html_e( 'Warnung', 'easy-status-check' ); ?></span>
                    </div>
                    <div class="esc-legend-item">
                        <span class="esc-status-indicator esc-status-offline"></span>
                        <span><?php esc_html_e( 'Offline', 'easy-status-check' ); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render individual service item
     */
    private function render_service_item( $service, $atts ) {
        $status_class = 'esc-status-unknown';
        $status_text = __( 'Unbekannt', 'easy-status-check' );
        $uptime_text = '';
        $response_time_text = '';
        
        if ( ! empty( $service->current_status ) ) {
            $status_class = 'esc-status-' . $service->current_status;
            
            switch ( $service->current_status ) {
                case 'online':
                    $status_text = __( 'Online', 'easy-status-check' );
                    break;
                case 'offline':
                    $status_text = __( 'Offline', 'easy-status-check' );
                    break;
                case 'warning':
                    $status_text = __( 'Warnung', 'easy-status-check' );
                    break;
            }
        }

        // Get uptime statistics if enabled
        if ( $atts['show_uptime'] === 'true' && class_exists( 'ESC_Status_Checker' ) ) {
            $checker = new ESC_Status_Checker();
            $stats = $checker->get_uptime_statistics( $service->id, '24h' );
            $uptime_text = $stats->uptime_percentage . '%';
        }

        // Format response time if available
        if ( $atts['show_response_time'] === 'true' && ! empty( $service->response_time ) ) {
            $response_time_text = round( $service->response_time, 0 ) . 'ms';
        }

        $last_checked = '';
        if ( ! empty( $service->last_checked ) ) {
            $last_checked = human_time_diff( strtotime( $service->last_checked ), current_time( 'timestamp' ) );
        }

        ob_start();
        ?>
        <div class="esc-service-item <?php echo esc_attr( $status_class ); ?>" data-service-id="<?php echo esc_attr( $service->id ); ?>">
            <div class="esc-service-main">
                <div class="esc-service-status">
                    <span class="esc-status-indicator"></span>
                    <span class="esc-status-text"><?php echo esc_html( $status_text ); ?></span>
                </div>
                
                <div class="esc-service-info">
                    <h4 class="esc-service-name"><?php echo esc_html( $service->name ); ?></h4>
                    <div class="esc-service-url"><?php echo esc_html( $service->url ); ?></div>
                </div>

                <div class="esc-service-meta">
                    <?php if ( ! empty( $uptime_text ) ) : ?>
                        <div class="esc-meta-item">
                            <span class="esc-meta-label"><?php esc_html_e( 'Verfügbarkeit:', 'easy-status-check' ); ?></span>
                            <span class="esc-meta-value"><?php echo esc_html( $uptime_text ); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $response_time_text ) ) : ?>
                        <div class="esc-meta-item">
                            <span class="esc-meta-label"><?php esc_html_e( 'Antwortzeit:', 'easy-status-check' ); ?></span>
                            <span class="esc-meta-value"><?php echo esc_html( $response_time_text ); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $last_checked ) ) : ?>
                        <div class="esc-meta-item">
                            <span class="esc-meta-label"><?php esc_html_e( 'Zuletzt geprüft:', 'easy-status-check' ); ?></span>
                            <span class="esc-meta-value"><?php echo esc_html( $last_checked ); ?> <?php esc_html_e( 'her', 'easy-status-check' ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ( ! empty( $service->error_message ) ) : ?>
                    <div class="esc-service-error">
                        <span class="esc-error-label"><?php esc_html_e( 'Fehler:', 'easy-status-check' ); ?></span>
                        <span class="esc-error-message"><?php echo esc_html( $service->error_message ); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="esc-service-details" style="display: none;">
                <div class="esc-detail-row">
                    <span class="esc-detail-label"><?php esc_html_e( 'HTTP Code:', 'easy-status-check' ); ?></span>
                    <span class="esc-detail-value"><?php echo esc_html( $service->http_code ?? 'N/A' ); ?></span>
                </div>
                <div class="esc-detail-row">
                    <span class="esc-detail-label"><?php esc_html_e( 'Methode:', 'easy-status-check' ); ?></span>
                    <span class="esc-detail-value"><?php echo esc_html( strtoupper( $service->method ) ); ?></span>
                </div>
                <div class="esc-detail-row">
                    <span class="esc-detail-label"><?php esc_html_e( 'Timeout:', 'easy-status-check' ); ?></span>
                    <span class="esc-detail-value"><?php echo esc_html( $service->timeout ); ?>s</span>
                </div>
                <div class="esc-detail-row">
                    <span class="esc-detail-label"><?php esc_html_e( 'Prüfintervall:', 'easy-status-check' ); ?></span>
                    <span class="esc-detail-value"><?php echo esc_html( $service->check_interval ); ?>s</span>
                </div>
            </div>

            <div class="esc-service-actions">
                <button type="button" class="esc-toggle-details">
                    <span class="esc-details-text"><?php esc_html_e( 'Details', 'easy-status-check' ); ?></span>
                    <span class="esc-hide-text" style="display: none;"><?php esc_html_e( 'Verbergen', 'easy-status-check' ); ?></span>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler for getting status data
     */
    public function ajax_get_status_data() {
        check_ajax_referer( 'esc_frontend_nonce', 'nonce' );
        
        $category = sanitize_text_field( $_POST['category'] ?? 'all' );
        $show_uptime = sanitize_text_field( $_POST['show_uptime'] ?? 'true' );
        $show_response_time = sanitize_text_field( $_POST['show_response_time'] ?? 'true' );
        
        if ( class_exists( 'ESC_Status_Checker' ) ) {
            $checker = new ESC_Status_Checker();
            $services = $checker->get_service_status_summary();
            
            // Filter by category if specified
            if ( $category !== 'all' ) {
                $services = array_filter( $services, function( $service ) use ( $category ) {
                    return $service->category === $category;
                });
            }

            // Group services by category
            $grouped_services = array();
            foreach ( $services as $service ) {
                if ( ! $service->enabled ) {
                    continue;
                }
                
                $category_name = $this->get_category_name( $service->category );
                if ( ! isset( $grouped_services[ $category_name ] ) ) {
                    $grouped_services[ $category_name ] = array();
                }
                $grouped_services[ $category_name ][] = $service;
            }

            // Render HTML for each category
            $html = '';
            foreach ( $grouped_services as $category_name => $category_services ) {
                $html .= '<div class="esc-category-section">';
                $html .= '<h4 class="esc-category-title">' . esc_html( $category_name ) . '</h4>';
                $html .= '<div class="esc-services-grid">';
                
                foreach ( $category_services as $service ) {
                    $html .= $this->render_service_item( $service, array(
                        'show_uptime' => $show_uptime,
                        'show_response_time' => $show_response_time
                    ) );
                }
                
                $html .= '</div></div>';
            }

            // Calculate overall statistics
            $total_services = count( $services );
            $online_services = count( array_filter( $services, function( $service ) {
                return $service->enabled && $service->current_status === 'online';
            }));
            $offline_services = count( array_filter( $services, function( $service ) {
                return $service->enabled && $service->current_status === 'offline';
            }));
            $warning_services = count( array_filter( $services, function( $service ) {
                return $service->enabled && $service->current_status === 'warning';
            }));

            wp_send_json_success( array(
                'html' => $html,
                'stats' => array(
                    'total' => $total_services,
                    'online' => $online_services,
                    'offline' => $offline_services,
                    'warning' => $warning_services
                ),
                'timestamp' => current_time( 'Y-m-d H:i:s' )
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Status Checker nicht verfügbar.', 'easy-status-check' ) ) );
        }
    }

    /**
     * Get category display name
     */
    private function get_category_name( $category ) {
        $categories = array(
            'cloud' => __( 'Cloud Services', 'easy-status-check' ),
            'hosting' => __( 'Hosting', 'easy-status-check' ),
            'custom' => __( 'Benutzerdefiniert', 'easy-status-check' )
        );
        
        return $categories[ $category ] ?? __( 'Andere', 'easy-status-check' );
    }

    /**
     * Generate status page
     */
    public function generate_status_page() {
        $page_title = __( 'Service Status', 'easy-status-check' );
        $page_content = '[easy_status_display layout="grid" show_uptime="true" show_response_time="true"]';
        
        // Check if page already exists
        $existing_page = get_page_by_title( $page_title );
        if ( $existing_page ) {
            return $existing_page->ID;
        }
        
        // Create new page
        $page_data = array(
            'post_title' => $page_title,
            'post_content' => $page_content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        
        $page_id = wp_insert_post( $page_data );
        
        if ( $page_id ) {
            update_option( 'esc_status_page_id', $page_id );
        }
        
        return $page_id;
    }
}

// Class will be instantiated by main plugin class
