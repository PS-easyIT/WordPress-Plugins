<?php
/**
 * Template for Public Services Status Page
 * 
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$services_table = $wpdb->prefix . 'esc_services';
$logs_table = $wpdb->prefix . 'esc_status_logs';

// Get public settings
$public_settings = get_option( 'esc_public_settings', array(
    'primary_color' => '#2271b1',
    'success_color' => '#00a32a',
    'warning_color' => '#f0b849',
    'error_color' => '#d63638',
    'background_color' => '#f0f0f1',
    'text_color' => '#1d2327',
    'show_response_time' => true,
    'show_uptime' => true,
    'columns' => 3
) );

$columns = get_option( 'esc_public_services_columns', 3 );

// Get services with latest status
$services = $wpdb->get_results( "
    SELECT 
        s.*,
        l.status as current_status,
        l.response_time,
        l.http_code,
        l.checked_at as last_checked
    FROM $services_table s
    LEFT JOIN $logs_table l ON s.id = l.service_id
    LEFT JOIN (
        SELECT service_id, MAX(checked_at) as max_checked
        FROM $logs_table
        GROUP BY service_id
    ) latest ON s.id = latest.service_id AND l.checked_at = latest.max_checked
    WHERE s.enabled = 1
    ORDER BY s.category, s.name
" );

$base_slug = get_option( 'esc_public_status_slug', 'status' );

get_header();
?>

<style>
    .esc-public-services {
        max-width: 1400px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .esc-public-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .esc-public-header h1 {
        font-size: 36px;
        margin-bottom: 10px;
        color: <?php echo esc_attr( $public_settings['text_color'] ); ?>;
    }
    
    .esc-public-nav {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-bottom: 40px;
        flex-wrap: wrap;
    }
    
    .esc-public-nav a {
        padding: 12px 24px;
        background: #fff;
        border-radius: 6px;
        text-decoration: none;
        color: <?php echo esc_attr( $public_settings['text_color'] ); ?>;
        border: 2px solid transparent;
        transition: all 0.3s;
        font-weight: 500;
    }
    
    .esc-public-nav a.active {
        border-color: <?php echo esc_attr( $public_settings['primary_color'] ); ?>;
        background: <?php echo esc_attr( $public_settings['primary_color'] ); ?>;
        color: #fff;
    }
    
    .esc-public-nav a:hover:not(.active) {
        border-color: <?php echo esc_attr( $public_settings['primary_color'] ); ?>;
    }
    
    .esc-services-grid {
        display: grid;
        grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr);
        gap: 20px;
    }
    
    .esc-service-card {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-left: 5px solid #ddd;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .esc-service-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .esc-service-card.online { border-left-color: <?php echo esc_attr( $public_settings['success_color'] ); ?>; }
    .esc-service-card.offline { border-left-color: <?php echo esc_attr( $public_settings['error_color'] ); ?>; }
    .esc-service-card.warning { border-left-color: <?php echo esc_attr( $public_settings['warning_color'] ); ?>; }
    
    .esc-service-card h3 {
        font-size: 20px;
        margin-bottom: 15px;
        color: <?php echo esc_attr( $public_settings['text_color'] ); ?>;
    }
    
    .esc-service-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 15px;
    }
    
    .esc-service-status.online {
        background: <?php echo esc_attr( $public_settings['success_color'] ); ?>22;
        color: <?php echo esc_attr( $public_settings['success_color'] ); ?>;
    }
    
    .esc-service-status.offline {
        background: <?php echo esc_attr( $public_settings['error_color'] ); ?>22;
        color: <?php echo esc_attr( $public_settings['error_color'] ); ?>;
    }
    
    .esc-service-status.warning {
        background: <?php echo esc_attr( $public_settings['warning_color'] ); ?>22;
        color: <?php echo esc_attr( $public_settings['warning_color'] ); ?>;
    }
    
    .esc-service-status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: currentColor;
    }
    
    .esc-service-meta {
        display: flex;
        gap: 20px;
        font-size: 14px;
        color: #666;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    
    .esc-service-meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .esc-service-url {
        font-size: 13px;
        color: #999;
        margin-bottom: 15px;
        word-break: break-all;
    }
    
    .esc-service-actions a {
        padding: 8px 16px;
        background: <?php echo esc_attr( $public_settings['primary_color'] ); ?>;
        color: #fff;
        text-decoration: none;
        border-radius: 4px;
        font-size: 14px;
        transition: opacity 0.2s;
        display: inline-block;
    }
    
    .esc-service-actions a:hover {
        opacity: 0.8;
    }
    
    @media (max-width: 1200px) {
        .esc-services-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .esc-services-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="esc-public-services">
    <div class="esc-public-header">
        <h1><?php echo esc_html( get_option( 'esc_public_status_title', __( 'Service Status', 'easy-status-check' ) ) ); ?></h1>
        <p><?php echo esc_html( get_option( 'esc_public_status_description', __( 'Aktuelle Status-Informationen unserer Services', 'easy-status-check' ) ) ); ?></p>
    </div>

    <div class="esc-public-nav">
        <a href="<?php echo home_url( '/' . $base_slug . '/services' ); ?>" class="active"><?php _e( 'Services', 'easy-status-check' ); ?></a>
        <a href="<?php echo home_url( '/' . $base_slug . '/incidents' ); ?>"><?php _e( 'Incidents', 'easy-status-check' ); ?></a>
    </div>

    <div class="esc-services-grid">
        <?php foreach ( $services as $service ) : 
            $status = $service->current_status ?? 'unknown';
            $status_text = array(
                'online' => __( 'Online', 'easy-status-check' ),
                'offline' => __( 'Offline', 'easy-status-check' ),
                'warning' => __( 'Warnung', 'easy-status-check' ),
                'unknown' => __( 'Unbekannt', 'easy-status-check' )
            );
        ?>
            <div class="esc-service-card <?php echo esc_attr( $status ); ?>">
                <h3><?php echo esc_html( $service->name ); ?></h3>
                
                <div class="esc-service-status <?php echo esc_attr( $status ); ?>">
                    <span class="esc-service-status-dot"></span>
                    <?php echo esc_html( $status_text[$status] ); ?>
                </div>
                
                <?php if ( $public_settings['show_response_time'] || $public_settings['show_uptime'] ) : ?>
                    <div class="esc-service-meta">
                        <?php if ( $public_settings['show_response_time'] && $service->response_time ) : ?>
                            <div class="esc-service-meta-item">
                                <span>⚡</span>
                                <span><?php echo round( $service->response_time ); ?>ms</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( $service->last_checked ) : ?>
                            <div class="esc-service-meta-item">
                                <span>🕐</span>
                                <span><?php echo human_time_diff( strtotime( $service->last_checked ), current_time( 'timestamp' ) ); ?> <?php _e( 'her', 'easy-status-check' ); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="esc-service-url"><?php echo esc_html( $service->url ); ?></div>
                
                <div class="esc-service-actions">
                    <a href="<?php echo home_url( '/' . $base_slug . '/history/' . $service->id ); ?>"><?php _e( 'History anzeigen', 'easy-status-check' ); ?></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
get_footer();
