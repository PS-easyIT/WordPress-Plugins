/**
 * easySTATUSCheck Frontend JavaScript
 */

(function($) {
    'use strict';

    const ESCFrontend = {
        
        init: function() {
            this.bindEvents();
            this.loadStatusData();
            this.startAutoRefresh();
        },

        bindEvents: function() {
            // Refresh button
            $('.esc-refresh-btn').on('click', this.handleRefresh.bind(this));
            
            // Toggle details
            $(document).on('click', '.esc-toggle-details', this.toggleDetails);
            
            // Handle visibility change (auto-refresh when tab becomes visible)
            $(document).on('visibilitychange', this.handleVisibilityChange.bind(this));
        },

        handleRefresh: function(e) {
            e.preventDefault();
            this.loadStatusData(true);
        },

        toggleDetails: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $serviceItem = $button.closest('.esc-service-item');
            const $details = $serviceItem.find('.esc-service-details');
            const $showText = $button.find('.esc-details-text');
            const $hideText = $button.find('.esc-hide-text');
            
            if ($details.is(':visible')) {
                $details.slideUp(300);
                $showText.show();
                $hideText.hide();
            } else {
                $details.slideDown(300);
                $showText.hide();
                $hideText.show();
            }
        },

        loadStatusData: function(force = false) {
            const containers = $('.esc-status-display');
            
            containers.each((index, container) => {
                this.loadContainerData($(container), force);
            });
        },

        loadContainerData: function($container, force = false) {
            const category = $container.data('category') || 'all';
            const showUptime = $container.data('show-uptime') || 'true';
            const showResponseTime = $container.data('show-response-time') || 'true';
            
            console.log('ESC Frontend: Loading status data', {
                category: category,
                force: force,
                ajaxUrl: escFrontend.ajaxUrl
            });
            
            // Show loading state
            if (force) {
                this.showLoading($container);
                this.setRefreshButtonLoading($container, true);
            }
            
            // Check if escFrontend is defined
            if (typeof escFrontend === 'undefined') {
                console.error('ESC Frontend: escFrontend object not defined!');
                this.showError($container, 'Konfigurationsfehler: escFrontend nicht definiert');
                return;
            }
            
            $.ajax({
                url: escFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'esc_get_status_data',
                    category: category,
                    show_uptime: showUptime,
                    show_response_time: showResponseTime,
                    nonce: escFrontend.nonce
                },
                success: (response) => {
                    console.log('ESC Frontend: AJAX success', response);
                    if (response.success) {
                        this.renderStatusData($container, response.data);
                        this.updateTimestamp($container, response.data.timestamp);
                    } else {
                        console.error('ESC Frontend: Response error', response);
                        this.showError($container, response.data?.message || 'Unbekannter Fehler');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('ESC Frontend: AJAX error', {xhr, status, error});
                    this.showError($container, escFrontend.strings.error + ': ' + error);
                },
                complete: () => {
                    this.setRefreshButtonLoading($container, false);
                }
            });
        },

        showLoading: function($container) {
            const $servicesContainer = $container.find('.esc-services-container');
            $servicesContainer.html(`
                <div class="esc-loading">
                    <div class="esc-loading-spinner"></div>
                    <span>${escFrontend.strings.loading}</span>
                </div>
            `);
        },

        showError: function($container, message) {
            const $servicesContainer = $container.find('.esc-services-container');
            $servicesContainer.html(`
                <div class="esc-empty-state">
                    <h3>⚠️ ${escFrontend.strings.error}</h3>
                    <p>${message}</p>
                </div>
            `);
        },

        renderStatusData: function($container, data) {
            const $servicesContainer = $container.find('.esc-services-container');
            
            if (data.html) {
                $servicesContainer.html(data.html);
                this.animateServices($servicesContainer);
            } else {
                $servicesContainer.html(`
                    <div class="esc-empty-state">
                        <h3>📊 Keine Services gefunden</h3>
                        <p>Es sind noch keine Services zur Überwachung konfiguriert.</p>
                    </div>
                `);
            }
        },

        animateServices: function($container) {
            const $services = $container.find('.esc-service-item');
            
            $services.each((index, service) => {
                $(service).css({
                    opacity: 0,
                    transform: 'translateY(20px)'
                }).delay(index * 100).animate({
                    opacity: 1
                }, 300).css({
                    transform: 'translateY(0)'
                });
            });
        },

        updateTimestamp: function($container, timestamp) {
            const $timestamp = $container.find('.esc-timestamp');
            if ($timestamp.length && timestamp) {
                $timestamp.text(this.formatTimestamp(timestamp));
            }
        },

        formatTimestamp: function(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = Math.abs(now - date) / 1000; // difference in seconds
            
            if (diff < 60) {
                return `vor ${Math.floor(diff)} Sekunden`;
            } else if (diff < 3600) {
                return `vor ${Math.floor(diff / 60)} Minuten`;
            } else if (diff < 86400) {
                return `vor ${Math.floor(diff / 3600)} Stunden`;
            } else {
                return date.toLocaleDateString('de-DE', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        },

        setRefreshButtonLoading: function($container, loading) {
            const $button = $container.find('.esc-refresh-btn');
            const $icon = $button.find('.esc-refresh-icon');
            
            if (loading) {
                $button.addClass('loading').prop('disabled', true);
                $icon.addClass('loading');
            } else {
                $button.removeClass('loading').prop('disabled', false);
                $icon.removeClass('loading');
            }
        },

        startAutoRefresh: function() {
            // Auto-refresh based on container settings
            $('.esc-status-display').each((index, container) => {
                const $container = $(container);
                const refreshInterval = parseInt($container.data('refresh')) || 300; // default 5 minutes
                
                if (refreshInterval > 0) {
                    setInterval(() => {
                        // Only refresh if tab is visible
                        if (!document.hidden) {
                            this.loadContainerData($container, false);
                        }
                    }, refreshInterval * 1000);
                }
            });
        },

        handleVisibilityChange: function() {
            // Refresh data when tab becomes visible again
            if (!document.hidden) {
                setTimeout(() => {
                    this.loadStatusData(false);
                }, 1000);
            }
        },

        // Utility functions
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Service status color theme
        getStatusColor: function(status) {
            const colors = {
                online: '#22c55e',
                offline: '#ef4444',
                warning: '#f59e0b',
                unknown: '#6b7280'
            };
            return colors[status] || colors.unknown;
        },

        // Create notification
        showNotification: function(message, type = 'info', duration = 5000) {
            const notification = $(`
                <div class="esc-notification esc-notification-${type}">
                    <span class="esc-notification-message">${message}</span>
                    <button class="esc-notification-close">&times;</button>
                </div>
            `);
            
            // Add to page
            $('body').append(notification);
            
            // Position and show
            notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                background: type === 'error' ? '#ef4444' : '#22c55e',
                color: 'white',
                padding: '12px 16px',
                borderRadius: '6px',
                zIndex: 10000,
                display: 'flex',
                alignItems: 'center',
                gap: '10px',
                boxShadow: '0 4px 12px rgba(0,0,0,0.3)',
                transform: 'translateX(100%)',
                transition: 'transform 0.3s ease'
            });
            
            // Animate in
            setTimeout(() => {
                notification.css('transform', 'translateX(0)');
            }, 100);
            
            // Auto-hide
            if (duration > 0) {
                setTimeout(() => {
                    this.hideNotification(notification);
                }, duration);
            }
            
            // Close button
            notification.find('.esc-notification-close').on('click', () => {
                this.hideNotification(notification);
            });
        },

        hideNotification: function($notification) {
            $notification.css('transform', 'translateX(100%)');
            setTimeout(() => {
                $notification.remove();
            }, 300);
        },

        // Handle connection status
        handleConnectionStatus: function() {
            const updateOnlineStatus = () => {
                if (navigator.onLine) {
                    this.loadStatusData(false);
                } else {
                    $('.esc-status-display').each((index, container) => {
                        this.showError($(container), 'Keine Internetverbindung');
                    });
                }
            };

            window.addEventListener('online', updateOnlineStatus);
            window.addEventListener('offline', updateOnlineStatus);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        console.log('ESC Frontend: Initializing...');
        try {
            ESCFrontend.init();
            ESCFrontend.handleConnectionStatus();
            console.log('ESC Frontend: Initialized successfully');
        } catch (error) {
            console.error('ESC Frontend: Initialization error', error);
        }
    });

    // Handle dynamic content (for AJAX-loaded content)
    $(document).on('DOMNodeInserted', '.esc-status-display', function() {
        if (!$(this).data('esc-initialized')) {
            $(this).data('esc-initialized', true);
            setTimeout(() => {
                ESCFrontend.loadContainerData($(this), false);
            }, 100);
        }
    });

    // Export to global scope
    window.ESCFrontend = ESCFrontend;

})(jQuery);
