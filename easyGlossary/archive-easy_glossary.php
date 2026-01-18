<?php
/**
 * Template for displaying glossary archive
 * 
 * @package easyGlossary
 */

get_header();
?>

<div class="mh-wrapper">
    <div class="mh-content">
        
            <!-- Glossary Search -->
            <div class="glossary-search-section">
                <form class="glossary-search-form" method="get">
                    <div class="search-input-group">
                        <input type="text" name="s" placeholder="<?php _e( 'Glossar-Begriff suchen...', 'easy-glossary' ); ?>" 
                               value="<?php echo esc_attr( get_query_var( 's' ) ); ?>" class="search-input">
                        <button type="submit" class="search-button">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </div>
                    <input type="hidden" name="post_type" value="easy_glossary">
                </form>
            </div>

            <!-- Glossary Content -->
            <div class="glossary-archive-content">
                <?php if ( have_posts() ) : ?>
                    
                    <!-- A-Z Navigation -->
                    <div class="glossary-alphabet-nav" data-glossary-archive>
                        <h3><?php _e( 'Nach Buchstaben filtern', 'easy-glossary' ); ?></h3>
                        <div class="alphabet-links">
                            <?php
                            // Alle Einträge für Buchstaben-Zählung abrufen
                            $all_glossary_terms = get_posts( array(
                                'post_type' => 'easy_glossary',
                                'post_status' => 'publish',
                                'numberposts' => -1
                            ) );
                            
                            // Nach Buchstaben gruppieren
                            $terms_by_letter = array();
                            foreach ( $all_glossary_terms as $term ) {
                                $first_letter = strtoupper( mb_substr( get_the_title( $term->ID ), 0, 1 ) );
                                if ( ! preg_match( '/[A-Z]/', $first_letter ) ) {
                                    $first_letter = '#';
                                }
                                if ( ! isset( $terms_by_letter[ $first_letter ] ) ) {
                                    $terms_by_letter[ $first_letter ] = 0;
                                }
                                $terms_by_letter[ $first_letter ]++;
                            }
                            
                            // "Alle" Button
                            echo '<a href="#" class="alphabet-link show-all active" data-letter="all">';
                            echo esc_html__( 'Alle', 'easy-glossary' );
                            echo ' <span class="letter-count">(' . count( $all_glossary_terms ) . ')</span>';
                            echo '</a>';
                            
                            // A-Z Buttons
                            $alphabet = array_merge( range( 'A', 'Z' ), array( '#' ) );
                            foreach ( $alphabet as $letter ) :
                                $has_terms = isset( $terms_by_letter[ $letter ] );
                                $count = $has_terms ? $terms_by_letter[ $letter ] : 0;
                                $class = $has_terms ? 'has-posts' : 'no-posts';
                            ?>
                            <a href="#" class="alphabet-link <?php echo esc_attr( $class ); ?>" data-letter="<?php echo esc_attr( $letter ); ?>">
                                <?php echo esc_html( $letter ); ?>
                                <?php if ( $has_terms ) : ?>
                                <span class="letter-count">(<?php echo $count; ?>)</span>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Glossary Terms List - Nur Titel -->
                    <div class="glossary-terms-simple-list">
                        <?php 
                        $current_letter = '';
                        while ( have_posts() ) : the_post(); 
                            $title = get_the_title();
                            $first_letter = strtoupper( mb_substr( $title, 0, 1 ) );
                            
                            // Nicht-Buchstaben unter '#' gruppieren
                            if ( ! preg_match( '/[A-Z]/', $first_letter ) ) {
                                $first_letter = '#';
                            }
                            
                            // Neue Buchstaben-Sektion
                            if ( $current_letter !== $first_letter ) :
                                if ( $current_letter !== '' ) :
                                    echo '</ul></div>'; // Close previous letter section
                                endif;
                                $current_letter = $first_letter;
                        ?>
                        <div class="glossary-letter-group" data-letter="<?php echo esc_attr( $current_letter ); ?>">
                            <h2 class="letter-heading"><?php echo esc_html( $current_letter ); ?></h2>
                            <ul class="terms-list">
                        <?php endif; ?>
                        
                            <li class="term-list-item">
                                <a href="<?php echo esc_url( get_permalink() ); ?>" class="term-link">
                                    <?php echo esc_html( $title ); ?>
                                </a>
                            </li>
                        
                        <?php endwhile; ?>
                        
                        <?php if ( $current_letter !== '' ) : ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <div class="glossary-pagination">
                        <?php
                        the_posts_pagination( array(
                            'mid_size' => 2,
                            'prev_text' => __( '← Vorherige', 'easy-glossary' ),
                            'next_text' => __( 'Nächste →', 'easy-glossary' ),
                        ) );
                        ?>
                    </div>

                <?php else : ?>
                    
                    <div class="no-glossary-terms">
                        <h2><?php _e( 'Keine Glossar-Begriffe gefunden', 'easy-glossary' ); ?></h2>
                        <p><?php _e( 'Es wurden noch keine Glossar-Begriffe erstellt.', 'easy-glossary' ); ?></p>
                        
                        <?php if ( current_user_can( 'edit_posts' ) ) : ?>
                        <p>
                            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=easy_glossary' ) ); ?>" class="button button-primary">
                                <?php _e( 'Ersten Begriff erstellen', 'easy-glossary' ); ?>
                            </a>
                        </p>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>
            </div>
        </div>

<?php
// Farben aus Settings laden
$settings = get_option( 'easy_glossary_settings', array() );
$primary_color = $settings['primary_button_color'] ?? '#e64946';
$primary_hover = $settings['primary_button_hover'] ?? '#d43835';
$link_color = $settings['link_color'] ?? '#e64946';
$link_hover = $settings['link_hover_color'] ?? '#d43835';
$heading_color = $settings['heading_color'] ?? '#000000';
$text_color = $settings['text_color'] ?? '#000000';
$text_light = $settings['text_light_color'] ?? '#666666';
?>

<style>
/**
 * Glossary Archive Styles
 * Optimiert für MH Magazine Theme - Full Width Layout
 */

/* Full Width Layout für Glossar-Archive */
.post-type-archive-easy_glossary .mh-content {
    width: 100%;
    float: none;
    margin-right: 0;
}

.post-type-archive-easy_glossary .mh-sidebar {
    display: none;
}

/* Glossary Search Section */
.glossary-search-section {
    background: #f5f5f5;
    padding: 25px;
    margin-bottom: 30px;
}

.glossary-search-form {
    max-width: 500px;
    margin: 0 auto;
}

.search-input-group {
    display: flex;
    gap: 0.5rem;
}

.search-input {
    flex: 1;
    padding: 12px 15px;
    border: 1px solid #ebebeb;
    border-radius: 4px;
    font-family: 'Open Sans', Helvetica, Arial, sans-serif;
    font-size: 14px;
    font-size: 0.875rem;
    color: <?php echo esc_attr( $text_color ); ?>;
    transition: 0.25s ease-out;
}

.search-input:focus {
    outline: none;
    border-color: <?php echo esc_attr( $primary_color ); ?>;
}

.search-button {
    padding: 12px 20px;
    background: <?php echo esc_attr( $primary_color ); ?>;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    transition: 0.25s ease-out;
}

.search-button:hover {
    background: <?php echo esc_attr( $primary_hover ); ?>;
}

.glossary-alphabet-nav {
    margin-bottom: 30px;
    padding: 25px;
    background: #fff;
    border: 1px solid #ebebeb;
}

.glossary-alphabet-nav h3 {
    font-family: 'Open Sans', Helvetica, Arial, sans-serif;
    font-size: 18px;
    font-size: 1.125rem;
    color: <?php echo esc_attr( $heading_color ); ?>;
    font-weight: 700;
    margin: 0 0 15px 0;
}

.alphabet-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.alphabet-link {
    display: inline-block;
    padding: 8px 12px;
    background: #f5f5f5;
    color: <?php echo esc_attr( $text_light ); ?>;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
    font-size: 14px;
    font-size: 0.875rem;
    transition: 0.25s ease-out;
    min-width: 36px;
    text-align: center;
}

.alphabet-link.has-posts {
    background: <?php echo esc_attr( $primary_color ); ?>;
    color: #fff;
}

.alphabet-link.has-posts:hover {
    background: <?php echo esc_attr( $primary_hover ); ?>;
}

.alphabet-link.no-posts {
    opacity: 0.3;
    cursor: not-allowed;
}

.alphabet-link.active {
    background: <?php echo esc_attr( $primary_hover ); ?> !important;
}

.letter-count {
    font-size: 0.85em;
    opacity: 0.8;
}

/* Neue Listendarstellung */
.glossary-terms-simple-list {
    margin-top: 2rem;
}

.glossary-letter-group {
    margin-bottom: 2rem;
}

.letter-heading {
    font-family: 'Open Sans', Helvetica, Arial, sans-serif;
    color: <?php echo esc_attr( $heading_color ); ?>;
    font-size: 24px;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 15px 0;
    padding: 15px 20px;
    background: #f5f5f5;
    border-left: 5px solid <?php echo esc_attr( $primary_color ); ?>;
}

.terms-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 0.5rem;
}

.term-list-item {
    margin: 0;
}

.term-link {
    display: block;
    padding: 12px 15px;
    background: #fff;
    border: 1px solid #ebebeb;
    color: <?php echo esc_attr( $link_color ); ?>;
    text-decoration: none;
    font-weight: 600;
    transition: 0.25s ease-out;
}

.term-link:hover {
    background: #f7f7f7;
    color: <?php echo esc_attr( $link_hover ); ?>;
    border-color: <?php echo esc_attr( $primary_color ); ?>;
}

.term-title a {
    color: var(--primary-color, #2563eb);
    text-decoration: none;
}

.term-title a:hover {
    text-decoration: underline;
}

.term-excerpt {
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.term-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.read-more-link {
    color: var(--primary-color, #2563eb);
    text-decoration: none;
    font-weight: 500;
}

.read-more-link:hover {
    text-decoration: underline;
}

.glossary-pagination {
    margin-top: 2rem;
    text-align: center;
}

.no-glossary-terms {
    text-align: center;
    padding: 3rem 1rem;
    background: #f8fafc;
    border-radius: 12px;
}

.no-glossary-terms h2 {
    font-family: 'Open Sans', Helvetica, Arial, sans-serif;
    color: <?php echo esc_attr( $heading_color ); ?>;
    font-weight: 700;
    margin-bottom: 15px;
}

.no-glossary-terms p {
    color: <?php echo esc_attr( $text_light ); ?>;
    margin-bottom: 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .alphabet-links {
        justify-content: center;
    }
    
    .glossary-search-form {
        max-width: 100%;
    }
    
    .search-input-group {
        flex-direction: column;
    }
    
    .terms-list {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .alphabet-link {
        padding: 0.4rem 0.6rem;
        font-size: 0.9rem;
    }
    
    .letter-heading {
        font-size: 1.5rem;
    }
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const archiveContainer = document.querySelector('[data-glossary-archive]');
        if (!archiveContainer) return;
        
        const letterLinks = archiveContainer.querySelectorAll('.alphabet-link');
        const letterGroups = document.querySelectorAll('.glossary-letter-group');
        
        letterLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const selectedLetter = this.getAttribute('data-letter');
                
                // Aktiven Link markieren
                letterLinks.forEach(function(l) {
                    l.classList.remove('active');
                });
                this.classList.add('active');
                
                // Gruppen filtern
                if (selectedLetter === 'all') {
                    // Alle anzeigen
                    letterGroups.forEach(function(group) {
                        group.style.display = 'block';
                    });
                } else {
                    // Nur gewählten Buchstaben anzeigen
                    letterGroups.forEach(function(group) {
                        const groupLetter = group.getAttribute('data-letter');
                        if (groupLetter === selectedLetter) {
                            group.style.display = 'block';
                        } else {
                            group.style.display = 'none';
                        }
                    });
                    
                    // Zum ersten sichtbaren Element scrollen
                    const visibleGroup = document.querySelector('.glossary-letter-group[data-letter="' + selectedLetter + '"]');
                    if (visibleGroup) {
                        visibleGroup.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });
    });
})();
</script>

<?php
get_footer();
