<?php
/**
 * Hanterar plugin-inställningar
 */

if (!defined('ABSPATH')) {
    exit;
}

class CYP_Settings {
    
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
        add_action('admin_post_cyp_save_settings', array($this, 'save_settings'));
    }
    
    /**
     * Lägg till inställningssida
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=cyp_event',
            __('Year Planning Settings', 'circular-year-planner'),
            __('Settings', 'circular-year-planner'),
            'manage_options',
            'cyp-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Registrera inställningar
     */
    public function register_settings() {
        register_setting('cyp_settings', 'cyp_event_types', array(
            'sanitize_callback' => array($this, 'sanitize_event_types'),
        ));
        register_setting('cyp_settings', 'cyp_fiscal_year_start', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('cyp_settings', 'cyp_color_scheme', array(
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
            if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'cyp_settings_saved')) {
                $message = sanitize_text_field(wp_unslash($_GET['message']));
            }
        }
        
        $event_types = get_option('cyp_event_types', array());
        $fiscal_year_start = get_option('cyp_fiscal_year_start', '01-01');
        $color_scheme = get_option('cyp_color_scheme', 'default');
        
        ?>
        <div class="wrap">
            <h1><?php _e('Circular Year Planning Settings', 'circular-year-planner'); ?></h1>
            
            <?php if ($message === 'saved') : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully', 'circular-year-planner'); ?>!</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="cyp-settings-form">
                <input type="hidden" name="action" value="cyp_save_settings">
                <?php wp_nonce_field('cyp_save_settings', 'cyp_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e('Event Types', 'circular-year-planner'); ?></label>
                        </th>
                        <td>
                            <div id="cyp-event-types">
                                <?php if (empty($event_types)) : ?>
                                    <div class="cyp-event-type-row">
                                        <input type="text" name="cyp_event_types[0][name]" placeholder="<?php esc_attr_e('Event type name', 'circular-year-planner'); ?>" class="regular-text">
                                        <input type="text" name="cyp_event_types[0][color]" value="#4A90E2" class="cyp-color-picker">
                                        <button type="button" class="button cyp-remove-type"><?php _e('Remove', 'circular-year-planner'); ?></button>
                                    </div>
                                <?php else : ?>
                                    <?php foreach ($event_types as $index => $type) : ?>
                                        <div class="cyp-event-type-row">
                                            <input type="text" name="cyp_event_types[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($type['name']); ?>" placeholder="<?php esc_attr_e('Event type name', 'circular-year-planner'); ?>" class="regular-text">
                                            <input type="text" name="cyp_event_types[<?php echo esc_attr($index); ?>][color]" value="<?php echo esc_attr($type['color']); ?>" class="cyp-color-picker">
                                            <button type="button" class="button cyp-remove-type"><?php _e('Remove', 'circular-year-planner'); ?></button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" id="cyp-add-event-type" class="button"><?php _e('Add Event Type', 'circular-year-planner'); ?></button>
                            <p class="description"><?php _e('Define different types of events and their colors.', 'circular-year-planner'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cyp_fiscal_year_start"><?php _e('Fiscal Year Start', 'circular-year-planner'); ?></label>
                        </th>
                        <td>
                            <select id="cyp_fiscal_year_start" name="cyp_fiscal_year_start">
                                <option value="01-01" <?php selected($fiscal_year_start, '01-01'); ?>><?php _e('January', 'circular-year-planner'); ?> (<?php _e('calendar year', 'circular-year-planner'); ?>)</option>
                                <option value="02-01" <?php selected($fiscal_year_start, '02-01'); ?>><?php _e('February', 'circular-year-planner'); ?></option>
                                <option value="03-01" <?php selected($fiscal_year_start, '03-01'); ?>><?php _e('March', 'circular-year-planner'); ?></option>
                                <option value="04-01" <?php selected($fiscal_year_start, '04-01'); ?>><?php _e('April', 'circular-year-planner'); ?></option>
                                <option value="05-01" <?php selected($fiscal_year_start, '05-01'); ?>><?php _e('May', 'circular-year-planner'); ?></option>
                                <option value="06-01" <?php selected($fiscal_year_start, '06-01'); ?>><?php _e('June', 'circular-year-planner'); ?></option>
                                <option value="07-01" <?php selected($fiscal_year_start, '07-01'); ?>><?php _e('July', 'circular-year-planner'); ?></option>
                                <option value="08-01" <?php selected($fiscal_year_start, '08-01'); ?>><?php _e('August', 'circular-year-planner'); ?></option>
                                <option value="09-01" <?php selected($fiscal_year_start, '09-01'); ?>><?php _e('September', 'circular-year-planner'); ?></option>
                                <option value="10-01" <?php selected($fiscal_year_start, '10-01'); ?>><?php _e('October', 'circular-year-planner'); ?></option>
                                <option value="11-01" <?php selected($fiscal_year_start, '11-01'); ?>><?php _e('November', 'circular-year-planner'); ?></option>
                                <option value="12-01" <?php selected($fiscal_year_start, '12-01'); ?>><?php _e('December', 'circular-year-planner'); ?></option>
                            </select>
                            <p class="description"><?php _e('Select when the fiscal year starts', 'circular-year-planner'); ?>.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cyp_color_scheme"><?php _e('Color Scheme', 'circular-year-planner'); ?></label>
                        </th>
                        <td>
                            <select id="cyp_color_scheme" name="cyp_color_scheme">
                                <option value="default" <?php selected($color_scheme, 'default'); ?>><?php _e('Standard', 'circular-year-planner'); ?> (<?php _e('light', 'circular-year-planner'); ?>)</option>
                                <option value="dark" <?php selected($color_scheme, 'dark'); ?>><?php _e('Dark', 'circular-year-planner'); ?></option>
                                <option value="blue" <?php selected($color_scheme, 'blue'); ?>><?php _e('Blue', 'circular-year-planner'); ?></option>
                                <option value="green" <?php selected($color_scheme, 'green'); ?>><?php _e('Green', 'circular-year-planner'); ?></option>
                            </select>
                            <p class="description"><?php _e('Choose color scheme for the circular calendar.', 'circular-year-planner'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Settings', 'circular-year-planner')); ?>
            </form>
            
            <hr>
            
            <h2><?php _e('How to Use', 'circular-year-planner'); ?></h2>
            <div class="cyp-instructions">
                <h3><?php _e('Shortcode', 'circular-year-planner'); ?></h3>
                <p><?php _e('Use the following shortcode to display the circular calendar:', 'circular-year-planner'); ?></p>
                <code>[circular_year_planner]</code>
                
                <h3><?php _e('Parameters', 'circular-year-planner'); ?></h3>
                <ul>
                    <li><code>[circular_year_planner year="2024/2025"]</code> - <?php _e('Show specific fiscal year', 'circular-year-planner'); ?></li>
                    <li><code>[circular_year_planner types="0,1"]</code> - <?php _e('Show only certain event types (by index)', 'circular-year-planner'); ?></li>
                </ul>
            </div>
        </div>
        
        <style>
            .cyp-event-type-row {
                margin-bottom: 10px;
                display: flex;
                gap: 10px;
                align-items: center;
            }
            .cyp-instructions {
                background: #f9f9f9;
                padding: 20px;
                border-left: 4px solid #2271b1;
            }
            .cyp-instructions code {
                background: #fff;
                padding: 2px 6px;
                border: 1px solid #ddd;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Lägg till händelsetyp
            $('#cyp-add-event-type').on('click', function() {
                var index = $('#cyp-event-types .cyp-event-type-row').length;
                var html = '<div class="cyp-event-type-row">' +
                    '<input type="text" name="cyp_event_types[' + index + '][name]" placeholder="<?php esc_attr_e('Event type name', 'circular-year-planner'); ?>" class="regular-text">' +
                    '<input type="text" name="cyp_event_types[' + index + '][color]" value="#4A90E2" class="cyp-color-picker">' +
                    '<button type="button" class="button cyp-remove-type"><?php _e('Remove', 'circular-year-planner'); ?></button>' +
                    '</div>';
                $('#cyp-event-types').append(html);
                
                // Initiera color picker för nya fält
                $('.cyp-color-picker').wpColorPicker();
            });
            
            // Ta bort händelsetyp
            $(document).on('click', '.cyp-remove-type', function() {
                $(this).closest('.cyp-event-type-row').remove();
            });
            
            // Initiera color pickers
            $('.cyp-color-picker').wpColorPicker();
        });
        </script>
        <?php
    }
    
    /**
     * Spara inställningar
     */
    public function save_settings() {
        // Kontrollera behörighet
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'circular-year-planner'));
        }
        
        // Verifiera nonce
        if (!isset($_POST['cyp_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cyp_settings_nonce'])), 'cyp_save_settings')) {
            wp_die(__('Security verification failed', 'circular-year-planner'));
        }
        
        // Spara händelsetyper
        if (isset($_POST['cyp_event_types'])) {
            $event_types = array();
            $raw_event_types = wp_unslash($_POST['cyp_event_types']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            foreach ($raw_event_types as $type) {
                if (!empty($type['name'])) {
                    $event_types[] = array(
                        'name' => sanitize_text_field($type['name']),
                        'color' => sanitize_hex_color($type['color']),
                    );
                }
            }
            update_option('cyp_event_types', $event_types);
        }
        
        // Spara verksamhetsår
        if (isset($_POST['cyp_fiscal_year_start'])) {
            update_option('cyp_fiscal_year_start', sanitize_text_field(wp_unslash($_POST['cyp_fiscal_year_start'])));
        }
        
        // Spara färgschema
        if (isset($_POST['cyp_color_scheme'])) {
            update_option('cyp_color_scheme', sanitize_text_field(wp_unslash($_POST['cyp_color_scheme'])));
        }
        
        // Redirect tillbaka med meddelande
        $redirect_url = add_query_arg(array(
            'post_type' => 'cyp_event',
            'page' => 'cyp-settings',
            'message' => 'saved',
            '_wpnonce' => wp_create_nonce('cyp_settings_saved')
        ), admin_url('edit.php'));
        wp_redirect($redirect_url);
        exit;
    }
}

