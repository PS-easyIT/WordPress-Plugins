/**
 * easyGlossary Frontend JavaScript
 */
(function($) {
    'use strict';
    
    var EasyGlossaryFrontend = {
        
        /**
         * Current tooltip element
         */
        currentTooltip: null,
        
        /**
         * Tooltip container
         */
        tooltipContainer: null,
        
        /**
         * Settings
         */
        settings: {
            delay: 500,
            hideDelay: 300,
            maxWidth: 300,
            offset: 10
        },
        
        /**
         * Initialize frontend functionality
         */
        init: function() {
            this.createTooltipContainer();
            this.bindEvents();
            this.initAccessibility();
        },
        
        /**
         * Create tooltip container
         */
        createTooltipContainer: function() {
            this.tooltipContainer = $('<div>')
                .addClass('glossary-tooltip-container')
                .appendTo('body');
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Mouse events for tooltips
            $(document).on('mouseenter', '.easy-glossary-tooltip', function(e) {
                self.showTooltip($(this), e);
            });
            
            $(document).on('mouseleave', '.easy-glossary-tooltip', function() {
                self.hideTooltip();
            });
            
            // Click events for mobile
            $(document).on('click', '.easy-glossary-tooltip', function(e) {
                if (self.isMobile()) {
                    e.preventDefault();
                    self.toggleTooltip($(this), e);
                }
            });
            
            // Touch events for better mobile support
            $(document).on('touchstart', '.easy-glossary-tooltip', function(e) {
                if (!self.isMobile()) return;
                
                e.preventDefault();
                self.toggleTooltip($(this), e);
            });
            
            // Hide tooltip when clicking outside
            $(document).on('click touchstart', function(e) {
                if (!$(e.target).closest('.easy-glossary-tooltip, .glossary-tooltip-container').length) {
                    self.hideTooltip();
                }
            });
            
            // Hide tooltip on scroll
            $(window).on('scroll', function() {
                if (self.currentTooltip) {
                    self.hideTooltip();
                }
            });
            
            // Reposition on window resize
            $(window).on('resize', function() {
                if (self.currentTooltip && self.tooltipContainer.hasClass('show')) {
                    self.positionTooltip(self.currentTooltip);
                }
            });
            
            // Keyboard navigation
            $(document).on('keydown', '.easy-glossary-tooltip', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    self.toggleTooltip($(this), e);
                }
                
                if (e.key === 'Escape') {
                    self.hideTooltip();
                }
            });
        },
        
        /**
         * Show tooltip
         */
        showTooltip: function($element, event) {
            var self = this;
            
            // Clear any existing timeout
            if (this.hideTimeout) {
                clearTimeout(this.hideTimeout);
                this.hideTimeout = null;
            }
            
            // Don't show if already showing for this element
            if (this.currentTooltip && this.currentTooltip[0] === $element[0]) {
                return;
            }
            
            // Set current tooltip
            this.currentTooltip = $element;
            
            // Get tooltip data
            var title = $element.attr('data-title') || $element.text();
            var content = $element.attr('data-content') || '';
            var link = $element.attr('data-link') || '';
            
            // Build tooltip content
            var tooltipHTML = this.buildTooltipHTML(title, content, link);
            
            // Update container
            this.tooltipContainer
                .removeClass('dark light')
                .addClass(this.getTooltipTheme())
                .html(tooltipHTML);
            
            // Position and show tooltip
            this.positionTooltip($element);
            
            // Show with delay for better UX
            this.showTimeout = setTimeout(function() {
                self.tooltipContainer.addClass('show');
                self.announceToScreenReader(title, content);
            }, this.isMobile() ? 0 : this.settings.delay);
            
            // Track analytics if enabled
            this.trackTooltipView($element);
        },
        
        /**
         * Hide tooltip
         */
        hideTooltip: function() {
            var self = this;
            
            // Clear show timeout
            if (this.showTimeout) {
                clearTimeout(this.showTimeout);
                this.showTimeout = null;
            }
            
            // Hide with delay
            this.hideTimeout = setTimeout(function() {
                self.tooltipContainer.removeClass('show');
                self.currentTooltip = null;
            }, this.settings.hideDelay);
        },
        
        /**
         * Toggle tooltip (for mobile/keyboard)
         */
        toggleTooltip: function($element, event) {
            if (this.currentTooltip && this.currentTooltip[0] === $element[0] && 
                this.tooltipContainer.hasClass('show')) {
                this.hideTooltip();
            } else {
                this.showTooltip($element, event);
            }
        },
        
        /**
         * Build tooltip HTML
         */
        buildTooltipHTML: function(title, content, link) {
            var html = '';
            
            // Title
            if (title) {
                html += '<div class="tooltip-title">' + this.escapeHtml(title) + '</div>';
            }
            
            // Content
            if (content) {
                html += '<div class="tooltip-content">' + this.escapeHtml(content) + '</div>';
            }
            
            // Link
            if (link) {
                html += '<a href="' + this.escapeHtml(link) + '" class="tooltip-link" ' +
                       'onclick="EasyGlossaryFrontend.trackTooltipClick(this)">' +
                       'Mehr erfahren <span class="dashicons dashicons-external"></span></a>';
            }
            
            return html;
        },
        
        /**
         * Position tooltip
         */
        positionTooltip: function($element) {
            var elementOffset = $element.offset();
            var elementWidth = $element.outerWidth();
            var elementHeight = $element.outerHeight();
            
            var tooltipWidth = this.tooltipContainer.outerWidth();
            var tooltipHeight = this.tooltipContainer.outerHeight();
            
            var windowWidth = $(window).width();
            var windowHeight = $(window).height();
            var scrollTop = $(window).scrollTop();
            
            // Calculate initial position (above element)
            var left = elementOffset.left + (elementWidth / 2) - (tooltipWidth / 2);
            var top = elementOffset.top - tooltipHeight - this.settings.offset;
            
            // Horizontal positioning adjustments
            if (left < 10) {
                left = 10;
            } else if (left + tooltipWidth > windowWidth - 10) {
                left = windowWidth - tooltipWidth - 10;
            }
            
            // Vertical positioning adjustments
            if (top < scrollTop + 10) {
                // Position below element if no space above
                top = elementOffset.top + elementHeight + this.settings.offset;
                this.tooltipContainer.addClass('below');
            } else {
                this.tooltipContainer.removeClass('below');
            }
            
            // Final boundary check
            if (top + tooltipHeight > scrollTop + windowHeight - 10) {
                top = scrollTop + windowHeight - tooltipHeight - 10;
            }
            
            // Apply positioning
            this.tooltipContainer.css({
                left: Math.max(10, left) + 'px',
                top: Math.max(scrollTop + 10, top) + 'px'
            });
        },
        
        /**
         * Get tooltip theme from settings or element
         */
        getTooltipTheme: function() {
            // Check for theme in body class or return default
            if ($('body').hasClass('dark-theme')) {
                return 'dark';
            } else if ($('body').hasClass('light-theme')) {
                return 'light';
            }
            return ''; // default theme
        },
        
        /**
         * Check if mobile device
         */
        isMobile: function() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                   $(window).width() < 768;
        },
        
        /**
         * Escape HTML characters
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            // Add ARIA attributes to tooltip triggers
            $('.easy-glossary-tooltip').each(function() {
                var $this = $(this);
                
                // Add role and tabindex for keyboard navigation
                $this.attr({
                    'role': 'button',
                    'tabindex': '0',
                    'aria-haspopup': 'true',
                    'aria-expanded': 'false',
                    'aria-describedby': 'glossary-tooltip-' + Math.random().toString(36).substr(2, 9)
                });
            });
            
            // Add live region for screen reader announcements
            if (!$('#glossary-live-region').length) {
                $('<div>')
                    .attr({
                        'id': 'glossary-live-region',
                        'aria-live': 'polite',
                        'aria-atomic': 'true'
                    })
                    .css({
                        position: 'absolute',
                        left: '-10000px',
                        width: '1px',
                        height: '1px',
                        overflow: 'hidden'
                    })
                    .appendTo('body');
            }
        },
        
        /**
         * Announce content to screen readers
         */
        announceToScreenReader: function(title, content) {
            var announcement = title;
            if (content) {
                announcement += '. ' + content;
            }
            
            $('#glossary-live-region').text(announcement);
        },
        
        /**
         * Track tooltip view for analytics
         */
        trackTooltipView: function($element) {
            var term = $element.attr('data-title') || $element.text();
            
            // Google Analytics tracking
            if (typeof gtag !== 'undefined') {
                gtag('event', 'glossary_tooltip_view', {
                    'term': term,
                    'event_category': 'glossary',
                    'event_label': term
                });
            }
            
            // Custom tracking
            $(document).trigger('glossary:tooltip:view', {
                term: term,
                element: $element
            });
        },
        
        /**
         * Track tooltip click for analytics
         */
        trackTooltipClick: function(link) {
            var term = $(link).closest('.glossary-tooltip-container').find('.tooltip-title').text();
            
            // Google Analytics tracking
            if (typeof gtag !== 'undefined') {
                gtag('event', 'glossary_tooltip_click', {
                    'term': term,
                    'event_category': 'glossary',
                    'event_label': term
                });
            }
            
            // Custom tracking
            $(document).trigger('glossary:tooltip:click', {
                term: term,
                link: link.href
            });
        },
        
        /**
         * Lazy load tooltip content (for performance)
         */
        lazyLoadTooltipContent: function($element) {
            var termId = $element.attr('data-term-id');
            
            if (!termId || $element.attr('data-content')) {
                return Promise.resolve();
            }
            
            return $.ajax({
                url: easyGlossary.ajaxurl,
                type: 'POST',
                data: {
                    action: 'easy_glossary_get_content',
                    term_id: termId,
                    nonce: easyGlossary.nonce
                }
            }).done(function(response) {
                if (response.success) {
                    $element.attr('data-content', response.data.content);
                    $element.attr('data-link', response.data.link);
                }
            });
        },
        
        /**
         * Destroy all tooltips
         */
        destroy: function() {
            // Remove event handlers
            $(document).off('.easy-glossary');
            $(window).off('.easy-glossary');
            
            // Remove tooltip container
            if (this.tooltipContainer) {
                this.tooltipContainer.remove();
                this.tooltipContainer = null;
            }
            
            // Clear timeouts
            if (this.showTimeout) {
                clearTimeout(this.showTimeout);
            }
            if (this.hideTimeout) {
                clearTimeout(this.hideTimeout);
            }
            
            // Reset current tooltip
            this.currentTooltip = null;
        }
    };
    
    // Make trackTooltipClick available globally
    window.EasyGlossaryFrontend = EasyGlossaryFrontend;
    
    // Initialize when document is ready
    $(document).ready(function() {
        EasyGlossaryFrontend.init();
    });
    
    // Reinitialize on AJAX content loads (for compatibility with other plugins)
    $(document).on('DOMContentLoaded ajaxComplete', function() {
        if ($('.easy-glossary-tooltip').length) {
            EasyGlossaryFrontend.initAccessibility();
        }
    });
    
})(jQuery);

// CSS for tooltip positioning adjustments
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        .glossary-tooltip-container.below::before {
            top: auto;
            bottom: -8px;
            border-top: 8px solid #fff;
            border-bottom: none;
        }
        
        .glossary-tooltip-container.below::after {
            top: auto;
            bottom: -9px;
            border-top: 8px solid #c3c4c7;
            border-bottom: none;
        }
        
        .glossary-tooltip-container.dark.below::before {
            border-top-color: #1d2327;
        }
        
        .glossary-tooltip-container.dark.below::after {
            border-top-color: #50575e;
        }
        
        .glossary-tooltip-container.light.below::before {
            border-top-color: #f6f7f7;
        }
        
        .glossary-tooltip-container.light.below::after {
            border-top-color: #dcdcde;
        }
        
        @media (max-width: 768px) {
            .glossary-tooltip-container {
                position: fixed !important;
                bottom: 20px !important;
                left: 10px !important;
                right: 10px !important;
                top: auto !important;
                max-width: none !important;
                width: auto !important;
            }
            
            .glossary-tooltip-container::before,
            .glossary-tooltip-container::after {
                display: none;
            }
        }
    `;
    document.head.appendChild(style);
});
