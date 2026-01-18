<?php
/**
 * Glossary Tooltip System
 * 
 * Hover-Tooltips und Mobile-optimierte Click-to-Show Variante
 * 
 * @package easyGlossary
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Easy_Glossary_Tooltips {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Frontend-Assets laden
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        
        // AJAX-Endpoint für Tooltip-Content
        add_action( 'wp_ajax_get_glossary_tooltip', array( $this, 'ajax_get_tooltip' ) );
        add_action( 'wp_ajax_nopriv_get_glossary_tooltip', array( $this, 'ajax_get_tooltip' ) );
    }
    
    /**
     * Lade Frontend-Assets
     */
    public function enqueue_assets() {
        // Nur laden wenn Tooltips aktiviert sind
        $settings = $this->get_settings();
        
        if ( empty( $settings['enable_tooltips'] ) ) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'easy-glossary-tooltips',
            EASY_GLOSSARY_ASSETS_URL . 'css/tooltips.css',
            array(),
            EASY_GLOSSARY_VERSION
        );
        
        // Inline-CSS für Tooltip-Style
        $this->add_inline_tooltip_styles( $settings['tooltip_style'] );
        
        // JavaScript
        wp_enqueue_script(
            'easy-glossary-tooltips',
            EASY_GLOSSARY_ASSETS_URL . 'js/tooltips.js',
            array( 'jquery' ),
            EASY_GLOSSARY_VERSION,
            true
        );
        
        // Lokalisierung
        wp_localize_script( 'easy-glossary-tooltips', 'easyGlossaryTooltips', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'glossary_tooltip_nonce' ),
            'trigger' => $settings['tooltip_trigger'],
            'ajaxLoading' => $settings['ajax_tooltips'],
            'externalLinks' => $settings['external_links_in_tooltip'],
            'isMobile' => wp_is_mobile(),
            'strings' => array(
                'loading' => __( 'Lädt...', 'easy-glossary' ),
                'error' => __( 'Fehler beim Laden', 'easy-glossary' ),
                'readMore' => __( 'Mehr erfahren', 'easy-glossary' ),
                'externalLink' => __( 'Externe Ressource', 'easy-glossary' )
            )
        ) );
    }
    
    /**
     * Füge Inline-Styles für Tooltip-Design hinzu
     */
    private function add_inline_tooltip_styles( $style ) {
        $css = '';
        
        switch ( $style ) {
            case 'dark':
                $css = '
                .easy-glossary-tooltip {
                    background: #1a1a1a;
                    color: #ffffff;
                    border-color: #333;
                }
                .easy-glossary-tooltip::before {
                    border-top-color: #1a1a1a;
                }
                .tooltip-link {
                    color: #60a5fa;
                }
                ';
                break;
                
            case 'light':
                $css = '
                .easy-glossary-tooltip {
                    background: #ffffff;
                    color: #1a1a1a;
                    border: 1px solid #e5e7eb;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                }
                .easy-glossary-tooltip::before {
                    border-top-color: #ffffff;
                }
                .tooltip-link {
                    color: #2563eb;
                }
                ';
                break;
                
            case 'minimal':
                $css = '
                .easy-glossary-tooltip {
                    background: #f9fafb;
                    color: #374151;
                    border: 1px solid #d1d5db;
                    box-shadow: none;
                    padding: 0.75rem;
                }
                .easy-glossary-tooltip::before {
                    border-top-color: #f9fafb;
                }
                .tooltip-title {
                    font-size: 0.9rem;
                    margin-bottom: 0.25rem;
                }
                .tooltip-content {
                    font-size: 0.85rem;
                }
                ';
                break;
                
            default: // 'default'
                $css = '
                .easy-glossary-tooltip {
                    background: #2563eb;
                    color: #ffffff;
                }
                .easy-glossary-tooltip::before {
                    border-top-color: #2563eb;
                }
                .tooltip-link {
                    color: #ffffff;
                    text-decoration: underline;
                }
                ';
                break;
        }
        
        if ( ! empty( $css ) ) {
            wp_add_inline_style( 'easy-glossary-tooltips', $css );
        }
    }
    
    /**
     * AJAX: Hole Tooltip-Content
     */
    public function ajax_get_tooltip() {
        check_ajax_referer( 'glossary_tooltip_nonce', 'nonce' );
        
        $term_id = isset( $_POST['term_id'] ) ? intval( $_POST['term_id'] ) : 0;
        
        if ( ! $term_id ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige Begriff-ID', 'easy-glossary' ) ) );
        }
        
        $term = get_post( $term_id );
        
        if ( ! $term || $term->post_type !== 'easy_glossary' ) {
            wp_send_json_error( array( 'message' => __( 'Begriff nicht gefunden', 'easy-glossary' ) ) );
        }
        
        $settings = $this->get_settings();
        
        // Externe URL prüfen
        $external_link = get_post_meta( $term_id, '_glossary_external_link', true );
        
        if ( ! empty( $settings['external_links_in_tooltip'] ) && ! empty( $external_link ) ) {
            // Externe Ressource anzeigen
            $content = sprintf(
                '<div class="tooltip-external">
                    <p>%s</p>
                    <a href="%s" target="_blank" rel="noopener noreferrer" class="tooltip-external-link">
                        <span class="dashicons dashicons-external"></span> %s
                    </a>
                </div>',
                esc_html__( 'Dieser Begriff verweist auf eine externe Ressource:', 'easy-glossary' ),
                esc_url( $external_link ),
                esc_html__( 'Externe Seite öffnen', 'easy-glossary' )
            );
        } else {
            // Normale Beschreibung
            $excerpt = get_the_excerpt( $term );
            
            if ( empty( $excerpt ) ) {
                $content = get_post_field( 'post_content', $term_id );
                $excerpt = wp_trim_words( strip_tags( $content ), 30, '...' );
            }
            
            $content = '<p>' . esc_html( $excerpt ) . '</p>';
        }
        
        wp_send_json_success( array(
            'title' => get_the_title( $term_id ),
            'content' => $content,
            'url' => get_permalink( $term_id ),
            'external_link' => $external_link
        ) );
    }
    
    /**
     * Hole Settings
     */
    private function get_settings() {
        $defaults = array(
            'enable_tooltips' => true,
            'tooltip_style' => 'default',
            'ajax_tooltips' => false,
            'tooltip_trigger' => 'hover',
            'external_links_in_tooltip' => false
        );
        
        $settings = get_option( 'easy_glossary_settings', array() );
        
        return wp_parse_args( $settings, $defaults );
    }
}

// Initialize
new Easy_Glossary_Tooltips();
