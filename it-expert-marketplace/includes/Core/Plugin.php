<?php
namespace PhinIT\Marketplace\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use PhinIT\Marketplace\PostTypes\Listing;
use PhinIT\Marketplace\Admin\MetaBoxes;

class Plugin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        new Listing();
        if ( is_admin() ) {
            new MetaBoxes();
        }
        new \PhinIT\Marketplace\Frontend\Form();
    }
}
