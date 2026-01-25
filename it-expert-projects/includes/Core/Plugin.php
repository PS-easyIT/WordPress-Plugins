<?php
namespace PhinIT\Projects\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use PhinIT\Projects\PostTypes\Project;
use PhinIT\Projects\Admin\MetaBoxes;
use PhinIT\Projects\Team\Manager;

class Plugin {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        new Project();
        if ( is_admin() ) {
            new MetaBoxes();
        }
    }

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'it_expert_project_members';

        $sql = "CREATE TABLE $table_name (
            project_id bigint(20) NOT NULL,
            expert_id bigint(20) NOT NULL,
            role varchar(100) NOT NULL,
            PRIMARY KEY  (project_id, expert_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        flush_rewrite_rules();
    }
}
