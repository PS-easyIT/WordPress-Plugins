<?php
/**
 * easyGlossary Dashboard Page
 * 
 * @package Easy_Glossary
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get glossary statistics
$total_terms = wp_count_posts( 'glossary_term' )->publish;
$total_categories = wp_count_terms( array( 'taxonomy' => 'glossary_category', 'hide_empty' => false ) );
$recent_terms = get_posts( array(
    'post_type' => 'glossary_term',
    'posts_per_page' => 5,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
) );

$auto_link_enabled = get_option( 'easy_glossary_auto_link', true );
?>

<div class="wrap easy-glossary-dashboard">
    <h1 class="wp-heading-inline">
        <?php _e( 'easyGlossary Dashboard', 'easy-glossary' ); ?>
        <span class="title-version">v<?php echo EASY_GLOSSARY_VERSION; ?></span>
    </h1>
    
    <div class="easy-glossary-actions">
        <a href="<?php echo admin_url( 'post-new.php?post_type=glossary_term' ); ?>" class="button button-primary">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e( 'Neuer Begriff', 'easy-glossary' ); ?>
        </a>
        <a href="<?php echo admin_url( 'edit.php?post_type=glossary_term' ); ?>" class="button">
            <span class="dashicons dashicons-list-view"></span>
            <?php _e( 'Alle Begriffe', 'easy-glossary' ); ?>
        </a>
    </div>

    <!-- Statistics Dashboard -->
    <div class="dashboard-widgets">
        <div class="dashboard-widget">
            <h3><?php _e( 'Statistiken', 'easy-glossary' ); ?></h3>
            <div class="widget-content">
                <div class="stat-item">
                    <span class="stat-number"><?php echo intval( $total_terms ); ?></span>
                    <span class="stat-label"><?php _e( 'Begriffe', 'easy-glossary' ); ?></span>
                </div>
            </div>
        </div>

        <div class="dashboard-widget">
            <h3><?php _e( 'Kategorien', 'easy-glossary' ); ?></h3>
            <div class="widget-content">
                <div class="stat-item">
                    <span class="stat-number"><?php echo intval( $total_categories ); ?></span>
                    <span class="stat-label"><?php _e( 'Kategorien', 'easy-glossary' ); ?></span>
                </div>
            </div>
        </div>

        <div class="dashboard-widget">
            <h3><?php _e( 'Auto-Linking', 'easy-glossary' ); ?></h3>
            <div class="widget-content">
                <div class="stat-item">
                    <span class="stat-number">
                        <?php echo $auto_link_enabled ? '<span style="color: var(--eg-success);">✓</span>' : '<span style="color: var(--eg-danger);">✗</span>'; ?>
                    </span>
                    <span class="stat-label">
                        <?php echo $auto_link_enabled ? __( 'Aktiv', 'easy-glossary' ) : __( 'Inaktiv', 'easy-glossary' ); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Terms -->
    <?php if ( ! empty( $recent_terms ) ) : ?>
    <div class="dashboard-instructions">
        <h3><?php _e( 'Neueste Begriffe', 'easy-glossary' ); ?></h3>
        <div class="recent-terms-list">
            <?php foreach ( $recent_terms as $term ) : ?>
            <div class="instruction-box">
                <h4>
                    <a href="<?php echo get_edit_post_link( $term->ID ); ?>">
                        <?php echo esc_html( $term->post_title ); ?>
                    </a>
                </h4>
                <p><?php echo esc_html( wp_trim_words( $term->post_content, 20 ) ); ?></p>
                <small><?php echo sprintf( __( 'Erstellt am %s', 'easy-glossary' ), get_the_date( '', $term->ID ) ); ?></small>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Start Guide -->
    <div class="dashboard-instructions">
        <h3><?php _e( 'Schnellstart-Anleitung', 'easy-glossary' ); ?></h3>
        
        <div class="instruction-box">
            <h4><?php _e( '1. Ersten Begriff hinzufügen', 'easy-glossary' ); ?></h4>
            <p><?php _e( 'Klicken Sie auf "Neuer Begriff" um Ihren ersten Glossar-Eintrag zu erstellen.', 'easy-glossary' ); ?></p>
        </div>
        
        <div class="instruction-box">
            <h4><?php _e( '2. Auto-Linking aktivieren', 'easy-glossary' ); ?></h4>
            <p><?php _e( 'Begriffe werden automatisch in Ihren Inhalten verlinkt und mit Tooltips versehen.', 'easy-glossary' ); ?></p>
        </div>
        
        <div class="instruction-box">
            <h4><?php _e( '3. Shortcodes verwenden', 'easy-glossary' ); ?></h4>
            <p><?php _e( 'Nutzen Sie Shortcodes wie', 'easy-glossary' ); ?> <code>[glossary_list]</code> <?php _e( 'um Begriffe anzuzeigen.', 'easy-glossary' ); ?></p>
        </div>
        
        <div class="instruction-box">
            <h4><?php _e( '4. Kategorien organisieren', 'easy-glossary' ); ?></h4>
            <p><?php _e( 'Erstellen Sie Kategorien um Ihre Begriffe thematisch zu organisieren.', 'easy-glossary' ); ?></p>
        </div>
    </div>
</div>
