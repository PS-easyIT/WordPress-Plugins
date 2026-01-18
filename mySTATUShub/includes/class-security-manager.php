<?php
/**
 * Security Manager
 *
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Security_Manager {

    private $encryption_key;
    private $rate_limit_option = 'esc_rate_limits';

    public function __construct() {
        $this->encryption_key = $this->get_encryption_key();
        
        add_action( 'init', array( $this, 'init_security' ) );
        add_filter( 'esc_before_status_check', array( $this, 'check_rate_limit' ), 10, 2 );
    }

    /**
     * Initialize security features
     */
    public function init_security() {
        if ( ! defined( 'ESC_ENCRYPTION_KEY' ) ) {
            $this->generate_encryption_key();
        }
    }

    /**
     * Get encryption key
     */
    private function get_encryption_key() {
        if ( defined( 'ESC_ENCRYPTION_KEY' ) ) {
            return ESC_ENCRYPTION_KEY;
        }
        
        $key = get_option( 'esc_encryption_key' );
        if ( ! $key ) {
            $key = $this->generate_encryption_key();
        }
        
        return $key;
    }

    /**
     * Generate encryption key
     */
    private function generate_encryption_key() {
        $key = wp_generate_password( 64, true, true );
        update_option( 'esc_encryption_key', $key );
        
        return $key;
    }

    /**
     * Encrypt data
     */
    public function encrypt( $data ) {
        if ( empty( $data ) ) {
            return '';
        }
        
        $method = 'AES-256-CBC';
        $key = hash( 'sha256', $this->encryption_key );
        $iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $method ) );
        
        $encrypted = openssl_encrypt( $data, $method, $key, 0, $iv );
        
        return base64_encode( $encrypted . '::' . $iv );
    }

    /**
     * Decrypt data
     */
    public function decrypt( $data ) {
        if ( empty( $data ) ) {
            return '';
        }
        
        $method = 'AES-256-CBC';
        $key = hash( 'sha256', $this->encryption_key );
        
        $decoded = base64_decode( $data );
        if ( strpos( $decoded, '::' ) === false ) {
            return '';
        }
        
        list( $encrypted_data, $iv ) = explode( '::', $decoded, 2 );
        
        return openssl_decrypt( $encrypted_data, $method, $key, 0, $iv );
    }

    /**
     * Encrypt API credentials
     */
    public function encrypt_credentials( $credentials ) {
        if ( ! is_array( $credentials ) ) {
            return $credentials;
        }
        
        $encrypted = array();
        foreach ( $credentials as $key => $value ) {
            $encrypted[ $key ] = $this->encrypt( $value );
        }
        
        return $encrypted;
    }

    /**
     * Decrypt API credentials
     */
    public function decrypt_credentials( $credentials ) {
        if ( ! is_array( $credentials ) ) {
            return $credentials;
        }
        
        $decrypted = array();
        foreach ( $credentials as $key => $value ) {
            $decrypted[ $key ] = $this->decrypt( $value );
        }
        
        return $decrypted;
    }

    /**
     * Check rate limit
     */
    public function check_rate_limit( $allowed, $service_id ) {
        $limits = get_option( $this->rate_limit_option, array() );
        $current_time = time();
        
        $limit_per_minute = apply_filters( 'esc_rate_limit_per_minute', 60 );
        $limit_per_hour = apply_filters( 'esc_rate_limit_per_hour', 1000 );
        
        if ( ! isset( $limits[ $service_id ] ) ) {
            $limits[ $service_id ] = array(
                'minute' => array(),
                'hour' => array(),
            );
        }
        
        $limits[ $service_id ]['minute'] = array_filter(
            $limits[ $service_id ]['minute'],
            function( $timestamp ) use ( $current_time ) {
                return $timestamp > ( $current_time - 60 );
            }
        );
        
        $limits[ $service_id ]['hour'] = array_filter(
            $limits[ $service_id ]['hour'],
            function( $timestamp ) use ( $current_time ) {
                return $timestamp > ( $current_time - 3600 );
            }
        );
        
        if ( count( $limits[ $service_id ]['minute'] ) >= $limit_per_minute ) {
            return false;
        }
        
        if ( count( $limits[ $service_id ]['hour'] ) >= $limit_per_hour ) {
            return false;
        }
        
        $limits[ $service_id ]['minute'][] = $current_time;
        $limits[ $service_id ]['hour'][] = $current_time;
        
        update_option( $this->rate_limit_option, $limits );
        
        return true;
    }

    /**
     * Validate IP whitelist
     */
    public function validate_ip_whitelist( $ip = null ) {
        if ( ! $ip ) {
            $ip = $this->get_client_ip();
        }
        
        $whitelist = get_option( 'esc_ip_whitelist', array() );
        
        if ( empty( $whitelist ) ) {
            return true;
        }
        
        return in_array( $ip, $whitelist );
    }

    /**
     * Get client IP
     */
    private function get_client_ip() {
        $ip = '';
        
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
    }

    /**
     * Add IP to whitelist
     */
    public function add_to_whitelist( $ip ) {
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            return false;
        }
        
        $whitelist = get_option( 'esc_ip_whitelist', array() );
        
        if ( ! in_array( $ip, $whitelist ) ) {
            $whitelist[] = $ip;
            update_option( 'esc_ip_whitelist', $whitelist );
        }
        
        return true;
    }

    /**
     * Remove IP from whitelist
     */
    public function remove_from_whitelist( $ip ) {
        $whitelist = get_option( 'esc_ip_whitelist', array() );
        
        $whitelist = array_filter( $whitelist, function( $whitelisted_ip ) use ( $ip ) {
            return $whitelisted_ip !== $ip;
        });
        
        update_option( 'esc_ip_whitelist', array_values( $whitelist ) );
        
        return true;
    }

    /**
     * Validate nonce with expiration
     */
    public function validate_nonce( $nonce, $action, $max_age = 3600 ) {
        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            return false;
        }
        
        $nonce_tick = wp_verify_nonce( $nonce, $action );
        if ( $nonce_tick === false ) {
            return false;
        }
        
        $nonce_life = apply_filters( 'nonce_life', DAY_IN_SECONDS );
        $created = ( ceil( time() / ( $nonce_life / 2 ) ) - $nonce_tick ) * ( $nonce_life / 2 );
        
        if ( ( time() - $created ) > $max_age ) {
            return false;
        }
        
        return true;
    }

    /**
     * Sanitize service data
     */
    public function sanitize_service_data( $data ) {
        $sanitized = array();
        
        $sanitized['name'] = sanitize_text_field( $data['name'] ?? '' );
        $sanitized['url'] = esc_url_raw( $data['url'] ?? '' );
        $sanitized['category'] = sanitize_text_field( $data['category'] ?? 'custom' );
        $sanitized['method'] = in_array( $data['method'] ?? 'GET', array( 'GET', 'POST', 'HEAD' ) ) 
            ? $data['method'] 
            : 'GET';
        $sanitized['timeout'] = min( max( intval( $data['timeout'] ?? 10 ), 1 ), 60 );
        $sanitized['expected_code'] = sanitize_text_field( $data['expected_code'] ?? '200' );
        $sanitized['check_interval'] = intval( $data['check_interval'] ?? 300 );
        $sanitized['enabled'] = intval( $data['enabled'] ?? 1 );
        
        return $sanitized;
    }

    /**
     * Log security event
     */
    public function log_security_event( $event_type, $details = array() ) {
        $log_entry = array(
            'timestamp' => current_time( 'mysql' ),
            'event_type' => $event_type,
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'details' => $details,
        );
        
        $security_log = get_option( 'esc_security_log', array() );
        $security_log[] = $log_entry;
        
        $max_entries = apply_filters( 'esc_security_log_max_entries', 1000 );
        if ( count( $security_log ) > $max_entries ) {
            $security_log = array_slice( $security_log, -$max_entries );
        }
        
        update_option( 'esc_security_log', $security_log );
        
        do_action( 'esc_security_event_logged', $event_type, $details );
    }

    /**
     * Get security log
     */
    public function get_security_log( $limit = 100 ) {
        $log = get_option( 'esc_security_log', array() );
        
        return array_slice( array_reverse( $log ), 0, $limit );
    }

    /**
     * Clear security log
     */
    public function clear_security_log() {
        delete_option( 'esc_security_log' );
    }

    /**
     * Check capability with logging
     */
    public function check_capability( $capability = 'manage_options' ) {
        if ( ! current_user_can( $capability ) ) {
            $this->log_security_event( 'unauthorized_access_attempt', array(
                'capability' => $capability,
                'user_id' => get_current_user_id(),
            ) );
            
            return false;
        }
        
        return true;
    }

    /**
     * Validate AJAX request
     */
    public function validate_ajax_request( $action, $nonce_action = null ) {
        if ( ! wp_doing_ajax() ) {
            return false;
        }
        
        if ( ! $this->check_capability() ) {
            return false;
        }
        
        $nonce_action = $nonce_action ?: $action;
        $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        
        if ( ! $this->validate_nonce( $nonce, $nonce_action ) ) {
            $this->log_security_event( 'invalid_nonce', array(
                'action' => $action,
            ) );
            
            return false;
        }
        
        return true;
    }

    /**
     * Generate secure token
     */
    public function generate_secure_token( $length = 32 ) {
        return bin2hex( random_bytes( $length / 2 ) );
    }

    /**
     * Hash password
     */
    public function hash_password( $password ) {
        return wp_hash_password( $password );
    }

    /**
     * Verify password
     */
    public function verify_password( $password, $hash ) {
        return wp_check_password( $password, $hash );
    }

    /**
     * Prevent SQL injection
     */
    public function sanitize_sql_input( $input ) {
        global $wpdb;
        return $wpdb->_real_escape( $input );
    }

    /**
     * Prevent XSS
     */
    public function sanitize_output( $output ) {
        return esc_html( $output );
    }

    /**
     * Get security recommendations
     */
    public function get_security_recommendations() {
        $recommendations = array();
        
        if ( ! defined( 'ESC_ENCRYPTION_KEY' ) ) {
            $recommendations[] = array(
                'severity' => 'medium',
                'message' => __( 'Definieren Sie ESC_ENCRYPTION_KEY in wp-config.php für erhöhte Sicherheit', 'easy-status-check' ),
            );
        }
        
        $whitelist = get_option( 'esc_ip_whitelist', array() );
        if ( empty( $whitelist ) ) {
            $recommendations[] = array(
                'severity' => 'low',
                'message' => __( 'Erwägen Sie die Einrichtung einer IP-Whitelist', 'easy-status-check' ),
            );
        }
        
        if ( ! is_ssl() ) {
            $recommendations[] = array(
                'severity' => 'high',
                'message' => __( 'Verwenden Sie HTTPS für sichere Datenübertragung', 'easy-status-check' ),
            );
        }
        
        return $recommendations;
    }

    /**
     * Export encrypted credentials
     */
    public function export_encrypted_credentials() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        $services = $wpdb->get_results( "SELECT id, name, custom_headers FROM $services_table" );
        
        $export = array();
        foreach ( $services as $service ) {
            if ( $service->custom_headers ) {
                $export[ $service->id ] = array(
                    'name' => $service->name,
                    'credentials' => $this->encrypt( $service->custom_headers ),
                );
            }
        }
        
        return $export;
    }
}
