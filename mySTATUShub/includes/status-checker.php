<?php
/**
 * Status checker for easySTATUSCheck
 *
 * @package Easy_Status_Check
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Status_Checker {

    public function __construct() {
        add_action( 'esc_status_check_cron', array( $this, 'run_scheduled_checks' ) );
        add_action( 'wp_ajax_esc_test_service', array( $this, 'ajax_test_service' ) );
        add_action( 'wp_ajax_esc_force_check_all', array( $this, 'ajax_force_check_all' ) );
    }

    /**
     * Run scheduled status checks for all enabled services
     */
    public function run_scheduled_checks() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        $services = $wpdb->get_results( "SELECT * FROM $services_table WHERE enabled = 1" );
        
        foreach ( $services as $service ) {
            $this->check_service_status( $service );
        }
    }

    /**
     * Check the status of a single service
     */
    public function check_service_status( $service ) {
        $start_time = microtime( true );
        
        // Prepare request arguments
        $args = array(
            'method' => $service->method,
            'timeout' => $service->timeout,
            'headers' => array(
                'User-Agent' => 'easySTATUSCheck/' . EASY_STATUS_CHECK_VERSION
            ),
            'sslverify' => false // Allow self-signed certificates for internal services
        );
        
        // Add custom headers if specified
        if ( ! empty( $service->custom_headers ) ) {
            $custom_headers = json_decode( $service->custom_headers, true );
            if ( is_array( $custom_headers ) ) {
                $args['headers'] = array_merge( $args['headers'], $custom_headers );
            }
        }
        
        // Make the request
        $response = wp_remote_request( $service->url, $args );
        $end_time = microtime( true );
        $response_time = round( ( $end_time - $start_time ) * 1000, 2 ); // Convert to milliseconds
        
        $status_data = array(
            'service_id' => $service->id,
            'response_time' => $response_time,
            'checked_at' => current_time( 'mysql' )
        );
        
        if ( is_wp_error( $response ) ) {
            $status_data['status'] = 'offline';
            $status_data['http_code'] = null;
            $status_data['error_message'] = $response->get_error_message();
        } else {
            $http_code = wp_remote_retrieve_response_code( $response );
            $expected_codes = $this->parse_expected_codes( $service->expected_code );
            
            if ( in_array( $http_code, $expected_codes ) ) {
                // Check if this is a JSON/RSS API response
                $body = wp_remote_retrieve_body( $response );
                $response_type = $this->get_service_response_type( $service );
                
                if ( $response_type === 'json' ) {
                    $status_result = $this->check_json_status( $service, $body );
                    $status_data['status'] = $status_result['status'];
                    $status_data['error_message'] = $status_result['message'];
                } elseif ( $response_type === 'rss' ) {
                    $status_result = $this->check_rss_status( $service, $body );
                    $status_data['status'] = $status_result['status'];
                    $status_data['error_message'] = $status_result['message'];
                } else {
                    // Standard HTTP check
                    $status_data['status'] = 'online';
                    $status_data['error_message'] = null;
                }
            } elseif ( $http_code >= 500 ) {
                $status_data['status'] = 'offline';
            } else {
                $status_data['status'] = 'warning';
            }
            
            $status_data['http_code'] = $http_code;
            
            // Check for specific content if configured (for non-API endpoints)
            if ( empty( $response_type ) && ! empty( $service->expected_content ) ) {
                if ( strpos( $body, $service->expected_content ) === false ) {
                    $status_data['status'] = 'warning';
                    $status_data['error_message'] = __( 'Erwarteter Inhalt nicht gefunden', 'easy-status-check' );
                }
            }
        }
        
        // Log the status
        $this->log_status( $status_data );
        
        // Check for status changes and send notifications
        $this->handle_status_change( $service, $status_data );
        
        return $status_data;
    }
    
    /**
     * Get service response type
     */
    private function get_service_response_type( $service ) {
        // Check if service has custom response_type property
        if ( isset( $service->response_type ) ) {
            return $service->response_type;
        }
        
        // Try to determine from URL
        if ( strpos( $service->url, '.json' ) !== false || strpos( $service->url, '/api/' ) !== false ) {
            return 'json';
        }
        
        if ( strpos( $service->url, '.rss' ) !== false || strpos( $service->url, '.xml' ) !== false ) {
            return 'rss';
        }
        
        return null; // Standard HTTP check
    }
    
    /**
     * Check JSON API status response
     */
    private function check_json_status( $service, $response_body ) {
        $data = json_decode( $response_body, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return array(
                'status' => 'warning',
                'message' => __( 'Ungültige JSON-Antwort', 'easy-status-check' )
            );
        }
        
        // Get the JSON path to check (e.g., 'status.indicator')
        $json_path = isset( $service->json_path ) ? $service->json_path : 'status.indicator';
        $status_value = $this->get_nested_value( $data, $json_path );
        
        if ( $status_value === null ) {
            return array(
                'status' => 'warning',
                'message' => sprintf( __( 'Status-Pfad "%s" nicht gefunden', 'easy-status-check' ), $json_path )
            );
        }
        
        // Determine status based on common patterns
        $status = $this->parse_status_indicator( $status_value );
        
        // Get additional information
        $message = null;
        if ( isset( $data['status']['description'] ) ) {
            $message = $data['status']['description'];
        }
        
        // Check for incidents
        if ( isset( $data['incidents'] ) && is_array( $data['incidents'] ) && ! empty( $data['incidents'] ) ) {
            $active_incidents = array_filter( $data['incidents'], function( $incident ) {
                return isset( $incident['status'] ) && $incident['status'] !== 'resolved';
            });
            
            if ( ! empty( $active_incidents ) ) {
                $status = 'warning';
                $incident = reset( $active_incidents );
                $message = isset( $incident['name'] ) ? $incident['name'] : __( 'Aktive Vorfälle', 'easy-status-check' );
            }
        }
        
        return array(
            'status' => $status,
            'message' => $message
        );
    }
    
    /**
     * Check RSS/XML status response
     */
    private function check_rss_status( $service, $response_body ) {
        // Parse XML
        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $response_body );
        
        if ( $xml === false ) {
            return array(
                'status' => 'warning',
                'message' => __( 'Ungültige XML-Antwort', 'easy-status-check' )
            );
        }
        
        // For AWS RSS feed, check for recent incidents
        if ( isset( $xml->channel->item ) ) {
            $recent_items = array_slice( (array) $xml->channel->item, 0, 10 );
            $has_issues = false;
            $issue_message = '';
            
            foreach ( $recent_items as $item ) {
                $title = (string) $item->title;
                $pub_date = (string) $item->pubDate;
                
                // Check if this is a recent issue (within last 24 hours)
                $item_time = strtotime( $pub_date );
                $day_ago = time() - (24 * 60 * 60);
                
                if ( $item_time > $day_ago ) {
                    // Check for issue indicators in title
                    if ( stripos( $title, 'issue' ) !== false || 
                         stripos( $title, 'problem' ) !== false || 
                         stripos( $title, 'outage' ) !== false ||
                         stripos( $title, 'degraded' ) !== false ) {
                        $has_issues = true;
                        $issue_message = $title;
                        break;
                    }
                }
            }
            
            if ( $has_issues ) {
                return array(
                    'status' => 'warning',
                    'message' => $issue_message
                );
            }
        }
        
        return array(
            'status' => 'online',
            'message' => __( 'Keine aktuellen Probleme', 'easy-status-check' )
        );
    }
    
    /**
     * Get nested value from array using dot notation
     */
    private function get_nested_value( $array, $path ) {
        $keys = explode( '.', $path );
        $value = $array;
        
        foreach ( $keys as $key ) {
            if ( ! is_array( $value ) || ! isset( $value[ $key ] ) ) {
                return null;
            }
            $value = $value[ $key ];
        }
        
        return $value;
    }
    
    /**
     * Parse status indicator value
     */
    private function parse_status_indicator( $value ) {
        $value = strtolower( (string) $value );
        
        // Common status indicators
        $status_map = array(
            'none' => 'online',
            'operational' => 'online',
            'ok' => 'online',
            'up' => 'online',
            'all systems operational' => 'online',
            'minor' => 'warning',
            'degraded' => 'warning',
            'partial' => 'warning',
            'major' => 'offline',
            'critical' => 'offline',
            'down' => 'offline',
            'outage' => 'offline'
        );
        
        // Direct mapping
        if ( isset( $status_map[ $value ] ) ) {
            return $status_map[ $value ];
        }
        
        // Partial matching
        foreach ( $status_map as $indicator => $status ) {
            if ( strpos( $value, $indicator ) !== false ) {
                return $status;
            }
        }
        
        // Default to warning for unknown values
        return 'warning';
    }

    /**
     * Parse expected HTTP codes from string
     */
    private function parse_expected_codes( $expected_code ) {
        if ( empty( $expected_code ) ) {
            return array( 200 );
        }
        
        $codes = array();
        $parts = explode( ',', $expected_code );
        
        foreach ( $parts as $part ) {
            $part = trim( $part );
            if ( strpos( $part, '-' ) !== false ) {
                // Range like "200-299"
                list( $start, $end ) = explode( '-', $part );
                $codes = array_merge( $codes, range( intval( $start ), intval( $end ) ) );
            } else {
                // Single code
                $codes[] = intval( $part );
            }
        }
        
        return array_unique( $codes );
    }

    /**
     * Log status to database
     */
    private function log_status( $status_data ) {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        
        $wpdb->insert( $logs_table, $status_data );
        
        // Clean up old logs (keep only last 30 days)
        $wpdb->query( $wpdb->prepare( "
            DELETE FROM $logs_table 
            WHERE checked_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND service_id = %d
        ", $status_data['service_id'] ) );
    }

    /**
     * Handle status changes and notifications
     */
    private function handle_status_change( $service, $current_status ) {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        
        // Get the last status
        $last_status = $wpdb->get_var( $wpdb->prepare( "
            SELECT status 
            FROM $logs_table 
            WHERE service_id = %d 
            AND checked_at < %s 
            ORDER BY checked_at DESC 
            LIMIT 1
        ", $service->id, $current_status['checked_at'] ) );
        
        // Check if status changed
        if ( $last_status && $last_status !== $current_status['status'] ) {
            $this->send_status_change_notification( $service, $last_status, $current_status );
            
            // Trigger incident tracking
            do_action( 'esc_status_changed', $service->id, $last_status, $current_status['status'] );
        }
        
        // Send notification for persistent offline status
        if ( $current_status['status'] === 'offline' ) {
            $this->check_persistent_offline( $service );
        }
    }

    /**
     * Send status change notification
     */
    private function send_status_change_notification( $service, $old_status, $new_status ) {
        if ( ! $service->notify_email ) {
            return;
        }
        
        $notification_settings = get_option( 'esc_notification_settings', array() );
        if ( empty( $notification_settings['enabled'] ) ) {
            return;
        }
        
        $to = $notification_settings['email'] ?? get_option( 'admin_email' );
        $site_name = get_bloginfo( 'name' );
        
        $status_labels = array(
            'online' => __( 'Online', 'easy-status-check' ),
            'offline' => __( 'Offline', 'easy-status-check' ),
            'warning' => __( 'Warnung', 'easy-status-check' )
        );
        
        $subject = sprintf( 
            '[%s] Status-Änderung: %s - %s → %s',
            $site_name,
            $service->name,
            $status_labels[ $old_status ],
            $status_labels[ $new_status['status'] ]
        );
        
        $message = sprintf(
            "Der Status des Service '%s' hat sich geändert:\n\n" .
            "Service: %s\n" .
            "URL: %s\n" .
            "Alter Status: %s\n" .
            "Neuer Status: %s\n" .
            "HTTP Code: %s\n" .
            "Antwortzeit: %s ms\n" .
            "Zeitpunkt: %s\n",
            $service->name,
            $service->name,
            $service->url,
            $status_labels[ $old_status ],
            $status_labels[ $new_status['status'] ],
            $new_status['http_code'] ?? 'N/A',
            $new_status['response_time'],
            $new_status['checked_at']
        );
        
        if ( ! empty( $new_status['error_message'] ) ) {
            $message .= "\nFehler: " . $new_status['error_message'];
        }
        
        wp_mail( $to, $subject, $message );
    }

    /**
     * Check for persistent offline status
     */
    private function check_persistent_offline( $service ) {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        
        // Check if service has been offline for more than 30 minutes
        $offline_count = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(*) 
            FROM $logs_table 
            WHERE service_id = %d 
            AND status = 'offline' 
            AND checked_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ", $service->id ) );
        
        // Send persistent offline notification only once per hour
        if ( $offline_count >= 6 ) { // 6 checks in 30 minutes (every 5 minutes)
            $last_persistent_notification = get_transient( "esc_persistent_notification_{$service->id}" );
            
            if ( ! $last_persistent_notification ) {
                $this->send_persistent_offline_notification( $service );
                set_transient( "esc_persistent_notification_{$service->id}", true, HOUR_IN_SECONDS );
            }
        }
    }

    /**
     * Send persistent offline notification
     */
    private function send_persistent_offline_notification( $service ) {
        if ( ! $service->notify_email ) {
            return;
        }
        
        $notification_settings = get_option( 'esc_notification_settings', array() );
        if ( empty( $notification_settings['enabled'] ) ) {
            return;
        }
        
        $to = $notification_settings['email'] ?? get_option( 'admin_email' );
        $site_name = get_bloginfo( 'name' );
        
        $subject = sprintf( 
            '[%s] KRITISCH: %s ist seit 30+ Minuten offline',
            $site_name,
            $service->name
        );
        
        $message = sprintf(
            "KRITISCHE BENACHRICHTIGUNG\n\n" .
            "Der Service '%s' ist seit mehr als 30 Minuten offline.\n\n" .
            "Service: %s\n" .
            "URL: %s\n" .
            "Kategorie: %s\n" .
            "Letzte Prüfung: %s\n\n" .
            "Bitte prüfen Sie den Service umgehend.\n",
            $service->name,
            $service->name,
            $service->url,
            $service->category,
            current_time( 'Y-m-d H:i:s' )
        );
        
        wp_mail( $to, $subject, $message );
    }

    /**
     * Get service status summary
     */
    public function get_service_status_summary( $service_id = null ) {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        
        $where_clause = $service_id ? $wpdb->prepare( "WHERE s.id = %d", $service_id ) : "";
        
        $results = $wpdb->get_results( "
            SELECT 
                s.*,
                l.status as current_status,
                l.http_code,
                l.response_time,
                l.error_message,
                l.checked_at as last_checked
            FROM $services_table s
            LEFT JOIN $logs_table l ON s.id = l.service_id
            LEFT JOIN (
                SELECT service_id, MAX(checked_at) as max_checked
                FROM $logs_table
                GROUP BY service_id
            ) latest ON s.id = latest.service_id AND l.checked_at = latest.max_checked
            $where_clause
            ORDER BY s.category, s.name
        " );
        
        return $service_id ? ( $results[0] ?? null ) : $results;
    }

    /**
     * Get uptime statistics for a service
     */
    public function get_uptime_statistics( $service_id, $period = '24h' ) {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        
        $interval = '';
        switch ( $period ) {
            case '1h':
                $interval = 'INTERVAL 1 HOUR';
                break;
            case '24h':
                $interval = 'INTERVAL 24 HOUR';
                break;
            case '7d':
                $interval = 'INTERVAL 7 DAY';
                break;
            case '30d':
                $interval = 'INTERVAL 30 DAY';
                break;
            default:
                $interval = 'INTERVAL 24 HOUR';
        }
        
        $stats = $wpdb->get_row( $wpdb->prepare( "
            SELECT 
                COUNT(*) as total_checks,
                SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online_checks,
                SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_checks,
                SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warning_checks,
                AVG(response_time) as avg_response_time,
                MIN(response_time) as min_response_time,
                MAX(response_time) as max_response_time
            FROM $logs_table 
            WHERE service_id = %d 
            AND checked_at > DATE_SUB(NOW(), $interval)
        ", $service_id ) );
        
        if ( $stats && $stats->total_checks > 0 ) {
            $stats->uptime_percentage = round( ( $stats->online_checks / $stats->total_checks ) * 100, 2 );
            $stats->avg_response_time = round( $stats->avg_response_time, 2 );
        } else {
            $stats = (object) array(
                'total_checks' => 0,
                'online_checks' => 0,
                'offline_checks' => 0,
                'warning_checks' => 0,
                'uptime_percentage' => 0,
                'avg_response_time' => 0,
                'min_response_time' => 0,
                'max_response_time' => 0
            );
        }
        
        return $stats;
    }

    /**
     * AJAX handler for testing a single service
     */
    public function ajax_test_service() {
        check_ajax_referer( 'esc_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Keine Berechtigung.', 'easy-status-check' ) );
        }
        
        $service_id = intval( $_POST['service_id'] );
        
        global $wpdb;
        $services_table = $wpdb->prefix . 'esc_services';
        $service = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $services_table WHERE id = %d", $service_id ) );
        
        if ( ! $service ) {
            wp_send_json_error( array( 'message' => __( 'Service nicht gefunden.', 'easy-status-check' ) ) );
        }
        
        $result = $this->check_service_status( $service );
        
        wp_send_json_success( array(
            'status' => $result['status'],
            'http_code' => $result['http_code'],
            'response_time' => $result['response_time'],
            'error_message' => $result['error_message']
        ) );
    }

    /**
     * AJAX handler for forcing check of all services
     */
    public function ajax_force_check_all() {
        check_ajax_referer( 'esc_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Keine Berechtigung.', 'easy-status-check' ) );
        }
        
        $this->run_scheduled_checks();
        
        wp_send_json_success( array( 'message' => __( 'Alle Services wurden geprüft.', 'easy-status-check' ) ) );
    }
}

// Class will be instantiated by main plugin class
