<?php
/**
 * Test Script für easyGlossary Funktionalität
 * 
 * Dieses Script testet die Permalink-Funktionalität des easyGlossary Plugins
 */

// Nur im Admin-Bereich ausführen
if ( ! is_admin() ) {
    wp_die( 'Dieses Script kann nur im Admin-Bereich ausgeführt werden.' );
}

// Prüfe ob das Plugin aktiv ist
if ( ! class_exists( 'Easy_Glossary' ) ) {
    wp_die( 'easyGlossary Plugin ist nicht aktiv!' );
}

echo '<div style="padding: 20px; background: #f1f1f1; margin: 20px;">';
echo '<h2>🔧 easyGlossary Funktionalitäts-Test</h2>';

// Test 1: Plugin-Instanz
echo '<h3>1. Plugin-Instanz</h3>';
$glossary = Easy_Glossary::instance();
if ( $glossary ) {
    echo '✅ Plugin-Instanz erfolgreich erstellt<br>';
} else {
    echo '❌ Plugin-Instanz konnte nicht erstellt werden<br>';
}

// Test 2: Post Type registriert
echo '<h3>2. Post Type Registrierung</h3>';
$post_type = get_post_type_object( 'easy_glossary' );
if ( $post_type ) {
    echo '✅ Post Type "easy_glossary" ist registriert<br>';
    echo 'Public: ' . ( $post_type->public ? 'Ja' : 'Nein' ) . '<br>';
    echo 'Rewrite Slug: ' . $post_type->rewrite['slug'] . '<br>';
} else {
    echo '❌ Post Type "easy_glossary" ist nicht registriert<br>';
}

// Test 3: Rewrite Rules
echo '<h3>3. Rewrite Rules</h3>';
global $wp_rewrite;
$rules = $wp_rewrite->wp_rewrite_rules();
$glossary_rules = array_filter( $rules, function( $key ) {
    return strpos( $key, 'glossar' ) !== false;
}, ARRAY_FILTER_USE_KEY );

if ( ! empty( $glossary_rules ) ) {
    echo '✅ Rewrite Rules für Glossar gefunden:<br>';
    foreach ( $glossary_rules as $pattern => $replacement ) {
        echo "Pattern: <code>$pattern</code> → <code>$replacement</code><br>";
    }
} else {
    echo '❌ Keine Rewrite Rules für Glossar gefunden<br>';
}

// Test 4: Glossar-Einträge
echo '<h3>4. Glossar-Einträge</h3>';
$glossary_posts = get_posts( array(
    'post_type' => 'easy_glossary',
    'post_status' => 'publish',
    'numberposts' => 5
) );

if ( ! empty( $glossary_posts ) ) {
    echo '✅ ' . count( $glossary_posts ) . ' Glossar-Einträge gefunden:<br>';
    foreach ( $glossary_posts as $post ) {
        $permalink = get_permalink( $post->ID );
        echo "- <strong>" . esc_html( $post->post_title ) . "</strong><br>";
        echo "  Permalink: <a href='$permalink' target='_blank'>$permalink</a><br>";
        echo "  Status: " . ( $permalink ? '✅ OK' : '❌ FEHLER' ) . '<br><br>';
    }
} else {
    echo '⚠️ Keine Glossar-Einträge gefunden. Erstelle einen Test-Eintrag:<br>';
    echo '<a href="' . admin_url( 'post-new.php?post_type=easy_glossary' ) . '" target="_blank">Neuen Glossar-Eintrag erstellen</a><br>';
}

// Test 5: Template-Dateien
echo '<h3>5. Template-Dateien</h3>';
$plugin_dir = plugin_dir_path( __FILE__ );
$single_template = $plugin_dir . 'single-easy_glossary.php';
$archive_template = $plugin_dir . 'archive-easy_glossary.php';

echo 'Single Template: ' . ( file_exists( $single_template ) ? '✅' : '❌' ) . ' ' . $single_template . '<br>';
echo 'Archive Template: ' . ( file_exists( $archive_template ) ? '✅' : '❌' ) . ' ' . $archive_template . '<br>';

// Test 6: Cache-Status
echo '<h3>6. Cache-Status</h3>';
$cache_key = 'easy_glossary_terms';
$cached_terms = wp_cache_get( $cache_key );
echo 'Cache aktiv: ' . ( $cached_terms !== false ? '✅ Ja' : '❌ Nein' ) . '<br>';

// Test 7: Permalink-Struktur
echo '<h3>7. Permalink-Struktur</h3>';
$permalink_structure = get_option( 'permalink_structure' );
echo 'Aktuelle Permalink-Struktur: <code>' . esc_html( $permalink_structure ) . '</code><br>';

if ( empty( $permalink_structure ) ) {
    echo '⚠️ Permalinks sind auf "Standard" gesetzt. Für Glossar-Funktionalität wird eine benutzerdefinierte Struktur empfohlen.<br>';
    echo '<a href="' . admin_url( 'options-permalink.php' ) . '" target="_blank">Permalink-Einstellungen ändern</a><br>';
}

// Test 8: AJAX-Funktionen
echo '<h3>8. AJAX-Funktionen</h3>';
$ajax_actions = array(
    'easy_glossary_import',
    'easy_glossary_export', 
    'easy_glossary_debug',
    'easy_glossary_fix_permalinks'
);

foreach ( $ajax_actions as $action ) {
    $hook_exists = has_action( "wp_ajax_$action" );
    echo "AJAX Action '$action': " . ( $hook_exists ? '✅' : '❌' ) . '<br>';
}

// Reparatur-Button
echo '<h3>9. Schnell-Reparatur</h3>';
echo '<button id="quick-fix-btn" style="padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer;">Permalinks reparieren</button>';
echo '<div id="fix-result" style="margin-top: 10px;"></div>';

echo '</div>';

// JavaScript für Reparatur-Button
?>
<script>
jQuery(document).ready(function($) {
    $('#quick-fix-btn').on('click', function() {
        const button = $(this);
        const result = $('#fix-result');
        
        button.prop('disabled', true).text('Repariere...');
        result.html('Repariere Permalinks...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'easy_glossary_fix_permalinks',
                nonce: '<?php echo wp_create_nonce( 'easy_glossary_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    result.html('<div style="color: green;">✅ ' + response.data.message + '</div>');
                    if (response.data.test_results) {
                        result.append('<h4>Test-Ergebnisse:</h4>');
                        response.data.test_results.forEach(function(test) {
                            result.append('<div>' + test.title + ': ' + test.status + '</div>');
                        });
                    }
                } else {
                    result.html('<div style="color: red;">❌ Fehler: ' + response.data + '</div>');
                }
            },
            error: function() {
                result.html('<div style="color: red;">❌ Netzwerkfehler</div>');
            },
            complete: function() {
                button.prop('disabled', false).text('Permalinks reparieren');
            }
        });
    });
});
</script>
