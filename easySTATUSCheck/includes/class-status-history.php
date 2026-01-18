<?php
/**
 * Status History with Charts
 *
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Status_History {

    public function __construct() {
        // History-Seite aus Admin-Menü entfernt - nur noch öffentliche History-Seite verfügbar
        // add_action( 'admin_menu', array( $this, 'add_history_page' ), 15 );
        add_action( 'wp_ajax_esc_get_history_data', array( $this, 'ajax_get_history_data' ) );
        add_action( 'admin_init', array( $this, 'handle_csv_export' ) );
        add_action( 'wp_ajax_esc_export_history', array( $this, 'ajax_export_history' ) );
    }

    /**
     * Add history page to admin menu
     */
    public function add_history_page() {
        add_submenu_page(
            'easy-status-check',
            __( 'Status-History', 'easy-status-check' ),
            __( 'History', 'easy-status-check' ),
            'manage_options',
            'easy-status-check-history',
            array( $this, 'render_history_page' )
        );
    }

    /**
     * Render history page
     */
    public function render_history_page() {
        global $wpdb;
        
        // Handle settings save
        if ( isset( $_POST['esc_save_history_settings'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'esc_history_settings' ) ) {
            update_option( 'esc_history_period', sanitize_text_field( $_POST['history_period'] ) );
            echo '<div class="notice notice-success"><p>' . __( 'Einstellungen gespeichert.', 'easy-status-check' ) . '</p></div>';
        }
        
        $services_table = $wpdb->prefix . 'esc_services';
        $services = $wpdb->get_results( "SELECT * FROM $services_table WHERE enabled = 1 ORDER BY name ASC" );
        $period = get_option( 'esc_history_period', '24h' );
        
        ?>
        <div class="wrap">
            <h1><?php _e( 'Status-History - Alle Services', 'easy-status-check' ); ?></h1>
            <p class="description"><?php _e( 'Übersicht der Status-Verläufe aller aktivierten Services als Graph Cards.', 'easy-status-check' ); ?></p>
            
            <div class="esc-history-settings">
                <form method="post" action="" style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
                    <?php wp_nonce_field( 'esc_history_settings' ); ?>
                    <input type="hidden" name="esc_save_history_settings" value="1">
                    
                    <label for="history-period" style="font-weight: 600; margin-right: 10px;"><?php _e( 'History-Zeitraum für alle Cards:', 'easy-status-check' ); ?></label>
                    <select id="history-period" name="history_period" class="regular-text">
                        <option value="24h" <?php selected( $period, '24h' ); ?>><?php _e( 'Letzte 24 Stunden', 'easy-status-check' ); ?></option>
                        <option value="7d" <?php selected( $period, '7d' ); ?>><?php _e( 'Letzte 7 Tage', 'easy-status-check' ); ?></option>
                        <option value="30d" <?php selected( $period, '30d' ); ?>><?php _e( 'Letzte 30 Tage', 'easy-status-check' ); ?></option>
                        <option value="90d" <?php selected( $period, '90d' ); ?>><?php _e( 'Letzte 90 Tage', 'easy-status-check' ); ?></option>
                    </select>
                    <?php submit_button( __( 'Zeitraum speichern', 'easy-status-check' ), 'primary', 'submit', false ); ?>
                </form>
            </div>
            
            <?php if ( empty( $services ) ) : ?>
                <div class="notice notice-warning">
                    <p><?php _e( 'Keine aktivierten Services gefunden. Bitte aktivieren Sie mindestens einen Service unter "Services".', 'easy-status-check' ); ?></p>
                </div>
            <?php else : ?>
            <div class="esc-history-grid">
                <?php foreach ( $services as $service ) : ?>
                    <div class="esc-history-card" data-service-id="<?php echo esc_attr( $service->id ); ?>">
                        <div class="esc-history-card-header">
                            <h3><?php echo esc_html( $service->name ); ?></h3>
                            <span class="esc-service-url"><?php echo esc_html( $service->url ); ?></span>
                        </div>
                        
                        <div class="esc-history-stats">
                            <div class="esc-stat-mini">
                                <span class="esc-stat-label"><?php _e( 'Uptime', 'easy-status-check' ); ?></span>
                                <span class="esc-stat-value" data-stat="uptime-<?php echo esc_attr( $service->id ); ?>">—</span>
                            </div>
                            <div class="esc-stat-mini">
                                <span class="esc-stat-label"><?php _e( 'Ø Zeit', 'easy-status-check' ); ?></span>
                                <span class="esc-stat-value" data-stat="avgtime-<?php echo esc_attr( $service->id ); ?>">—</span>
                            </div>
                            <div class="esc-stat-mini">
                                <span class="esc-stat-label"><?php _e( 'Checks', 'easy-status-check' ); ?></span>
                                <span class="esc-stat-value" data-stat="checks-<?php echo esc_attr( $service->id ); ?>">—</span>
                            </div>
                        </div>
                        
                        <div class="esc-chart-mini">
                            <canvas id="chart-<?php echo esc_attr( $service->id ); ?>" width="400" height="150"></canvas>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="esc-history-table-container">
                    <h2><?php _e( 'Detaillierte Logs', 'easy-status-check' ); ?></h2>
                    <table class="wp-list-table widefat fixed striped" id="esc-history-table">
                        <thead>
                            <tr>
                                <th><?php _e( 'Zeitpunkt', 'easy-status-check' ); ?></th>
                                <th><?php _e( 'Status', 'easy-status-check' ); ?></th>
                                <th><?php _e( 'HTTP-Code', 'easy-status-check' ); ?></th>
                                <th><?php _e( 'Antwortzeit', 'easy-status-check' ); ?></th>
                                <th><?php _e( 'Fehler', 'easy-status-check' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="esc-history-tbody">
                            <tr>
                                <td colspan="5"><?php _e( 'Lade Daten...', 'easy-status-check' ); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="notice notice-info" style="margin-top: 20px;">
                <p><strong><?php _e( 'Hinweis:', 'easy-status-check' ); ?></strong> <?php _e( 'History-Daten werden erst nach der ersten Service-Prüfung angezeigt. Services müssen aktiviert sein und mindestens einmal geprüft worden sein.', 'easy-status-check' ); ?></p>
            </div>
        </div>
        
        <style>
            .esc-history-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                margin: 20px 0;
            }
            
            .esc-history-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            .esc-history-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            
            .esc-history-card-header h3 {
                margin: 0 0 5px 0;
                font-size: 16px;
                color: #1d2327;
            }
            
            .esc-service-url {
                font-size: 12px;
                color: #999;
                word-break: break-all;
            }
            
            .esc-history-stats {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                margin: 15px 0;
                padding: 15px 0;
                border-top: 1px solid #f0f0f1;
                border-bottom: 1px solid #f0f0f1;
            }
            
            .esc-stat-mini {
                text-align: center;
            }
            
            .esc-stat-label {
                display: block;
                font-size: 11px;
                color: #666;
                margin-bottom: 5px;
                text-transform: uppercase;
            }
            
            .esc-stat-value {
                display: block;
                font-size: 18px;
                font-weight: bold;
                color: #2271b1;
            }
            
            .esc-chart-mini {
                margin-top: 15px;
            }
            
            .esc-chart-mini canvas {
                width: 100% !important;
                height: auto !important;
            }
            
            @media (max-width: 1200px) {
                .esc-history-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            
            @media (max-width: 768px) {
                .esc-history-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
        jQuery(document).ready(function($) {
            var period = '<?php echo esc_js( $period ); ?>';
            var charts = {};
            
            // Load data for all services
            $('.esc-history-card').each(function() {
                var serviceId = $(this).data('service-id');
                loadServiceData(serviceId);
            });
            
            function loadServiceData(serviceId) {
                $.post(ajaxurl, {
                    action: 'esc_get_history_data',
                    service_id: serviceId,
                    period: period,
                    nonce: '<?php echo wp_create_nonce( 'esc_get_history_data' ); ?>'
                }, function(response) {
                    if (response.success) {
                        updateServiceStats(serviceId, response.data.stats);
                        createServiceChart(serviceId, response.data.chart_data);
                    } else {
                        // Fehlerfall: Zeige "Keine Daten"
                        $('[data-stat="uptime-' + serviceId + '"]').text('N/A');
                        $('[data-stat="avgtime-' + serviceId + '"]').text('N/A');
                        $('[data-stat="checks-' + serviceId + '"]').text('0');
                    }
                }).fail(function() {
                    // AJAX-Fehler: Zeige "Fehler"
                    $('[data-stat="uptime-' + serviceId + '"]').text('Fehler');
                    $('[data-stat="avgtime-' + serviceId + '"]').text('Fehler');
                    $('[data-stat="checks-' + serviceId + '"]').text('0');
                });
            }
            
            function updateServiceStats(serviceId, stats) {
                $('[data-stat="uptime-' + serviceId + '"]').text(stats.uptime + '%');
                $('[data-stat="avgtime-' + serviceId + '"]').text(stats.avg_response + 'ms');
                $('[data-stat="checks-' + serviceId + '"]').text(stats.total_checks);
            }
            
            function createServiceChart(serviceId, chartData) {
                var ctx = document.getElementById('chart-' + serviceId);
                if (!ctx) return;
                
                if (charts[serviceId]) {
                    charts[serviceId].destroy();
                }
                
                charts[serviceId] = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: 'Status',
                            data: chartData.response_data,
                            borderColor: '#2271b1',
                            backgroundColor: 'rgba(34, 113, 177, 0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 2,
                            pointHoverRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                display: false
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Alte Funktionen entfernt - nicht mehr benötigt
            function updateTable(logs) {
                // Nicht mehr verwendet
            }
            
            function updateCharts(chartData) {
                // Nicht mehr verwendet
            }
            
            function loadHistoryData() {
                // Nicht mehr verwendet
                        '<td>' + (log.http_code || '—') + '</td>' +
                        '<td>' + (log.response_time ? log.response_time + ' ms' : '—') + '</td>' +
                        '<td>' + (log.error_message || '—') + '</td>' +
                        '</tr>';
                    
                    tbody.append(row);
                });
            }
            
            // Event-Handler entfernt - keine Buttons mehr vorhanden
            // Alle Services werden automatisch beim Laden geladen
                
                if (!serviceId) {
                    alert('<?php _e( 'Bitte wählen Sie einen Service aus', 'easy-status-check' ); ?>');
                    return;
                }
                
                window.location.href = ajaxurl + '?action=esc_export_history&service_id=' + serviceId + 
                    '&period=' + period + '&nonce=<?php echo wp_create_nonce( 'esc_export_history' ); ?>';
            });
            
            <?php if ( $selected_service ) : ?>
            loadHistoryData();
            <?php endif; ?>
        });
        </script>
        <?php
    }

    /**
     * AJAX: Get history data
     */
    public function ajax_get_history_data() {
        check_ajax_referer( 'esc_get_history_data', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'easy-status-check' ) ) );
        }
        
        $service_id = isset( $_POST['service_id'] ) ? intval( $_POST['service_id'] ) : 0;
        $period = isset( $_POST['period'] ) ? sanitize_text_field( $_POST['period'] ) : '24h';
        
        if ( ! $service_id ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige Service-ID', 'easy-status-check' ) ) );
        }
        
        $data = $this->get_history_data( $service_id, $period );
        
        wp_send_json_success( $data );
    }

    /**
     * Get history data for a service
     */
    private function get_history_data( $service_id, $period ) {
        global $wpdb;
        
        $period_map = array(
            '24h' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
        );
        
        $days = isset( $period_map[ $period ] ) ? $period_map[ $period ] : 1;
        $start_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
        
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        $logs = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $logs_table WHERE service_id = %d AND checked_at >= %s ORDER BY checked_at ASC",
            $service_id,
            $start_date
        ) );
        
        $stats = $this->calculate_stats( $logs );
        $chart_data = $this->prepare_chart_data( $logs );
        
        return array(
            'stats' => $stats,
            'chart_data' => $chart_data,
            'logs' => $logs,
        );
    }

    /**
     * Calculate statistics
     */
    private function calculate_stats( $logs ) {
        if ( empty( $logs ) ) {
            return array(
                'uptime' => 0,
                'avg_response' => 0,
                'total_checks' => 0,
                'downtime_count' => 0,
            );
        }
        
        $total_checks = count( $logs );
        $online_count = 0;
        $total_response_time = 0;
        $downtime_count = 0;
        
        foreach ( $logs as $log ) {
            if ( $log->status === 'online' ) {
                $online_count++;
            } else {
                $downtime_count++;
            }
            
            if ( $log->response_time ) {
                $total_response_time += $log->response_time;
            }
        }
        
        $uptime = ( $online_count / $total_checks ) * 100;
        $avg_response = $total_response_time / $total_checks;
        
        return array(
            'uptime' => round( $uptime, 2 ),
            'avg_response' => round( $avg_response, 2 ),
            'total_checks' => $total_checks,
            'downtime_count' => $downtime_count,
        );
    }

    /**
     * Prepare chart data
     */
    private function prepare_chart_data( $logs ) {
        $labels = array();
        $status_data = array();
        $response_data = array();
        
        foreach ( $logs as $log ) {
            $labels[] = date( 'H:i', strtotime( $log->checked_at ) );
            $status_data[] = $log->status === 'online' ? 1 : 0;
            $response_data[] = $log->response_time ? floatval( $log->response_time ) : null;
        }
        
        return array(
            'labels' => $labels,
            'status_data' => $status_data,
            'response_data' => $response_data,
        );
    }

    /**
     * Handle CSV export via admin_init
     */
    public function handle_csv_export() {
        if ( ! isset( $_GET['esc_export_csv'] ) || ! isset( $_GET['service_id'] ) ) {
            return;
        }
        
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'esc_export_history' ) ) {
            wp_die( __( 'Sicherheitsüberprüfung fehlgeschlagen', 'easy-status-check' ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Keine Berechtigung', 'easy-status-check' ) );
        }
        
        $service_id = intval( $_GET['service_id'] );
        $period = isset( $_GET['period'] ) ? sanitize_text_field( $_GET['period'] ) : '24h';
        
        $this->export_csv( $service_id, $period );
        exit;
    }
    
    /**
     * Export history data as CSV
     */
    private function export_csv( $service_id, $period ) {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        $logs_table = $wpdb->prefix . 'esc_status_logs';
        
        $service = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $services_table WHERE id = %d",
            $service_id
        ) );
        
        if ( ! $service ) {
            wp_die( __( 'Service nicht gefunden', 'easy-status-check' ) );
        }
        
        // Calculate date range
        $period_map = array(
            '24h' => '24 HOUR',
            '7d' => '7 DAY',
            '30d' => '30 DAY',
            '90d' => '90 DAY',
        );
        
        $interval = isset( $period_map[ $period ] ) ? $period_map[ $period ] : '24 HOUR';
        
        $logs = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $logs_table 
            WHERE service_id = %d 
            AND checked_at >= DATE_SUB(NOW(), INTERVAL $interval)
            ORDER BY checked_at DESC",
            $service_id
        ) );
        
        // Set headers for CSV download
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="status-history-' . sanitize_file_name( $service->name ) . '-' . date( 'Y-m-d' ) . '.csv"' );
        
        $output = fopen( 'php://output', 'w' );
        
        // Add BOM for Excel UTF-8 support
        fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );
        
        // CSV headers
        fputcsv( $output, array(
            __( 'Zeitpunkt', 'easy-status-check' ),
            __( 'Status', 'easy-status-check' ),
            __( 'HTTP-Code', 'easy-status-check' ),
            __( 'Antwortzeit (ms)', 'easy-status-check' ),
            __( 'Fehlermeldung', 'easy-status-check' ),
        ) );
        
        // CSV data
        foreach ( $logs as $log ) {
            fputcsv( $output, array(
                $log->checked_at,
                $log->status,
                $log->http_code,
                $log->response_time ? round( $log->response_time, 2 ) : '',
                $log->error_message,
            ) );
        }
        
        fclose( $output );
    }
    
    /**
     * AJAX: Export history as CSV
     */
    public function ajax_export_history() {
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'esc_export_history' ) ) {
            wp_die( __( 'Sicherheitsüberprüfung fehlgeschlagen', 'easy-status-check' ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Keine Berechtigung', 'easy-status-check' ) );
        }
        
        $service_id = isset( $_GET['service_id'] ) ? intval( $_GET['service_id'] ) : 0;
        $period = isset( $_GET['period'] ) ? sanitize_text_field( $_GET['period'] ) : '24h';
        
        if ( ! $service_id ) {
            wp_die( __( 'Ungültige Service-ID', 'easy-status-check' ) );
        }
        
        global $wpdb;
        $services_table = $wpdb->prefix . 'esc_services';
        $service = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $services_table WHERE id = %d",
            $service_id
        ) );
        
        if ( ! $service ) {
            wp_die( __( 'Service nicht gefunden', 'easy-status-check' ) );
        }
        
        $data = $this->get_history_data( $service_id, $period );
        
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="status-history-' . sanitize_file_name( $service->name ) . '-' . date( 'Y-m-d' ) . '.csv"' );
        
        $output = fopen( 'php://output', 'w' );
        
        fputcsv( $output, array( 'Zeitpunkt', 'Status', 'HTTP-Code', 'Antwortzeit (ms)', 'Fehler' ) );
        
        foreach ( $data['logs'] as $log ) {
            fputcsv( $output, array(
                $log->checked_at,
                $log->status,
                $log->http_code,
                $log->response_time,
                $log->error_message,
            ) );
        }
        
        fclose( $output );
        exit;
    }
}
