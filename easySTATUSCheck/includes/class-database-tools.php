<?php
/**
 * Database Tools for easySTATUSCheck
 *
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Database_Tools {

    public function __construct() {
        add_action( 'wp_ajax_esc_check_tables', array( $this, 'ajax_check_tables' ) );
        add_action( 'wp_ajax_esc_create_tables', array( $this, 'ajax_create_tables' ) );
        add_action( 'wp_ajax_esc_repair_tables', array( $this, 'ajax_repair_tables' ) );
    }

    /**
     * Get all required tables
     */
    public function get_required_tables() {
        global $wpdb;
        
        return array(
            'services' => array(
                'name' => $wpdb->prefix . 'esc_services',
                'label' => __( 'Services', 'easy-status-check' ),
                'description' => __( 'Speichert alle überwachten Services', 'easy-status-check' ),
            ),
            'logs' => array(
                'name' => $wpdb->prefix . 'esc_status_logs',
                'label' => __( 'Status Logs', 'easy-status-check' ),
                'description' => __( 'Speichert alle Status-Prüfungen', 'easy-status-check' ),
            ),
            'incidents' => array(
                'name' => $wpdb->prefix . 'esc_incidents',
                'label' => __( 'Incidents', 'easy-status-check' ),
                'description' => __( 'Speichert Ausfälle und Störungen', 'easy-status-check' ),
            ),
            'notifications' => array(
                'name' => $wpdb->prefix . 'esc_notifications',
                'label' => __( 'Benachrichtigungen', 'easy-status-check' ),
                'description' => __( 'Speichert gesendete Benachrichtigungen', 'easy-status-check' ),
            ),
        );
    }

    /**
     * Check if table exists
     */
    public function table_exists( $table_name ) {
        global $wpdb;
        
        $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
        return $wpdb->get_var( $query ) === $table_name;
    }

    /**
     * Get table status
     */
    public function get_tables_status() {
        $tables = $this->get_required_tables();
        $status = array();
        
        foreach ( $tables as $key => $table ) {
            $exists = $this->table_exists( $table['name'] );
            $row_count = 0;
            
            if ( $exists ) {
                global $wpdb;
                $row_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table['name']}" );
            }
            
            $status[ $key ] = array(
                'name' => $table['name'],
                'label' => $table['label'],
                'description' => $table['description'],
                'exists' => $exists,
                'row_count' => intval( $row_count ),
            );
        }
        
        return $status;
    }

    /**
     * Create all tables
     */
    public function create_all_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $created = array();
        $errors = array();
        
        // Services table
        $table_name = $wpdb->prefix . 'esc_services';
        if ( ! $this->table_exists( $table_name ) ) {
            $sql = "CREATE TABLE $table_name (
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
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
            
            if ( $this->table_exists( $table_name ) ) {
                $created[] = 'services';
            } else {
                $errors[] = 'services';
            }
        }
        
        // Status logs table
        $table_name_logs = $wpdb->prefix . 'esc_status_logs';
        if ( ! $this->table_exists( $table_name_logs ) ) {
            $sql_logs = "CREATE TABLE $table_name_logs (
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
                KEY checked_at (checked_at)
            ) $charset_collate;";
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql_logs );
            
            if ( $this->table_exists( $table_name_logs ) ) {
                $created[] = 'logs';
            } else {
                $errors[] = 'logs';
            }
        }
        
        // Incidents table
        $table_name_incidents = $wpdb->prefix . 'esc_incidents';
        if ( ! $this->table_exists( $table_name_incidents ) ) {
            $sql_incidents = "CREATE TABLE $table_name_incidents (
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
                KEY resolved_at (resolved_at)
            ) $charset_collate;";
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql_incidents );
            
            if ( $this->table_exists( $table_name_incidents ) ) {
                $created[] = 'incidents';
            } else {
                $errors[] = 'incidents';
            }
        }
        
        // Notifications table
        $table_name_notifications = $wpdb->prefix . 'esc_notifications';
        if ( ! $this->table_exists( $table_name_notifications ) ) {
            $sql_notifications = "CREATE TABLE $table_name_notifications (
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
                KEY sent_at (sent_at)
            ) $charset_collate;";
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql_notifications );
            
            if ( $this->table_exists( $table_name_notifications ) ) {
                $created[] = 'notifications';
            } else {
                $errors[] = 'notifications';
            }
        }
        
        return array(
            'created' => $created,
            'errors' => $errors,
        );
    }

    /**
     * Repair/optimize tables
     */
    public function repair_tables() {
        global $wpdb;
        
        $tables = $this->get_required_tables();
        $repaired = array();
        
        foreach ( $tables as $key => $table ) {
            if ( $this->table_exists( $table['name'] ) ) {
                $wpdb->query( "REPAIR TABLE {$table['name']}" );
                $wpdb->query( "OPTIMIZE TABLE {$table['name']}" );
                $repaired[] = $key;
            }
        }
        
        return $repaired;
    }

    /**
     * AJAX: Check tables status
     */
    public function ajax_check_tables() {
        check_ajax_referer( 'esc_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }
        
        $status = $this->get_tables_status();
        
        wp_send_json_success( array(
            'tables' => $status,
            'message' => __( 'Tabellen-Status erfolgreich geprüft', 'easy-status-check' ),
        ) );
    }

    /**
     * AJAX: Create missing tables
     */
    public function ajax_create_tables() {
        check_ajax_referer( 'esc_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }
        
        $result = $this->create_all_tables();
        
        if ( ! empty( $result['errors'] ) ) {
            wp_send_json_error( array(
                'message' => sprintf(
                    __( 'Fehler beim Erstellen von %d Tabelle(n)', 'easy-status-check' ),
                    count( $result['errors'] )
                ),
                'created' => $result['created'],
                'errors' => $result['errors'],
            ) );
        }
        
        wp_send_json_success( array(
            'message' => sprintf(
                __( '%d Tabelle(n) erfolgreich erstellt', 'easy-status-check' ),
                count( $result['created'] )
            ),
            'created' => $result['created'],
        ) );
    }

    /**
     * AJAX: Repair tables
     */
    public function ajax_repair_tables() {
        check_ajax_referer( 'esc_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }
        
        $repaired = $this->repair_tables();
        
        wp_send_json_success( array(
            'message' => sprintf(
                __( '%d Tabelle(n) repariert und optimiert', 'easy-status-check' ),
                count( $repaired )
            ),
            'repaired' => $repaired,
        ) );
    }
}
