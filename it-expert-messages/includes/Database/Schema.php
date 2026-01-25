<?php
namespace PhinIT\Messages\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Schema {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_conversations = $wpdb->prefix . 'it_expert_conversations';
        $table_messages      = $wpdb->prefix . 'it_expert_messages';
        $table_participants  = $wpdb->prefix . 'it_expert_message_participants';

        $sql_conversations = "CREATE TABLE $table_conversations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            participant_ids longtext NOT NULL, -- JSON
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_messages = "CREATE TABLE $table_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) NOT NULL,
            sender_id bigint(20) NOT NULL,
            content longtext NOT NULL,
            sent_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            read_at datetime DEFAULT NULL,
            attachments longtext DEFAULT NULL, -- JSON
            PRIMARY KEY  (id),
            KEY conversation_id (conversation_id),
            KEY sender_id (sender_id)
        ) $charset_collate;";

        $sql_participants = "CREATE TABLE $table_participants (
            conversation_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            last_read_at datetime DEFAULT NULL,
            is_muted tinyint(1) DEFAULT 0 NOT NULL,
            PRIMARY KEY  (conversation_id, user_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_conversations );
        dbDelta( $sql_messages );
        dbDelta( $sql_participants );
    }
}
