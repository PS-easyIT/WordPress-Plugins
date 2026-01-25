<?php
namespace PhinIT\Marketplace\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Form {

    public function __construct() {
        add_shortcode( 'it_expert_marketplace_submit', array( $this, 'render_form' ) );
        add_action( 'init', array( $this, 'handle_submission' ) );
    }

    public function render_form() {
        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'Bitte anmelden, um ein Angebot zu erstellen.', 'it-expert-marketplace' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="it-expert-marketplace-form">
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'it_expert_submit_listing', 'listing_nonce' ); ?>
                <p>
                    <label>Titel *<br>
                    <input type="text" name="listing_title" required class="widefat" style="width:100%; padding:8px;">
                    </label>
                </p>
                <p>
                    <label>Beschreibung *<br>
                    <textarea name="listing_content" rows="5" required class="widefat" style="width:100%; padding:8px;"></textarea>
                    </label>
                </p>
                <div style="display:flex; gap:20px;">
                    <p style="flex:1;">
                        <label>Preis (€) *<br>
                        <input type="number" step="0.01" name="listing_price" required style="width:100%; padding:8px;">
                        </label>
                    </p>
                    <p style="flex:1;">
                        <label>Typ *<br>
                        <select name="listing_type" style="width:100%; padding:8px;">
                            <option value="license">Software Lizenz</option>
                            <option value="hardware">Hardware</option>
                            <option value="service" selected>Dienstleistung</option>
                        </select>
                        </label>
                    </p>
                </div>
                <p>
                    <input type="submit" name="submit_listing" value="Angebot einreichen" class="button button-primary" style="padding:10px 20px;">
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_submission() {
        if ( isset( $_POST['submit_listing'] ) && isset( $_POST['listing_nonce'] ) ) {
            if ( ! wp_verify_nonce( $_POST['listing_nonce'], 'it_expert_submit_listing' ) ) {
                return;
            }

            if ( ! is_user_logged_in() ) return;

            $title = sanitize_text_field( $_POST['listing_title'] );
            $content = sanitize_textarea_field( $_POST['listing_content'] );
            $price = sanitize_text_field( $_POST['listing_price'] );
            $type = sanitize_text_field( $_POST['listing_type'] );

            $post_id = wp_insert_post( array(
                'post_title' => $title,
                'post_content' => $content,
                'post_type' => 'marketplace_listing',
                'post_status' => 'pending', // Moderation required
                'post_author' => get_current_user_id()
            ) );

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_listing_price', $price );
                update_post_meta( $post_id, '_listing_type', $type );
                update_post_meta( $post_id, '_listing_status', 'active' );
                
                // Redirect to avoid resubmission (Simplistic approach)
                wp_safe_redirect( add_query_arg( 'listing_submitted', 'true', remove_query_arg( 'submit_listing' ) ) );
                exit;
            }
        }
    }
}
