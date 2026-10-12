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
        add_filter('manage_cypl_event_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_cypl_event_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('manage_edit-cypl_event_sortable_columns', array($this, 'set_sortable_columns'));
        add_action('pre_get_posts', array($this, 'handle_custom_column_sorting'));
        add_action('restrict_manage_posts', array($this, 'render_event_type_filter'));
        add_action('pre_get_posts', array($this, 'handle_event_type_filter'));
        add_filter('disable_months_dropdown', array($this, 'disable_core_months_dropdown'), 10, 2);
        add_action('restrict_manage_posts', array($this, 'render_period_filter'), 5);
        add_action('pre_get_posts', array($this, 'handle_period_filter'));
    }
    
    /**
     * Månader (YYYY-MM) per händelse, cachad per anrop
     *
     * @var array|null
     */
    private $event_months = null;
    
    /**
     * WordPress inbyggda datumfilter utgår från publiceringsdatum, inte händelsens datum
     */
    public function disable_core_months_dropdown($disable, $post_type) {
        return 'cypl_event' === $post_type ? true : $disable;
    }
    
    /**
     * Hämta valt period-filter (YYYY eller YYYY-MM) från URL:en, eller null om inget är valt
     */
    private function get_period_filter() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filtrering i listvy, ändrar inget.
        $period = isset($_GET['cypl_period']) ? sanitize_text_field(wp_unslash($_GET['cypl_period'])) : '';
        
        return preg_match('/^\d{4}(-(0[1-9]|1[0-2]))?$/', $period) ? $period : null;
    }
    
    /**
     * Månader (YYYY-MM) då varje händelse pågår, indexerat på post-ID
     *
     * Återkommande serier slutar aldrig, så deras förekomster räknas fram till
     * slutet av nästa verksamhetsår.
     */
    private function get_event_months() {
        if (null !== $this->event_months) {
            return $this->event_months;
        }
        
        $this->event_months = array();
        $current = CYPL_Recurrence::get_current_fiscal_year_range();
        $horizon = $current[1]->modify('+1 year');
        
        $post_ids = get_posts(array(
            'post_type' => 'cypl_event',
            'post_status' => array('publish', 'pending', 'draft', 'future', 'private', 'trash'),
            'numberposts' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ));
        
        foreach ($post_ids as $post_id) {
            $start = CYPL_Recurrence::parse_date(get_post_meta($post_id, '_cypl_start_date', true));
            if (!$start) {
                continue;
            }
            
            $end = CYPL_Recurrence::parse_date(get_post_meta($post_id, '_cypl_end_date', true));
            $duration = ($end && $end > $start) ? CYPL_Recurrence::days_between($start, $end) : 0;
            
            if (CYPL_Recurrence::get_rule($post_id)) {
                $occurrences = CYPL_Recurrence::get_occurrence_dates($post_id, $start, $horizon);
            } else {
                $occurrences = array($start->format('Y-m-d'));
            }
            
            $months = array();
            foreach ($occurrences as $occurrence) {
                $month = CYPL_Recurrence::parse_date($occurrence)->modify('first day of this month');
                $last = CYPL_Recurrence::parse_date($occurrence)->modify('+' . $duration . ' days');
                while ($month <= $last) {
                    $months[$month->format('Y-m')] = true;
                    $month = $month->modify('+1 month');
                }
            }
            
            $this->event_months[$post_id] = array_keys($months);
        }
        
        return $this->event_months;
    }
    
    /**
     * Visa filter för år och månad ovanför listan
     */
    public function render_period_filter($post_type) {
        global $wp_locale;
        
        if ('cypl_event' !== $post_type) {
            return;
        }
        
        $all_months = array();
        foreach ($this->get_event_months() as $months) {
            $all_months = array_merge($all_months, $months);
        }
        $all_months = array_unique($all_months);
        sort($all_months);
        
        if (empty($all_months)) {
            return;
        }
        
        $years = array();
        foreach ($all_months as $month) {
            $years[substr($month, 0, 4)][] = $month;
        }
        
        $selected = $this->get_period_filter();
        ?>
        <label for="cypl-filter-period" class="screen-reader-text"><?php esc_html_e('Filter by date', 'circular-year-planner'); ?></label>
        <select name="cypl_period" id="cypl-filter-period">
            <option value=""><?php esc_html_e('All dates', 'circular-year-planner'); ?></option>
            <?php foreach ($years as $year => $months) : ?>
                <option value="<?php echo esc_attr($year); ?>" <?php selected($selected, (string) $year); ?>><?php echo esc_html($year); ?></option>
                <?php foreach ($months as $month) : ?>
                    <option value="<?php echo esc_attr($month); ?>" <?php selected($selected, $month); ?>>
                        <?php
                        /* translators: 1: Month name, 2: Year */
                        echo '&nbsp;&nbsp;&nbsp;' . esc_html(sprintf(__('%1$s %2$s', 'circular-year-planner'), $wp_locale->get_month(substr($month, 5, 2)), $year));
                        ?>
                    </option>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Filtrera adminlistan på år eller månad då händelsen pågår
     */
    public function handle_period_filter($query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'cypl_event') {
            return;
        }
        
        $period = $this->get_period_filter();
        if (null === $period) {
            return;
        }
        
        $matching = array();
        foreach ($this->get_event_months() as $post_id => $months) {
            foreach ($months as $month) {
                if (0 === strpos($month, $period)) {
                    $matching[] = $post_id;
                    break;
                }
            }
        }
        
        $post_in = $query->get('post__in');
        if (!empty($post_in)) {
            $matching = array_intersect($matching, array_map('intval', $post_in));
        }
        
        $query->set('post__in', !empty($matching) ? array_values($matching) : array(0));
    }
    
    /**
     * Hämta valt händelsetypsfilter från URL:en, eller null om inget är valt
     */
    private function get_event_type_filter() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filtrering i listvy, ändrar inget.
        if (!isset($_GET['cypl_event_type']) || '' === $_GET['cypl_event_type']) {
            return null;
        }
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filtrering i listvy, ändrar inget.
        return absint(wp_unslash($_GET['cypl_event_type']));
    }
    
    /**
     * Visa filter för händelsetyp ovanför listan
     */
    public function render_event_type_filter($post_type) {
        if ('cypl_event' !== $post_type) {
            return;
        }
        
        $event_types = get_option('cypl_event_types', array());
        if (empty($event_types)) {
            return;
        }
        
        $selected = $this->get_event_type_filter();
        ?>
        <label for="cypl-filter-event-type" class="screen-reader-text"><?php esc_html_e('Filter by event type', 'circular-year-planner'); ?></label>
        <select name="cypl_event_type" id="cypl-filter-event-type">
            <option value=""><?php esc_html_e('All event types', 'circular-year-planner'); ?></option>
            <?php foreach ($event_types as $index => $type) : ?>
                <option value="<?php echo esc_attr($index); ?>" <?php selected($selected, (int) $index); ?>><?php echo esc_html($type['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Filtrera adminlistan på händelsetyp
     */
    public function handle_event_type_filter($query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'cypl_event') {
            return;
        }
        
        $selected = $this->get_event_type_filter();
        if (null === $selected) {
            return;
        }
        
        $meta_query = (array) $query->get('meta_query');
        $meta_query[] = array(
            'key' => '_cypl_event_type',
            'value' => (string) $selected,
        );
        $query->set('meta_query', $meta_query);
    }
    
    /**
     * Registrera Custom Post Type
     */
    public static function register_post_type() {
        $labels = array(
            'name' => __('Events', 'circular-year-planner'),
            'singular_name' => __('Event', 'circular-year-planner'),
            'menu_name' => __('Planly', 'circular-year-planner'),
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
            'public' => false,
            'has_archive' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title'),
            'capability_type' => 'post',
            'rewrite' => false,
            'query_var' => false,
        );
        
        register_post_type('cypl_event', $args);
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
        $new_columns['recurrence'] = __('Repeats', 'circular-year-planner');
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
                
            case 'recurrence':
                $description = CYPL_Recurrence::describe(CYPL_Recurrence::get_rule($post_id));
                echo $description ? esc_html($description) : '—';
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

