<?php
namespace PhinIT\Messages\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Assets {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
    }

    public function enqueue_frontend() {
        wp_register_script( 
            'it-expert-messages', 
            IT_EXPERT_MESSAGES_URL . 'assets/js/frontend.js', 
            array( 'jquery' ), 
            IT_EXPERT_MESSAGES_VERSION, 
            true 
        );

        wp_localize_script( 'it-expert-messages', 'ITExpertMessages', array(
            'root' => esc_url_raw( rest_url() ),
            'api_url' => esc_url_raw( rest_url( 'it-expert/v1' ) ),
            'nonce' => wp_create_nonce( 'wp_rest' )
        ) );
    }
}
