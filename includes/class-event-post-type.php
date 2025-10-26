<?php
/**
 * Hanterar Custom Post Type för händelser
 */

if (!defined('ABSPATH')) {
    exit;
}

class CYPL_Event_Post_Type {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('manage_cypl_event_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_cypl_event_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('manage_edit-cypl_event_sortable_columns', array($this, 'set_sortable_columns'));
        add_action('pre_get_posts', array($this, 'handle_custom_column_sorting'));
    }
    
    /**
     * Registrera Custom Post Type
     */
    public static function register_post_type() {
        $labels = array(
            'name' => __('Events', 'circular-year-planner'),
            'singular_name' => __('Event', 'circular-year-planner'),
            'menu_name' => __('Year Planning', 'circular-year-planner'),
            'add_new' => __('Add New', 'circular-year-planner'),
            'add_new_item' => __('Add New Event', 'circular-year-planner'),
            'edit_item' => __('Edit Event', 'circular-year-planner'),
            'new_item' => __('New Event', 'circular-year-planner'),
            'view_item' => __('View Event', 'circular-year-planner'),
            'search_items' => __('Search Events', 'circular-year-planner'),
            'not_found' => __('No events found', 'circular-year-planner'),
            'not_found_in_trash' => __('No events found in trash', 'circular-year-planner'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title', 'editor'),
            'capability_type' => 'post',
            'rewrite' => array('slug' => 'event'),
        );
        
        register_post_type('cypl_event', $args);
    }
    
    /**
     * Lägg till meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'cypl_event_details',
            __('Event Details', 'circular-year-planner'),
            array($this, 'render_event_details_meta_box'),
            'cypl_event',
            'normal',
            'high'
        );
    }
    
    /**
     * Rendera meta box för händelsedetaljer
     */
    public function render_event_details_meta_box($post) {
        wp_nonce_field('cypl_save_event_details', 'cypl_event_details_nonce');
        
        $start_date = get_post_meta($post->ID, '_cypl_start_date', true);
        $end_date = get_post_meta($post->ID, '_cypl_end_date', true);
        $event_type = get_post_meta($post->ID, '_cypl_event_type', true);
        
        $event_types = get_option('cypl_event_types', array());
        
        ?>
        <div class="cyp-meta-box">
            <p>
                <label for="cypl_start_date"><strong><?php esc_html_e('Start Date', 'circular-year-planner'); ?>:</strong></label><br>
                <input type="date" id="cypl_start_date" name="cypl_start_date" value="<?php echo esc_attr($start_date); ?>" class="widefat" required placeholder="yyyy-mm-dd">
                <span class="description"><?php esc_html_e('Format: YYYY-MM-DD', 'circular-year-planner'); ?></span>
            </p>
            
            <p>
                <label for="cypl_end_date"><strong><?php esc_html_e('End Date', 'circular-year-planner'); ?>:</strong></label><br>
                <input type="date" id="cypl_end_date" name="cypl_end_date" value="<?php echo esc_attr($end_date); ?>" class="widefat" required placeholder="yyyy-mm-dd">
                <span class="description"><?php esc_html_e('Format: YYYY-MM-DD (automatically set to start date if left empty)', 'circular-year-planner'); ?></span>
            </p>
            
            <p>
                <label for="cypl_event_type"><strong><?php esc_html_e('Event Type', 'circular-year-planner'); ?>:</strong></label><br>
                <select id="cypl_event_type" name="cypl_event_type" class="widefat">
                    <option value=""><?php esc_html_e('Select event type', 'circular-year-planner'); ?></option>
                    <?php foreach ($event_types as $index => $type) : ?>
                        <option value="<?php echo esc_attr($index); ?>" <?php selected($event_type, $index); ?>>
                            <?php echo esc_html($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            
        </div>
        <?php
    }
    
    /**
     * Spara meta box data
     */
    public function save_meta_boxes($post_id) {
        // Kontrollera nonce
        if (!isset($_POST['cypl_event_details_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cypl_event_details_nonce'])), 'cypl_save_event_details')) {
            return;
        }
        
        // Kontrollera autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Kontrollera behörighet
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Spara fält
        if (isset($_POST['cypl_start_date'])) {
            update_post_meta($post_id, '_cypl_start_date', sanitize_text_field(wp_unslash($_POST['cypl_start_date'])));
        }
        
        if (isset($_POST['cypl_end_date'])) {
            update_post_meta($post_id, '_cypl_end_date', sanitize_text_field(wp_unslash($_POST['cypl_end_date'])));
        }
        
        if (isset($_POST['cypl_event_type'])) {
            update_post_meta($post_id, '_cypl_event_type', sanitize_text_field(wp_unslash($_POST['cypl_event_type'])));
        }
    }
    
    /**
     * Anpassa kolumner i admin-listan
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Event', 'circular-year-planner');
        $new_columns['event_type'] = __('Type', 'circular-year-planner');
        $new_columns['start_date'] = __('Start Date', 'circular-year-planner');
        $new_columns['end_date'] = __('End Date', 'circular-year-planner');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Visa innehåll i anpassade kolumner
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'event_type':
                $event_type_index = get_post_meta($post_id, '_cypl_event_type', true);
                $event_types = get_option('cypl_event_types', array());
                if (isset($event_types[$event_type_index])) {
                    $type = $event_types[$event_type_index];
                    echo '<span style="display: inline-block; width: 12px; height: 12px; background-color: ' . esc_attr($type['color']) . '; border-radius: 50%; margin-right: 5px;"></span>';
                    echo esc_html($type['name']);
                }
                break;
                
            case 'start_date':
                $start_date = get_post_meta($post_id, '_cypl_start_date', true);
                if ($start_date) {
                    // Format according to WordPress date settings using mysql2date
                    echo esc_html(mysql2date(get_option('date_format'), $start_date));
                } else {
                    echo '—';
                }
                break;
                
            case 'end_date':
                $end_date = get_post_meta($post_id, '_cypl_end_date', true);
                if ($end_date) {
                    // Format according to WordPress date settings using mysql2date
                    echo esc_html(mysql2date(get_option('date_format'), $end_date));
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    /**
     * Beräkna verksamhetsår baserat på ett datum
     */
    public function calculate_fiscal_year($date) {
        $fiscal_start = get_option('cypl_fiscal_year_start', '01-01');
        list($start_month, $start_day) = explode('-', $fiscal_start);
        
        $event_date = new DateTime($date);
        $event_year = $event_date->format('Y');
        $fiscal_start_date = new DateTime($event_year . '-' . $fiscal_start);
        
        if ($event_date >= $fiscal_start_date) {
            // Händelsen är i verksamhetsåret som börjar detta kalenderår
            if ($fiscal_start === '01-01') {
                return $event_year;
            } else {
                $next_year = $event_year + 1;
                return $event_year . '/' . $next_year;
            }
        } else {
            // Händelsen är i verksamhetsåret som började föregående kalenderår
            $prev_year = $event_year - 1;
            if ($fiscal_start === '01-01') {
                return $prev_year;
            } else {
                return $prev_year . '/' . $event_year;
            }
        }
    }
    
    /**
     * Hämta verksamhetsår för en händelse (statisk metod för användning utanför klassen)
     */
    public static function get_event_fiscal_year($post_id) {
        $start_date = get_post_meta($post_id, '_cypl_start_date', true);
        if (!$start_date) {
            return '';
        }
        
        $instance = self::get_instance();
        return $instance->calculate_fiscal_year($start_date);
    }
    
    /**
     * Gör kolumner sorterbara
     */
    public function set_sortable_columns($columns) {
        $columns['event_type'] = 'event_type';
        $columns['start_date'] = 'start_date';
        $columns['end_date'] = 'end_date';
        return $columns;
    }
    
    /**
     * Hantera sortering av anpassade kolumner
     */
    public function handle_custom_column_sorting($query) {
        // Kontrollera att vi är i admin och på rätt post type
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ($query->get('post_type') !== 'cypl_event') {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        switch ($orderby) {
            case 'start_date':
                $query->set('meta_key', '_cypl_start_date');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'end_date':
                $query->set('meta_key', '_cypl_end_date');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'event_type':
                $query->set('meta_key', '_cypl_event_type');
                $query->set('orderby', 'meta_value_num');
                break;
        }
    }
}

