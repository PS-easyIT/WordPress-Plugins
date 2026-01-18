<?php
/**
 * Template for displaying single glossary entries
 * Optimiert für MH Magazine Theme
 * 
 * @package easyGlossary
 * @version 1.3.0
 */

get_header();
?>

<div class="mh-wrapper">
    <div class="mh-content">
        <?php while ( have_posts() ) : the_post(); ?>

            <!-- Glossary Entry -->
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'glossary-single-entry' ); ?>>
                
                <!-- Breadcrumb Navigation -->
                <div class="glossary-breadcrumb">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
                    <span class="separator">›</span>
                    <a href="<?php echo esc_url( home_url( '/glossar/' ) ); ?>">Glossar</a>
                    <span class="separator">›</span>
                    <span class="current"><?php the_title(); ?></span>
                </div>

                <!-- Entry Header -->
                <header class="glossary-entry-header">
                    <?php the_title( '<h1 class="glossary-entry-title">', '</h1>' ); ?>
                    
                    <div class="glossary-meta">
                        <time class="entry-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                            <?php echo esc_html( get_the_date() ); ?>
                        </time>
                        
                        <?php if ( get_the_modified_date() !== get_the_date() ) : ?>
                        <span class="meta-separator">•</span>
                        <span class="entry-updated">
                            Aktualisiert: <?php echo esc_html( get_the_modified_date() ); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </header>

                <!-- Quick Definition (Excerpt) -->
                <?php if ( has_excerpt() ) : ?>
                <div class="glossary-quick-definition">
                    <div class="definition-header">
                        <span class="definition-icon">💡</span>
                        <h2>Kurzdefinition</h2>
                    </div>
                    <div class="definition-content">
                        <?php the_excerpt(); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Main Content -->
                <div class="glossary-main-content">
                    <?php
                    the_content();
                    
                    wp_link_pages(
                        array(
                            'before' => '<div class="page-links"><span class="page-links-title">' . esc_html__( 'Seiten:', 'easy-glossary' ) . '</span>',
                            'after'  => '</div>',
                        )
                    );
                    ?>
                </div>

                <!-- Meta Information -->
                <?php
                $synonyms = get_post_meta( get_the_ID(), '_glossary_synonyms', true );
                $related_terms_ids = get_post_meta( get_the_ID(), '_glossary_related_terms', true );
                $external_link = get_post_meta( get_the_ID(), '_glossary_external_link', true );
                ?>

                <?php if ( ! empty( $synonyms ) ) : ?>
                <div class="glossary-synonyms-box">
                    <h3>
                        <span class="box-icon">🔤</span>
                        Synonyme & Alternative Begriffe
                    </h3>
                    <div class="synonyms-list">
                        <?php
                        $synonyms_array = array_map( 'trim', explode( ',', $synonyms ) );
                        foreach ( $synonyms_array as $synonym ) :
                        ?>
                        <span class="synonym-tag"><?php echo esc_html( $synonym ); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $external_link ) ) : ?>
                <div class="glossary-external-link">
                    <h3>
                        <span class="box-icon">🔗</span>
                        Weiterführende Informationen
                    </h3>
                    <a href="<?php echo esc_url( $external_link ); ?>" target="_blank" rel="noopener noreferrer" class="external-link-button">
                        <?php echo esc_html( parse_url( $external_link, PHP_URL_HOST ) ); ?>
                        <span class="external-icon">↗</span>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Related Terms -->
                <?php
                if ( ! empty( $related_terms_ids ) && is_array( $related_terms_ids ) ) :
                    $related_query = new WP_Query( array(
                        'post_type' => 'easy_glossary',
                        'post__in' => $related_terms_ids,
                        'posts_per_page' => 6,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ) );
                else :
                    $related_query = new WP_Query( array(
                        'post_type' => 'easy_glossary',
                        'post_status' => 'publish',
                        'posts_per_page' => 6,
                        'post__not_in' => array( get_the_ID() ),
                        'orderby' => 'rand'
                    ) );
                endif;
                
                if ( $related_query->have_posts() ) :
                ?>
                <div class="glossary-related-terms">
                    <h3>
                        <span class="box-icon">🔍</span>
                        Verwandte Begriffe
                    </h3>
                    <div class="related-terms-grid">
                        <?php while ( $related_query->have_posts() ) : $related_query->the_post(); ?>
                        <a href="<?php the_permalink(); ?>" class="related-term-card">
                            <h4><?php the_title(); ?></h4>
                            <?php if ( has_excerpt() ) : ?>
                            <p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 12, '...' ) ); ?></p>
                            <?php endif; ?>
                            <span class="read-more">Mehr erfahren →</span>
                        </a>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Navigation -->
                <div class="glossary-entry-navigation">
                    <a href="<?php echo esc_url( home_url( '/glossar/' ) ); ?>" class="back-to-glossary">
                        <span class="nav-icon">←</span>
                        Zurück zum Glossar
                    </a>
                    
                    <?php if ( current_user_can( 'edit_post', get_the_ID() ) ) : ?>
                    <a href="<?php echo esc_url( get_edit_post_link() ); ?>" class="edit-entry">
                        <span class="nav-icon">✏️</span>
                        Bearbeiten
                    </a>
                    <?php endif; ?>
                </div>

            </article>

        <?php endwhile; ?>
    </div>
</div>

<?php
// Farben aus Settings laden
$settings = get_option( 'easy_glossary_settings', array() );
$primary_color = $settings['primary_button_color'] ?? '#e64946';
$primary_hover = $settings['primary_button_hover'] ?? '#d43835';
$secondary_color = $settings['secondary_button_color'] ?? '#000000';
$secondary_hover = $settings['secondary_button_hover'] ?? '#333333';
$link_color = $settings['link_color'] ?? '#e64946';
$link_hover = $settings['link_hover_color'] ?? '#d43835';
$heading_color = $settings['heading_color'] ?? '#000000';
$text_color = $settings['text_color'] ?? '#000000';
$text_light = $settings['text_light_color'] ?? '#666666';
?>

<style>
/**
 * Single Glossary Entry Styles
 * Optimiert für MH Magazine Theme - Full Width Layout
 */

/* Full Width Layout für Glossar-Seiten */
.single-easy_glossary .mh-content {
    width: 100%;
    float: none;
    margin-right: 0;
}

.single-easy_glossary .mh-sidebar {
    display: none;
}

/* Breadcrumb Navigation */
.glossary-breadcrumb {
    font-size: 14px;
    font-size: 0.875rem;
    color: <?php echo esc_attr( $text_light ); ?>;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ebebeb;
}

.glossary-breadcrumb a {
    color: <?php echo esc_attr( $text_color ); ?>;
    text-decoration: none;
    transition: 0.25s ease-out;
}

.glossary-breadcrumb a:hover {
    color: <?php echo esc_attr( $primary_color ); ?>;
}

.glossary-breadcrumb .separator {
    margin: 0 8px;
    color: #9a9b97;
}

.glossary-breadcrumb .current {
    color: #9a9b97;
}

/* Entry Header */
.glossary-entry-header {
    background: #f5f5f5;
    padding: 30px;
    margin-bottom: 30px;
    border-radius: 0;
}

.glossary-entry-title {
    font-family: 'Open Sans', Helvetica, Arial, sans-serif;
    font-size: 32px;
    font-size: 2rem;
    color: <?php echo esc_attr( $heading_color ); ?>;
    font-weight: 700;
    line-height: 1.3;
    margin: 0 0 15px 0;
}

.glossary-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-size: 0.875rem;
    color: <?php echo esc_attr( $text_light ); ?>;
}

.glossary-meta .meta-separator {
    color: #9a9b97;
}

.glossary-meta .entry-date,
.glossary-meta .entry-updated {
    display: inline-block;
}

/* Quick Definition Box */
.glossary-quick-definition {
    background: #fff;
    border-left: 5px solid <?php echo esc_attr( $primary_color ); ?>;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0px 0px 10px rgba(50, 50, 50, 0.17);
}

.glossary-quick-definition .definition-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.glossary-quick-definition .definition-icon {
    font-size: 24px;
}

.glossary-quick-definition h2 {
    font-family: 'Open Sans', Helvetica, Arial, sans-serif;
    font-size: 20px;
    font-size: 1.25rem;
    color: <?php echo esc_attr( $heading_color ); ?>;
    font-weight: 700;
    margin: 0;
}

.glossary-quick-definition .definition-content {
    font-size: 16px;
    font-size: 1rem;
    line-height: 1.6;
    color: <?php echo esc_attr( $text_color ); ?>;
}

.glossary-quick-definition .definition-content p {
    margin: 0;
}

/* Main Content */
.glossary-main-content {
    background: #fff;
    padding: 30px;
    margin-bottom: 30px;
    font-size: 14px;
    font-size: 0.875rem;
    line-height: 1.6;
    color: <?php echo esc_attr( $text_color ); ?>;
}

.glossary-main-content h2,
.glossary-main-content h3,
.glossary-main-content h4 {
    font-family: 'Open Sans', Helvetica, Arial, sans-serif;
    color: <?php echo esc_attr( $heading_color ); ?>;
    font-weight: 700;
    margin: 25px 0 15px 0;
}

.glossary-main-content h2 {
    font-size: 24px;
    font-size: 1.5rem;
}

.glossary-main-content h3 {
    font-size: 20px;
    font-size: 1.25rem;
}

.glossary-main-content h4 {
    font-size: 18px;
    font-size: 1.125rem;
}

.glossary-main-content p {
    margin-bottom: 20px;
    margin-bottom: 1.25rem;
}

.glossary-main-content a {
    color: <?php echo esc_attr( $primary_color ); ?>;
    transition: 0.25s ease-out;
}

.glossary-main-content a:hover {
    color: <?php echo esc_attr( $primary_hover ); ?>;
}

.glossary-main-content ul,
.glossary-main-content ol {
    margin: 0 0 20px 40px;
}

.glossary-main-content li {
    margin-bottom: 5px;
}

/* Synonyms Box */
.glossary-synonyms-box {
    background: #f7f7f7;
    padding: 25px;
    margin-bottom: 30px;
    border-radius: 0;
}

.glossary-synonyms-box h3 {
    font-family: 'Open Sans', Helvetica, Arial, sans-serif;
    font-size: 18px;
    font-size: 1.125rem;
    color: <?php echo esc_attr( $heading_color ); ?>;
    font-weight: 700;
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.glossary-synonyms-box .box-icon {
    font-size: 20px;
}

.synonyms-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.synonym-tag {
    display: inline-block;
    background: #fff;
    color: <?php echo esc_attr( $text_color ); ?>;
    padding: 6px 15px;
    border: 1px solid #ebebeb;
    border-radius: 4px;
    font-size: 14px;
    font-size: 0.875rem;
    transition: 0.25s ease-out;
}

.synonym-tag:hover {
    background: <?php echo esc_attr( $primary_color ); ?>;
    color: #fff;
    border-color: <?php echo esc_attr( $primary_color ); ?>;
}

/* External Link Box */
.glossary-external-link {
    background: #f5f5f5;
    padding: 25px;
    margin-bottom: 30px;
}

.glossary-external-link h3 {
    font-family: 'Open Sans', Helvetica, Arial, sans-serif;
    font-size: 18px;
    font-size: 1.125rem;
    color: <?php echo esc_attr( $heading_color ); ?>;
    font-weight: 700;
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.external-link-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: <?php echo esc_attr( $primary_color ); ?>;
    color: #fff;
    padding: 12px 20px;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
    transition: 0.25s ease-out;
}

.external-link-button:hover {
    background: <?php echo esc_attr( $primary_hover ); ?>;
    color: #fff;
}

.external-link-button .external-icon {
    font-size: 18px;
}

/* Related Terms */
.glossary-related-terms {
    background: #fff;
    padding: 30px;
    margin-bottom: 30px;
}

.glossary-related-terms h3 {
    font-family: 'Open Sans', Helvetica, Arial, sans-serif;
    font-size: 20px;
    font-size: 1.25rem;
    color: <?php echo esc_attr( $heading_color ); ?>;
    font-weight: 700;
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid <?php echo esc_attr( $primary_color ); ?>;
    display: flex;
    align-items: center;
    gap: 10px;
}

.related-terms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.related-term-card {
    background: #f7f7f7;
    padding: 20px;
    text-decoration: none;
    display: block;
    transition: 0.25s ease-out;
    border: 1px solid #ebebeb;
}

.related-term-card:hover {
    background: #fff;
    box-shadow: 0px 0px 10px rgba(50, 50, 50, 0.17);
    transform: translateY(-2px);
}

.related-term-card h4 {
    font-family: 'Open Sans', Helvetica, Arial, sans-serif;
    font-size: 16px;
    font-size: 1rem;
    color: <?php echo esc_attr( $heading_color ); ?>;
    font-weight: 700;
    margin: 0 0 10px 0;
}

.related-term-card p {
    font-size: 14px;
    font-size: 0.875rem;
    color: <?php echo esc_attr( $text_light ); ?>;
    line-height: 1.6;
    margin: 0 0 10px 0;
}

.related-term-card .read-more {
    color: <?php echo esc_attr( $primary_color ); ?>;
    font-size: 14px;
    font-size: 0.875rem;
    font-weight: 600;
}

/* Entry Navigation */
.glossary-entry-navigation {
    background: #f5f5f5;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.back-to-glossary,
.edit-entry {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: <?php echo esc_attr( $primary_color ); ?>;
    color: #fff;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
    font-size: 14px;
    font-size: 0.875rem;
    transition: 0.25s ease-out;
}

.back-to-glossary:hover,
.edit-entry:hover {
    background: <?php echo esc_attr( $primary_hover ); ?>;
    color: #fff;
}

.edit-entry {
    background: <?php echo esc_attr( $secondary_color ); ?>;
}

.edit-entry:hover {
    background: <?php echo esc_attr( $secondary_hover ); ?>;
}

/* Responsive Design */
@media only screen and (max-width: 980px) {
    .glossary-entry-header {
        padding: 25px;
    }
    
    .glossary-entry-title {
        font-size: 28px;
        font-size: 1.75rem;
    }
    
    .related-terms-grid {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    }
}

@media only screen and (max-width: 767px) {
    .glossary-entry-header {
        padding: 20px;
    }
    
    .glossary-entry-title {
        font-size: 24px;
        font-size: 1.5rem;
    }
    
    .glossary-quick-definition,
    .glossary-main-content,
    .glossary-synonyms-box,
    .glossary-external-link,
    .glossary-related-terms {
        padding: 20px;
    }
    
    .related-terms-grid {
        grid-template-columns: 1fr;
    }
    
    .glossary-entry-navigation {
        flex-direction: column;
        align-items: stretch;
    }
    
    .back-to-glossary,
    .edit-entry {
        justify-content: center;
        width: 100%;
    }
}

@media only screen and (max-width: 479px) {
    .glossary-breadcrumb {
        font-size: 12px;
        font-size: 0.75rem;
    }
    
    .glossary-entry-header {
        padding: 15px;
    }
    
    .glossary-entry-title {
        font-size: 20px;
        font-size: 1.25rem;
    }
    
    .glossary-meta {
        font-size: 12px;
        font-size: 0.75rem;
    }
}

/* Print Styles */
@media print {
    .glossary-breadcrumb,
    .glossary-badge,
    .glossary-entry-navigation,
    .edit-entry {
        display: none !important;
    }
    
    .glossary-entry-header {
        background: none;
        padding: 0;
    }
}
</style>

<?php
get_footer();
