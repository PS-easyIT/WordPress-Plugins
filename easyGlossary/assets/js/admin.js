/**
 * easyGlossary Admin JavaScript
 */
(function($) {
    'use strict';
    
    var EasyGlossaryAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Import form submission
            $('#glossary-import-form').on('submit', this.handleImport.bind(this));
            
            // File input change
            $('#glossary_file').on('change', this.validateFile.bind(this));
            
            // Settings form enhancements
            this.enhanceSettingsForm();
        },
        
        /**
         * Handle glossary import
         */
        handleImport: function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'easy_glossary_import');
            formData.append('nonce', easyGlossary.nonce);
            
            this.showProgress();
            this.logMessage('Import wird gestartet...', 'info');
            
            $.ajax({
                url: easyGlossary.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            EasyGlossaryAdmin.updateProgress(percentComplete);
                        }
                    }, false);
                    return xhr;
                },
                success: this.handleImportSuccess.bind(this),
                error: this.handleImportError.bind(this)
            });
        },
        
        /**
         * Handle successful import
         */
        handleImportSuccess: function(response) {
            this.updateProgress(100);
            
            if (response.success) {
                this.logMessage('✓ ' + response.data.message, 'success');
                if (response.data.imported) {
                    this.logMessage('Importierte Einträge: ' + response.data.imported, 'info');
                }
                this.showNotice(response.data.message, 'success');
                
                // Reset form after successful import
                setTimeout(() => {
                    $('#glossary-import-form')[0].reset();
                    this.hideProgress();
                }, 2000);
            } else {
                this.logMessage('✗ ' + response.data, 'error');
                this.showNotice(response.data, 'error');
            }
        },
        
        /**
         * Handle import error
         */
        handleImportError: function(xhr, status, error) {
            this.logMessage('✗ Netzwerkfehler: ' + error, 'error');
            this.showNotice('Ein Netzwerkfehler ist aufgetreten', 'error');
        },
        
        /**
         * Validate uploaded file
         */
        validateFile: function(e) {
            const file = e.target.files[0];
            const $feedback = $('#file-feedback');
            
            // Remove existing feedback
            $feedback.remove();
            
            if (!file) {
                return;
            }
            
            let message = '';
            let type = 'info';
            
            // Check file type
            if (!file.name.toLowerCase().endsWith('.csv')) {
                message = 'Warnung: Nur CSV-Dateien werden unterstützt.';
                type = 'warning';
            } else if (file.size > 5 * 1024 * 1024) { // 5MB limit
                message = 'Warnung: Die Datei ist größer als 5MB.';
                type = 'warning';
            } else {
                message = 'Datei bereit für Import: ' + file.name + ' (' + this.formatFileSize(file.size) + ')';
                type = 'success';
            }
            
            // Show feedback
            $(e.target).after(
                '<div id="file-feedback" class="file-feedback ' + type + '">' + message + '</div>'
            );
        },
        
        /**
         * Format file size
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        /**
         * Show progress bar
         */
        showProgress: function() {
            $('#import-progress').show();
            $('.progress-fill').css('width', '0%');
            $('#import-log').empty();
        },
        
        /**
         * Hide progress bar
         */
        hideProgress: function() {
            $('#import-progress').fadeOut();
        },
        
        /**
         * Update progress bar
         */
        updateProgress: function(percentage) {
            $('.progress-fill').css('width', percentage + '%');
        },
        
        /**
         * Log message to import log
         */
        logMessage: function(message, type) {
            type = type || 'info';
            const $log = $('#import-log');
            const timestamp = new Date().toLocaleTimeString();
            
            const $entry = $('<div class="log-entry ' + type + '">')
                .html('<span class="timestamp">[' + timestamp + ']</span> ' + message);
                
            $log.append($entry);
            $log.scrollTop($log[0].scrollHeight);
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible">')
                .html('<p>' + message + '</p>')
                .hide();
                
            $('.wrap h1').after($notice);
            $notice.slideDown();
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
            
            // Manual dismiss
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(() => $notice.remove());
            });
        },
        
        /**
         * Enhance settings form
         */
        enhanceSettingsForm: function() {
            // Add tooltips to form elements
            $('input[name="auto_tooltips"]').closest('label').attr('title', 
                'Aktiviert automatische Tooltip-Erkennung für Glossarbegriffe im Content');
                
            $('select[name="tooltip_style"]').attr('title', 
                'Wählen Sie das Aussehen der Tooltips');
                
            $('input[name="case_sensitive"]').closest('label').attr('title', 
                'Bestimmt ob Groß-/Kleinschreibung bei der Begriffserkennung beachtet wird');
                
            $('input[name="whole_words_only"]').closest('label').attr('title', 
                'Verhindert Markierung von Begriffen innerhalb von Wörtern');
            
            // Live preview for tooltip style
            $('select[name="tooltip_style"]').on('change', function() {
                const style = $(this).val();
                this.showTooltipPreview(style);
            }.bind(this));
        },
        
        /**
         * Show tooltip style preview
         */
        showTooltipPreview: function(style) {
            // Remove existing preview
            $('.tooltip-preview').remove();
            
            // Create preview
            const $preview = $('<div class="tooltip-preview">')
                .html('<div class="glossary-tooltip-container ' + style + ' show">' +
                      '<div class="tooltip-title">Beispiel Begriff</div>' +
                      '<div class="tooltip-content">Dies ist eine Beispiel-Beschreibung für den Tooltip.</div>' +
                      '<a href="#" class="tooltip-link">Mehr erfahren →</a>' +
                      '</div>');
                      
            $('select[name="tooltip_style"]').after($preview);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                $preview.fadeOut(() => $preview.remove());
            }, 3000);
        },
        
        /**
         * Initialize admin tooltips
         */
        initTooltips: function() {
            // Simple tooltip functionality for admin
            $(document).on('mouseenter', '[title], [data-tooltip]', function() {
                const $this = $(this);
                const text = $this.attr('title') || $this.attr('data-tooltip');
                
                if (!text) return;
                
                // Remove title to prevent default browser tooltip
                $this.removeAttr('title').attr('data-original-title', text);
                
                // Create custom tooltip
                const $tooltip = $('<div class="admin-tooltip-popup">')
                    .text(text)
                    .css({
                        position: 'absolute',
                        background: '#1d2327',
                        color: 'white',
                        padding: '5px 10px',
                        borderRadius: '4px',
                        fontSize: '12px',
                        zIndex: 10000,
                        pointerEvents: 'none'
                    });
                    
                $('body').append($tooltip);
                
                // Position tooltip
                const offset = $this.offset();
                $tooltip.css({
                    top: offset.top - $tooltip.outerHeight() - 5,
                    left: offset.left + ($this.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                });
            });
            
            $(document).on('mouseleave', '[data-original-title]', function() {
                const $this = $(this);
                const originalTitle = $this.attr('data-original-title');
                
                // Restore original title
                if (originalTitle) {
                    $this.attr('title', originalTitle).removeAttr('data-original-title');
                }
                
                // Remove tooltip
                $('.admin-tooltip-popup').remove();
            });
        },
        
        /**
         * Initialize dashboard widgets
         */
        initDashboard: function() {
            // Animate statistics on load
            $('.stat-number').each(function() {
                const $this = $(this);
                const target = parseInt($this.text());
                let current = 0;
                const increment = target / 50;
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    $this.text(Math.floor(current));
                }, 50);
            });
            
            // Add click tracking for quick actions
            $('.dashboard-widget .button').on('click', function() {
                const action = $(this).find('span:last').text();
                console.log('Dashboard action clicked:', action);
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        EasyGlossaryAdmin.init();
        
        // Initialize dashboard if on dashboard page
        if ($('.easy-glossary-dashboard').length) {
            EasyGlossaryAdmin.initDashboard();
        }
    });
    
})(jQuery);

/* Additional CSS for JavaScript enhancements */
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        .file-feedback {
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .file-feedback.success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #a3cfbb;
        }
        
        .file-feedback.warning {
            background: #fff3cd;
            color: #664d03;
            border: 1px solid #ffecb5;
        }
        
        .file-feedback.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f1aeb5;
        }
        
        .log-entry {
            margin-bottom: 5px;
            padding: 3px 0;
        }
        
        .log-entry.success {
            color: #0f5132;
        }
        
        .log-entry.error {
            color: #721c24;
        }
        
        .log-entry.info {
            color: #055160;
        }
        
        .log-entry .timestamp {
            color: #6c757d;
            font-size: 11px;
        }
        
        .tooltip-preview {
            margin-top: 10px;
            position: relative;
        }
        
        .admin-tooltip-popup {
            white-space: nowrap;
            max-width: 200px;
            word-wrap: break-word;
            white-space: normal;
        }
    `;
    document.head.appendChild(style);
});
