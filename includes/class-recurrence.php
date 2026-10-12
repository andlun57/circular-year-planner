<?php
/**
 * Regler och expansion för återkommande händelser
 */

if (!defined('ABSPATH')) {
    exit;
}

class CYPL_Recurrence {
    
    const META_RULE = '_cypl_recurrence';
    const META_EXDATES = '_cypl_exdates';
    const META_UNTIL = '_cypl_until';
    
    const FREQUENCIES = array('daily', 'weekly', 'monthly', 'yearly');
    const SETPOS_VALUES = array(1, 2, 3, 4, 5, -2, -1);
    const DAY_KINDS = array('1', '2', '3', '4', '5', '6', '7', 'day', 'weekday', 'weekend');
    const MAX_INTERVAL = 999;
    
    /**
     * Tolka ett datum i formatet YYYY-MM-DD
     */
    public static function parse_date($value) {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
        if (!$date || $date->format('Y-m-d') !== $value) {
            return null;
        }
        
        return $date;
    }
    
    /**
     * Antal dagar från $from till $to (negativt om $to är före $from)
     */
    public static function days_between($from, $to) {
        return (int) $from->diff($to)->format('%r%a');
    }
    
    /**
     * Sanera regel från händelseformuläret
     *
     * Formuläret skickar en grupp fält per frekvens, t.ex. cypl_recurrence[weekly][interval].
     */
    public static function sanitize_form_input($raw, $start_date) {
        if (!is_array($raw) || empty($raw['freq'])) {
            return null;
        }
        
        $freq = sanitize_key($raw['freq']);
        if (!in_array($freq, self::FREQUENCIES, true)) {
            return null;
        }
        
        $group = isset($raw[$freq]) && is_array($raw[$freq]) ? $raw[$freq] : array();
        $rule = array_map('sanitize_text_field', $group);
        $rule['freq'] = $freq;
        
        if ('yearly' === $freq) {
            $rule['onthe'] = !empty($group['onthe']);
        }
        
        return self::normalize_rule($rule, $start_date);
    }
    
    /**
     * Validera regel och fyll i standardvärden utifrån startdatum
     */
    public static function normalize_rule($rule, $start_date = null) {
        if (!is_array($rule) || !isset($rule['freq']) || !in_array($rule['freq'], self::FREQUENCIES, true)) {
            return null;
        }
        
        $start = $start_date instanceof DateTimeImmutable ? $start_date : self::parse_date((string) $start_date);
        $default_weekday = $start ? (int) $start->format('N') : 1;
        
        $normalized = array(
            'freq' => $rule['freq'],
            'interval' => self::clamp_int(isset($rule['interval']) ? $rule['interval'] : 1, 1, self::MAX_INTERVAL, 1),
        );
        
        switch ($rule['freq']) {
            case 'weekly':
                $normalized['weekday'] = self::clamp_int(isset($rule['weekday']) ? $rule['weekday'] : $default_weekday, 1, 7, $default_weekday);
                break;
                
            case 'monthly':
                $normalized['mode'] = (isset($rule['mode']) && 'onthe' === $rule['mode']) ? 'onthe' : 'each';
                if ('onthe' === $normalized['mode']) {
                    $normalized = array_merge($normalized, self::normalize_position($rule, $default_weekday));
                } else {
                    $default_day = $start ? (int) $start->format('j') : 1;
                    $normalized['monthday'] = self::clamp_int(isset($rule['monthday']) ? $rule['monthday'] : $default_day, 1, 31, $default_day);
                }
                break;
                
            case 'yearly':
                $default_month = $start ? (int) $start->format('n') : 1;
                $normalized['month'] = self::clamp_int(isset($rule['month']) ? $rule['month'] : $default_month, 1, 12, $default_month);
                $normalized['onthe'] = !empty($rule['onthe']);
                if ($normalized['onthe']) {
                    $normalized = array_merge($normalized, self::normalize_position($rule, $default_weekday));
                }
                break;
        }
        
        return $normalized;
    }
    
    private static function normalize_position($rule, $default_weekday) {
        $setpos = isset($rule['setpos']) ? (int) $rule['setpos'] : 1;
        $day_kind = isset($rule['day_kind']) ? (string) $rule['day_kind'] : (string) $default_weekday;
        
        return array(
            'setpos' => in_array($setpos, self::SETPOS_VALUES, true) ? $setpos : 1,
            'day_kind' => in_array($day_kind, self::DAY_KINDS, true) ? $day_kind : (string) $default_weekday,
        );
    }
    
    private static function clamp_int($value, $min, $max, $default) {
        if (!is_numeric($value)) {
            return $default;
        }
        return max($min, min($max, (int) $value));
    }
    
    /**
     * Hämta regeln för en händelse, eller null om den inte upprepas
     */
    public static function get_rule($post_id) {
        $json = get_post_meta($post_id, self::META_RULE, true);
        if (empty($json) || !is_string($json)) {
            return null;
        }
        
        return self::normalize_rule(json_decode($json, true));
    }
    
    public static function save_rule($post_id, $rule) {
        update_post_meta($post_id, self::META_RULE, wp_slash(wp_json_encode($rule)));
    }
    
    /**
     * Ta bort regel, undantag och avslut
     */
    public static function clear($post_id) {
        delete_post_meta($post_id, self::META_RULE);
        delete_post_meta($post_id, self::META_EXDATES);
        delete_post_meta($post_id, self::META_UNTIL);
    }
    
    public static function get_exdates($post_id) {
        $exdates = get_post_meta($post_id, self::META_EXDATES, true);
        if (!is_array($exdates)) {
            return array();
        }
        
        return array_values(array_filter($exdates, function ($date) {
            return null !== self::parse_date($date);
        }));
    }
    
    public static function get_until($post_id) {
        $until = get_post_meta($post_id, self::META_UNTIL, true);
        return self::parse_date($until) ? $until : '';
    }
    
    /**
     * Startdatum (YYYY-MM-DD) för alla förekomster som startar inom intervallet
     */
    public static function get_occurrence_dates($post_id, $range_start, $range_end) {
        $start = self::parse_date(get_post_meta($post_id, '_cypl_start_date', true));
        if (!$start) {
            return array();
        }
        
        $rule = self::get_rule($post_id);
        if (!$rule) {
            return ($start >= $range_start && $start <= $range_end) ? array($start->format('Y-m-d')) : array();
        }
        
        $lo = $range_start > $start ? $range_start : $start;
        $hi = $range_end;
        $until = self::parse_date(self::get_until($post_id));
        if ($until && $until < $hi) {
            $hi = $until;
        }
        
        if ($lo > $hi) {
            return array();
        }
        
        $exdates = array_flip(self::get_exdates($post_id));
        $dates = array();
        
        // Startdatumet är alltid första förekomsten, även om det inte matchar regeln
        $candidates = self::expand($rule, $start, $lo, $hi);
        if ($start >= $lo) {
            $candidates[] = $start;
        }
        
        foreach ($candidates as $date) {
            $key = $date->format('Y-m-d');
            if (!isset($exdates[$key])) {
                $dates[$key] = $key;
            }
        }
        
        ksort($dates);
        return array_values($dates);
    }
    
    public static function is_occurrence($post_id, $date) {
        $parsed = self::parse_date($date);
        if (!$parsed) {
            return false;
        }
        
        return in_array($date, self::get_occurrence_dates($post_id, $parsed, $parsed), true);
    }
    
    /**
     * Finns det några förekomster kvar i serien?
     */
    public static function has_remaining_occurrences($post_id) {
        $until = self::parse_date(self::get_until($post_id));
        if (!$until) {
            return true;
        }
        
        $start = self::parse_date(get_post_meta($post_id, '_cypl_start_date', true));
        if (!$start || $until < $start) {
            return false;
        }
        
        return !empty(self::get_occurrence_dates($post_id, $start, $until));
    }
    
    /**
     * Radera en förekomst
     *
     * Returnerar 'excluded', 'truncated' eller 'empty' (serien saknar förekomster och ska slängas).
     */
    public static function delete_occurrence($post_id, $date, $mode) {
        if (!self::get_rule($post_id)) {
            return 'empty';
        }
        
        if (!self::is_occurrence($post_id, $date)) {
            return new WP_Error('cypl_invalid_occurrence', __('The occurrence does not exist.', 'circular-year-planner'), array('status' => 404));
        }
        
        if ('future' === $mode) {
            $until = self::parse_date($date)->modify('-1 day')->format('Y-m-d');
            update_post_meta($post_id, self::META_UNTIL, $until);
            
            $exdates = array_filter(self::get_exdates($post_id), function ($exdate) use ($until) {
                return $exdate <= $until;
            });
            update_post_meta($post_id, self::META_EXDATES, array_values($exdates));
            $result = 'truncated';
        } else {
            $exdates = self::get_exdates($post_id);
            $exdates[] = $date;
            $exdates = array_values(array_unique($exdates));
            sort($exdates);
            update_post_meta($post_id, self::META_EXDATES, $exdates);
            $result = 'excluded';
        }
        
        return self::has_remaining_occurrences($post_id) ? $result : 'empty';
    }
    
    /**
     * Expandera regeln till förekomster med start inom [$lo, $hi]
     */
    private static function expand($rule, $start, $lo, $hi) {
        $interval = $rule['interval'];
        $dates = array();
        
        switch ($rule['freq']) {
            case 'daily':
                $steps = (int) ceil(self::days_between($start, $lo) / $interval);
                $date = $start->modify('+' . ($steps * $interval) . ' days');
                while ($date <= $hi) {
                    $dates[] = $date;
                    $date = $date->modify('+' . $interval . ' days');
                }
                break;
                
            case 'weekly':
                $series_monday = $start->modify('-' . ((int) $start->format('N') - 1) . ' days');
                $weeks = intdiv(self::days_between($series_monday, $lo), 7);
                $monday = $series_monday->modify('+' . (intdiv($weeks, $interval) * $interval * 7) . ' days');
                while ($monday <= $hi) {
                    $date = $monday->modify('+' . ($rule['weekday'] - 1) . ' days');
                    if ($date >= $lo && $date <= $hi) {
                        $dates[] = $date;
                    }
                    $monday = $monday->modify('+' . ($interval * 7) . ' days');
                }
                break;
                
            case 'monthly':
                $base = self::month_index($start);
                $index = $base + (int) ceil(max(0, self::month_index($lo) - $base) / $interval) * $interval;
                $last = self::month_index($hi);
                for (; $index <= $last; $index += $interval) {
                    $year = intdiv($index, 12);
                    $month = $index % 12 + 1;
                    $day = 'onthe' === $rule['mode']
                        ? self::nth_day($year, $month, $rule['setpos'], $rule['day_kind'])
                        : self::valid_day($year, $month, $rule['monthday']);
                    self::push_if_in_range($dates, $year, $month, $day, $lo, $hi);
                }
                break;
                
            case 'yearly':
                $base = (int) $start->format('Y');
                $year = $base + (int) ceil(max(0, (int) $lo->format('Y') - $base) / $interval) * $interval;
                $last = (int) $hi->format('Y');
                for (; $year <= $last; $year += $interval) {
                    $day = $rule['onthe']
                        ? self::nth_day($year, $rule['month'], $rule['setpos'], $rule['day_kind'])
                        : self::valid_day($year, $rule['month'], (int) $start->format('j'));
                    self::push_if_in_range($dates, $year, $rule['month'], $day, $lo, $hi);
                }
                break;
        }
        
        return $dates;
    }
    
    private static function push_if_in_range(&$dates, $year, $month, $day, $lo, $hi) {
        if (!$day) {
            return;
        }
        
        $date = self::make_date($year, $month, $day);
        if ($date >= $lo && $date <= $hi) {
            $dates[] = $date;
        }
    }
    
    private static function month_index($date) {
        return (int) $date->format('Y') * 12 + (int) $date->format('n') - 1;
    }
    
    private static function make_date($year, $month, $day) {
        return self::parse_date(sprintf('%04d-%02d-%02d', $year, $month, $day));
    }
    
    private static function days_in_month($year, $month) {
        return (int) self::make_date($year, $month, 1)->format('t');
    }
    
    /**
     * Dagen om den finns i månaden (t.ex. ingen 31:a i april)
     */
    private static function valid_day($year, $month, $day) {
        return $day <= self::days_in_month($year, $month) ? $day : null;
    }
    
    /**
     * Dag i månaden för t.ex. "andra tisdagen" eller "sista veckodagen"
     */
    private static function nth_day($year, $month, $setpos, $day_kind) {
        $days_in_month = self::days_in_month($year, $month);
        $first_weekday = (int) self::make_date($year, $month, 1)->format('N');
        $matches = array();
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $weekday = ($first_weekday + $day - 2) % 7 + 1;
            if (self::day_matches($weekday, $day_kind)) {
                $matches[] = $day;
            }
        }
        
        $index = $setpos > 0 ? $setpos - 1 : count($matches) + $setpos;
        return isset($matches[$index]) ? $matches[$index] : null;
    }
    
    private static function day_matches($weekday, $day_kind) {
        switch ($day_kind) {
            case 'day':
                return true;
            case 'weekday':
                return $weekday <= 5;
            case 'weekend':
                return $weekday >= 6;
            default:
                return (int) $day_kind === $weekday;
        }
    }
    
    /**
     * Start- och slutdatum för ett verksamhetsår, t.ex. "2024" eller "2024/2025"
     */
    public static function get_fiscal_year_range($fiscal_year) {
        $start_year = (int) explode('/', (string) $fiscal_year)[0];
        if ($start_year < 1) {
            return null;
        }
        
        return self::fiscal_year_range_from($start_year);
    }
    
    public static function get_current_fiscal_year_range() {
        $today = self::parse_date(current_time('Y-m-d'));
        $range = self::fiscal_year_range_from((int) $today->format('Y'));
        
        if ($today < $range[0]) {
            $range = self::fiscal_year_range_from((int) $today->format('Y') - 1);
        }
        
        return $range;
    }
    
    private static function fiscal_year_range_from($start_year) {
        list($month, $day) = array_map('intval', explode('-', get_option('cypl_fiscal_year_start', '01-01')));
        $start = self::make_date($start_year, $month, $day);
        
        return array($start, $start->modify('+1 year')->modify('-1 day'));
    }
    
    /**
     * Etiketter för ordningstal; null markerar avskiljaren i menyn
     */
    public static function setpos_labels() {
        return array(
            1 => __('first', 'circular-year-planner'),
            2 => __('second', 'circular-year-planner'),
            3 => __('third', 'circular-year-planner'),
            4 => __('fourth', 'circular-year-planner'),
            5 => __('fifth', 'circular-year-planner'),
            'separator' => null,
            -2 => __('second to last', 'circular-year-planner'),
            -1 => __('last', 'circular-year-planner'),
        );
    }
    
    /**
     * Etiketter för dagtyper; null markerar avskiljaren i menyn
     */
    public static function day_kind_labels() {
        $labels = array();
        for ($weekday = 1; $weekday <= 7; $weekday++) {
            $labels[(string) $weekday] = self::weekday_name($weekday);
        }
        $labels['separator'] = null;
        $labels['day'] = __('day', 'circular-year-planner');
        $labels['weekday'] = __('weekday', 'circular-year-planner');
        $labels['weekend'] = __('weekend day', 'circular-year-planner');
        
        return $labels;
    }
    
    /**
     * Veckodagens namn, 1 = måndag … 7 = söndag
     */
    public static function weekday_name($weekday, $abbrev = false) {
        global $wp_locale;
        $name = $wp_locale->get_weekday($weekday % 7);
        return $abbrev ? $wp_locale->get_weekday_abbrev($name) : $name;
    }
    
    /**
     * Läsbar sammanfattning av regeln, t.ex. "Every 2 weeks on Monday"
     */
    public static function describe($rule) {
        global $wp_locale;
        
        if (!$rule) {
            return '';
        }
        
        $interval = $rule['interval'];
        
        switch ($rule['freq']) {
            case 'daily':
                return 1 === $interval
                    ? __('Every day', 'circular-year-planner')
                    /* translators: %d: number of days between occurrences */
                    : sprintf(_n('Every %d day', 'Every %d days', $interval, 'circular-year-planner'), $interval);
                
            case 'weekly':
                $frequency = 1 === $interval
                    ? __('Every week', 'circular-year-planner')
                    /* translators: %d: number of weeks between occurrences */
                    : sprintf(_n('Every %d week', 'Every %d weeks', $interval, 'circular-year-planner'), $interval);
                /* translators: 1: frequency such as "Every 2 weeks", 2: weekday name */
                return sprintf(__('%1$s on %2$s', 'circular-year-planner'), $frequency, self::weekday_name($rule['weekday']));
                
            case 'monthly':
                $frequency = 1 === $interval
                    ? __('Every month', 'circular-year-planner')
                    /* translators: %d: number of months between occurrences */
                    : sprintf(_n('Every %d month', 'Every %d months', $interval, 'circular-year-planner'), $interval);
                if ('onthe' === $rule['mode']) {
                    /* translators: 1: frequency such as "Every 2 months", 2: position such as "the first Monday" */
                    return sprintf(__('%1$s on %2$s', 'circular-year-planner'), $frequency, self::describe_position($rule));
                }
                /* translators: 1: frequency such as "Every 2 months", 2: day of month */
                return sprintf(__('%1$s on day %2$d', 'circular-year-planner'), $frequency, $rule['monthday']);
                
            case 'yearly':
                $frequency = 1 === $interval
                    ? __('Every year', 'circular-year-planner')
                    /* translators: %d: number of years between occurrences */
                    : sprintf(_n('Every %d year', 'Every %d years', $interval, 'circular-year-planner'), $interval);
                $month = $wp_locale->get_month($rule['month']);
                if ($rule['onthe']) {
                    /* translators: 1: frequency such as "Every 2 years", 2: month name, 3: position such as "the first Monday" */
                    return sprintf(__('%1$s in %2$s on %3$s', 'circular-year-planner'), $frequency, $month, self::describe_position($rule));
                }
                /* translators: 1: frequency such as "Every 2 years", 2: month name */
                return sprintf(__('%1$s in %2$s', 'circular-year-planner'), $frequency, $month);
        }
        
        return '';
    }
    
    private static function describe_position($rule) {
        $setpos_labels = self::setpos_labels();
        $day_kind_labels = self::day_kind_labels();
        
        return sprintf(
            /* translators: 1: ordinal such as "first" or "last", 2: day such as "Monday" or "weekday" */
            __('the %1$s %2$s', 'circular-year-planner'),
            $setpos_labels[$rule['setpos']],
            $day_kind_labels[$rule['day_kind']]
        );
    }
}
