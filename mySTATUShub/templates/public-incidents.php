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
    'text_color' => '#1d2327',
    'page_title' => 'Service Status',
    'page_description' => 'Aktuelle Status-Informationen unserer Services',
    'incidents_title' => 'Security Incidents & CVE Feeds',
    'incidents_description' => 'Aktuelle Sicherheitsvorfälle und Schwachstellen aus verschiedenen Quellen'
) );

/**
 * Fetch CVE feed data
 */
function esc_fetch_cve_feed( $url, $max_items = 10 ) {
    $transient_key = 'esc_cve_feed_' . md5( $url . '_' . $max_items );
    $cached = get_transient( $transient_key );
    
    if ( $cached !== false ) {
        return $cached;
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
    :root {
        --esc-primary: <?php echo esc_attr( $public_settings['primary_color'] ); ?>;
        --esc-error: <?php echo esc_attr( $public_settings['error_color'] ); ?>;
        --esc-bg: <?php echo esc_attr( $public_settings['background_color'] ); ?>;
        --esc-text: <?php echo esc_attr( $public_settings['text_color'] ); ?>;
        --esc-border-radius: 12px;
        --esc-shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
        --esc-shadow-md: 0 4px 12px rgba(0,0,0,0.1);
        --esc-shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
    }
    
    body.esc-public-page {
        background: var(--esc-bg);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }
    
    .esc-public-incidents {
        max-width: 1400px;
        margin: 0 auto;
        padding: 60px 24px;
    }
    
    .esc-public-header {
        text-align: center;
        margin-bottom: 48px;
        animation: fadeInDown 0.6s ease-out;
        background: linear-gradient(135deg, #fff 0%, #f9fafb 100%);
        padding: 40px 32px;
        border-radius: var(--esc-border-radius);
        box-shadow: var(--esc-shadow-md);
        border: 1px solid #e5e7eb;
        position: relative;
        overflow: hidden;
    }
    
    .esc-public-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--esc-primary), var(--esc-error));
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .esc-public-header h1 {
        font-size: clamp(28px, 4vw, 40px);
        font-weight: 700;
        margin-bottom: 12px;
        color: var(--esc-text);
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    
    .esc-public-header p {
        font-size: 16px;
        color: #6b7280;
        margin: 0;
        line-height: 1.6;
    }
    
    .esc-public-nav {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-bottom: 48px;
        flex-wrap: wrap;
        animation: fadeIn 0.6s ease-out 0.2s both;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .esc-public-nav a {
        padding: 14px 28px;
        background: #fff;
        border-radius: var(--esc-border-radius);
        text-decoration: none;
        color: var(--esc-text);
        border: 2px solid #e5e7eb;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 600;
        font-size: 15px;
        box-shadow: var(--esc-shadow-sm);
    }
    
    .esc-public-nav a.active {
        border-color: var(--esc-primary);
        background: var(--esc-primary);
        color: #fff;
        box-shadow: var(--esc-shadow-md);
        transform: translateY(-1px);
    }
    
    .esc-public-nav a:hover:not(.active) {
        border-color: var(--esc-primary);
        transform: translateY(-2px);
        box-shadow: var(--esc-shadow-md);
    }
    
    .esc-cve-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        animation: fadeIn 0.6s ease-out 0.4s both;
    }
    
    .esc-cve-feed-section {
        margin-bottom: 48px;
    }
    
    .esc-cve-feed-section h2 {
        margin-bottom: 20px;
        font-size: 24px;
        font-weight: 700;
        color: var(--esc-text);
        display: flex;
        align-items: center;
        gap: 10px;
        letter-spacing: -0.01em;
    }
    
    .esc-cve-feed-section h2::before {
        content: '';
        width: 4px;
        height: 28px;
        background: linear-gradient(to bottom, var(--esc-error), transparent);
        border-radius: 2px;
    }
    
    .esc-cve-card {
        background: #fff;
        padding: 20px;
        border-radius: var(--esc-border-radius);
        box-shadow: var(--esc-shadow-sm);
        border: 1px solid #e5e7eb;
        border-left: 4px solid var(--esc-error);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        text-decoration: none;
        color: inherit;
    }
    
    a.esc-cve-card {
        cursor: pointer;
    }
    
    .esc-cve-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--esc-error);
        transform: scaleY(0);
        transition: transform 0.3s ease;
    }
    
    .esc-cve-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--esc-shadow-lg);
        border-color: var(--esc-error);
    }
    
    a.esc-cve-card:hover h3 {
        color: color-mix(in srgb, var(--esc-error) 85%, black);
    }
    
    .esc-cve-card:hover::before {
        transform: scaleY(1);
    }
    
    .esc-cve-card h3 {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 10px;
        color: var(--esc-error);
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .esc-cve-date {
        font-size: 13px;
        color: #9ca3af;
        margin-bottom: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        background: #f9fafb;
        border-radius: 6px;
        font-weight: 500;
    }
    
    .esc-cve-date::before {
        content: '🕐';
    }
    
    .esc-cve-description {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.6;
        margin-top: 10px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .esc-no-feeds {
        text-align: center;
        padding: 80px 20px;
        background: #fff;
        border-radius: var(--esc-border-radius);
        box-shadow: var(--esc-shadow-sm);
        animation: fadeIn 0.6s ease-out 0.4s both;
    }
    
    .esc-no-feeds h2 {
        font-size: 24px;
        color: var(--esc-text);
        margin-bottom: 12px;
        font-weight: 700;
    }
    
    .esc-no-feeds p {
        color: #6b7280;
        font-size: 16px;
        line-height: 1.6;
    }
    
    .esc-feed-overview {
        background: linear-gradient(135deg, #fff 0%, #f9fafb 100%);
        padding: 20px 28px;
        border-radius: var(--esc-border-radius);
        box-shadow: var(--esc-shadow-sm);
        border: 1px solid #e5e7eb;
        margin-bottom: 32px;
        display: flex;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
        animation: fadeIn 0.6s ease-out 0.3s both;
    }
    
    .esc-feed-info {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
    
    .esc-feed-info-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--esc-primary)15, var(--esc-primary)25);
        color: var(--esc-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    
    .esc-feed-info-text {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    
    .esc-feed-info-label {
        font-size: 12px;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .esc-feed-info-value {
        font-size: 18px;
        font-weight: 700;
        color: var(--esc-text);
        line-height: 1;
    }
    
    @media (max-width: 1200px) {
        .esc-cve-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 900px) {
        .esc-cve-grid {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .esc-public-incidents {
            padding: 40px 16px;
        }
        
        .esc-public-header h1 {
            font-size: 32px;
        }
        
        .esc-public-header p {
            font-size: 16px;
        }
        
        .esc-cve-feed-section h2 {
            font-size: 20px;
        }
        
        .esc-cve-card {
            padding: 16px;
        }
        
        .esc-cve-card h3 {
            font-size: 15px;
        }
        
        .esc-public-nav {
            gap: 8px;
        }
        
        .esc-public-nav a {
            padding: 12px 20px;
            font-size: 14px;
        }
    }
</style>

<div class="esc-public-incidents">
    <div class="esc-public-header">
        <h1><?php echo esc_html( isset( $public_settings['incidents_title'] ) ? $public_settings['incidents_title'] : 'Security Incidents & CVE Feeds' ); ?></h1>
        <p><?php echo esc_html( isset( $public_settings['incidents_description'] ) ? $public_settings['incidents_description'] : 'Aktuelle Sicherheitsvorfälle und Schwachstellen aus verschiedenen Quellen' ); ?></p>
    </div>

    <?php if ( ! empty( $cve_feeds ) ) : ?>
        <div class="esc-feed-overview">
            <div class="esc-feed-info" style="flex: 1;">
                <div class="esc-feed-info-icon">
                    <span>🔖</span>
                </div>
                <div class="esc-feed-info-text">
                    <div class="esc-feed-info-label"><?php _e( 'Aktive Quellen', 'easy-status-check' ); ?></div>
                    <div class="esc-feed-info-value" style="font-size: 14px; font-weight: 600;">
                        <?php 
                        $feed_names = array_map( function($feed) { return $feed['name']; }, $cve_feeds );
                        echo esc_html( implode( ', ', $feed_names ) );
                        ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ( empty( $cve_feeds ) ) : ?>
        <div class="esc-no-feeds">
            <h2><?php _e( 'Keine CVE Feeds konfiguriert', 'easy-status-check' ); ?></h2>
            <p><?php _e( 'Bitte konfigurieren Sie CVE Feeds unter Status Check → Incidents.', 'easy-status-check' ); ?></p>
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
                            <a href="<?php echo esc_url( $item['link'] ); ?>" class="esc-cve-card" target="_blank" rel="noopener noreferrer">
                                <h3><?php echo esc_html( $item['title'] ); ?></h3>
                                <div class="esc-cve-date"><?php echo esc_html( $item['date'] ); ?></div>
                                <div class="esc-cve-description"><?php echo esc_html( wp_trim_words( $item['description'], 30 ) ); ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Copyright Box -->
<div style="background: linear-gradient(135deg, #f9fafb 0%, #fff 100%); border-top: 1px solid #e5e7eb; padding: 20px; text-align: center; margin-top: 60px;">
    <p style="margin: 0; color: #6b7280; font-size: 14px;">
        Powered by <strong style="color: #2271b1;">mySTATUShub</strong> © <?php echo date('Y'); ?> 
        <a href="https://phinit.de" target="_blank" rel="noopener noreferrer" style="color: #2271b1; text-decoration: none; font-weight: 600;">PHiNiT.de</a>
    </p>
</div>

<?php
get_footer();
