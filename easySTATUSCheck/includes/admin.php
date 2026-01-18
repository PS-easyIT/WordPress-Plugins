<?php
/**
 * Admin interface for easySTATUSCheck
 *
 * @package Easy_Status_Check
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_menu', array( $this, 'add_settings_menu' ), 100 ); // Einstellungen ganz am Ende
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page - Dashboard
        add_menu_page(
            __( 'Status Check Dashboard', 'easy-status-check' ),
            __( 'Status Check', 'easy-status-check' ),
            'manage_options',
            'easy-status-check',
            array( $this, 'render_dashboard' ),
            'dashicons-visibility',
            30
        );

        // Dashboard submenu (ersetzt den Hauptmenüpunkt)
        add_submenu_page(
            'easy-status-check',
            __( 'Dashboard', 'easy-status-check' ),
            __( 'Dashboard', 'easy-status-check' ),
            'manage_options',
            'easy-status-check',
            array( $this, 'render_dashboard' )
        );

        // Services submenu - Position 2
        add_submenu_page(
            'easy-status-check',
            __( 'Services verwalten', 'easy-status-check' ),
            __( 'Services', 'easy-status-check' ),
            'manage_options',
            'easy-status-check-services',
            array( $this, 'render_services' )
        );
        
        // Note: Templates (Position 3) und Incidents (Position 4) 
        // werden von ihren jeweiligen Klassen registriert
        // ESC_Service_Templates adds 'easy-status-check-templates' with priority 20
        // ESC_Incident_Tracker adds 'easy-status-check-incidents' with priority 25
        // History wurde aus Admin-Menü entfernt - nur noch öffentliche History-Seite verfügbar

        // Note: Einstellungen wird mit Priorität 100 am Ende registriert (siehe unten)
    }
    
    /**
     * Register settings menu at the end
     */
    public function add_settings_menu() {
        add_submenu_page(
            'easy-status-check',
            __( 'Einstellungen', 'easy-status-check' ),
            __( 'Einstellungen', 'easy-status-check' ),
            'manage_options',
            'easy-status-check-settings',
            array( $this, 'render_settings' )
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'easy-status-check' ) === false ) {
            return;
        }

        $dependencies = array( 'jquery' );
        $style_dependencies = array();
        
        // Check if easy-design-system exists
        $design_system_path = EASY_STATUS_CHECK_DIR . '../easy-design-system/easy-design-system.css';
        
        if ( file_exists( $design_system_path ) ) {
            // Enqueue unified design system
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
            
            $dependencies[] = 'easy-design-system';
            $style_dependencies[] = 'easy-design-system';
        }

        wp_enqueue_script(
            'esc-admin-js',
            EASY_STATUS_CHECK_URL . 'assets/js/admin.js',
            $dependencies,
            EASY_STATUS_CHECK_VERSION,
            true
        );

        wp_enqueue_style(
            'esc-admin-css',
            EASY_STATUS_CHECK_URL . 'assets/css/admin.css',
            $style_dependencies,
            EASY_STATUS_CHECK_VERSION
        );

        wp_localize_script( 'esc-admin-js', 'escAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'esc_admin_nonce' ),
            'strings' => array(
                'confirmDelete' => __( 'Sind Sie sicher, dass Sie diesen Service löschen möchten?', 'easy-status-check' ),
                'testSuccess' => __( 'Test erfolgreich!', 'easy-status-check' ),
                'testFailed' => __( 'Test fehlgeschlagen:', 'easy-status-check' ),
                'saving' => __( 'Speichern...', 'easy-status-check' ),
                'testing' => __( 'Teste...', 'easy-status-check' ),
            )
        ));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'esc_settings', 'esc_general_settings' );
        register_setting( 'esc_settings', 'esc_notification_settings' );
        register_setting( 'esc_public_settings', 'esc_public_settings' );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        
        // Get statistics
        $total_services = $wpdb->get_var( "SELECT COUNT(*) FROM $services_table WHERE enabled = 1" );
        $online_services = $wpdb->get_var( "
            SELECT COUNT(DISTINCT s.id) 
            FROM $services_table s 
            INNER JOIN $logs_table l ON s.id = l.service_id 
            WHERE s.enabled = 1 
            AND l.status = 'online' 
            AND l.checked_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        " );
        $offline_services = $total_services - $online_services;
        
        // Get recent status changes
        $recent_changes = $wpdb->get_results( "
            SELECT s.name, l.status, l.checked_at, l.error_message
            FROM $logs_table l
            INNER JOIN $services_table s ON l.service_id = s.id
            WHERE l.checked_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY l.checked_at DESC
            LIMIT 10
        " );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Status Check Dashboard', 'easy-status-check' ); ?></h1>
            
            <div class="esc-dashboard-stats">
                <div class="esc-stat-card esc-stat-total">
                    <div class="esc-stat-number"><?php echo esc_html( $total_services ); ?></div>
                    <div class="esc-stat-label"><?php esc_html_e( 'Gesamt Services', 'easy-status-check' ); ?></div>
                </div>
                <div class="esc-stat-card esc-stat-online">
                    <div class="esc-stat-number"><?php echo esc_html( $online_services ); ?></div>
                    <div class="esc-stat-label"><?php esc_html_e( 'Online', 'easy-status-check' ); ?></div>
                </div>
                <div class="esc-stat-card esc-stat-offline">
                    <div class="esc-stat-number"><?php echo esc_html( $offline_services ); ?></div>
                    <div class="esc-stat-label"><?php esc_html_e( 'Offline', 'easy-status-check' ); ?></div>
                </div>
            </div>

            <div class="esc-dashboard-grid">
                <div class="esc-dashboard-column">
                    <div class="esc-widget">
                        <h3><?php esc_html_e( 'Kürzliche Statusänderungen', 'easy-status-check' ); ?></h3>
                        <div class="esc-recent-changes">
                            <?php if ( empty( $recent_changes ) ) : ?>
                                <p><?php esc_html_e( 'Keine kürzlichen Änderungen gefunden.', 'easy-status-check' ); ?></p>
                            <?php else : ?>
                                <?php foreach ( $recent_changes as $change ) : ?>
                                    <div class="esc-change-item">
                                        <span class="esc-status-indicator esc-status-<?php echo esc_attr( $change->status ); ?>"></span>
                                        <div class="esc-change-details">
                                            <div class="esc-change-service"><?php echo esc_html( $change->name ); ?></div>
                                            <div class="esc-change-time"><?php echo esc_html( human_time_diff( strtotime( $change->checked_at ), current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'her', 'easy-status-check' ); ?></div>
                                            <?php if ( ! empty( $change->error_message ) ) : ?>
                                                <div class="esc-change-error"><?php echo esc_html( $change->error_message ); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="esc-dashboard-column">
                    <div class="esc-widget">
                        <h3><?php esc_html_e( 'Schnellaktionen', 'easy-status-check' ); ?></h3>
                        <div class="esc-quick-actions">
                            <a href="<?php echo admin_url( 'admin.php?page=easy-status-check-services' ); ?>" class="button">
                                <?php esc_html_e( 'Services verwalten', 'easy-status-check' ); ?>
                            </a>
                            <button type="button" id="esc-force-check" class="button button-primary">
                                <span class="dashicons dashicons-update"></span>
                                <?php esc_html_e( 'Alle Services jetzt prüfen', 'easy-status-check' ); ?>
                            </button>
                            <span id="esc-check-status" style="margin-left: 10px; display: none;"></span>
                        </div>
                    </div>

                    <div class="esc-widget">
                        <h3><?php esc_html_e( 'Shortcode', 'easy-status-check' ); ?></h3>
                        <p><?php esc_html_e( 'Verwenden Sie diesen Shortcode, um die Status-Anzeige auf Ihren Seiten einzubinden:', 'easy-status-check' ); ?></p>
                        <code>[easy_status_display]</code>
                        <p class="description">
                            <?php esc_html_e( 'Parameter: category="cloud|hosting|custom|all", layout="grid|list", refresh="300"', 'easy-status-check' ); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render services management page
     */
    public function render_services() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        
        // Handle form submissions
        if ( isset( $_POST['action'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'esc_services_action' ) ) {
            $this->handle_service_action( $_POST );
        }
        
        // Get services
        $services = $wpdb->get_results( "SELECT * FROM $services_table ORDER BY category, name" );
        
        // Get categories
        $categories = array(
            'cloud' => __( 'Cloud Services', 'easy-status-check' ),
            'hosting' => __( 'Hosting', 'easy-status-check' ),
            'custom' => __( 'Benutzerdefiniert', 'easy-status-check' )
        );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Services verwalten', 'easy-status-check' ); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php esc_html_e( 'Neue Services hinzufügen:', 'easy-status-check' ); ?></strong>
                    <?php 
                    printf( 
                        __( 'Um neue Services hinzuzufügen, nutzen Sie bitte die %s.', 'easy-status-check' ),
                        '<a href="' . admin_url( 'admin.php?page=easy-status-check-templates' ) . '">' . __( 'Templates-Seite', 'easy-status-check' ) . '</a>'
                    ); 
                    ?>
                </p>
            </div>

            <form method="post" id="esc-services-form">
                <?php wp_nonce_field( 'esc_services_action' ); ?>
                <input type="hidden" name="action" value="save_services">
                
                <div class="esc-services-list">
                    <?php foreach ( $categories as $category_key => $category_name ) : ?>
                        <?php $category_services = array_filter( $services, function( $service ) use ( $category_key ) {
                            return $service->category === $category_key;
                        }); ?>
                        
                        <?php if ( ! empty( $category_services ) ) : ?>
                            <div class="esc-category-section">
                                <h3><?php echo esc_html( $category_name ); ?></h3>
                                <div class="esc-services-grid">
                                    <?php foreach ( $category_services as $service ) : ?>
                                        <?php $this->render_service_item( $service ); ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <div class="esc-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e( 'Änderungen speichern', 'easy-status-check' ); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <?php $this->render_service_modal(); ?>
        <?php $this->render_predefined_services_modal(); ?>
        <?php
    }

    /**
     * Render individual service item
     */
    private function render_service_item( $service ) {
        ?>
        <div class="esc-service-item" data-service-id="<?php echo esc_attr( $service->id ); ?>">
            <div class="esc-service-header">
                <h4><?php echo esc_html( $service->name ); ?></h4>
                <div class="esc-service-controls">
                    <label class="esc-toggle">
                        <input type="checkbox" name="services[<?php echo esc_attr( $service->id ); ?>][enabled]" value="1" <?php checked( $service->enabled ); ?>>
                        <span class="esc-toggle-slider"></span>
                    </label>
                    <button type="button" class="button button-small esc-edit-service" data-service-id="<?php echo esc_attr( $service->id ); ?>">
                        <?php esc_html_e( 'Bearbeiten', 'easy-status-check' ); ?>
                    </button>
                    <button type="button" class="button button-small esc-test-service" data-service-id="<?php echo esc_attr( $service->id ); ?>">
                        <?php esc_html_e( 'Testen', 'easy-status-check' ); ?>
                    </button>
                    <?php 
                    $public_enabled = get_option( 'esc_public_status_enabled', false );
                    $public_slug = get_option( 'esc_public_status_slug', 'status' );
                    if ( $public_enabled ) : 
                    ?>
                        <a href="<?php echo home_url( '/' . $public_slug . '/history/' . $service->id ); ?>" class="button button-small" target="_blank">
                            <span class="dashicons dashicons-chart-line" style="margin-top: 3px;"></span>
                            <?php esc_html_e( 'History', 'easy-status-check' ); ?>
                        </a>
                    <?php endif; ?>
                    <button type="button" class="button button-small button-link-delete esc-delete-service" data-service-id="<?php echo esc_attr( $service->id ); ?>">
                        <?php esc_html_e( 'Löschen', 'easy-status-check' ); ?>
                    </button>
                </div>
            </div>
            <div class="esc-service-details">
                <div class="esc-service-url"><?php echo esc_html( $service->url ); ?></div>
                <div class="esc-service-meta">
                    <span><?php echo esc_html( strtoupper( $service->method ) ); ?></span>
                    <span><?php printf( __( 'Alle %d Sekunden', 'easy-status-check' ), $service->check_interval ); ?></span>
                </div>
            </div>
            
            <!-- Hidden fields for form submission -->
            <input type="hidden" name="services[<?php echo esc_attr( $service->id ); ?>][id]" value="<?php echo esc_attr( $service->id ); ?>">
            <input type="hidden" name="services[<?php echo esc_attr( $service->id ); ?>][name]" value="<?php echo esc_attr( $service->name ); ?>">
            <input type="hidden" name="services[<?php echo esc_attr( $service->id ); ?>][url]" value="<?php echo esc_attr( $service->url ); ?>">
            <input type="hidden" name="services[<?php echo esc_attr( $service->id ); ?>][category]" value="<?php echo esc_attr( $service->category ); ?>">
            <input type="hidden" name="services[<?php echo esc_attr( $service->id ); ?>][method]" value="<?php echo esc_attr( $service->method ); ?>">
            <input type="hidden" name="services[<?php echo esc_attr( $service->id ); ?>][timeout]" value="<?php echo esc_attr( $service->timeout ); ?>">
            <input type="hidden" name="services[<?php echo esc_attr( $service->id ); ?>][expected_code]" value="<?php echo esc_attr( $service->expected_code ); ?>">
            <input type="hidden" name="services[<?php echo esc_attr( $service->id ); ?>][check_interval]" value="<?php echo esc_attr( $service->check_interval ); ?>">
        </div>
        <?php
    }

    /**
     * Render service modal for add/edit
     */
    private function render_service_modal() {
        ?>
        <div id="esc-service-modal" class="esc-modal" style="display: none;">
            <div class="esc-modal-content">
                <div class="esc-modal-header">
                    <h3 id="esc-modal-title"><?php esc_html_e( 'Service hinzufügen', 'easy-status-check' ); ?></h3>
                    <button type="button" class="esc-modal-close">&times;</button>
                </div>
                <div class="esc-modal-body">
                    <form id="esc-service-form">
                        <input type="hidden" id="service-id" name="service_id">
                        
                        <div class="esc-form-row">
                            <label for="service-name"><?php esc_html_e( 'Service Name', 'easy-status-check' ); ?></label>
                            <input type="text" id="service-name" name="service_name" required>
                        </div>
                        
                        <div class="esc-form-row">
                            <label for="service-url"><?php esc_html_e( 'URL', 'easy-status-check' ); ?></label>
                            <input type="url" id="service-url" name="service_url" required>
                        </div>
                        
                        <div class="esc-form-row">
                            <label for="service-category"><?php esc_html_e( 'Kategorie', 'easy-status-check' ); ?></label>
                            <select id="service-category" name="service_category">
                                <option value="cloud"><?php esc_html_e( 'Cloud Services', 'easy-status-check' ); ?></option>
                                <option value="hosting"><?php esc_html_e( 'Hosting', 'easy-status-check' ); ?></option>
                                <option value="custom"><?php esc_html_e( 'Benutzerdefiniert', 'easy-status-check' ); ?></option>
                            </select>
                        </div>
                        
                        <div class="esc-form-row esc-form-row-half">
                            <div>
                                <label for="service-method"><?php esc_html_e( 'HTTP Methode', 'easy-status-check' ); ?></label>
                                <select id="service-method" name="service_method">
                                    <option value="GET">GET</option>
                                    <option value="POST">POST</option>
                                    <option value="HEAD">HEAD</option>
                                </select>
                            </div>
                            <div>
                                <label for="service-timeout"><?php esc_html_e( 'Timeout (Sekunden)', 'easy-status-check' ); ?></label>
                                <input type="number" id="service-timeout" name="service_timeout" min="1" max="60" value="10">
                            </div>
                        </div>
                        
                        <div class="esc-form-row esc-form-row-half">
                            <div>
                                <label for="service-expected-code"><?php esc_html_e( 'Erwarteter HTTP Code', 'easy-status-check' ); ?></label>
                                <input type="text" id="service-expected-code" name="service_expected_code" value="200" placeholder="200,201,204">
                            </div>
                            <div>
                                <label for="service-interval"><?php esc_html_e( 'Prüfintervall (Sekunden)', 'easy-status-check' ); ?></label>
                                <select id="service-interval" name="service_interval">
                                    <option value="60">1 <?php esc_html_e( 'Minute', 'easy-status-check' ); ?></option>
                                    <option value="300" selected>5 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                                    <option value="600">10 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                                    <option value="1800">30 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="esc-form-row">
                            <label>
                                <input type="checkbox" id="service-enabled" name="service_enabled" checked>
                                <?php esc_html_e( 'Service aktiviert', 'easy-status-check' ); ?>
                            </label>
                        </div>
                        
                        <div class="esc-form-row">
                            <label>
                                <input type="checkbox" id="service-notify" name="service_notify" checked>
                                <?php esc_html_e( 'E-Mail Benachrichtigungen', 'easy-status-check' ); ?>
                            </label>
                        </div>
                        
                        <div class="esc-form-row">
                            <label for="service-response-type"><?php esc_html_e( 'Response-Typ', 'easy-status-check' ); ?></label>
                            <select id="service-response-type" name="service_response_type">
                                <option value=""><?php esc_html_e( 'Standard HTTP-Check', 'easy-status-check' ); ?></option>
                                <option value="json"><?php esc_html_e( 'JSON-API', 'easy-status-check' ); ?></option>
                                <option value="rss"><?php esc_html_e( 'RSS/XML', 'easy-status-check' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Für Status-APIs von Cloud-Anbietern', 'easy-status-check' ); ?></p>
                        </div>
                        
                        <div class="esc-form-row esc-json-fields" style="display: none;">
                            <label for="service-json-path"><?php esc_html_e( 'JSON-Pfad zum Status', 'easy-status-check' ); ?></label>
                            <input type="text" id="service-json-path" name="service_json_path" placeholder="status.indicator">
                            <p class="description"><?php esc_html_e( 'Pfad zum Status-Wert in der JSON-Response (z.B. "status.indicator")', 'easy-status-check' ); ?></p>
                        </div>
                        
                        <div class="esc-form-row">
                            <label>
                                <input type="checkbox" id="service-check-content" name="service_check_content">
                                <?php esc_html_e( 'Erweiterte Inhalts-Prüfung', 'easy-status-check' ); ?>
                            </label>
                        </div>
                    </form>
                </div>
                <div class="esc-modal-footer">
                    <button type="button" class="button button-primary" id="esc-save-service">
                        <?php esc_html_e( 'Speichern', 'easy-status-check' ); ?>
                    </button>
                    <button type="button" class="button" id="esc-cancel-service">
                        <?php esc_html_e( 'Abbrechen', 'easy-status-check' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render predefined services modal
     */
    private function render_predefined_services_modal() {
        if ( class_exists( 'ESC_Predefined_Services' ) ) {
            $predefined = new ESC_Predefined_Services();
            $services = $predefined->get_predefined_services();
            
            ?>
            <div id="esc-predefined-modal" class="esc-modal" style="display: none;">
                <div class="esc-modal-content esc-modal-large">
                    <div class="esc-modal-header">
                        <h3><?php esc_html_e( 'Vordefinierte Services hinzufügen', 'easy-status-check' ); ?></h3>
                        <button type="button" class="esc-modal-close">&times;</button>
                    </div>
                    <div class="esc-modal-body">
                        <div class="esc-predefined-categories">
                            <?php foreach ( $services as $category => $category_services ) : ?>
                                <div class="esc-predefined-category">
                                    <h4><?php echo esc_html( $category ); ?></h4>
                                    <div class="esc-predefined-services">
                                        <?php foreach ( $category_services as $service ) : ?>
                                            <label class="esc-predefined-service">
                                                <input type="checkbox" name="predefined_services[]" value="<?php echo esc_attr( json_encode( $service ) ); ?>">
                                                <div class="esc-service-info">
                                                    <strong><?php echo esc_html( $service['name'] ); ?></strong>
                                                    <small><?php echo esc_html( $service['url'] ); ?></small>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="esc-modal-footer">
                        <button type="button" class="button button-primary" id="esc-add-predefined-services">
                            <?php esc_html_e( 'Ausgewählte Services hinzufügen', 'easy-status-check' ); ?>
                        </button>
                        <button type="button" class="button" id="esc-cancel-predefined">
                            <?php esc_html_e( 'Abbrechen', 'easy-status-check' ); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Render settings page with tabs
     */
    public function render_settings() {
        require_once EASY_STATUS_CHECK_DIR . 'includes/admin-settings-tabs.php';
    }
    
    /**
     * OLD render_settings - DEPRECATED
     */
    public function render_settings_old() {
        $general_settings = get_option( 'esc_general_settings', array() );
        $notification_settings = get_option( 'esc_notification_settings', array() );
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Status Check Einstellungen', 'easy-status-check' ); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields( 'esc_settings' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Standard Prüfintervall', 'easy-status-check' ); ?></th>
                        <td>
                            <select name="esc_general_settings[default_interval]">
                                <option value="300" <?php selected( $general_settings['default_interval'] ?? 300, 300 ); ?>>5 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                                <option value="600" <?php selected( $general_settings['default_interval'] ?? 300, 600 ); ?>>10 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                                <option value="1800" <?php selected( $general_settings['default_interval'] ?? 300, 1800 ); ?>>30 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Standard Timeout', 'easy-status-check' ); ?></th>
                        <td>
                            <input type="number" name="esc_general_settings[default_timeout]" value="<?php echo esc_attr( $general_settings['default_timeout'] ?? 10 ); ?>" min="1" max="60">
                            <p class="description"><?php esc_html_e( 'Timeout in Sekunden für HTTP-Anfragen', 'easy-status-check' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'E-Mail Benachrichtigungen', 'easy-status-check' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="esc_notification_settings[enabled]" value="1" <?php checked( $notification_settings['enabled'] ?? 1 ); ?>>
                                <?php esc_html_e( 'E-Mail Benachrichtigungen aktivieren', 'easy-status-check' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Benachrichtigungs E-Mail', 'easy-status-check' ); ?></th>
                        <td>
                            <input type="email" name="esc_notification_settings[email]" value="<?php echo esc_attr( $notification_settings['email'] ?? get_option( 'admin_email' ) ); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e( 'E-Mail Adresse für Benachrichtigungen', 'easy-status-check' ); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <!-- Public Pages Sektion -->
            <hr style="margin: 40px 0;">
            
            <h2><?php esc_html_e( 'Öffentliche Seiten', 'easy-status-check' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Konfigurieren Sie das Aussehen und Verhalten der öffentlichen Status-Seiten.', 'easy-status-check' ); ?>
            </p>
            
            <form method="post" action="">
                <?php 
                // Handle settings save
                if ( isset( $_POST['esc_save_public_settings'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'esc_public_settings' ) ) {
                    update_option( 'esc_public_status_enabled', isset( $_POST['esc_public_status_enabled'] ) ? 1 : 0 );
                    update_option( 'esc_public_status_slug', sanitize_title( $_POST['esc_public_status_slug'] ) );
                    
                    // Public Settings
                    $public_settings = array(
                        'primary_color' => sanitize_hex_color( $_POST['esc_public_settings']['primary_color'] ?? '#2271b1' ),
                        'success_color' => sanitize_hex_color( $_POST['esc_public_settings']['success_color'] ?? '#00a32a' ),
                        'warning_color' => sanitize_hex_color( $_POST['esc_public_settings']['warning_color'] ?? '#f0b849' ),
                        'error_color' => sanitize_hex_color( $_POST['esc_public_settings']['error_color'] ?? '#d63638' ),
                        'background_color' => sanitize_hex_color( $_POST['esc_public_settings']['background_color'] ?? '#f0f0f1' ),
                        'text_color' => sanitize_hex_color( $_POST['esc_public_settings']['text_color'] ?? '#1d2327' ),
                        'show_response_time' => isset( $_POST['esc_public_settings']['show_response_time'] ),
                        'show_uptime' => isset( $_POST['esc_public_settings']['show_uptime'] ),
                        'refresh_interval' => intval( $_POST['esc_public_settings']['refresh_interval'] ?? 300 )
                    );
                    update_option( 'esc_public_settings', $public_settings );
                    
                    flush_rewrite_rules();
                    
                    echo '<div class="notice notice-success"><p>' . __( 'Einstellungen gespeichert.', 'easy-status-check' ) . '</p></div>';
                }
                
                wp_nonce_field( 'esc_public_settings' );
                ?>
                <input type="hidden" name="esc_save_public_settings" value="1">
                
                <?php
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
                        <th scope="row"><?php esc_html_e( 'Primärfarbe', 'easy-status-check' ); ?></th>
                        <td>
                            <input type="color" name="esc_public_settings[primary_color]" value="<?php echo esc_attr( $public_settings['primary_color'] ); ?>">
                            <p class="description"><?php esc_html_e( 'Hauptfarbe für Buttons und Akzente', 'easy-status-check' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Erfolgsfarbe (Online)', 'easy-status-check' ); ?></th>
                        <td>
                            <input type="color" name="esc_public_settings[success_color]" value="<?php echo esc_attr( $public_settings['success_color'] ); ?>">
                            <p class="description"><?php esc_html_e( 'Farbe für Online-Status', 'easy-status-check' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Warnfarbe', 'easy-status-check' ); ?></th>
                        <td>
                            <input type="color" name="esc_public_settings[warning_color]" value="<?php echo esc_attr( $public_settings['warning_color'] ); ?>">
                            <p class="description"><?php esc_html_e( 'Farbe für Warnungen', 'easy-status-check' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Fehlerfarbe (Offline)', 'easy-status-check' ); ?></th>
                        <td>
                            <input type="color" name="esc_public_settings[error_color]" value="<?php echo esc_attr( $public_settings['error_color'] ); ?>">
                            <p class="description"><?php esc_html_e( 'Farbe für Offline-Status', 'easy-status-check' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Hintergrundfarbe', 'easy-status-check' ); ?></th>
                        <td>
                            <input type="color" name="esc_public_settings[background_color]" value="<?php echo esc_attr( $public_settings['background_color'] ); ?>">
                            <p class="description"><?php esc_html_e( 'Hintergrundfarbe der Seite', 'easy-status-check' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Textfarbe', 'easy-status-check' ); ?></th>
                        <td>
                            <input type="color" name="esc_public_settings[text_color]" value="<?php echo esc_attr( $public_settings['text_color'] ); ?>">
                            <p class="description"><?php esc_html_e( 'Haupttextfarbe', 'easy-status-check' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Anzeigeoptionen', 'easy-status-check' ); ?></th>
                        <td>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="esc_public_settings[show_response_time]" value="1" <?php checked( $public_settings['show_response_time'] ?? true ); ?>>
                                <?php esc_html_e( 'Antwortzeiten anzeigen', 'easy-status-check' ); ?>
                            </label>
                            <label style="display: block;">
                                <input type="checkbox" name="esc_public_settings[show_uptime]" value="1" <?php checked( $public_settings['show_uptime'] ?? true ); ?>>
                                <?php esc_html_e( 'Verfügbarkeit (Uptime) anzeigen', 'easy-status-check' ); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Auto-Refresh Intervall', 'easy-status-check' ); ?></th>
                        <td>
                            <select name="esc_public_settings[refresh_interval]">
                                <option value="0" <?php selected( $public_settings['refresh_interval'] ?? 300, 0 ); ?>><?php esc_html_e( 'Deaktiviert', 'easy-status-check' ); ?></option>
                                <option value="60" <?php selected( $public_settings['refresh_interval'] ?? 300, 60 ); ?>>1 <?php esc_html_e( 'Minute', 'easy-status-check' ); ?></option>
                                <option value="300" <?php selected( $public_settings['refresh_interval'] ?? 300, 300 ); ?>>5 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                                <option value="600" <?php selected( $public_settings['refresh_interval'] ?? 300, 600 ); ?>>10 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Wie oft soll die Seite automatisch aktualisiert werden?', 'easy-status-check' ); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button( __( 'Design-Einstellungen speichern', 'easy-status-check' ) ); ?>
            </form>
            
            <!-- Datenbank-Tools Sektion -->
            <hr style="margin: 40px 0;">
            
            <h2><?php esc_html_e( 'Datenbank-Tools', 'easy-status-check' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Verwalten Sie die Datenbank-Tabellen des Plugins. Hier können Sie fehlende Tabellen neu erstellen oder vorhandene Tabellen reparieren.', 'easy-status-check' ); ?>
            </p>
            
            <?php
            // Show last service errors if any
            $last_errors = get_transient( 'esc_last_service_errors' );
            if ( ! empty( $last_errors ) ) :
            ?>
                <div class="notice notice-error">
                    <p><strong><?php esc_html_e( 'Letzte Service-Fehler:', 'easy-status-check' ); ?></strong></p>
                    <ul>
                        <?php foreach ( $last_errors as $error ) : ?>
                            <li><?php echo esc_html( $error ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p>
                        <button type="button" class="button" onclick="jQuery.post(ajaxurl, {action: 'esc_clear_errors'}, function() { location.reload(); })">
                            <?php esc_html_e( 'Fehler löschen', 'easy-status-check' ); ?>
                        </button>
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="esc-database-tools">
                <div class="esc-db-status" id="esc-db-status">
                    <p><em><?php esc_html_e( 'Klicken Sie auf "Status prüfen" um die Tabellen zu überprüfen...', 'easy-status-check' ); ?></em></p>
                </div>
                
                <div class="esc-db-actions">
                    <button type="button" class="button button-primary" id="esc-check-tables">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e( 'Status prüfen', 'easy-status-check' ); ?>
                    </button>
                    <button type="button" class="button" id="esc-create-tables" disabled>
                        <span class="dashicons dashicons-plus"></span>
                        <?php esc_html_e( 'Fehlende Tabellen erstellen', 'easy-status-check' ); ?>
                    </button>
                    <button type="button" class="button" id="esc-repair-tables" disabled>
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php esc_html_e( 'Tabellen reparieren & optimieren', 'easy-status-check' ); ?>
                    </button>
                </div>
                
                <div class="esc-db-danger-zone" style="margin-top: 30px; padding: 20px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 4px;">
                    <h3 style="margin: 0 0 10px 0; color: #856404;">
                        <span class="dashicons dashicons-warning" style="color: #ffc107;"></span>
                        <?php esc_html_e( 'Gefahrenbereich', 'easy-status-check' ); ?>
                    </h3>
                    <p style="margin: 0 0 15px 0; color: #856404;">
                        <strong><?php esc_html_e( 'WARNUNG:', 'easy-status-check' ); ?></strong>
                        <?php esc_html_e( 'Diese Aktion löscht ALLE Daten aus allen Tabellen unwiderruflich! Services, Logs, Incidents und Benachrichtigungen werden permanent gelöscht.', 'easy-status-check' ); ?>
                    </p>
                    <button type="button" class="button button-link-delete" id="esc-reset-tables">
                        <span class="dashicons dashicons-trash"></span>
                        <?php esc_html_e( 'Alle Tabellen zurücksetzen (Daten löschen)', 'easy-status-check' ); ?>
                    </button>
                </div>
            </div>
            
            <style>
                .esc-database-tools {
                    background: #fff;
                    padding: 20px;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                    margin-top: 20px;
                }
                
                .esc-db-status {
                    background: #f9f9f9;
                    padding: 15px;
                    border-left: 4px solid #2271b1;
                    margin-bottom: 20px;
                }
                
                .esc-db-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                }
                
                .esc-db-table th,
                .esc-db-table td {
                    padding: 12px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                
                .esc-db-table th {
                    background: #f0f0f1;
                    font-weight: 600;
                }
                
                .esc-db-table-status {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 4px 10px;
                    border-radius: 3px;
                    font-size: 13px;
                    font-weight: 500;
                }
                
                .esc-db-table-status.exists {
                    background: #d4edda;
                    color: #155724;
                }
                
                .esc-db-table-status.missing {
                    background: #f8d7da;
                    color: #721c24;
                }
                
                .esc-db-actions {
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                }
                
                .esc-db-actions .button {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                }
                
                .esc-db-actions .dashicons {
                    font-size: 16px;
                    width: 16px;
                    height: 16px;
                }
                
                .esc-db-notification {
                    padding: 12px 15px;
                    margin: 15px 0;
                    border-left: 4px solid;
                    border-radius: 2px;
                }
                
                .esc-db-notification.success {
                    background: #d4edda;
                    border-color: #28a745;
                    color: #155724;
                }
                
                .esc-db-notification.error {
                    background: #f8d7da;
                    border-color: #dc3545;
                    color: #721c24;
                }
                
                .esc-db-notification.info {
                    background: #d1ecf1;
                    border-color: #17a2b8;
                    color: #0c5460;
                }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                var missingTables = [];
                
                // Status prüfen
                $('#esc-check-tables').on('click', function() {
                    var button = $(this);
                    var originalHtml = button.html();
                    
                    button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e( 'Prüfe...', 'easy-status-check' ); ?>');
                    
                    $.post(escAdmin.ajaxUrl, {
                        action: 'esc_check_tables',
                        nonce: escAdmin.nonce
                    }, function(response) {
                        if (response.success) {
                            displayTablesStatus(response.data.tables);
                            button.html(originalHtml).prop('disabled', false);
                        } else {
                            $('#esc-db-status').html('<div class="esc-db-notification error">' + response.data.message + '</div>');
                            button.html(originalHtml).prop('disabled', false);
                        }
                    });
                });
                
                // Tabellen erstellen
                $('#esc-create-tables').on('click', function() {
                    if (!confirm('<?php esc_html_e( 'Möchten Sie wirklich fehlende Tabellen erstellen?', 'easy-status-check' ); ?>')) {
                        return;
                    }
                    
                    var button = $(this);
                    var originalHtml = button.html();
                    
                    button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e( 'Erstelle...', 'easy-status-check' ); ?>');
                    
                    $.post(escAdmin.ajaxUrl, {
                        action: 'esc_create_tables',
                        nonce: escAdmin.nonce
                    }, function(response) {
                        if (response.success) {
                            $('#esc-db-status').prepend('<div class="esc-db-notification success">' + response.data.message + '</div>');
                            // Status neu prüfen
                            $('#esc-check-tables').trigger('click');
                        } else {
                            $('#esc-db-status').prepend('<div class="esc-db-notification error">' + response.data.message + '</div>');
                        }
                        button.html(originalHtml).prop('disabled', false);
                    });
                });
                
                // Tabellen reparieren
                $('#esc-repair-tables').on('click', function() {
                    if (!confirm('<?php esc_html_e( 'Möchten Sie wirklich alle Tabellen reparieren und optimieren?', 'easy-status-check' ); ?>')) {
                        return;
                    }
                    
                    var button = $(this);
                    var originalHtml = button.html();
                    
                    button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e( 'Repariere...', 'easy-status-check' ); ?>');
                    
                    $.post(escAdmin.ajaxUrl, {
                        action: 'esc_repair_tables',
                        nonce: escAdmin.nonce
                    }, function(response) {
                        if (response.success) {
                            $('#esc-db-status').prepend('<div class="esc-db-notification success">' + response.data.message + '</div>');
                        } else {
                            $('#esc-db-status').prepend('<div class="esc-db-notification error">' + response.data.message + '</div>');
                        }
                        button.html(originalHtml).prop('disabled', false);
                    });
                });
                
                // Tabellen zurücksetzen (GEFÄHRLICH!)
                $('#esc-reset-tables').on('click', function() {
                    var warningMessage = '⚠️ WARNUNG: ALLE DATEN WERDEN UNWIDERRUFLICH GELÖSCHT! ⚠️\n\n';
                    warningMessage += '<?php esc_html_e( 'Dies betrifft:', 'easy-status-check' ); ?>\n';
                    warningMessage += '• <?php esc_html_e( 'Alle Services', 'easy-status-check' ); ?>\n';
                    warningMessage += '• <?php esc_html_e( 'Alle Status-Logs', 'easy-status-check' ); ?>\n';
                    warningMessage += '• <?php esc_html_e( 'Alle Incidents', 'easy-status-check' ); ?>\n';
                    warningMessage += '• <?php esc_html_e( 'Alle Benachrichtigungen', 'easy-status-check' ); ?>\n\n';
                    warningMessage += '<?php esc_html_e( 'Möchten Sie wirklich fortfahren?', 'easy-status-check' ); ?>';
                    
                    if (!confirm(warningMessage)) {
                        return;
                    }
                    
                    // Zweite Bestätigung mit Texteingabe
                    var confirmation = prompt('<?php esc_html_e( 'Geben Sie "RESET" ein, um zu bestätigen:', 'easy-status-check' ); ?>');
                    
                    if (confirmation !== 'RESET') {
                        alert('<?php esc_html_e( 'Zurücksetzen abgebrochen. Die Bestätigung war nicht korrekt.', 'easy-status-check' ); ?>');
                        return;
                    }
                    
                    var button = $(this);
                    var originalHtml = button.html();
                    
                    button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e( 'Setze zurück...', 'easy-status-check' ); ?>');
                    
                    $.post(escAdmin.ajaxUrl, {
                        action: 'esc_reset_tables',
                        nonce: escAdmin.nonce,
                        confirm: 'RESET'
                    }, function(response) {
                        if (response.success) {
                            $('#esc-db-status').html('<div class="esc-db-notification success">' + response.data.message + '</div>');
                            // Status neu prüfen nach 2 Sekunden
                            setTimeout(function() {
                                $('#esc-check-tables').trigger('click');
                            }, 2000);
                        } else {
                            $('#esc-db-status').prepend('<div class="esc-db-notification error">' + response.data.message + '</div>');
                        }
                        button.html(originalHtml).prop('disabled', false);
                    }).fail(function() {
                        $('#esc-db-status').prepend('<div class="esc-db-notification error"><?php esc_html_e( 'Fehler beim Zurücksetzen der Tabellen.', 'easy-status-check' ); ?></div>');
                        button.html(originalHtml).prop('disabled', false);
                    });
                });
                
                // Tabellen-Status anzeigen
                function displayTablesStatus(tables) {
                    missingTables = [];
                    var html = '<table class="esc-db-table">';
                    html += '<thead><tr>';
                    html += '<th><?php esc_html_e( 'Tabelle', 'easy-status-check' ); ?></th>';
                    html += '<th><?php esc_html_e( 'Beschreibung', 'easy-status-check' ); ?></th>';
                    html += '<th><?php esc_html_e( 'Status', 'easy-status-check' ); ?></th>';
                    html += '<th><?php esc_html_e( 'Einträge', 'easy-status-check' ); ?></th>';
                    html += '</tr></thead><tbody>';
                    
                    $.each(tables, function(key, table) {
                        var statusClass = table.exists ? 'exists' : 'missing';
                        var statusText = table.exists ? '✓ <?php esc_html_e( 'Vorhanden', 'easy-status-check' ); ?>' : '✗ <?php esc_html_e( 'Fehlt', 'easy-status-check' ); ?>';
                        
                        if (!table.exists) {
                            missingTables.push(key);
                        }
                        
                        html += '<tr>';
                        html += '<td><strong>' + table.label + '</strong><br><code>' + table.name + '</code></td>';
                        html += '<td>' + table.description + '</td>';
                        html += '<td><span class="esc-db-table-status ' + statusClass + '">' + statusText + '</span></td>';
                        html += '<td>' + (table.exists ? table.row_count : '—') + '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table>';
                    
                    // Info-Box hinzufügen
                    if (missingTables.length > 0) {
                        html = '<div class="esc-db-notification error"><strong><?php esc_html_e( 'Achtung:', 'easy-status-check' ); ?></strong> ' + 
                               missingTables.length + ' <?php esc_html_e( 'Tabelle(n) fehlen. Bitte erstellen Sie diese mit dem Button unten.', 'easy-status-check' ); ?></div>' + html;
                        $('#esc-create-tables').prop('disabled', false);
                    } else {
                        html = '<div class="esc-db-notification success"><strong>✓</strong> <?php esc_html_e( 'Alle Tabellen sind vorhanden und einsatzbereit.', 'easy-status-check' ); ?></div>' + html;
                        $('#esc-create-tables').prop('disabled', true);
                    }
                    
                    // Repair-Button aktivieren wenn Tabellen vorhanden
                    var hasAnyTable = Object.values(tables).some(function(table) { return table.exists; });
                    $('#esc-repair-tables').prop('disabled', !hasAnyTable);
                    
                    $('#esc-db-status').html(html);
                }
                
                // Spin-Animation
                $('<style>.dashicons.spin { animation: spin 1s linear infinite; } @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>').appendTo('head');
            });
            </script>
        </div>
        <?php
    }

    /**
     * Render status overview page
     */
    public function render_status_overview() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Status Übersicht', 'easy-status-check' ); ?></h1>
            
            <div id="esc-status-overview">
                <?php
                if ( class_exists( 'ESC_Frontend_Display' ) ) {
                    $frontend = new ESC_Frontend_Display();
                    echo $frontend->render_status_display( array( 'layout' => 'admin' ) );
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Handle service actions
     */
    private function handle_service_action( $post_data ) {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        
        switch ( $post_data['action'] ) {
            case 'save_services':
                if ( isset( $post_data['services'] ) ) {
                    foreach ( $post_data['services'] as $service_data ) {
                        $wpdb->update(
                            $services_table,
                            array(
                                'enabled' => isset( $service_data['enabled'] ) ? 1 : 0
                            ),
                            array( 'id' => intval( $service_data['id'] ) )
                        );
                    }
                    
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . esc_html__( 'Services erfolgreich aktualisiert.', 'easy-status-check' ) . '</p></div>';
                    });
                }
                break;
        }
    }
}

// Class will be instantiated by main plugin class
