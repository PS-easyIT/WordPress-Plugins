<?php
/**
 * Glossary Auto-Linking Engine
 * 
 * Automatische Verlinkung von Glossar-Begriffen in Posts/Pages
 * 
 * @package easyGlossary
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Easy_Glossary_Auto_Linking {
    
    /**
     * Cached glossary terms
     */
    private $cached_terms = null;
    
    /**
     * Cache expiration time (1 hour)
     */
    private $cache_expiration = 3600;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Content-Filter für Auto-Linking
        add_filter( 'the_content', array( $this, 'add_auto_links' ), 20 );
        
        // Cache leeren bei Änderungen
        add_action( 'save_post_easy_glossary', array( $this, 'clear_cache' ) );
        add_action( 'delete_post', array( $this, 'clear_cache' ) );
        add_action( 'easy_glossary_settings_updated', array( $this, 'clear_cache' ) );
    }
    
    /**
     * Hauptfunktion für Auto-Linking
     */
    public function add_auto_links( $content ) {
        // Nur im Frontend
        if ( is_admin() ) {
            return $content;
        }
        
        // Nur in Hauptabfrage und im Loop
        if ( ! is_main_query() || ! in_the_loop() ) {
            return $content;
        }
        
        // Settings abrufen
        $settings = $this->get_settings();
        
        // Auto-Linking deaktiviert?
        if ( empty( $settings['enable_auto_linking'] ) ) {
            return $content;
        }
        
        // Aktuelle Seite prüfen
        if ( $this->should_exclude_current_page( $settings ) ) {
            return $content;
        }
        
        // Glossar-Begriffe abrufen (gecacht)
        $terms = $this->get_cached_terms();
        
        if ( empty( $terms ) ) {
            return $content;
        }
        
        // Auto-Linking durchführen
        return $this->process_auto_linking( $content, $terms, $settings );
    }
    
    /**
     * Prüfe ob aktuelle Seite ausgeschlossen werden soll
     */
    private function should_exclude_current_page( $settings ) {
        global $post;
        
        // Startseite ausschließen?
        if ( ! empty( $settings['exclude_homepage'] ) && is_front_page() ) {
            return true;
        }
        
        // Glossar-Seiten selbst ausschließen (verhindert Rekursion)
        if ( $post && $post->post_type === 'easy_glossary' ) {
            return true;
        }
        
        // Ausgeschlossene Post-Types
        if ( ! empty( $settings['excluded_post_types'] ) && is_array( $settings['excluded_post_types'] ) ) {
            if ( $post && in_array( $post->post_type, $settings['excluded_post_types'] ) ) {
                return true;
            }
        }
        
        // Ausgeschlossene Seiten (IDs)
        if ( ! empty( $settings['excluded_pages'] ) && is_array( $settings['excluded_pages'] ) ) {
            if ( $post && in_array( $post->ID, $settings['excluded_pages'] ) ) {
                return true;
            }
        }
        
        // Nur auf bestimmten Post-Types?
        if ( ! empty( $settings['allowed_post_types'] ) && is_array( $settings['allowed_post_types'] ) ) {
            if ( $post && ! in_array( $post->post_type, $settings['allowed_post_types'] ) ) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verarbeite Auto-Linking
     */
    private function process_auto_linking( $content, $terms, $settings ) {
        // Nur erstes Vorkommen oder alle?
        $link_all = ! empty( $settings['link_all_occurrences'] );
        
        // Case-sensitive oder case-insensitive?
        $case_sensitive = ! empty( $settings['case_sensitive'] );
        
        // Bereits verlinkte Begriffe tracken
        $linked_terms = array();
        
        // HTML-Tags schützen (nicht in Tags verlinken)
        $content = $this->protect_html_tags( $content );
        
        // Sortiere Begriffe nach Länge (längste zuerst, um Teilwort-Probleme zu vermeiden)
        usort( $terms, function( $a, $b ) {
            return strlen( get_the_title( $b->ID ) ) - strlen( get_the_title( $a->ID ) );
        });
        
        foreach ( $terms as $term ) {
            $title = get_the_title( $term->ID );
            $permalink = get_permalink( $term->ID );
            
            // Validierung
            if ( empty( $title ) || strlen( $title ) < 2 || empty( $permalink ) ) {
                continue;
            }
            
            // Synonyme einbeziehen
            $search_terms = array( $title );
            $synonyms = get_post_meta( $term->ID, '_glossary_synonyms', true );
            
            if ( ! empty( $synonyms ) ) {
                $synonym_array = array_filter( array_map( 'trim', explode( "\n", $synonyms ) ) );
                $search_terms = array_merge( $search_terms, $synonym_array );
            }
            
            foreach ( $search_terms as $search_term ) {
                // Nur erstes Vorkommen und bereits verlinkt?
                if ( ! $link_all && in_array( strtolower( $search_term ), $linked_terms ) ) {
                    continue;
                }
                
                // Prüfe ob Begriff im Content vorkommt
                $pattern = $this->build_search_pattern( $search_term, $case_sensitive );
                
                if ( ! preg_match( $pattern, $content ) ) {
                    continue;
                }
                
                // Externe URL oder interne Seite?
                $external_link = get_post_meta( $term->ID, '_glossary_external_link', true );
                $target_url = ! empty( $external_link ) ? $external_link : $permalink;
                
                // Tooltip-Content vorbereiten
                $tooltip_content = $this->get_tooltip_content( $term );
                
                // Replacement erstellen
                $replacement = $this->build_replacement( $search_term, $target_url, $tooltip_content, $settings );
                
                // Nur erstes Vorkommen?
                if ( ! $link_all ) {
                    $content = preg_replace( $pattern, $replacement, $content, 1 );
                    $linked_terms[] = strtolower( $search_term );
                } else {
                    $content = preg_replace( $pattern, $replacement, $content );
                }
            }
        }
        
        // HTML-Tags wiederherstellen
        $content = $this->restore_html_tags( $content );
        
        return $content;
    }
    
    /**
     * Erstelle Suchmuster für Regex
     */
    private function build_search_pattern( $term, $case_sensitive = false ) {
        $term = preg_quote( $term, '/' );
        
        // Wortgrenzen verwenden, um Teilwort-Matches zu vermeiden
        $pattern = '/\b(' . $term . ')\b/';
        
        // Case-insensitive?
        if ( ! $case_sensitive ) {
            $pattern .= 'i';
        }
        
        return $pattern;
    }
    
    /**
     * Erstelle Replacement-String
     */
    private function build_replacement( $term, $url, $tooltip_content, $settings ) {
        $classes = array( 'easy-glossary-link' );
        
        // Tooltip aktiviert?
        if ( ! empty( $settings['enable_tooltips'] ) ) {
            $classes[] = 'has-tooltip';
        }
        
        $class_string = implode( ' ', $classes );
        
        // Data-Attribute für Tooltip
        $data_attrs = '';
        if ( ! empty( $settings['enable_tooltips'] ) ) {
            if ( ! empty( $settings['ajax_tooltips'] ) ) {
                // AJAX-Loading
                $data_attrs .= ' data-tooltip-ajax="true" data-term-id="' . esc_attr( $tooltip_content['id'] ) . '"';
            } else {
                // Inline-Content
                $data_attrs .= ' data-tooltip-content="' . esc_attr( $tooltip_content['excerpt'] ) . '"';
            }
        }
        
        // Link erstellen
        $replacement = '<a href="' . esc_url( $url ) . '" ' .
                      'class="' . esc_attr( $class_string ) . '" ' .
                      'title="' . esc_attr( $tooltip_content['title'] ) . '"' .
                      $data_attrs . '>$1</a>';
        
        return $replacement;
    }
    
    /**
     * Hole Tooltip-Content
     */
    private function get_tooltip_content( $term ) {
        $excerpt = get_the_excerpt( $term->ID );
        
        // Fallback: Ersten Satz aus Content
        if ( empty( $excerpt ) ) {
            $content = get_post_field( 'post_content', $term->ID );
            $excerpt = wp_trim_words( strip_tags( $content ), 20, '...' );
        }
        
        return array(
            'id' => $term->ID,
            'title' => get_the_title( $term->ID ),
            'excerpt' => $excerpt
        );
    }
    
    /**
     * Schütze HTML-Tags vor Verlinkung
     */
    private function protect_html_tags( $content ) {
        // Ersetze HTML-Tags temporär durch Platzhalter
        return preg_replace_callback( '/<[^>]+>/', function( $matches ) {
            return '{{HTML_TAG_' . base64_encode( $matches[0] ) . '}}';
        }, $content );
    }
    
    /**
     * Stelle HTML-Tags wieder her
     */
    private function restore_html_tags( $content ) {
        return preg_replace_callback( '/\{\{HTML_TAG_([^}]+)\}\}/', function( $matches ) {
            return base64_decode( $matches[1] );
        }, $content );
    }
    
    /**
     * Hole gecachte Begriffe
     */
    private function get_cached_terms() {
        // Aus Objekt-Cache
        if ( $this->cached_terms !== null ) {
            return $this->cached_terms;
        }
        
        // Aus WordPress-Cache
        $cache_key = 'easy_glossary_auto_link_terms';
        $cached = wp_cache_get( $cache_key, 'easy_glossary' );
        
        if ( false !== $cached ) {
            $this->cached_terms = $cached;
            return $cached;
        }
        
        // Aus Transient
        $cached = get_transient( $cache_key );
        
        if ( false !== $cached ) {
            wp_cache_set( $cache_key, $cached, 'easy_glossary', $this->cache_expiration );
            $this->cached_terms = $cached;
            return $cached;
        }
        
        // Neu laden
        $terms = get_posts( array(
            'post_type' => 'easy_glossary',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids'
        ) );
        
        // Als Objekte laden
        $term_objects = array();
        foreach ( $terms as $term_id ) {
            $term_objects[] = get_post( $term_id );
        }
        
        // Cachen
        set_transient( $cache_key, $term_objects, $this->cache_expiration );
        wp_cache_set( $cache_key, $term_objects, 'easy_glossary', $this->cache_expiration );
        
        $this->cached_terms = $term_objects;
        
        return $term_objects;
    }
    
    /**
     * Cache leeren
     */
    public function clear_cache() {
        $cache_key = 'easy_glossary_auto_link_terms';
        
        delete_transient( $cache_key );
        wp_cache_delete( $cache_key, 'easy_glossary' );
        
        $this->cached_terms = null;
    }
    
    /**
     * Hole Settings
     */
    private function get_settings() {
        $defaults = array(
            'enable_auto_linking' => true,
            'link_all_occurrences' => false,
            'case_sensitive' => false,
            'exclude_homepage' => true,
            'excluded_post_types' => array(),
            'excluded_pages' => array(),
            'allowed_post_types' => array( 'post', 'page' ),
            'enable_tooltips' => true,
            'ajax_tooltips' => false
        );
        
        $settings = get_option( 'easy_glossary_settings', array() );
        
        return wp_parse_args( $settings, $defaults );
    }
}

// Initialize
new Easy_Glossary_Auto_Linking();
