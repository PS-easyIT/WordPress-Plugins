<?php
namespace PhinIT\Promos\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use PhinIT\Promos\PostTypes\Promo;

class Plugin {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        new Promo();
    }

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'it_expert_promo_usage';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            promo_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            used_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            referral_source varchar(255) DEFAULT '',
            PRIMARY KEY  (id),
            KEY promo_id (promo_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}
