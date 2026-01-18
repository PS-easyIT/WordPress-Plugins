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
    'text_color' => '#1d2327',
    'page_title' => 'Service Status',
    'page_description' => 'Aktuelle Status-Informationen unserer Services'
) );

get_header();
?>

<style>
    :root {
        --esc-primary: <?php echo esc_attr( $public_settings['primary_color'] ); ?>;
        --esc-success: <?php echo esc_attr( $public_settings['success_color'] ); ?>;
        --esc-warning: <?php echo esc_attr( $public_settings['warning_color'] ); ?>;
        --esc-error: <?php echo esc_attr( $public_settings['error_color'] ); ?>;
        --esc-bg: <?php echo esc_attr( $public_settings['background_color'] ); ?>;
        --esc-text: <?php echo esc_attr( $public_settings['text_color'] ); ?>;
        --esc-border-radius: 12px;
        --esc-shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
        --esc-shadow-md: 0 4px 12px rgba(0,0,0,0.1);
        --esc-shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
    }
    
    body.esc-public-page {
        background: var(--esc-bg);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }
    
    .esc-public-history {
        max-width: 1400px;
        margin: 0 auto;
        padding: 60px 24px;
    }
    
    .esc-back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 32px;
        color: var(--esc-primary);
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        padding: 10px 20px;
        background: #fff;
        border-radius: var(--esc-border-radius);
        border: 2px solid #e5e7eb;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: var(--esc-shadow-sm);
    }
    
    .esc-back-link:hover {
        border-color: var(--esc-primary);
        transform: translateX(-4px);
        box-shadow: var(--esc-shadow-md);
    }
    
    .esc-public-header {
        margin-bottom: 40px;
        animation: fadeInDown 0.6s ease-out;
        background: linear-gradient(135deg, #fff 0%, #f9fafb 100%);
        padding: 32px;
        border-radius: var(--esc-border-radius);
        box-shadow: var(--esc-shadow-md);
        border: 1px solid #e5e7eb;
        position: relative;
        overflow: hidden;
    }
    
    .esc-public-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--esc-primary), var(--esc-success));
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .esc-public-header h1 {
        font-size: clamp(24px, 4vw, 36px);
        font-weight: 700;
        margin-bottom: 12px;
        color: var(--esc-text);
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    
    .esc-public-header p {
        color: #6b7280;
        margin: 0;
        font-size: 14px;
        font-family: 'Courier New', monospace;
        background: #f3f4f6;
        padding: 8px 12px;
        border-radius: 6px;
        display: inline-block;
    }
    
    .esc-history-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 40px;
        animation: fadeIn 0.6s ease-out 0.2s both;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .esc-stat-card {
        background: #fff;
        padding: 24px;
        border-radius: var(--esc-border-radius);
        box-shadow: var(--esc-shadow-sm);
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    
    .esc-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--esc-shadow-md);
    }
    
    .esc-stat-label {
        font-size: 13px;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }
    
    .esc-stat-value {
        font-size: 32px;
        font-weight: 700;
        color: var(--esc-text);
        line-height: 1;
    }
    
    .esc-history-table {
        background: #fff;
        border-radius: var(--esc-border-radius);
        overflow: hidden;
        box-shadow: var(--esc-shadow-sm);
        border: 1px solid #e5e7eb;
        animation: fadeIn 0.6s ease-out 0.4s both;
    }
    
    .esc-history-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .esc-history-table th {
        background: linear-gradient(to bottom, #f9fafb, #f3f4f6);
        padding: 16px 20px;
        text-align: left;
        font-weight: 700;
        border-bottom: 2px solid #e5e7eb;
        color: var(--esc-text);
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .esc-history-table td {
        padding: 16px 20px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
        color: #374151;
    }
    
    .esc-history-table tbody tr {
        transition: background-color 0.2s ease;
    }
    
    .esc-history-table tbody tr:hover {
        background-color: #f9fafb;
    }
    
    .esc-history-table tr:last-child td {
        border-bottom: none;
    }
    
    .esc-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .esc-status-badge::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }
    
    .esc-status-badge.online {
        background: linear-gradient(135deg, var(--esc-success)15, var(--esc-success)25);
        color: var(--esc-success);
    }
    
    .esc-status-badge.offline {
        background: linear-gradient(135deg, var(--esc-error)15, var(--esc-error)25);
        color: var(--esc-error);
    }
    
    .esc-status-badge.warning {
        background: linear-gradient(135deg, var(--esc-warning)15, var(--esc-warning)25);
        color: var(--esc-warning);
    }
    
    .esc-no-data {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
        font-size: 16px;
    }
    
    @media (max-width: 1200px) {
        .esc-history-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .esc-public-history {
            padding: 40px 16px;
        }
        
        .esc-public-header h1 {
            font-size: 28px;
        }
        
        .esc-history-stats {
            grid-template-columns: 1fr;
        }
        
        .esc-history-table {
            overflow-x: auto;
        }
        
        .esc-history-table table {
            min-width: 600px;
        }
        
        .esc-history-table th,
        .esc-history-table td {
            padding: 12px 16px;
            font-size: 13px;
        }
    }
</style>

<div class="esc-public-history">
    <a href="<?php echo home_url( '/' . $base_slug . '/services' ); ?>" class="esc-back-link">← <?php _e( 'Zurück zu Services', 'easy-status-check' ); ?></a>
    
    <div class="esc-public-header">
        <h1><?php echo esc_html( $service->name ); ?> - History</h1>
        <p><?php echo esc_html( $service->url ); ?></p>
    </div>

    <?php if ( ! empty( $logs ) ) :
        $total_checks = count( $logs );
        $online_checks = count( array_filter( $logs, function($log) { return $log->status === 'online'; } ) );
        $uptime_percentage = $total_checks > 0 ? round( ( $online_checks / $total_checks ) * 100, 2 ) : 0;
        $avg_response_time = 0;
        $response_times = array_filter( array_map( function($log) { return $log->response_time; }, $logs ) );
        if ( ! empty( $response_times ) ) {
            $avg_response_time = round( array_sum( $response_times ) / count( $response_times ) );
        }
    ?>
        <div class="esc-history-stats">
            <div class="esc-stat-card">
                <div class="esc-stat-label"><?php _e( 'Uptime', 'easy-status-check' ); ?></div>
                <div class="esc-stat-value" style="color: var(--esc-success);"><?php echo $uptime_percentage; ?>%</div>
            </div>
            <div class="esc-stat-card">
                <div class="esc-stat-label"><?php _e( 'Durchschn. Antwortzeit', 'easy-status-check' ); ?></div>
                <div class="esc-stat-value" style="color: var(--esc-primary);"><?php echo $avg_response_time; ?>ms</div>
            </div>
            <div class="esc-stat-card">
                <div class="esc-stat-label"><?php _e( 'Anzahl Checks', 'easy-status-check' ); ?></div>
                <div class="esc-stat-value"><?php echo $total_checks; ?></div>
            </div>
            <div class="esc-stat-card">
                <div class="esc-stat-label"><?php _e( 'Online Checks', 'easy-status-check' ); ?></div>
                <div class="esc-stat-value" style="color: var(--esc-success);"><?php echo $online_checks; ?></div>
            </div>
        </div>
    <?php endif; ?>

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
                        <td colspan="5" class="esc-no-data">
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

<!-- Copyright Box -->
<div style="background: linear-gradient(135deg, #f9fafb 0%, #fff 100%); border-top: 1px solid #e5e7eb; padding: 20px; text-align: center; margin-top: 60px;">
    <p style="margin: 0; color: #6b7280; font-size: 14px;">
        Powered by <strong style="color: #2271b1;">mySTATUShub</strong> © <?php echo date('Y'); ?> 
        <a href="https://phinit.de" target="_blank" rel="noopener noreferrer" style="color: #2271b1; text-decoration: none; font-weight: 600;">PHiNiT.de</a>
    </p>
</div>

<?php
get_footer();
