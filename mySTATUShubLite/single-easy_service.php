<?php
/**
 * Single Service Template
 *
 * @package Easy_Status_Check
 */

get_header();

global $wpdb;
$services_table = $wpdb->prefix . 'esc_services';
$logs_table = $wpdb->prefix . 'esc_status_logs';

// Get service by URL from post meta
$endpoint_url = get_post_meta( get_the_ID(), '_esc_endpoint_url', true );
$service = null;

if ( $endpoint_url ) {
    $service = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $services_table WHERE url = %s LIMIT 1",
        $endpoint_url
    ) );
}

if ( ! $service ) {
    ?>
    <div class="esc-single-container">
        <p><?php _e( 'Service nicht gefunden.', 'easy-status-check' ); ?></p>
    </div>
    <?php
    get_footer();
    return;
}

// Get latest status
$latest_log = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM $logs_table WHERE service_id = %d ORDER BY checked_at DESC LIMIT 1",
    $service->id
) );

// Get uptime statistics
if ( class_exists( 'ESC_Status_Checker' ) ) {
    $checker = new ESC_Status_Checker();
    $stats_24h = $checker->get_uptime_statistics( $service->id, '24h' );
    $stats_7d = $checker->get_uptime_statistics( $service->id, '7d' );
    $stats_30d = $checker->get_uptime_statistics( $service->id, '30d' );
}

// Get recent logs
$recent_logs = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM $logs_table WHERE service_id = %d ORDER BY checked_at DESC LIMIT 20",
    $service->id
) );

$status_class = 'esc-status-' . ( $latest_log->status ?? 'unknown' );
?>

<div class="esc-single-container">
    <article class="esc-service-detail">
        <header class="esc-service-detail-header <?php echo esc_attr( $status_class ); ?>">
            <h1 class="esc-service-detail-title"><?php the_title(); ?></h1>
            
            <?php if ( $latest_log ) : ?>
                <div class="esc-current-status">
                    <span class="esc-status-badge <?php echo esc_attr( $status_class ); ?>">
                        <?php
                        $status_labels = array(
                            'online' => __( 'Online', 'easy-status-check' ),
                            'offline' => __( 'Offline', 'easy-status-check' ),
                            'warning' => __( 'Warnung', 'easy-status-check' ),
                        );
                        echo esc_html( $status_labels[ $latest_log->status ] ?? __( 'Unbekannt', 'easy-status-check' ) );
                        ?>
                    </span>
                    <span class="esc-last-check">
                        <?php _e( 'Letzte Prüfung:', 'easy-status-check' ); ?>
                        <?php echo esc_html( human_time_diff( strtotime( $latest_log->checked_at ), current_time( 'timestamp' ) ) ); ?>
                        <?php _e( 'her', 'easy-status-check' ); ?>
                    </span>
                </div>
            <?php endif; ?>
        </header>

        <div class="esc-service-detail-body">
            <div class="esc-service-info-grid">
                <div class="esc-info-box">
                    <h3><?php _e( 'Service-Informationen', 'easy-status-check' ); ?></h3>
                    <dl>
                        <dt><?php _e( 'URL:', 'easy-status-check' ); ?></dt>
                        <dd><code><?php echo esc_html( $service->url ); ?></code></dd>
                        
                        <dt><?php _e( 'Kategorie:', 'easy-status-check' ); ?></dt>
                        <dd><?php echo esc_html( ucfirst( $service->category ) ); ?></dd>
                        
                        <dt><?php _e( 'Methode:', 'easy-status-check' ); ?></dt>
                        <dd><?php echo esc_html( $service->method ); ?></dd>
                        
                        <dt><?php _e( 'Timeout:', 'easy-status-check' ); ?></dt>
                        <dd><?php echo esc_html( $service->timeout ); ?> <?php _e( 'Sekunden', 'easy-status-check' ); ?></dd>
                        
                        <dt><?php _e( 'Erwarteter Code:', 'easy-status-check' ); ?></dt>
                        <dd><?php echo esc_html( $service->expected_code ); ?></dd>
                    </dl>
                </div>

                <?php if ( isset( $stats_24h ) ) : ?>
                    <div class="esc-info-box">
                        <h3><?php _e( 'Uptime-Statistiken', 'easy-status-check' ); ?></h3>
                        <div class="esc-uptime-stats">
                            <div class="esc-stat">
                                <span class="esc-stat-label"><?php _e( '24 Stunden', 'easy-status-check' ); ?></span>
                                <span class="esc-stat-value"><?php echo esc_html( $stats_24h->uptime_percentage ); ?>%</span>
                            </div>
                            <div class="esc-stat">
                                <span class="esc-stat-label"><?php _e( '7 Tage', 'easy-status-check' ); ?></span>
                                <span class="esc-stat-value"><?php echo esc_html( $stats_7d->uptime_percentage ); ?>%</span>
                            </div>
                            <div class="esc-stat">
                                <span class="esc-stat-label"><?php _e( '30 Tage', 'easy-status-check' ); ?></span>
                                <span class="esc-stat-value"><?php echo esc_html( $stats_30d->uptime_percentage ); ?>%</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( $latest_log ) : ?>
                    <div class="esc-info-box">
                        <h3><?php _e( 'Aktueller Status', 'easy-status-check' ); ?></h3>
                        <dl>
                            <dt><?php _e( 'HTTP-Code:', 'easy-status-check' ); ?></dt>
                            <dd><?php echo esc_html( $latest_log->http_code ?: '—' ); ?></dd>
                            
                            <dt><?php _e( 'Antwortzeit:', 'easy-status-check' ); ?></dt>
                            <dd><?php echo esc_html( $latest_log->response_time ); ?> ms</dd>
                            
                            <dt><?php _e( 'Geprüft am:', 'easy-status-check' ); ?></dt>
                            <dd><?php echo esc_html( $latest_log->checked_at ); ?></dd>
                            
                            <?php if ( $latest_log->error_message ) : ?>
                                <dt><?php _e( 'Fehler:', 'easy-status-check' ); ?></dt>
                                <dd class="esc-error"><?php echo esc_html( $latest_log->error_message ); ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ( ! empty( $recent_logs ) ) : ?>
                <div class="esc-recent-logs">
                    <h3><?php _e( 'Letzte Prüfungen', 'easy-status-check' ); ?></h3>
                    <table class="esc-logs-table">
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
                            <?php foreach ( $recent_logs as $log ) : ?>
                                <tr class="esc-status-<?php echo esc_attr( $log->status ); ?>">
                                    <td><?php echo esc_html( $log->checked_at ); ?></td>
                                    <td>
                                        <span class="esc-status-badge esc-status-<?php echo esc_attr( $log->status ); ?>">
                                            <?php echo esc_html( ucfirst( $log->status ) ); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html( $log->http_code ?: '—' ); ?></td>
                                    <td><?php echo esc_html( $log->response_time ); ?> ms</td>
                                    <td><?php echo esc_html( $log->error_message ?: '—' ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ( has_post_thumbnail() || get_the_content() ) : ?>
                <div class="esc-service-content">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="esc-service-thumbnail">
                            <?php the_post_thumbnail( 'medium' ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( get_the_content() ) : ?>
                        <div class="esc-service-description">
                            <?php the_content(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </article>
</div>

<style>
.esc-single-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.esc-service-detail-header {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 30px;
    border-left: 5px solid #ddd;
}

.esc-service-detail-header.esc-status-online {
    border-left-color: #46b450;
}

.esc-service-detail-header.esc-status-offline {
    border-left-color: #dc3232;
}

.esc-service-detail-header.esc-status-warning {
    border-left-color: #ffb900;
}

.esc-service-detail-title {
    margin: 0 0 15px 0;
    font-size: 32px;
}

.esc-current-status {
    display: flex;
    align-items: center;
    gap: 20px;
}

.esc-status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 14px;
}

.esc-status-badge.esc-status-online {
    background: #d4edda;
    color: #155724;
}

.esc-status-badge.esc-status-offline {
    background: #f8d7da;
    color: #721c24;
}

.esc-status-badge.esc-status-warning {
    background: #fff3cd;
    color: #856404;
}

.esc-last-check {
    color: #666;
    font-size: 14px;
}

.esc-service-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.esc-info-box {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.esc-info-box h3 {
    margin: 0 0 15px 0;
    font-size: 18px;
    border-bottom: 2px solid #2271b1;
    padding-bottom: 10px;
}

.esc-info-box dl {
    margin: 0;
}

.esc-info-box dt {
    font-weight: bold;
    margin-top: 10px;
    color: #666;
    font-size: 13px;
}

.esc-info-box dd {
    margin: 5px 0 0 0;
    font-size: 14px;
}

.esc-info-box code {
    background: #f5f5f5;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    word-break: break-all;
}

.esc-uptime-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.esc-stat {
    text-align: center;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.esc-stat-label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.esc-stat-value {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}

.esc-error {
    color: #dc3232;
    font-weight: bold;
}

.esc-recent-logs {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
    margin-bottom: 30px;
}

.esc-recent-logs h3 {
    margin: 0 0 15px 0;
    font-size: 18px;
    border-bottom: 2px solid #2271b1;
    padding-bottom: 10px;
}

.esc-logs-table {
    width: 100%;
    border-collapse: collapse;
}

.esc-logs-table th,
.esc-logs-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.esc-logs-table th {
    background: #f9f9f9;
    font-weight: bold;
    font-size: 13px;
}

.esc-logs-table td {
    font-size: 13px;
}

.esc-service-content {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

@media (max-width: 768px) {
    .esc-service-info-grid {
        grid-template-columns: 1fr;
    }
    
    .esc-uptime-stats {
        grid-template-columns: 1fr;
    }
    
    .esc-logs-table {
        font-size: 12px;
    }
    
    .esc-logs-table th,
    .esc-logs-table td {
        padding: 8px 5px;
    }
}
</style>

<?php
get_footer();
