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
        add_action( 'admin_menu', array( $this, 'add_history_page' ), 20 );
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
        $services_table = $wpdb->prefix . 'esc_services';
        $services = $wpdb->get_results( "SELECT * FROM $services_table WHERE enabled = 1 ORDER BY name ASC" );

        $selected_service = isset( $_GET['service_id'] ) ? intval( $_GET['service_id'] ) : 0;
        $period = isset( $_GET['period'] ) ? sanitize_text_field( $_GET['period'] ) : '24h';
        
        ?>
        <div class="wrap">
            <h1><?php _e( 'Status-History', 'easy-status-check' ); ?></h1>
            
            <div class="esc-history-filters">
                <select id="esc-service-select" class="regular-text">
                    <option value=""><?php _e( 'Service auswählen', 'easy-status-check' ); ?></option>
                    <?php foreach ( $services as $service ) : ?>
                        <option value="<?php echo esc_attr( $service->id ); ?>" <?php selected( $selected_service, $service->id ); ?>>
                            <?php echo esc_html( $service->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select id="esc-period-select" class="regular-text">
                    <option value="24h" <?php selected( $period, '24h' ); ?>><?php _e( 'Letzte 24 Stunden', 'easy-status-check' ); ?></option>
                    <option value="7d" <?php selected( $period, '7d' ); ?>><?php _e( 'Letzte 7 Tage', 'easy-status-check' ); ?></option>
                    <option value="30d" <?php selected( $period, '30d' ); ?>><?php _e( 'Letzte 30 Tage', 'easy-status-check' ); ?></option>
                    <option value="90d" <?php selected( $period, '90d' ); ?>><?php _e( 'Letzte 90 Tage', 'easy-status-check' ); ?></option>
                </select>
                
                <button id="esc-load-history" class="button button-primary"><?php _e( 'Laden', 'easy-status-check' ); ?></button>
                <button id="esc-export-history" class="button"><?php _e( 'Als CSV exportieren', 'easy-status-check' ); ?></button>
            </div>
            
            <?php if ( $selected_service ) : ?>
                <div class="esc-history-stats">
                    <div class="esc-stat-box">
                        <h3><?php _e( 'Uptime', 'easy-status-check' ); ?></h3>
                        <div class="esc-stat-value" id="esc-uptime-stat">—</div>
                    </div>
                    <div class="esc-stat-box">
                        <h3><?php _e( 'Durchschn. Antwortzeit', 'easy-status-check' ); ?></h3>
                        <div class="esc-stat-value" id="esc-avg-response-stat">—</div>
                    </div>
                    <div class="esc-stat-box">
                        <h3><?php _e( 'Checks gesamt', 'easy-status-check' ); ?></h3>
                        <div class="esc-stat-value" id="esc-total-checks-stat">—</div>
                    </div>
                    <div class="esc-stat-box">
                        <h3><?php _e( 'Ausfälle', 'easy-status-check' ); ?></h3>
                        <div class="esc-stat-value" id="esc-downtime-stat">—</div>
                    </div>
                </div>
                
                <div class="esc-chart-container">
                    <h2><?php _e( 'Status-Verlauf', 'easy-status-check' ); ?></h2>
                    <canvas id="esc-status-chart"></canvas>
                </div>
                
                <div class="esc-chart-container">
                    <h2><?php _e( 'Antwortzeiten', 'easy-status-check' ); ?></h2>
                    <canvas id="esc-response-chart"></canvas>
                </div>
                
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
            <?php else : ?>
                <div class="notice notice-info">
                    <p><?php _e( 'Bitte wählen Sie einen Service aus, um die History anzuzeigen.', 'easy-status-check' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
            .esc-history-filters { margin: 20px 0; }
            .esc-history-filters select,
            .esc-history-filters button { margin-right: 10px; }
            
            .esc-history-stats {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
                margin: 30px 0;
            }
            
            .esc-stat-box {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                text-align: center;
            }
            
            .esc-stat-box h3 {
                margin: 0 0 10px 0;
                font-size: 14px;
                color: #666;
                font-weight: normal;
            }
            
            .esc-stat-value {
                font-size: 32px;
                font-weight: bold;
                color: #2271b1;
            }
            
            .esc-chart-container {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .esc-chart-container h2 {
                margin: 0 0 20px 0;
                font-size: 18px;
            }
            
            .esc-chart-container canvas {
                max-height: 400px;
            }
            
            .esc-history-table-container {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .esc-history-table-container h2 {
                margin: 0 0 20px 0;
                font-size: 18px;
            }
            
            .esc-status-online { color: #46b450; font-weight: bold; }
            .esc-status-offline { color: #dc3232; font-weight: bold; }
            .esc-status-warning { color: #ffb900; font-weight: bold; }
        </style>
        
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
        jQuery(document).ready(function($) {
            var statusChart = null;
            var responseChart = null;
            
            function loadHistoryData() {
                var serviceId = $('#esc-service-select').val();
                var period = $('#esc-period-select').val();
                
                if (!serviceId) {
                    alert('<?php _e( 'Bitte wählen Sie einen Service aus', 'easy-status-check' ); ?>');
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'esc_get_history_data',
                    service_id: serviceId,
                    period: period,
                    nonce: '<?php echo wp_create_nonce( 'esc_get_history_data' ); ?>'
                }, function(response) {
                    if (response.success) {
                        updateStats(response.data.stats);
                        updateCharts(response.data.chart_data);
                        updateTable(response.data.logs);
                    }
                });
            }
            
            function updateStats(stats) {
                $('#esc-uptime-stat').text(stats.uptime + '%');
                $('#esc-avg-response-stat').text(stats.avg_response + ' ms');
                $('#esc-total-checks-stat').text(stats.total_checks);
                $('#esc-downtime-stat').text(stats.downtime_count);
            }
            
            function updateCharts(chartData) {
                var ctx1 = document.getElementById('esc-status-chart');
                var ctx2 = document.getElementById('esc-response-chart');
                
                if (statusChart) statusChart.destroy();
                if (responseChart) responseChart.destroy();
                
                statusChart = new Chart(ctx1, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: '<?php _e( 'Status', 'easy-status-check' ); ?>',
                            data: chartData.status_data,
                            borderColor: '#2271b1',
                            backgroundColor: 'rgba(34, 113, 177, 0.1)',
                            stepped: true,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 1,
                                ticks: {
                                    callback: function(value) {
                                        return value === 1 ? 'Online' : 'Offline';
                                    }
                                }
                            }
                        }
                    }
                });
                
                responseChart = new Chart(ctx2, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: '<?php _e( 'Antwortzeit (ms)', 'easy-status-check' ); ?>',
                            data: chartData.response_data,
                            borderColor: '#46b450',
                            backgroundColor: 'rgba(70, 180, 80, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            function updateTable(logs) {
                var tbody = $('#esc-history-tbody');
                tbody.empty();
                
                if (logs.length === 0) {
                    tbody.append('<tr><td colspan="5"><?php _e( 'Keine Daten verfügbar', 'easy-status-check' ); ?></td></tr>');
                    return;
                }
                
                $.each(logs, function(i, log) {
                    var statusClass = 'esc-status-' + log.status;
                    var statusText = log.status === 'online' ? 'Online' : (log.status === 'offline' ? 'Offline' : 'Warnung');
                    
                    var row = '<tr>' +
                        '<td>' + log.checked_at + '</td>' +
                        '<td class="' + statusClass + '">' + statusText + '</td>' +
                        '<td>' + (log.http_code || '—') + '</td>' +
                        '<td>' + (log.response_time ? log.response_time + ' ms' : '—') + '</td>' +
                        '<td>' + (log.error_message || '—') + '</td>' +
                        '</tr>';
                    
                    tbody.append(row);
                });
            }
            
            $('#esc-load-history').on('click', function() {
                loadHistoryData();
            });
            
            $('#esc-export-history').on('click', function() {
                var serviceId = $('#esc-service-select').val();
                var period = $('#esc-period-select').val();
                
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
