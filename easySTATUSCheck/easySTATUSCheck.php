<?php
/**
 * Plugin Name:       easySTATUSCheck
 * Plugin URI:        https://phin.network
 * Description:       Umfassende Status-Überwachung für Cloud-Services, Hosting-Anbieter und benutzerdefinierte Services. Ideal für IT-Administratoren und Systemmonitoring.
 * Version:           1.0.0
 * Author:            PHIN IT Solutions
 * Author URI:        https://phin.network
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       easy-status-check
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'EASY_STATUS_CHECK_VERSION', '1.0.0' );
define( 'EASY_STATUS_CHECK_DIR', plugin_dir_path( __FILE__ ) );
define( 'EASY_STATUS_CHECK_URL', plugin_dir_url( __FILE__ ) );
define( 'EASY_STATUS_CHECK_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 */
final class Easy_Status_Check {

    /**
     * The single instance of the class.
     */
    private static $_instance = null;

    /**
     * Main Easy_Status_Check Instance.
     * Ensures only one instance of the class is loaded.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->setup_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Setup plugin constants.
     */
    private function setup_constants() {
        // Plugin version
        define( 'ESC_VERSION', EASY_STATUS_CHECK_VERSION );
    }

    /**
     * Include required files.
     */
    private function includes() {
        // Core features
        // Include shared admin UX
        if ( file_exists( EASY_STATUS_CHECK_DIR . '../easy-admin-integration.php' ) ) {
            require_once EASY_STATUS_CHECK_DIR . '../easy-admin-integration.php';
        }
        
        // Include asset manager for performance
        if ( file_exists( EASY_STATUS_CHECK_DIR . '../easy-design-system/asset-manager.php' ) ) {
            require_once EASY_STATUS_CHECK_DIR . '../easy-design-system/asset-manager.php';
        }
        
        require_once EASY_STATUS_CHECK_DIR . 'includes/class-service-post-type.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/class-incident-tracker.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/class-status-history.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/class-service-templates.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/class-performance-optimizer.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/class-security-manager.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/class-database-tools.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/class-support-tools.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/class-public-status-page.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/class-public-pages.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/admin.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/status-checker.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/frontend-display.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/predefined-services.php';
        require_once EASY_STATUS_CHECK_DIR . 'includes/ajax-handler.php';
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'plugins_loaded', array( $this, 'init_features' ) );
        
        // Activation and deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        
        // Admin hooks
        add_action( 'admin_init', array( $this, 'check_dependencies' ) );
        add_action( 'init', array( $this, 'init_shortcodes' ) );
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'easy-status-check', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Initialize all features after textdomain is loaded.
     */
    public function init_features() {
        // Initialize classes
        if ( class_exists( 'ESC_Service_Post_Type' ) && ! isset( $GLOBALS['esc_service_post_type'] ) ) {
            $GLOBALS['esc_service_post_type'] = new ESC_Service_Post_Type();
        }
        
        if ( class_exists( 'ESC_Incident_Tracker' ) && ! isset( $GLOBALS['esc_incident_tracker'] ) ) {
            $GLOBALS['esc_incident_tracker'] = new ESC_Incident_Tracker();
        }
        
        if ( class_exists( 'ESC_Status_History' ) && ! isset( $GLOBALS['esc_status_history'] ) ) {
            $GLOBALS['esc_status_history'] = new ESC_Status_History();
        }
        
        if ( class_exists( 'ESC_Service_Templates' ) && ! isset( $GLOBALS['esc_service_templates'] ) ) {
            $GLOBALS['esc_service_templates'] = new ESC_Service_Templates();
        }
        
        if ( class_exists( 'ESC_Performance_Optimizer' ) && ! isset( $GLOBALS['esc_performance'] ) ) {
            $GLOBALS['esc_performance'] = new ESC_Performance_Optimizer();
        }
        
        if ( class_exists( 'ESC_Security_Manager' ) && ! isset( $GLOBALS['esc_security'] ) ) {
            $GLOBALS['esc_security'] = new ESC_Security_Manager();
        }
        
        if ( class_exists( 'ESC_Database_Tools' ) && ! isset( $GLOBALS['esc_database_tools'] ) ) {
            $GLOBALS['esc_database_tools'] = new ESC_Database_Tools();
        }
        
        if ( class_exists( 'ESC_Support_Tools' ) && ! isset( $GLOBALS['esc_support_tools'] ) ) {
            $GLOBALS['esc_support_tools'] = new ESC_Support_Tools();
        }
        
        if ( class_exists( 'ESC_Public_Status_Page' ) && ! isset( $GLOBALS['esc_public_status'] ) ) {
            $GLOBALS['esc_public_status'] = new ESC_Public_Status_Page();
        }
        
        if ( class_exists( 'ESC_Public_Pages' ) && ! isset( $GLOBALS['esc_public_pages'] ) ) {
            $GLOBALS['esc_public_pages'] = new ESC_Public_Pages();
        }
        
        if ( class_exists( 'ESC_Admin' ) && ! isset( $GLOBALS['esc_admin'] ) ) {
            $GLOBALS['esc_admin'] = new ESC_Admin();
        }
        
        if ( class_exists( 'ESC_Status_Checker' ) && ! isset( $GLOBALS['esc_status_checker'] ) ) {
            $GLOBALS['esc_status_checker'] = new ESC_Status_Checker();
        }
        
        if ( class_exists( 'ESC_Frontend_Display' ) && ! isset( $GLOBALS['esc_frontend'] ) ) {
            $GLOBALS['esc_frontend'] = new ESC_Frontend_Display();
        }
        
        if ( class_exists( 'ESC_Ajax_Handler' ) && ! isset( $GLOBALS['esc_ajax'] ) ) {
            $GLOBALS['esc_ajax'] = new ESC_Ajax_Handler();
        }
    }
    
    /**
     * Initialize shortcodes.
     */
    public function init_shortcodes() {
        add_shortcode( 'easy_status_display', array( $this, 'status_display_shortcode' ) );
    }
    
    /**
     * Status display shortcode.
     */
    public function status_display_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'category' => 'all',
            'layout' => 'grid',
            'refresh' => '300'
        ), $atts );
        
        if ( class_exists( 'ESC_Frontend_Display' ) ) {
            $frontend = new ESC_Frontend_Display();
            return $frontend->render_status_display( $atts );
        }
        
        return '';
    }
    
    /**
     * Plugin activation.
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Check if we need to migrate existing installations
        $current_version = get_option( 'esc_plugin_version', '0.0.0' );
        if ( version_compare( $current_version, EASY_STATUS_CHECK_VERSION, '<' ) ) {
            $this->migrate_database( $current_version );
        }
        
        // Set default options
        update_option( 'esc_plugin_version', EASY_STATUS_CHECK_VERSION );
        add_option( 'esc_activation_time', current_time( 'timestamp' ) );
        
        // Create default services
        $this->create_default_services();
        
        // Schedule status checks
        if ( ! wp_next_scheduled( 'esc_status_check_cron' ) ) {
            wp_schedule_event( time(), 'every_five_minutes', 'esc_status_check_cron' );
        }
        
        // Initialize public pages to register rewrite rules
        if ( class_exists( 'ESC_Public_Pages' ) ) {
            $public_pages = new ESC_Public_Pages();
            $public_pages->register_public_pages();
        }
        
        // Flush rewrite rules to activate public pages
        flush_rewrite_rules();
    }
    
    /**
     * Migrate database for existing installations.
     */
    private function migrate_database( $from_version ) {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        
        // Add new columns if they don't exist
        $columns = $wpdb->get_col( "DESCRIBE $services_table" );
        
        if ( ! in_array( 'response_type', $columns ) ) {
            $wpdb->query( "ALTER TABLE $services_table ADD COLUMN response_type varchar(20) DEFAULT NULL AFTER custom_headers" );
        }
        
        if ( ! in_array( 'json_path', $columns ) ) {
            $wpdb->query( "ALTER TABLE $services_table ADD COLUMN json_path varchar(255) DEFAULT NULL AFTER response_type" );
        }
        
        if ( ! in_array( 'check_content', $columns ) ) {
            $wpdb->query( "ALTER TABLE $services_table ADD COLUMN check_content tinyint(1) NOT NULL DEFAULT 0 AFTER json_path" );
        }
        
        // Add index for response_type if it doesn't exist
        $indexes = $wpdb->get_results( "SHOW INDEX FROM $services_table WHERE Key_name = 'response_type'" );
        if ( empty( $indexes ) ) {
            $wpdb->query( "ALTER TABLE $services_table ADD KEY response_type (response_type)" );
        }
    }
    
    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook( 'esc_status_check_cron' );
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Check plugin dependencies.
     */
    public function check_dependencies() {
        global $wp_version;
        
        $min_wp_version = '5.0';
        $min_php_version = '7.4';
        
        if ( version_compare( $wp_version, $min_wp_version, '<' ) ) {
            add_action( 'admin_notices', function() use ( $min_wp_version ) {
                echo '<div class="notice notice-error"><p>';
                printf( 
                    esc_html__( 'easySTATUSCheck benötigt WordPress %s oder höher. Ihre Version: %s', 'easy-status-check' ),
                    $min_wp_version,
                    $GLOBALS['wp_version']
                );
                echo '</p></div>';
            });
        }
        
        if ( version_compare( PHP_VERSION, $min_php_version, '<' ) ) {
            add_action( 'admin_notices', function() use ( $min_php_version ) {
                echo '<div class="notice notice-error"><p>';
                printf( 
                    esc_html__( 'easySTATUSCheck benötigt PHP %s oder höher. Ihre Version: %s', 'easy-status-check' ),
                    $min_php_version,
                    PHP_VERSION
                );
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Create database tables.
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Services table
        $table_name = $wpdb->prefix . 'esc_services';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url varchar(2048) NOT NULL,
            category varchar(100) NOT NULL DEFAULT 'custom',
            method varchar(10) NOT NULL DEFAULT 'GET',
            timeout int(5) NOT NULL DEFAULT 10,
            expected_code varchar(20) NOT NULL DEFAULT '200',
            check_interval int(10) NOT NULL DEFAULT 300,
            enabled tinyint(1) NOT NULL DEFAULT 1,
            notify_email tinyint(1) NOT NULL DEFAULT 1,
            custom_headers longtext,
            response_type varchar(20) DEFAULT NULL,
            json_path varchar(255) DEFAULT NULL,
            check_content tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category (category),
            KEY enabled (enabled),
            KEY response_type (response_type)
        ) $charset_collate;";
        
        // Status logs table
        $table_name_logs = $wpdb->prefix . 'esc_status_logs';
        $sql_logs = "CREATE TABLE IF NOT EXISTS $table_name_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            service_id bigint(20) unsigned NOT NULL,
            status enum('online','offline','warning') NOT NULL,
            http_code varchar(20),
            response_time float,
            error_message text,
            checked_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY service_id (service_id),
            KEY status (status),
            KEY checked_at (checked_at),
            FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}esc_services(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Incidents table
        $table_name_incidents = $wpdb->prefix . 'esc_incidents';
        $sql_incidents = "CREATE TABLE IF NOT EXISTS $table_name_incidents (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            service_id bigint(20) unsigned NOT NULL,
            severity enum('minor','major','critical') NOT NULL DEFAULT 'minor',
            title varchar(255) NOT NULL,
            description text,
            started_at datetime NOT NULL,
            resolved_at datetime DEFAULT NULL,
            duration int(11) DEFAULT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY service_id (service_id),
            KEY severity (severity),
            KEY started_at (started_at),
            KEY resolved_at (resolved_at),
            FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}esc_services(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Notifications table
        $table_name_notifications = $wpdb->prefix . 'esc_notifications';
        $sql_notifications = "CREATE TABLE IF NOT EXISTS $table_name_notifications (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            service_id bigint(20) unsigned NOT NULL,
            incident_id bigint(20) unsigned DEFAULT NULL,
            channel enum('email','webhook','slack','discord') NOT NULL DEFAULT 'email',
            recipient varchar(255) NOT NULL,
            subject varchar(255),
            message text,
            status enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
            sent_at datetime DEFAULT NULL,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY service_id (service_id),
            KEY incident_id (incident_id),
            KEY channel (channel),
            KEY status (status),
            KEY sent_at (sent_at),
            FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}esc_services(id) ON DELETE CASCADE,
            FOREIGN KEY (incident_id) REFERENCES {$wpdb->prefix}esc_incidents(id) ON DELETE SET NULL
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        dbDelta( $sql_logs );
        dbDelta( $sql_incidents );
        dbDelta( $sql_notifications );
    }
    
    /**
     * Create default predefined services.
     */
    private function create_default_services() {
        if ( class_exists( 'ESC_Predefined_Services' ) ) {
            $predefined = new ESC_Predefined_Services();
            $predefined->create_default_services();
        }
    }
}

/**
 * Add custom cron interval for 5 minutes.
 */
add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300,
        'display'  => __( 'Alle 5 Minuten', 'easy-status-check' )
    );
    return $schedules;
});

/**
 * Register with Admin UX System
 */
add_action( 'init', function() {
    if ( function_exists( 'easy_register_admin_ux' ) ) {
        easy_register_admin_ux( 'easy-status-check', array(
            'auto_save',
            'enhanced_tables', 
            'modal_forms'
        ) );
    }
});

/**
 * Main function for returning the main instance of the class.
 */
function Easy_Status_Check() {
    return Easy_Status_Check::instance();
}

// Get the plugin running.
Easy_Status_Check();
