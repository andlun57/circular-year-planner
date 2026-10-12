<?php
/**
 * Hanterar plugin-inställningar
 */

if (!defined('ABSPATH')) {
    exit;
}

class CYPL_Settings {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_cypl_save_settings', array($this, 'save_settings'));
    }
    
    /**
     * Lägg till inställningssida
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=cypl_event',
            __('Planly Settings', 'circular-year-planner'),
            __('Settings', 'circular-year-planner'),
            'manage_options',
            'cypl-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Registrera inställningar
     */
    public function register_settings() {
        register_setting('cypl_settings', 'cypl_event_types', array(
            'sanitize_callback' => array($this, 'sanitize_event_types'),
        ));
        register_setting('cypl_settings', 'cypl_fiscal_year_start', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('cypl_settings', 'cypl_color_scheme', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));
    }
    
    /**
     * Sanitera händelsetyper
     */
    public function sanitize_event_types($value) {
        if (!is_array($value)) {
            return array();
        }
        
        // Nyckeln är typens id och får aldrig numreras om; händelser och kortkoder pekar på den
        $sanitized = array();
        foreach ($value as $id => $type) {
            if (is_int($id) && $id >= 0 && is_array($type) && !empty($type['name'])) {
                $sanitized[$id] = self::sanitize_event_type($type);
            }
        }
        return $sanitized;
    }
    
    private static function sanitize_event_type($type) {
        return array(
            'name' => sanitize_text_field($type['name']),
            'color' => isset($type['color']) ? (string) sanitize_hex_color($type['color']) : '',
            'text_color' => !empty($type['text_color']) ? (string) sanitize_hex_color($type['text_color']) : '',
        );
    }
    
    /**
     * Antal händelser per typ-id, utom händelser i papperskorgen
     */
    public static function count_events_by_type($event_types) {
        $counts = array();
        foreach (array_keys($event_types) as $id) {
            $query = new WP_Query(array(
                'post_type' => 'cypl_event',
                'post_status' => array('publish', 'pending', 'draft', 'future', 'private'),
                'posts_per_page' => 1,
                'fields' => 'ids',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Körs bara på inställningssidan.
                'meta_query' => array(
                    array(
                        'key' => '_cypl_event_type',
                        'value' => (string) $id,
                    ),
                ),
            ));
            $counts[$id] = (int) $query->found_posts;
        }
        return $counts;
    }
    
    /**
     * Nästa lediga id för en ny händelsetyp; borttagna id:n återanvänds aldrig
     */
    private static function next_event_type_id($event_types) {
        $next = (int) get_option('cypl_event_types_next_id', 0);
        if (!empty($event_types)) {
            $next = max($next, max(array_keys($event_types)) + 1);
        }
        return $next;
    }
    
    /**
     * Rendera inställningssida
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Hantera meddelanden
        $message = '';
        if (isset($_GET['message']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'cypl_settings_saved')) {
                $message = sanitize_text_field(wp_unslash($_GET['message']));
            }
        }
        
        $event_types = get_option('cypl_event_types', array());
        $event_counts = self::count_events_by_type($event_types);
        $fiscal_year_start = get_option('cypl_fiscal_year_start', '01-01');
        $color_scheme = get_option('cypl_color_scheme', 'default');
        
        $kept_in_use = array();
        if ('saved' === $message) {
            $kept_in_use = (array) get_transient('cypl_types_kept_' . get_current_user_id());
            delete_transient('cypl_types_kept_' . get_current_user_id());
            $kept_in_use = array_filter($kept_in_use);
        }
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Planly Settings', 'circular-year-planner'); ?></h1>
            
            <?php if ($message === 'saved') : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Settings saved successfully', 'circular-year-planner'); ?>!</p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($kept_in_use)) : ?>
                <div class="notice notice-error is-dismissible">
                    <p>
                        <?php
                        echo esc_html(sprintf(
                            /* translators: %s: Comma-separated list of event type names */
                            __('These event types were not removed because events use them: %s. Delete all events of a type before removing it.', 'circular-year-planner'),
                            implode(', ', $kept_in_use)
                        ));
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="cypl-settings-form">
                <input type="hidden" name="action" value="cypl_save_settings">
                <?php wp_nonce_field('cypl_save_settings', 'cypl_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e('Event Types', 'circular-year-planner'); ?></label>
                        </th>
                        <td>
                            <div id="cyp-event-types">
                                <?php if (empty($event_types)) : ?>
                                    <div class="cyp-event-type-row">
                                        <div class="cyp-type-index">
                                            <span class="cyp-color-label"><?php esc_html_e('ID', 'circular-year-planner'); ?></span>
                                            <span class="cyp-type-index-value">–</span>
                                        </div>
                                        <div class="regular-text-container">
                                            <label class="cyp-color-label"><?php esc_html_e('Name', 'circular-year-planner'); ?></label>
                                            <input type="text" name="cypl_event_types[new-0][name]" placeholder="<?php esc_attr_e('Event type name', 'circular-year-planner'); ?>" class="regular-text">
                                        </div>
                                        <div class="cyp-color-picker-group">
                                            <label class="cyp-color-label"><?php esc_html_e('Background', 'circular-year-planner'); ?></label>
                                            <input type="text" name="cypl_event_types[new-0][color]" value="#4A90E2" class="cyp-color-picker">
                                        </div>
                                        <div class="cyp-color-picker-group">
                                            <label class="cyp-color-label"><?php esc_html_e('Text', 'circular-year-planner'); ?></label>
                                            <input type="text" name="cypl_event_types[new-0][text_color]" value="" placeholder="<?php esc_attr_e('Auto', 'circular-year-planner'); ?>" class="cyp-color-picker cyp-text-color-picker">
                                        </div>
                                        <button type="button" class="button cyp-remove-type"><?php esc_html_e('Remove', 'circular-year-planner'); ?></button>
                                    </div>
                                <?php else : ?>
                                    <?php foreach ($event_types as $index => $type) : ?>
                                        <?php $event_count = isset($event_counts[$index]) ? $event_counts[$index] : 0; ?>
                                        <div class="cyp-event-type-row" data-event-count="<?php echo esc_attr($event_count); ?>">
                                            <div class="cyp-type-index">
                                                <span class="cyp-color-label"><?php esc_html_e('ID', 'circular-year-planner'); ?></span>
                                                <span class="cyp-type-index-value"><?php echo esc_html($index); ?></span>
                                            </div>
                                            <div class="regular-text-container">
                                                <label class="cyp-color-label"><?php esc_html_e('Name', 'circular-year-planner'); ?></label>
                                                <input type="text" name="cypl_event_types[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($type['name']); ?>" placeholder="<?php esc_attr_e('Event type name', 'circular-year-planner'); ?>" class="regular-text">
                                            </div>
                                            <div class="cyp-color-picker-group">
                                                <label class="cyp-color-label"><?php esc_html_e('Background', 'circular-year-planner'); ?></label>
                                                <input type="text" name="cypl_event_types[<?php echo esc_attr($index); ?>][color]" value="<?php echo esc_attr($type['color']); ?>" class="cyp-color-picker">
                                            </div>
                                            <div class="cyp-color-picker-group">
                                                <label class="cyp-color-label"><?php esc_html_e('Text', 'circular-year-planner'); ?></label>
                                                <input type="text" name="cypl_event_types[<?php echo esc_attr($index); ?>][text_color]" value="<?php echo esc_attr(!empty($type['text_color']) ? $type['text_color'] : ''); ?>" placeholder="<?php esc_attr_e('Auto', 'circular-year-planner'); ?>" class="cyp-color-picker cyp-text-color-picker">
                                            </div>
                                            <button type="button" class="button cyp-remove-type"><?php esc_html_e('Remove', 'circular-year-planner'); ?></button>
                                            <?php if ($event_count > 0) : ?>
                                                <p class="cyp-type-in-use-notice" role="alert" hidden>
                                                    <?php
                                                    echo esc_html(sprintf(
                                                        /* translators: %d: Number of events */
                                                        _n('This event type cannot be removed because %d event uses it. Delete all events of this type first.', 'This event type cannot be removed because %d events use it. Delete all events of this type first.', $event_count, 'circular-year-planner'),
                                                        $event_count
                                                    ));
                                                    ?>
                                                    <a href="<?php echo esc_url(add_query_arg(array('post_type' => 'cypl_event', 'cypl_event_type' => $index), admin_url('edit.php'))); ?>"><?php esc_html_e('Show events', 'circular-year-planner'); ?></a>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" id="cyp-add-event-type" class="button"><?php esc_html_e('Add Event Type', 'circular-year-planner'); ?></button>
                            <p class="description"><?php esc_html_e('Define different types of events and their colors. Leave text color empty for automatic contrast.', 'circular-year-planner'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cypl_fiscal_year_start"><?php esc_html_e('Fiscal Year Start', 'circular-year-planner'); ?></label>
                        </th>
                        <td>
                            <select id="cypl_fiscal_year_start" name="cypl_fiscal_year_start">
                                <option value="01-01" <?php selected($fiscal_year_start, '01-01'); ?>><?php esc_html_e('January', 'circular-year-planner'); ?> (<?php esc_html_e('calendar year', 'circular-year-planner'); ?>)</option>
                                <option value="02-01" <?php selected($fiscal_year_start, '02-01'); ?>><?php esc_html_e('February', 'circular-year-planner'); ?></option>
                                <option value="03-01" <?php selected($fiscal_year_start, '03-01'); ?>><?php esc_html_e('March', 'circular-year-planner'); ?></option>
                                <option value="04-01" <?php selected($fiscal_year_start, '04-01'); ?>><?php esc_html_e('April', 'circular-year-planner'); ?></option>
                                <option value="05-01" <?php selected($fiscal_year_start, '05-01'); ?>><?php esc_html_e('May', 'circular-year-planner'); ?></option>
                                <option value="06-01" <?php selected($fiscal_year_start, '06-01'); ?>><?php esc_html_e('June', 'circular-year-planner'); ?></option>
                                <option value="07-01" <?php selected($fiscal_year_start, '07-01'); ?>><?php esc_html_e('July', 'circular-year-planner'); ?></option>
                                <option value="08-01" <?php selected($fiscal_year_start, '08-01'); ?>><?php esc_html_e('August', 'circular-year-planner'); ?></option>
                                <option value="09-01" <?php selected($fiscal_year_start, '09-01'); ?>><?php esc_html_e('September', 'circular-year-planner'); ?></option>
                                <option value="10-01" <?php selected($fiscal_year_start, '10-01'); ?>><?php esc_html_e('October', 'circular-year-planner'); ?></option>
                                <option value="11-01" <?php selected($fiscal_year_start, '11-01'); ?>><?php esc_html_e('November', 'circular-year-planner'); ?></option>
                                <option value="12-01" <?php selected($fiscal_year_start, '12-01'); ?>><?php esc_html_e('December', 'circular-year-planner'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Select when the fiscal year starts', 'circular-year-planner'); ?>.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cypl_color_scheme"><?php esc_html_e('Color Scheme', 'circular-year-planner'); ?></label>
                        </th>
                        <td>
                            <select id="cypl_color_scheme" name="cypl_color_scheme">
                                <option value="default" <?php selected($color_scheme, 'default'); ?>><?php esc_html_e('Standard', 'circular-year-planner'); ?> (<?php esc_html_e('Light', 'circular-year-planner'); ?>)</option>
                                <option value="dark" <?php selected($color_scheme, 'dark'); ?>><?php esc_html_e('Dark', 'circular-year-planner'); ?></option>
                                <option value="blue" <?php selected($color_scheme, 'blue'); ?>><?php esc_html_e('Blue', 'circular-year-planner'); ?></option>
                                <option value="green" <?php selected($color_scheme, 'green'); ?>><?php esc_html_e('Green', 'circular-year-planner'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Choose color scheme for the circular calendar.', 'circular-year-planner'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Settings', 'circular-year-planner')); ?>
            </form>
            
            <hr>
            
            <h2><?php esc_html_e('How to Use', 'circular-year-planner'); ?></h2>
            <div class="cypl-instructions">
                <h3><?php esc_html_e('Shortcode', 'circular-year-planner'); ?></h3>
                <p><?php esc_html_e('Use the following shortcode to display the circular calendar:', 'circular-year-planner'); ?></p>
                <code>[planly]</code>
                
                <h3><?php esc_html_e('Parameters', 'circular-year-planner'); ?></h3>
                <ul>
                    <li><code>[planly year="2024/2025"]</code> - <?php esc_html_e('Show specific fiscal year', 'circular-year-planner'); ?></li>
                    <li><code>[planly types="0,1"]</code> - <?php esc_html_e('Show only certain event types (by ID)', 'circular-year-planner'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Spara inställningar
     */
    public function save_settings() {
        // Kontrollera behörighet
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized access', 'circular-year-planner'));
        }
        
        // Verifiera nonce
        if (!isset($_POST['cypl_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cypl_settings_nonce'])), 'cypl_save_settings')) {
            wp_die(esc_html__('Security verification failed', 'circular-year-planner'));
        }
        
        // Spara händelsetyper. Befintliga typer behåller sitt id, nya rader (nyckel "new-N") får nästa lediga.
        // Typer som används av händelser får inte tas bort.
        $existing_types = get_option('cypl_event_types', array());
        $event_counts = self::count_events_by_type($existing_types);
        $next_id = self::next_event_type_id($existing_types);
        $raw_event_types = isset($_POST['cypl_event_types']) && is_array($_POST['cypl_event_types'])
            ? wp_unslash($_POST['cypl_event_types']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Saneras per fält i sanitize_event_type().
            : array();
        
        $submitted = array();
        $new_types = array();
        foreach ($raw_event_types as $key => $type) {
            if (!is_array($type) || empty($type['name'])) {
                continue;
            }
            
            if (is_int($key) && isset($existing_types[$key])) {
                $submitted[$key] = self::sanitize_event_type($type);
            } else {
                $new_types[] = self::sanitize_event_type($type);
            }
        }
        
        $event_types = array();
        $kept_in_use = array();
        foreach ($existing_types as $id => $type) {
            if (isset($submitted[$id])) {
                $event_types[$id] = $submitted[$id];
            } elseif (!empty($event_counts[$id])) {
                $event_types[$id] = $type;
                $kept_in_use[] = $type['name'];
            }
        }
        foreach ($new_types as $type) {
            $event_types[$next_id++] = $type;
        }
        
        update_option('cypl_event_types', $event_types);
        update_option('cypl_event_types_next_id', $next_id);
        
        // Spara verksamhetsår
        if (isset($_POST['cypl_fiscal_year_start'])) {
            update_option('cypl_fiscal_year_start', sanitize_text_field(wp_unslash($_POST['cypl_fiscal_year_start'])));
        }
        
        // Spara färgschema
        if (isset($_POST['cypl_color_scheme'])) {
            update_option('cypl_color_scheme', sanitize_text_field(wp_unslash($_POST['cypl_color_scheme'])));
        }
        
        // Redirect tillbaka med meddelande
        if (!empty($kept_in_use)) {
            set_transient('cypl_types_kept_' . get_current_user_id(), $kept_in_use, MINUTE_IN_SECONDS * 5);
        }
        
        $redirect_url = add_query_arg(array(
            'post_type' => 'cypl_event',
            'page' => 'cypl-settings',
            'message' => 'saved',
            '_wpnonce' => wp_create_nonce('cypl_settings_saved')
        ), admin_url('edit.php'));
        wp_safe_redirect($redirect_url);
        exit;
    }
}

