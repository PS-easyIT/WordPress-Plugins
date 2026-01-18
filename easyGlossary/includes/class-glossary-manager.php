<?php
/**
 * Glossary Manager Class
 * 
 * @package Easy_Glossary
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Glossary Manager Class
 */
class Easy_Glossary_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_taxonomy' ) );
        add_filter( 'the_content', array( $this, 'auto_link_glossary_terms' ) );
    }
    
    /**
     * Register glossary post type
     */
    public function register_post_type() {
        $args = array(
            'labels' => array(
                'name' => __( 'Glossary Terms', 'easy-glossary' ),
                'singular_name' => __( 'Glossary Term', 'easy-glossary' ),
                'add_new' => __( 'Add New Term', 'easy-glossary' ),
                'add_new_item' => __( 'Add New Glossary Term', 'easy-glossary' ),
                'edit_item' => __( 'Edit Glossary Term', 'easy-glossary' ),
                'new_item' => __( 'New Glossary Term', 'easy-glossary' ),
                'view_item' => __( 'View Glossary Term', 'easy-glossary' ),
                'search_items' => __( 'Search Glossary Terms', 'easy-glossary' ),
                'not_found' => __( 'No glossary terms found', 'easy-glossary' ),
                'not_found_in_trash' => __( 'No glossary terms found in trash', 'easy-glossary' ),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array( 'slug' => 'glossary' ),
            'supports' => array( 'title', 'editor', 'thumbnail' ),
            'menu_icon' => 'dashicons-book-alt',
            'show_in_rest' => true,
        );
        
        register_post_type( 'glossary_term', $args );
    }
    
    /**
     * Register glossary taxonomy
     */
    public function register_taxonomy() {
        $args = array(
            'labels' => array(
                'name' => __( 'Glossary Categories', 'easy-glossary' ),
                'singular_name' => __( 'Glossary Category', 'easy-glossary' ),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => array( 'slug' => 'glossary-category' ),
        );
        
        register_taxonomy( 'glossary_category', 'glossary_term', $args );
    }
    
    /**
     * Auto-link glossary terms in content
     */
    public function auto_link_glossary_terms( $content ) {
        if ( ! is_main_query() || is_admin() ) {
            return $content;
        }
        
        $auto_link = get_option( 'easy_glossary_auto_link', true );
        if ( ! $auto_link ) {
            return $content;
        }
        
        $terms = get_posts( array(
            'post_type' => 'glossary_term',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ) );
        
        foreach ( $terms as $term ) {
            $term_title = esc_html( $term->post_title );
            $term_link = get_permalink( $term->ID );
            $term_excerpt = wp_trim_words( $term->post_content, 20 );
            
            // Create tooltip link
            $tooltip_link = sprintf(
                '<a href="%s" class="glossary-tooltip" data-tooltip="%s">%s</a>',
                esc_url( $term_link ),
                esc_attr( $term_excerpt ),
                $term_title
            );
            
            // Replace term in content (case-insensitive, whole words only)
            $pattern = '/\b' . preg_quote( $term_title, '/' ) . '\b/i';
            $content = preg_replace( $pattern, $tooltip_link, $content, 1 );
        }
        
        return $content;
    }
    
    /**
     * Get glossary term definition
     */
    public static function get_definition( $term_name ) {
        $term = get_page_by_title( $term_name, OBJECT, 'glossary_term' );
        
        if ( $term ) {
            return array(
                'title' => $term->post_title,
                'content' => $term->post_content,
                'excerpt' => $term->post_excerpt,
                'link' => get_permalink( $term->ID ),
            );
        }
        
        return false;
    }
    
    /**
     * Add glossary term programmatically
     */
    public static function add_term( $title, $definition, $category = '' ) {
        $post_data = array(
            'post_title' => sanitize_text_field( $title ),
            'post_content' => wp_kses_post( $definition ),
            'post_type' => 'glossary_term',
            'post_status' => 'publish',
        );
        
        $post_id = wp_insert_post( $post_data );
        
        if ( $post_id && ! empty( $category ) ) {
            wp_set_object_terms( $post_id, $category, 'glossary_category' );
        }
        
        return $post_id;
    }
    
    /**
     * Get all glossary terms
     */
    public static function get_all_terms( $args = array() ) {
        $defaults = array(
            'post_type' => 'glossary_term',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        return get_posts( $args );
    }
    
    /**
     * Search glossary terms
     */
    public static function search_terms( $search_query ) {
        $args = array(
            'post_type' => 'glossary_term',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            's' => sanitize_text_field( $search_query ),
        );
        
        return get_posts( $args );
    }
}

// Initialize
new Easy_Glossary_Manager();
