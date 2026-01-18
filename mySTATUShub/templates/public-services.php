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
    'columns' => 3,
    'page_title' => 'Service Status',
    'page_description' => 'Aktuelle Status-Informationen unserer Services'
) );

$columns = isset( $public_settings['columns'] ) ? intval( $public_settings['columns'] ) : 3;

// Get services with latest status
$services = $wpdb->get_results( "
    SELECT DISTINCT
        s.id,
        s.name,
        s.url,
        s.category,
        s.enabled,
        l.status as current_status,
        l.response_time,
        l.http_code,
        l.checked_at as last_checked
    FROM $services_table s
    LEFT JOIN (
        SELECT 
            l1.service_id,
            l1.status,
            l1.response_time,
            l1.http_code,
            l1.checked_at
        FROM $logs_table l1
        INNER JOIN (
            SELECT service_id, MAX(checked_at) as max_checked
            FROM $logs_table
            GROUP BY service_id
        ) l2 ON l1.service_id = l2.service_id AND l1.checked_at = l2.max_checked
    ) l ON s.id = l.service_id
    WHERE s.enabled = 1
    GROUP BY s.id
    ORDER BY s.category, s.name
" );

$base_slug = get_option( 'esc_public_status_slug', 'status' );

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
    
    .esc-public-services {
        max-width: 1400px;
        margin: 0 auto;
        padding: 60px 24px;
    }
    
    .esc-public-header {
        text-align: center;
        margin-bottom: 48px;
        animation: fadeInDown 0.6s ease-out;
        background: linear-gradient(135deg, #fff 0%, #f9fafb 100%);
        padding: 40px 32px;
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
        font-size: clamp(28px, 4vw, 40px);
        font-weight: 700;
        margin-bottom: 12px;
        color: var(--esc-text);
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    
    .esc-public-header p {
        font-size: 16px;
        color: #6b7280;
        margin: 0;
        line-height: 1.6;
    }
    
    .esc-public-nav {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-bottom: 48px;
        flex-wrap: wrap;
        animation: fadeIn 0.6s ease-out 0.2s both;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .esc-public-nav a {
        padding: 14px 28px;
        background: #fff;
        border-radius: var(--esc-border-radius);
        text-decoration: none;
        color: var(--esc-text);
        border: 2px solid #e5e7eb;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 600;
        font-size: 15px;
        box-shadow: var(--esc-shadow-sm);
    }
    
    .esc-public-nav a.active {
        border-color: var(--esc-primary);
        background: var(--esc-primary);
        color: #fff;
        box-shadow: var(--esc-shadow-md);
        transform: translateY(-1px);
    }
    
    .esc-public-nav a:hover:not(.active) {
        border-color: var(--esc-primary);
        transform: translateY(-2px);
        box-shadow: var(--esc-shadow-md);
    }
    
    .esc-status-overview {
        background: linear-gradient(135deg, #fff 0%, #f9fafb 100%);
        padding: 24px 32px;
        border-radius: var(--esc-border-radius);
        box-shadow: var(--esc-shadow-sm);
        border: 1px solid #e5e7eb;
        margin-bottom: 32px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        animation: fadeIn 0.6s ease-out 0.3s both;
    }
    
    .esc-status-stat {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    
    .esc-status-stat:hover {
        transform: translateY(-2px);
        box-shadow: var(--esc-shadow-md);
    }
    
    .esc-status-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }
    
    .esc-status-icon.online {
        background: linear-gradient(135deg, var(--esc-success)15, var(--esc-success)25);
        color: var(--esc-success);
    }
    
    .esc-status-icon.offline {
        background: linear-gradient(135deg, var(--esc-error)15, var(--esc-error)25);
        color: var(--esc-error);
    }
    
    .esc-status-icon.warning {
        background: linear-gradient(135deg, var(--esc-warning)15, var(--esc-warning)25);
        color: var(--esc-warning);
    }
    
    .esc-status-icon.total {
        background: linear-gradient(135deg, var(--esc-primary)15, var(--esc-primary)25);
        color: var(--esc-primary);
    }
    
    .esc-status-info {
        flex: 1;
    }
    
    .esc-status-label {
        font-size: 13px;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }
    
    .esc-status-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--esc-text);
        line-height: 1;
    }
    
    .esc-services-grid {
        display: grid;
        grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr);
        gap: 24px;
        animation: fadeIn 0.6s ease-out 0.4s both;
    }
    
    .esc-service-card {
        background: #fff;
        padding: 28px;
        border-radius: var(--esc-border-radius);
        box-shadow: var(--esc-shadow-sm);
        border: 1px solid #e5e7eb;
        border-left: 4px solid #e5e7eb;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .esc-service-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: currentColor;
        transform: scaleY(0);
        transition: transform 0.3s ease;
    }
    
    .esc-service-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--esc-shadow-lg);
        border-color: currentColor;
    }
    
    .esc-service-card:hover::before {
        transform: scaleY(1);
    }
    
    .esc-service-card.online { color: var(--esc-success); border-left-color: var(--esc-success); }
    .esc-service-card.offline { color: var(--esc-error); border-left-color: var(--esc-error); }
    .esc-service-card.warning { color: var(--esc-warning); border-left-color: var(--esc-warning); }
    .esc-service-card.unknown { color: #9ca3af; border-left-color: #9ca3af; }
    
    .esc-service-card h3 {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 16px;
        color: var(--esc-text);
        line-height: 1.3;
        letter-spacing: -0.01em;
    }
    
    .esc-service-status {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .esc-service-status.online {
        background: linear-gradient(135deg, var(--esc-success)15, var(--esc-success)25);
        color: var(--esc-success);
        box-shadow: 0 2px 8px var(--esc-success)20;
    }
    
    .esc-service-status.offline {
        background: linear-gradient(135deg, var(--esc-error)15, var(--esc-error)25);
        color: var(--esc-error);
        box-shadow: 0 2px 8px var(--esc-error)20;
    }
    
    .esc-service-status.warning {
        background: linear-gradient(135deg, var(--esc-warning)15, var(--esc-warning)25);
        color: var(--esc-warning);
        box-shadow: 0 2px 8px var(--esc-warning)20;
    }
    
    .esc-service-status.unknown {
        background: #f3f4f6;
        color: #6b7280;
    }
    
    .esc-service-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
        animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.1); }
    }
    
    .esc-service-meta {
        display: flex;
        gap: 24px;
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .esc-service-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        background: #f9fafb;
        border-radius: 6px;
        font-weight: 500;
    }
    
    .esc-service-url {
        font-size: 13px;
        color: #9ca3af;
        margin-bottom: 20px;
        word-break: break-all;
        font-family: 'Courier New', monospace;
        background: #f9fafb;
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
    }
    
    .esc-service-actions a {
        padding: 10px 20px;
        background: var(--esc-primary);
        color: #fff;
        text-decoration: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: var(--esc-shadow-sm);
    }
    
    .esc-service-actions a:hover {
        background: color-mix(in srgb, var(--esc-primary) 85%, black);
        transform: translateY(-2px);
        box-shadow: var(--esc-shadow-md);
    }
    
    .esc-service-actions a::after {
        content: '→';
        transition: transform 0.3s;
    }
    
    .esc-service-actions a:hover::after {
        transform: translateX(4px);
    }
    
    @media (max-width: 1200px) {
        .esc-services-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .esc-public-services {
            padding: 40px 16px;
        }
        
        .esc-public-header h1 {
            font-size: 32px;
        }
        
        .esc-public-header p {
            font-size: 16px;
        }
        
        .esc-services-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        
        .esc-service-card {
            padding: 20px;
        }
        
        .esc-public-nav {
            gap: 8px;
        }
        
        .esc-public-nav a {
            padding: 12px 20px;
            font-size: 14px;
        }
    }
    
    .esc-no-services {
        text-align: center;
        padding: 80px 20px;
        background: #fff;
        border-radius: var(--esc-border-radius);
        box-shadow: var(--esc-shadow-sm);
    }
    
    .esc-no-services h2 {
        font-size: 24px;
        color: var(--esc-text);
        margin-bottom: 12px;
    }
    
    .esc-no-services p {
        color: #6b7280;
        font-size: 16px;
    }
</style>

<div class="esc-public-services">
    <div class="esc-public-header">
        <h1><?php echo esc_html( isset( $public_settings['page_title'] ) ? $public_settings['page_title'] : 'Service Status' ); ?></h1>
        <p><?php echo esc_html( isset( $public_settings['page_description'] ) ? $public_settings['page_description'] : 'Aktuelle Status-Informationen unserer Services' ); ?></p>
    </div>

    <?php
    // Calculate statistics
    $total_services = count( $services );
    $online_count = 0;
    $offline_count = 0;
    $warning_count = 0;
    
    foreach ( $services as $service ) {
        $status = $service->current_status ?? 'unknown';
        if ( $status === 'online' ) {
            $online_count++;
        } elseif ( $status === 'offline' ) {
            $offline_count++;
        } elseif ( $status === 'warning' ) {
            $warning_count++;
        }
    }
    ?>

    <div class="esc-status-overview">
        <div class="esc-status-stat">
            <div class="esc-status-icon total">
                <span>📊</span>
            </div>
            <div class="esc-status-info">
                <div class="esc-status-label"><?php _e( 'Gesamt', 'easy-status-check' ); ?></div>
                <div class="esc-status-value"><?php echo $total_services; ?></div>
            </div>
        </div>
        
        <div class="esc-status-stat">
            <div class="esc-status-icon online">
                <span>✓</span>
            </div>
            <div class="esc-status-info">
                <div class="esc-status-label"><?php _e( 'Online', 'easy-status-check' ); ?></div>
                <div class="esc-status-value"><?php echo $online_count; ?></div>
            </div>
        </div>
        
        <?php if ( $warning_count > 0 ) : ?>
        <div class="esc-status-stat">
            <div class="esc-status-icon warning">
                <span>⚠</span>
            </div>
            <div class="esc-status-info">
                <div class="esc-status-label"><?php _e( 'Warnung', 'easy-status-check' ); ?></div>
                <div class="esc-status-value"><?php echo $warning_count; ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ( $offline_count > 0 ) : ?>
        <div class="esc-status-stat">
            <div class="esc-status-icon offline">
                <span>✕</span>
            </div>
            <div class="esc-status-info">
                <div class="esc-status-label"><?php _e( 'Offline', 'easy-status-check' ); ?></div>
                <div class="esc-status-value"><?php echo $offline_count; ?></div>
            </div>
        </div>
        <?php endif; ?>
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

<!-- Copyright Box -->
<div style="background: linear-gradient(135deg, #f9fafb 0%, #fff 100%); border-top: 1px solid #e5e7eb; padding: 20px; text-align: center; margin-top: 60px;">
    <p style="margin: 0; color: #6b7280; font-size: 14px;">
        Powered by <strong style="color: #2271b1;">mySTATUShub</strong> © <?php echo date('Y'); ?> 
        <a href="https://phinit.de" target="_blank" rel="noopener noreferrer" style="color: #2271b1; text-decoration: none; font-weight: 600;">PHiNiT.de</a>
    </p>
</div>

<?php
get_footer();
