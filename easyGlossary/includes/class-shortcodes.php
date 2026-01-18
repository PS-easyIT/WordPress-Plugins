<?php
/**
 * Shortcodes Class
 * 
 * @package Easy_Glossary
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcodes Class
 */
class Easy_Glossary_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_shortcodes();
    }
    
    /**
     * Initialize shortcodes
     */
    private function init_shortcodes() {
        add_shortcode( 'glossary', array( $this, 'single_term_shortcode' ) );
        add_shortcode( 'glossary_list', array( $this, 'terms_list_shortcode' ) );
        add_shortcode( 'glossary_search', array( $this, 'search_form_shortcode' ) );
        add_shortcode( 'glossary_random', array( $this, 'random_term_shortcode' ) );
        add_shortcode( 'glossary_categories', array( $this, 'categories_list_shortcode' ) );
    }
    
    /**
     * Single term shortcode
     * [glossary term="WordPress"]
     */
    public function single_term_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'term' => '',
            'display' => 'tooltip', // tooltip, link, definition
        ), $atts );
        
        if ( empty( $atts['term'] ) ) {
            return '';
        }
        
        $term_data = Easy_Glossary_Manager::get_definition( $atts['term'] );
        
        if ( ! $term_data ) {
            return esc_html( $atts['term'] );
        }
        
        switch ( $atts['display'] ) {
            case 'tooltip':
                return sprintf(
                    '<span class="glossary-tooltip" data-tooltip="%s">%s</span>',
                    esc_attr( wp_trim_words( $term_data['content'], 20 ) ),
                    esc_html( $term_data['title'] )
                );
                
            case 'link':
                return sprintf(
                    '<a href="%s" class="glossary-link">%s</a>',
                    esc_url( $term_data['link'] ),
                    esc_html( $term_data['title'] )
                );
                
            case 'definition':
                return sprintf(
                    '<div class="glossary-definition"><strong>%s:</strong> %s</div>',
                    esc_html( $term_data['title'] ),
                    wp_kses_post( $term_data['content'] )
                );
                
            default:
                return esc_html( $term_data['title'] );
        }
    }
    
    /**
     * Terms list shortcode
     * [glossary_list category="webdev" limit="10"]
     */
    public function terms_list_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'category' => '',
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'columns' => 1,
            'show_description' => false,
        ), $atts );
        
        $args = array(
            'posts_per_page' => intval( $atts['limit'] ),
            'orderby' => sanitize_text_field( $atts['orderby'] ),
            'order' => sanitize_text_field( $atts['order'] ),
        );
        
        if ( ! empty( $atts['category'] ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'glossary_category',
                    'field' => 'slug',
                    'terms' => sanitize_text_field( $atts['category'] ),
                ),
            );
        }
        
        $terms = Easy_Glossary_Manager::get_all_terms( $args );
        
        if ( empty( $terms ) ) {
            return '<p>' . __( 'No glossary terms found.', 'easy-glossary' ) . '</p>';
        }
        
        $columns = max( 1, intval( $atts['columns'] ) );
        $output = '<div class="glossary-list glossary-columns-' . $columns . '">';
        
        foreach ( $terms as $term ) {
            $output .= '<div class="glossary-item">';
            $output .= '<h4><a href="' . esc_url( get_permalink( $term->ID ) ) . '">' . esc_html( $term->post_title ) . '</a></h4>';
            
            if ( filter_var( $atts['show_description'], FILTER_VALIDATE_BOOLEAN ) ) {
                $excerpt = ! empty( $term->post_excerpt ) ? $term->post_excerpt : wp_trim_words( $term->post_content, 20 );
                $output .= '<p>' . esc_html( $excerpt ) . '</p>';
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Search form shortcode
     * [glossary_search placeholder="Search terms..."]
     */
    public function search_form_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'placeholder' => __( 'Search glossary terms...', 'easy-glossary' ),
            'button_text' => __( 'Search', 'easy-glossary' ),
        ), $atts );
        
        ob_start();
        ?>
        <form class="glossary-search-form" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <input type="hidden" name="post_type" value="glossary_term">
            <div class="search-input-wrapper">
                <input type="search" name="s" placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>">
                <button type="submit"><?php echo esc_html( $atts['button_text'] ); ?></button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Random term shortcode
     * [glossary_random]
     */
    public function random_term_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'display' => 'link', // link, definition, tooltip
        ), $atts );
        
        $args = array(
            'posts_per_page' => 1,
            'orderby' => 'rand',
        );
        
        $terms = Easy_Glossary_Manager::get_all_terms( $args );
        
        if ( empty( $terms ) ) {
            return '';
        }
        
        $term = $terms[0];
        
        switch ( $atts['display'] ) {
            case 'definition':
                return sprintf(
                    '<div class="glossary-random-term"><h4>%s</h4><p>%s</p><a href="%s">%s</a></div>',
                    esc_html( $term->post_title ),
                    esc_html( wp_trim_words( $term->post_content, 30 ) ),
                    esc_url( get_permalink( $term->ID ) ),
                    __( 'Read more', 'easy-glossary' )
                );
                
            case 'tooltip':
                return sprintf(
                    '<span class="glossary-tooltip glossary-random" data-tooltip="%s">%s</span>',
                    esc_attr( wp_trim_words( $term->post_content, 20 ) ),
                    esc_html( $term->post_title )
                );
                
            default:
                return sprintf(
                    '<a href="%s" class="glossary-random-link">%s</a>',
                    esc_url( get_permalink( $term->ID ) ),
                    esc_html( $term->post_title )
                );
        }
    }
    
    /**
     * Categories list shortcode
     * [glossary_categories show_count="true"]
     */
    public function categories_list_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'show_count' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ), $atts );
        
        $categories = get_terms( array(
            'taxonomy' => 'glossary_category',
            'hide_empty' => true,
            'orderby' => sanitize_text_field( $atts['orderby'] ),
            'order' => sanitize_text_field( $atts['order'] ),
        ) );
        
        if ( empty( $categories ) || is_wp_error( $categories ) ) {
            return '<p>' . __( 'No glossary categories found.', 'easy-glossary' ) . '</p>';
        }
        
        $output = '<ul class="glossary-categories-list">';
        
        foreach ( $categories as $category ) {
            $output .= '<li>';
            $output .= '<a href="' . esc_url( get_term_link( $category ) ) . '">' . esc_html( $category->name ) . '</a>';
            
            if ( filter_var( $atts['show_count'], FILTER_VALIDATE_BOOLEAN ) ) {
                $output .= ' <span class="count">(' . intval( $category->count ) . ')</span>';
            }
            
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        
        return $output;
    }
}

// Initialize
new Easy_Glossary_Shortcodes();
