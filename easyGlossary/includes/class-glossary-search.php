<?php
/**
 * Glossary Search Handler
 * 
 * Erweiterte Suchfunktionalität für Glossar-Begriffe
 * 
 * @package easyGlossary
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Easy_Glossary_Search {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Erweitere WordPress-Suche für Glossar
        add_filter( 'pre_get_posts', array( $this, 'extend_search' ) );
        
        // AJAX-Suche für Live-Search
        add_action( 'wp_ajax_glossary_live_search', array( $this, 'ajax_live_search' ) );
        add_action( 'wp_ajax_nopriv_glossary_live_search', array( $this, 'ajax_live_search' ) );
        
        // Suchformular-Shortcode
        add_shortcode( 'glossary_search', array( $this, 'search_form_shortcode' ) );
    }
    
    /**
     * Erweitere WordPress-Suche für Glossar-Begriffe
     */
    public function extend_search( $query ) {
        // Nur für Hauptabfrage und Suchseiten
        if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
            
            // Prüfe ob nach Glossar-Begriffen gesucht werden soll
            $search_glossary = get_query_var( 'search_glossary', false );
            
            if ( $search_glossary || isset( $_GET['post_type'] ) && $_GET['post_type'] === 'easy_glossary' ) {
                // Nur Glossar-Einträge durchsuchen
                $query->set( 'post_type', 'easy_glossary' );
                
                // Auch in Meta-Feldern suchen (Synonyme)
                add_filter( 'posts_search', array( $this, 'search_meta_fields' ), 10, 2 );
            }
        }
        
        return $query;
    }
    
    /**
     * Suche auch in Meta-Feldern (Synonyme)
     */
    public function search_meta_fields( $search, $query ) {
        global $wpdb;
        
        if ( empty( $search ) ) {
            return $search;
        }
        
        $search_term = $query->get( 's' );
        if ( empty( $search_term ) ) {
            return $search;
        }
        
        // Suche auch in Synonymen
        $meta_search = " OR EXISTS (
            SELECT * FROM {$wpdb->postmeta} 
            WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
            AND {$wpdb->postmeta}.meta_key = '_glossary_synonyms'
            AND {$wpdb->postmeta}.meta_value LIKE '%" . esc_sql( $wpdb->esc_like( $search_term ) ) . "%'
        )";
        
        $search = str_replace( ')))', '))' . $meta_search . ')', $search );
        
        return $search;
    }
    
    /**
     * AJAX Live-Search
     */
    public function ajax_live_search() {
        check_ajax_referer( 'glossary_search_nonce', 'nonce' );
        
        $search_term = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        
        if ( empty( $search_term ) || strlen( $search_term ) < 2 ) {
            wp_send_json_success( array( 'results' => array() ) );
        }
        
        // Suche in Titeln
        $args = array(
            'post_type' => 'easy_glossary',
            'post_status' => 'publish',
            's' => $search_term,
            'posts_per_page' => 10,
            'orderby' => 'relevance',
            'order' => 'DESC'
        );
        
        $query = new WP_Query( $args );
        $results = array();
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                
                $results[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'excerpt' => wp_trim_words( get_the_excerpt(), 15, '...' ),
                    'url' => get_permalink()
                );
            }
            wp_reset_postdata();
        }
        
        // Suche auch in Synonymen wenn keine Ergebnisse
        if ( empty( $results ) ) {
            $meta_query = new WP_Query( array(
                'post_type' => 'easy_glossary',
                'post_status' => 'publish',
                'posts_per_page' => 10,
                'meta_query' => array(
                    array(
                        'key' => '_glossary_synonyms',
                        'value' => $search_term,
                        'compare' => 'LIKE'
                    )
                )
            ) );
            
            if ( $meta_query->have_posts() ) {
                while ( $meta_query->have_posts() ) {
                    $meta_query->the_post();
                    
                    $results[] = array(
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'excerpt' => wp_trim_words( get_the_excerpt(), 15, '...' ),
                        'url' => get_permalink(),
                        'match_type' => 'synonym'
                    );
                }
                wp_reset_postdata();
            }
        }
        
        wp_send_json_success( array( 'results' => $results ) );
    }
    
    /**
     * Suchformular Shortcode
     */
    public function search_form_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'placeholder' => __( 'Glossar durchsuchen...', 'easy-glossary' ),
            'button_text' => __( 'Suchen', 'easy-glossary' ),
            'live_search' => 'true',
            'autocomplete' => 'true',
            'show_categories' => 'false'
        ), $atts );
        
        $live_search = filter_var( $atts['live_search'], FILTER_VALIDATE_BOOLEAN );
        $autocomplete = filter_var( $atts['autocomplete'], FILTER_VALIDATE_BOOLEAN );
        $show_categories = filter_var( $atts['show_categories'], FILTER_VALIDATE_BOOLEAN );
        $unique_id = 'glossary-search-' . uniqid();
        
        ob_start();
        ?>
        <div class="glossary-search-widget" id="<?php echo esc_attr( $unique_id ); ?>">
            <form class="glossary-search-form" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <div class="search-input-wrapper">
                    <input 
                        type="text" 
                        name="s" 
                        class="glossary-search-input" 
                        placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
                        autocomplete="off"
                        <?php if ( $live_search ) echo 'data-live-search="true"'; ?>
                    >
                    <input type="hidden" name="post_type" value="easy_glossary">
                    <button type="submit" class="glossary-search-button">
                        <?php echo esc_html( $atts['button_text'] ); ?>
                    </button>
                </div>
                
                <?php if ( $live_search ) : ?>
                <div class="glossary-search-results" style="display: none;">
                    <div class="search-results-list"></div>
                </div>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if ( $live_search ) : ?>
        <style>
        .glossary-search-widget {
            position: relative;
            margin: 1rem 0;
        }
        
        .glossary-search-form {
            position: relative;
        }
        
        .search-input-wrapper {
            display: flex;
            gap: 0.5rem;
        }
        
        .glossary-search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .glossary-search-button {
            padding: 0.75rem 1.5rem;
            background: var(--primary-color, #2563eb);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s ease;
        }
        
        .glossary-search-button:hover {
            background: var(--primary-color-dark, #1d4ed8);
        }
        
        .glossary-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .search-results-list {
            padding: 0.5rem;
        }
        
        .search-result-item {
            padding: 0.75rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s ease;
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .search-result-item:hover {
            background: #f9fafb;
        }
        
        .search-result-item a {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .search-result-title {
            font-weight: 600;
            color: var(--primary-color, #2563eb);
            margin-bottom: 0.25rem;
        }
        
        .search-result-excerpt {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .search-result-badge {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            background: #fef3c7;
            color: #92400e;
            font-size: 0.75rem;
            border-radius: 4px;
            margin-left: 0.5rem;
        }
        
        .search-no-results {
            padding: 1rem;
            text-align: center;
            color: #6b7280;
        }
        
        .search-loading {
            padding: 1rem;
            text-align: center;
            color: #6b7280;
        }
        </style>
        
        <script>
        (function() {
            var searchWidget = document.getElementById('<?php echo esc_js( $unique_id ); ?>');
            if (!searchWidget) return;
            
            var searchInput = searchWidget.querySelector('.glossary-search-input');
            var resultsContainer = searchWidget.querySelector('.glossary-search-results');
            var resultsList = searchWidget.querySelector('.search-results-list');
            var searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                
                var searchTerm = this.value.trim();
                
                if (searchTerm.length < 2) {
                    resultsContainer.style.display = 'none';
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    performSearch(searchTerm);
                }, 300);
            });
            
            function performSearch(term) {
                resultsList.innerHTML = '<div class="search-loading">Suche läuft...</div>';
                resultsContainer.style.display = 'block';
                
                var formData = new FormData();
                formData.append('action', 'glossary_live_search');
                formData.append('nonce', '<?php echo wp_create_nonce( 'glossary_search_nonce' ); ?>');
                formData.append('search', term);
                
                fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.results) {
                        displayResults(data.data.results);
                    } else {
                        resultsList.innerHTML = '<div class="search-no-results">Keine Ergebnisse gefunden</div>';
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    resultsList.innerHTML = '<div class="search-no-results">Fehler bei der Suche</div>';
                });
            }
            
            function displayResults(results) {
                if (results.length === 0) {
                    resultsList.innerHTML = '<div class="search-no-results">Keine Ergebnisse gefunden</div>';
                    return;
                }
                
                var html = '';
                results.forEach(function(result) {
                    var badge = result.match_type === 'synonym' ? 
                        '<span class="search-result-badge">Synonym</span>' : '';
                    
                    html += '<div class="search-result-item">';
                    html += '<a href="' + result.url + '">';
                    html += '<div class="search-result-title">' + result.title + badge + '</div>';
                    html += '<div class="search-result-excerpt">' + result.excerpt + '</div>';
                    html += '</a>';
                    html += '</div>';
                });
                
                resultsList.innerHTML = html;
            }
            
            // Schließe Ergebnisse bei Klick außerhalb
            document.addEventListener('click', function(e) {
                if (!searchWidget.contains(e.target)) {
                    resultsContainer.style.display = 'none';
                }
            });
        })();
        </script>
        <?php endif; ?>
        
        <?php
        return ob_get_clean();
    }
}

// Initialize
new Easy_Glossary_Search();
