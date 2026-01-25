<?php
namespace PhinIT\Messages\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use PhinIT\Messages\Database\ConversationManager;

class MessagesController {

    private $db_manager;

    public function __construct() {
        $this->db_manager = new ConversationManager();
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        $namespace = 'it-expert/v1';

        register_rest_route( $namespace, '/messages/send', array(
            'methods' => 'POST',
            'callback' => array( $this, 'send_message' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $namespace, '/messages/conversations', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_conversations' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $namespace, '/messages/conversations/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_conversation_details' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ) );
    }

    public function send_message( $request ) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();

        $to_user_id = isset( $params['to_user_id'] ) ? intval( $params['to_user_id'] ) : 0;
        $content = isset( $params['content'] ) ? sanitize_textarea_field( $params['content'] ) : '';
        $conversation_id = isset( $params['conversation_id'] ) ? intval( $params['conversation_id'] ) : 0;

        if ( empty( $content ) ) {
            return new \WP_Error( 'empty_content', 'Message cannot be empty', array( 'status' => 400 ) );
        }

        if ( ! $conversation_id && ! $to_user_id ) {
            return new \WP_Error( 'missing_recipient', 'Recipient required for new conversation', array( 'status' => 400 ) );
        }

        // Logic: Create conversation if not exists, else add to existing
        if ( ! $conversation_id ) {
            // Check if user exists
            if ( ! get_userdata( $to_user_id ) ) {
                return new \WP_Error( 'invalid_recipient', 'User does not exist', array( 'status' => 404 ) );
            }
            $conversation_id = $this->db_manager->create_conversation( array( $user_id, $to_user_id ) );
        }

        $message_id = $this->db_manager->add_message( $conversation_id, $user_id, $content );

        return new \WP_REST_Response( array( 
            'success' => true, 
            'conversation_id' => $conversation_id,
            'message_id' => $message_id
        ), 200 );
    }

    public function get_conversations( $request ) {
        $user_id = get_current_user_id();
        $conversations = $this->db_manager->get_user_conversations( $user_id );
        return new \WP_REST_Response( $conversations, 200 );
    }

    public function get_conversation_details( $request ) {
        $user_id = get_current_user_id();
        $conversation_id = $request['id'];

        $messages = $this->db_manager->get_messages( $conversation_id, $user_id );

        if ( is_wp_error( $messages ) ) {
            return $messages;
        }

        // Mark as read
        $this->db_manager->mark_read( $conversation_id, $user_id );

        return new \WP_REST_Response( $messages, 200 );
    }

    public function check_permission() {
        return is_user_logged_in();
    }
}
