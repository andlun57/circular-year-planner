<?php
/**
 * Plugin Name: Planly
 * Plugin URI: https://github.com/andlun57/year-planning
 * Description: En cirkulär årsplanerare för att visualisera verksamhetsår och händelser
 * Version: 1.0.22
 * Author: Anders Lundkvist
 * Author URI: https://github.com/andlun57
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: circular-year-planner
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Förhindra direkt åtkomst
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-konstanter
define('CYPL_VERSION', '1.0.22');
define('CYPL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CYPL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CYPL_PLUGIN_FILE', __FILE__);

/**
 * Huvudklass för Circular Year Planner
 */
class Circular_Year_Planner {
    
    /**
     * Singleton-instans
     */
    private static $instance = null;
    
    /**
     * Hämta singleton-instans
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Konstruktor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Ladda beroenden
     */
    private function load_dependencies() {
        require_once CYPL_PLUGIN_DIR . 'includes/class-event-post-type.php';
        require_once CYPL_PLUGIN_DIR . 'includes/class-settings.php';
        require_once CYPL_PLUGIN_DIR . 'includes/class-rest-api.php';
        require_once CYPL_PLUGIN_DIR . 'includes/class-shortcode.php';
    }
    
    /**
     * Initiera hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Aktivering och avaktivering
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialisera plugin
     */
    public function init() {
        // Initiera klasser
        CYPL_Event_Post_Type::get_instance();
        CYPL_Settings::get_instance();
        CYPL_REST_API::get_instance();
        CYPL_Shortcode::get_instance();
    }
    
    /**
     * Ladda frontend-resurser
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'cypl-frontend',
            CYPL_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            CYPL_VERSION
        );
        
        wp_enqueue_script(
            'cypl-circular-calendar',
            CYPL_PLUGIN_URL . 'assets/js/circular-calendar.js',
            array('jquery'),
            CYPL_VERSION,
            true
        );
        
        // Skicka data till JavaScript
        wp_localize_script('cypl-circular-calendar', 'cyplData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('cypl/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'monthNames' => array(
                __('Jan', 'circular-year-planner'),
                __('Feb', 'circular-year-planner'),
                __('Mar', 'circular-year-planner'),
                __('Apr', 'circular-year-planner'),
                __('May', 'circular-year-planner'),
                __('Jun', 'circular-year-planner'),
                __('Jul', 'circular-year-planner'),
                __('Aug', 'circular-year-planner'),
                __('Sep', 'circular-year-planner'),
                __('Oct', 'circular-year-planner'),
                __('Nov', 'circular-year-planner'),
                __('Dec', 'circular-year-planner'),
            ),
            'monthNamesFull' => array(
                __('January', 'circular-year-planner'),
                __('February', 'circular-year-planner'),
                __('March', 'circular-year-planner'),
                __('April', 'circular-year-planner'),
                __('May', 'circular-year-planner'),
                __('June', 'circular-year-planner'),
                __('July', 'circular-year-planner'),
                __('August', 'circular-year-planner'),
                __('September', 'circular-year-planner'),
                __('October', 'circular-year-planner'),
                __('November', 'circular-year-planner'),
                __('December', 'circular-year-planner'),
            ),
            'i18n' => array(
                'date' => __('Date', 'circular-year-planner'),
                'startDate' => __('Start Date', 'circular-year-planner'),
                'endDate' => __('End Date', 'circular-year-planner'),
                'type' => __('Type', 'circular-year-planner'),
                'fiscalYear' => __('Fiscal Year', 'circular-year-planner'),
                'display' => __('Display', 'circular-year-planner'),
                'wholeWeek' => __('Whole week (for visibility)', 'circular-year-planner'),
                'description' => __('Description', 'circular-year-planner'),
            ),
            'dateFormat' => get_option('date_format'),
        ));
    }
    
    /**
     * Ladda admin-resurser
     */
    public function enqueue_admin_assets($hook) {
        // Ladda endast på relevanta sidor
        if ('post.php' === $hook || 'post-new.php' === $hook || strpos($hook, 'cypl-settings') !== false) {
            wp_enqueue_style('wp-color-picker');
            
            wp_enqueue_style(
                'cypl-admin',
                CYPL_PLUGIN_URL . 'assets/css/admin.css',
                array('wp-color-picker'),
                CYPL_VERSION
            );
            
            wp_enqueue_script(
                'cypl-admin',
                CYPL_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'wp-color-picker'),
                CYPL_VERSION,
                true
            );
            
            // Skicka översättningar till JavaScript
            wp_localize_script('cypl-admin', 'cyplAdmin', array(
                'i18n' => array(
                    'eventTypeName' => __('Event type name', 'circular-year-planner'),
                    'background' => __('Background', 'circular-year-planner'),
                    'text' => __('Text', 'circular-year-planner'),
                    'auto' => __('Auto', 'circular-year-planner'),
                    'remove' => __('Remove', 'circular-year-planner'),
                ),
            ));
        }
    }
    
    /**
     * Aktivera plugin
     */
    public function activate() {
        // Registrera post type
        CYPL_Event_Post_Type::register_post_type();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Sätt standardinställningar om de inte finns
        if (!get_option('cypl_event_types')) {
            update_option('cypl_event_types', array(
                array('name' => __('Program', 'circular-year-planner'), 'color' => '#4A90E2'),
                array('name' => __('Campaign', 'circular-year-planner'), 'color' => '#E24A90'),
                array('name' => __('Training', 'circular-year-planner'), 'color' => '#90E24A'),
                array('name' => __('Meeting', 'circular-year-planner'), 'color' => '#E2904A'),
            ));
        }
        
        if (!get_option('cypl_fiscal_year_start')) {
            update_option('cypl_fiscal_year_start', '01-01'); // Månad-Dag
        }
    }
    
    /**
     * Avaktivera plugin
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Initiera plugin
function cypl_init() {
    return Circular_Year_Planner::get_instance();
}

// Starta plugin
cypl_init();
