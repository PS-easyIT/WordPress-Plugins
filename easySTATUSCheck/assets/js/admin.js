/**
 * easySTATUSCheck Admin JavaScript
 */

(function($) {
    'use strict';

    const ESCAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initModalHandlers();
        },

        bindEvents: function() {
            // Dashboard force check
            $('#esc-force-check').on('click', this.forceCheckAll);
            
            // Service management
            $('#esc-add-service').on('click', this.showAddServiceModal);
            $('#esc-add-predefined').on('click', this.showPredefinedModal);
            
            // Service item actions
            $(document).on('click', '.esc-edit-service', this.editService);
            $(document).on('click', '.esc-delete-service', this.deleteService);
            $(document).on('click', '.esc-test-service', this.testService);
            
            // Modal actions
            $('#esc-save-service').on('click', this.saveService);
            $('#esc-cancel-service').on('click', this.hideServiceModal);
            $('#esc-add-predefined-services').on('click', this.addPredefinedServices);
            $('#esc-cancel-predefined').on('click', this.hidePredefinedModal);
            
            // Modal close buttons
            $('.esc-modal-close').on('click', this.hideAllModals);
            
            // Response type toggle
            $(document).on('change', '#service-response-type', this.toggleResponseTypeFields);
            
            // Click outside modal to close
            $('.esc-modal').on('click', function(e) {
                if (e.target === this) {
                    ESCAdmin.hideAllModals();
                }
            });
            
            // Escape key to close modals
            $(document).on('keyup', function(e) {
                if (e.keyCode === 27) {
                    ESCAdmin.hideAllModals();
                }
            });
            
            // Auto-refresh status display
            this.startAutoRefresh();
        },

        initModalHandlers: function() {
            // Initialize any modal-specific handlers
        },

        showAddServiceModal: function() {
            $('#esc-modal-title').text(escAdmin.strings.addService || 'Service hinzufügen');
            $('#esc-service-form')[0].reset();
            $('#service-id').val('');
            $('#esc-service-modal').show();
        },

        showPredefinedModal: function() {
            $('#esc-predefined-modal').show();
        },

        hideServiceModal: function() {
            $('#esc-service-modal').hide();
        },

        hidePredefinedModal: function() {
            $('#esc-predefined-modal').hide();
        },

        hideAllModals: function() {
            $('.esc-modal').hide();
        },

        toggleResponseTypeFields: function() {
            const responseType = $('#service-response-type').val();
            const $jsonFields = $('.esc-json-fields');
            
            if (responseType === 'json') {
                $jsonFields.show();
            } else {
                $jsonFields.hide();
            }
        },

        editService: function(e) {
            e.preventDefault();
            const serviceId = $(this).data('service-id');
            
            $.ajax({
                url: escAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'esc_get_service',
                    service_id: serviceId,
                    nonce: escAdmin.nonce
                },
                beforeSend: function() {
                    $(e.target).prop('disabled', true).text(escAdmin.strings.loading || 'Lade...');
                },
                success: function(response) {
                    if (response.success) {
                        const service = response.data;
                        
                        $('#esc-modal-title').text('Service bearbeiten');
                        $('#service-id').val(service.id);
                        $('#service-name').val(service.name);
                        $('#service-url').val(service.url);
                        $('#service-category').val(service.category);
                        $('#service-method').val(service.method);
                        $('#service-timeout').val(service.timeout);
                        $('#service-expected-code').val(service.expected_code);
                        $('#service-interval').val(service.check_interval);
                        $('#service-enabled').prop('checked', service.enabled == 1);
                        $('#service-notify').prop('checked', service.notify_email == 1);
                        $('#service-response-type').val(service.response_type || '');
                        $('#service-json-path').val(service.json_path || '');
                        $('#service-check-content').prop('checked', service.check_content == 1);
                        $('#custom-headers').val(service.custom_headers || '');
                        
                        // Toggle JSON fields visibility
                        ESCAdmin.toggleResponseTypeFields();
                        
                        $('#esc-service-modal').show();
                    } else {
                        ESCAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    ESCAdmin.showNotice('Fehler beim Laden des Services.', 'error');
                },
                complete: function() {
                    $(e.target).prop('disabled', false).text('Bearbeiten');
                }
            });
        },

        deleteService: function(e) {
            e.preventDefault();
            
            if (!confirm(escAdmin.strings.confirmDelete || 'Sind Sie sicher?')) {
                return;
            }
            
            const serviceId = $(this).data('service-id');
            
            $.ajax({
                url: escAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'esc_delete_service',
                    service_id: serviceId,
                    nonce: escAdmin.nonce
                },
                beforeSend: function() {
                    $(e.target).prop('disabled', true).text('Lösche...');
                },
                success: function(response) {
                    if (response.success) {
                        $(e.target).closest('.esc-service-item').fadeOut(300, function() {
                            $(this).remove();
                        });
                        ESCAdmin.showNotice(response.data.message, 'success');
                    } else {
                        ESCAdmin.showNotice(response.data.message, 'error');
                        $(e.target).prop('disabled', false).text('Löschen');
                    }
                },
                error: function() {
                    ESCAdmin.showNotice('Fehler beim Löschen des Services.', 'error');
                    $(e.target).prop('disabled', false).text('Löschen');
                }
            });
        },

        testService: function(e) {
            e.preventDefault();
            const serviceId = $(this).data('service-id');
            const $button = $(this);
            const originalText = $button.text();
            
            $.ajax({
                url: escAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'esc_test_service',
                    service_id: serviceId,
                    nonce: escAdmin.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text(escAdmin.strings.testing || 'Teste...');
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        let message = `Status: ${data.status}`;
                        if (data.http_code) {
                            message += ` (${data.http_code})`;
                        }
                        if (data.response_time) {
                            message += ` - ${Math.round(data.response_time)}ms`;
                        }
                        if (data.error_message) {
                            message += ` - Fehler: ${data.error_message}`;
                        }
                        
                        const noticeType = data.status === 'online' ? 'success' : 'warning';
                        ESCAdmin.showNotice(message, noticeType);
                    } else {
                        ESCAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    ESCAdmin.showNotice('Fehler beim Testen des Services.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        saveService: function(e) {
            e.preventDefault();
            
            const formData = $('#esc-service-form').serialize();
            
            $.ajax({
                url: escAdmin.ajaxUrl,
                type: 'POST',
                data: formData + '&action=esc_save_service&nonce=' + escAdmin.nonce,
                beforeSend: function() {
                    $('#esc-save-service').prop('disabled', true).text(escAdmin.strings.saving || 'Speichern...');
                },
                success: function(response) {
                    if (response.success) {
                        ESCAdmin.showNotice(response.data.message, 'success');
                        ESCAdmin.hideServiceModal();
                        // Reload page to show updated service
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        ESCAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    ESCAdmin.showNotice('Fehler beim Speichern des Services.', 'error');
                },
                complete: function() {
                    $('#esc-save-service').prop('disabled', false).text('Speichern');
                }
            });
        },

        addPredefinedServices: function(e) {
            e.preventDefault();
            
            const selectedServices = [];
            $('#esc-predefined-modal input[name="predefined_services[]"]:checked').each(function() {
                selectedServices.push($(this).val());
            });
            
            if (selectedServices.length === 0) {
                ESCAdmin.showNotice('Bitte wählen Sie mindestens einen Service aus.', 'warning');
                return;
            }
            
            $.ajax({
                url: escAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'esc_add_predefined_services',
                    services: selectedServices,
                    nonce: escAdmin.nonce
                },
                beforeSend: function() {
                    $('#esc-add-predefined-services').prop('disabled', true).text('Hinzufügen...');
                },
                success: function(response) {
                    if (response.success) {
                        ESCAdmin.showNotice(response.data.message, 'success');
                        ESCAdmin.hidePredefinedModal();
                        // Reload page to show new services
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        ESCAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    ESCAdmin.showNotice('Fehler beim Hinzufügen der Services.', 'error');
                },
                complete: function() {
                    $('#esc-add-predefined-services').prop('disabled', false).text('Ausgewählte Services hinzufügen');
                }
            });
        },

        forceCheckAll: function(e) {
            e.preventDefault();
            
            $.ajax({
                url: escAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'esc_force_check_all',
                    nonce: escAdmin.nonce
                },
                beforeSend: function() {
                    $('#esc-force-check').prop('disabled', true).text('Prüfe...');
                },
                success: function(response) {
                    if (response.success) {
                        ESCAdmin.showNotice(response.data.message, 'success');
                        // Refresh status overview if present
                        if ($('#esc-status-overview').length) {
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        }
                    } else {
                        ESCAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    ESCAdmin.showNotice('Fehler bei der Status-Prüfung.', 'error');
                },
                complete: function() {
                    $('#esc-force-check').prop('disabled', false).text('Sofortige Prüfung');
                }
            });
        },

        showNotice: function(message, type) {
            type = type || 'info';
            
            const notice = $(`
                <div class="notice notice-${type} is-dismissible esc-notice">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Diese Benachrichtigung ausblenden.</span>
                    </button>
                </div>
            `);
            
            // Remove existing notices
            $('.esc-notice').remove();
            
            // Add new notice
            $('.wrap h1').after(notice);
            
            // Auto-hide success notices
            if (type === 'success') {
                setTimeout(() => {
                    notice.fadeOut();
                }, 5000);
            }
            
            // Handle dismiss button
            notice.find('.notice-dismiss').on('click', function() {
                notice.fadeOut();
            });
        },

        startAutoRefresh: function() {
            // Auto-refresh dashboard stats every 5 minutes
            if ($('.esc-dashboard-stats').length) {
                setInterval(() => {
                    this.refreshDashboardStats();
                }, 300000); // 5 minutes
            }
        },

        refreshDashboardStats: function() {
            // Refresh dashboard statistics
            $.ajax({
                url: escAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'esc_get_dashboard_stats',
                    nonce: escAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const stats = response.data;
                        $('.esc-stat-total .esc-stat-number').text(stats.total);
                        $('.esc-stat-online .esc-stat-number').text(stats.online);
                        $('.esc-stat-offline .esc-stat-number').text(stats.offline);
                    }
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ESCAdmin.init();
    });

    // Export to global scope
    window.ESCAdmin = ESCAdmin;

})(jQuery);
