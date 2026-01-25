<?php
namespace PhinIT\Promos\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Promo {
    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'init', array( $this, 'register_taxonomy' ) );
    }

    public function register_cpt() {
        register_post_type( 'promo_deal', array(
            'labels' => array(
                'name' => 'Promos',
                'singular_name' => 'Promo',
                'menu_name' => 'Promos & Deals'
            ),
            'public' => true,
            'menu_icon' => 'dashicons-tickets-alt',
            'supports' => array( 'title', 'editor', 'thumbnail', 'author', 'custom-fields' ),
            'show_in_rest' => true
        ) );
    }

    public function register_taxonomy() {
        register_taxonomy( 'promo_category', 'promo_deal', array(
            'labels' => array(
                'name' => 'Kategorien',
                'singular_name' => 'Kategorie'
            ),
            'hierarchical' => true,
            'show_admin_column' => true,
            'show_in_rest' => true
        ) );
    }
}
