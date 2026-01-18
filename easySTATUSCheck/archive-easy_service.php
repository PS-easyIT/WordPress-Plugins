<?php
/**
 * Archive Template for Services
 *
 * @package Easy_Status_Check
 */

get_header();
?>

<div class="esc-archive-container">
    <header class="esc-archive-header">
        <h1 class="esc-archive-title"><?php _e( 'Service-Status Übersicht', 'easy-status-check' ); ?></h1>
        <p class="esc-archive-description"><?php _e( 'Aktuelle Status-Informationen aller überwachten Services', 'easy-status-check' ); ?></p>
    </header>

    <?php
    // Get all services from database
    global $wpdb;
    $services_table = $wpdb->prefix . 'esc_services';
    $logs_table = $wpdb->prefix . 'esc_status_logs';
    
    $services = $wpdb->get_results( "
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
        WHERE s.enabled = 1
        ORDER BY s.category, s.name
    " );
    
    // Group services by category
    $grouped_services = array();
    foreach ( $services as $service ) {
        $category = $service->category ?: 'custom';
        if ( ! isset( $grouped_services[ $category ] ) ) {
            $grouped_services[ $category ] = array();
        }
        $grouped_services[ $category ][] = $service;
    }
    
    $category_labels = array(
        'cloud' => __( 'Cloud Services', 'easy-status-check' ),
        'hosting' => __( 'Hosting-Anbieter', 'easy-status-check' ),
        'custom' => __( 'Benutzerdefinierte Services', 'easy-status-check' ),
    );
    ?>

    <?php if ( ! empty( $services ) ) : ?>
        <div class="esc-services-grid">
            <?php foreach ( $grouped_services as $category => $category_services ) : ?>
                <div class="esc-category-section">
                    <h2 class="esc-category-title">
                        <?php echo esc_html( $category_labels[ $category ] ?? ucfirst( $category ) ); ?>
                    </h2>
                    
                    <div class="esc-services-list">
                        <?php foreach ( $category_services as $service ) : ?>
                            <?php
                            $status_class = 'esc-status-' . ( $service->current_status ?: 'unknown' );
                            $status_icon = array(
                                'online' => '🟢',
                                'offline' => '🔴',
                                'warning' => '🟡',
                                'unknown' => '⚪',
                            );
                            ?>
                            <div class="esc-service-card <?php echo esc_attr( $status_class ); ?>">
                                <div class="esc-service-header">
                                    <span class="esc-status-icon">
                                        <?php echo $status_icon[ $service->current_status ?: 'unknown' ]; ?>
                                    </span>
                                    <h3 class="esc-service-name"><?php echo esc_html( $service->name ); ?></h3>
                                </div>
                                
                                <div class="esc-service-body">
                                    <div class="esc-service-url">
                                        <code><?php echo esc_html( $service->url ); ?></code>
                                    </div>
                                    
                                    <div class="esc-service-meta">
                                        <?php if ( $service->response_time ) : ?>
                                            <div class="esc-meta-item">
                                                <span class="esc-meta-label"><?php _e( 'Antwortzeit', 'easy-status-check' ); ?></span>
                                                <span class="esc-meta-value"><?php echo esc_html( round( $service->response_time, 2 ) ); ?> ms</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ( $service->last_checked ) : ?>
                                            <div class="esc-meta-item">
                                                <span class="esc-meta-label"><?php _e( 'Letzte Prüfung', 'easy-status-check' ); ?></span>
                                                <span class="esc-meta-value">
                                                    <?php echo esc_html( human_time_diff( strtotime( $service->last_checked ), current_time( 'timestamp' ) ) ); ?>
                                                    <?php _e( 'her', 'easy-status-check' ); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ( $service->error_message ) : ?>
                                        <div class="esc-service-error">
                                            <strong><?php _e( 'Fehler:', 'easy-status-check' ); ?></strong>
                                            <?php echo esc_html( $service->error_message ); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="esc-empty-state">
            <p><?php _e( 'Keine Services gefunden.', 'easy-status-check' ); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.esc-archive-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.esc-archive-header {
    text-align: center;
    margin-bottom: 40px;
}

.esc-archive-title {
    font-size: 32px;
    margin-bottom: 10px;
}

.esc-archive-description {
    color: #666;
    font-size: 16px;
}

.esc-category-section {
    margin-bottom: 50px;
}

.esc-category-title {
    font-size: 24px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 3px solid #2271b1;
}

.esc-services-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.esc-service-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
}

.esc-service-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.esc-service-card.esc-status-online {
    border-left: 4px solid #46b450;
}

.esc-service-card.esc-status-offline {
    border-left: 4px solid #dc3232;
}

.esc-service-card.esc-status-warning {
    border-left: 4px solid #ffb900;
}

.esc-service-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.esc-status-icon {
    font-size: 20px;
}

.esc-service-name {
    margin: 0;
    font-size: 18px;
}

.esc-service-url {
    margin-bottom: 15px;
}

.esc-service-url code {
    background: #f5f5f5;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    word-break: break-all;
}

.esc-service-meta {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 10px;
}

.esc-meta-item {
    background: #f9f9f9;
    padding: 10px;
    border-radius: 4px;
}

.esc-meta-label {
    display: block;
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.esc-meta-value {
    font-weight: bold;
    color: #333;
}

.esc-service-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    padding: 10px;
    border-radius: 4px;
    font-size: 13px;
    color: #991b1b;
}

.esc-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

@media (max-width: 768px) {
    .esc-services-list {
        grid-template-columns: 1fr;
    }
    
    .esc-service-meta {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
get_footer();
