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
        
        register_rest_route('cypl/v1', '/occurrences', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array($this, 'delete_occurrence'),
            'permission_callback' => array($this, 'can_edit_occurrence'),
            'args' => array(
                'occurrence_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'pattern' => '^\d+:\d{4}-\d{2}-\d{2}$',
                ),
                'mode' => array(
                    'type' => 'string',
                    'enum' => array('single', 'future'),
                    'default' => 'single',
                ),
            ),
        ));
    }
    
    /**
     * Hämta händelser, med återkommande händelser expanderade till förekomster
     */
    public function get_events($request) {
        $fiscal_year = $request->get_param('fiscal_year');
        $event_types = $request->get_param('types');
        
        $range = $fiscal_year ? CYPL_Recurrence::get_fiscal_year_range($fiscal_year) : null;
        $recurrence_range = $range ? $range : CYPL_Recurrence::get_current_fiscal_year_range();
        $event_types_data = get_option('cypl_event_types', array());
        $post_type = CYPL_Event_Post_Type::get_instance();
        
        $query = new WP_Query(array(
            'post_type' => 'cypl_event',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'no_found_rows' => true,
        ));
        
        $events = array();
        
        foreach ($query->posts as $post) {
            $post_id = $post->ID;
            $event_type_index = get_post_meta($post_id, '_cypl_event_type', true);
            
            // Filtrera på händelsetyper om angivet
            if ($event_types && !in_array($event_type_index, explode(',', $event_types))) {
                continue;
            }
            
            $start_date = get_post_meta($post_id, '_cypl_start_date', true);
            $end_date = get_post_meta($post_id, '_cypl_end_date', true);
            $rule = CYPL_Recurrence::get_rule($post_id);
            
            if ($rule) {
                $dates = CYPL_Recurrence::get_occurrence_dates($post_id, $recurrence_range[0], $recurrence_range[1]);
            } else {
                // Filtrera på verksamhetsår om angivet
                if ($fiscal_year && CYPL_Event_Post_Type::get_event_fiscal_year($post_id) !== $fiscal_year) {
                    continue;
                }
                $dates = array($start_date);
            }
            
            $start = CYPL_Recurrence::parse_date($start_date);
            $end = CYPL_Recurrence::parse_date($end_date);
            $duration = ($start && $end) ? max(0, CYPL_Recurrence::days_between($start, $end)) : 0;
            
            $event_type_data = isset($event_types_data[$event_type_index]) ? $event_types_data[$event_type_index] : array('name' => '', 'color' => '#cccccc', 'text_color' => '');
            $title = html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8');
            $recurrence_text = CYPL_Recurrence::describe($rule);
            
            foreach ($dates as $date) {
                $occurrence_start = CYPL_Recurrence::parse_date($date);
                $occurrence_end = $occurrence_start
                    ? $occurrence_start->modify('+' . $duration . ' days')->format('Y-m-d')
                    : $end_date;
                
                $events[] = array(
                    'id' => $post_id,
                    'occurrence_id' => $post_id . ':' . $date,
                    'occurrence_date' => $date,
                    'title' => $title,
                    'start_date' => $date,
                    'end_date' => $occurrence_end,
                    'is_recurring' => (bool) $rule,
                    'recurrence_text' => $recurrence_text,
                    'event_type' => $event_type_index,
                    'event_type_name' => $event_type_data['name'],
                    'event_type_color' => $event_type_data['color'],
                    'event_type_text_color' => !empty($event_type_data['text_color']) ? $event_type_data['text_color'] : '',
                    'fiscal_year' => $date ? $post_type->calculate_fiscal_year($date) : '',
                );
            }
        }
        
        return rest_ensure_response($events);
    }
    
    /**
     * Dela upp "123:2026-03-15" i händelse-ID och datum
     */
    private function parse_occurrence_id($occurrence_id) {
        if (!is_string($occurrence_id) || !preg_match('/^(\d+):(\d{4}-\d{2}-\d{2})$/', $occurrence_id, $matches)) {
            return null;
        }
        
        $post_id = (int) $matches[1];
        if ('cypl_event' !== get_post_type($post_id) || !CYPL_Recurrence::parse_date($matches[2])) {
            return null;
        }
        
        return array($post_id, $matches[2]);
    }
    
    public function can_edit_occurrence($request) {
        $parsed = $this->parse_occurrence_id($request->get_param('occurrence_id'));
        return $parsed && current_user_can('edit_post', $parsed[0]);
    }
    
    /**
     * Radera en förekomst, alla framtida förekomster eller en enskild händelse
     */
    public function delete_occurrence($request) {
        list($post_id, $date) = $this->parse_occurrence_id($request->get_param('occurrence_id'));
        
        $result = CYPL_Recurrence::delete_occurrence($post_id, $date, $request->get_param('mode'));
        if (is_wp_error($result)) {
            return $result;
        }
        
        if ('empty' === $result) {
            if (!current_user_can('delete_post', $post_id)) {
                return new WP_Error('cypl_cannot_delete', __('Sorry, you are not allowed to delete this event.', 'circular-year-planner'), array('status' => 403));
            }
            if (!wp_trash_post($post_id)) {
                return new WP_Error('cypl_delete_failed', __('The event could not be deleted.', 'circular-year-planner'), array('status' => 500));
            }
            $result = 'trashed';
        }
        
        return rest_ensure_response(array('result' => $result));
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
