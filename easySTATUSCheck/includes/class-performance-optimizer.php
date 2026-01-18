<?php
/**
 * Performance Optimizer
 *
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Performance_Optimizer {

    private $cache_group = 'esc_status_cache';
    private $queue_option = 'esc_check_queue';

    public function __construct() {
        add_action( 'init', array( $this, 'init_cache' ) );
        add_action( 'esc_status_check_cron', array( $this, 'process_queue' ) );
        add_action( 'esc_cleanup_old_logs', array( $this, 'cleanup_old_logs' ) );
        add_filter( 'cron_schedules', array( $this, 'add_custom_cron_schedules' ) );
        
        if ( ! wp_next_scheduled( 'esc_cleanup_old_logs' ) ) {
            wp_schedule_event( time(), 'daily', 'esc_cleanup_old_logs' );
        }
    }

    /**
     * Initialize cache
     */
    public function init_cache() {
        wp_cache_add_global_groups( array( $this->cache_group ) );
    }

    /**
     * Add custom cron schedules
     */
    public function add_custom_cron_schedules( $schedules ) {
        $schedules['every_minute'] = array(
            'interval' => 60,
            'display'  => __( 'Jede Minute', 'easy-status-check' )
        );
        
        $schedules['every_fifteen_minutes'] = array(
            'interval' => 900,
            'display'  => __( 'Alle 15 Minuten', 'easy-status-check' )
        );
        
        $schedules['every_thirty_minutes'] = array(
            'interval' => 1800,
            'display'  => __( 'Alle 30 Minuten', 'easy-status-check' )
        );
        
        return $schedules;
    }

    /**
     * Get cached status
     */
    public function get_cached_status( $service_id ) {
        $cache_key = 'service_status_' . $service_id;
        $cached = wp_cache_get( $cache_key, $this->cache_group );
        
        if ( false !== $cached ) {
            return $cached;
        }
        
        return false;
    }

    /**
     * Set cached status
     */
    public function set_cached_status( $service_id, $status_data, $expiration = 300 ) {
        $cache_key = 'service_status_' . $service_id;
        wp_cache_set( $cache_key, $status_data, $this->cache_group, $expiration );
        
        set_transient( $cache_key, $status_data, $expiration );
    }

    /**
     * Clear cache for service
     */
    public function clear_service_cache( $service_id ) {
        $cache_key = 'service_status_' . $service_id;
        wp_cache_delete( $cache_key, $this->cache_group );
        delete_transient( $cache_key );
    }

    /**
     * Add service to check queue
     */
    public function add_to_queue( $service_id, $priority = 'normal' ) {
        $queue = get_option( $this->queue_option, array() );
        
        if ( ! isset( $queue[ $priority ] ) ) {
            $queue[ $priority ] = array();
        }
        
        if ( ! in_array( $service_id, $queue[ $priority ] ) ) {
            $queue[ $priority ][] = $service_id;
            update_option( $this->queue_option, $queue );
        }
    }

    /**
     * Process check queue
     */
    public function process_queue() {
        $queue = get_option( $this->queue_option, array() );
        
        if ( empty( $queue ) ) {
            return;
        }
        
        $priorities = array( 'high', 'normal', 'low' );
        $processed = 0;
        $max_per_run = apply_filters( 'esc_max_checks_per_run', 10 );
        
        foreach ( $priorities as $priority ) {
            if ( ! isset( $queue[ $priority ] ) || empty( $queue[ $priority ] ) ) {
                continue;
            }
            
            while ( $processed < $max_per_run && ! empty( $queue[ $priority ] ) ) {
                $service_id = array_shift( $queue[ $priority ] );
                
                $this->process_service_check( $service_id );
                
                $processed++;
            }
            
            if ( $processed >= $max_per_run ) {
                break;
            }
        }
        
        update_option( $this->queue_option, $queue );
    }

    /**
     * Process single service check
     */
    private function process_service_check( $service_id ) {
        if ( ! class_exists( 'ESC_Status_Checker' ) ) {
            return;
        }
        
        global $wpdb;
        $services_table = $wpdb->prefix . 'esc_services';
        $service = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $services_table WHERE id = %d",
            $service_id
        ) );
        
        if ( ! $service ) {
            return;
        }
        
        $checker = new ESC_Status_Checker();
        $result = $checker->check_service_status( $service );
        
        if ( $result ) {
            $this->set_cached_status( $service_id, $result );
        }
    }

    /**
     * Batch process services
     */
    public function batch_process_services( $service_ids, $batch_size = 5 ) {
        $batches = array_chunk( $service_ids, $batch_size );
        
        foreach ( $batches as $batch ) {
            $this->process_batch( $batch );
            
            usleep( 100000 );
        }
    }

    /**
     * Process batch of services
     */
    private function process_batch( $service_ids ) {
        foreach ( $service_ids as $service_id ) {
            $this->process_service_check( $service_id );
        }
    }

    /**
     * Cleanup old logs
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        $retention_days = apply_filters( 'esc_log_retention_days', 30 );
        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );
        
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM $logs_table WHERE checked_at < %s",
            $cutoff_date
        ) );
        
        $notifications_table = $wpdb->prefix . 'esc_notifications';
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM $notifications_table WHERE created_at < %s AND status = 'sent'",
            $cutoff_date
        ) );
        
        $this->optimize_tables();
        
        do_action( 'esc_after_cleanup', $deleted );
    }

    /**
     * Optimize database tables
     */
    public function optimize_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'esc_services',
            $wpdb->prefix . 'esc_status_logs',
            $wpdb->prefix . 'esc_incidents',
            $wpdb->prefix . 'esc_notifications',
        );
        
        foreach ( $tables as $table ) {
            $wpdb->query( "OPTIMIZE TABLE $table" );
        }
    }

    /**
     * Get performance stats
     */
    public function get_performance_stats() {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        $services_table = $wpdb->prefix . 'esc_services';
        
        $total_services = $wpdb->get_var( "SELECT COUNT(*) FROM $services_table" );
        $enabled_services = $wpdb->get_var( "SELECT COUNT(*) FROM $services_table WHERE enabled = 1" );
        
        $total_checks_today = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $logs_table WHERE DATE(checked_at) = %s",
            current_time( 'Y-m-d' )
        ) );
        
        $avg_response_time = $wpdb->get_var( $wpdb->prepare(
            "SELECT AVG(response_time) FROM $logs_table WHERE checked_at >= %s",
            date( 'Y-m-d H:i:s', strtotime( '-24 hours' ) )
        ) );
        
        $cache_hits = wp_cache_get( 'cache_hits', $this->cache_group ) ?: 0;
        $cache_misses = wp_cache_get( 'cache_misses', $this->cache_group ) ?: 0;
        $cache_hit_rate = $cache_hits + $cache_misses > 0 
            ? ( $cache_hits / ( $cache_hits + $cache_misses ) ) * 100 
            : 0;
        
        return array(
            'total_services' => intval( $total_services ),
            'enabled_services' => intval( $enabled_services ),
            'total_checks_today' => intval( $total_checks_today ),
            'avg_response_time' => round( floatval( $avg_response_time ), 2 ),
            'cache_hit_rate' => round( $cache_hit_rate, 2 ),
            'queue_size' => $this->get_queue_size(),
        );
    }

    /**
     * Get queue size
     */
    public function get_queue_size() {
        $queue = get_option( $this->queue_option, array() );
        $total = 0;
        
        foreach ( $queue as $priority_queue ) {
            $total += count( $priority_queue );
        }
        
        return $total;
    }

    /**
     * Clear all caches
     */
    public function clear_all_caches() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        $services = $wpdb->get_results( "SELECT id FROM $services_table" );
        
        foreach ( $services as $service ) {
            $this->clear_service_cache( $service->id );
        }
        
        wp_cache_flush_group( $this->cache_group );
        
        delete_option( $this->queue_option );
    }

    /**
     * Get database size
     */
    public function get_database_size() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'esc_services',
            $wpdb->prefix . 'esc_status_logs',
            $wpdb->prefix . 'esc_incidents',
            $wpdb->prefix . 'esc_notifications',
        );
        
        $total_size = 0;
        
        foreach ( $tables as $table ) {
            $size = $wpdb->get_var( $wpdb->prepare(
                "SELECT (data_length + index_length) as size 
                FROM information_schema.TABLES 
                WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ) );
            
            if ( $size ) {
                $total_size += intval( $size );
            }
        }
        
        return $total_size;
    }

    /**
     * Format bytes to human readable
     */
    public function format_bytes( $bytes, $precision = 2 ) {
        $units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
        
        for ( $i = 0; $bytes > 1024 && $i < count( $units ) - 1; $i++ ) {
            $bytes /= 1024;
        }
        
        return round( $bytes, $precision ) . ' ' . $units[ $i ];
    }

    /**
     * Enable async checks
     */
    public function enable_async_checks() {
        if ( ! function_exists( 'wp_schedule_single_event' ) ) {
            return false;
        }
        
        update_option( 'esc_async_checks_enabled', true );
        
        return true;
    }

    /**
     * Disable async checks
     */
    public function disable_async_checks() {
        update_option( 'esc_async_checks_enabled', false );
    }

    /**
     * Check if async checks are enabled
     */
    public function is_async_enabled() {
        return get_option( 'esc_async_checks_enabled', true );
    }
}
