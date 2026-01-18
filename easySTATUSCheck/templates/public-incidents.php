<?php
/**
 * Template for Public Incidents/CVE Page
 * 
 * @package Easy_Status_Check
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$cve_feeds = get_option( 'esc_cve_feeds', array() );
$max_items = get_option( 'esc_public_cve_max_items', 10 );
$base_slug = get_option( 'esc_public_status_slug', 'status' );

$public_settings = get_option( 'esc_public_settings', array(
    'primary_color' => '#2271b1',
    'error_color' => '#d63638',
    'background_color' => '#f0f0f1',
    'text_color' => '#1d2327'
) );

/**
 * Fetch CVE feed data
 */
function esc_fetch_cve_feed( $url, $max_items = 10 ) {
    $transient_key = 'esc_cve_feed_' . md5( $url );
    $cached = get_transient( $transient_key );
    
    if ( $cached !== false ) {
        return array_slice( $cached, 0, $max_items );
    }
    
    $response = wp_remote_get( $url, array( 'timeout' => 15 ) );
    
    if ( is_wp_error( $response ) ) {
        return array();
    }
    
    $body = wp_remote_retrieve_body( $response );
    $xml = simplexml_load_string( $body );
    
    if ( ! $xml ) {
        return array();
    }
    
    $items = array();
    
    foreach ( $xml->channel->item as $item ) {
        $items[] = array(
            'title' => (string) $item->title,
            'description' => strip_tags( (string) $item->description ),
            'date' => date_i18n( 'd.m.Y H:i', strtotime( (string) $item->pubDate ) ),
            'link' => (string) $item->link
        );
        
        if ( count( $items ) >= $max_items ) {
            break;
        }
    }
    
    set_transient( $transient_key, $items, HOUR_IN_SECONDS );
    
    return $items;
}

get_header();
?>

<style>
    .esc-public-incidents {
        max-width: 1400px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .esc-public-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .esc-public-header h1 {
        font-size: 36px;
        margin-bottom: 10px;
        color: <?php echo esc_attr( $public_settings['text_color'] ); ?>;
    }
    
    .esc-public-nav {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-bottom: 40px;
        flex-wrap: wrap;
    }
    
    .esc-public-nav a {
        padding: 12px 24px;
        background: #fff;
        border-radius: 6px;
        text-decoration: none;
        color: <?php echo esc_attr( $public_settings['text_color'] ); ?>;
        border: 2px solid transparent;
        transition: all 0.3s;
        font-weight: 500;
    }
    
    .esc-public-nav a.active {
        border-color: <?php echo esc_attr( $public_settings['primary_color'] ); ?>;
        background: <?php echo esc_attr( $public_settings['primary_color'] ); ?>;
        color: #fff;
    }
    
    .esc-public-nav a:hover:not(.active) {
        border-color: <?php echo esc_attr( $public_settings['primary_color'] ); ?>;
    }
    
    .esc-cve-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .esc-cve-feed-section {
        margin-bottom: 40px;
    }
    
    .esc-cve-feed-section h2 {
        margin-bottom: 20px;
        font-size: 24px;
        color: <?php echo esc_attr( $public_settings['text_color'] ); ?>;
    }
    
    .esc-cve-card {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-left: 4px solid <?php echo esc_attr( $public_settings['error_color'] ); ?>;
    }
    
    .esc-cve-card h3 {
        font-size: 18px;
        margin-bottom: 10px;
        color: <?php echo esc_attr( $public_settings['error_color'] ); ?>;
    }
    
    .esc-cve-date {
        font-size: 13px;
        color: #999;
        margin-bottom: 10px;
    }
    
    .esc-cve-description {
        font-size: 14px;
        color: #666;
        line-height: 1.6;
    }
    
    @media (max-width: 768px) {
        .esc-cve-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="esc-public-incidents">
    <div class="esc-public-header">
        <h1><?php _e( 'Security Incidents & CVE Feeds', 'easy-status-check' ); ?></h1>
        <p><?php _e( 'Aktuelle Sicherheitsvorfälle und Schwachstellen', 'easy-status-check' ); ?></p>
    </div>

    <div class="esc-public-nav">
        <a href="<?php echo home_url( '/' . $base_slug . '/services' ); ?>"><?php _e( 'Services', 'easy-status-check' ); ?></a>
        <a href="<?php echo home_url( '/' . $base_slug . '/incidents' ); ?>" class="active"><?php _e( 'Incidents', 'easy-status-check' ); ?></a>
    </div>

    <?php if ( empty( $cve_feeds ) ) : ?>
        <div style="text-align: center; padding: 40px; background: #fff; border-radius: 8px;">
            <p><?php _e( 'Keine CVE Feeds konfiguriert. Bitte konfigurieren Sie CVE Feeds unter Status Check → Incidents → Public Status Page.', 'easy-status-check' ); ?></p>
        </div>
    <?php else : ?>
        <?php foreach ( $cve_feeds as $feed ) : 
            $feed_data = esc_fetch_cve_feed( $feed['url'], $max_items );
        ?>
            <div class="esc-cve-feed-section">
                <h2><?php echo esc_html( $feed['name'] ); ?></h2>
                <?php if ( empty( $feed_data ) ) : ?>
                    <p><?php _e( 'Keine Daten verfügbar.', 'easy-status-check' ); ?></p>
                <?php else : ?>
                    <div class="esc-cve-grid">
                        <?php foreach ( $feed_data as $item ) : ?>
                            <div class="esc-cve-card">
                                <h3><?php echo esc_html( $item['title'] ); ?></h3>
                                <div class="esc-cve-date"><?php echo esc_html( $item['date'] ); ?></div>
                                <div class="esc-cve-description"><?php echo esc_html( wp_trim_words( $item['description'], 30 ) ); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
get_footer();
