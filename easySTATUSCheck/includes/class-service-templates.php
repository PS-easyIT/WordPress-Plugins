<?php
/**
 * Service Templates Manager
 *
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Service_Templates {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_templates_page' ), 20 );
        add_action( 'wp_ajax_esc_add_template', array( $this, 'ajax_add_template' ) );
        add_action( 'wp_ajax_esc_save_custom_template', array( $this, 'ajax_save_custom_template' ) );
        add_action( 'wp_ajax_esc_delete_custom_template', array( $this, 'ajax_delete_custom_template' ) );
    }

    /**
     * Add templates page
     */
    public function add_templates_page() {
        add_submenu_page(
            'easy-status-check',
            __( 'Service-Templates', 'easy-status-check' ),
            __( 'Templates', 'easy-status-check' ),
            'manage_options',
            'easy-status-check-templates',
            array( $this, 'render_templates_page' )
        );
    }

    /**
     * Get extended predefined templates
     */
    public function get_extended_templates() {
        return array(
            'Microsoft 365' => array(
                array(
                    'name' => 'Microsoft 365 Status',
                    'url' => 'https://status.office.com/api/v2/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
                array(
                    'name' => 'Microsoft Teams',
                    'url' => 'https://status.office.com/api/v2/components/teams/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
                array(
                    'name' => 'Exchange Online',
                    'url' => 'https://status.office.com/api/v2/components/exchange/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
                array(
                    'name' => 'SharePoint Online',
                    'url' => 'https://status.office.com/api/v2/components/sharepoint/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
                array(
                    'name' => 'OneDrive for Business',
                    'url' => 'https://status.office.com/api/v2/components/onedrive/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
            ),
            'Deutsche Hosting-Anbieter' => array(
                array(
                    'name' => 'IONOS Status',
                    'url' => 'https://status.ionos.de',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                ),
                array(
                    'name' => 'Strato Status',
                    'url' => 'https://www.strato.de/status',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                ),
                array(
                    'name' => 'All-Inkl Status',
                    'url' => 'https://all-inkl.com/status',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                ),
                array(
                    'name' => 'Mittwald Status',
                    'url' => 'https://status.mittwald.de/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
                array(
                    'name' => 'Netcup Status',
                    'url' => 'https://www.netcup-status.de/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
            ),
            'CDN & Performance' => array(
                array(
                    'name' => 'Cloudflare CDN',
                    'url' => 'https://www.cloudflarestatus.com/api/v2/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
                array(
                    'name' => 'Fastly CDN',
                    'url' => 'https://status.fastly.com/api/v2/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
                array(
                    'name' => 'KeyCDN Status',
                    'url' => 'https://status.keycdn.com/api/v2/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
                array(
                    'name' => 'BunnyCDN Status',
                    'url' => 'https://status.bunny.net/api/v2/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
            ),
            'Monitoring & Analytics' => array(
                array(
                    'name' => 'Google Analytics',
                    'url' => 'https://www.google.com/appsstatus/dashboard/incidents',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                ),
                array(
                    'name' => 'New Relic Status',
                    'url' => 'https://status.newrelic.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
                array(
                    'name' => 'Datadog Status',
                    'url' => 'https://status.datadoghq.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
                array(
                    'name' => 'Pingdom Status',
                    'url' => 'https://status.pingdom.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
            ),
            'DNS Services' => array(
                array(
                    'name' => 'Cloudflare DNS (1.1.1.1)',
                    'url' => 'https://1.1.1.1',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 10,
                ),
                array(
                    'name' => 'Google DNS (8.8.8.8)',
                    'url' => 'https://dns.google',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 10,
                ),
                array(
                    'name' => 'Quad9 DNS',
                    'url' => 'https://www.quad9.net',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 10,
                ),
            ),
            'Security Services' => array(
                array(
                    'name' => 'Sucuri Status',
                    'url' => 'https://status.sucuri.net/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
                array(
                    'name' => 'Wordfence Status',
                    'url' => 'https://www.wordfence.com',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                ),
                array(
                    'name' => 'Let\'s Encrypt Status',
                    'url' => 'https://letsencrypt.status.io/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
            ),
            'Database Services' => array(
                array(
                    'name' => 'MongoDB Atlas Status',
                    'url' => 'https://status.cloud.mongodb.com/api/v2/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
                array(
                    'name' => 'Redis Cloud Status',
                    'url' => 'https://status.redislabs.com/api/v2/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator',
                ),
            ),
        );
    }

    /**
     * Render templates page
     */
    public function render_templates_page() {
        $predefined = new ESC_Predefined_Services();
        $all_templates = array_merge(
            $predefined->get_predefined_services(),
            $this->get_extended_templates()
        );
        
        $custom_templates = $this->get_custom_templates();
        
        ?>
        <div class="wrap">
            <h1><?php _e( 'Service-Templates', 'easy-status-check' ); ?></h1>
            
            <div class="esc-templates-header">
                <div class="esc-search-box">
                    <input type="text" id="esc-template-search" class="regular-text" placeholder="<?php esc_attr_e( 'Templates durchsuchen...', 'easy-status-check' ); ?>">
                    <button class="button" id="esc-clear-search"><?php _e( 'Zurücksetzen', 'easy-status-check' ); ?></button>
                </div>
                <div class="esc-template-stats">
                    <span class="esc-stat-item">
                        <strong><?php echo count( $all_templates, COUNT_RECURSIVE ) - count( $all_templates ); ?></strong>
                        <?php _e( 'Vordefinierte Templates', 'easy-status-check' ); ?>
                    </span>
                    <span class="esc-stat-item">
                        <strong><?php echo count( $custom_templates ); ?></strong>
                        <?php _e( 'Eigene Templates', 'easy-status-check' ); ?>
                    </span>
                </div>
            </div>
            
            <div class="esc-templates-container">
                <div class="esc-templates-sidebar">
                    <h2><?php _e( 'Kategorien', 'easy-status-check' ); ?></h2>
                    <ul class="esc-category-list">
                        <li><a href="#all" class="active" data-count="<?php echo count( $all_templates, COUNT_RECURSIVE ) - count( $all_templates ); ?>">
                            <?php _e( 'Alle Templates', 'easy-status-check' ); ?>
                            <span class="count">(<?php echo count( $all_templates, COUNT_RECURSIVE ) - count( $all_templates ); ?>)</span>
                        </a></li>
                        <?php foreach ( $all_templates as $category => $templates ) : ?>
                            <li><a href="#<?php echo esc_attr( sanitize_title( $category ) ); ?>" data-count="<?php echo count( $templates ); ?>">
                                <?php echo esc_html( $category ); ?>
                                <span class="count">(<?php echo count( $templates ); ?>)</span>
                            </a></li>
                        <?php endforeach; ?>
                        <?php if ( ! empty( $custom_templates ) ) : ?>
                            <li><a href="#custom" data-count="<?php echo count( $custom_templates ); ?>">
                                <?php _e( 'Eigene Templates', 'easy-status-check' ); ?>
                                <span class="count">(<?php echo count( $custom_templates ); ?>)</span>
                            </a></li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="esc-template-actions">
                        <button class="button button-primary button-large" id="esc-create-custom-template">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e( 'Eigenes Template erstellen', 'easy-status-check' ); ?>
                        </button>
                        <button class="button button-secondary" id="esc-bulk-add-templates">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e( 'Mehrere hinzufügen', 'easy-status-check' ); ?>
                        </button>
                    </div>
                </div>
                
                <div class="esc-templates-main">
                    <?php foreach ( $all_templates as $category => $templates ) : ?>
                        <div class="esc-template-category" data-category="<?php echo esc_attr( sanitize_title( $category ) ); ?>">
                            <h2><?php echo esc_html( $category ); ?></h2>
                            <div class="esc-templates-grid">
                                <?php foreach ( $templates as $template ) : ?>
                                    <div class="esc-template-card" data-template-name="<?php echo esc_attr( strtolower( $template['name'] ) ); ?>">
                                        <div class="esc-template-header">
                                            <h3><?php echo esc_html( $template['name'] ); ?></h3>
                                            <span class="esc-template-type esc-type-<?php echo esc_attr( $template['response_type'] ?? 'http' ); ?>">
                                                <?php echo esc_html( strtoupper( $template['response_type'] ?? 'HTTP' ) ); ?>
                                            </span>
                                        </div>
                                        <div class="esc-template-body">
                                            <p class="esc-template-url" title="<?php echo esc_attr( $template['url'] ); ?>">
                                                <?php echo esc_html( $template['url'] ); ?>
                                            </p>
                                            <div class="esc-template-meta">
                                                <span class="esc-meta-badge">
                                                    <span class="dashicons dashicons-clock"></span>
                                                    <?php printf( __( '%ds', 'easy-status-check' ), $template['timeout'] ); ?>
                                                </span>
                                                <span class="esc-meta-badge">
                                                    <span class="dashicons dashicons-yes-alt"></span>
                                                    <?php echo esc_html( $template['expected_code'] ); ?>
                                                </span>
                                                <span class="esc-meta-badge">
                                                    <span class="dashicons dashicons-category"></span>
                                                    <?php echo esc_html( ucfirst( $template['category'] ) ); ?>
                                                </span>
                                            </div>
                                            <?php if ( ! empty( $template['json_path'] ) ) : ?>
                                                <div class="esc-template-detail">
                                                    <small><strong>JSON Path:</strong> <?php echo esc_html( $template['json_path'] ); ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="esc-template-footer">
                                            <button class="button button-primary esc-add-template" 
                                                    data-template='<?php echo esc_attr( json_encode( $template ) ); ?>'
                                                    data-template-name="<?php echo esc_attr( $template['name'] ); ?>">
                                                <span class="dashicons dashicons-plus"></span>
                                                <?php _e( 'Hinzufügen', 'easy-status-check' ); ?>
                                            </button>
                                            <button class="button esc-preview-template" 
                                                    data-url="<?php echo esc_attr( $template['url'] ); ?>"
                                                    title="<?php esc_attr_e( 'URL in neuem Tab öffnen', 'easy-status-check' ); ?>">
                                                <span class="dashicons dashicons-external"></span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ( ! empty( $custom_templates ) ) : ?>
                        <div class="esc-template-category" data-category="custom">
                            <h2><?php _e( 'Eigene Templates', 'easy-status-check' ); ?></h2>
                            <div class="esc-templates-grid">
                                <?php foreach ( $custom_templates as $template ) : ?>
                                    <div class="esc-template-card esc-custom-template">
                                        <div class="esc-template-header">
                                            <h3><?php echo esc_html( $template['name'] ); ?></h3>
                                            <span class="esc-template-badge"><?php _e( 'Eigenes', 'easy-status-check' ); ?></span>
                                        </div>
                                        <div class="esc-template-body">
                                            <p class="esc-template-url"><?php echo esc_html( $template['url'] ); ?></p>
                                        </div>
                                        <div class="esc-template-footer">
                                            <button class="button button-primary esc-add-template" 
                                                    data-template='<?php echo esc_attr( json_encode( $template ) ); ?>'>
                                                <?php _e( 'Hinzufügen', 'easy-status-check' ); ?>
                                            </button>
                                            <button class="button esc-delete-custom-template" 
                                                    data-template-id="<?php echo esc_attr( $template['id'] ); ?>">
                                                <?php _e( 'Löschen', 'easy-status-check' ); ?>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
            .esc-templates-header {
                background: #fff;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 20px;
            }
            
            .esc-search-box {
                display: flex;
                gap: 10px;
                flex: 1;
                max-width: 500px;
            }
            
            .esc-search-box input {
                flex: 1;
            }
            
            .esc-template-stats {
                display: flex;
                gap: 30px;
            }
            
            .esc-stat-item {
                font-size: 14px;
                color: #666;
            }
            
            .esc-stat-item strong {
                color: #2271b1;
                font-size: 18px;
                margin-right: 5px;
            }
            
            .esc-templates-container {
                display: flex;
                gap: 30px;
                margin-top: 20px;
            }
            
            .esc-templates-sidebar {
                width: 250px;
                flex-shrink: 0;
            }
            
            .esc-category-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            
            .esc-category-list li {
                margin: 0;
            }
            
            .esc-category-list a {
                display: block;
                padding: 10px 15px;
                text-decoration: none;
                color: #2271b1;
                border-left: 3px solid transparent;
                transition: all 0.2s;
            }
            
            .esc-category-list a:hover,
            .esc-category-list a.active {
                background: #f0f0f1;
                border-left-color: #2271b1;
            }
            
            .esc-template-actions {
                margin-top: 20px;
            }
            
            .esc-templates-main {
                flex: 1;
            }
            
            .esc-template-category {
                margin-bottom: 40px;
            }
            
            .esc-template-category h2 {
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 2px solid #2271b1;
            }
            
            .esc-templates-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
            }
            
            .esc-template-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 8px;
                overflow: hidden;
                transition: all 0.3s;
            }
            
            .esc-template-card:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                transform: translateY(-2px);
            }
            
            .esc-template-header {
                padding: 15px;
                background: #f6f7f7;
                border-bottom: 1px solid #ccd0d4;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .esc-template-header h3 {
                margin: 0;
                font-size: 16px;
            }
            
            .esc-template-type {
                background: #2271b1;
                color: #fff;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: bold;
            }
            
            .esc-template-badge {
                background: #00a32a;
                color: #fff;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: bold;
            }
            
            .esc-template-body {
                padding: 15px;
            }
            
            .esc-template-url {
                font-family: monospace;
                font-size: 12px;
                color: #666;
                word-break: break-all;
                margin: 0 0 10px 0;
            }
            
            .esc-template-meta {
                display: flex;
                gap: 15px;
                font-size: 12px;
                color: #666;
            }
            
            .esc-template-footer {
                padding: 15px;
                background: #f6f7f7;
                border-top: 1px solid #ccd0d4;
                display: flex;
                gap: 10px;
            }
            
            .esc-template-footer .button {
                flex: 1;
            }
            
            .esc-preview-template {
                flex: 0 0 auto !important;
                padding: 6px 10px;
            }
            
            .esc-meta-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: #f0f0f1;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
            }
            
            .esc-meta-badge .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
            }
            
            .esc-template-detail {
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px solid #eee;
            }
            
            .esc-template-detail small {
                color: #666;
            }
            
            .esc-type-json {
                background: #4caf50;
            }
            
            .esc-type-rss {
                background: #ff9800;
            }
            
            .esc-type-http {
                background: #2196f3;
            }
            
            .esc-category-list .count {
                float: right;
                background: #f0f0f1;
                padding: 2px 8px;
                border-radius: 10px;
                font-size: 11px;
                color: #666;
            }
            
            .esc-category-list a.active .count {
                background: #2271b1;
                color: #fff;
            }
            
            .esc-template-actions {
                margin-top: 20px;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .esc-template-actions .button {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
            
            .esc-template-actions .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
            
            .button-success {
                background: #46b450 !important;
                border-color: #46b450 !important;
                color: #fff !important;
            }
            
            .button-error {
                background: #dc3232 !important;
                border-color: #dc3232 !important;
                color: #fff !important;
            }
            
            .dashicons.spin {
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            .esc-notification {
                position: fixed;
                top: 32px;
                right: -400px;
                background: #fff;
                padding: 15px 20px;
                border-left: 4px solid #2271b1;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                z-index: 999999;
                transition: right 0.3s ease;
                max-width: 350px;
            }
            
            .esc-notification.show {
                right: 20px;
            }
            
            .esc-notification-success {
                border-left-color: #46b450;
            }
            
            .esc-notification-error {
                border-left-color: #dc3232;
            }
            
            @media (max-width: 768px) {
                .esc-templates-header {
                    flex-direction: column;
                    align-items: stretch;
                }
                
                .esc-search-box {
                    max-width: none;
                }
                
                .esc-template-stats {
                    flex-direction: column;
                    gap: 10px;
                }
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Template hinzufügen
            $('.esc-add-template').on('click', function() {
                var button = $(this);
                var template = button.data('template');
                var templateName = button.data('template-name');
                var originalHtml = button.html();
                
                button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e( 'Wird hinzugefügt...', 'easy-status-check' ); ?>');
                
                $.post(ajaxurl, {
                    action: 'esc_add_template',
                    template: JSON.stringify(template),
                    nonce: '<?php echo wp_create_nonce( 'esc_add_template' ); ?>'
                }, function(response) {
                    if (response.success) {
                        button.html('<span class="dashicons dashicons-yes"></span> <?php _e( 'Hinzugefügt', 'easy-status-check' ); ?>').addClass('button-success');
                        
                        // Erfolgs-Notification
                        showNotification('success', templateName + ' <?php _e( 'wurde erfolgreich hinzugefügt', 'easy-status-check' ); ?>');
                        
                        setTimeout(function() {
                            button.prop('disabled', false).html(originalHtml).removeClass('button-success');
                        }, 3000);
                    } else {
                        button.html('<span class="dashicons dashicons-no"></span> <?php _e( 'Fehler', 'easy-status-check' ); ?>').addClass('button-error');
                        showNotification('error', response.data.message);
                        
                        setTimeout(function() {
                            button.prop('disabled', false).html(originalHtml).removeClass('button-error');
                        }, 3000);
                    }
                });
            });
            
            // Kategorie-Filter
            $('.esc-category-list a').on('click', function(e) {
                e.preventDefault();
                $('.esc-category-list a').removeClass('active');
                $(this).addClass('active');
                
                var category = $(this).attr('href').substring(1);
                if (category === 'all') {
                    $('.esc-template-category').show();
                } else {
                    $('.esc-template-category').hide();
                    $('.esc-template-category[data-category="' + category + '"]').show();
                }
                
                // Suche zurücksetzen
                $('#esc-template-search').val('');
            });
            
            // Suche
            $('#esc-template-search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                
                if (searchTerm === '') {
                    $('.esc-template-card').show();
                    return;
                }
                
                $('.esc-template-card').each(function() {
                    var templateName = $(this).data('template-name');
                    if (templateName.indexOf(searchTerm) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
            
            // Suche zurücksetzen
            $('#esc-clear-search').on('click', function() {
                $('#esc-template-search').val('').trigger('input');
            });
            
            // Preview öffnen
            $('.esc-preview-template').on('click', function() {
                var url = $(this).data('url');
                window.open(url, '_blank');
            });
            
            // Notification System
            function showNotification(type, message) {
                var notification = $('<div class="esc-notification esc-notification-' + type + '">' + message + '</div>');
                $('body').append(notification);
                
                setTimeout(function() {
                    notification.addClass('show');
                }, 100);
                
                setTimeout(function() {
                    notification.removeClass('show');
                    setTimeout(function() {
                        notification.remove();
                    }, 300);
                }, 3000);
            }
            
            // Bulk Add (Placeholder)
            $('#esc-bulk-add-templates').on('click', function() {
                alert('<?php _e( 'Bulk-Hinzufügen-Funktion kommt bald!', 'easy-status-check' ); ?>');
            });
            
            // Custom Template erstellen (Placeholder)
            $('#esc-create-custom-template').on('click', function() {
                alert('<?php _e( 'Custom-Template-Funktion kommt bald!', 'easy-status-check' ); ?>');
            });
        });
        </script>
        <?php
    }

    /**
     * Get custom templates
     */
    private function get_custom_templates() {
        return get_option( 'esc_custom_templates', array() );
    }

    /**
     * AJAX: Add template
     */
    public function ajax_add_template() {
        check_ajax_referer( 'esc_add_template', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }
        
        $template_json = isset( $_POST['template'] ) ? stripslashes( $_POST['template'] ) : '';
        $template = json_decode( $template_json, true );
        
        if ( ! $template || ! is_array( $template ) ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige Template-Daten', 'easy-status-check' ) ) );
        }
        
        // Validate required fields
        if ( empty( $template['name'] ) || empty( $template['url'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Name und URL sind erforderlich', 'easy-status-check' ) ) );
        }
        
        $predefined = new ESC_Predefined_Services();
        $result = $predefined->add_predefined_services( array( $template ) );
        
        if ( $result > 0 ) {
            wp_send_json_success( array( 
                'message' => __( 'Service erfolgreich hinzugefügt', 'easy-status-check' ),
                'count' => $result
            ) );
        } else {
            // Get detailed error messages
            $errors = get_transient( 'esc_last_service_errors' );
            $error_message = __( 'Service konnte nicht hinzugefügt werden', 'easy-status-check' );
            
            if ( ! empty( $errors ) ) {
                $error_message .= ': ' . implode( ', ', $errors );
                delete_transient( 'esc_last_service_errors' );
            }
            
            wp_send_json_error( array( 
                'message' => $error_message,
                'errors' => $errors
            ) );
        }
    }

    /**
     * AJAX: Save custom template
     */
    public function ajax_save_custom_template() {
        check_ajax_referer( 'esc_save_custom_template', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }
        
        $custom_templates = $this->get_custom_templates();
        $new_template = array(
            'id' => uniqid(),
            'name' => sanitize_text_field( $_POST['name'] ),
            'url' => esc_url_raw( $_POST['url'] ),
            'category' => sanitize_text_field( $_POST['category'] ),
            'method' => sanitize_text_field( $_POST['method'] ),
            'expected_code' => sanitize_text_field( $_POST['expected_code'] ),
            'timeout' => intval( $_POST['timeout'] ),
        );
        
        $custom_templates[] = $new_template;
        update_option( 'esc_custom_templates', $custom_templates );
        
        wp_send_json_success( array( 'message' => __( 'Template gespeichert', 'easy-status-check' ) ) );
    }

    /**
     * AJAX: Delete custom template
     */
    public function ajax_delete_custom_template() {
        check_ajax_referer( 'esc_delete_custom_template', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }
        
        $template_id = sanitize_text_field( $_POST['template_id'] );
        $custom_templates = $this->get_custom_templates();
        
        $custom_templates = array_filter( $custom_templates, function( $template ) use ( $template_id ) {
            return $template['id'] !== $template_id;
        });
        
        update_option( 'esc_custom_templates', array_values( $custom_templates ) );
        
        wp_send_json_success( array( 'message' => __( 'Template gelöscht', 'easy-status-check' ) ) );
    }
}
