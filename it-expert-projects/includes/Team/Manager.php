<?php
namespace PhinIT\Projects\Team;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Manager {
    public function get_members( $project_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'it_expert_project_members';
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE project_id = %d", $project_id ) );
    }

    public function add_member( $project_id, $expert_id, $role ) {
        global $wpdb;
        $table = $wpdb->prefix . 'it_expert_project_members';
        return $wpdb->replace( $table, array( 
            'project_id' => $project_id,
            'expert_id' => $expert_id,
            'role' => $role
        ), array( '%d', '%d', '%s' ) );
    }
    
    public function remove_member( $project_id, $expert_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'it_expert_project_members';
        return $wpdb->delete( $table, array( 'project_id' => $project_id, 'expert_id' => $expert_id ), array( '%d', '%d' ) );
    }
}
