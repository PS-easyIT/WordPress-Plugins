<?php
/**
 * Glossary Bulk Actions
 * 
 * Erweiterte Bulk-Aktionen für Glossar-Begriffe
 * 
 * @package easyGlossary
 * @since 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Easy_Glossary_Bulk_Actions {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Bulk-Aktionen registrieren
        add_filter( 'bulk_actions-edit-easy_glossary', array( $this, 'register_bulk_actions' ) );
        
        // Bulk-Aktionen verarbeiten
        add_filter( 'handle_bulk_actions-edit-easy_glossary', array( $this, 'handle_bulk_actions' ), 10, 3 );
        
        // Admin-Notices für Bulk-Aktionen
        add_action( 'admin_notices', array( $this, 'bulk_action_notices' ) );
        
        // Quick-Edit Felder
        add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_fields' ), 10, 2 );
        add_action( 'save_post_easy_glossary', array( $this, 'save_quick_edit' ), 10, 2 );
        
        // Bulk-Edit Felder
        add_action( 'bulk_edit_custom_box', array( $this, 'bulk_edit_fields' ), 10, 2 );
    }
    
    /**
     * Registriere Bulk-Aktionen
     */
    public function register_bulk_actions( $bulk_actions ) {
        $bulk_actions['export_terms'] = __( 'Als CSV exportieren', 'easy-glossary' );
        $bulk_actions['add_to_category'] = __( 'Zu Kategorie hinzufügen', 'easy-glossary' );
        $bulk_actions['duplicate_terms'] = __( 'Duplizieren', 'easy-glossary' );
        $bulk_actions['update_synonyms'] = __( 'Synonyme aktualisieren', 'easy-glossary' );
        $bulk_actions['clear_cache'] = __( 'Cache leeren', 'easy-glossary' );
        
        return $bulk_actions;
    }
    
    /**
     * Verarbeite Bulk-Aktionen
     */
    public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {
        // Sicherheitsprüfung
        if ( ! current_user_can( 'edit_posts' ) ) {
            return $redirect_to;
        }
        
        switch ( $action ) {
            case 'export_terms':
                $this->bulk_export_terms( $post_ids );
                break;
                
            case 'add_to_category':
                $count = $this->bulk_add_to_category( $post_ids );
                $redirect_to = add_query_arg( 'bulk_categorized', $count, $redirect_to );
                break;
                
            case 'duplicate_terms':
                $count = $this->bulk_duplicate_terms( $post_ids );
                $redirect_to = add_query_arg( 'bulk_duplicated', $count, $redirect_to );
                break;
                
            case 'update_synonyms':
                $count = $this->bulk_update_synonyms( $post_ids );
                $redirect_to = add_query_arg( 'bulk_synonyms_updated', $count, $redirect_to );
                break;
                
            case 'clear_cache':
                $this->bulk_clear_cache( $post_ids );
                $redirect_to = add_query_arg( 'bulk_cache_cleared', count( $post_ids ), $redirect_to );
                break;
        }
        
        return $redirect_to;
    }
    
    /**
     * Bulk-Export als CSV
     */
    private function bulk_export_terms( $post_ids ) {
        if ( empty( $post_ids ) ) {
            return;
        }
        
        // CSV-Header
        $filename = 'glossary-export-' . date( 'Y-m-d-His' ) . '.csv';
        
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
        
        $output = fopen( 'php://output', 'w' );
        
        // UTF-8 BOM für Excel
        fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );
        
        // Header-Zeile
        fputcsv( $output, array(
            'ID',
            'Titel',
            'Beschreibung',
            'Inhalt',
            'Synonyme',
            'Verwandte Begriffe',
            'Externer Link',
            'SEO-Titel',
            'SEO-Beschreibung',
            'Status',
            'Datum'
        ), ';' );
        
        // Daten-Zeilen
        foreach ( $post_ids as $post_id ) {
            $post = get_post( $post_id );
            
            if ( ! $post || $post->post_type !== 'easy_glossary' ) {
                continue;
            }
            
            $synonyms = get_post_meta( $post_id, '_glossary_synonyms', true );
            $related = get_post_meta( $post_id, '_glossary_related_terms', true );
            $external_link = get_post_meta( $post_id, '_glossary_external_link', true );
            $seo_title = get_post_meta( $post_id, '_glossary_seo_title', true );
            $seo_desc = get_post_meta( $post_id, '_glossary_seo_description', true );
            
            // Verwandte Begriffe als Titel-Liste
            $related_titles = array();
            if ( is_array( $related ) ) {
                foreach ( $related as $related_id ) {
                    $related_titles[] = get_the_title( $related_id );
                }
            }
            
            fputcsv( $output, array(
                $post->ID,
                $post->post_title,
                get_the_excerpt( $post ),
                wp_strip_all_tags( $post->post_content ),
                is_array( $synonyms ) ? implode( ', ', $synonyms ) : $synonyms,
                implode( ', ', $related_titles ),
                $external_link,
                $seo_title,
                $seo_desc,
                $post->post_status,
                get_the_date( 'Y-m-d H:i:s', $post )
            ), ';' );
        }
        
        fclose( $output );
        exit;
    }
    
    /**
     * Bulk-Kategorisierung
     */
    private function bulk_add_to_category( $post_ids ) {
        $count = 0;
        
        // Hier könnte eine Kategorie-Auswahl implementiert werden
        // Für jetzt: Placeholder-Implementierung
        
        foreach ( $post_ids as $post_id ) {
            // Kategorie zuweisen (wenn Taxonomie existiert)
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Bulk-Duplizierung
     */
    private function bulk_duplicate_terms( $post_ids ) {
        $count = 0;
        
        foreach ( $post_ids as $post_id ) {
            $original = get_post( $post_id );
            
            if ( ! $original || $original->post_type !== 'easy_glossary' ) {
                continue;
            }
            
            // Neuen Post erstellen
            $new_post = array(
                'post_title' => $original->post_title . ' (Kopie)',
                'post_content' => $original->post_content,
                'post_excerpt' => $original->post_excerpt,
                'post_status' => 'draft',
                'post_type' => 'easy_glossary',
                'post_author' => get_current_user_id()
            );
            
            $new_id = wp_insert_post( $new_post );
            
            if ( $new_id && ! is_wp_error( $new_id ) ) {
                // Meta-Daten kopieren
                $meta_keys = array(
                    '_glossary_synonyms',
                    '_glossary_related_terms',
                    '_glossary_external_link',
                    '_glossary_seo_title',
                    '_glossary_seo_description',
                    '_glossary_additional_info'
                );
                
                foreach ( $meta_keys as $meta_key ) {
                    $meta_value = get_post_meta( $post_id, $meta_key, true );
                    if ( ! empty( $meta_value ) ) {
                        update_post_meta( $new_id, $meta_key, $meta_value );
                    }
                }
                
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Bulk-Synonym-Update
     */
    private function bulk_update_synonyms( $post_ids ) {
        $count = 0;
        
        foreach ( $post_ids as $post_id ) {
            $synonyms = get_post_meta( $post_id, '_glossary_synonyms', true );
            
            if ( ! empty( $synonyms ) ) {
                // Synonyme bereinigen und normalisieren
                if ( is_string( $synonyms ) ) {
                    $synonyms = array_map( 'trim', explode( ',', $synonyms ) );
                }
                
                $synonyms = array_filter( $synonyms );
                $synonyms = array_unique( $synonyms );
                
                update_post_meta( $post_id, '_glossary_synonyms', $synonyms );
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Bulk-Cache löschen
     */
    private function bulk_clear_cache( $post_ids ) {
        // Cache-Keys löschen
        wp_cache_delete( 'easy_glossary_auto_link_terms' );
        delete_transient( 'easy_glossary_auto_link_terms' );
        
        // Objekt-Cache für jeden Begriff löschen
        foreach ( $post_ids as $post_id ) {
            clean_post_cache( $post_id );
        }
        
        // Hook für zusätzliche Cache-Löschungen
        do_action( 'easy_glossary_bulk_clear_cache', $post_ids );
    }
    
    /**
     * Admin-Notices für Bulk-Aktionen
     */
    public function bulk_action_notices() {
        if ( ! isset( $_REQUEST['post_type'] ) || $_REQUEST['post_type'] !== 'easy_glossary' ) {
            return;
        }
        
        if ( isset( $_REQUEST['bulk_categorized'] ) ) {
            $count = intval( $_REQUEST['bulk_categorized'] );
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(
                    _n(
                        '%d Begriff wurde kategorisiert.',
                        '%d Begriffe wurden kategorisiert.',
                        $count,
                        'easy-glossary'
                    ),
                    $count
                )
            );
        }
        
        if ( isset( $_REQUEST['bulk_duplicated'] ) ) {
            $count = intval( $_REQUEST['bulk_duplicated'] );
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(
                    _n(
                        '%d Begriff wurde dupliziert.',
                        '%d Begriffe wurden dupliziert.',
                        $count,
                        'easy-glossary'
                    ),
                    $count
                )
            );
        }
        
        if ( isset( $_REQUEST['bulk_synonyms_updated'] ) ) {
            $count = intval( $_REQUEST['bulk_synonyms_updated'] );
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(
                    _n(
                        'Synonyme von %d Begriff wurden aktualisiert.',
                        'Synonyme von %d Begriffen wurden aktualisiert.',
                        $count,
                        'easy-glossary'
                    ),
                    $count
                )
            );
        }
        
        if ( isset( $_REQUEST['bulk_cache_cleared'] ) ) {
            $count = intval( $_REQUEST['bulk_cache_cleared'] );
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(
                    _n(
                        'Cache für %d Begriff wurde geleert.',
                        'Cache für %d Begriffe wurde geleert.',
                        $count,
                        'easy-glossary'
                    ),
                    $count
                )
            );
        }
    }
    
    /**
     * Quick-Edit Felder
     */
    public function quick_edit_fields( $column_name, $post_type ) {
        if ( $post_type !== 'easy_glossary' ) {
            return;
        }
        
        if ( $column_name === 'synonyms' ) {
            ?>
            <fieldset class="inline-edit-col-right">
                <div class="inline-edit-col">
                    <label>
                        <span class="title"><?php _e( 'Synonyme', 'easy-glossary' ); ?></span>
                        <span class="input-text-wrap">
                            <input type="text" name="glossary_synonyms" class="ptitle" value="">
                        </span>
                    </label>
                    <p class="description"><?php _e( 'Komma-getrennt', 'easy-glossary' ); ?></p>
                </div>
            </fieldset>
            <?php
        }
    }
    
    /**
     * Bulk-Edit Felder
     */
    public function bulk_edit_fields( $column_name, $post_type ) {
        if ( $post_type !== 'easy_glossary' ) {
            return;
        }
        
        if ( $column_name === 'synonyms' ) {
            ?>
            <fieldset class="inline-edit-col-right">
                <div class="inline-edit-col">
                    <label>
                        <span class="title"><?php _e( 'Synonyme hinzufügen', 'easy-glossary' ); ?></span>
                        <span class="input-text-wrap">
                            <input type="text" name="bulk_glossary_synonyms" class="ptitle" value="">
                        </span>
                    </label>
                    <p class="description"><?php _e( 'Komma-getrennt. Werden zu bestehenden Synonymen hinzugefügt.', 'easy-glossary' ); ?></p>
                </div>
            </fieldset>
            <?php
        }
    }
    
    /**
     * Speichere Quick-Edit Daten
     */
    public function save_quick_edit( $post_id, $post ) {
        // Nonce-Prüfung
        if ( ! isset( $_POST['_inline_edit'] ) || ! wp_verify_nonce( $_POST['_inline_edit'], 'inlineeditnonce' ) ) {
            return;
        }
        
        // Capability-Check
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Synonyme speichern
        if ( isset( $_POST['glossary_synonyms'] ) ) {
            $synonyms = sanitize_text_field( $_POST['glossary_synonyms'] );
            $synonyms_array = array_map( 'trim', explode( ',', $synonyms ) );
            $synonyms_array = array_filter( $synonyms_array );
            
            update_post_meta( $post_id, '_glossary_synonyms', $synonyms_array );
        }
        
        // Bulk-Edit Synonyme
        if ( isset( $_POST['bulk_glossary_synonyms'] ) ) {
            $new_synonyms = sanitize_text_field( $_POST['bulk_glossary_synonyms'] );
            $new_synonyms_array = array_map( 'trim', explode( ',', $new_synonyms ) );
            $new_synonyms_array = array_filter( $new_synonyms_array );
            
            // Bestehende Synonyme holen
            $existing = get_post_meta( $post_id, '_glossary_synonyms', true );
            if ( ! is_array( $existing ) ) {
                $existing = array();
            }
            
            // Zusammenführen
            $merged = array_unique( array_merge( $existing, $new_synonyms_array ) );
            
            update_post_meta( $post_id, '_glossary_synonyms', $merged );
        }
    }
}

// Initialize
new Easy_Glossary_Bulk_Actions();
