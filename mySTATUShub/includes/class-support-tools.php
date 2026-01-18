<?php
/**
 * Support Tools - Database and Cron Actions
 *
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Support_Tools {

    public function __construct() {
        add_action( 'wp_ajax_esc_db_check', array( $this, 'ajax_db_check' ) );
        add_action( 'wp_ajax_esc_db_create', array( $this, 'ajax_db_create' ) );
        add_action( 'wp_ajax_esc_db_optimize', array( $this, 'ajax_db_optimize' ) );
        add_action( 'wp_ajax_esc_db_repair', array( $this, 'ajax_db_repair' ) );
        add_action( 'wp_ajax_esc_cron_check', array( $this, 'ajax_cron_check' ) );
        add_action( 'wp_ajax_esc_cron_run', array( $this, 'ajax_cron_run' ) );
    }

    /**
     * Check database tables
     */
    public function ajax_db_check() {
        check_ajax_referer( 'esc_db_action', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }

        global $wpdb;
        $tables = array( 'esc_services', 'esc_status_logs', 'esc_incidents', 'esc_notifications' );
        $missing = array();
        $existing = array();

        foreach ( $tables as $table ) {
            $table_name = $wpdb->prefix . $table;
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
                $existing[] = $table;
            } else {
                $missing[] = $table;
            }
        }

        if ( empty( $missing ) ) {
            wp_send_json_success( array( 
                'message' => sprintf( __( '✓ Alle %d Tabellen existieren und sind bereit.', 'easy-status-check' ), count( $existing ) )
            ) );
        } else {
            wp_send_json_error( array( 
                'message' => sprintf( __( '⚠ %d Tabellen fehlen: %s', 'easy-status-check' ), count( $missing ), implode( ', ', $missing ) )
            ) );
        }
    }

    /**
     * Create missing database tables
     */
    public function ajax_db_create() {
        check_ajax_referer( 'esc_db_action', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }

        // Trigger database creation
        require_once EASY_STATUS_CHECK_DIR . 'includes/class-database.php';
        $database = new ESC_Database();
        $database->create_tables();

        wp_send_json_success( array( 
            'message' => __( '✓ Datenbank-Tabellen wurden erfolgreich erstellt/aktualisiert.', 'easy-status-check' )
        ) );
    }

    /**
     * Optimize database tables
     */
    public function ajax_db_optimize() {
        check_ajax_referer( 'esc_db_action', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }

        global $wpdb;
        $tables = array( 'esc_services', 'esc_status_logs', 'esc_incidents', 'esc_notifications' );
        $optimized = 0;

        foreach ( $tables as $table ) {
            $table_name = $wpdb->prefix . $table;
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
                $wpdb->query( "OPTIMIZE TABLE $table_name" );
                $optimized++;
            }
        }

        wp_send_json_success( array( 
            'message' => sprintf( __( '✓ %d Tabellen wurden optimiert.', 'easy-status-check' ), $optimized )
        ) );
    }

    /**
     * Repair database tables
     */
    public function ajax_db_repair() {
        check_ajax_referer( 'esc_db_action', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }

        global $wpdb;
        $tables = array( 'esc_services', 'esc_status_logs', 'esc_incidents', 'esc_notifications' );
        $repaired = 0;

        foreach ( $tables as $table ) {
            $table_name = $wpdb->prefix . $table;
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
                $wpdb->query( "REPAIR TABLE $table_name" );
                $repaired++;
            }
        }

        wp_send_json_success( array( 
            'message' => sprintf( __( '✓ %d Tabellen wurden repariert.', 'easy-status-check' ), $repaired )
        ) );
    }

    /**
     * Check cron status
     */
    public function ajax_cron_check() {
        check_ajax_referer( 'esc_cron_action', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }

        $cron_jobs = _get_cron_array();
        $esc_crons = 0;

        foreach ( $cron_jobs as $timestamp => $cron ) {
            foreach ( $cron as $hook => $details ) {
                if ( strpos( $hook, 'esc_' ) === 0 ) {
                    $esc_crons++;
                }
            }
        }

        if ( $esc_crons > 0 ) {
            wp_send_json_success( array( 
                'message' => sprintf( __( '✓ %d Cron-Jobs sind geplant und aktiv.', 'easy-status-check' ), $esc_crons )
            ) );
        } else {
            wp_send_json_error( array( 
                'message' => __( '⚠ Keine Cron-Jobs gefunden. Möglicherweise müssen Services aktiviert werden.', 'easy-status-check' )
            ) );
        }
    }

    /**
     * Run cron manually
     */
    public function ajax_cron_run() {
        check_ajax_referer( 'esc_cron_action', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }

        // Trigger service checks manually
        do_action( 'esc_check_services' );

        wp_send_json_success( array( 
            'message' => __( '✓ Service-Checks wurden manuell ausgeführt.', 'easy-status-check' )
        ) );
    }
}
