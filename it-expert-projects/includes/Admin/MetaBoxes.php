<?php
namespace PhinIT\Projects\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MetaBoxes {
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_boxes' ) );
        add_action( 'save_post', array( $this, 'save_data' ) );
    }

    public function add_boxes() {
        add_meta_box(
            'project_details',
            'Projekt Details',
            array( $this, 'render_box' ),
            'expert_project',
            'normal',
            'high'
        );
    }

    public function render_box( $post ) {
        wp_nonce_field( 'save_project_data', 'project_nonce' );
        $client = get_post_meta( $post->ID, '_project_client', true );
        $start_date = get_post_meta( $post->ID, '_project_start_date', true );
        $status = get_post_meta( $post->ID, '_project_status', true );
        ?>
        <div class="it-expert-project-meta">
            <p>
                <label>Kunde<br>
                <input type="text" name="project_client" value="<?php echo esc_attr( $client ); ?>" class="widefat">
                </label>
            </p>
            <p>
                <label>Start Datum<br>
                <input type="date" name="project_start_date" value="<?php echo esc_attr( $start_date ); ?>" class="widefat">
                </label>
            </p>
            <p>
                <label>Status<br>
                <select name="project_status" class="widefat">
                    <option value="planned" <?php selected( $status, 'planned' ); ?>>Geplant</option>
                    <option value="ongoing" <?php selected( $status, 'ongoing' ); ?>>Laufend</option>
                    <option value="completed" <?php selected( $status, 'completed' ); ?>>Abgeschlossen</option>
                </select>
                </label>
            </p>
        </div>
        <?php
    }

    public function save_data( $post_id ) {
        if ( ! isset( $_POST['project_nonce'] ) || ! wp_verify_nonce( $_POST['project_nonce'], 'save_project_data' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $fields = array( 'project_client', 'project_start_date', 'project_status' );
        foreach ( $fields as $field ) {
            if ( isset( $_POST[$field] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[$field] ) );
            }
        }
    }
}
