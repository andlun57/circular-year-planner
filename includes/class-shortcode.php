<?php
/**
 * Hanterar shortcode för att visa cirkulär kalender
 */

if (!defined('ABSPATH')) {
    exit;
}

class CYP_Shortcode {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode('circular_year_planner', array($this, 'render_shortcode'));
    }
    
    /**
     * Rendera shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'year' => '',
            'types' => '',
            'width' => '800',
            'height' => '800',
        ), $atts);
        
        $fiscal_year = !empty($atts['year']) ? $atts['year'] : $this->get_current_fiscal_year();
        $event_types = !empty($atts['types']) ? $atts['types'] : '';
        $width = intval($atts['width']);
        $height = intval($atts['height']);
        
        // Förkorta verksamhetsår för visning
        $display_year = $fiscal_year;
        if (strpos($fiscal_year, '/') !== false) {
            $years = explode('/', $fiscal_year);
            $display_year = substr($years[0], -2) . '/' . substr($years[1], -2);
        }
        
        ob_start();
        ?>
        <div class="cyp-calendar-container" 
             data-fiscal-year="<?php echo esc_attr($fiscal_year); ?>"
             data-event-types="<?php echo esc_attr($event_types); ?>"
             data-width="<?php echo esc_attr($width); ?>"
             data-height="<?php echo esc_attr($height); ?>">
            <div class="cyp-calendar-controls">
                <button class="cyp-prev-year" aria-label="Föregående år">‹</button>
                <h3 class="cyp-year-title"><?php echo esc_html($display_year); ?></h3>
                <button class="cyp-next-year" aria-label="Nästa år">›</button>
            </div>
            <div class="cyp-calendar-legend"></div>
            <div class="cyp-calendar-wrapper">
                <svg id="cyp-circular-calendar" width="<?php echo esc_attr($width); ?>" height="<?php echo esc_attr($height); ?>"></svg>
            </div>
            <div class="cyp-event-details" style="display: none;">
                <button class="cyp-close-details" aria-label="Stäng">×</button>
                <div class="cyp-event-content"></div>
            </div>
            <div class="cyp-loading">Laddar...</div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Beräkna aktuellt verksamhetsår
     */
    private function get_current_fiscal_year() {
        $fiscal_start = get_option('cyp_fiscal_year_start', '01-01');
        list($start_month, $start_day) = explode('-', $fiscal_start);
        
        $current_date = new DateTime();
        $current_year = $current_date->format('Y');
        $fiscal_start_date = new DateTime($current_year . '-' . $fiscal_start);
        
        if ($current_date >= $fiscal_start_date) {
            // Vi är i verksamhetsåret som börjar detta kalenderår
            if ($fiscal_start === '01-01') {
                return $current_year;
            } else {
                $next_year = $current_year + 1;
                return $current_year . '/' . $next_year;
            }
        } else {
            // Vi är i verksamhetsåret som började föregående kalenderår
            $prev_year = $current_year - 1;
            if ($fiscal_start === '01-01') {
                return $prev_year;
            } else {
                return $prev_year . '/' . $current_year;
            }
        }
    }
}

