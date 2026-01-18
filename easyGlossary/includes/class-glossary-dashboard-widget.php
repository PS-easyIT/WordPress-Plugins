<?php
/**
 * Glossary Dashboard Widget
 * 
 * Dashboard-Widget mit umfassenden Statistiken
 * 
 * @package easyGlossary
 * @since 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Easy_Glossary_Dashboard_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
    }
    
    /**
     * Registriere Dashboard-Widget
     */
    public function register_dashboard_widget() {
        wp_add_dashboard_widget(
            'easy_glossary_stats',
            __( 'easyGlossary Statistiken', 'easy-glossary' ),
            array( $this, 'render_dashboard_widget' ),
            null,
            null,
            'normal',
            'high'
        );
    }
    
    /**
     * Rendere Dashboard-Widget
     */
    public function render_dashboard_widget() {
        $stats = $this->get_statistics();
        ?>
        <div class="easy-glossary-dashboard-widget">
            <!-- Haupt-Statistiken -->
            <div class="glossary-stats-grid">
                <div class="stat-box">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-book"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html( $stats['total_terms'] ); ?></div>
                        <div class="stat-label"><?php _e( 'Begriffe gesamt', 'easy-glossary' ); ?></div>
                    </div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-icon published">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html( $stats['published'] ); ?></div>
                        <div class="stat-label"><?php _e( 'Veröffentlicht', 'easy-glossary' ); ?></div>
                    </div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-icon draft">
                        <span class="dashicons dashicons-edit"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html( $stats['drafts'] ); ?></div>
                        <div class="stat-label"><?php _e( 'Entwürfe', 'easy-glossary' ); ?></div>
                    </div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-icon synonyms">
                        <span class="dashicons dashicons-tag"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html( $stats['with_synonyms'] ); ?></div>
                        <div class="stat-label"><?php _e( 'Mit Synonymen', 'easy-glossary' ); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Erweiterte Statistiken -->
            <div class="glossary-stats-details">
                <h4><?php _e( 'Detaillierte Statistiken', 'easy-glossary' ); ?></h4>
                
                <table class="widefat">
                    <tbody>
                        <tr>
                            <td><?php _e( 'Durchschnittliche Wortanzahl', 'easy-glossary' ); ?></td>
                            <td><strong><?php echo esc_html( $stats['avg_word_count'] ); ?></strong></td>
                        </tr>
                        <tr class="alternate">
                            <td><?php _e( 'Begriffe mit externen Links', 'easy-glossary' ); ?></td>
                            <td><strong><?php echo esc_html( $stats['with_external_links'] ); ?></strong></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Begriffe mit verwandten Begriffen', 'easy-glossary' ); ?></td>
                            <td><strong><?php echo esc_html( $stats['with_related'] ); ?></strong></td>
                        </tr>
                        <tr class="alternate">
                            <td><?php _e( 'Gesamtanzahl Synonyme', 'easy-glossary' ); ?></td>
                            <td><strong><?php echo esc_html( $stats['total_synonyms'] ); ?></strong></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Letzte Aktualisierung', 'easy-glossary' ); ?></td>
                            <td><strong><?php echo esc_html( $stats['last_modified'] ); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- A-Z Verteilung -->
            <div class="glossary-stats-az">
                <h4><?php _e( 'Verteilung nach Anfangsbuchstaben', 'easy-glossary' ); ?></h4>
                <div class="az-distribution">
                    <?php foreach ( $stats['az_distribution'] as $letter => $count ) : ?>
                        <?php if ( $count > 0 ) : ?>
                            <div class="az-item" title="<?php echo esc_attr( sprintf( __( '%d Begriffe mit %s', 'easy-glossary' ), $count, $letter ) ); ?>">
                                <span class="az-letter"><?php echo esc_html( $letter ); ?></span>
                                <span class="az-count"><?php echo esc_html( $count ); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Neueste Begriffe -->
            <div class="glossary-stats-recent">
                <h4><?php _e( 'Zuletzt hinzugefügt', 'easy-glossary' ); ?></h4>
                <ul>
                    <?php foreach ( $stats['recent_terms'] as $term ) : ?>
                        <li>
                            <a href="<?php echo esc_url( get_edit_post_link( $term->ID ) ); ?>">
                                <?php echo esc_html( get_the_title( $term->ID ) ); ?>
                            </a>
                            <span class="term-date"><?php echo esc_html( human_time_diff( strtotime( $term->post_date ), current_time( 'timestamp' ) ) ); ?> <?php _e( 'her', 'easy-glossary' ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Quick Actions -->
            <div class="glossary-stats-actions">
                <a href="<?php echo admin_url( 'post-new.php?post_type=easy_glossary' ); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e( 'Neuer Begriff', 'easy-glossary' ); ?>
                </a>
                <a href="<?php echo admin_url( 'edit.php?post_type=easy_glossary' ); ?>" class="button">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php _e( 'Alle Begriffe', 'easy-glossary' ); ?>
                </a>
                <a href="<?php echo admin_url( 'admin.php?page=easy-glossary-settings' ); ?>" class="button">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e( 'Einstellungen', 'easy-glossary' ); ?>
                </a>
            </div>
        </div>
        
        <style>
        .easy-glossary-dashboard-widget {
            margin: -12px -12px 0;
        }
        
        .glossary-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            padding: 15px;
            background: #f6f7f7;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .stat-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #2271b1;
            border-radius: 50%;
            color: #fff;
        }
        
        .stat-icon.published {
            background: #00a32a;
        }
        
        .stat-icon.draft {
            background: #dba617;
        }
        
        .stat-icon.synonyms {
            background: #8c44c0;
        }
        
        .stat-icon .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 600;
            line-height: 1;
            color: #1d2327;
        }
        
        .stat-label {
            font-size: 12px;
            color: #646970;
            margin-top: 4px;
        }
        
        .glossary-stats-details,
        .glossary-stats-az,
        .glossary-stats-recent {
            padding: 15px;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .glossary-stats-details h4,
        .glossary-stats-az h4,
        .glossary-stats-recent h4 {
            margin: 0 0 12px 0;
            font-size: 14px;
            color: #1d2327;
        }
        
        .glossary-stats-details table {
            margin: 0;
        }
        
        .glossary-stats-details td {
            padding: 8px 10px;
        }
        
        .az-distribution {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .az-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 12px;
            background: #f0f0f1;
            border-radius: 4px;
            min-width: 45px;
            cursor: help;
        }
        
        .az-letter {
            font-weight: 600;
            font-size: 16px;
            color: #2271b1;
        }
        
        .az-count {
            font-size: 11px;
            color: #646970;
            margin-top: 2px;
        }
        
        .glossary-stats-recent ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .glossary-stats-recent li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .glossary-stats-recent li:last-child {
            border-bottom: none;
        }
        
        .glossary-stats-recent a {
            text-decoration: none;
            color: #2271b1;
        }
        
        .glossary-stats-recent a:hover {
            color: #135e96;
        }
        
        .term-date {
            font-size: 12px;
            color: #646970;
        }
        
        .glossary-stats-actions {
            padding: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .glossary-stats-actions .button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .glossary-stats-actions .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        </style>
        <?php
    }
    
    /**
     * Hole Statistiken
     */
    private function get_statistics() {
        // Cache-Key
        $cache_key = 'easy_glossary_dashboard_stats';
        $stats = get_transient( $cache_key );
        
        if ( false !== $stats ) {
            return $stats;
        }
        
        // Basis-Statistiken
        $total_terms = wp_count_posts( 'easy_glossary' );
        
        $stats = array(
            'total_terms' => $total_terms->publish + $total_terms->draft + $total_terms->pending,
            'published' => $total_terms->publish,
            'drafts' => $total_terms->draft,
            'pending' => $total_terms->pending,
            'with_synonyms' => 0,
            'with_external_links' => 0,
            'with_related' => 0,
            'total_synonyms' => 0,
            'avg_word_count' => 0,
            'last_modified' => '',
            'az_distribution' => array(),
            'recent_terms' => array()
        );
        
        // Alle Begriffe holen
        $terms = get_posts( array(
            'post_type' => 'easy_glossary',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ) );
        
        $total_words = 0;
        $letters = range( 'A', 'Z' );
        $az_count = array_fill_keys( $letters, 0 );
        
        foreach ( $terms as $term ) {
            // Synonyme
            $synonyms = get_post_meta( $term->ID, '_glossary_synonyms', true );
            if ( ! empty( $synonyms ) ) {
                $stats['with_synonyms']++;
                if ( is_array( $synonyms ) ) {
                    $stats['total_synonyms'] += count( $synonyms );
                }
            }
            
            // Externe Links
            $external_link = get_post_meta( $term->ID, '_glossary_external_link', true );
            if ( ! empty( $external_link ) ) {
                $stats['with_external_links']++;
            }
            
            // Verwandte Begriffe
            $related = get_post_meta( $term->ID, '_glossary_related_terms', true );
            if ( ! empty( $related ) && is_array( $related ) ) {
                $stats['with_related']++;
            }
            
            // Wortanzahl
            $word_count = str_word_count( strip_tags( $term->post_content ) );
            $total_words += $word_count;
            
            // A-Z Verteilung
            $first_letter = strtoupper( mb_substr( $term->post_title, 0, 1 ) );
            if ( isset( $az_count[ $first_letter ] ) ) {
                $az_count[ $first_letter ]++;
            }
        }
        
        // Durchschnittliche Wortanzahl
        if ( count( $terms ) > 0 ) {
            $stats['avg_word_count'] = round( $total_words / count( $terms ) );
        }
        
        // A-Z Verteilung
        $stats['az_distribution'] = $az_count;
        
        // Letzte Aktualisierung
        $last_modified = get_posts( array(
            'post_type' => 'easy_glossary',
            'posts_per_page' => 1,
            'orderby' => 'modified',
            'order' => 'DESC'
        ) );
        
        if ( ! empty( $last_modified ) ) {
            $stats['last_modified'] = human_time_diff( 
                strtotime( $last_modified[0]->post_modified ), 
                current_time( 'timestamp' ) 
            ) . ' ' . __( 'her', 'easy-glossary' );
        }
        
        // Neueste Begriffe
        $stats['recent_terms'] = get_posts( array(
            'post_type' => 'easy_glossary',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ) );
        
        // Cache für 1 Stunde
        set_transient( $cache_key, $stats, HOUR_IN_SECONDS );
        
        return $stats;
    }
}

// Initialize
new Easy_Glossary_Dashboard_Widget();
