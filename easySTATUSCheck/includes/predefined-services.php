<?php
/**
 * Predefined services for easySTATUSCheck
 *
 * @package Easy_Status_Check
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ESC_Predefined_Services {

    /**
     * Get all predefined services grouped by category
     */
    public function get_predefined_services() {
        return array(
            'Cloud Services' => array(
                // Amazon Web Services
                array(
                    'name' => 'AWS All Services Status',
                    'url' => 'https://status.aws.amazon.com/rss/all.rss',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'rss',
                    'check_content' => true
                ),
                
                // Google Cloud
                array(
                    'name' => 'Google Cloud Platform Status',
                    'url' => 'https://status.cloud.google.com/incidents.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'incidents'
                ),
                array(
                    'name' => 'Google Workspace Status',
                    'url' => 'https://www.google.com/appsstatus/json/en',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'services'
                ),
                
                // Microsoft Azure
                array(
                    'name' => 'Microsoft Azure Status',
                    'url' => 'https://azure.status.microsoft/api/v2/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Cloudflare
                array(
                    'name' => 'Cloudflare Status',
                    'url' => 'https://www.cloudflarestatus.com/api/v2/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // GitHub
                array(
                    'name' => 'GitHub Status',
                    'url' => 'https://www.githubstatus.com/api/v2/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // GitLab
                array(
                    'name' => 'GitLab Status',
                    'url' => 'https://status.gitlab.com/api/v2/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Netlify
                array(
                    'name' => 'Netlify Status',
                    'url' => 'https://www.netlifystatus.com/api/v2/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Vercel
                array(
                    'name' => 'Vercel Status',
                    'url' => 'https://www.vercel-status.com/api/v2/status.json',
                    'category' => 'cloud',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
            ),

            'Hosting Anbieter' => array(
                // Hetzner
                array(
                    'name' => 'Hetzner Status',
                    'url' => 'https://status.hetzner.com/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // DigitalOcean
                array(
                    'name' => 'DigitalOcean Status',
                    'url' => 'https://status.digitalocean.com/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.description'
                ),
                
                // OVH
                array(
                    'name' => 'OVH Cloud Status',
                    'url' => 'https://status.ovh.com/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Vultr
                array(
                    'name' => 'Vultr Status',
                    'url' => 'https://status.vultr.com/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Linode
                array(
                    'name' => 'Linode Status',
                    'url' => 'https://status.linode.com/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.description'
                ),
                
                // Hostinger
                array(
                    'name' => 'Hostinger Status',
                    'url' => 'https://statuspage.hostinger.com/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // SiteGround
                array(
                    'name' => 'SiteGround Status',
                    'url' => 'https://status.siteground.com/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Kinsta
                array(
                    'name' => 'Kinsta Status',
                    'url' => 'https://status.kinsta.com/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // WP Engine
                array(
                    'name' => 'WP Engine Status',
                    'url' => 'https://wpengine.statuspage.io/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Bluehost
                array(
                    'name' => 'Bluehost Status',
                    'url' => 'https://www.bluehost.com',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                ),
                
                // GoDaddy
                array(
                    'name' => 'GoDaddy Status',
                    'url' => 'https://status.godaddy.com/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Namecheap
                array(
                    'name' => 'Namecheap Status',
                    'url' => 'https://status.namecheap.com/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Cloudways
                array(
                    'name' => 'Cloudways Status',
                    'url' => 'https://status.cloudways.com/api/v2/status.json',
                    'category' => 'hosting',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
            ),

            'IT Services' => array(
                // Communication Services
                array(
                    'name' => 'Slack Status',
                    'url' => 'https://status.slack.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                array(
                    'name' => 'Discord Status',
                    'url' => 'https://discordstatus.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                array(
                    'name' => 'Zoom Status',
                    'url' => 'https://status.zoom.us/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Payment Services
                array(
                    'name' => 'Stripe Status',
                    'url' => 'https://status.stripe.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                array(
                    'name' => 'PayPal Status',
                    'url' => 'https://www.paypal-status.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // E-Commerce
                array(
                    'name' => 'Shopify Status',
                    'url' => 'https://status.shopify.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Developer Tools
                array(
                    'name' => 'Atlassian Status',
                    'url' => 'https://status.atlassian.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Email Services
                array(
                    'name' => 'SendGrid Status',
                    'url' => 'https://status.sendgrid.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                array(
                    'name' => 'Mailgun Status',
                    'url' => 'https://status.mailgun.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Twilio
                array(
                    'name' => 'Twilio Status',
                    'url' => 'https://status.twilio.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Mailchimp
                array(
                    'name' => 'Mailchimp Status',
                    'url' => 'https://status.mailchimp.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Dropbox
                array(
                    'name' => 'Dropbox Status',
                    'url' => 'https://status.dropbox.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Box
                array(
                    'name' => 'Box Status',
                    'url' => 'https://status.box.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Salesforce
                array(
                    'name' => 'Salesforce Status',
                    'url' => 'https://status.salesforce.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // HubSpot
                array(
                    'name' => 'HubSpot Status',
                    'url' => 'https://status.hubspot.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Zendesk
                array(
                    'name' => 'Zendesk Status',
                    'url' => 'https://status.zendesk.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Intercom
                array(
                    'name' => 'Intercom Status',
                    'url' => 'https://www.intercomstatus.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Trello
                array(
                    'name' => 'Trello Status',
                    'url' => 'https://trello.status.atlassian.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Asana
                array(
                    'name' => 'Asana Status',
                    'url' => 'https://status.asana.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Monday.com
                array(
                    'name' => 'Monday.com Status',
                    'url' => 'https://status.monday.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Notion
                array(
                    'name' => 'Notion Status',
                    'url' => 'https://status.notion.so/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Airtable
                array(
                    'name' => 'Airtable Status',
                    'url' => 'https://status.airtable.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Figma
                array(
                    'name' => 'Figma Status',
                    'url' => 'https://status.figma.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Canva
                array(
                    'name' => 'Canva Status',
                    'url' => 'https://status.canva.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Adobe Creative Cloud
                array(
                    'name' => 'Adobe Creative Cloud Status',
                    'url' => 'https://status.adobe.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Spotify
                array(
                    'name' => 'Spotify Status',
                    'url' => 'https://www.spotify-status.com',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                ),
                
                // Netflix
                array(
                    'name' => 'Netflix Status',
                    'url' => 'https://www.netflix.com',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                ),
                
                // Twitter/X
                array(
                    'name' => 'Twitter/X Status',
                    'url' => 'https://api.twitterstat.us/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // LinkedIn
                array(
                    'name' => 'LinkedIn Status',
                    'url' => 'https://www.linkedin-status.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Meta/Facebook
                array(
                    'name' => 'Meta/Facebook Status',
                    'url' => 'https://metastatus.com/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
                
                // Instagram
                array(
                    'name' => 'Instagram Status',
                    'url' => 'https://www.instagram-status.com',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                ),
                
                // WhatsApp
                array(
                    'name' => 'WhatsApp Status',
                    'url' => 'https://www.whatsapp-status.com',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                ),
                
                // Telegram
                array(
                    'name' => 'Telegram Status',
                    'url' => 'https://www.telegram-status.com',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                ),
                
                // Signal
                array(
                    'name' => 'Signal Status',
                    'url' => 'https://status.signal.org/api/v2/status.json',
                    'category' => 'custom',
                    'method' => 'GET',
                    'expected_code' => '200',
                    'timeout' => 15,
                    'response_type' => 'json',
                    'json_path' => 'status.indicator'
                ),
            )
        );
    }

    /**
     * Create default services during plugin activation
     */
    public function create_default_services() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        
        // Check if any services already exist
        $existing_count = $wpdb->get_var( "SELECT COUNT(*) FROM $services_table" );
        if ( $existing_count > 0 ) {
            return; // Don't add defaults if services already exist
        }

        // Add some essential services by default
        $default_services = array(
            array(
                'name' => 'Google',
                'url' => 'https://www.google.com',
                'category' => 'custom',
                'method' => 'GET',
                'timeout' => 10,
                'expected_code' => '200',
                'check_interval' => 300,
                'enabled' => 1,
                'notify_email' => 1
            ),
            array(
                'name' => 'Cloudflare DNS',
                'url' => 'https://1.1.1.1',
                'category' => 'custom',
                'method' => 'GET',
                'timeout' => 10,
                'expected_code' => '200',
                'check_interval' => 300,
                'enabled' => 1,
                'notify_email' => 1
            ),
            array(
                'name' => 'GitHub Website',
                'url' => 'https://github.com',
                'category' => 'cloud',
                'method' => 'GET',
                'timeout' => 15,
                'expected_code' => '200',
                'check_interval' => 600,
                'enabled' => 0,
                'notify_email' => 1
            )
        );

        foreach ( $default_services as $service ) {
            $wpdb->insert( $services_table, $service );
        }
    }

    /**
     * Bulk add predefined services
     */
    public function add_predefined_services( $services_data ) {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        $added_count = 0;
        
        foreach ( $services_data as $service ) {
            // Handle both array and JSON string
            if ( is_string( $service ) ) {
                $service = json_decode( $service, true );
            }
            
            if ( ! is_array( $service ) || empty( $service['name'] ) || empty( $service['url'] ) ) {
                continue;
            }
            
            // Check if service URL already exists (only check URL, not name)
            $existing = $wpdb->get_var( $wpdb->prepare( 
                "SELECT id FROM $services_table WHERE url = %s",
                $service['url']
            ) );
            
            if ( $existing ) {
                continue; // Skip if URL already exists
            }
            
            $service_data = array(
                'name' => sanitize_text_field( $service['name'] ),
                'url' => esc_url_raw( $service['url'] ),
                'category' => sanitize_text_field( $service['category'] ),
                'method' => sanitize_text_field( $service['method'] ?? 'GET' ),
                'timeout' => intval( $service['timeout'] ?? 10 ),
                'expected_code' => sanitize_text_field( $service['expected_code'] ?? '200' ),
                'check_interval' => 300,
                'enabled' => 1,
                'notify_email' => 1,
                'response_type' => sanitize_text_field( $service['response_type'] ?? null ),
                'json_path' => sanitize_text_field( $service['json_path'] ?? null ),
                'check_content' => intval( $service['check_content'] ?? 0 ),
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' )
            );
            
            $result = $wpdb->insert( $services_table, $service_data );
            if ( $result ) {
                $added_count++;
            }
        }
        
        return $added_count;
    }

    /**
     * Get recommended services based on existing services
     */
    public function get_recommended_services() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        $existing_services = $wpdb->get_results( "SELECT url FROM $services_table" );
        
        $existing_urls = array_column( $existing_services, 'url' );
        
        $all_predefined = $this->get_predefined_services();
        $recommendations = array();
        
        foreach ( $all_predefined as $category => $services ) {
            foreach ( $services as $service ) {
                // Skip if URL already exists
                if ( in_array( $service['url'], $existing_urls ) ) {
                    continue;
                }
                
                // Add to recommendations
                if ( ! isset( $recommendations[ $category ] ) ) {
                    $recommendations[ $category ] = array();
                }
                $recommendations[ $category ][] = $service;
            }
        }
        
        return $recommendations;
    }

    /**
     * Export services configuration
     */
    public function export_services() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'esc_services';
        $services = $wpdb->get_results( "SELECT * FROM $services_table ORDER BY category, name" );
        
        $export_data = array(
            'plugin' => 'easySTATUSCheck',
            'version' => EASY_STATUS_CHECK_VERSION,
            'exported_at' => current_time( 'Y-m-d H:i:s' ),
            'services' => $services
        );
        
        return $export_data;
    }

    /**
     * Import services configuration
     */
    public function import_services( $import_data ) {
        if ( ! is_array( $import_data ) || empty( $import_data['services'] ) ) {
            return false;
        }
        
        global $wpdb;
        $services_table = $wpdb->prefix . 'esc_services';
        $imported_count = 0;
        
        foreach ( $import_data['services'] as $service ) {
            // Skip if required fields are missing
            if ( empty( $service->name ) || empty( $service->url ) ) {
                continue;
            }
            
            // Check if service URL already exists
            $existing = $wpdb->get_var( $wpdb->prepare( 
                "SELECT id FROM $services_table WHERE url = %s",
                $service->url
            ) );
            
            if ( $existing ) {
                continue; // Skip if URL already exists
            }
            
            $service_data = array(
                'name' => sanitize_text_field( $service->name ),
                'url' => esc_url_raw( $service->url ),
                'category' => sanitize_text_field( $service->category ?? 'custom' ),
                'method' => sanitize_text_field( $service->method ?? 'GET' ),
                'timeout' => intval( $service->timeout ?? 10 ),
                'expected_code' => sanitize_text_field( $service->expected_code ?? '200' ),
                'check_interval' => intval( $service->check_interval ?? 300 ),
                'enabled' => intval( $service->enabled ?? 1 ),
                'notify_email' => intval( $service->notify_email ?? 1 ),
                'custom_headers' => $service->custom_headers ?? null,
                'response_type' => sanitize_text_field( $service->response_type ?? null ),
                'json_path' => sanitize_text_field( $service->json_path ?? null ),
                'check_content' => intval( $service->check_content ?? 0 ),
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' )
            );
            
            $result = $wpdb->insert( $services_table, $service_data );
            if ( $result ) {
                $imported_count++;
            }
        }
        
        return $imported_count;
    }
}

// Class will be instantiated by main plugin class
