<?php
/**
 * Eget formulär för att skapa och redigera händelser
 */

if (!defined('ABSPATH')) {
    exit;
}

class CYPL_Event_Form {
    
    const PAGE_SLUG = 'cypl-event-edit';
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_form_page'));
        add_action('admin_post_cypl_save_event', array($this, 'save_event'));
        add_action('load-post-new.php', array($this, 'redirect_new_post'));
        add_action('load-post.php', array($this, 'redirect_edit_post'));
        add_filter('get_edit_post_link', array($this, 'filter_edit_post_link'), 10, 3);
        add_filter('submenu_file', array($this, 'filter_submenu_file'));
    }
    
    /**
     * URL till formuläret, med eller utan befintlig händelse
     */
    public static function get_form_url($post_id = 0, $args = array()) {
        $args = array_merge(array(
            'post_type' => 'cypl_event',
            'page' => self::PAGE_SLUG,
        ), $post_id ? array('post' => $post_id) : array(), $args);
        
        return add_query_arg($args, admin_url('edit.php'));
    }
    
    /**
     * Ersätt "Lägg till" i menyn med formuläret
     */
    public function add_form_page() {
        $post_type = get_post_type_object('cypl_event');
        
        remove_submenu_page('edit.php?post_type=cypl_event', 'post-new.php?post_type=cypl_event');
        
        add_submenu_page(
            'edit.php?post_type=cypl_event',
            __('Add New Event', 'circular-year-planner'),
            __('Add New Event', 'circular-year-planner'),
            $post_type->cap->create_posts,
            self::PAGE_SLUG,
            array($this, 'render_page'),
            1
        );
    }
    
    /**
     * Markera "Alla händelser" i menyn när en befintlig händelse redigeras
     */
    public function filter_submenu_file($submenu_file) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['page'], $_GET['post']) && self::PAGE_SLUG === $_GET['page']) {
            return 'edit.php?post_type=cypl_event';
        }
        return $submenu_file;
    }
    
    public function redirect_new_post() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
        
        if ('cypl_event' === $post_type) {
            wp_safe_redirect(self::get_form_url());
            exit;
        }
    }
    
    public function redirect_edit_post() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        
        if ('edit' === $action && $post_id && 'cypl_event' === get_post_type($post_id)) {
            wp_safe_redirect(self::get_form_url($post_id));
            exit;
        }
    }
    
    public function filter_edit_post_link($link, $post_id, $context) {
        if ('cypl_event' !== get_post_type($post_id)) {
            return $link;
        }
        
        $url = self::get_form_url($post_id);
        return 'display' === $context ? esc_url($url) : $url;
    }
    
    /**
     * Rendera formuläret
     */
    public function render_page() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        $message = '';
        if (isset($_GET['message'], $_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'cypl_event_message')) {
            $message = sanitize_key(wp_unslash($_GET['message']));
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        
        $post = $post_id ? get_post($post_id) : null;
        if ($post_id && (!$post || 'cypl_event' !== $post->post_type)) {
            wp_die(esc_html__('Event not found.', 'circular-year-planner'));
        }
        if ($post && !current_user_can('edit_post', $post_id)) {
            wp_die(esc_html__('Sorry, you are not allowed to edit this event.', 'circular-year-planner'));
        }
        
        $data = array(
            'title' => $post ? $post->post_title : '',
            'start_date' => $post ? get_post_meta($post_id, '_cypl_start_date', true) : '',
            'end_date' => $post ? get_post_meta($post_id, '_cypl_end_date', true) : '',
            'event_type' => $post ? get_post_meta($post_id, '_cypl_event_type', true) : '',
            'rule' => $post ? CYPL_Recurrence::get_rule($post_id) : null,
        );
        
        // Värden från ett misslyckat försök att spara
        $submitted = get_transient($this->get_transient_key());
        if (is_array($submitted) && $message && 'saved' !== $message) {
            $data = array_merge($data, $submitted);
        }
        delete_transient($this->get_transient_key());
        
        $until = $post ? CYPL_Recurrence::get_until($post_id) : '';
        $exdate_count = $post ? count(CYPL_Recurrence::get_exdates($post_id)) : 0;
        $event_types = get_option('cypl_event_types', array());
        $freq = $data['rule'] ? $data['rule']['freq'] : '';
        
        ?>
        <div class="wrap cypl-event-form-wrap">
            <h1 class="wp-heading-inline">
                <?php echo $post ? esc_html__('Edit Event', 'circular-year-planner') : esc_html__('Add New Event', 'circular-year-planner'); ?>
            </h1>
            <?php if ($post) : ?>
                <a href="<?php echo esc_url(self::get_form_url()); ?>" class="page-title-action"><?php esc_html_e('Add New Event', 'circular-year-planner'); ?></a>
            <?php endif; ?>
            <hr class="wp-header-end">
            
            <?php $this->render_message($message); ?>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="cypl-event-form" class="cypl-event-form">
                <input type="hidden" name="action" value="cypl_save_event">
                <input type="hidden" name="cypl_event_id" value="<?php echo esc_attr($post_id); ?>">
                <?php wp_nonce_field('cypl_save_event', 'cypl_event_nonce'); ?>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="cypl_title"><?php esc_html_e('Title', 'circular-year-planner'); ?></label></th>
                        <td>
                            <input type="text" id="cypl_title" name="cypl_title" value="<?php echo esc_attr($data['title']); ?>" class="regular-text cypl-title-field" required <?php echo $post ? '' : 'autofocus'; ?>>
                            <p class="description"><?php esc_html_e('Shown on the event in the calendar.', 'circular-year-planner'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cypl_start_date"><?php esc_html_e('Start Date', 'circular-year-planner'); ?></label></th>
                        <td>
                            <input type="date" id="cypl_start_date" name="cypl_start_date" value="<?php echo esc_attr($data['start_date']); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cypl_end_date"><?php esc_html_e('End Date', 'circular-year-planner'); ?></label></th>
                        <td>
                            <input type="date" id="cypl_end_date" name="cypl_end_date" value="<?php echo esc_attr($data['end_date']); ?>" required>
                            <p class="description"><?php esc_html_e('For single-day events, use the same date as the start date.', 'circular-year-planner'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cypl_event_type"><?php esc_html_e('Event Type', 'circular-year-planner'); ?></label></th>
                        <td>
                            <select id="cypl_event_type" name="cypl_event_type" required>
                                <option value=""><?php esc_html_e('Select event type', 'circular-year-planner'); ?></option>
                                <?php foreach ($event_types as $index => $type) : ?>
                                    <option value="<?php echo esc_attr($index); ?>" <?php selected((string) $data['event_type'], (string) $index); ?>>
                                        <?php echo esc_html($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cypl_recurrence_freq"><?php esc_html_e('Repeat', 'circular-year-planner'); ?></label></th>
                        <td>
                            <select id="cypl_recurrence_freq" name="cypl_recurrence[freq]">
                                <option value="" <?php selected($freq, ''); ?>><?php esc_html_e('Never', 'circular-year-planner'); ?></option>
                                <option value="daily" <?php selected($freq, 'daily'); ?>><?php esc_html_e('Daily', 'circular-year-planner'); ?></option>
                                <option value="weekly" <?php selected($freq, 'weekly'); ?>><?php esc_html_e('Weekly', 'circular-year-planner'); ?></option>
                                <option value="monthly" <?php selected($freq, 'monthly'); ?>><?php esc_html_e('Monthly', 'circular-year-planner'); ?></option>
                                <option value="yearly" <?php selected($freq, 'yearly'); ?>><?php esc_html_e('Yearly', 'circular-year-planner'); ?></option>
                            </select>
                            
                            <div class="cypl-recurrence-panels">
                                <?php
                                $this->render_daily_panel($this->get_panel_values($data, 'daily'), $freq);
                                $this->render_weekly_panel($this->get_panel_values($data, 'weekly'), $freq);
                                $this->render_monthly_panel($this->get_panel_values($data, 'monthly'), $freq);
                                $this->render_yearly_panel($this->get_panel_values($data, 'yearly'), $freq);
                                ?>
                            </div>
                            
                            <?php if ($until || $exdate_count) : ?>
                                <div class="cypl-series-status">
                                    <?php if ($until) : ?>
                                        <p>
                                            <?php
                                            printf(
                                                /* translators: %s: date of the last possible occurrence */
                                                esc_html__('The series ends on %s because future occurrences have been deleted.', 'circular-year-planner'),
                                                esc_html(mysql2date(get_option('date_format'), $until))
                                            );
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($exdate_count) : ?>
                                        <p>
                                            <?php
                                            printf(
                                                /* translators: %d: number of deleted occurrences */
                                                esc_html(_n('%d occurrence has been deleted from the series.', '%d occurrences have been deleted from the series.', $exdate_count, 'circular-year-planner')),
                                                (int) $exdate_count
                                            );
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                    <label>
                                        <input type="checkbox" name="cypl_recurrence_reset" value="1">
                                        <?php esc_html_e('Restore the series (remove the end date and all deleted occurrences)', 'circular-year-planner'); ?>
                                    </label>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <div class="cypl-event-form-actions">
                    <?php submit_button(__('Save Event', 'circular-year-planner'), 'primary', 'submit', false); ?>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=cypl_event')); ?>" class="button"><?php esc_html_e('All Events', 'circular-year-planner'); ?></a>
                    <?php if ($post && current_user_can('delete_post', $post_id)) : ?>
                        <a href="<?php echo esc_url(get_delete_post_link($post_id)); ?>" class="cypl-trash-link"><?php esc_html_e('Move to Trash', 'circular-year-planner'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php
    }
    
    private function render_message($message) {
        $messages = array(
            'saved' => array('success', __('Event saved.', 'circular-year-planner')),
            'title' => array('error', __('Please enter a title.', 'circular-year-planner')),
            'start_date' => array('error', __('Please enter a valid start date.', 'circular-year-planner')),
            'end_date' => array('error', __('The end date cannot be before the start date.', 'circular-year-planner')),
            'event_type' => array('error', __('Please select an event type.', 'circular-year-planner')),
        );
        
        if (!isset($messages[$message])) {
            return;
        }
        
        list($type, $text) = $messages[$message];
        printf('<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr($type), esc_html($text));
    }
    
    /**
     * Värden för en frekvenspanel: sparad regel om frekvensen matchar, annars standardvärden från startdatum
     */
    private function get_panel_values($data, $freq) {
        $start = CYPL_Recurrence::parse_date((string) $data['start_date']);
        $weekday = $start ? (string) $start->format('N') : '1';
        
        $values = ($data['rule'] && $data['rule']['freq'] === $freq)
            ? $data['rule']
            : CYPL_Recurrence::normalize_rule(array('freq' => $freq), $start);
        
        return array_merge(array(
            'monthday' => $start ? (int) $start->format('j') : 1,
            'setpos' => 1,
            'day_kind' => $weekday,
        ), $values);
    }
    
    private function open_panel($freq, $current_freq) {
        $active = $freq === $current_freq;
        printf(
            '<fieldset class="cypl-recurrence-panel" data-freq="%1$s"%2$s>',
            esc_attr($freq),
            $active ? '' : ' hidden disabled'
        );
    }
    
    /**
     * Rad med intervallfält inbakat i texten, t.ex. "Med intervall på [2] vecka på:"
     */
    private function render_interval($freq, $interval, $format) {
        $input = sprintf(
            '<input type="number" name="cypl_recurrence[%1$s][interval]" value="%2$d" min="1" max="%3$d" step="1" class="small-text cypl-interval" required>',
            esc_attr($freq),
            (int) $interval,
            (int) CYPL_Recurrence::MAX_INTERVAL
        );
        
        echo '<p class="cypl-recurrence-interval"><label>';
        echo wp_kses(sprintf(esc_html($format), $input), array(
            'input' => array(
                'type' => true,
                'name' => true,
                'value' => true,
                'min' => true,
                'max' => true,
                'step' => true,
                'class' => true,
                'required' => true,
            ),
        ));
        echo '</label></p>';
    }
    
    private function render_daily_panel($values, $current_freq) {
        $this->open_panel('daily', $current_freq);
        /* translators: %s: number input for the interval in days */
        $this->render_interval('daily', $values['interval'], __('Every %s day(s)', 'circular-year-planner'));
        echo '</fieldset>';
    }
    
    private function render_weekly_panel($values, $current_freq) {
        $this->open_panel('weekly', $current_freq);
        /* translators: %s: number input for the interval in weeks */
        $this->render_interval('weekly', $values['interval'], __('Every %s week(s) on:', 'circular-year-planner'));
        ?>
        <div class="cypl-choice-grid cypl-weekday-grid" role="radiogroup" aria-label="<?php esc_attr_e('Weekday', 'circular-year-planner'); ?>">
            <?php for ($weekday = 1; $weekday <= 7; $weekday++) : ?>
                <label class="cypl-choice" title="<?php echo esc_attr(CYPL_Recurrence::weekday_name($weekday)); ?>">
                    <input type="radio" name="cypl_recurrence[weekly][weekday]" value="<?php echo esc_attr($weekday); ?>" <?php checked((int) $values['weekday'], $weekday); ?>>
                    <span><?php echo esc_html(CYPL_Recurrence::weekday_name($weekday, true)); ?></span>
                </label>
            <?php endfor; ?>
        </div>
        </fieldset>
        <?php
    }
    
    private function render_monthly_panel($values, $current_freq) {
        $mode = isset($values['mode']) ? $values['mode'] : 'each';
        
        $this->open_panel('monthly', $current_freq);
        /* translators: %s: number input for the interval in months */
        $this->render_interval('monthly', $values['interval'], __('Every %s month(s)', 'circular-year-planner'));
        ?>
        <div class="cypl-recurrence-option" data-mode-group>
            <label class="cypl-mode-label">
                <input type="radio" name="cypl_recurrence[monthly][mode]" value="each" <?php checked($mode, 'each'); ?>>
                <?php esc_html_e('Each', 'circular-year-planner'); ?>
            </label>
            <select name="cypl_recurrence[monthly][monthday]" aria-label="<?php esc_attr_e('Day of month', 'circular-year-planner'); ?>" data-mode="each">
                <?php for ($day = 1; $day <= 31; $day++) : ?>
                    <option value="<?php echo esc_attr($day); ?>" <?php selected((int) $values['monthday'], $day); ?>><?php echo esc_html($day); ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="cypl-recurrence-option">
            <label class="cypl-mode-label">
                <input type="radio" name="cypl_recurrence[monthly][mode]" value="onthe" <?php checked($mode, 'onthe'); ?>>
                <?php esc_html_e('On the', 'circular-year-planner'); ?>
            </label>
            <?php $this->render_position_selects('monthly', $values, 'onthe'); ?>
        </div>
        </fieldset>
        <?php
    }
    
    private function render_yearly_panel($values, $current_freq) {
        global $wp_locale;
        
        $this->open_panel('yearly', $current_freq);
        /* translators: %s: number input for the interval in years */
        $this->render_interval('yearly', $values['interval'], __('Every %s year(s) in:', 'circular-year-planner'));
        ?>
        <div class="cypl-choice-grid cypl-month-grid" role="radiogroup" aria-label="<?php esc_attr_e('Month', 'circular-year-planner'); ?>">
            <?php for ($month = 1; $month <= 12; $month++) : ?>
                <label class="cypl-choice" title="<?php echo esc_attr($wp_locale->get_month($month)); ?>">
                    <input type="radio" name="cypl_recurrence[yearly][month]" value="<?php echo esc_attr($month); ?>" <?php checked((int) $values['month'], $month); ?>>
                    <span><?php echo esc_html($wp_locale->get_month_abbrev($wp_locale->get_month($month))); ?></span>
                </label>
            <?php endfor; ?>
        </div>
        <div class="cypl-recurrence-option">
            <label class="cypl-mode-label">
                <input type="checkbox" name="cypl_recurrence[yearly][onthe]" value="1" class="cypl-yearly-onthe" <?php checked(!empty($values['onthe'])); ?>>
                <?php esc_html_e('On the', 'circular-year-planner'); ?>
            </label>
            <?php $this->render_position_selects('yearly', $values, 'onthe'); ?>
        </div>
        </fieldset>
        <?php
    }
    
    /**
     * Menyer för "första … sista" och "måndag … veckoslutsdag"
     */
    private function render_position_selects($freq, $values, $mode) {
        ?>
        <select name="cypl_recurrence[<?php echo esc_attr($freq); ?>][setpos]" aria-label="<?php esc_attr_e('Position', 'circular-year-planner'); ?>" data-mode="<?php echo esc_attr($mode); ?>">
            <?php foreach (CYPL_Recurrence::setpos_labels() as $value => $label) : ?>
                <?php if (null === $label) : ?>
                    <option disabled>──────────</option>
                <?php else : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected((int) $values['setpos'], (int) $value); ?>><?php echo esc_html($label); ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
        <select name="cypl_recurrence[<?php echo esc_attr($freq); ?>][day_kind]" aria-label="<?php esc_attr_e('Day', 'circular-year-planner'); ?>" data-mode="<?php echo esc_attr($mode); ?>">
            <?php foreach (CYPL_Recurrence::day_kind_labels() as $value => $label) : ?>
                <?php if (null === $label) : ?>
                    <option disabled>──────────</option>
                <?php else : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected((string) $values['day_kind'], (string) $value); ?>><?php echo esc_html($label); ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    private function get_transient_key() {
        return 'cypl_event_form_' . get_current_user_id();
    }
    
    /**
     * Spara händelse från formuläret
     */
    public function save_event() {
        if (!isset($_POST['cypl_event_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cypl_event_nonce'])), 'cypl_save_event')) {
            wp_die(esc_html__('Security verification failed', 'circular-year-planner'));
        }
        
        $post_id = isset($_POST['cypl_event_id']) ? absint($_POST['cypl_event_id']) : 0;
        $post_type = get_post_type_object('cypl_event');
        
        if ($post_id) {
            if ('cypl_event' !== get_post_type($post_id) || !current_user_can('edit_post', $post_id)) {
                wp_die(esc_html__('Sorry, you are not allowed to edit this event.', 'circular-year-planner'));
            }
        } elseif (!current_user_can($post_type->cap->create_posts)) {
            wp_die(esc_html__('Unauthorized access', 'circular-year-planner'));
        }
        
        $title = isset($_POST['cypl_title']) ? sanitize_text_field(wp_unslash($_POST['cypl_title'])) : '';
        $start_date = isset($_POST['cypl_start_date']) ? sanitize_text_field(wp_unslash($_POST['cypl_start_date'])) : '';
        $end_date = isset($_POST['cypl_end_date']) ? sanitize_text_field(wp_unslash($_POST['cypl_end_date'])) : '';
        $event_type = isset($_POST['cypl_event_type']) ? sanitize_text_field(wp_unslash($_POST['cypl_event_type'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Saneras i CYPL_Recurrence::sanitize_form_input().
        $raw_recurrence = isset($_POST['cypl_recurrence']) ? wp_unslash($_POST['cypl_recurrence']) : array();
        $reset_series = !empty($_POST['cypl_recurrence_reset']);
        
        if ('' === $end_date) {
            $end_date = $start_date;
        }
        
        $start = CYPL_Recurrence::parse_date($start_date);
        $end = CYPL_Recurrence::parse_date($end_date);
        $event_types = get_option('cypl_event_types', array());
        $rule = CYPL_Recurrence::sanitize_form_input($raw_recurrence, $start);
        
        $error = '';
        if ('' === $title) {
            $error = 'title';
        } elseif (!$start) {
            $error = 'start_date';
        } elseif (!$end || $end < $start) {
            $error = 'end_date';
        } elseif (!isset($event_types[$event_type])) {
            $error = 'event_type';
        }
        
        if ($error) {
            set_transient($this->get_transient_key(), array(
                'title' => $title,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'event_type' => $event_type,
                'rule' => $rule,
            ), MINUTE_IN_SECONDS);
            $this->redirect_with_message($post_id, $error);
        }
        
        $postarr = array(
            'post_type' => 'cypl_event',
            'post_title' => $title,
            'post_status' => current_user_can($post_type->cap->publish_posts) ? 'publish' : 'pending',
        );
        
        if ($post_id) {
            $postarr['ID'] = $post_id;
            $result = wp_update_post($postarr, true);
        } else {
            $result = wp_insert_post($postarr, true);
        }
        
        if (is_wp_error($result)) {
            wp_die(esc_html($result->get_error_message()));
        }
        
        $post_id = (int) $result;
        update_post_meta($post_id, '_cypl_start_date', $start_date);
        update_post_meta($post_id, '_cypl_end_date', $end_date);
        update_post_meta($post_id, '_cypl_event_type', $event_type);
        
        if (!$rule || $reset_series) {
            CYPL_Recurrence::clear($post_id);
        }
        if ($rule) {
            CYPL_Recurrence::save_rule($post_id, $rule);
        }
        
        $this->redirect_with_message($post_id, 'saved');
    }
    
    private function redirect_with_message($post_id, $message) {
        wp_safe_redirect(self::get_form_url($post_id, array(
            'message' => $message,
            '_wpnonce' => wp_create_nonce('cypl_event_message'),
        )));
        exit;
    }
}
