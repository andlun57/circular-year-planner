<?php
/**
 * REST API endpoints för Circular Year Planner
 */

if (!defined('ABSPATH')) {
    exit;
}

class CYPL_REST_API {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Registrera REST API routes
     */
    public function register_routes() {
        register_rest_route('cypl/v1', '/events', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_events'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route('cypl/v1', '/settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_settings'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Hämta händelser
     */
    public function get_events($request) {
        $fiscal_year = $request->get_param('fiscal_year');
        $event_types = $request->get_param('types');
        
        $args = array(
            'post_type' => 'cypl_event',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        $query = new WP_Query($args);
        $events = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $event_type_index = get_post_meta($post_id, '_cypl_event_type', true);
                
                // Filtrera på händelsetyper om angivet
                if ($event_types && !in_array($event_type_index, explode(',', $event_types))) {
                    continue;
                }
                
                // Beräkna verksamhetsår från startdatum
                $calculated_fiscal_year = CYPL_Event_Post_Type::get_event_fiscal_year($post_id);
                
                // Filtrera på verksamhetsår om angivet
                if ($fiscal_year && $calculated_fiscal_year !== $fiscal_year) {
                    continue;
                }
                
                $event_types_data = get_option('cypl_event_types', array());
                $event_type_data = isset($event_types_data[$event_type_index]) ? $event_types_data[$event_type_index] : array('name' => '', 'color' => '#cccccc', 'text_color' => '');
                
                $events[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'description' => get_the_content(),
                    'start_date' => get_post_meta($post_id, '_cypl_start_date', true),
                    'end_date' => get_post_meta($post_id, '_cypl_end_date', true),
                    'event_type' => $event_type_index,
                    'event_type_name' => $event_type_data['name'],
                    'event_type_color' => $event_type_data['color'],
                    'event_type_text_color' => !empty($event_type_data['text_color']) ? $event_type_data['text_color'] : '',
                    'fiscal_year' => $calculated_fiscal_year,
                );
            }
            wp_reset_postdata();
        }
        
        return rest_ensure_response($events);
    }
    
    /**
     * Hämta inställningar
     */
    public function get_settings($request) {
        $settings = array(
            'event_types' => get_option('cypl_event_types', array()),
            'fiscal_year_start' => get_option('cypl_fiscal_year_start', '01-01'),
            'color_scheme' => get_option('cypl_color_scheme', 'default'),
        );
        
        return rest_ensure_response($settings);
    }
}

