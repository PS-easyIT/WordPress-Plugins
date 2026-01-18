<?php
/**
 * easyGlossary Settings Page
 * 
 * @package Easy_Glossary
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle form submission
if ( isset( $_POST['easy_glossary_settings_nonce'] ) && wp_verify_nonce( $_POST['easy_glossary_settings_nonce'], 'easy_glossary_settings' ) ) {
    
    // Auto-linking settings
    $auto_link = isset( $_POST['auto_link'] ) ? 1 : 0;
    update_option( 'easy_glossary_auto_link', $auto_link );
    
    // Tooltip settings
    $tooltip_style = sanitize_text_field( $_POST['tooltip_style'] ?? 'dark' );
    update_option( 'easy_glossary_tooltip_style', $tooltip_style );
    
    // Display settings
    $terms_per_page = intval( $_POST['terms_per_page'] ?? 20 );
    update_option( 'easy_glossary_terms_per_page', $terms_per_page );
    
    // Exclude settings
    $exclude_posts = sanitize_textarea_field( $_POST['exclude_posts'] ?? '' );
    update_option( 'easy_glossary_exclude_posts', $exclude_posts );
    
    echo '<div class="notice notice-success"><p>' . __( 'Einstellungen gespeichert!', 'easy-glossary' ) . '</p></div>';
}

// Get current settings
$auto_link = get_option( 'easy_glossary_auto_link', true );
$tooltip_style = get_option( 'easy_glossary_tooltip_style', 'dark' );
$terms_per_page = get_option( 'easy_glossary_terms_per_page', 20 );
$exclude_posts = get_option( 'easy_glossary_exclude_posts', '' );
?>

<div class="wrap easy-glossary-dashboard">
    <h1 class="wp-heading-inline">
        <?php _e( 'easyGlossary Einstellungen', 'easy-glossary' ); ?>
        <span class="title-version">v<?php echo EASY_GLOSSARY_VERSION; ?></span>
    </h1>

    <form method="post" action="">
        <?php wp_nonce_field( 'easy_glossary_settings', 'easy_glossary_settings_nonce' ); ?>
        
        <div class="dashboard-widgets">
            <!-- Auto-Linking Settings -->
            <div class="dashboard-widget">
                <h3><?php _e( 'Auto-Linking Einstellungen', 'easy-glossary' ); ?></h3>
                <div class="widget-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Auto-Linking aktivieren', 'easy-glossary' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_link" value="1" <?php checked( $auto_link ); ?>>
                                    <?php _e( 'Automatisch Begriffe in Inhalten verlinken', 'easy-glossary' ); ?>
                                </label>
                                <p class="description"><?php _e( 'Begriffe werden automatisch erkannt und mit Tooltips versehen.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Tooltip Settings -->
            <div class="dashboard-widget">
                <h3><?php _e( 'Tooltip Einstellungen', 'easy-glossary' ); ?></h3>
                <div class="widget-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Tooltip Design', 'easy-glossary' ); ?></th>
                            <td>
                                <select name="tooltip_style">
                                    <option value="dark" <?php selected( $tooltip_style, 'dark' ); ?>><?php _e( 'Dunkel', 'easy-glossary' ); ?></option>
                                    <option value="light" <?php selected( $tooltip_style, 'light' ); ?>><?php _e( 'Hell', 'easy-glossary' ); ?></option>
                                    <option value="colorful" <?php selected( $tooltip_style, 'colorful' ); ?>><?php _e( 'Farbenfroh', 'easy-glossary' ); ?></option>
                                </select>
                                <p class="description"><?php _e( 'Wählen Sie das Design für die Tooltips.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="dashboard-widgets">
            <!-- Display Settings -->
            <div class="dashboard-widget">
                <h3><?php _e( 'Anzeige Einstellungen', 'easy-glossary' ); ?></h3>
                <div class="widget-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Begriffe pro Seite', 'easy-glossary' ); ?></th>
                            <td>
                                <input type="number" name="terms_per_page" value="<?php echo esc_attr( $terms_per_page ); ?>" min="1" max="100">
                                <p class="description"><?php _e( 'Anzahl der Begriffe pro Seite in der Glossar-Ansicht.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Exclusion Settings -->
            <div class="dashboard-widget">
                <h3><?php _e( 'Ausschluss Einstellungen', 'easy-glossary' ); ?></h3>
                <div class="widget-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Ausgeschlossene Beiträge', 'easy-glossary' ); ?></th>
                            <td>
                                <textarea name="exclude_posts" rows="4" class="large-text" placeholder="123, 456, 789"><?php echo esc_textarea( $exclude_posts ); ?></textarea>
                                <p class="description"><?php _e( 'Beitrags-IDs (durch Kommas getrennt), in denen Auto-Linking deaktiviert werden soll.', 'easy-glossary' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Shortcode Reference -->
        <div class="dashboard-instructions">
            <h3><?php _e( 'Verfügbare Shortcodes', 'easy-glossary' ); ?></h3>
            
            <div class="instruction-box">
                <h4><?php _e( 'Einzelner Begriff', 'easy-glossary' ); ?></h4>
                <p><code>[glossary term="WordPress"]</code></p>
                <p class="description"><?php _e( 'Zeigt einen einzelnen Begriff mit Tooltip an.', 'easy-glossary' ); ?></p>
            </div>
            
            <div class="instruction-box">
                <h4><?php _e( 'Begriffe-Liste', 'easy-glossary' ); ?></h4>
                <p><code>[glossary_list category="webdev" limit="10"]</code></p>
                <p class="description"><?php _e( 'Zeigt eine Liste von Begriffen an, optional nach Kategorie gefiltert.', 'easy-glossary' ); ?></p>
            </div>
            
            <div class="instruction-box">
                <h4><?php _e( 'Suchformular', 'easy-glossary' ); ?></h4>
                <p><code>[glossary_search]</code></p>
                <p class="description"><?php _e( 'Fügt ein Suchformular für Glossar-Begriffe ein.', 'easy-glossary' ); ?></p>
            </div>
            
            <div class="instruction-box">
                <h4><?php _e( 'Zufälliger Begriff', 'easy-glossary' ); ?></h4>
                <p><code>[glossary_random]</code></p>
                <p class="description"><?php _e( 'Zeigt einen zufälligen Begriff an.', 'easy-glossary' ); ?></p>
            </div>
            
            <div class="instruction-box">
                <h4><?php _e( 'Kategorien-Liste', 'easy-glossary' ); ?></h4>
                <p><code>[glossary_categories show_count="true"]</code></p>
                <p class="description"><?php _e( 'Zeigt alle Glossar-Kategorien an.', 'easy-glossary' ); ?></p>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary">
                <span class="dashicons dashicons-saved"></span>
                <?php _e( 'Einstellungen speichern', 'easy-glossary' ); ?>
            </button>
        </p>
    </form>
</div>
