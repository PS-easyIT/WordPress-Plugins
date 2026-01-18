/**
 * easyGlossary - Tooltip System
 * 
 * @package easyGlossary
 * @since 1.2.0
 */

(function($) {
    'use strict';
    
    // Tooltip Manager
    const TooltipManager = {
        activeTooltip: null,
        tooltipCache: {},
        
        /**
         * Initialisierung
         */
        init: function() {
            this.bindEvents();
            this.setupAccessibility();
        },
        
        /**
         * Event-Binding
         */
        bindEvents: function() {
            const self = this;
            const trigger = easyGlossaryTooltips.trigger;
            const isMobile = easyGlossaryTooltips.isMobile;
            
            // Delegierte Events für dynamisch geladene Links
            $(document).on('mouseenter', '.easy-glossary-link.has-tooltip', function(e) {
                if (trigger === 'hover' || (trigger === 'both' && !isMobile)) {
                    self.showTooltip($(this));
                }
            });
            
            $(document).on('mouseleave', '.easy-glossary-link.has-tooltip', function(e) {
                if (trigger === 'hover' || trigger === 'both') {
                    self.hideTooltip($(this));
                }
            });
            
            $(document).on('click', '.easy-glossary-link.has-tooltip', function(e) {
                if (trigger === 'click' || (trigger === 'both' && isMobile)) {
                    e.preventDefault();
                    self.toggleTooltip($(this));
                }
            });
            
            // Schließe Tooltip bei Klick außerhalb
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.easy-glossary-link.has-tooltip, .easy-glossary-tooltip').length) {
                    self.hideAllTooltips();
                }
            });
            
            // Schließe Tooltip bei ESC
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.hideAllTooltips();
                }
            });
            
            // Tooltip-Links klickbar machen
            $(document).on('click', '.tooltip-link', function(e) {
                e.stopPropagation();
            });
        },
        
        /**
         * Accessibility Setup
         */
        setupAccessibility: function() {
            $('.easy-glossary-link.has-tooltip').each(function() {
                $(this).attr({
                    'role': 'button',
                    'aria-haspopup': 'true',
                    'aria-expanded': 'false',
                    'tabindex': '0'
                });
            });
            
            // Keyboard-Navigation
            $(document).on('keydown', '.easy-glossary-link.has-tooltip', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    TooltipManager.toggleTooltip($(this));
                }
            });
        },
        
        /**
         * Zeige Tooltip
         */
        showTooltip: function($link) {
            const self = this;
            
            // Verstecke andere Tooltips
            this.hideAllTooltips();
            
            // Erstelle oder hole Tooltip
            let $tooltip = $link.data('tooltip-element');
            
            if (!$tooltip) {
                $tooltip = this.createTooltip($link);
                $link.data('tooltip-element', $tooltip);
            }
            
            // Lade Content
            if (easyGlossaryTooltips.ajaxLoading) {
                this.loadTooltipContent($link, $tooltip);
            }
            
            // Positioniere und zeige Tooltip
            this.positionTooltip($link, $tooltip);
            
            setTimeout(function() {
                $tooltip.addClass('show');
                $link.attr('aria-expanded', 'true').addClass('active');
            }, 10);
            
            this.activeTooltip = $tooltip;
        },
        
        /**
         * Verstecke Tooltip
         */
        hideTooltip: function($link) {
            const $tooltip = $link.data('tooltip-element');
            
            if ($tooltip) {
                $tooltip.removeClass('show');
                $link.attr('aria-expanded', 'false').removeClass('active');
                
                setTimeout(function() {
                    if (!$tooltip.hasClass('show')) {
                        $tooltip.remove();
                        $link.removeData('tooltip-element');
                    }
                }, 300);
            }
            
            if (this.activeTooltip === $tooltip) {
                this.activeTooltip = null;
            }
        },
        
        /**
         * Toggle Tooltip
         */
        toggleTooltip: function($link) {
            const $tooltip = $link.data('tooltip-element');
            
            if ($tooltip && $tooltip.hasClass('show')) {
                this.hideTooltip($link);
            } else {
                this.showTooltip($link);
            }
        },
        
        /**
         * Verstecke alle Tooltips
         */
        hideAllTooltips: function() {
            $('.easy-glossary-tooltip').removeClass('show');
            $('.easy-glossary-link.has-tooltip').attr('aria-expanded', 'false').removeClass('active');
            
            setTimeout(function() {
                $('.easy-glossary-tooltip:not(.show)').remove();
                $('.easy-glossary-link.has-tooltip').removeData('tooltip-element');
            }, 300);
            
            this.activeTooltip = null;
        },
        
        /**
         * Erstelle Tooltip-Element
         */
        createTooltip: function($link) {
            const termId = $link.data('term-id');
            const title = $link.attr('title') || $link.text();
            const content = $link.data('tooltip-content');
            
            let tooltipHtml = '<div class="easy-glossary-tooltip" role="tooltip">';
            
            if (easyGlossaryTooltips.ajaxLoading) {
                tooltipHtml += '<div class="tooltip-loading">' + easyGlossaryTooltips.strings.loading + '</div>';
            } else {
                tooltipHtml += '<div class="tooltip-title">' + this.escapeHtml(title) + '</div>';
                
                if (content) {
                    tooltipHtml += '<div class="tooltip-content">' + this.escapeHtml(content) + '</div>';
                }
                
                tooltipHtml += '<div class="tooltip-footer">';
                tooltipHtml += '<a href="' + $link.attr('href') + '" class="tooltip-link">';
                tooltipHtml += easyGlossaryTooltips.strings.readMore;
                tooltipHtml += ' <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
                tooltipHtml += '</a>';
                tooltipHtml += '</div>';
            }
            
            tooltipHtml += '</div>';
            
            const $tooltip = $(tooltipHtml);
            $('body').append($tooltip);
            
            return $tooltip;
        },
        
        /**
         * Lade Tooltip-Content per AJAX
         */
        loadTooltipContent: function($link, $tooltip) {
            const self = this;
            const termId = $link.data('term-id');
            
            // Aus Cache?
            if (this.tooltipCache[termId]) {
                this.renderTooltipContent($tooltip, this.tooltipCache[termId]);
                return;
            }
            
            // AJAX-Request
            $.ajax({
                url: easyGlossaryTooltips.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_glossary_tooltip',
                    nonce: easyGlossaryTooltips.nonce,
                    term_id: termId
                },
                success: function(response) {
                    if (response.success) {
                        self.tooltipCache[termId] = response.data;
                        self.renderTooltipContent($tooltip, response.data);
                    } else {
                        $tooltip.html('<div class="tooltip-error">' + easyGlossaryTooltips.strings.error + '</div>');
                    }
                },
                error: function() {
                    $tooltip.html('<div class="tooltip-error">' + easyGlossaryTooltips.strings.error + '</div>');
                }
            });
        },
        
        /**
         * Rendere Tooltip-Content
         */
        renderTooltipContent: function($tooltip, data) {
            let html = '<div class="tooltip-title">' + this.escapeHtml(data.title) + '</div>';
            html += '<div class="tooltip-content">' + data.content + '</div>';
            
            if (!easyGlossaryTooltips.externalLinks || !data.external_link) {
                html += '<div class="tooltip-footer">';
                html += '<a href="' + data.url + '" class="tooltip-link">';
                html += easyGlossaryTooltips.strings.readMore;
                html += ' <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
                html += '</a>';
                html += '</div>';
            }
            
            $tooltip.html(html);
        },
        
        /**
         * Positioniere Tooltip
         */
        positionTooltip: function($link, $tooltip) {
            const linkOffset = $link.offset();
            const linkHeight = $link.outerHeight();
            const linkWidth = $link.outerWidth();
            const tooltipHeight = $tooltip.outerHeight();
            const tooltipWidth = $tooltip.outerWidth();
            const windowHeight = $(window).height();
            const windowWidth = $(window).width();
            const scrollTop = $(window).scrollTop();
            
            let top, left;
            let position = 'top';
            
            // Prüfe ob Platz oben ist
            if (linkOffset.top - scrollTop > tooltipHeight + 20) {
                // Tooltip über dem Link
                top = linkOffset.top - tooltipHeight - 10;
                position = 'top';
            } else {
                // Tooltip unter dem Link
                top = linkOffset.top + linkHeight + 10;
                position = 'bottom';
            }
            
            // Horizontal zentrieren
            left = linkOffset.left + (linkWidth / 2) - (tooltipWidth / 2);
            
            // Prüfe Viewport-Grenzen
            if (left < 10) {
                left = 10;
            } else if (left + tooltipWidth > windowWidth - 10) {
                left = windowWidth - tooltipWidth - 10;
            }
            
            $tooltip.css({
                top: top + 'px',
                left: left + 'px'
            }).addClass('position-' + position);
        },
        
        /**
         * HTML escapen
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };
    
    // Initialisierung bei DOM-Ready
    $(document).ready(function() {
        TooltipManager.init();
    });
    
    // Globaler Zugriff für Debugging
    window.easyGlossaryTooltipManager = TooltipManager;
    
})(jQuery);
