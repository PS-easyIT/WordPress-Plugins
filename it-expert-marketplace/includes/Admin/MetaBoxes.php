<?php
namespace PhinIT\Marketplace\Admin;

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
            'marketplace_data',
            __( 'Angebots-Daten', 'it-expert-marketplace' ),
            array( $this, 'render_box' ),
            'marketplace_listing',
            'normal',
            'high'
        );
    }

    public function render_box( $post ) {
        wp_nonce_field( 'save_marketplace_data', 'marketplace_nonce' );
        $price = get_post_meta( $post->ID, '_listing_price', true );
        $type = get_post_meta( $post->ID, '_listing_type', true );
        $condition = get_post_meta( $post->ID, '_listing_condition', true );
        
        ?>
        <div class="it-expert-meta-row">
            <p>
                <label for="listing_price"><?php _e('Preis', 'it-expert-marketplace'); ?></label><br>
                <input type="number" step="0.01" id="listing_price" name="listing_price" value="<?php echo esc_attr( $price ); ?>" class="widefat" style="max-width: 200px;">
            </p>
            <p>
                <label for="listing_type"><?php _e('Typ', 'it-expert-marketplace'); ?></label><br>
                <select id="listing_type" name="listing_type" class="widefat" style="max-width: 200px;">
                    <option value="license" <?php selected( $type, 'license' ); ?>><?php _e('Software Lizenz', 'it-expert-marketplace'); ?></option>
                    <option value="hardware" <?php selected( $type, 'hardware' ); ?>><?php _e('Hardware', 'it-expert-marketplace'); ?></option>
                    <option value="service" <?php selected( $type, 'service' ); ?>><?php _e('Dienstleistung', 'it-expert-marketplace'); ?></option>
                </select>
            </p>
            <p>
                <label for="listing_condition"><?php _e('Zustand', 'it-expert-marketplace'); ?></label><br>
                <select id="listing_condition" name="listing_condition" class="widefat" style="max-width: 200px;">
                    <option value="new" <?php selected( $condition, 'new' ); ?>><?php _e('Neu', 'it-expert-marketplace'); ?></option>
                    <option value="used" <?php selected( $condition, 'used' ); ?>><?php _e('Gebraucht', 'it-expert-marketplace'); ?></option>
                    <option value="refurbished" <?php selected( $condition, 'refurbished' ); ?>><?php _e('Generalüberholt', 'it-expert-marketplace'); ?></option>
                </select>
            </p>
        </div>
        <?php
    }

    public function save_data( $post_id ) {
        if ( ! isset( $_POST['marketplace_nonce'] ) || ! wp_verify_nonce( $_POST['marketplace_nonce'], 'save_marketplace_data' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = array( 'listing_price', 'listing_type', 'listing_condition' );
        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
    }
}
