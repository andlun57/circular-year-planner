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
            __('Year Planning Settings', 'circular-year-planner'),
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
        
        $sanitized = array();
        foreach ($value as $type) {
            if (!empty($type['name'])) {
                $sanitized[] = array(
                    'name' => sanitize_text_field($type['name']),
                    'color' => sanitize_hex_color($type['color']),
                    'text_color' => !empty($type['text_color']) ? sanitize_hex_color($type['text_color']) : '',
                );
            }
        }
        return $sanitized;
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
        $fiscal_year_start = get_option('cypl_fiscal_year_start', '01-01');
        $color_scheme = get_option('cypl_color_scheme', 'default');
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Circular Year Planning Settings', 'circular-year-planner'); ?></h1>
            
            <?php if ($message === 'saved') : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Settings saved successfully', 'circular-year-planner'); ?>!</p>
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
                                        <input type="text" name="cypl_event_types[0][name]" placeholder="<?php esc_attr_e('Event type name', 'circular-year-planner'); ?>" class="regular-text">
                                        <div class="cyp-color-picker-group">
                                            <label class="cyp-color-label"><?php esc_html_e('Background', 'circular-year-planner'); ?></label>
                                            <input type="text" name="cypl_event_types[0][color]" value="#4A90E2" class="cyp-color-picker">
                                        </div>
                                        <div class="cyp-color-picker-group">
                                            <label class="cyp-color-label"><?php esc_html_e('Text', 'circular-year-planner'); ?></label>
                                            <input type="text" name="cypl_event_types[0][text_color]" value="" placeholder="<?php esc_attr_e('Auto', 'circular-year-planner'); ?>" class="cyp-color-picker cyp-text-color-picker">
                                        </div>
                                        <button type="button" class="button cyp-remove-type"><?php esc_html_e('Remove', 'circular-year-planner'); ?></button>
                                    </div>
                                <?php else : ?>
                                    <?php foreach ($event_types as $index => $type) : ?>
                                        <div class="cyp-event-type-row">
                                            <input type="text" name="cypl_event_types[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($type['name']); ?>" placeholder="<?php esc_attr_e('Event type name', 'circular-year-planner'); ?>" class="regular-text">
                                            <div class="cyp-color-picker-group">
                                                <label class="cyp-color-label"><?php esc_html_e('Background', 'circular-year-planner'); ?></label>
                                                <input type="text" name="cypl_event_types[<?php echo esc_attr($index); ?>][color]" value="<?php echo esc_attr($type['color']); ?>" class="cyp-color-picker">
                                            </div>
                                            <div class="cyp-color-picker-group">
                                                <label class="cyp-color-label"><?php esc_html_e('Text', 'circular-year-planner'); ?></label>
                                                <input type="text" name="cypl_event_types[<?php echo esc_attr($index); ?>][text_color]" value="<?php echo esc_attr(!empty($type['text_color']) ? $type['text_color'] : ''); ?>" placeholder="<?php esc_attr_e('Auto', 'circular-year-planner'); ?>" class="cyp-color-picker cyp-text-color-picker">
                                            </div>
                                            <button type="button" class="button cyp-remove-type"><?php esc_html_e('Remove', 'circular-year-planner'); ?></button>
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
                                <option value="default" <?php selected($color_scheme, 'default'); ?>><?php esc_html_e('Standard', 'circular-year-planner'); ?> (<?php esc_html_e('light', 'circular-year-planner'); ?>)</option>
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
                <code>[circular_year_planner]</code>
                
                <h3><?php esc_html_e('Parameters', 'circular-year-planner'); ?></h3>
                <ul>
                    <li><code>[circular_year_planner year="2024/2025"]</code> - <?php esc_html_e('Show specific fiscal year', 'circular-year-planner'); ?></li>
                    <li><code>[circular_year_planner types="0,1"]</code> - <?php esc_html_e('Show only certain event types (by index)', 'circular-year-planner'); ?></li>
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
        
        // Spara händelsetyper
        if (isset($_POST['cypl_event_types'])) {
            $event_types = array();
            $raw_event_types = wp_unslash($_POST['cypl_event_types']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            foreach ($raw_event_types as $type) {
                if (!empty($type['name'])) {
                    $event_types[] = array(
                        'name' => sanitize_text_field($type['name']),
                        'color' => sanitize_hex_color($type['color']),
                        'text_color' => !empty($type['text_color']) ? sanitize_hex_color($type['text_color']) : '',
                    );
                }
            }
            update_option('cypl_event_types', $event_types);
        }
        
        // Spara verksamhetsår
        if (isset($_POST['cypl_fiscal_year_start'])) {
            update_option('cypl_fiscal_year_start', sanitize_text_field(wp_unslash($_POST['cypl_fiscal_year_start'])));
        }
        
        // Spara färgschema
        if (isset($_POST['cypl_color_scheme'])) {
            update_option('cypl_color_scheme', sanitize_text_field(wp_unslash($_POST['cypl_color_scheme'])));
        }
        
        // Redirect tillbaka med meddelande
        $redirect_url = add_query_arg(array(
            'post_type' => 'cypl_event',
            'page' => 'cypl-settings',
            'message' => 'saved',
            '_wpnonce' => wp_create_nonce('cypl_settings_saved')
        ), admin_url('edit.php'));
        wp_redirect($redirect_url);
        exit;
    }
}

