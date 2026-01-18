<?php
/**
 * Plugin Name: easyGlossary
 * Plugin URI: https://phin.network
 * Description: Erweiterte Glossar-Funktionen für WordPress-Websites mit A-Z Navigation, automatischen Tooltips und Begriffsverknüpfungen.
 * Version: 1.3.0
 * Author: PHIN IT Solutions
 * Author URI: https://phin.network
 * Text Domain: easy-glossary
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'EASY_GLOSSARY_VERSION', '1.3.0' );
define( 'EASY_GLOSSARY_PLUGIN_FILE', __FILE__ );
define( 'EASY_GLOSSARY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EASY_GLOSSARY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EASY_GLOSSARY_ASSETS_URL', EASY_GLOSSARY_PLUGIN_URL . 'assets/' );

/**
 * Main easyGlossary class
 */
final class Easy_Glossary {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Include shared admin UX
        if ( file_exists( EASY_GLOSSARY_PLUGIN_DIR . '../easy-admin-integration.php' ) ) {
            require_once EASY_GLOSSARY_PLUGIN_DIR . '../easy-admin-integration.php';
        }
        
        // Load Meta Boxes
        if ( is_admin() && file_exists( EASY_GLOSSARY_PLUGIN_DIR . 'includes/class-glossary-meta-boxes.php' ) ) {
            require_once EASY_GLOSSARY_PLUGIN_DIR . 'includes/class-glossary-meta-boxes.php';
        }
        
        // Load Search Handler
        if ( file_exists( EASY_GLOSSARY_PLUGIN_DIR . 'includes/class-glossary-search.php' ) ) {
            require_once EASY_GLOSSARY_PLUGIN_DIR . 'includes/class-glossary-search.php';
        }
        
        // Load Auto-Linking Engine
        if ( file_exists( EASY_GLOSSARY_PLUGIN_DIR . 'includes/class-glossary-auto-linking.php' ) ) {
            require_once EASY_GLOSSARY_PLUGIN_DIR . 'includes/class-glossary-auto-linking.php';
        }
        
        // Load Tooltip System
        if ( file_exists( EASY_GLOSSARY_PLUGIN_DIR . 'includes/class-glossary-tooltips.php' ) ) {
            require_once EASY_GLOSSARY_PLUGIN_DIR . 'includes/class-glossary-tooltips.php';
        }
        
        // Load Bulk Actions
        if ( is_admin() && file_exists( EASY_GLOSSARY_PLUGIN_DIR . 'includes/class-glossary-bulk-actions.php' ) ) {
            require_once EASY_GLOSSARY_PLUGIN_DIR . 'includes/class-glossary-bulk-actions.php';
        }
        
        // Load Dashboard Widget
        if ( is_admin() && file_exists( EASY_GLOSSARY_PLUGIN_DIR . 'includes/class-glossary-dashboard-widget.php' ) ) {
            require_once EASY_GLOSSARY_PLUGIN_DIR . 'includes/class-glossary-dashboard-widget.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'wp_head', array( $this, 'output_custom_colors' ), 100 );
        
        // Content filtering wird von Auto-Linking-Klasse übernommen
        // add_filter( 'the_content', array( $this, 'add_tooltips_to_content' ), 20 );
        
        // Admin UX Integration
        add_action( 'init', array( $this, 'register_admin_ux' ) );
        
        // Shortcodes
        add_shortcode( 'glossary', array( $this, 'glossary_shortcode' ) );
        add_shortcode( 'glossary_index', array( $this, 'glossary_index_shortcode' ) );
        add_shortcode( 'glossary_az', array( $this, 'glossary_az_shortcode' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_easy_glossary_import', array( $this, 'ajax_import_glossary' ) );
        add_action( 'wp_ajax_easy_glossary_export', array( $this, 'ajax_export_glossary' ) );
        
        // Debug AJAX handler
        add_action( 'wp_ajax_easy_glossary_debug', array( $this, 'ajax_debug_glossary' ) );
        
        // Fix permalinks AJAX handler
        add_action( 'wp_ajax_easy_glossary_fix_permalinks', array( $this, 'ajax_fix_permalinks' ) );
        
        // Cache management
        add_action( 'save_post', array( $this, 'clear_glossary_cache' ) );
        add_action( 'delete_post', array( $this, 'clear_glossary_cache' ) );
        
        // Activation/Deactivation
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        
        // Template loading
        add_filter( 'template_include', array( $this, 'load_glossary_template' ) );
        
        // Permalink handling
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_filter( 'post_type_link', array( $this, 'custom_glossary_permalink' ), 10, 2 );
        add_action( 'template_redirect', array( $this, 'handle_glossary_redirects' ) );
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'easy-glossary', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }
    
    /**
     * Register glossary post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Glossar', 'Post type general name', 'easy-glossary' ),
            'singular_name'         => _x( 'Glossareintrag', 'Post type singular name', 'easy-glossary' ),
            'menu_name'             => _x( 'Glossar', 'Admin Menu text', 'easy-glossary' ),
            'name_admin_bar'        => _x( 'Glossareintrag', 'Add New on Toolbar', 'easy-glossary' ),
            'add_new'               => __( 'Neuen Eintrag erstellen', 'easy-glossary' ),
            'add_new_item'          => __( 'Neuen Glossareintrag erstellen', 'easy-glossary' ),
            'new_item'              => __( 'Neuer Glossareintrag', 'easy-glossary' ),
            'edit_item'             => __( 'Glossareintrag bearbeiten', 'easy-glossary' ),
            'view_item'             => __( 'Glossareintrag ansehen', 'easy-glossary' ),
            'all_items'             => __( 'Alle Einträge', 'easy-glossary' ),
            'search_items'          => __( 'Glossareinträge suchen', 'easy-glossary' ),
            'parent_item_colon'     => __( 'Übergeordneter Glossareintrag:', 'easy-glossary' ),
            'not_found'             => __( 'Keine Glossareinträge gefunden.', 'easy-glossary' ),
            'not_found_in_trash'    => __( 'Keine Glossareinträge im Papierkorb gefunden.', 'easy-glossary' ),
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __( 'Glossar-Einträge für automatische Tooltips und Begriffsverknüpfungen', 'easy-glossary' ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'easy-glossary',
            'query_var'          => true,
            'rewrite'            => array( 
                'slug' => 'glossar',
                'with_front' => false,
                'feeds' => true,
                'pages' => true
            ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
            'show_in_rest'       => true,
            'show_in_nav_menus'  => true,
            'can_export'         => true,
        );

        register_post_type( 'easy_glossary', $args );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __( 'easyGlossary', 'easy-glossary' ),
            __( 'easyGlossary', 'easy-glossary' ),
            'manage_options',
            'easy-glossary',
            array( $this, 'render_dashboard' ),
            'dashicons-book-alt',
            32
        );
        
        // Dashboard submenu
        add_submenu_page(
            'easy-glossary',
            __( 'easyGlossary Dashboard', 'easy-glossary' ),
            __( 'Dashboard', 'easy-glossary' ),
            'manage_options',
            'easy-glossary',
            array( $this, 'render_dashboard' )
        );
        
        // Import/Export
        add_submenu_page(
            'easy-glossary',
            __( 'Import & Export', 'easy-glossary' ),
            __( 'Import & Export', 'easy-glossary' ),
            'manage_options',
            'easy-glossary-import-export',
            array( $this, 'render_import_export' )
        );
        
        // Settings
        add_submenu_page(
            'easy-glossary',
            __( 'Glossar Einstellungen', 'easy-glossary' ),
            __( 'Einstellungen', 'easy-glossary' ),
            'manage_options',
            'easy-glossary-settings',
            array( $this, 'render_settings' )
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'easy-glossary' ) === false ) {
            return;
        }
        
        wp_enqueue_style(
            'easy-glossary-admin',
            EASY_GLOSSARY_ASSETS_URL . 'css/admin.css',
            array(),
            EASY_GLOSSARY_VERSION
        );
        
        wp_enqueue_script(
            'easy-glossary-admin',
            EASY_GLOSSARY_ASSETS_URL . 'js/admin.js',
            array( 'jquery' ),
            EASY_GLOSSARY_VERSION,
            true
        );
        
        wp_localize_script( 'easy-glossary-admin', 'easyGlossary', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'easy_glossary_nonce' ),
            'strings' => array(
                'importing' => __( 'Importiere...', 'easy-glossary' ),
                'exporting' => __( 'Exportiere...', 'easy-glossary' ),
                'success' => __( 'Erfolgreich!', 'easy-glossary' ),
                'error' => __( 'Fehler aufgetreten', 'easy-glossary' ),
            )
        ) );
    }
    
    /**
     * Dynamische Farben als CSS-Variablen ausgeben
     */
    public function output_custom_colors() {
        $settings = get_option( 'easy_glossary_settings', array() );
        
        $primary_color = $settings['primary_button_color'] ?? '#e64946';
        $primary_hover = $settings['primary_button_hover'] ?? '#d43835';
        $secondary_color = $settings['secondary_button_color'] ?? '#000000';
        $secondary_hover = $settings['secondary_button_hover'] ?? '#333333';
        $link_color = $settings['link_color'] ?? '#e64946';
        $link_hover = $settings['link_hover_color'] ?? '#d43835';
        $heading_color = $settings['heading_color'] ?? '#000000';
        $text_color = $settings['text_color'] ?? '#000000';
        $text_light = $settings['text_light_color'] ?? '#666666';
        
        ?>
        <style id="easy-glossary-custom-colors">
        :root {
            --glossary-primary: <?php echo esc_attr( $primary_color ); ?>;
            --glossary-primary-hover: <?php echo esc_attr( $primary_hover ); ?>;
            --glossary-secondary: <?php echo esc_attr( $secondary_color ); ?>;
            --glossary-secondary-hover: <?php echo esc_attr( $secondary_hover ); ?>;
            --glossary-link: <?php echo esc_attr( $link_color ); ?>;
            --glossary-link-hover: <?php echo esc_attr( $link_hover ); ?>;
            --glossary-heading: <?php echo esc_attr( $heading_color ); ?>;
            --glossary-text: <?php echo esc_attr( $text_color ); ?>;
            --glossary-text-light: <?php echo esc_attr( $text_light ); ?>;
        }
        </style>
        <?php
    }
    
    /**
     * Enqueue frontend assets (conditional loading)
     */
    public function enqueue_frontend_assets() {
        // Nur laden, wenn Glossar-Features benötigt werden
        if ( ! $this->should_load_glossary_assets() ) {
            return;
        }
        
        // Prüfe ob MH Magazine Theme aktiv ist
        $current_theme = wp_get_theme();
        $is_mh_magazine = ( $current_theme->get( 'Name' ) === 'MH Magazine' || 
                           $current_theme->get( 'Template' ) === 'mh-magazine' );
        
        if ( $is_mh_magazine ) {
            // MH Magazine optimierte Styles
            wp_enqueue_style(
                'easy-glossary-mh-magazine',
                EASY_GLOSSARY_ASSETS_URL . 'css/mh-magazine-theme.css',
                array(),
                EASY_GLOSSARY_VERSION
            );
        } else {
            // Standard Frontend Styles
            wp_enqueue_style(
                'easy-glossary-frontend',
                EASY_GLOSSARY_ASSETS_URL . 'css/frontend.css',
                array(),
                EASY_GLOSSARY_VERSION
            );
        }
        
        wp_enqueue_script(
            'easy-glossary-frontend',
            EASY_GLOSSARY_ASSETS_URL . 'js/frontend.js',
            array( 'jquery' ),
            EASY_GLOSSARY_VERSION,
            true
        );
    }
    
    /**
     * Prüft, ob Glossar-Assets geladen werden sollen
     */
    private function should_load_glossary_assets() {
        // Nicht im Admin-Bereich laden
        if ( is_admin() ) {
            return false;
        }
        
        global $post;
        
        if ( ! $post ) {
            return false;
        }
        
        // Nur auf Blogbeiträgen und Einzelseiten laden
        if ( ! is_single() && ! is_page() ) {
            return false;
        }
        
        // Glossar-Shortcodes
        if ( has_shortcode( $post->post_content, 'glossary' ) || 
             has_shortcode( $post->post_content, 'glossary_index' ) ) {
            return true;
        }
        
        // Glossar-Seiten
        if ( $post->post_type === 'easy_glossary' || $post->post_name === 'glossary' ) {
            return true;
        }
        
        // Wenn Auto-Tooltips aktiviert sind
        $settings = get_option( 'easy_glossary_settings', array() );
        if ( ! empty( $settings['auto_tooltips'] ) && ! empty( $post->post_content ) ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Add tooltips to content
     */
    public function add_tooltips_to_content( $content ) {
        try {
            // Nur im Frontend anwenden
            if ( is_admin() ) {
                return $content;
            }
            
            // Nur auf Hauptabfrage und im Loop
            if ( ! is_main_query() || ! in_the_loop() ) {
                return $content;
            }
            
            // NUR auf Blogbeiträgen (single posts) und Einzelseiten (pages) anwenden
            if ( ! is_single() && ! is_page() ) {
                return $content;
            }
            
            // Nicht auf Glossar-Seiten selbst anwenden (verhindert Rekursion)
            global $post;
            if ( $post && $post->post_type === 'easy_glossary' ) {
                return $content;
            }
            
            // Get glossary terms mit Fehlerbehandlung
            $glossary_terms = $this->get_glossary_terms();
            
            if ( empty( $glossary_terms ) || ! is_array( $glossary_terms ) ) {
                return $content;
            }
            
            // Verbesserte Verlinkung für alle Vorkommen
            foreach ( $glossary_terms as $term ) {
                if ( ! is_object( $term ) || ! isset( $term->ID ) ) {
                    continue;
                }
                
                $title = get_the_title( $term->ID );
                $permalink = $this->get_glossary_permalink( $term->ID );
                
                if ( empty( $title ) || strlen( $title ) < 3 || empty( $permalink ) ) {
                    continue;
                }
                
                // Debug: Prüfe Permalink
                error_log( 'easyGlossary: Begriff "' . $title . '" -> Permalink: ' . $permalink );
                
                // Prüfe ob Begriff bereits verlinkt ist
                if ( strpos( $content, 'easy-glossary-link' ) !== false && 
                     strpos( $content, $title ) !== false ) {
                    continue;
                }
                
                // Alle Vorkommen des Begriffs ersetzen (case-insensitive)
                $search = $title;
                $replace = '<a href="' . esc_url( $permalink ) . '" class="easy-glossary-link" title="' . esc_attr( $title ) . '">' . esc_html( $title ) . '</a>';
                
                // Verwende str_ireplace für case-insensitive Ersetzung aller Vorkommen
                $new_content = str_ireplace( $search, $replace, $content );
                
                // Nur aktualisieren wenn tatsächlich ersetzt wurde
                if ( $new_content !== $content ) {
                    $content = $new_content;
                    error_log( 'easyGlossary: Begriff "' . $title . '" wurde ersetzt' );
                }
            }
            
            return $content;
            
        } catch ( Exception $e ) {
            error_log( 'easyGlossary Error: ' . $e->getMessage() );
            return $content;
        }
    }
    
    /**
     * Get glossary terms
     */
    private function get_glossary_terms() {
        $cache_key = 'easy_glossary_terms';
        $terms = wp_cache_get( $cache_key );
        
        if ( false === $terms ) {
            $terms = get_posts( array(
                'post_type' => 'easy_glossary',
                'post_status' => 'publish',
                'numberposts' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ) );
            
            wp_cache_set( $cache_key, $terms, '', 3600 ); // Cache for 1 hour
        }
        
        return $terms;
    }
    
    /**
     * Get proper glossary permalink
     */
    private function get_glossary_permalink( $post_id ) {
        $post = get_post( $post_id );
        
        if ( ! $post || $post->post_type !== 'easy_glossary' ) {
            return '';
        }
        
        // Use custom permalink structure
        $permalink = home_url( '/glossar/' . $post->post_name . '/' );
        
        return $permalink;
    }
    
    /**
     * Clear glossary cache
     */
    public function clear_glossary_cache( $post_id = null ) {
        if ( $post_id && get_post_type( $post_id ) !== 'easy_glossary' ) {
            return;
        }
        
        wp_cache_delete( 'easy_glossary_terms' );
        
        // Debug: Cache geleert
        error_log( 'easyGlossary: Cache wurde geleert für Post ID: ' . $post_id );
    }
    
    /**
     * Glossary shortcode
     */
    public function glossary_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'term' => '',
            'id' => ''
        ), $atts );
        
        if ( $atts['id'] ) {
            $post = get_post( $atts['id'] );
        } elseif ( $atts['term'] ) {
            $posts = get_posts( array(
                'post_type' => 'easy_glossary',
                'title' => $atts['term'],
                'numberposts' => 1
            ) );
            $post = $posts ? $posts[0] : null;
        } else {
            return '';
        }
        
        if ( ! $post ) {
            return '';
        }
        
        $title = get_the_title( $post->ID );
        $content = get_the_content( null, false, $post->ID );
        $permalink = $this->get_glossary_permalink( $post->ID );
        
        return sprintf(
            '<div class="easy-glossary-entry">
                <h4><a href="%s">%s</a></h4>
                <div class="glossary-content">%s</div>
            </div>',
            esc_url( $permalink ),
            esc_html( $title ),
            wpautop( $content )
        );
    }
    
    /**
     * Glossary index shortcode
     */
    public function glossary_index_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'orderby' => 'title',
            'order' => 'ASC',
            'limit' => -1
        ), $atts );
        
        $terms = get_posts( array(
            'post_type' => 'easy_glossary',
            'post_status' => 'publish',
            'numberposts' => $atts['limit'],
            'orderby' => $atts['orderby'],
            'order' => $atts['order']
        ) );
        
        if ( empty( $terms ) ) {
            return '<p>' . __( 'Keine Glossareinträge gefunden.', 'easy-glossary' ) . '</p>';
        }
        
        $output = '<div class="easy-glossary-index">';
        
        foreach ( $terms as $term ) {
            $title = get_the_title( $term->ID );
            $excerpt = get_the_excerpt( $term->ID );
            $permalink = $this->get_glossary_permalink( $term->ID );
            
            $output .= sprintf(
                '<div class="glossary-index-entry">
                    <h4><a href="%s">%s</a></h4>
                    <p>%s</p>
                </div>',
                esc_url( $permalink ),
                esc_html( $title ),
                esc_html( $excerpt )
            );
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Glossary A-Z Navigation shortcode
     * Design-neutral output - Theme übernimmt Styling
     */
    public function glossary_az_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'show_empty' => 'false',
            'show_count' => 'true'
        ), $atts );
        
        $show_empty = filter_var( $atts['show_empty'], FILTER_VALIDATE_BOOLEAN );
        $show_count = filter_var( $atts['show_count'], FILTER_VALIDATE_BOOLEAN );
        
        // Alle Glossar-Einträge abrufen
        $all_terms = get_posts( array(
            'post_type' => 'easy_glossary',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ) );
        
        if ( empty( $all_terms ) ) {
            return '<p class="glossary-az-empty">' . __( 'Keine Glossareinträge gefunden.', 'easy-glossary' ) . '</p>';
        }
        
        // Begriffe nach Anfangsbuchstaben gruppieren
        $terms_by_letter = array();
        foreach ( $all_terms as $term ) {
            $title = get_the_title( $term->ID );
            $first_letter = strtoupper( mb_substr( $title, 0, 1 ) );
            
            // Nur A-Z, andere Zeichen unter '#' gruppieren
            if ( ! preg_match( '/[A-Z]/', $first_letter ) ) {
                $first_letter = '#';
            }
            
            if ( ! isset( $terms_by_letter[ $first_letter ] ) ) {
                $terms_by_letter[ $first_letter ] = array();
            }
            
            $terms_by_letter[ $first_letter ][] = $term;
        }
        
        // Sortieren
        ksort( $terms_by_letter );
        
        // Output generieren
        $output = '<div class="glossary-az-container" data-glossary-az>';
        
        // A-Z Navigation
        $output .= '<nav class="glossary-az-nav" role="navigation" aria-label="' . esc_attr__( 'Alphabetische Navigation', 'easy-glossary' ) . '">';
        $output .= '<ul class="glossary-az-letters">';
        
        $alphabet = array_merge( range( 'A', 'Z' ), array( '#' ) );
        foreach ( $alphabet as $letter ) {
            $has_terms = isset( $terms_by_letter[ $letter ] );
            $count = $has_terms ? count( $terms_by_letter[ $letter ] ) : 0;
            
            if ( ! $show_empty && ! $has_terms ) {
                continue;
            }
            
            $class = $has_terms ? 'has-terms' : 'no-terms';
            $count_html = ( $show_count && $has_terms ) ? ' <span class="letter-count">(' . $count . ')</span>' : '';
            
            $output .= sprintf(
                '<li class="letter-item %s"><a href="#glossary-letter-%s" class="letter-link" data-letter="%s">%s%s</a></li>',
                esc_attr( $class ),
                esc_attr( strtolower( $letter ) ),
                esc_attr( $letter ),
                esc_html( $letter ),
                $count_html
            );
        }
        
        $output .= '</ul>';
        $output .= '</nav>';
        
        // Glossar-Einträge nach Buchstaben
        $output .= '<div class="glossary-az-content">';
        
        foreach ( $terms_by_letter as $letter => $terms ) {
            $output .= sprintf(
                '<section class="glossary-letter-section" id="glossary-letter-%s" data-letter="%s">',
                esc_attr( strtolower( $letter ) ),
                esc_attr( $letter )
            );
            
            $output .= '<h2 class="glossary-letter-heading">' . esc_html( $letter ) . '</h2>';
            
            $output .= '<ul class="glossary-terms-list">';
            
            foreach ( $terms as $term ) {
                $title = get_the_title( $term->ID );
                $permalink = $this->get_glossary_permalink( $term->ID );
                
                $output .= sprintf(
                    '<li class="glossary-term-item"><a href="%s" class="glossary-term-link">%s</a></li>',
                    esc_url( $permalink ),
                    esc_html( $title )
                );
            }
            
            $output .= '</ul>';
            $output .= '</section>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        // Minimales JavaScript für Filter-Funktionalität
        $output .= $this->get_az_navigation_script();
        
        return $output;
    }
    
    /**
     * JavaScript für A-Z Navigation
     */
    private function get_az_navigation_script() {
        ob_start();
        ?>
        <script>
        (function() {
            if (typeof window.easyGlossaryAZInit !== 'undefined') return;
            window.easyGlossaryAZInit = true;
            
            document.addEventListener('DOMContentLoaded', function() {
                const containers = document.querySelectorAll('[data-glossary-az]');
                
                containers.forEach(function(container) {
                    const letterLinks = container.querySelectorAll('.letter-link');
                    const sections = container.querySelectorAll('.glossary-letter-section');
                    
                    letterLinks.forEach(function(link) {
                        link.addEventListener('click', function(e) {
                            e.preventDefault();
                            
                            const letter = this.getAttribute('data-letter');
                            
                            // Alle Sektionen ausblenden
                            sections.forEach(function(section) {
                                section.style.display = 'none';
                            });
                            
                            // Aktive Sektion anzeigen
                            const activeSection = container.querySelector('[data-letter="' + letter + '"]');
                            if (activeSection) {
                                activeSection.style.display = 'block';
                                activeSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                            
                            // Aktiven Link markieren
                            letterLinks.forEach(function(l) {
                                l.classList.remove('active');
                            });
                            this.classList.add('active');
                        });
                    });
                    
                    // Ersten verfügbaren Buchstaben aktivieren
                    const firstLink = container.querySelector('.letter-link.has-terms');
                    if (firstLink) {
                        firstLink.click();
                    }
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        $glossary_count = wp_count_posts( 'easy_glossary' )->publish;
        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-book-alt"></span>
                <?php _e( 'easyGlossary Dashboard', 'easy-glossary' ); ?>
            </h1>
            
            <div class="easy-glossary-dashboard">
                <div class="dashboard-widgets">
                    <!-- Statistics Widget -->
                    <div class="dashboard-widget">
                        <h3><?php _e( 'Statistiken', 'easy-glossary' ); ?></h3>
                        <div class="widget-content">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo esc_html( $glossary_count ); ?></span>
                                <span class="stat-label"><?php _e( 'Glossareinträge', 'easy-glossary' ); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions Widget -->
                    <div class="dashboard-widget">
                        <h3><?php _e( 'Schnellaktionen', 'easy-glossary' ); ?></h3>
                        <div class="widget-content">
                            <p>
                                <a href="<?php echo admin_url( 'post-new.php?post_type=easy_glossary' ); ?>" class="button button-primary">
                                    <span class="dashicons dashicons-plus"></span>
                                    <?php _e( 'Neuer Eintrag', 'easy-glossary' ); ?>
                                </a>
                            </p>
                            <p>
                                <a href="<?php echo admin_url( 'admin.php?page=easy-glossary-import-export' ); ?>" class="button">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php _e( 'Import & Export', 'easy-glossary' ); ?>
                                </a>
                            </p>
                            <p>
                                <button id="fix-permalinks-btn" class="button button-secondary">
                                    <span class="dashicons dashicons-admin-links"></span>
                                    <?php _e( 'Permalinks reparieren', 'easy-glossary' ); ?>
                                </button>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Recent Entries Widget -->
                    <div class="dashboard-widget">
                        <h3><?php _e( 'Neueste Einträge', 'easy-glossary' ); ?></h3>
                        <div class="widget-content">
                            <?php
                            $recent_terms = get_posts( array(
                                'post_type' => 'easy_glossary',
                                'numberposts' => 5,
                                'orderby' => 'date',
                                'order' => 'DESC'
                            ) );
                            
                            if ( $recent_terms ) :
                                echo '<ul>';
                                foreach ( $recent_terms as $term ) :
                                    echo '<li><a href="' . get_edit_post_link( $term->ID ) . '">' . esc_html( get_the_title( $term->ID ) ) . '</a></li>';
                                endforeach;
                                echo '</ul>';
                            else :
                                echo '<p>' . __( 'Noch keine Einträge vorhanden.', 'easy-glossary' ) . '</p>';
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Shortcode Instructions -->
                <div class="dashboard-instructions">
                    <h3><?php _e( 'Verwendung', 'easy-glossary' ); ?></h3>
                    <div class="instruction-box">
                        <h4><?php _e( 'Shortcodes', 'easy-glossary' ); ?></h4>
                        <p><code>[glossary term="Begriff"]</code> - <?php _e( 'Zeigt einen einzelnen Glossareintrag an', 'easy-glossary' ); ?></p>
                        <p><code>[glossary_index]</code> - <?php _e( 'Zeigt alle Glossareinträge als Index an', 'easy-glossary' ); ?></p>
                        
                        <h4><?php _e( 'Automatische Tooltips', 'easy-glossary' ); ?></h4>
                        <p><?php _e( 'Glossarbegriffe werden automatisch im Content erkannt und mit Tooltips versehen.', 'easy-glossary' ); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .easy-glossary-dashboard {
            margin-top: 20px;
        }
        
        .dashboard-widgets {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .dashboard-widget {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            padding: 20px;
        }
        
        .dashboard-widget h3 {
            margin-top: 0;
            color: #1d2327;
            border-bottom: 1px solid #e1e5e9;
            padding-bottom: 10px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px 0;
        }
        
        .stat-number {
            display: block;
            font-size: 36px;
            font-weight: bold;
            color: #0073aa;
        }
        
        .stat-label {
            display: block;
            color: #646970;
            margin-top: 5px;
        }
        
        .instruction-box {
            background: #f6f7f7;
            border: 1px solid #c3c4c7;
            border-radius: 6px;
            padding: 20px;
        }
        
        .instruction-box code {
            background: #2271b1;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#fix-permalinks-btn').on('click', function() {
                const button = $(this);
                const originalText = button.html();
                
                button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Repariere...');
                
                $.ajax({
                    url: easyGlossary.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'easy_glossary_fix_permalinks',
                        nonce: easyGlossary.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✓ ' + response.data.message);
                            if (response.data.test_results) {
                                console.log('Permalink-Test:', response.data.test_results);
                            }
                        } else {
                            alert('✗ Fehler: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('✗ Netzwerkfehler beim Reparieren der Permalinks');
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render import/export page
     */
    public function render_import_export() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'easyGlossary - Import & Export', 'easy-glossary' ); ?></h1>
            
            <div class="import-export-section">
                <div class="import-section">
                    <h2><?php _e( 'Glossar Import', 'easy-glossary' ); ?></h2>
                    <p><?php _e( 'Importieren Sie Glossareinträge aus einer CSV-Datei.', 'easy-glossary' ); ?></p>
                    
                    <form id="glossary-import-form" enctype="multipart/form-data">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e( 'CSV-Datei', 'easy-glossary' ); ?></th>
                                <td>
                                    <input type="file" id="glossary_file" name="glossary_file" accept=".csv" required>
                                    <p class="description"><?php _e( 'CSV-Format: Titel, Beschreibung, Inhalt', 'easy-glossary' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e( 'Optionen', 'easy-glossary' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="overwrite_existing" value="1">
                                        <?php _e( 'Vorhandene Einträge überschreiben', 'easy-glossary' ); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" name="auto_publish" value="1" checked>
                                        <?php _e( 'Einträge automatisch veröffentlichen', 'easy-glossary' ); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-upload"></span>
                                <?php _e( 'Glossar importieren', 'easy-glossary' ); ?>
                            </button>
                        </p>
                    </form>
                    
                    <div id="import-progress" style="display: none;">
                        <h3><?php _e( 'Import läuft...', 'easy-glossary' ); ?></h3>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 0%"></div>
                        </div>
                        <div id="import-log"></div>
                    </div>
                </div>
                
                <div class="export-section">
                    <h2><?php _e( 'Glossar Export', 'easy-glossary' ); ?></h2>
                    <p><?php _e( 'Exportieren Sie alle Glossareinträge als CSV-Datei.', 'easy-glossary' ); ?></p>
                    
                    <p>
                        <a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=easy_glossary_export' ), 'easy_glossary_export' ); ?>" 
                           class="button button-secondary">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e( 'Glossar exportieren', 'easy-glossary' ); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <style>
        .import-export-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 20px;
        }
        
        .import-section, .export-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f1f1f1;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: #0073aa;
            transition: width 0.3s ease;
        }
        
        #import-log {
            max-height: 200px;
            overflow-y: auto;
            background: #f6f7f7;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 12px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#glossary-import-form').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'easy_glossary_import');
                formData.append('nonce', easyGlossary.nonce);
                
                $('#import-progress').show();
                $('.progress-fill').css('width', '0%');
                $('#import-log').empty();
                
                $.ajax({
                    url: easyGlossary.ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('.progress-fill').css('width', '100%');
                        if (response.success) {
                            $('#import-log').append('<div style="color: green;">✓ ' + response.data.message + '</div>');
                            if (response.data.imported) {
                                $('#import-log').append('<div>Importierte Einträge: ' + response.data.imported + '</div>');
                            }
                        } else {
                            $('#import-log').append('<div style="color: red;">✗ ' + response.data + '</div>');
                        }
                    },
                    error: function() {
                        $('#import-log').append('<div style="color: red;">✗ Netzwerkfehler</div>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        // WordPress Color Picker enqueuen
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        
        if ( isset( $_POST['submit'] ) ) {
            check_admin_referer( 'easy_glossary_settings' );
            
            // Ausgeschlossene Post-Types
            $excluded_post_types = array();
            if ( isset( $_POST['excluded_post_types'] ) && is_array( $_POST['excluded_post_types'] ) ) {
                $excluded_post_types = array_map( 'sanitize_text_field', $_POST['excluded_post_types'] );
            }
            
            // Ausgeschlossene Seiten
            $excluded_pages = array();
            if ( isset( $_POST['excluded_pages'] ) && is_array( $_POST['excluded_pages'] ) ) {
                $excluded_pages = array_map( 'intval', $_POST['excluded_pages'] );
            }
            
            // Erlaubte Post-Types
            $allowed_post_types = array();
            if ( isset( $_POST['allowed_post_types'] ) && is_array( $_POST['allowed_post_types'] ) ) {
                $allowed_post_types = array_map( 'sanitize_text_field', $_POST['allowed_post_types'] );
            }
            
            $settings = array(
                // Auto-Linking
                'enable_auto_linking' => isset( $_POST['enable_auto_linking'] ),
                'link_all_occurrences' => isset( $_POST['link_all_occurrences'] ),
                'case_sensitive' => isset( $_POST['case_sensitive'] ),
                'exclude_homepage' => isset( $_POST['exclude_homepage'] ),
                'excluded_post_types' => $excluded_post_types,
                'excluded_pages' => $excluded_pages,
                'allowed_post_types' => $allowed_post_types,
                
                // Tooltips
                'enable_tooltips' => isset( $_POST['enable_tooltips'] ),
                'tooltip_style' => sanitize_text_field( $_POST['tooltip_style'] ?? 'default' ),
                'ajax_tooltips' => isset( $_POST['ajax_tooltips'] ),
                'tooltip_trigger' => sanitize_text_field( $_POST['tooltip_trigger'] ?? 'hover' ),
                'external_links_in_tooltip' => isset( $_POST['external_links_in_tooltip'] ),
                
                // Design & Farben
                'primary_button_color' => sanitize_hex_color( $_POST['primary_button_color'] ?? '#e64946' ),
                'primary_button_hover' => sanitize_hex_color( $_POST['primary_button_hover'] ?? '#d43835' ),
                'secondary_button_color' => sanitize_hex_color( $_POST['secondary_button_color'] ?? '#000000' ),
                'secondary_button_hover' => sanitize_hex_color( $_POST['secondary_button_hover'] ?? '#333333' ),
                'link_color' => sanitize_hex_color( $_POST['link_color'] ?? '#e64946' ),
                'link_hover_color' => sanitize_hex_color( $_POST['link_hover_color'] ?? '#d43835' ),
                'heading_color' => sanitize_hex_color( $_POST['heading_color'] ?? '#000000' ),
                'text_color' => sanitize_hex_color( $_POST['text_color'] ?? '#000000' ),
                'text_light_color' => sanitize_hex_color( $_POST['text_light_color'] ?? '#666666' ),
                
                // Legacy
                'auto_tooltips' => isset( $_POST['enable_tooltips'] ),
                'whole_words_only' => true
            );
            
            update_option( 'easy_glossary_settings', $settings );
            do_action( 'easy_glossary_settings_updated' );
            
            echo '<div class="notice notice-success"><p>' . __( 'Einstellungen gespeichert!', 'easy-glossary' ) . '</p></div>';
        }
        
        $defaults = array(
            'enable_auto_linking' => true,
            'link_all_occurrences' => false,
            'case_sensitive' => false,
            'exclude_homepage' => true,
            'excluded_post_types' => array(),
            'excluded_pages' => array(),
            'allowed_post_types' => array( 'post', 'page' ),
            'enable_tooltips' => true,
            'tooltip_style' => 'default',
            'ajax_tooltips' => false,
            'tooltip_trigger' => 'hover',
            'external_links_in_tooltip' => false,
            'primary_button_color' => '#e64946',
            'primary_button_hover' => '#d43835',
            'secondary_button_color' => '#000000',
            'secondary_button_hover' => '#333333',
            'link_color' => '#e64946',
            'link_hover_color' => '#d43835',
            'heading_color' => '#000000',
            'text_color' => '#000000',
            'text_light_color' => '#666666',
            'auto_tooltips' => true,
            'whole_words_only' => true
        );
        
        $settings = wp_parse_args( get_option( 'easy_glossary_settings', array() ), $defaults );
        
        // Alle Post-Types für Auswahl
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        unset( $post_types['easy_glossary'] ); // Glossar selbst ausschließen
        
        // Alle Seiten für Ausschluss-Liste
        $pages = get_pages( array( 'post_status' => 'publish' ) );
        ?>
        <div class="wrap">
            <h1><?php _e( 'easyGlossary Einstellungen', 'easy-glossary' ); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="#auto-linking" class="nav-tab nav-tab-active"><?php _e( 'Auto-Linking', 'easy-glossary' ); ?></a>
                <a href="#tooltips" class="nav-tab"><?php _e( 'Tooltips', 'easy-glossary' ); ?></a>
                <a href="#design" class="nav-tab"><?php _e( 'Design & Farben', 'easy-glossary' ); ?></a>
                <a href="#exclusions" class="nav-tab"><?php _e( 'Ausschlüsse', 'easy-glossary' ); ?></a>
            </h2>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'easy_glossary_settings' ); ?>
                
                <!-- Auto-Linking Tab -->
                <div id="auto-linking" class="tab-content active">
                    <h2><?php _e( 'Automatische Verlinkung', 'easy-glossary' ); ?></h2>
                    <p class="description"><?php _e( 'Konfigurieren Sie, wie Glossar-Begriffe automatisch in Ihrem Content verlinkt werden.', 'easy-glossary' ); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Auto-Linking aktivieren', 'easy-glossary' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_auto_linking" value="1" <?php checked( $settings['enable_auto_linking'] ); ?>>
                                    <?php _e( 'Glossarbegriffe automatisch verlinken', 'easy-glossary' ); ?>
                                </label>
                                <p class="description"><?php _e( 'Aktiviert die automatische Verlinkung von Glossar-Begriffen in Posts und Pages.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Verlinkungsmodus', 'easy-glossary' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="link_all_occurrences" value="1" <?php checked( $settings['link_all_occurrences'] ); ?>>
                                    <?php _e( 'Alle Vorkommen verlinken', 'easy-glossary' ); ?>
                                </label>
                                <p class="description"><?php _e( 'Wenn deaktiviert, wird nur das erste Vorkommen eines Begriffs verlinkt.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Groß-/Kleinschreibung', 'easy-glossary' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="case_sensitive" value="1" <?php checked( $settings['case_sensitive'] ); ?>>
                                    <?php _e( 'Groß-/Kleinschreibung beachten', 'easy-glossary' ); ?>
                                </label>
                                <p class="description"><?php _e( 'Wenn aktiviert, muss die Schreibweise exakt übereinstimmen.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Erlaubte Post-Types', 'easy-glossary' ); ?></th>
                            <td>
                                <?php foreach ( $post_types as $post_type ) : ?>
                                    <label style="display: block; margin-bottom: 5px;">
                                        <input type="checkbox" 
                                               name="allowed_post_types[]" 
                                               value="<?php echo esc_attr( $post_type->name ); ?>"
                                               <?php checked( in_array( $post_type->name, $settings['allowed_post_types'] ) ); ?>>
                                        <?php echo esc_html( $post_type->label ); ?>
                                    </label>
                                <?php endforeach; ?>
                                <p class="description"><?php _e( 'Wählen Sie die Post-Types aus, in denen Auto-Linking aktiv sein soll.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Tooltips Tab -->
                <div id="tooltips" class="tab-content">
                    <h2><?php _e( 'Tooltip-System', 'easy-glossary' ); ?></h2>
                    <p class="description"><?php _e( 'Konfigurieren Sie das Verhalten und Aussehen der Tooltips.', 'easy-glossary' ); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Tooltips aktivieren', 'easy-glossary' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_tooltips" value="1" <?php checked( $settings['enable_tooltips'] ); ?>>
                                    <?php _e( 'Tooltips für verlinkte Begriffe anzeigen', 'easy-glossary' ); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Tooltip-Trigger', 'easy-glossary' ); ?></th>
                            <td>
                                <select name="tooltip_trigger">
                                    <option value="hover" <?php selected( $settings['tooltip_trigger'], 'hover' ); ?>><?php _e( 'Hover (Desktop)', 'easy-glossary' ); ?></option>
                                    <option value="click" <?php selected( $settings['tooltip_trigger'], 'click' ); ?>><?php _e( 'Click (Mobile-optimiert)', 'easy-glossary' ); ?></option>
                                    <option value="both" <?php selected( $settings['tooltip_trigger'], 'both' ); ?>><?php _e( 'Hover + Click', 'easy-glossary' ); ?></option>
                                </select>
                                <p class="description"><?php _e( 'Wie sollen Tooltips ausgelöst werden?', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Tooltip-Stil', 'easy-glossary' ); ?></th>
                            <td>
                                <select name="tooltip_style">
                                    <option value="default" <?php selected( $settings['tooltip_style'], 'default' ); ?>><?php _e( 'Standard', 'easy-glossary' ); ?></option>
                                    <option value="dark" <?php selected( $settings['tooltip_style'], 'dark' ); ?>><?php _e( 'Dunkel', 'easy-glossary' ); ?></option>
                                    <option value="light" <?php selected( $settings['tooltip_style'], 'light' ); ?>><?php _e( 'Hell', 'easy-glossary' ); ?></option>
                                    <option value="minimal" <?php selected( $settings['tooltip_style'], 'minimal' ); ?>><?php _e( 'Minimal', 'easy-glossary' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'AJAX-Loading', 'easy-glossary' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ajax_tooltips" value="1" <?php checked( $settings['ajax_tooltips'] ); ?>>
                                    <?php _e( 'Tooltip-Content per AJAX laden', 'easy-glossary' ); ?>
                                </label>
                                <p class="description"><?php _e( 'Verbessert Performance bei vielen Begriffen. Content wird erst beim Hover geladen.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Externe Links in Tooltips', 'easy-glossary' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="external_links_in_tooltip" value="1" <?php checked( $settings['external_links_in_tooltip'] ); ?>>
                                    <?php _e( 'Externe Links statt Beschreibung anzeigen', 'easy-glossary' ); ?>
                                </label>
                                <p class="description"><?php _e( 'Wenn ein externer Link hinterlegt ist, wird dieser im Tooltip angezeigt statt der Beschreibung.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Design & Farben Tab -->
                <div id="design" class="tab-content">
                    <h2><?php _e( 'Design & Farben', 'easy-glossary' ); ?></h2>
                    <p class="description"><?php _e( 'Passen Sie die Farben der Buttons und UI-Elemente an Ihr Theme an.', 'easy-glossary' ); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Primäre Button-Farbe', 'easy-glossary' ); ?></th>
                            <td>
                                <input type="text" 
                                       name="primary_button_color" 
                                       value="<?php echo esc_attr( $settings['primary_button_color'] ); ?>" 
                                       class="color-picker"
                                       data-default-color="#e64946">
                                <p class="description"><?php _e( 'Farbe für primäre Buttons (z.B. "Zurück zum Glossar", externe Links, Synonym-Tags).', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Primäre Button-Farbe (Hover)', 'easy-glossary' ); ?></th>
                            <td>
                                <input type="text" 
                                       name="primary_button_hover" 
                                       value="<?php echo esc_attr( $settings['primary_button_hover'] ); ?>" 
                                       class="color-picker"
                                       data-default-color="#d43835">
                                <p class="description"><?php _e( 'Hover-Farbe für primäre Buttons.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Sekundäre Button-Farbe', 'easy-glossary' ); ?></th>
                            <td>
                                <input type="text" 
                                       name="secondary_button_color" 
                                       value="<?php echo esc_attr( $settings['secondary_button_color'] ); ?>" 
                                       class="color-picker"
                                       data-default-color="#000000">
                                <p class="description"><?php _e( 'Farbe für sekundäre Buttons (z.B. "Bearbeiten"-Button).', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Sekundäre Button-Farbe (Hover)', 'easy-glossary' ); ?></th>
                            <td>
                                <input type="text" 
                                       name="secondary_button_hover" 
                                       value="<?php echo esc_attr( $settings['secondary_button_hover'] ); ?>" 
                                       class="color-picker"
                                       data-default-color="#333333">
                                <p class="description"><?php _e( 'Hover-Farbe für sekundäre Buttons.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th colspan="2"><hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;"></th>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Link-Farbe', 'easy-glossary' ); ?></th>
                            <td>
                                <input type="text" 
                                       name="link_color" 
                                       value="<?php echo esc_attr( $settings['link_color'] ); ?>" 
                                       class="color-picker"
                                       data-default-color="#e64946">
                                <p class="description"><?php _e( 'Farbe für alle Links in Glossar-Seiten (Begriffstitel, "Mehr erfahren", etc.).', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Link-Farbe (Hover)', 'easy-glossary' ); ?></th>
                            <td>
                                <input type="text" 
                                       name="link_hover_color" 
                                       value="<?php echo esc_attr( $settings['link_hover_color'] ); ?>" 
                                       class="color-picker"
                                       data-default-color="#d43835">
                                <p class="description"><?php _e( 'Hover-Farbe für alle Links.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th colspan="2"><hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;"></th>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Überschriften-Farbe', 'easy-glossary' ); ?></th>
                            <td>
                                <input type="text" 
                                       name="heading_color" 
                                       value="<?php echo esc_attr( $settings['heading_color'] ); ?>" 
                                       class="color-picker"
                                       data-default-color="#000000">
                                <p class="description"><?php _e( 'Farbe für alle Überschriften (H1, H2, H3, etc.).', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Text-Farbe', 'easy-glossary' ); ?></th>
                            <td>
                                <input type="text" 
                                       name="text_color" 
                                       value="<?php echo esc_attr( $settings['text_color'] ); ?>" 
                                       class="color-picker"
                                       data-default-color="#000000">
                                <p class="description"><?php _e( 'Hauptfarbe für normalen Text.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Text-Farbe (Hell)', 'easy-glossary' ); ?></th>
                            <td>
                                <input type="text" 
                                       name="text_light_color" 
                                       value="<?php echo esc_attr( $settings['text_light_color'] ); ?>" 
                                       class="color-picker"
                                       data-default-color="#666666">
                                <p class="description"><?php _e( 'Farbe für sekundären Text (Meta-Infos, Beschreibungen, etc.).', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Exclusions Tab -->
                <div id="exclusions" class="tab-content">
                    <h2><?php _e( 'Ausschlüsse', 'easy-glossary' ); ?></h2>
                    <p class="description"><?php _e( 'Definieren Sie, wo Auto-Linking NICHT aktiv sein soll.', 'easy-glossary' ); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Startseite ausschließen', 'easy-glossary' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="exclude_homepage" value="1" <?php checked( $settings['exclude_homepage'] ); ?>>
                                    <?php _e( 'Auto-Linking auf Startseite deaktivieren', 'easy-glossary' ); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Ausgeschlossene Post-Types', 'easy-glossary' ); ?></th>
                            <td>
                                <?php foreach ( $post_types as $post_type ) : ?>
                                    <label style="display: block; margin-bottom: 5px;">
                                        <input type="checkbox" 
                                               name="excluded_post_types[]" 
                                               value="<?php echo esc_attr( $post_type->name ); ?>"
                                               <?php checked( in_array( $post_type->name, $settings['excluded_post_types'] ) ); ?>>
                                        <?php echo esc_html( $post_type->label ); ?>
                                    </label>
                                <?php endforeach; ?>
                                <p class="description"><?php _e( 'Post-Types, die vom Auto-Linking ausgeschlossen werden sollen.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e( 'Ausgeschlossene Seiten', 'easy-glossary' ); ?></th>
                            <td>
                                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                                    <?php if ( ! empty( $pages ) ) : ?>
                                        <?php foreach ( $pages as $page ) : ?>
                                            <label style="display: block; margin-bottom: 5px;">
                                                <input type="checkbox" 
                                                       name="excluded_pages[]" 
                                                       value="<?php echo esc_attr( $page->ID ); ?>"
                                                       <?php checked( in_array( $page->ID, $settings['excluded_pages'] ) ); ?>>
                                                <?php echo esc_html( get_the_title( $page->ID ) ); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <p><?php _e( 'Keine Seiten vorhanden.', 'easy-glossary' ); ?></p>
                                    <?php endif; ?>
                                </div>
                                <p class="description"><?php _e( 'Spezifische Seiten, auf denen Auto-Linking deaktiviert werden soll.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <style>
        .nav-tab-wrapper {
            margin-bottom: 20px;
        }
        
        .tab-content {
            display: none;
            background: #fff;
            padding: 20px;
            border: 1px solid #c3c4c7;
            border-top: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .tab-content h2 {
            margin-top: 0;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Color Picker initialisieren
            $('.color-picker').wpColorPicker();
            
            // Tab Navigation
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Import glossary
     */
    public function ajax_import_glossary() {
        check_ajax_referer( 'easy_glossary_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Keine Berechtigung', 'easy-glossary' ) );
        }
        
        if ( ! isset( $_FILES['glossary_file'] ) ) {
            wp_send_json_error( __( 'Keine Datei hochgeladen', 'easy-glossary' ) );
        }
        
        $file = $_FILES['glossary_file'];
        $overwrite = isset( $_POST['overwrite_existing'] );
        $auto_publish = isset( $_POST['auto_publish'] );
        
        // Validate file
        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( __( 'Upload-Fehler', 'easy-glossary' ) );
        }
        
        // Read CSV
        $handle = fopen( $file['tmp_name'], 'r' );
        if ( ! $handle ) {
            wp_send_json_error( __( 'Datei konnte nicht gelesen werden', 'easy-glossary' ) );
        }
        
        $imported = 0;
        $row = 0;
        
        while ( ( $data = fgetcsv( $handle, 1000, ',' ) ) !== FALSE ) {
            $row++;
            
            // Skip header row
            if ( $row === 1 && ( $data[0] === 'Titel' || $data[0] === 'Title' ) ) {
                continue;
            }
            
            if ( count( $data ) < 2 ) {
                continue;
            }
            
            $title = sanitize_text_field( $data[0] );
            $excerpt = sanitize_textarea_field( $data[1] ?? '' );
            $content = wp_kses_post( $data[2] ?? $excerpt );
            
            if ( empty( $title ) ) {
                continue;
            }
            
            // Check if exists
            $existing = get_page_by_title( $title, OBJECT, 'easy_glossary' );
            
            if ( $existing && ! $overwrite ) {
                continue;
            }
            
            $post_data = array(
                'post_title' => $title,
                'post_content' => $content,
                'post_excerpt' => $excerpt,
                'post_type' => 'easy_glossary',
                'post_status' => $auto_publish ? 'publish' : 'draft'
            );
            
            if ( $existing && $overwrite ) {
                $post_data['ID'] = $existing->ID;
                wp_update_post( $post_data );
            } else {
                wp_insert_post( $post_data );
            }
            
            $imported++;
        }
        
        fclose( $handle );
        
        // Clear cache
        $this->clear_glossary_cache();
        
        wp_send_json_success( array(
            'message' => sprintf( __( 'Import erfolgreich abgeschlossen!', 'easy-glossary' ) ),
            'imported' => $imported
        ) );
    }
    
    /**
     * AJAX: Export glossary
     */
    public function ajax_export_glossary() {
        check_admin_referer( 'easy_glossary_export' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Keine Berechtigung', 'easy-glossary' ) );
        }
        
        $terms = get_posts( array(
            'post_type' => 'easy_glossary',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ) );
        
        // Set headers for CSV download
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename="glossary-export-' . date( 'Y-m-d' ) . '.csv"' );
        
        $output = fopen( 'php://output', 'w' );
        
        // Write header
        fputcsv( $output, array( 'Titel', 'Beschreibung', 'Inhalt' ) );
        
        // Write data
        foreach ( $terms as $term ) {
            fputcsv( $output, array(
                get_the_title( $term->ID ),
                get_the_excerpt( $term->ID ),
                wp_strip_all_tags( get_the_content( null, false, $term->ID ) )
            ) );
        }
        
        fclose( $output );
        exit;
    }
    
    /**
     * AJAX: Debug glossary functionality
     */
    public function ajax_debug_glossary() {
        check_ajax_referer( 'easy_glossary_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Keine Berechtigung', 'easy-glossary' ) );
        }
        
        $debug_info = array();
        
        // Glossar-Einträge abrufen
        $terms = $this->get_glossary_terms();
        $debug_info['terms_count'] = count( $terms );
        $debug_info['terms'] = array();
        
        foreach ( $terms as $term ) {
            $debug_info['terms'][] = array(
                'id' => $term->ID,
                'title' => get_the_title( $term->ID ),
                'excerpt' => get_the_excerpt( $term->ID ),
                'status' => $term->post_status,
                'permalink' => get_permalink( $term->ID )
            );
        }
        
        // Einstellungen
        $settings = get_option( 'easy_glossary_settings', array() );
        $debug_info['settings'] = $settings;
        
        // Cache-Status
        $cache_key = 'easy_glossary_terms';
        $cached_terms = wp_cache_get( $cache_key );
        $debug_info['cache_active'] = ( $cached_terms !== false );
        
        wp_send_json_success( $debug_info );
    }
    
    /**
     * AJAX: Fix permalinks
     */
    public function ajax_fix_permalinks() {
        check_ajax_referer( 'easy_glossary_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Keine Berechtigung', 'easy-glossary' ) );
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear cache
        $this->clear_glossary_cache();
        
        // Test permalinks
        $terms = get_posts( array(
            'post_type' => 'easy_glossary',
            'post_status' => 'publish',
            'numberposts' => 5
        ) );
        
        $test_results = array();
        foreach ( $terms as $term ) {
            $permalink = $this->get_glossary_permalink( $term->ID );
            $test_results[] = array(
                'title' => get_the_title( $term->ID ),
                'permalink' => $permalink,
                'status' => ! empty( $permalink ) ? 'OK' : 'FEHLER'
            );
        }
        
        wp_send_json_success( array(
            'message' => __( 'Permalinks wurden repariert!', 'easy-glossary' ),
            'test_results' => $test_results
        ) );
    }
    
    /**
     * Add custom rewrite rules for glossary
     */
    public function add_rewrite_rules() {
        // Add rewrite rule for glossary archive
        add_rewrite_rule(
            '^glossar/?$',
            'index.php?post_type=easy_glossary',
            'top'
        );
        
        // Add rewrite rule for individual glossary entries
        add_rewrite_rule(
            '^glossar/([^/]+)/?$',
            'index.php?easy_glossary=$matches[1]',
            'top'
        );
    }
    
    /**
     * Custom permalink structure for glossary entries
     */
    public function custom_glossary_permalink( $post_link, $post ) {
        if ( $post->post_type === 'easy_glossary' ) {
            $post_link = home_url( '/glossar/' . $post->post_name . '/' );
        }
        return $post_link;
    }
    
    /**
     * Handle glossary redirects and 404s
     */
    public function handle_glossary_redirects() {
        global $wp_query;
        
        // Check if we're on a glossary page that doesn't exist
        if ( is_404() && isset( $wp_query->query_vars['easy_glossary'] ) ) {
            $glossary_slug = $wp_query->query_vars['easy_glossary'];
            
            // Try to find the glossary entry
            $glossary_post = get_page_by_path( $glossary_slug, OBJECT, 'easy_glossary' );
            
            if ( $glossary_post ) {
                // Redirect to the correct URL
                wp_redirect( get_permalink( $glossary_post->ID ), 301 );
                exit;
            }
        }
    }
    
    /**
     * Load glossary template
     */
    public function load_glossary_template( $template ) {
        if ( is_singular( 'easy_glossary' ) ) {
            $plugin_template = EASY_GLOSSARY_PLUGIN_DIR . 'single-easy_glossary.php';
            
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }
        
        // Handle glossary archive
        if ( is_post_type_archive( 'easy_glossary' ) ) {
            $archive_template = EASY_GLOSSARY_PLUGIN_DIR . 'archive-easy_glossary.php';
            
            if ( file_exists( $archive_template ) ) {
                return $archive_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Sort terms by length (longest first)
     */
    public function sort_terms_by_length( $a, $b ) {
        return strlen( get_the_title( $b->ID ) ) - strlen( get_the_title( $a->ID ) );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Register post type first
        $this->register_post_type();
        
        // Add custom rewrite rules
        $this->add_rewrite_rules();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        if ( ! get_option( 'easy_glossary_settings' ) ) {
            add_option( 'easy_glossary_settings', array(
                'auto_tooltips' => true,
                'tooltip_style' => 'default',
                'case_sensitive' => false,
                'whole_words_only' => true
            ) );
        }
        
        // Debug: Log activation
        error_log( 'easyGlossary: Plugin aktiviert - Rewrite Rules gesetzt' );
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear cache
        $this->clear_glossary_cache();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Glossary analytics table
        $table_name = $wpdb->prefix . 'easy_glossary_analytics';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            term_id bigint(20) NOT NULL,
            views bigint(20) DEFAULT 0,
            tooltip_clicks bigint(20) DEFAULT 0,
            last_viewed datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY term_id (term_id)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    /**
     * Register with Admin UX System
     */
    public function register_admin_ux() {
        if ( function_exists( 'easy_register_admin_ux' ) ) {
            easy_register_admin_ux( 'easy-glossary', array(
                'auto_save',
                'enhanced_tables'
            ) );
        }
    }
}

/**
 * Auto-save functionality for Admin UX integration
 */
function easy_init_auto_save_easy_glossary() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Auto-save for Easy Glossary forms
        $('.easy-glossary-form input, .easy-glossary-form select, .easy-glossary-form textarea').on('change', function() {
            var form = $(this).closest('form');
            if (form.length) {
                // Debounce auto-save
                clearTimeout(window.easyGlossaryAutoSave);
                window.easyGlossaryAutoSave = setTimeout(function() {
                    form.submit();
                }, 1000);
            }
        });
    });
    </script>
    <?php
}

/**
 * Initialize easyGlossary
 */
function Easy_Glossary() {
    return Easy_Glossary::instance();
}

// Start the plugin
Easy_Glossary();
