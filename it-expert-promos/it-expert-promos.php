<?php
/**
 * Plugin Name: IT Expert Hub - Promos & Deals
 * Plugin URI: https://phinit.solutions
 * Description: Modul für Promos, Deals und Gutscheine.
 * Version: 1.0.0
 * Author: PHIN IT Solutions
 * Text Domain: it-expert-promos
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Constants
define( 'IT_EXPERT_PROMOS_VERSION', '1.0.0' );
define( 'IT_EXPERT_PROMOS_PATH', plugin_dir_path( __FILE__ ) );
define( 'IT_EXPERT_PROMOS_URL', plugin_dir_url( __FILE__ ) );

// Autoloader
spl_autoload_register( function ( $class ) {
    $prefix = 'PhinIT\\Promos\\';
    $base_dir = IT_EXPERT_PROMOS_PATH . 'includes/';
    
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
add_action( 'plugins_loaded', array( 'PhinIT\Promos\Core\Plugin', 'get_instance' ) );
register_activation_hook( __FILE__, array( 'PhinIT\Promos\Core\Plugin', 'activate' ) );
