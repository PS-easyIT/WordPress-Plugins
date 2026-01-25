<?php
namespace PhinIT\Messages\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use PhinIT\Messages\Database\Schema;
use PhinIT\Messages\Api\MessagesController;

class Plugin {

    private static $instance = null;
    private $api_controller;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
        $this->init_modules();
    }

    private function init_hooks() {
        // Localization
        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    private function init_modules() {
        // Initialize Assets
        new Assets();

        // Initialize API
        $this->api_controller = new MessagesController();

        // Initialize Frontend
        new \PhinIT\Messages\Frontend\Shortcodes();
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'it-expert-messages', false, dirname( plugin_basename( IT_EXPERT_MESSAGES_PATH . 'it-expert-messages.php' ) ) . '/languages' );
    }

    public static function activate() {
        Schema::create_tables();
        flush_rewrite_rules();
    }
}
