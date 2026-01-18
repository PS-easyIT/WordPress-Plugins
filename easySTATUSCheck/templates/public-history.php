<?php
/**
 * Template for Public History Page
 * 
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$service_id = get_query_var( 'service_id' );

if ( ! $service_id ) {
    wp_die( __( 'Service nicht gefunden.', 'easy-status-check' ) );
}

$services_table = $wpdb->prefix . 'esc_services';
$logs_table = $wpdb->prefix . 'esc_status_logs';

$service = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $services_table WHERE id = %d", $service_id ) );

if ( ! $service ) {
    wp_die( __( 'Service nicht gefunden.', 'easy-status-check' ) );
}

// Get last 100 logs
$logs = $wpdb->get_results( $wpdb->prepare( "
    SELECT * FROM $logs_table 
    WHERE service_id = %d 
    ORDER BY checked_at DESC 
    LIMIT 100
", $service_id ) );

$base_slug = get_option( 'esc_public_status_slug', 'status' );

$public_settings = get_option( 'esc_public_settings', array(
    'primary_color' => '#2271b1',
    'success_color' => '#00a32a',
    'warning_color' => '#f0b849',
    'error_color' => '#d63638',
    'background_color' => '#f0f0f1',
    'text_color' => '#1d2327'
) );

get_header();
?>

<style>
    .esc-public-history {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .esc-back-link {
        display: inline-block;
        margin-bottom: 20px;
        color: <?php echo esc_attr( $public_settings['primary_color'] ); ?>;
        text-decoration: none;
        font-weight: 500;
    }
    
    .esc-back-link:hover {
        text-decoration: underline;
    }
    
    .esc-public-header h1 {
        font-size: 32px;
        margin-bottom: 10px;
        color: <?php echo esc_attr( $public_settings['text_color'] ); ?>;
    }
    
    .esc-public-header p {
        color: #666;
        margin-bottom: 30px;
    }
    
    .esc-history-table {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .esc-history-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .esc-history-table th {
        background: #f5f5f5;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #ddd;
        color: <?php echo esc_attr( $public_settings['text_color'] ); ?>;
    }
    
    .esc-history-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
    }
    
    .esc-history-table tr:last-child td {
        border-bottom: none;
    }
    
    .esc-status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .esc-status-badge.online {
        background: <?php echo esc_attr( $public_settings['success_color'] ); ?>22;
        color: <?php echo esc_attr( $public_settings['success_color'] ); ?>;
    }
    
    .esc-status-badge.offline {
        background: <?php echo esc_attr( $public_settings['error_color'] ); ?>22;
        color: <?php echo esc_attr( $public_settings['error_color'] ); ?>;
    }
    
    .esc-status-badge.warning {
        background: <?php echo esc_attr( $public_settings['warning_color'] ); ?>22;
        color: <?php echo esc_attr( $public_settings['warning_color'] ); ?>;
    }
    
    @media (max-width: 768px) {
        .esc-history-table {
            overflow-x: auto;
        }
        
        .esc-history-table table {
            min-width: 600px;
        }
    }
</style>

<div class="esc-public-history">
    <a href="<?php echo home_url( '/' . $base_slug . '/services' ); ?>" class="esc-back-link">← <?php _e( 'Zurück zu Services', 'easy-status-check' ); ?></a>
    
    <div class="esc-public-header">
        <h1><?php echo esc_html( $service->name ); ?> - History</h1>
        <p><?php echo esc_html( $service->url ); ?></p>
    </div>

    <div class="esc-history-table">
        <table>
            <thead>
                <tr>
                    <th><?php _e( 'Zeitpunkt', 'easy-status-check' ); ?></th>
                    <th><?php _e( 'Status', 'easy-status-check' ); ?></th>
                    <th><?php _e( 'HTTP-Code', 'easy-status-check' ); ?></th>
                    <th><?php _e( 'Antwortzeit', 'easy-status-check' ); ?></th>
                    <th><?php _e( 'Fehler', 'easy-status-check' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $logs ) ) : ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">
                            <?php _e( 'Keine History-Daten verfügbar.', 'easy-status-check' ); ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $logs as $log ) : ?>
                        <tr>
                            <td><?php echo esc_html( date_i18n( 'd.m.Y H:i:s', strtotime( $log->checked_at ) ) ); ?></td>
                            <td><span class="esc-status-badge <?php echo esc_attr( $log->status ); ?>"><?php echo esc_html( ucfirst( $log->status ) ); ?></span></td>
                            <td><?php echo esc_html( $log->http_code ?? '-' ); ?></td>
                            <td><?php echo $log->response_time ? round( $log->response_time ) . 'ms' : '-'; ?></td>
                            <td><?php echo esc_html( $log->error_message ?? '-' ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
get_footer();
