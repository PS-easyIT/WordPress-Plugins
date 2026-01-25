<?php
namespace PhinIT\Marketplace\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Listing {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'init', array( $this, 'register_taxonomy' ) );
    }

    public function register_cpt() {
        register_post_type( 'marketplace_listing', array(
            'labels' => array(
                'name' => __( 'Angebote', 'it-expert-marketplace' ),
                'singular_name' => __( 'Angebot', 'it-expert-marketplace' ),
                'menu_name' => __( 'Marketplace', 'it-expert-marketplace' ),
                'add_new' => __( 'Neues Angebot', 'it-expert-marketplace' ),
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-store',
            'supports' => array( 'title', 'editor', 'thumbnail', 'author', 'custom-fields' ),
            'show_in_rest' => true
        ) );
    }

    public function register_taxonomy() {
        register_taxonomy( 'marketplace_category', 'marketplace_listing', array(
            'labels' => array(
                'name' => __( 'Kategorien', 'it-expert-marketplace' ),
                'singular_name' => __( 'Kategorie', 'it-expert-marketplace' ),
            ),
            'hierarchical' => true,
            'show_admin_column' => true,
            'show_in_rest' => true
        ) );
    }
}
