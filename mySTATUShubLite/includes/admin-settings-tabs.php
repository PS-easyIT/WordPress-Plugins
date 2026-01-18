<?php
/**
 * Settings Page with Tabs
 *
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current tab
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

// Handle settings save
if ( isset( $_POST['esc_save_settings'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'esc_settings_' . $active_tab ) ) {
    switch ( $active_tab ) {
        case 'general':
            update_option( 'esc_public_status_enabled', isset( $_POST['esc_public_status_enabled'] ) );
            update_option( 'esc_public_services_enabled', isset( $_POST['esc_public_services_enabled'] ) );
            update_option( 'esc_public_incidents_enabled', isset( $_POST['esc_public_incidents_enabled'] ) );
            update_option( 'esc_public_history_enabled', isset( $_POST['esc_public_history_enabled'] ) );
            update_option( 'esc_public_status_slug', sanitize_title( $_POST['esc_public_status_slug'] ?? 'status' ) );
            update_option( 'esc_public_services_slug', sanitize_title( $_POST['esc_public_services_slug'] ?? 'services' ) );
            update_option( 'esc_public_incidents_slug', sanitize_title( $_POST['esc_public_incidents_slug'] ?? 'incidents' ) );
            update_option( 'esc_public_history_slug', sanitize_title( $_POST['esc_public_history_slug'] ?? 'history' ) );
            update_option( 'esc_general_settings', array(
                'default_interval' => intval( $_POST['default_interval'] ?? 300 ),
                'default_timeout' => intval( $_POST['default_timeout'] ?? 10 )
            ) );
            flush_rewrite_rules();
            break;
            
        case 'notifications':
            update_option( 'esc_notification_settings', array(
                'enabled' => isset( $_POST['notifications_enabled'] ),
                'email' => sanitize_email( $_POST['notification_email'] ?? get_option( 'admin_email' ) )
            ) );
            break;
            
        case 'design':
            $public_settings = array(
                'primary_color' => sanitize_hex_color( $_POST['primary_color'] ?? '#2271b1' ),
                'success_color' => sanitize_hex_color( $_POST['success_color'] ?? '#00a32a' ),
                'warning_color' => sanitize_hex_color( $_POST['warning_color'] ?? '#f0b849' ),
                'error_color' => sanitize_hex_color( $_POST['error_color'] ?? '#d63638' ),
                'background_color' => sanitize_hex_color( $_POST['background_color'] ?? '#f0f0f1' ),
                'text_color' => sanitize_hex_color( $_POST['text_color'] ?? '#1d2327' ),
                'show_response_time' => isset( $_POST['show_response_time'] ),
                'show_uptime' => isset( $_POST['show_uptime'] ),
                'refresh_interval' => intval( $_POST['refresh_interval'] ?? 300 ),
                'columns' => intval( $_POST['columns'] ?? 3 ),
                'page_title' => sanitize_text_field( $_POST['page_title'] ?? 'Service Status' ),
                'page_description' => sanitize_text_field( $_POST['page_description'] ?? 'Aktuelle Status-Informationen unserer Services' ),
                'incidents_title' => sanitize_text_field( $_POST['incidents_title'] ?? 'Security Incidents & CVE Feeds' ),
                'incidents_description' => sanitize_text_field( $_POST['incidents_description'] ?? 'Aktuelle Sicherheitsvorfälle und Schwachstellen aus verschiedenen Quellen' )
            );
            update_option( 'esc_public_settings', $public_settings );
            break;
    }
    
    echo '<div class="notice notice-success"><p>' . __( 'Einstellungen gespeichert.', 'easy-status-check' ) . '</p></div>';
}

// Pro Feature Label Helper
function esc_pro_label() {
    return '<span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; margin-left: 8px;">⭐ PRO</span>';
}

// Get settings
$general_settings = get_option( 'esc_general_settings', array( 'default_interval' => 300, 'default_timeout' => 10 ) );
$notification_settings = get_option( 'esc_notification_settings', array( 'enabled' => 1, 'email' => get_option( 'admin_email' ) ) );
$public_settings = get_option( 'esc_public_settings', array(
    'primary_color' => '#2271b1',
    'success_color' => '#00a32a',
    'warning_color' => '#f0b849',
    'error_color' => '#d63638',
    'background_color' => '#f0f0f1',
    'text_color' => '#1d2327',
    'show_response_time' => true,
    'show_uptime' => true,
    'refresh_interval' => 300,
    'columns' => 3,
    'page_title' => 'Service Status',
    'page_description' => 'Aktuelle Status-Informationen unserer Services',
    'incidents_title' => 'Security Incidents & CVE Feeds',
    'incidents_description' => 'Aktuelle Sicherheitsvorfälle und Schwachstellen aus verschiedenen Quellen'
) );
$public_status_enabled = get_option( 'esc_public_status_enabled', false );
$public_services_enabled = get_option( 'esc_public_services_enabled', true );
$public_incidents_enabled = get_option( 'esc_public_incidents_enabled', true );
$public_history_enabled = get_option( 'esc_public_history_enabled', true );
$public_status_slug = get_option( 'esc_public_status_slug', 'status' );
$public_services_slug = get_option( 'esc_public_services_slug', 'services' );
$public_incidents_slug = get_option( 'esc_public_incidents_slug', 'incidents' );
$public_history_slug = get_option( 'esc_public_history_slug', 'history' );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Einstellungen', 'easy-status-check' ); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=easy-status-check-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Allgemein', 'easy-status-check' ); ?>
        </a>
        <a href="?page=easy-status-check-settings&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Benachrichtigungen', 'easy-status-check' ); ?>
        </a>
        <a href="?page=easy-status-check-settings&tab=design" class="nav-tab <?php echo $active_tab === 'design' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Design', 'easy-status-check' ); ?>
        </a>
        <a href="?page=easy-status-check-settings&tab=support" class="nav-tab <?php echo $active_tab === 'support' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Support', 'easy-status-check' ); ?>
        </a>
        <a href="?page=easy-status-check-settings&tab=pro" class="nav-tab <?php echo $active_tab === 'pro' ? 'nav-tab-active' : ''; ?>" style="color: #d63638; font-weight: 600;">
            ⭐ <?php esc_html_e( 'Get Pro', 'easy-status-check' ); ?>
        </a>
    </h2>
    
    <form method="post" action="">
        <?php wp_nonce_field( 'esc_settings_' . $active_tab ); ?>
        <input type="hidden" name="esc_save_settings" value="1">
        
        <?php if ( $active_tab === 'general' ) : ?>
            <!-- ALLGEMEIN TAB -->
            <h2><?php esc_html_e( 'Allgemeine Einstellungen', 'easy-status-check' ); ?></h2>
            
            <h3 style="margin-top: 30px; padding-bottom: 10px; border-bottom: 2px solid #2271b1;"><?php esc_html_e( '🌐 Public Pages', 'easy-status-check' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Master-Schalter', 'easy-status-check' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="esc_public_status_enabled" value="1" <?php checked( $public_status_enabled ); ?>>
                            <?php esc_html_e( 'Public Pages generell aktivieren', 'easy-status-check' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Hauptschalter für alle öffentlichen Seiten (ohne Login-Pflicht)', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Einzelne Seiten aktivieren', 'easy-status-check' ); ?></th>
                    <td>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" name="esc_public_services_enabled" value="1" <?php checked( $public_services_enabled ); ?> <?php disabled( ! $public_status_enabled ); ?>>
                            <strong><?php esc_html_e( 'Services-Seite', 'easy-status-check' ); ?></strong> - <?php esc_html_e( 'Übersicht aller Services', 'easy-status-check' ); ?>
                        </label>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" name="esc_public_incidents_enabled" value="1" <?php checked( $public_incidents_enabled ); ?> <?php disabled( ! $public_status_enabled ); ?>>
                            <strong><?php esc_html_e( 'Incidents-Seite', 'easy-status-check' ); ?></strong> - <?php esc_html_e( 'CVE-Feeds und Sicherheitsvorfälle', 'easy-status-check' ); ?>
                        </label>
                        <label style="display: block;">
                            <input type="checkbox" name="esc_public_history_enabled" value="1" <?php checked( $public_history_enabled ); ?> <?php disabled( ! $public_status_enabled ); ?>>
                            <strong><?php esc_html_e( 'History-Seite', 'easy-status-check' ); ?></strong> - <?php esc_html_e( 'Service-Historie mit Statistiken', 'easy-status-check' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Wählen Sie, welche Seiten öffentlich zugänglich sein sollen', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
            </table>
            
            <h3 style="margin-top: 30px; padding-bottom: 10px; border-bottom: 2px solid #2271b1;"><?php esc_html_e( '🔗 URL-Struktur', 'easy-status-check' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Basis URL-Slug', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="text" name="esc_public_status_slug" value="<?php echo esc_attr( $public_status_slug ); ?>" class="regular-text" placeholder="status">
                        <p class="description">
                            <?php printf( __( 'Basis-URL: %s', 'easy-status-check' ), '<code>' . home_url( '/' ) . esc_html( $public_status_slug ) . '</code>' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Services URL-Slug', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="text" name="esc_public_services_slug" value="<?php echo esc_attr( $public_services_slug ); ?>" class="regular-text" placeholder="services">
                        <p class="description">
                            <?php printf( __( 'Vollständige URL: %s', 'easy-status-check' ), '<code>' . home_url( '/' . $public_status_slug . '/' . $public_services_slug ) . '</code>' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Incidents URL-Slug', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="text" name="esc_public_incidents_slug" value="<?php echo esc_attr( $public_incidents_slug ); ?>" class="regular-text" placeholder="incidents">
                        <p class="description">
                            <?php printf( __( 'Vollständige URL: %s', 'easy-status-check' ), '<code>' . home_url( '/' . $public_status_slug . '/' . $public_incidents_slug ) . '</code>' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'History URL-Slug', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="text" name="esc_public_history_slug" value="<?php echo esc_attr( $public_history_slug ); ?>" class="regular-text" placeholder="history">
                        <p class="description">
                            <?php printf( __( 'Vollständige URL: %s', 'easy-status-check' ), '<code>' . home_url( '/' . $public_status_slug . '/' . $public_history_slug . '/[ID]' ) . '</code>' ); ?>
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
            </table>
            
            <h3 style="margin-top: 30px; padding-bottom: 10px; border-bottom: 2px solid #2271b1;"><?php esc_html_e( '⚙️ Service-Monitoring', 'easy-status-check' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Standard Prüfintervall', 'easy-status-check' ); ?></th>
                    <td>
                        <select name="default_interval" class="regular-text">
                            <option value="300" <?php selected( $general_settings['default_interval'], 300 ); ?>>5 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                            <option value="600" <?php selected( $general_settings['default_interval'], 600 ); ?>>10 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                            <option value="1800" <?php selected( $general_settings['default_interval'], 1800 ); ?>>30 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                            <option value="3600" <?php selected( $general_settings['default_interval'], 3600 ); ?>>60 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Wie oft sollen Services standardmäßig geprüft werden?', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Standard Timeout', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="number" name="default_timeout" value="<?php echo esc_attr( $general_settings['default_timeout'] ); ?>" min="1" max="60" class="small-text"> <?php esc_html_e( 'Sekunden', 'easy-status-check' ); ?>
                        <p class="description"><?php esc_html_e( 'Maximale Wartezeit für HTTP-Anfragen (1-60 Sekunden)', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
            </table>
            
        <?php elseif ( $active_tab === 'notifications' ) : ?>
            <!-- BENACHRICHTIGUNGEN TAB -->
            <h2><?php esc_html_e( 'Benachrichtigungs-Einstellungen', 'easy-status-check' ); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'E-Mail Benachrichtigungen', 'easy-status-check' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="notifications_enabled" value="1" <?php checked( $notification_settings['enabled'] ); ?>>
                            <?php esc_html_e( 'E-Mail Benachrichtigungen bei Statusänderungen aktivieren', 'easy-status-check' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Erhalten Sie E-Mails wenn Services offline gehen oder wieder online kommen', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Benachrichtigungs E-Mail', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="email" name="notification_email" value="<?php echo esc_attr( $notification_settings['email'] ); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e( 'E-Mail Adresse für Benachrichtigungen', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
            </table>
            
        <?php elseif ( $active_tab === 'design' ) : ?>
            <!-- DESIGN TAB -->
            <h2><?php esc_html_e( 'Design-Einstellungen für Public Pages', 'easy-status-check' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Passen Sie das Aussehen der öffentlichen Status-Seiten an.', 'easy-status-check' ); ?></p>
            
            <h3 style="margin-top: 30px; padding-bottom: 10px; border-bottom: 2px solid #2271b1;"><?php esc_html_e( '📄 Seitentexte', 'easy-status-check' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Services - Titel', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="text" name="page_title" value="<?php echo esc_attr( isset( $public_settings['page_title'] ) ? $public_settings['page_title'] : 'Service Status' ); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Titel der öffentlichen Services-Seite', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Services - Beschreibung', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="text" name="page_description" value="<?php echo esc_attr( isset( $public_settings['page_description'] ) ? $public_settings['page_description'] : 'Aktuelle Status-Informationen unserer Services' ); ?>" class="large-text">
                        <p class="description"><?php esc_html_e( 'Beschreibung unter dem Titel der Services-Seite', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Incidents - Titel', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="text" name="incidents_title" value="<?php echo esc_attr( isset( $public_settings['incidents_title'] ) ? $public_settings['incidents_title'] : 'Security Incidents & CVE Feeds' ); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Titel der öffentlichen Incidents-Seite', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Incidents - Beschreibung', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="text" name="incidents_description" value="<?php echo esc_attr( isset( $public_settings['incidents_description'] ) ? $public_settings['incidents_description'] : 'Aktuelle Sicherheitsvorfälle und Schwachstellen aus verschiedenen Quellen' ); ?>" class="large-text">
                        <p class="description"><?php esc_html_e( 'Beschreibung unter dem Titel der Incidents-Seite', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
            </table>
            
            <h3 style="margin-top: 30px; padding-bottom: 10px; border-bottom: 2px solid #2271b1;"><?php esc_html_e( '🎨 Farbschema', 'easy-status-check' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Primärfarbe', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="color" name="primary_color" value="<?php echo esc_attr( $public_settings['primary_color'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Hauptfarbe für Buttons und Akzente', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Erfolgsfarbe (Online)', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="color" name="success_color" value="<?php echo esc_attr( $public_settings['success_color'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Farbe für Online-Status', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Warnfarbe', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="color" name="warning_color" value="<?php echo esc_attr( $public_settings['warning_color'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Farbe für Warnungen', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Fehlerfarbe (Offline)', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="color" name="error_color" value="<?php echo esc_attr( $public_settings['error_color'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Farbe für Offline-Status', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Hintergrundfarbe', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="color" name="background_color" value="<?php echo esc_attr( $public_settings['background_color'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Hintergrundfarbe der Seite', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Textfarbe', 'easy-status-check' ); ?></th>
                    <td>
                        <input type="color" name="text_color" value="<?php echo esc_attr( $public_settings['text_color'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Haupttextfarbe', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
            </table>
            
            <h3 style="margin-top: 30px; padding-bottom: 10px; border-bottom: 2px solid #2271b1;"><?php esc_html_e( '⚙️ Layout-Optionen', 'easy-status-check' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Spaltenanzahl (Services)', 'easy-status-check' ); ?></th>
                    <td>
                        <select name="columns" class="regular-text">
                            <option value="1" <?php selected( $public_settings['columns'], 1 ); ?>>1 <?php esc_html_e( 'Spalte', 'easy-status-check' ); ?></option>
                            <option value="2" <?php selected( $public_settings['columns'], 2 ); ?>>2 <?php esc_html_e( 'Spalten', 'easy-status-check' ); ?></option>
                            <option value="3" <?php selected( $public_settings['columns'], 3 ); ?>>3 <?php esc_html_e( 'Spalten', 'easy-status-check' ); ?></option>
                            <option value="4" <?php selected( $public_settings['columns'], 4 ); ?>>4 <?php esc_html_e( 'Spalten', 'easy-status-check' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Anzahl der Service-Cards nebeneinander auf der öffentlichen Status-Seite', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e( 'Auto-Refresh Intervall', 'easy-status-check' ); ?></th>
                    <td>
                        <select name="refresh_interval" class="regular-text">
                            <option value="0" <?php selected( $public_settings['refresh_interval'], 0 ); ?>><?php esc_html_e( 'Deaktiviert', 'easy-status-check' ); ?></option>
                            <option value="60" <?php selected( $public_settings['refresh_interval'], 60 ); ?>>1 <?php esc_html_e( 'Minute', 'easy-status-check' ); ?></option>
                            <option value="300" <?php selected( $public_settings['refresh_interval'], 300 ); ?>>5 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                            <option value="600" <?php selected( $public_settings['refresh_interval'], 600 ); ?>>10 <?php esc_html_e( 'Minuten', 'easy-status-check' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Wie oft soll die öffentliche Seite automatisch aktualisiert werden?', 'easy-status-check' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Copyright ausblenden', 'easy-status-check' ); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="hide_copyright" value="1" disabled>
                            <?php esc_html_e( 'Copyright-Box auf Public Pages ausblenden', 'easy-status-check' ); ?>
                        </label>
                        <p class="description" style="color: #666;">
                            <?php esc_html_e( 'Diese Funktion ist nur in der Pro-Version verfügbar.', 'easy-status-check' ); ?>
                            <a href="?page=easy-status-check-settings&tab=pro" style="color: #667eea; font-weight: 600;"><?php esc_html_e( 'Jetzt upgraden', 'easy-status-check' ); ?></a>
                            <?php echo esc_pro_label(); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <h3 style="margin-top: 30px; padding-bottom: 10px; border-bottom: 2px solid #2271b1;"><?php esc_html_e( '📊 Erweiterte Optionen', 'easy-status-check' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Detaillierte Analytics', 'easy-status-check' ); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_analytics" value="1" disabled>
                            <?php esc_html_e( 'Erweiterte Statistiken und Reports aktivieren', 'easy-status-check' ); ?>
                        </label>
                        <p class="description" style="color: #666;">
                            <?php esc_html_e( 'Detaillierte Auswertungen, Uptime-Reports, Performance-Analysen. Nur in Pro-Version.', 'easy-status-check' ); ?>
                            <a href="?page=easy-status-check-settings&tab=pro" style="color: #667eea; font-weight: 600;"><?php esc_html_e( 'Jetzt upgraden', 'easy-status-check' ); ?></a>
                            <?php echo esc_pro_label(); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Erweiterte Benachrichtigungen', 'easy-status-check' ); ?>
                    </th>
                    <td>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" name="enable_sms" value="1" disabled>
                            <?php esc_html_e( 'SMS-Benachrichtigungen', 'easy-status-check' ); ?>
                        </label>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" name="enable_slack" value="1" disabled>
                            <?php esc_html_e( 'Slack-Integration', 'easy-status-check' ); ?>
                        </label>
                        <label style="display: block;">
                            <input type="checkbox" name="enable_teams" value="1" disabled>
                            <?php esc_html_e( 'Microsoft Teams-Integration', 'easy-status-check' ); ?>
                        </label>
                        <p class="description" style="color: #666;">
                            <?php esc_html_e( 'Erweiterte Benachrichtigungskanäle für schnellere Reaktionszeiten. Nur in Pro-Version.', 'easy-status-check' ); ?>
                            <a href="?page=easy-status-check-settings&tab=pro" style="color: #667eea; font-weight: 600;"><?php esc_html_e( 'Jetzt upgraden', 'easy-status-check' ); ?></a>
                            <?php echo esc_pro_label(); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
        <?php elseif ( $active_tab === 'pro' ) : ?>
            <!-- GET PRO TAB -->
            <div style="max-width: 800px; margin: 40px auto; text-align: center;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 60px 40px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); color: white;">
                    <h2 style="font-size: 42px; margin-bottom: 20px; color: white;">⭐ mySTATUShub Pro</h2>
                    <p style="font-size: 20px; margin-bottom: 30px; opacity: 0.95;">Erweitern Sie Ihr Monitoring mit professionellen Features</p>
                    
                    <div style="background: rgba(255,255,255,0.1); padding: 30px; border-radius: 12px; margin: 30px 0; backdrop-filter: blur(10px);">
                        <h3 style="color: white; margin-bottom: 20px; font-size: 24px;">🚀 Pro Features</h3>
                        <ul style="list-style: none; padding: 0; text-align: left; max-width: 600px; margin: 0 auto;">
                            <li style="padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.2); font-size: 16px;">✅ Erweiterte Monitoring-Optionen</li>
                            <li style="padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.2); font-size: 16px;">✅ 1 Jahr Support & Updates</li>
                            <li style="padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.2); font-size: 16px;">✅ Erweiterte Templates (bis zu 350+ Services)</li>
                            <li style="padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.2); font-size: 16px;">✅ Import/Export (Services & History)</li>
                            <li style="padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.2); font-size: 16px;">✅ Detaillierte Analytics & Reports</li>
                            <li style="padding: 12px 0; font-size: 16px;">✅ White-Label Optionen (Copyright ausblenden)</li>
                        </ul>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.15); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <p style="font-size: 16px; margin: 0 0 10px 0; opacity: 0.9;">Einmalige Zahlung</p>
                        <p style="font-size: 48px; font-weight: 700; margin: 0; color: white;">19,99 €</p>
                        <p style="font-size: 14px; margin: 10px 0 0 0; opacity: 0.8;">Lebenslanger Zugriff • Alle Updates inklusive</p>
                    </div>
                    
                    <a href="https://phinit.de/wordpress-plugins" target="_blank" rel="noopener noreferrer" 
                       style="display: inline-block; background: white; color: #667eea; padding: 18px 48px; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 18px; margin-top: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease;">
                        🛒 Jetzt für 19,99 € kaufen
                    </a>
                    
                    <p style="margin-top: 30px; font-size: 14px; opacity: 0.8;">
                        Besuchen Sie <strong>phinit.de/wordpress-plugins</strong> für weitere Informationen
                    </p>
                </div>
            </div>
            
        <?php elseif ( $active_tab === 'support' ) : ?>
            <!-- SUPPORT TAB -->
            <h2><?php esc_html_e( 'Support & System-Status', 'easy-status-check' ); ?></h2>
            
            <!-- Hilfe & Dokumentation -->
            <div style="background: #fff; padding: 20px; border-left: 4px solid #2271b1; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0;"><?php esc_html_e( '📖 Hilfe & Dokumentation', 'easy-status-check' ); ?></h3>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 15px;">
                    <div>
                        <h4><?php esc_html_e( 'Schnellstart', 'easy-status-check' ); ?></h4>
                        <ol style="margin: 10px 0; padding-left: 20px;">
                            <li><?php esc_html_e( 'Gehen Sie zu "Services" und fügen Sie Services hinzu (Templates oder manuell)', 'easy-status-check' ); ?></li>
                            <li><?php esc_html_e( 'Aktivieren Sie die Services die Sie überwachen möchten', 'easy-status-check' ); ?></li>
                            <li><?php esc_html_e( 'Konfigurieren Sie unter "Einstellungen" die Public Pages und Benachrichtigungen', 'easy-status-check' ); ?></li>
                            <li><?php esc_html_e( 'Besuchen Sie die Public Status Page um den aktuellen Status zu sehen', 'easy-status-check' ); ?></li>
                        </ol>
                    </div>
                    
                    <div>
                        <h4><?php esc_html_e( 'Wichtige Hinweise', 'easy-status-check' ); ?></h4>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li><?php esc_html_e( 'Nach URL-Änderungen: Permalinks neu speichern (Einstellungen → Permalinks)', 'easy-status-check' ); ?></li>
                            <li><?php esc_html_e( 'History-Daten: Services müssen aktiviert sein und geprüft werden', 'easy-status-check' ); ?></li>
                            <li><?php esc_html_e( 'Cron-Jobs: Werden automatisch bei Service-Aktivierung angelegt', 'easy-status-check' ); ?></li>
                            <li><?php esc_html_e( 'CVE-Feeds: Werden auf der Public Incidents Page angezeigt', 'easy-status-check' ); ?></li>
                        </ul>
                    </div>
                </div>
                
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f1;">
                    <strong><?php esc_html_e( 'Weitere Informationen:', 'easy-status-check' ); ?></strong>
                    Vollständige Dokumentation finden Sie in der <code>README.md</code> Datei im Plugin-Verzeichnis.
                </div>
            </div>
            
            <style>
                .esc-support-grid {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 20px;
                    margin: 20px 0;
                }
                .esc-support-card {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    border-radius: 8px;
                    padding: 20px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                }
                .esc-support-card h3 {
                    margin: 0 0 15px 0;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #2271b1;
                    color: #2271b1;
                }
                .esc-support-card .button {
                    margin: 5px 5px 5px 0;
                }
                .esc-status-item {
                    padding: 8px 0;
                    border-bottom: 1px solid #f0f0f1;
                }
                .esc-status-item:last-child {
                    border-bottom: none;
                }
                .esc-status-ok { color: #00a32a; font-weight: bold; }
                .esc-status-warning { color: #f0b849; font-weight: bold; }
                .esc-status-error { color: #d63638; font-weight: bold; }
                @media (max-width: 1200px) {
                    .esc-support-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
            
            <!-- Pro Features im Support Tab -->
            <h3 style="margin-top: 30px; padding-bottom: 10px; border-bottom: 2px solid #2271b1;"><?php esc_html_e( '💼 Erweiterte Tools', 'easy-status-check' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Import/Export', 'easy-status-check' ); ?>
                    </th>
                    <td>
                        <button type="button" class="button" disabled>
                            <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                            <?php esc_html_e( 'Services exportieren', 'easy-status-check' ); ?>
                        </button>
                        <button type="button" class="button" disabled style="margin-left: 10px;">
                            <span class="dashicons dashicons-upload" style="margin-top: 3px;"></span>
                            <?php esc_html_e( 'Services importieren', 'easy-status-check' ); ?>
                        </button>
                        <button type="button" class="button" disabled style="margin-left: 10px;">
                            <span class="dashicons dashicons-backup" style="margin-top: 3px;"></span>
                            <?php esc_html_e( 'History exportieren', 'easy-status-check' ); ?>
                        </button>
                        <p class="description" style="color: #666; margin-top: 10px;">
                            <?php esc_html_e( 'Exportieren und importieren Sie Services und History-Daten. Einzeln nach Service oder gesamt. Nur in Pro-Version.', 'easy-status-check' ); ?>
                            <a href="?page=easy-status-check-settings&tab=pro" style="color: #667eea; font-weight: 600;"><?php esc_html_e( 'Jetzt upgraden', 'easy-status-check' ); ?></a>
                            <?php echo esc_pro_label(); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Erweiterte Templates', 'easy-status-check' ); ?>
                    </th>
                    <td>
                        <p style="margin: 0 0 10px 0; color: #1d2327;">
                            <strong><?php esc_html_e( 'Zugriff auf 350+ vorkonfigurierte Service-Templates:', 'easy-status-check' ); ?></strong>
                        </p>
                        <ul style="margin: 0; padding-left: 20px; color: #666;">
                            <li><?php esc_html_e( 'Erweiterte Cloud-Provider (AWS, Azure, GCP, Oracle Cloud)', 'easy-status-check' ); ?></li>
                            <li><?php esc_html_e( 'Internationale Hosting-Anbieter (50+ Länder)', 'easy-status-check' ); ?></li>
                            <li><?php esc_html_e( 'SaaS-Dienste (CRM, Marketing, Analytics)', 'easy-status-check' ); ?></li>
                            <li><?php esc_html_e( 'Development-Tools (GitHub, GitLab, Bitbucket, CI/CD)', 'easy-status-check' ); ?></li>
                            <li><?php esc_html_e( 'E-Commerce-Plattformen (Shopify, WooCommerce, Magento)', 'easy-status-check' ); ?></li>
                        </ul>
                        <p class="description" style="color: #666; margin-top: 10px;">
                            <?php esc_html_e( 'Professionelle Templates mit optimierten Prüfintervallen. Nur in Pro-Version.', 'easy-status-check' ); ?>
                            <a href="?page=easy-status-check-settings&tab=pro" style="color: #667eea; font-weight: 600;"><?php esc_html_e( 'Jetzt upgraden', 'easy-status-check' ); ?></a>
                            <?php echo esc_pro_label(); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <div class="esc-support-grid">
                <!-- SYSTEM CARD -->
                <div class="esc-support-card">
                    <h3><?php esc_html_e( 'System-Status', 'easy-status-check' ); ?></h3>
                    
                    <div class="esc-status-item">
                        <strong>WordPress:</strong> <?php echo get_bloginfo( 'version' ); ?>
                    </div>
                    <div class="esc-status-item">
                        <strong>PHP:</strong> <?php echo PHP_VERSION; ?>
                    </div>
                    <div class="esc-status-item">
                        <strong>MySQL:</strong> <?php global $wpdb; echo $wpdb->db_version(); ?>
                    </div>
                    <div class="esc-status-item">
                        <strong>cURL:</strong> 
                        <?php if ( function_exists( 'curl_version' ) ) : ?>
                            <span class="esc-status-ok">✓ Aktiv</span>
                        <?php else : ?>
                            <span class="esc-status-error">✗ Nicht verfügbar</span>
                        <?php endif; ?>
                    </div>
                    <div class="esc-status-item">
                        <strong>Plugin Version:</strong> 1.0.0
                    </div>
                </div>
            
                <!-- DATENBANK CARD -->
                <div class="esc-support-card">
                    <h3><?php esc_html_e( 'Datenbank-Status', 'easy-status-check' ); ?></h3>
                    
                    <?php
                    $tables = array(
                        'esc_services' => __( 'Services', 'easy-status-check' ),
                        'esc_status_logs' => __( 'Status-Logs', 'easy-status-check' ),
                        'esc_incidents' => __( 'Incidents', 'easy-status-check' ),
                        'esc_notifications' => __( 'Benachrichtigungen', 'easy-status-check' )
                    );
                    $all_exist = true;
                    $total_entries = 0;
                    foreach ( $tables as $table => $label ) :
                        $table_name = $wpdb->prefix . $table;
                        $exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;
                        $count = $exists ? $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ) : 0;
                        $total_entries += $count;
                        if ( ! $exists ) $all_exist = false;
                    ?>
                        <div class="esc-status-item">
                            <strong><?php echo esc_html( $label ); ?>:</strong>
                            <?php if ( $exists ) : ?>
                                <span class="esc-status-ok">✓</span> <?php echo number_format_i18n( $count ); ?> Einträge
                            <?php else : ?>
                                <span class="esc-status-error">✗ Fehlt</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f1;">
                        <strong><?php esc_html_e( 'Aktionen:', 'easy-status-check' ); ?></strong><br>
                        <button type="button" class="button" id="esc-db-check">
                            <span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Prüfen', 'easy-status-check' ); ?>
                        </button>
                        <button type="button" class="button" id="esc-db-create">
                            <span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Erstellen', 'easy-status-check' ); ?>
                        </button>
                        <button type="button" class="button" id="esc-db-optimize">
                            <span class="dashicons dashicons-performance"></span> <?php esc_html_e( 'Optimieren', 'easy-status-check' ); ?>
                        </button>
                        <button type="button" class="button" id="esc-db-repair">
                            <span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Reparieren', 'easy-status-check' ); ?>
                        </button>
                    </div>
                    <div id="esc-db-result" style="margin-top: 10px;"></div>
                </div>
            
                <!-- CRON CARD -->
                <div class="esc-support-card">
                    <h3><?php esc_html_e( 'Cron-Status', 'easy-status-check' ); ?></h3>
                    
                    <div class="esc-status-item">
                        <strong>WordPress Cron:</strong>
                        <?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) : ?>
                            <span class="esc-status-warning">⚠ Deaktiviert</span>
                        <?php else : ?>
                            <span class="esc-status-ok">✓ Aktiv</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php
                    $cron_jobs = _get_cron_array();
                    $esc_crons = array();
                    foreach ( $cron_jobs as $timestamp => $cron ) {
                        foreach ( $cron as $hook => $details ) {
                            if ( strpos( $hook, 'esc_' ) === 0 ) {
                                $esc_crons[] = array(
                                    'hook' => $hook,
                                    'timestamp' => $timestamp,
                                    'next_run' => human_time_diff( $timestamp )
                                );
                            }
                        }
                    }
                    ?>
                    
                    <div class="esc-status-item">
                        <strong>Geplante Jobs:</strong> <?php echo count( $esc_crons ); ?>
                    </div>
                    
                    <?php if ( ! empty( $esc_crons ) ) : ?>
                        <?php foreach ( array_slice( $esc_crons, 0, 3 ) as $cron ) : ?>
                            <div class="esc-status-item">
                                <code style="font-size: 11px;"><?php echo esc_html( $cron['hook'] ); ?></code><br>
                                <small>in <?php echo esc_html( $cron['next_run'] ); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f1;">
                        <strong><?php esc_html_e( 'Aktionen:', 'easy-status-check' ); ?></strong><br>
                        <button type="button" class="button" id="esc-cron-check">
                            <span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Prüfen', 'easy-status-check' ); ?>
                        </button>
                        <button type="button" class="button" id="esc-cron-run">
                            <span class="dashicons dashicons-controls-play"></span> <?php esc_html_e( 'Manuell ausführen', 'easy-status-check' ); ?>
                        </button>
                    </div>
                    <div id="esc-cron-result" style="margin-top: 10px;"></div>
                </div>
            </div>
            
        <?php endif; ?>
        
        <?php if ( $active_tab !== 'support' ) : ?>
            <?php submit_button( __( 'Einstellungen speichern', 'easy-status-check' ) ); ?>
        <?php else : ?>
            <!-- JavaScript für Support-Tab Buttons -->
            <script>
            jQuery(document).ready(function($) {
                // Datenbank Prüfen
                $('#esc-db-check').on('click', function() {
                    var btn = $(this);
                    btn.prop('disabled', true);
                    $('#esc-db-result').html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.post(ajaxurl, {
                        action: 'esc_db_check',
                        nonce: '<?php echo wp_create_nonce( 'esc_db_action' ); ?>'
                    }, function(response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            $('#esc-db-result').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#esc-db-result').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                        setTimeout(function() { location.reload(); }, 2000);
                    });
                });
                
                // Datenbank Erstellen
                $('#esc-db-create').on('click', function() {
                    if (!confirm('<?php esc_html_e( 'Möchten Sie fehlende Datenbank-Tabellen erstellen?', 'easy-status-check' ); ?>')) return;
                    
                    var btn = $(this);
                    btn.prop('disabled', true);
                    $('#esc-db-result').html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.post(ajaxurl, {
                        action: 'esc_db_create',
                        nonce: '<?php echo wp_create_nonce( 'esc_db_action' ); ?>'
                    }, function(response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            $('#esc-db-result').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#esc-db-result').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                        setTimeout(function() { location.reload(); }, 2000);
                    });
                });
                
                // Datenbank Optimieren
                $('#esc-db-optimize').on('click', function() {
                    var btn = $(this);
                    btn.prop('disabled', true);
                    $('#esc-db-result').html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.post(ajaxurl, {
                        action: 'esc_db_optimize',
                        nonce: '<?php echo wp_create_nonce( 'esc_db_action' ); ?>'
                    }, function(response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            $('#esc-db-result').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#esc-db-result').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    });
                });
                
                // Datenbank Reparieren
                $('#esc-db-repair').on('click', function() {
                    if (!confirm('<?php esc_html_e( 'Möchten Sie die Datenbank-Tabellen reparieren?', 'easy-status-check' ); ?>')) return;
                    
                    var btn = $(this);
                    btn.prop('disabled', true);
                    $('#esc-db-result').html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.post(ajaxurl, {
                        action: 'esc_db_repair',
                        nonce: '<?php echo wp_create_nonce( 'esc_db_action' ); ?>'
                    }, function(response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            $('#esc-db-result').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#esc-db-result').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    });
                });
                
                // Cron Prüfen
                $('#esc-cron-check').on('click', function() {
                    var btn = $(this);
                    btn.prop('disabled', true);
                    $('#esc-cron-result').html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.post(ajaxurl, {
                        action: 'esc_cron_check',
                        nonce: '<?php echo wp_create_nonce( 'esc_cron_action' ); ?>'
                    }, function(response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            $('#esc-cron-result').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#esc-cron-result').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                        setTimeout(function() { location.reload(); }, 2000);
                    });
                });
                
                // Cron Manuell ausführen
                $('#esc-cron-run').on('click', function() {
                    if (!confirm('<?php esc_html_e( 'Möchten Sie alle Service-Checks jetzt manuell ausführen?', 'easy-status-check' ); ?>')) return;
                    
                    var btn = $(this);
                    btn.prop('disabled', true);
                    $('#esc-cron-result').html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.post(ajaxurl, {
                        action: 'esc_cron_run',
                        nonce: '<?php echo wp_create_nonce( 'esc_cron_action' ); ?>'
                    }, function(response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            $('#esc-cron-result').html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $('#esc-cron-result').html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    });
                });
            });
            </script>
        <?php endif; ?>
    </form>
</div>
