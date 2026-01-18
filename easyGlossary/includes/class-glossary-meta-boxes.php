<?php
/**
 * Glossary Meta Boxes
 * 
 * Handles custom meta boxes for glossary entries
 * 
 * @package easyGlossary
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Easy_Glossary_Meta_Boxes {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_easy_glossary', array( $this, 'save_meta_boxes' ), 10, 2 );
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Synonyme Meta Box
        add_meta_box(
            'easy_glossary_synonyms',
            __( 'Synonyme & Alternative Begriffe', 'easy-glossary' ),
            array( $this, 'render_synonyms_meta_box' ),
            'easy_glossary',
            'normal',
            'default'
        );
        
        // Verwandte Begriffe Meta Box
        add_meta_box(
            'easy_glossary_related',
            __( 'Verwandte Begriffe', 'easy-glossary' ),
            array( $this, 'render_related_meta_box' ),
            'easy_glossary',
            'side',
            'default'
        );
        
        // Medienverwaltung Meta Box
        add_meta_box(
            'easy_glossary_media',
            __( 'Medien & Anhänge', 'easy-glossary' ),
            array( $this, 'render_media_meta_box' ),
            'easy_glossary',
            'normal',
            'default'
        );
        
        // SEO Meta Box
        add_meta_box(
            'easy_glossary_seo',
            __( 'SEO Einstellungen', 'easy-glossary' ),
            array( $this, 'render_seo_meta_box' ),
            'easy_glossary',
            'normal',
            'low'
        );
        
        // Zusätzliche Informationen
        add_meta_box(
            'easy_glossary_info',
            __( 'Zusätzliche Informationen', 'easy-glossary' ),
            array( $this, 'render_info_meta_box' ),
            'easy_glossary',
            'side',
            'low'
        );
    }
    
    /**
     * Render Synonyme Meta Box
     */
    public function render_synonyms_meta_box( $post ) {
        wp_nonce_field( 'easy_glossary_synonyms_nonce', 'easy_glossary_synonyms_nonce' );
        
        $synonyms = get_post_meta( $post->ID, '_glossary_synonyms', true );
        ?>
        <div class="easy-glossary-meta-box">
            <p class="description">
                <?php _e( 'Geben Sie alternative Bezeichnungen für diesen Begriff ein (eine pro Zeile).', 'easy-glossary' ); ?>
            </p>
            <textarea 
                name="glossary_synonyms" 
                id="glossary_synonyms" 
                rows="5" 
                class="large-text"
                placeholder="<?php esc_attr_e( 'z.B. API, Programmierschnittstelle, Application Interface', 'easy-glossary' ); ?>"
            ><?php echo esc_textarea( $synonyms ); ?></textarea>
            
            <p class="description">
                <?php _e( 'Diese Begriffe werden ebenfalls im Content erkannt und verlinkt.', 'easy-glossary' ); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render Related Terms Meta Box
     */
    public function render_related_meta_box( $post ) {
        wp_nonce_field( 'easy_glossary_related_nonce', 'easy_glossary_related_nonce' );
        
        $related_terms = get_post_meta( $post->ID, '_glossary_related_terms', true );
        if ( ! is_array( $related_terms ) ) {
            $related_terms = array();
        }
        
        // Alle Glossar-Einträge außer dem aktuellen
        $all_terms = get_posts( array(
            'post_type' => 'easy_glossary',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post__not_in' => array( $post->ID )
        ) );
        ?>
        <div class="easy-glossary-meta-box">
            <p class="description">
                <?php _e( 'Wählen Sie verwandte Glossar-Begriffe aus:', 'easy-glossary' ); ?>
            </p>
            
            <div class="related-terms-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                <?php if ( ! empty( $all_terms ) ) : ?>
                    <?php foreach ( $all_terms as $term ) : ?>
                        <label style="display: block; margin-bottom: 5px;">
                            <input 
                                type="checkbox" 
                                name="glossary_related_terms[]" 
                                value="<?php echo esc_attr( $term->ID ); ?>"
                                <?php checked( in_array( $term->ID, $related_terms ) ); ?>
                            >
                            <?php echo esc_html( get_the_title( $term->ID ) ); ?>
                        </label>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p><?php _e( 'Noch keine weiteren Glossar-Einträge vorhanden.', 'easy-glossary' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render SEO Meta Box
     */
    public function render_seo_meta_box( $post ) {
        wp_nonce_field( 'easy_glossary_seo_nonce', 'easy_glossary_seo_nonce' );
        
        $focus_keyword = get_post_meta( $post->ID, '_glossary_focus_keyword', true );
        $meta_description = get_post_meta( $post->ID, '_glossary_meta_description', true );
        ?>
        <div class="easy-glossary-meta-box">
            <p>
                <label for="glossary_focus_keyword">
                    <strong><?php _e( 'Focus Keyword:', 'easy-glossary' ); ?></strong>
                </label>
                <input 
                    type="text" 
                    name="glossary_focus_keyword" 
                    id="glossary_focus_keyword" 
                    value="<?php echo esc_attr( $focus_keyword ); ?>" 
                    class="regular-text"
                    placeholder="<?php esc_attr_e( 'Haupt-Keyword für SEO', 'easy-glossary' ); ?>"
                >
            </p>
            
            <p>
                <label for="glossary_meta_description">
                    <strong><?php _e( 'Meta Description:', 'easy-glossary' ); ?></strong>
                </label>
                <textarea 
                    name="glossary_meta_description" 
                    id="glossary_meta_description" 
                    rows="3" 
                    class="large-text"
                    placeholder="<?php esc_attr_e( 'Kurze Beschreibung für Suchmaschinen (max. 160 Zeichen)', 'easy-glossary' ); ?>"
                    maxlength="160"
                ><?php echo esc_textarea( $meta_description ); ?></textarea>
                <span class="description">
                    <span id="meta-desc-count">0</span> / 160 <?php _e( 'Zeichen', 'easy-glossary' ); ?>
                </span>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var textarea = $('#glossary_meta_description');
            var counter = $('#meta-desc-count');
            
            function updateCount() {
                counter.text(textarea.val().length);
            }
            
            textarea.on('input', updateCount);
            updateCount();
        });
        </script>
        <?php
    }
    
    /**
     * Render Media Meta Box
     */
    public function render_media_meta_box( $post ) {
        wp_nonce_field( 'easy_glossary_media_nonce', 'easy_glossary_media_nonce' );
        
        $gallery_images = get_post_meta( $post->ID, '_glossary_gallery_images', true );
        $featured_video = get_post_meta( $post->ID, '_glossary_featured_video', true );
        $attachments = get_post_meta( $post->ID, '_glossary_attachments', true );
        
        if ( ! is_array( $gallery_images ) ) {
            $gallery_images = array();
        }
        if ( ! is_array( $attachments ) ) {
            $attachments = array();
        }
        ?>
        <div class="easy-glossary-media-box">
            <!-- Bildergalerie -->
            <div class="media-section">
                <h4><?php _e( 'Bildergalerie', 'easy-glossary' ); ?></h4>
                <p class="description"><?php _e( 'Fügen Sie Bilder hinzu, die den Begriff visuell erklären.', 'easy-glossary' ); ?></p>
                
                <div id="glossary-gallery-container" class="gallery-container">
                    <?php foreach ( $gallery_images as $image_id ) : ?>
                        <?php $image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' ); ?>
                        <?php if ( $image_url ) : ?>
                            <div class="gallery-item" data-id="<?php echo esc_attr( $image_id ); ?>">
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="">
                                <button type="button" class="remove-gallery-image" title="<?php _e( 'Entfernen', 'easy-glossary' ); ?>">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </button>
                                <input type="hidden" name="glossary_gallery_images[]" value="<?php echo esc_attr( $image_id ); ?>">
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" class="button button-secondary" id="add-gallery-images">
                    <span class="dashicons dashicons-images-alt2"></span>
                    <?php _e( 'Bilder hinzufügen', 'easy-glossary' ); ?>
                </button>
            </div>
            
            <!-- Featured Video -->
            <div class="media-section">
                <h4><?php _e( 'Video', 'easy-glossary' ); ?></h4>
                <p class="description"><?php _e( 'YouTube, Vimeo oder direkter Video-Link.', 'easy-glossary' ); ?></p>
                
                <input 
                    type="url" 
                    name="glossary_featured_video" 
                    id="glossary_featured_video" 
                    value="<?php echo esc_url( $featured_video ); ?>" 
                    class="regular-text"
                    placeholder="https://www.youtube.com/watch?v=..."
                >
                
                <?php if ( ! empty( $featured_video ) ) : ?>
                    <div class="video-preview" style="margin-top: 10px;">
                        <?php echo wp_oembed_get( $featured_video, array( 'width' => 400 ) ); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Anhänge/Downloads -->
            <div class="media-section">
                <h4><?php _e( 'Anhänge & Downloads', 'easy-glossary' ); ?></h4>
                <p class="description"><?php _e( 'PDFs, Dokumente oder andere Dateien zum Download.', 'easy-glossary' ); ?></p>
                
                <div id="glossary-attachments-container" class="attachments-container">
                    <?php foreach ( $attachments as $attachment_id ) : ?>
                        <?php 
                        $file_url = wp_get_attachment_url( $attachment_id );
                        $file_name = basename( get_attached_file( $attachment_id ) );
                        $file_type = wp_check_filetype( $file_name );
                        ?>
                        <?php if ( $file_url ) : ?>
                            <div class="attachment-item" data-id="<?php echo esc_attr( $attachment_id ); ?>">
                                <span class="dashicons dashicons-media-document"></span>
                                <span class="attachment-name"><?php echo esc_html( $file_name ); ?></span>
                                <span class="attachment-type"><?php echo esc_html( strtoupper( $file_type['ext'] ) ); ?></span>
                                <button type="button" class="remove-attachment" title="<?php _e( 'Entfernen', 'easy-glossary' ); ?>">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </button>
                                <input type="hidden" name="glossary_attachments[]" value="<?php echo esc_attr( $attachment_id ); ?>">
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" class="button button-secondary" id="add-attachments">
                    <span class="dashicons dashicons-paperclip"></span>
                    <?php _e( 'Dateien hinzufügen', 'easy-glossary' ); ?>
                </button>
            </div>
        </div>
        
        <style>
        .easy-glossary-media-box {
            padding: 10px 0;
        }
        
        .media-section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .media-section:last-child {
            border-bottom: none;
        }
        
        .media-section h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        
        .gallery-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin: 15px 0;
            min-height: 50px;
        }
        
        .gallery-item {
            position: relative;
            aspect-ratio: 1;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            background: #f9f9f9;
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .gallery-item .remove-gallery-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0,0,0,0.7);
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 2px;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .gallery-item:hover .remove-gallery-image {
            opacity: 1;
        }
        
        .gallery-item .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        .attachments-container {
            margin: 15px 0;
        }
        
        .attachment-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        
        .attachment-item .dashicons {
            color: #666;
        }
        
        .attachment-name {
            flex: 1;
            font-size: 13px;
        }
        
        .attachment-type {
            font-size: 11px;
            color: #666;
            background: #e5e5e5;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .attachment-item .remove-attachment {
            background: transparent;
            border: none;
            color: #b32d2e;
            cursor: pointer;
            padding: 0;
        }
        
        .video-preview {
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Media Uploader für Galerie
            var galleryFrame;
            $('#add-gallery-images').on('click', function(e) {
                e.preventDefault();
                
                if (galleryFrame) {
                    galleryFrame.open();
                    return;
                }
                
                galleryFrame = wp.media({
                    title: '<?php _e( 'Bilder zur Galerie hinzufügen', 'easy-glossary' ); ?>',
                    button: {
                        text: '<?php _e( 'Hinzufügen', 'easy-glossary' ); ?>'
                    },
                    multiple: true,
                    library: {
                        type: 'image'
                    }
                });
                
                galleryFrame.on('select', function() {
                    var selection = galleryFrame.state().get('selection');
                    selection.map(function(attachment) {
                        attachment = attachment.toJSON();
                        var html = '<div class="gallery-item" data-id="' + attachment.id + '">' +
                            '<img src="' + attachment.sizes.thumbnail.url + '" alt="">' +
                            '<button type="button" class="remove-gallery-image" title="<?php _e( 'Entfernen', 'easy-glossary' ); ?>">' +
                            '<span class="dashicons dashicons-no-alt"></span>' +
                            '</button>' +
                            '<input type="hidden" name="glossary_gallery_images[]" value="' + attachment.id + '">' +
                            '</div>';
                        $('#glossary-gallery-container').append(html);
                    });
                });
                
                galleryFrame.open();
            });
            
            // Bild aus Galerie entfernen
            $(document).on('click', '.remove-gallery-image', function(e) {
                e.preventDefault();
                $(this).closest('.gallery-item').remove();
            });
            
            // Media Uploader für Anhänge
            var attachmentFrame;
            $('#add-attachments').on('click', function(e) {
                e.preventDefault();
                
                if (attachmentFrame) {
                    attachmentFrame.open();
                    return;
                }
                
                attachmentFrame = wp.media({
                    title: '<?php _e( 'Anhänge hinzufügen', 'easy-glossary' ); ?>',
                    button: {
                        text: '<?php _e( 'Hinzufügen', 'easy-glossary' ); ?>'
                    },
                    multiple: true
                });
                
                attachmentFrame.on('select', function() {
                    var selection = attachmentFrame.state().get('selection');
                    selection.map(function(attachment) {
                        attachment = attachment.toJSON();
                        var fileType = attachment.filename.split('.').pop().toUpperCase();
                        var html = '<div class="attachment-item" data-id="' + attachment.id + '">' +
                            '<span class="dashicons dashicons-media-document"></span>' +
                            '<span class="attachment-name">' + attachment.filename + '</span>' +
                            '<span class="attachment-type">' + fileType + '</span>' +
                            '<button type="button" class="remove-attachment" title="<?php _e( 'Entfernen', 'easy-glossary' ); ?>">' +
                            '<span class="dashicons dashicons-no-alt"></span>' +
                            '</button>' +
                            '<input type="hidden" name="glossary_attachments[]" value="' + attachment.id + '">' +
                            '</div>';
                        $('#glossary-attachments-container').append(html);
                    });
                });
                
                attachmentFrame.open();
            });
            
            // Anhang entfernen
            $(document).on('click', '.remove-attachment', function(e) {
                e.preventDefault();
                $(this).closest('.attachment-item').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render Info Meta Box
     */
    public function render_info_meta_box( $post ) {
        wp_nonce_field( 'easy_glossary_info_nonce', 'easy_glossary_info_nonce' );
        
        $external_link = get_post_meta( $post->ID, '_glossary_external_link', true );
        $difficulty_level = get_post_meta( $post->ID, '_glossary_difficulty_level', true );
        ?>
        <div class="easy-glossary-meta-box">
            <p>
                <label for="glossary_external_link">
                    <strong><?php _e( 'Externer Link:', 'easy-glossary' ); ?></strong>
                </label>
                <input 
                    type="url" 
                    name="glossary_external_link" 
                    id="glossary_external_link" 
                    value="<?php echo esc_url( $external_link ); ?>" 
                    class="regular-text"
                    placeholder="https://"
                >
                <span class="description">
                    <?php _e( 'Link zu weiterführenden Informationen', 'easy-glossary' ); ?>
                </span>
            </p>
            
            <p>
                <label for="glossary_difficulty_level">
                    <strong><?php _e( 'Schwierigkeitsgrad:', 'easy-glossary' ); ?></strong>
                </label>
                <select name="glossary_difficulty_level" id="glossary_difficulty_level" class="regular-text">
                    <option value=""><?php _e( 'Nicht festgelegt', 'easy-glossary' ); ?></option>
                    <option value="beginner" <?php selected( $difficulty_level, 'beginner' ); ?>><?php _e( 'Anfänger', 'easy-glossary' ); ?></option>
                    <option value="intermediate" <?php selected( $difficulty_level, 'intermediate' ); ?>><?php _e( 'Fortgeschritten', 'easy-glossary' ); ?></option>
                    <option value="advanced" <?php selected( $difficulty_level, 'advanced' ); ?>><?php _e( 'Experte', 'easy-glossary' ); ?></option>
                </select>
            </p>
        </div>
        
        <style>
        .easy-glossary-meta-box {
            padding: 10px 0;
        }
        .easy-glossary-meta-box p {
            margin-bottom: 15px;
        }
        .easy-glossary-meta-box label {
            display: block;
            margin-bottom: 5px;
        }
        .easy-glossary-meta-box .description {
            display: block;
            margin-top: 5px;
            font-style: italic;
            color: #666;
        }
        </style>
        <?php
    }
    
    /**
     * Save meta boxes
     */
    public function save_meta_boxes( $post_id, $post ) {
        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Save Synonyms
        if ( isset( $_POST['easy_glossary_synonyms_nonce'] ) && 
             wp_verify_nonce( $_POST['easy_glossary_synonyms_nonce'], 'easy_glossary_synonyms_nonce' ) ) {
            
            if ( isset( $_POST['glossary_synonyms'] ) ) {
                $synonyms = sanitize_textarea_field( $_POST['glossary_synonyms'] );
                update_post_meta( $post_id, '_glossary_synonyms', $synonyms );
            }
        }
        
        // Save Related Terms
        if ( isset( $_POST['easy_glossary_related_nonce'] ) && 
             wp_verify_nonce( $_POST['easy_glossary_related_nonce'], 'easy_glossary_related_nonce' ) ) {
            
            $related_terms = isset( $_POST['glossary_related_terms'] ) ? 
                array_map( 'intval', $_POST['glossary_related_terms'] ) : array();
            update_post_meta( $post_id, '_glossary_related_terms', $related_terms );
        }
        
        // Save SEO Data
        if ( isset( $_POST['easy_glossary_seo_nonce'] ) && 
             wp_verify_nonce( $_POST['easy_glossary_seo_nonce'], 'easy_glossary_seo_nonce' ) ) {
            
            if ( isset( $_POST['glossary_focus_keyword'] ) ) {
                $focus_keyword = sanitize_text_field( $_POST['glossary_focus_keyword'] );
                update_post_meta( $post_id, '_glossary_focus_keyword', $focus_keyword );
            }
            
            if ( isset( $_POST['glossary_meta_description'] ) ) {
                $meta_description = sanitize_textarea_field( $_POST['glossary_meta_description'] );
                update_post_meta( $post_id, '_glossary_meta_description', $meta_description );
            }
        }
        
        // Save Media Data
        if ( isset( $_POST['easy_glossary_media_nonce'] ) && 
             wp_verify_nonce( $_POST['easy_glossary_media_nonce'], 'easy_glossary_media_nonce' ) ) {
            
            // Gallery Images
            $gallery_images = isset( $_POST['glossary_gallery_images'] ) ? 
                array_map( 'intval', $_POST['glossary_gallery_images'] ) : array();
            update_post_meta( $post_id, '_glossary_gallery_images', $gallery_images );
            
            // Featured Video
            if ( isset( $_POST['glossary_featured_video'] ) ) {
                $featured_video = esc_url_raw( $_POST['glossary_featured_video'] );
                update_post_meta( $post_id, '_glossary_featured_video', $featured_video );
            }
            
            // Attachments
            $attachments = isset( $_POST['glossary_attachments'] ) ? 
                array_map( 'intval', $_POST['glossary_attachments'] ) : array();
            update_post_meta( $post_id, '_glossary_attachments', $attachments );
        }
        
        // Save Additional Info
        if ( isset( $_POST['easy_glossary_info_nonce'] ) && 
             wp_verify_nonce( $_POST['easy_glossary_info_nonce'], 'easy_glossary_info_nonce' ) ) {
            
            if ( isset( $_POST['glossary_external_link'] ) ) {
                $external_link = esc_url_raw( $_POST['glossary_external_link'] );
                update_post_meta( $post_id, '_glossary_external_link', $external_link );
            }
            
            if ( isset( $_POST['glossary_difficulty_level'] ) ) {
                $difficulty_level = sanitize_text_field( $_POST['glossary_difficulty_level'] );
                update_post_meta( $post_id, '_glossary_difficulty_level', $difficulty_level );
            }
        }
    }
}

// Initialize
new Easy_Glossary_Meta_Boxes();
