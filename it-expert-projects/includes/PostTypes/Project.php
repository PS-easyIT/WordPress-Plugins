<?php
namespace PhinIT\Projects\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Project {
    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
    }

    public function register_cpt() {
        register_post_type( 'expert_project', array(
            'labels' => array(
                'name' => 'Projekte',
                'singular_name' => 'Projekt',
                'all_items' => 'Alle Projekte',
                'add_new' => 'Neues Projekt',
                'add_new_item' => 'Neues Projekt erstellen',
                'edit_item' => 'Projekt bearbeiten',
                'menu_name' => 'Projekte'
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-portfolio',
            'supports' => array( 'title', 'editor', 'thumbnail', 'author', 'custom-fields' ),
            'rewrite' => array( 'slug' => 'projects' ),
            'show_in_rest' => true
        ) );
    }

    public function register_taxonomies() {
        $taxonomies = array(
            'project_category' => array( 'Projekt Kategorie', 'Projekt Kategorien' ),
            'project_technology' => array( 'Technologie', 'Technologien' ),
            'project_industry' => array( 'Branche', 'Branchen' )
        );

        foreach ( $taxonomies as $slug => $labels ) {
            register_taxonomy( $slug, 'expert_project', array(
                'labels' => array(
                    'name' => $labels[1],
                    'singular_name' => $labels[0],
                ),
                'hierarchical' => true,
                'show_admin_column' => true,
                'show_in_rest' => true
            ) );
        }
    }
}
