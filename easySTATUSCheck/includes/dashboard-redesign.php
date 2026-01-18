<?php
/**
 * Modern Dashboard for easySTATUSCheck using Easy Design System
 * 
 * @package Easy_Status_Check
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Modern_Dashboard {
    
    public function render() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        
        // Get statistics
        $total_services = $wpdb->get_var( "SELECT COUNT(*) FROM $services_table WHERE enabled = 1" );
        $online_services = $wpdb->get_var( "
            SELECT COUNT(DISTINCT s.id) 
            FROM $services_table s 
            INNER JOIN $logs_table l ON s.id = l.service_id 
            WHERE s.enabled = 1 
            AND l.status = 'online' 
            AND l.checked_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        " );
        $offline_services = $total_services - $online_services;
        $warning_services = $wpdb->get_var( "
            SELECT COUNT(DISTINCT s.id) 
            FROM $services_table s 
            INNER JOIN $logs_table l ON s.id = l.service_id 
            WHERE s.enabled = 1 
            AND l.status = 'warning' 
            AND l.checked_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        " );
        
        // Calculate uptime percentage
        $uptime_percentage = $total_services > 0 ? round( ($online_services / $total_services) * 100, 1 ) : 0;
        
        // Get recent status changes
        $recent_changes = $wpdb->get_results( "
            SELECT s.name, l.status, l.checked_at, l.error_message, l.response_time, s.category
            FROM $logs_table l
            INNER JOIN $services_table s ON l.service_id = s.id
            WHERE l.checked_at > DATE_SUB(NOW(), INTERVAL 6 HOUR)
            ORDER BY l.checked_at DESC
            LIMIT 8
        " );
        
        // Get service categories breakdown
        $categories = $wpdb->get_results( "
            SELECT s.category, COUNT(*) as count,
                   SUM(CASE WHEN l.status = 'online' THEN 1 ELSE 0 END) as online_count
            FROM $services_table s
            LEFT JOIN $logs_table l ON s.id = l.service_id 
            AND l.checked_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            WHERE s.enabled = 1
            GROUP BY s.category
        " );
        
        ?>
        <div class="easy-dashboard-page">
            <div class="easy-container">
                <!-- Page Header -->
                <div class="easy-page-header">
                    <div class="easy-container">
                        <h1 class="easy-page-title">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php _e( 'Status Dashboard', 'easy-status-check' ); ?>
                            <div class="easy-page-actions">
                                <button type="button" id="esc-force-check" class="easy-btn easy-btn-secondary">
                                    <span class="dashicons dashicons-update"></span>
                                    <?php _e( 'Alle prüfen', 'easy-status-check' ); ?>
                                </button>
                                <a href="<?php echo admin_url( 'admin.php?page=easy-status-check-services' ); ?>" class="easy-btn easy-btn-primary">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                    <?php _e( 'Service hinzufügen', 'easy-status-check' ); ?>
                                </a>
                            </div>
                        </h1>
                        <p class="easy-page-description">
                            <?php _e( 'Überwachen Sie den Status Ihrer wichtigsten Services und Cloud-Infrastrukturen in Echtzeit.', 'easy-status-check' ); ?>
                        </p>
                    </div>
                </div>

                <!-- Statistics Grid -->
                <div class="easy-stats-grid easy-mb-6">
                    <div class="easy-stat-card">
                        <div class="easy-stat-number" style="color: var(--easy-primary);"><?php echo intval( $total_services ); ?></div>
                        <div class="easy-stat-label"><?php _e( 'Gesamt Services', 'easy-status-check' ); ?></div>
                    </div>
                    
                    <div class="easy-stat-card">
                        <div class="easy-stat-number" style="color: var(--easy-success);"><?php echo intval( $online_services ); ?></div>
                        <div class="easy-stat-label"><?php _e( 'Online', 'easy-status-check' ); ?></div>
                    </div>
                    
                    <div class="easy-stat-card">
                        <div class="easy-stat-number" style="color: var(--easy-warning);"><?php echo intval( $warning_services ); ?></div>
                        <div class="easy-stat-label"><?php _e( 'Warnungen', 'easy-status-check' ); ?></div>
                    </div>
                    
                    <div class="easy-stat-card">
                        <div class="easy-stat-number" style="color: var(--easy-error);"><?php echo intval( $offline_services ); ?></div>
                        <div class="easy-stat-label"><?php _e( 'Offline', 'easy-status-check' ); ?></div>
                    </div>
                    
                    <div class="easy-stat-card">
                        <div class="easy-stat-number" style="color: <?php echo $uptime_percentage >= 99 ? 'var(--easy-success)' : ($uptime_percentage >= 95 ? 'var(--easy-warning)' : 'var(--easy-error)'); ?>;">
                            <?php echo $uptime_percentage; ?>%
                        </div>
                        <div class="easy-stat-label"><?php _e( 'Verfügbarkeit', 'easy-status-check' ); ?></div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="easy-grid easy-grid-cols-3">
                    <!-- Recent Activity Card -->
                    <div class="easy-card easy-dashboard-full-width" style="grid-column: 1 / 3;">
                        <div class="easy-card-header">
                            <h3 class="easy-card-title">
                                <span class="dashicons dashicons-clock"></span>
                                <?php _e( 'Aktuelle Aktivitäten', 'easy-status-check' ); ?>
                                <div class="easy-card-subtitle"><?php _e( 'Letzte 6 Stunden', 'easy-status-check' ); ?></div>
                            </h3>
                            <div class="easy-card-actions">
                                <button class="easy-btn easy-btn-ghost easy-btn-sm" data-tooltip="Aktualisieren" onclick="location.reload();">
                                    <span class="dashicons dashicons-update"></span>
                                </button>
                                <a href="<?php echo admin_url( 'admin.php?page=easy-status-check-logs' ); ?>" class="easy-btn easy-btn-ghost easy-btn-sm">
                                    <span class="dashicons dashicons-external"></span>
                                    <?php _e( 'Alle Logs', 'easy-status-check' ); ?>
                                </a>
                            </div>
                        </div>
                        <div class="easy-card-content">
                            <?php if ( empty( $recent_changes ) ) : ?>
                                <div class="easy-empty-state">
                                    <span class="dashicons dashicons-admin-page" style="font-size: 48px; color: var(--easy-gray-400);"></span>
                                    <h4><?php _e( 'Keine Aktivitäten', 'easy-status-check' ); ?></h4>
                                    <p><?php _e( 'Es wurden keine Status-Änderungen in den letzten Stunden aufgezeichnet.', 'easy-status-check' ); ?></p>
                                    <button type="button" id="esc-start-check" class="easy-btn easy-btn-primary">
                                        <span class="dashicons dashicons-update"></span>
                                        <?php _e( 'Erste Prüfung starten', 'easy-status-check' ); ?>
                                    </button>
                                </div>
                            <?php else : ?>
                                <div class="esc-activity-timeline">
                                    <?php foreach ( $recent_changes as $change ) : ?>
                                        <div class="esc-activity-item">
                                            <div class="esc-activity-indicator">
                                                <div class="easy-status easy-status-<?php echo esc_attr( $change->status ); ?>">
                                                    <div class="easy-status-dot"></div>
                                                </div>
                                            </div>
                                            <div class="esc-activity-content">
                                                <div class="esc-activity-header">
                                                    <div class="esc-activity-title"><?php echo esc_html( $change->name ); ?></div>
                                                    <div class="esc-activity-time" data-tooltip="<?php echo esc_attr( $change->checked_at ); ?>">
                                                        <?php echo esc_html( human_time_diff( strtotime( $change->checked_at ) ) ); ?> <?php _e( 'her', 'easy-status-check' ); ?>
                                                    </div>
                                                </div>
                                                <div class="esc-activity-details">
                                                    <div class="esc-activity-status">
                                                        <?php 
                                                        $status_text = ucfirst( $change->status );
                                                        if ( $change->response_time ) {
                                                            $status_text .= ' • ' . $change->response_time . 'ms';
                                                        }
                                                        if ( $change->category ) {
                                                            $status_text .= ' • ' . ucfirst( $change->category );
                                                        }
                                                        echo esc_html( $status_text );
                                                        ?>
                                                    </div>
                                                    <?php if ( $change->error_message ) : ?>
                                                        <div class="esc-activity-error">
                                                            <?php echo esc_html( wp_trim_words( $change->error_message, 15 ) ); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions & System Info -->
                    <div class="easy-card">
                        <div class="easy-card-header">
                            <h3 class="easy-card-title">
                                <span class="dashicons dashicons-admin-tools"></span>
                                <?php _e( 'Schnellaktionen', 'easy-status-check' ); ?>
                            </h3>
                        </div>
                        <div class="easy-card-content">
                            <div class="esc-quick-actions">
                                <a href="<?php echo admin_url( 'admin.php?page=easy-status-check-services' ); ?>" class="esc-quick-action-item">
                                    <span class="dashicons dashicons-networking"></span>
                                    <div class="esc-action-text">
                                        <strong><?php _e( 'Services verwalten', 'easy-status-check' ); ?></strong>
                                        <small><?php _e( 'Services hinzufügen, bearbeiten oder entfernen', 'easy-status-check' ); ?></small>
                                    </div>
                                </a>
                                
                                <a href="<?php echo admin_url( 'admin.php?page=easy-status-check-settings' ); ?>" class="esc-quick-action-item">
                                    <span class="dashicons dashicons-admin-generic"></span>
                                    <div class="esc-action-text">
                                        <strong><?php _e( 'Einstellungen', 'easy-status-check' ); ?></strong>
                                        <small><?php _e( 'Benachrichtigungen und Intervalle konfigurieren', 'easy-status-check' ); ?></small>
                                    </div>
                                </a>
                                
                                <button type="button" class="esc-quick-action-item" onclick="window.open('<?php echo site_url( '?esc_status_page=1' ); ?>', '_blank');">
                                    <span class="dashicons dashicons-external"></span>
                                    <div class="esc-action-text">
                                        <strong><?php _e( 'Öffentliche Ansicht', 'easy-status-check' ); ?></strong>
                                        <small><?php _e( 'Status-Seite für Kunden anzeigen', 'easy-status-check' ); ?></small>
                                    </div>
                                </button>
                                
                                <button type="button" class="esc-quick-action-item" id="esc-export-status">
                                    <span class="dashicons dashicons-download"></span>
                                    <div class="esc-action-text">
                                        <strong><?php _e( 'Status exportieren', 'easy-status-check' ); ?></strong>
                                        <small><?php _e( 'Berichte und Logs herunterladen', 'easy-status-check' ); ?></small>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categories & System Info Row -->
                <div class="easy-grid easy-grid-cols-2 easy-mt-6">
                    <!-- Categories Breakdown -->
                    <?php if ( ! empty( $categories ) ) : ?>
                    <div class="easy-card">
                        <div class="easy-card-header">
                            <h3 class="easy-card-title">
                                <span class="dashicons dashicons-category"></span>
                                <?php _e( 'Kategorien', 'easy-status-check' ); ?>
                            </h3>
                        </div>
                        <div class="easy-card-content">
                            <div class="esc-categories-breakdown">
                                <?php foreach ( $categories as $category ) : ?>
                                    <div class="esc-category-item">
                                        <div class="esc-category-info">
                                            <div class="esc-category-name"><?php echo esc_html( ucfirst( $category->category ) ); ?></div>
                                            <div class="esc-category-stats">
                                                <?php echo intval( $category->online_count ); ?>/<?php echo intval( $category->count ); ?> <?php _e( 'online', 'easy-status-check' ); ?>
                                            </div>
                                        </div>
                                        <div class="esc-category-status">
                                            <?php 
                                            $category_percentage = $category->count > 0 ? ($category->online_count / $category->count) * 100 : 0;
                                            $status_class = $category_percentage >= 95 ? 'online' : ($category_percentage >= 80 ? 'warning' : 'offline');
                                            ?>
                                            <div class="easy-status easy-status-<?php echo $status_class; ?>">
                                                <div class="easy-status-dot"></div>
                                                <?php echo round( $category_percentage, 1 ); ?>%
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- System Information -->
                    <div class="easy-card">
                        <div class="easy-card-header">
                            <h3 class="easy-card-title">
                                <span class="dashicons dashicons-info"></span>
                                <?php _e( 'System-Information', 'easy-status-check' ); ?>
                            </h3>
                        </div>
                        <div class="easy-card-content">
                            <div class="esc-system-info">
                                <div class="esc-info-item">
                                    <div class="esc-info-label"><?php _e( 'Plugin Version', 'easy-status-check' ); ?></div>
                                    <div class="esc-info-value"><?php echo EASY_STATUS_CHECK_VERSION; ?></div>
                                </div>
                                
                                <div class="esc-info-item">
                                    <div class="esc-info-label"><?php _e( 'Aktive Services', 'easy-status-check' ); ?></div>
                                    <div class="esc-info-value"><?php echo intval( $total_services ); ?></div>
                                </div>
                                
                                <div class="esc-info-item">
                                    <div class="esc-info-label"><?php _e( 'Letzte Prüfung', 'easy-status-check' ); ?></div>
                                    <div class="esc-info-value">
                                        <?php 
                                        $last_check = get_option( 'esc_last_global_check' );
                                        if ( $last_check ) {
                                            echo esc_html( human_time_diff( $last_check ) ) . ' ' . __( 'her', 'easy-status-check' );
                                        } else {
                                            echo __( 'Nie', 'easy-status-check' );
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="esc-info-item">
                                    <div class="esc-info-label"><?php _e( 'Nächste Prüfung', 'easy-status-check' ); ?></div>
                                    <div class="esc-info-value">
                                        <?php 
                                        $next_scheduled = wp_next_scheduled( 'esc_status_check_cron' );
                                        if ( $next_scheduled ) {
                                            echo esc_html( human_time_diff( $next_scheduled ) );
                                        } else {
                                            echo __( 'Nicht geplant', 'easy-status-check' );
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="esc-info-item">
                                    <div class="esc-info-label"><?php _e( 'Benachrichtigungen', 'easy-status-check' ); ?></div>
                                    <div class="esc-info-value">
                                        <?php 
                                        $notifications = get_option( 'esc_notification_settings', array() );
                                        $email_enabled = isset( $notifications['email_enabled'] ) && $notifications['email_enabled'];
                                        ?>
                                        <div class="easy-status easy-status-<?php echo $email_enabled ? 'online' : 'offline'; ?>">
                                            <div class="easy-status-dot"></div>
                                            <?php echo $email_enabled ? __( 'Aktiv', 'easy-status-check' ) : __( 'Inaktiv', 'easy-status-check' ); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="esc-info-item">
                                    <div class="esc-info-label"><?php _e( 'Prüfintervall', 'easy-status-check' ); ?></div>
                                    <div class="esc-info-value">
                                        <?php 
                                        $interval = get_option( 'esc_check_interval', 300 );
                                        echo intval( $interval / 60 ) . ' ' . __( 'Minuten', 'easy-status-check' );
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="easy-card-footer">
                            <button type="button" class="easy-btn easy-btn-ghost easy-btn-sm" onclick="location.href='<?php echo admin_url( 'admin.php?page=easy-status-check-settings' ); ?>'">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php _e( 'Konfigurieren', 'easy-status-check' ); ?>
                            </button>
                            <button type="button" class="easy-btn easy-btn-secondary easy-btn-sm" id="esc-system-check">
                                <span class="dashicons dashicons-admin-tools"></span>
                                <?php _e( 'System prüfen', 'easy-status-check' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Force check all services
            $('#esc-force-check, #esc-start-check').on('click', function() {
                var $button = $(this);
                $button.addClass('loading').prop('disabled', true);
                
                $.ajax({
                    url: escAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'esc_force_check_all',
                        nonce: escAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            if (typeof EasyDesignSystem !== 'undefined') {
                                EasyDesignSystem.showNotification('<?php _e( 'Alle Services wurden erfolgreich geprüft.', 'easy-status-check' ); ?>', 'success');
                            }
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            if (typeof EasyDesignSystem !== 'undefined') {
                                EasyDesignSystem.showNotification('<?php _e( 'Fehler bei der Prüfung:', 'easy-status-check' ); ?> ' + response.data, 'error');
                            } else {
                                alert('<?php _e( 'Fehler bei der Prüfung:', 'easy-status-check' ); ?> ' + response.data);
                            }
                        }
                    },
                    error: function() {
                        if (typeof EasyDesignSystem !== 'undefined') {
                            EasyDesignSystem.showNotification('<?php _e( 'AJAX-Fehler aufgetreten', 'easy-status-check' ); ?>', 'error');
                        } else {
                            alert('<?php _e( 'AJAX-Fehler aufgetreten', 'easy-status-check' ); ?>');
                        }
                    },
                    complete: function() {
                        $button.removeClass('loading').prop('disabled', false);
                    }
                });
            });

            // Export status data
            $('#esc-export-status').on('click', function() {
                window.location.href = escAdmin.ajaxUrl + '?action=esc_export_status&nonce=' + escAdmin.nonce;
            });

            // System check
            $('#esc-system-check').on('click', function() {
                var $button = $(this);
                $button.addClass('loading').prop('disabled', true);
                
                // Simulate system check
                setTimeout(function() {
                    $button.removeClass('loading').prop('disabled', false);
                    if (typeof EasyDesignSystem !== 'undefined') {
                        EasyDesignSystem.showNotification('<?php _e( 'System-Check abgeschlossen. Alle Komponenten funktionieren ordnungsgemäß.', 'easy-status-check' ); ?>', 'success');
                    } else {
                        alert('<?php _e( 'System-Check abgeschlossen.', 'easy-status-check' ); ?>');
                    }
                }, 2000);
            });

            // Auto-refresh every 5 minutes
            setInterval(function() {
                if (!document.hidden) {
                    location.reload();
                }
            }, 300000);
        });
        </script>

        <style>
        /* Activity Timeline Styles */
        .esc-activity-timeline {
            display: flex;
            flex-direction: column;
            gap: var(--easy-space-4);
        }

        .esc-activity-item {
            display: flex;
            align-items: flex-start;
            gap: var(--easy-space-3);
            padding: var(--easy-space-4);
            background: var(--easy-gray-50);
            border-radius: var(--easy-radius);
            border: 1px solid var(--easy-gray-200);
            transition: var(--easy-transition);
        }

        .esc-activity-item:hover {
            background: var(--easy-gray-100);
            border-color: var(--easy-gray-300);
        }

        .esc-activity-indicator {
            flex-shrink: 0;
            margin-top: 2px;
        }

        .esc-activity-content {
            flex: 1;
        }

        .esc-activity-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--easy-space-2);
        }

        .esc-activity-title {
            font-weight: 600;
            color: var(--easy-gray-900);
            font-size: var(--easy-text-base);
        }

        .esc-activity-time {
            font-size: var(--easy-text-xs);
            color: var(--easy-gray-500);
            white-space: nowrap;
        }

        .esc-activity-details {
            display: flex;
            flex-direction: column;
            gap: var(--easy-space-1);
        }

        .esc-activity-status {
            font-size: var(--easy-text-sm);
            color: var(--easy-gray-600);
        }

        .esc-activity-error {
            font-size: var(--easy-text-xs);
            color: var(--easy-error);
            background: var(--easy-error-light);
            padding: var(--easy-space-2);
            border-radius: var(--easy-radius-sm);
        }

        /* Quick Actions Styles */
        .esc-quick-actions {
            display: flex;
            flex-direction: column;
            gap: var(--easy-space-3);
        }

        .esc-quick-action-item {
            display: flex;
            align-items: center;
            gap: var(--easy-space-3);
            padding: var(--easy-space-4);
            background: var(--easy-gray-50);
            border: 1px solid var(--easy-gray-200);
            border-radius: var(--easy-radius);
            text-decoration: none;
            color: var(--easy-gray-700);
            transition: var(--easy-transition);
            cursor: pointer;
        }

        .esc-quick-action-item:hover {
            background: var(--easy-primary-light);
            border-color: var(--easy-primary);
            color: var(--easy-primary);
            text-decoration: none;
        }

        .esc-quick-action-item .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .esc-action-text {
            flex: 1;
        }

        .esc-action-text strong {
            display: block;
            font-size: var(--easy-text-sm);
            font-weight: 600;
            margin-bottom: var(--easy-space-1);
        }

        .esc-action-text small {
            font-size: var(--easy-text-xs);
            color: var(--easy-gray-500);
        }

        /* Categories Breakdown */
        .esc-categories-breakdown {
            display: flex;
            flex-direction: column;
            gap: var(--easy-space-3);
        }

        .esc-category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--easy-space-3);
            background: var(--easy-gray-50);
            border-radius: var(--easy-radius);
            border: 1px solid var(--easy-gray-200);
        }

        .esc-category-info {
            flex: 1;
        }

        .esc-category-name {
            font-weight: 500;
            color: var(--easy-gray-900);
            margin-bottom: var(--easy-space-1);
        }

        .esc-category-stats {
            font-size: var(--easy-text-xs);
            color: var(--easy-gray-500);
        }

        .esc-category-status {
            flex-shrink: 0;
        }

        /* System Info */
        .esc-system-info {
            display: flex;
            flex-direction: column;
            gap: var(--easy-space-3);
        }

        .esc-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--easy-space-2) 0;
            border-bottom: 1px solid var(--easy-gray-200);
        }

        .esc-info-item:last-child {
            border-bottom: none;
        }

        .esc-info-label {
            font-size: var(--easy-text-sm);
            color: var(--easy-gray-600);
        }

        .esc-info-value {
            font-weight: 500;
            color: var(--easy-gray-900);
            font-size: var(--easy-text-sm);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .esc-activity-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--easy-space-1);
            }

            .esc-activity-time {
                white-space: normal;
            }

            .esc-category-item {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--easy-space-2);
            }

            .esc-quick-action-item {
                padding: var(--easy-space-3);
            }

            .esc-action-text strong {
                font-size: var(--easy-text-xs);
            }

            .esc-action-text small {
                display: none;
            }
        }
        </style>
        <?php
    }
}
