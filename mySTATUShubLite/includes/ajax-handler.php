<?php
/**
 * AJAX handler for easySTATUSCheck
 *
 * @package Easy_Status_Check
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Ajax_Handler {

    public function __construct() {
        // Admin AJAX handlers
        add_action( 'wp_ajax_esc_save_service', array( $this, 'save_service' ) );
        add_action( 'wp_ajax_esc_delete_service', array( $this, 'delete_service' ) );
        add_action( 'wp_ajax_esc_get_service', array( $this, 'get_service' ) );
        add_action( 'wp_ajax_esc_add_predefined_services', array( $this, 'add_predefined_services' ) );
        add_action( 'wp_ajax_esc_export_services', array( $this, 'export_services' ) );
        add_action( 'wp_ajax_esc_import_services', array( $this, 'import_services' ) );
        add_action( 'wp_ajax_esc_bulk_action', array( $this, 'bulk_action' ) );
    }

    /**
     * Save or update a service
     */
    public function save_service() {
        check_ajax_referer( 'esc_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'easy-status-check' ) ) );
        }
        
        global $wpdb;
        $services_table = $wpdb->prefix . 'esc_services';
        
        $service_id = intval( $_POST['service_id'] ?? 0 );
        $service_name = sanitize_text_field( $_POST['service_name'] ?? '' );
        $service_url = esc_url_raw( $_POST['service_url'] ?? '' );
        $service_category = sanitize_text_field( $_POST['service_category'] ?? 'custom' );
        $service_method = sanitize_text_field( $_POST['service_method'] ?? 'GET' );
        $service_timeout = intval( $_POST['service_timeout'] ?? 10 );
        $service_expected_code = sanitize_text_field( $_POST['service_expected_code'] ?? '200' );
        $service_interval = intval( $_POST['service_interval'] ?? 300 );
        $service_enabled = isset( $_POST['service_enabled'] ) ? 1 : 0;
        $service_notify = isset( $_POST['service_notify'] ) ? 1 : 0;
        $service_response_type = sanitize_text_field( $_POST['service_response_type'] ?? '' );
        $service_json_path = sanitize_text_field( $_POST['service_json_path'] ?? '' );
        $service_check_content = isset( $_POST['service_check_content'] ) ? 1 : 0;
        $custom_headers = sanitize_textarea_field( $_POST['custom_headers'] ?? '' );
        
        // Validation
        if ( empty( $service_name ) || empty( $service_url ) ) {
            wp_send_json_error( array( 'message' => __( 'Name und URL sind erforderlich.', 'easy-status-check' ) ) );
        }
        
        if ( ! filter_var( $service_url, FILTER_VALIDATE_URL ) ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige URL.', 'easy-status-check' ) ) );
        }
        
        if ( ! in_array( $service_method, array( 'GET', 'POST', 'HEAD' ) ) ) {
            $service_method = 'GET';
        }
        
        if ( $service_timeout < 1 || $service_timeout > 60 ) {
            $service_timeout = 10;
        }
        
        if ( $service_interval < 60 ) {
            $service_interval = 300;
        }
        
        // Parse custom headers
        $headers_json = null;
        if ( ! empty( $custom_headers ) ) {
            $headers_array = array();
            $lines = explode( "\n", $custom_headers );
            foreach ( $lines as $line ) {
                $line = trim( $line );
                if ( empty( $line ) || strpos( $line, ':' ) === false ) {
                    continue;
                }
                list( $key, $value ) = explode( ':', $line, 2 );
                $headers_array[ trim( $key ) ] = trim( $value );
            }
            if ( ! empty( $headers_array ) ) {
                $headers_json = json_encode( $headers_array );
            }
        }
        
        $service_data = array(
            'name' => $service_name,
            'url' => $service_url,
            'category' => $service_category,
            'method' => $service_method,
            'timeout' => $service_timeout,
            'expected_code' => $service_expected_code,
            'check_interval' => $service_interval,
            'enabled' => $service_enabled,
            'notify_email' => $service_notify,
            'custom_headers' => $headers_json,
            'response_type' => ! empty( $service_response_type ) ? $service_response_type : null,
            'json_path' => ! empty( $service_json_path ) ? $service_json_path : null,
            'check_content' => $service_check_content,
            'updated_at' => current_time( 'mysql' )
        );
        
        if ( $service_id > 0 ) {
            // Update existing service
            $result = $wpdb->update( $services_table, $service_data, array( 'id' => $service_id ) );
            
            if ( $result !== false ) {
                wp_send_json_success( array( 
                    'message' => __( 'Service erfolgreich aktualisiert.', 'easy-status-check' ),
                    'service_id' => $service_id
                ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Fehler beim Aktualisieren des Service.', 'easy-status-check' ) ) );
            }
        } else {
            // Check for URL duplicates
            $existing = $wpdb->get_var( $wpdb->prepare( 
                "SELECT id FROM $services_table WHERE url = %s",
                $service_url
            ) );
            
            if ( $existing ) {
                wp_send_json_error( array( 'message' => __( 'Ein Service mit dieser URL existiert bereits.', 'easy-status-check' ) ) );
            }
            
            // Create new service
            $service_data['created_at'] = current_time( 'mysql' );
            $result = $wpdb->insert( $services_table, $service_data );
            
            if ( $result ) {
                $new_service_id = $wpdb->insert_id;
                wp_send_json_success( array( 
                    'message' => __( 'Service erfolgreich erstellt.', 'easy-status-check' ),
                    'service_id' => $new_service_id
                ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Fehler beim Erstellen des Service.', 'easy-status-check' ) ) );
            }
        }
    }

    /**
     * Delete a service
     */
    public function delete_service() {
        check_ajax_referer( 'esc_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'easy-status-check' ) ) );
        }
        
        $service_id = intval( $_POST['service_id'] ?? 0 );
        
        if ( $service_id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige Service-ID.', 'easy-status-check' ) ) );
        }
        
        global $wpdb;
        $services_table = $wpdb->prefix . 'esc_services';
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        
        // Delete service logs first (due to foreign key constraint)
        $wpdb->delete( $logs_table, array( 'service_id' => $service_id ) );
        
        // Delete service
        $result = $wpdb->delete( $services_table, array( 'id' => $service_id ) );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Service erfolgreich gelöscht.', 'easy-status-check' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Fehler beim Löschen des Service.', 'easy-status-check' ) ) );
        }
    }

    /**
     * Get service data for editing
     */
    public function get_service() {
        check_ajax_referer( 'esc_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'easy-status-check' ) ) );
        }
        
        $service_id = intval( $_POST['service_id'] ?? 0 );
        
        if ( $service_id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige Service-ID.', 'easy-status-check' ) ) );
        }
        
        global $wpdb;
        $services_table = $wpdb->prefix . 'esc_services';
        $service = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $services_table WHERE id = %d", $service_id ) );
        
        if ( ! $service ) {
            wp_send_json_error( array( 'message' => __( 'Service nicht gefunden.', 'easy-status-check' ) ) );
        }
        
        // Parse custom headers for display
        $custom_headers_text = '';
        if ( ! empty( $service->custom_headers ) ) {
            $headers = json_decode( $service->custom_headers, true );
            if ( is_array( $headers ) ) {
                $header_lines = array();
                foreach ( $headers as $key => $value ) {
                    $header_lines[] = $key . ': ' . $value;
                }
                $custom_headers_text = implode( "\n", $header_lines );
            }
        }
        
        wp_send_json_success( array(
            'id' => $service->id,
            'name' => $service->name,
            'url' => $service->url,
            'category' => $service->category,
            'method' => $service->method,
            'timeout' => $service->timeout,
            'expected_code' => $service->expected_code,
            'check_interval' => $service->check_interval,
            'enabled' => $service->enabled,
            'notify_email' => $service->notify_email,
            'response_type' => $service->response_type,
            'json_path' => $service->json_path,
            'check_content' => $service->check_content,
            'custom_headers' => $custom_headers_text
        ) );
    }

    /**
     * Add predefined services
     */
    public function add_predefined_services() {
        check_ajax_referer( 'esc_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'easy-status-check' ) ) );
        }
        
        $services_data = $_POST['services'] ?? array();
        
        if ( empty( $services_data ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Services ausgewählt.', 'easy-status-check' ) ) );
        }
        
        if ( class_exists( 'ESC_Predefined_Services' ) ) {
            $predefined = new ESC_Predefined_Services();
            $added_count = $predefined->add_predefined_services( $services_data );
            
            if ( $added_count > 0 ) {
                wp_send_json_success( array( 
                    'message' => sprintf( 
                        _n( '%d Service hinzugefügt.', '%d Services hinzugefügt.', $added_count, 'easy-status-check' ),
                        $added_count
                    ),
                    'added_count' => $added_count
                ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Keine neuen Services hinzugefügt. Möglicherweise existieren alle bereits.', 'easy-status-check' ) ) );
            }
        } else {
            wp_send_json_error( array( 'message' => __( 'Predefined Services Klasse nicht verfügbar.', 'easy-status-check' ) ) );
        }
    }

    /**
     * Export services
     */
    public function export_services() {
        check_ajax_referer( 'esc_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'easy-status-check' ) ) );
        }
        
        if ( class_exists( 'ESC_Predefined_Services' ) ) {
            $predefined = new ESC_Predefined_Services();
            $export_data = $predefined->export_services();
            
            wp_send_json_success( array(
                'data' => $export_data,
                'filename' => 'easy-status-check-export-' . date( 'Y-m-d-H-i-s' ) . '.json'
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Export-Funktion nicht verfügbar.', 'easy-status-check' ) ) );
        }
    }

    /**
     * Import services
     */
    public function import_services() {
        check_ajax_referer( 'esc_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'easy-status-check' ) ) );
        }
        
        if ( empty( $_FILES['import_file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Datei hochgeladen.', 'easy-status-check' ) ) );
        }
        
        $file = $_FILES['import_file'];
        
        // Validate file
        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( array( 'message' => __( 'Fehler beim Datei-Upload.', 'easy-status-check' ) ) );
        }
        
        if ( $file['type'] !== 'application/json' && pathinfo( $file['name'], PATHINFO_EXTENSION ) !== 'json' ) {
            wp_send_json_error( array( 'message' => __( 'Nur JSON-Dateien sind erlaubt.', 'easy-status-check' ) ) );
        }
        
        // Read and parse file
        $file_content = file_get_contents( $file['tmp_name'] );
        $import_data = json_decode( $file_content, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige JSON-Datei.', 'easy-status-check' ) ) );
        }
        
        if ( empty( $import_data['plugin'] ) || $import_data['plugin'] !== 'easySTATUSCheck' ) {
            wp_send_json_error( array( 'message' => __( 'Datei stammt nicht von easySTATUSCheck.', 'easy-status-check' ) ) );
        }
        
        if ( class_exists( 'ESC_Predefined_Services' ) ) {
            $predefined = new ESC_Predefined_Services();
            $imported_count = $predefined->import_services( $import_data );
            
            if ( $imported_count > 0 ) {
                wp_send_json_success( array( 
                    'message' => sprintf( 
                        _n( '%d Service importiert.', '%d Services importiert.', $imported_count, 'easy-status-check' ),
                        $imported_count
                    ),
                    'imported_count' => $imported_count
                ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Keine Services importiert. Möglicherweise existieren alle bereits.', 'easy-status-check' ) ) );
            }
        } else {
            wp_send_json_error( array( 'message' => __( 'Import-Funktion nicht verfügbar.', 'easy-status-check' ) ) );
        }
    }

    /**
     * Handle bulk actions
     */
    public function bulk_action() {
        check_ajax_referer( 'esc_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'easy-status-check' ) ) );
        }
        
        $action = sanitize_text_field( $_POST['bulk_action'] ?? '' );
        $service_ids = array_map( 'intval', $_POST['service_ids'] ?? array() );
        
        if ( empty( $action ) || empty( $service_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'Aktion oder Service-IDs fehlen.', 'easy-status-check' ) ) );
        }
        
        global $wpdb;
        $services_table = $wpdb->prefix . 'esc_services';
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        
        $affected_count = 0;
        
        switch ( $action ) {
            case 'enable':
                foreach ( $service_ids as $service_id ) {
                    $result = $wpdb->update( 
                        $services_table, 
                        array( 'enabled' => 1 ), 
                        array( 'id' => $service_id ) 
                    );
                    if ( $result !== false ) {
                        $affected_count++;
                    }
                }
                $message = sprintf( 
                    _n( '%d Service aktiviert.', '%d Services aktiviert.', $affected_count, 'easy-status-check' ),
                    $affected_count
                );
                break;
                
            case 'disable':
                foreach ( $service_ids as $service_id ) {
                    $result = $wpdb->update( 
                        $services_table, 
                        array( 'enabled' => 0 ), 
                        array( 'id' => $service_id ) 
                    );
                    if ( $result !== false ) {
                        $affected_count++;
                    }
                }
                $message = sprintf( 
                    _n( '%d Service deaktiviert.', '%d Services deaktiviert.', $affected_count, 'easy-status-check' ),
                    $affected_count
                );
                break;
                
            case 'delete':
                foreach ( $service_ids as $service_id ) {
                    // Delete logs first
                    $wpdb->delete( $logs_table, array( 'service_id' => $service_id ) );
                    // Delete service
                    $result = $wpdb->delete( $services_table, array( 'id' => $service_id ) );
                    if ( $result ) {
                        $affected_count++;
                    }
                }
                $message = sprintf( 
                    _n( '%d Service gelöscht.', '%d Services gelöscht.', $affected_count, 'easy-status-check' ),
                    $affected_count
                );
                break;
                
            case 'test':
                if ( class_exists( 'ESC_Status_Checker' ) ) {
                    $checker = new ESC_Status_Checker();
                    foreach ( $service_ids as $service_id ) {
                        $service = $wpdb->get_row( $wpdb->prepare( 
                            "SELECT * FROM $services_table WHERE id = %d", 
                            $service_id 
                        ) );
                        if ( $service ) {
                            $checker->check_service_status( $service );
                            $affected_count++;
                        }
                    }
                    $message = sprintf( 
                        _n( '%d Service getestet.', '%d Services getestet.', $affected_count, 'easy-status-check' ),
                        $affected_count
                    );
                } else {
                    wp_send_json_error( array( 'message' => __( 'Status Checker nicht verfügbar.', 'easy-status-check' ) ) );
                }
                break;
                
            default:
                wp_send_json_error( array( 'message' => __( 'Unbekannte Aktion.', 'easy-status-check' ) ) );
        }
        
        if ( $affected_count > 0 ) {
            wp_send_json_success( array( 
                'message' => $message,
                'affected_count' => $affected_count
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Keine Services wurden verarbeitet.', 'easy-status-check' ) ) );
        }
    }
}

// Class will be instantiated by main plugin class
