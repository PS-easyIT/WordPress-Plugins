<?php
/**
 * Service Custom Post Type
 *
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Service_Post_Type {

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_easy_service', array( $this, 'save_meta_boxes' ) );
        add_filter( 'manage_easy_service_posts_columns', array( $this, 'set_custom_columns' ) );
        add_action( 'manage_easy_service_posts_custom_column', array( $this, 'custom_column_content' ), 10, 2 );
    }

    /**
     * Register Service Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => __( 'Services', 'easy-status-check' ),
            'singular_name'         => __( 'Service', 'easy-status-check' ),
            'menu_name'             => __( 'Services', 'easy-status-check' ),
            'add_new'               => __( 'Neuer Service', 'easy-status-check' ),
            'add_new_item'          => __( 'Neuen Service hinzufügen', 'easy-status-check' ),
            'edit_item'             => __( 'Service bearbeiten', 'easy-status-check' ),
            'new_item'              => __( 'Neuer Service', 'easy-status-check' ),
            'view_item'             => __( 'Service ansehen', 'easy-status-check' ),
            'search_items'          => __( 'Services durchsuchen', 'easy-status-check' ),
            'not_found'             => __( 'Keine Services gefunden', 'easy-status-check' ),
            'not_found_in_trash'    => __( 'Keine Services im Papierkorb', 'easy-status-check' ),
            'all_items'             => __( 'Services', 'easy-status-check' ),
        );

        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => false, // Komplett aus Admin-Menü entfernen
            'query_var'             => true,
            'rewrite'               => array( 'slug' => 'service' ),
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => null,
            'supports'              => array( 'title', 'editor', 'thumbnail' ),
            'show_in_rest'          => true,
        );

        register_post_type( 'easy_service', $args );
    }

    /**
     * Register Service Taxonomies
     */
    public function register_taxonomies() {
        // Service Category Taxonomy
        $category_labels = array(
            'name'              => __( 'Service-Kategorien', 'easy-status-check' ),
            'singular_name'     => __( 'Service-Kategorie', 'easy-status-check' ),
            'search_items'      => __( 'Kategorien durchsuchen', 'easy-status-check' ),
            'all_items'         => __( 'Alle Kategorien', 'easy-status-check' ),
            'edit_item'         => __( 'Kategorie bearbeiten', 'easy-status-check' ),
            'update_item'       => __( 'Kategorie aktualisieren', 'easy-status-check' ),
            'add_new_item'      => __( 'Neue Kategorie hinzufügen', 'easy-status-check' ),
            'new_item_name'     => __( 'Neuer Kategoriename', 'easy-status-check' ),
            'menu_name'         => __( 'Kategorien', 'easy-status-check' ),
        );

        register_taxonomy(
            'service_category',
            'easy_service',
            array(
                'hierarchical'      => true,
                'labels'            => $category_labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => array( 'slug' => 'service-category' ),
                'show_in_rest'      => true,
            )
        );

        // Service Type Taxonomy
        $type_labels = array(
            'name'              => __( 'Service-Typen', 'easy-status-check' ),
            'singular_name'     => __( 'Service-Typ', 'easy-status-check' ),
            'search_items'      => __( 'Typen durchsuchen', 'easy-status-check' ),
            'all_items'         => __( 'Alle Typen', 'easy-status-check' ),
            'edit_item'         => __( 'Typ bearbeiten', 'easy-status-check' ),
            'update_item'       => __( 'Typ aktualisieren', 'easy-status-check' ),
            'add_new_item'      => __( 'Neuen Typ hinzufügen', 'easy-status-check' ),
            'new_item_name'     => __( 'Neuer Typname', 'easy-status-check' ),
            'menu_name'         => __( 'Typen', 'easy-status-check' ),
        );

        register_taxonomy(
            'service_type',
            'easy_service',
            array(
                'hierarchical'      => false,
                'labels'            => $type_labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => array( 'slug' => 'service-type' ),
                'show_in_rest'      => true,
            )
        );
    }

    /**
     * Add Meta Boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'esc_service_config',
            __( 'Service-Konfiguration', 'easy-status-check' ),
            array( $this, 'render_config_meta_box' ),
            'easy_service',
            'normal',
            'high'
        );

        add_meta_box(
            'esc_service_auth',
            __( 'Authentifizierung', 'easy-status-check' ),
            array( $this, 'render_auth_meta_box' ),
            'easy_service',
            'normal',
            'default'
        );

        add_meta_box(
            'esc_service_advanced',
            __( 'Erweiterte Einstellungen', 'easy-status-check' ),
            array( $this, 'render_advanced_meta_box' ),
            'easy_service',
            'normal',
            'default'
        );

        add_meta_box(
            'esc_service_status',
            __( 'Aktueller Status', 'easy-status-check' ),
            array( $this, 'render_status_meta_box' ),
            'easy_service',
            'side',
            'high'
        );
    }

    /**
     * Render Service Configuration Meta Box
     */
    public function render_config_meta_box( $post ) {
        wp_nonce_field( 'esc_service_meta_box', 'esc_service_meta_box_nonce' );

        $endpoint_url = get_post_meta( $post->ID, '_esc_endpoint_url', true );
        $service_type = get_post_meta( $post->ID, '_esc_service_type', true );
        $expected_status = get_post_meta( $post->ID, '_esc_expected_status', true ) ?: '200';
        $timeout = get_post_meta( $post->ID, '_esc_timeout', true ) ?: '10';
        $check_interval = get_post_meta( $post->ID, '_esc_check_interval', true ) ?: '5';
        $service_enabled = get_post_meta( $post->ID, '_esc_service_enabled', true ) !== '0';
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="esc_endpoint_url"><?php _e( 'Endpoint-URL', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <input type="url" id="esc_endpoint_url" name="esc_endpoint_url" 
                           value="<?php echo esc_attr( $endpoint_url ); ?>" 
                           class="large-text" required>
                    <p class="description"><?php _e( 'Die zu überwachende URL (z.B. https://api.example.com/status)', 'easy-status-check' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="esc_service_type"><?php _e( 'Service-Typ', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <select id="esc_service_type" name="esc_service_type" class="regular-text">
                        <option value="http" <?php selected( $service_type, 'http' ); ?>><?php _e( 'HTTP/HTTPS', 'easy-status-check' ); ?></option>
                        <option value="api" <?php selected( $service_type, 'api' ); ?>><?php _e( 'API (JSON)', 'easy-status-check' ); ?></option>
                        <option value="ping" <?php selected( $service_type, 'ping' ); ?>><?php _e( 'Ping', 'easy-status-check' ); ?></option>
                        <option value="dns" <?php selected( $service_type, 'dns' ); ?>><?php _e( 'DNS', 'easy-status-check' ); ?></option>
                        <option value="port" <?php selected( $service_type, 'port' ); ?>><?php _e( 'Port-Check', 'easy-status-check' ); ?></option>
                    </select>
                    <p class="description"><?php _e( 'Art der Überwachung', 'easy-status-check' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="esc_expected_status"><?php _e( 'Erwarteter HTTP-Status', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <input type="text" id="esc_expected_status" name="esc_expected_status" 
                           value="<?php echo esc_attr( $expected_status ); ?>" 
                           class="regular-text">
                    <p class="description"><?php _e( 'Erwartete HTTP-Status-Codes (z.B. 200, 201, 204)', 'easy-status-check' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="esc_timeout"><?php _e( 'Timeout (Sekunden)', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <input type="number" id="esc_timeout" name="esc_timeout" 
                           value="<?php echo esc_attr( $timeout ); ?>" 
                           min="1" max="60" class="small-text">
                    <p class="description"><?php _e( 'Maximale Wartezeit für Antwort (1-60 Sekunden)', 'easy-status-check' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="esc_check_interval"><?php _e( 'Check-Intervall (Minuten)', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <select id="esc_check_interval" name="esc_check_interval" class="regular-text">
                        <option value="1" <?php selected( $check_interval, '1' ); ?>>1 <?php _e( 'Minute', 'easy-status-check' ); ?></option>
                        <option value="5" <?php selected( $check_interval, '5' ); ?>>5 <?php _e( 'Minuten', 'easy-status-check' ); ?></option>
                        <option value="15" <?php selected( $check_interval, '15' ); ?>>15 <?php _e( 'Minuten', 'easy-status-check' ); ?></option>
                        <option value="30" <?php selected( $check_interval, '30' ); ?>>30 <?php _e( 'Minuten', 'easy-status-check' ); ?></option>
                        <option value="60" <?php selected( $check_interval, '60' ); ?>>60 <?php _e( 'Minuten', 'easy-status-check' ); ?></option>
                    </select>
                    <p class="description"><?php _e( 'Wie oft soll der Service geprüft werden?', 'easy-status-check' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="esc_service_enabled"><?php _e( 'Service aktiviert', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="esc_service_enabled" name="esc_service_enabled" 
                               value="1" <?php checked( $service_enabled, true ); ?>>
                        <?php _e( 'Service-Überwachung aktivieren', 'easy-status-check' ); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Authentication Meta Box
     */
    public function render_auth_meta_box( $post ) {
        $auth_type = get_post_meta( $post->ID, '_esc_auth_type', true );
        $api_key = get_post_meta( $post->ID, '_esc_api_key', true );
        $bearer_token = get_post_meta( $post->ID, '_esc_bearer_token', true );
        $basic_auth_user = get_post_meta( $post->ID, '_esc_basic_auth_user', true );
        $basic_auth_pass = get_post_meta( $post->ID, '_esc_basic_auth_pass', true );
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="esc_auth_type"><?php _e( 'Authentifizierungstyp', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <select id="esc_auth_type" name="esc_auth_type" class="regular-text">
                        <option value="none" <?php selected( $auth_type, 'none' ); ?>><?php _e( 'Keine', 'easy-status-check' ); ?></option>
                        <option value="api_key" <?php selected( $auth_type, 'api_key' ); ?>><?php _e( 'API-Key', 'easy-status-check' ); ?></option>
                        <option value="bearer" <?php selected( $auth_type, 'bearer' ); ?>><?php _e( 'Bearer Token', 'easy-status-check' ); ?></option>
                        <option value="basic" <?php selected( $auth_type, 'basic' ); ?>><?php _e( 'Basic Auth', 'easy-status-check' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr class="esc-auth-field esc-auth-api-key" style="display: <?php echo $auth_type === 'api_key' ? 'table-row' : 'none'; ?>;">
                <th scope="row">
                    <label for="esc_api_key"><?php _e( 'API-Key', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <input type="text" id="esc_api_key" name="esc_api_key" 
                           value="<?php echo esc_attr( $api_key ); ?>" 
                           class="large-text">
                    <p class="description"><?php _e( 'Ihr API-Schlüssel für die Authentifizierung', 'easy-status-check' ); ?></p>
                </td>
            </tr>
            <tr class="esc-auth-field esc-auth-bearer" style="display: <?php echo $auth_type === 'bearer' ? 'table-row' : 'none'; ?>;">
                <th scope="row">
                    <label for="esc_bearer_token"><?php _e( 'Bearer Token', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <input type="text" id="esc_bearer_token" name="esc_bearer_token" 
                           value="<?php echo esc_attr( $bearer_token ); ?>" 
                           class="large-text">
                    <p class="description"><?php _e( 'Ihr Bearer Token für die Authentifizierung', 'easy-status-check' ); ?></p>
                </td>
            </tr>
            <tr class="esc-auth-field esc-auth-basic" style="display: <?php echo $auth_type === 'basic' ? 'table-row' : 'none'; ?>;">
                <th scope="row">
                    <label for="esc_basic_auth_user"><?php _e( 'Benutzername', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <input type="text" id="esc_basic_auth_user" name="esc_basic_auth_user" 
                           value="<?php echo esc_attr( $basic_auth_user ); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr class="esc-auth-field esc-auth-basic" style="display: <?php echo $auth_type === 'basic' ? 'table-row' : 'none'; ?>;">
                <th scope="row">
                    <label for="esc_basic_auth_pass"><?php _e( 'Passwort', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <input type="password" id="esc_basic_auth_pass" name="esc_basic_auth_pass" 
                           value="<?php echo esc_attr( $basic_auth_pass ); ?>" 
                           class="regular-text">
                </td>
            </tr>
        </table>
        <script>
        jQuery(document).ready(function($) {
            $('#esc_auth_type').on('change', function() {
                $('.esc-auth-field').hide();
                var authType = $(this).val();
                if (authType !== 'none') {
                    $('.esc-auth-' + authType.replace('_', '-')).show();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render Advanced Settings Meta Box
     */
    public function render_advanced_meta_box( $post ) {
        $custom_headers = get_post_meta( $post->ID, '_esc_custom_headers', true );
        $json_path = get_post_meta( $post->ID, '_esc_json_path', true );
        $service_icon = get_post_meta( $post->ID, '_esc_service_icon', true );
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="esc_service_icon"><?php _e( 'Service-Icon/Logo URL', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <input type="url" id="esc_service_icon" name="esc_service_icon" 
                           value="<?php echo esc_attr( $service_icon ); ?>" 
                           class="large-text">
                    <p class="description"><?php _e( 'URL zum Service-Logo (optional)', 'easy-status-check' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="esc_json_path"><?php _e( 'JSON-Pfad', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <input type="text" id="esc_json_path" name="esc_json_path" 
                           value="<?php echo esc_attr( $json_path ); ?>" 
                           class="large-text">
                    <p class="description"><?php _e( 'Pfad zum Status-Wert in JSON-APIs (z.B. status.indicator)', 'easy-status-check' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="esc_custom_headers"><?php _e( 'Custom Headers', 'easy-status-check' ); ?></label>
                </th>
                <td>
                    <textarea id="esc_custom_headers" name="esc_custom_headers" 
                              rows="5" class="large-text code"><?php echo esc_textarea( $custom_headers ); ?></textarea>
                    <p class="description"><?php _e( 'Zusätzliche HTTP-Header (ein Header pro Zeile, Format: Header-Name: Wert)', 'easy-status-check' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Current Status Meta Box
     */
    public function render_status_meta_box( $post ) {
        global $wpdb;
        
        // Get service from database table by matching post meta
        $services_table = $wpdb->prefix . 'esc_services';
        $endpoint_url = get_post_meta( $post->ID, '_esc_endpoint_url', true );
        
        $service = null;
        if ( $endpoint_url ) {
            $service = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $services_table WHERE url = %s LIMIT 1",
                $endpoint_url
            ) );
        }
        
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        $latest_log = null;
        
        if ( $service ) {
            $latest_log = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $logs_table WHERE service_id = %d ORDER BY checked_at DESC LIMIT 1",
                $service->id
            ) );
        }

        if ( $latest_log ) {
            $status_class = 'esc-status-' . $latest_log->status;
            $status_text = '';
            switch ( $latest_log->status ) {
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
            ?>
            <div class="esc-status-display <?php echo esc_attr( $status_class ); ?>">
                <p><strong><?php _e( 'Status:', 'easy-status-check' ); ?></strong> <?php echo esc_html( $status_text ); ?></p>
                <p><strong><?php _e( 'HTTP-Code:', 'easy-status-check' ); ?></strong> <?php echo esc_html( $latest_log->http_code ); ?></p>
                <p><strong><?php _e( 'Antwortzeit:', 'easy-status-check' ); ?></strong> <?php echo esc_html( $latest_log->response_time ); ?> ms</p>
                <p><strong><?php _e( 'Letzte Prüfung:', 'easy-status-check' ); ?></strong> <?php echo esc_html( $latest_log->checked_at ); ?></p>
                <?php if ( $latest_log->error_message ) : ?>
                    <p><strong><?php _e( 'Fehler:', 'easy-status-check' ); ?></strong> <?php echo esc_html( $latest_log->error_message ); ?></p>
                <?php endif; ?>
            </div>
            <style>
                .esc-status-display { padding: 15px; border-radius: 5px; margin: 10px 0; }
                .esc-status-online { background: #d4edda; border: 1px solid #c3e6cb; }
                .esc-status-offline { background: #f8d7da; border: 1px solid #f5c6cb; }
                .esc-status-warning { background: #fff3cd; border: 1px solid #ffeaa7; }
            </style>
            <?php
        } else {
            ?>
            <p><?php _e( 'Noch keine Status-Daten verfügbar.', 'easy-status-check' ); ?></p>
            <?php
        }
        ?>
        <p>
            <button type="button" class="button button-primary" id="esc-test-service">
                <?php _e( 'Jetzt testen', 'easy-status-check' ); ?>
            </button>
        </p>
        <script>
        jQuery(document).ready(function($) {
            $('#esc-test-service').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('<?php _e( 'Teste...', 'easy-status-check' ); ?>');
                
                $.post(ajaxurl, {
                    action: 'esc_test_service',
                    post_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce( 'esc_test_service' ); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                        button.prop('disabled', false).text('<?php _e( 'Jetzt testen', 'easy-status-check' ); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Save Meta Box Data
     */
    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['esc_service_meta_box_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['esc_service_meta_box_nonce'], 'esc_service_meta_box' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = array(
            'esc_endpoint_url',
            'esc_service_type',
            'esc_expected_status',
            'esc_timeout',
            'esc_check_interval',
            'esc_auth_type',
            'esc_api_key',
            'esc_bearer_token',
            'esc_basic_auth_user',
            'esc_basic_auth_pass',
            'esc_custom_headers',
            'esc_json_path',
            'esc_service_icon',
        );

        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = sanitize_text_field( $_POST[ $field ] );
                if ( $field === 'esc_custom_headers' ) {
                    $value = sanitize_textarea_field( $_POST[ $field ] );
                }
                update_post_meta( $post_id, '_' . $field, $value );
            }
        }

        $service_enabled = isset( $_POST['esc_service_enabled'] ) ? '1' : '0';
        update_post_meta( $post_id, '_esc_service_enabled', $service_enabled );

        // Sync with database table
        $this->sync_service_to_database( $post_id );

        do_action( 'esc_after_save_service', $post_id );
    }

    /**
     * Sync service post to database table
     */
    private function sync_service_to_database( $post_id ) {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        $endpoint_url = get_post_meta( $post_id, '_esc_endpoint_url', true );
        
        if ( empty( $endpoint_url ) ) {
            return;
        }
        
        $post = get_post( $post_id );
        
        $service_data = array(
            'name' => $post->post_title,
            'url' => $endpoint_url,
            'category' => get_post_meta( $post_id, '_esc_service_category', true ) ?: 'custom',
            'method' => 'GET',
            'timeout' => intval( get_post_meta( $post_id, '_esc_timeout', true ) ) ?: 10,
            'expected_code' => get_post_meta( $post_id, '_esc_expected_status', true ) ?: '200',
            'check_interval' => intval( get_post_meta( $post_id, '_esc_check_interval', true ) ) * 60 ?: 300,
            'enabled' => get_post_meta( $post_id, '_esc_service_enabled', true ) === '1' ? 1 : 0,
            'notify_email' => 1,
            'response_type' => get_post_meta( $post_id, '_esc_service_type', true ),
            'json_path' => get_post_meta( $post_id, '_esc_json_path', true ),
            'updated_at' => current_time( 'mysql' ),
        );
        
        // Build custom headers JSON
        $auth_type = get_post_meta( $post_id, '_esc_auth_type', true );
        $custom_headers = array();
        
        if ( $auth_type === 'api_key' ) {
            $api_key = get_post_meta( $post_id, '_esc_api_key', true );
            if ( $api_key ) {
                $custom_headers['X-API-Key'] = $api_key;
            }
        } elseif ( $auth_type === 'bearer' ) {
            $bearer_token = get_post_meta( $post_id, '_esc_bearer_token', true );
            if ( $bearer_token ) {
                $custom_headers['Authorization'] = 'Bearer ' . $bearer_token;
            }
        } elseif ( $auth_type === 'basic' ) {
            $user = get_post_meta( $post_id, '_esc_basic_auth_user', true );
            $pass = get_post_meta( $post_id, '_esc_basic_auth_pass', true );
            if ( $user && $pass ) {
                $custom_headers['Authorization'] = 'Basic ' . base64_encode( $user . ':' . $pass );
            }
        }
        
        // Add custom headers from textarea
        $custom_headers_text = get_post_meta( $post_id, '_esc_custom_headers', true );
        if ( $custom_headers_text ) {
            $lines = explode( "\n", $custom_headers_text );
            foreach ( $lines as $line ) {
                if ( strpos( $line, ':' ) !== false ) {
                    list( $key, $value ) = explode( ':', $line, 2 );
                    $custom_headers[ trim( $key ) ] = trim( $value );
                }
            }
        }
        
        if ( ! empty( $custom_headers ) ) {
            $service_data['custom_headers'] = json_encode( $custom_headers );
        }
        
        // Check if service exists
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $services_table WHERE url = %s",
            $endpoint_url
        ) );
        
        if ( $existing ) {
            $wpdb->update(
                $services_table,
                $service_data,
                array( 'id' => $existing ),
                array( '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            $service_data['created_at'] = current_time( 'mysql' );
            $wpdb->insert(
                $services_table,
                $service_data,
                array( '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
            );
        }
    }

    /**
     * Set Custom Columns
     */
    public function set_custom_columns( $columns ) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['status'] = __( 'Status', 'easy-status-check' );
        $new_columns['endpoint'] = __( 'Endpoint', 'easy-status-check' );
        $new_columns['type'] = __( 'Typ', 'easy-status-check' );
        $new_columns['response_time'] = __( 'Antwortzeit', 'easy-status-check' );
        $new_columns['last_check'] = __( 'Letzte Prüfung', 'easy-status-check' );
        $new_columns['enabled'] = __( 'Aktiviert', 'easy-status-check' );
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }

    /**
     * Custom Column Content
     */
    public function custom_column_content( $column, $post_id ) {
        global $wpdb;
        
        // Get service ID from database
        $services_table = $wpdb->prefix . 'esc_services';
        $endpoint_url = get_post_meta( $post_id, '_esc_endpoint_url', true );
        
        $service = null;
        if ( $endpoint_url ) {
            $service = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $services_table WHERE url = %s LIMIT 1",
                $endpoint_url
            ) );
        }
        
        switch ( $column ) {
            case 'status':
                if ( ! $service ) {
                    echo '⚪';
                    break;
                }
                
                $logs_table = $wpdb->prefix . 'esc_status_logs';
                $latest_log = $wpdb->get_row( $wpdb->prepare(
                    "SELECT status FROM $logs_table WHERE service_id = %d ORDER BY checked_at DESC LIMIT 1",
                    $service->id
                ) );
                
                if ( $latest_log ) {
                    $status_icons = array(
                        'online' => '🟢',
                        'offline' => '🔴',
                        'warning' => '🟡',
                    );
                    echo $status_icons[ $latest_log->status ] ?? '⚪';
                } else {
                    echo '⚪';
                }
                break;
                
            case 'endpoint':
                $endpoint = get_post_meta( $post_id, '_esc_endpoint_url', true );
                echo esc_html( $endpoint );
                break;
                
            case 'type':
                $type = get_post_meta( $post_id, '_esc_service_type', true );
                echo esc_html( strtoupper( $type ) );
                break;
                
            case 'response_time':
                if ( ! $service ) {
                    echo '—';
                    break;
                }
                
                $logs_table = $wpdb->prefix . 'esc_status_logs';
                $latest_log = $wpdb->get_row( $wpdb->prepare(
                    "SELECT response_time FROM $logs_table WHERE service_id = %d ORDER BY checked_at DESC LIMIT 1",
                    $service->id
                ) );
                
                if ( $latest_log && $latest_log->response_time ) {
                    echo esc_html( $latest_log->response_time ) . ' ms';
                } else {
                    echo '—';
                }
                break;
                
            case 'last_check':
                if ( ! $service ) {
                    echo '—';
                    break;
                }
                
                $logs_table = $wpdb->prefix . 'esc_status_logs';
                $latest_log = $wpdb->get_row( $wpdb->prepare(
                    "SELECT checked_at FROM $logs_table WHERE service_id = %d ORDER BY checked_at DESC LIMIT 1",
                    $service->id
                ) );
                
                if ( $latest_log ) {
                    echo esc_html( human_time_diff( strtotime( $latest_log->checked_at ), current_time( 'timestamp' ) ) ) . ' ' . __( 'her', 'easy-status-check' );
                } else {
                    echo '—';
                }
                break;
                
            case 'enabled':
                $enabled = get_post_meta( $post_id, '_esc_service_enabled', true );
                echo $enabled === '1' ? '✓' : '✗';
                break;
        }
    }
}
