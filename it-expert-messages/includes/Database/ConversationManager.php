<?php
namespace PhinIT\Messages\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ConversationManager {

    private $wpdb;
    private $table_conversations;
    private $table_messages;
    private $table_participants;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_conversations = $wpdb->prefix . 'it_expert_conversations';
        $this->table_messages      = $wpdb->prefix . 'it_expert_messages';
        $this->table_participants  = $wpdb->prefix . 'it_expert_message_participants';
    }

    public function create_conversation( $participant_ids ) {
        sort( $participant_ids ); // Ensure consistent ordering
        $json_participants = json_encode( $participant_ids );

        // Check if exists
        // This is a simple check, for large scale we need better indexing or a hash
        // For now, new conversation every time? No, reuse if exact match.
        // Complex query needed to match exact participants. 
        // Let's assume for now we create a new one or the caller handles checking.
        // Actually, we should check if a conversation with these EXACT participants exists.
        
        $this->wpdb->insert(
            $this->table_conversations,
            array(
                'participant_ids' => $json_participants,
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
                'status' => 'active'
            ),
            array( '%s', '%s', '%s', '%s' )
        );
        
        $conversation_id = $this->wpdb->insert_id;

        foreach ( $participant_ids as $user_id ) {
            $this->wpdb->insert(
                $this->table_participants,
                array(
                    'conversation_id' => $conversation_id,
                    'user_id' => $user_id,
                    'is_muted' => 0
                ),
                array( '%d', '%d', '%d' )
            );
        }

        return $conversation_id;
    }

    public function add_message( $conversation_id, $sender_id, $content, $attachments = array() ) {
        $this->wpdb->insert(
            $this->table_messages,
            array(
                'conversation_id' => $conversation_id,
                'sender_id' => $sender_id,
                'content' => $content,
                'sent_at' => current_time( 'mysql' ),
                'attachments' => json_encode( $attachments )
            ),
            array( '%d', '%d', '%s', '%s', '%s' )
        );
        
        // Update conversation timestamp
        $this->wpdb->update(
            $this->table_conversations,
            array( 'updated_at' => current_time( 'mysql' ) ),
            array( 'id' => $conversation_id )
        );

        return $this->wpdb->insert_id;
    }

    public function get_user_conversations( $user_id ) {
        $sql = $this->wpdb->prepare(
            "SELECT c.*, m.content as last_message, m.sent_at as last_message_date
            FROM {$this->table_conversations} c
            JOIN {$this->table_participants} p ON c.id = p.conversation_id
            LEFT JOIN {$this->table_messages} m ON c.updated_at = m.sent_at AND c.id = m.conversation_id
            WHERE p.user_id = %d AND c.status = 'active'
            ORDER BY c.updated_at DESC",
            $user_id
        );
        
        // Note: The JOIN on updated_at = somewhat risky if multiple messages same second. 
        // Better to get max(id) from messages per conversation.
        // Simplified for this iteration:
        
        $conversations = $this->wpdb->get_results( $sql );
        
        // Fetch last message properly for each
        foreach ($conversations as $conv) {
             $last = $this->wpdb->get_row( $this->wpdb->prepare(
                 "SELECT * FROM {$this->table_messages} WHERE conversation_id = %d ORDER BY id DESC LIMIT 1",
                 $conv->id
             ));
             $conv->last_message_obj = $last;
        }

        return $conversations;
    }

    public function get_messages( $conversation_id, $user_id ) {
        // Verify participation
        $is_participant = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT count(*) FROM {$this->table_participants} WHERE conversation_id = %d AND user_id = %d",
            $conversation_id, $user_id
        ) );

        if ( ! $is_participant ) {
            return new \WP_Error( 'forbidden', 'Access denied', array( 'status' => 403 ) );
        }

        // Mark as read for this user? Maybe in a separate call or here.
        // Let's do it here implicitly for simplicity or keep valid separation?
        // Let's keep it separate in mark_read();

        return $this->wpdb->get_results( $this->wpdb->prepare(
            "SELECT * FROM {$this->table_messages} WHERE conversation_id = %d ORDER BY sent_at ASC",
            $conversation_id
        ) );
    }

    public function mark_read( $conversation_id, $user_id ) {
         $this->wpdb->update(
            $this->table_participants,
            array( 'last_read_at' => current_time( 'mysql' ) ),
            array( 'conversation_id' => $conversation_id, 'user_id' => $user_id )
        );
    }
}
