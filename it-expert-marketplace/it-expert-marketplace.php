<?php
/**
 * Plugin Name: IT Expert Hub - Marketplace
 * Plugin URI: https://phinit.solutions
 * Description: Marketplace Modul für Angebote (Lizenzen, Hardware, Services).
 * Version: 1.0.0
 * Author: PHIN IT Solutions
 * Text Domain: it-expert-marketplace
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Constants
define( 'IT_EXPERT_MARKETPLACE_VERSION', '1.0.0' );
define( 'IT_EXPERT_MARKETPLACE_PATH', plugin_dir_path( __FILE__ ) );
define( 'IT_EXPERT_MARKETPLACE_URL', plugin_dir_url( __FILE__ ) );

// Autoloader
spl_autoload_register( function ( $class ) {
    $prefix = 'PhinIT\\Marketplace\\';
    $base_dir = IT_EXPERT_MARKETPLACE_PATH . 'includes/';
    
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
add_action( 'plugins_loaded', array( 'PhinIT\Marketplace\Core\Plugin', 'get_instance' ) );
