<?php
/**
 * Plugin Name: IT Expert Hub - Private Messages
 * Plugin URI: https://phinit.solutions
 * Description: Eigenes Messaging-System für IT Expert Hub (Direktnachrichten, Gruppen, Attachments).
 * Version: 1.0.0
 * Author: PHIN IT Solutions
 * Text Domain: it-expert-messages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Constants
define( 'IT_EXPERT_MESSAGES_VERSION', '1.0.0' );
define( 'IT_EXPERT_MESSAGES_PATH', plugin_dir_path( __FILE__ ) );
define( 'IT_EXPERT_MESSAGES_URL', plugin_dir_url( __FILE__ ) );

// Simple Autoloader
spl_autoload_register( function ( $class ) {
    $prefix = 'PhinIT\\Messages\\';
    $base_dir = IT_EXPERT_MESSAGES_PATH . 'includes/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

// Initialize
function it_expert_messages_init() {
    return \PhinIT\Messages\Core\Plugin::get_instance();
}

add_action( 'plugins_loaded', 'it_expert_messages_init' );

// Activation Hook
register_activation_hook( __FILE__, array( '\PhinIT\Messages\Core\Plugin', 'activate' ) );
