<?php
namespace PhinIT\Messages\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Shortcodes {

    public function __construct() {
        add_shortcode( 'it_expert_messages', array( $this, 'render_messages_app' ) );
    }

    public function render_messages_app( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'Please login to view messages.', 'it-expert-messages' ) . '</p>';
        }

        wp_enqueue_script( 'it-expert-messages' );
        
        return '<div id="it-expert-messages-app">' . __( 'Loading messages...', 'it-expert-messages' ) . '</div>';
    }
}
