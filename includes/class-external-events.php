<?php
/**
 * Händelser som skapas och hålls uppdaterade av andra plugin på samma sajt
 *
 * Funktionerna anropas från PHP. De registreras inte i REST eller admin-ajax, så
 * den anropande koden ansvarar för behörighetskontrollen.
 */

if (!defined('ABSPATH')) {
    exit;
}

class CYPL_External_Events {
    
    const META_SOURCE = '_cypl_link_source';
    const META_ID = '_cypl_link_id';
    
    /**
     * Händelsetyper som id => namn, i inställningarnas ordning
     */
    public static function get_event_types() {
        $types = array();
        foreach ((array) get_option('cypl_event_types', array()) as $id => $type) {
            $types[(int) $id] = isset($type['name']) ? (string) $type['name'] : '';
        }
        return $types;
    }
    
    /**
     * Skapa eller uppdatera en endagshändelse kopplad till en extern post
     *
     * @param string     $source      Kort nyckel för det anropande pluginet, t.ex. "my-plugin".
     * @param string|int $external_id Postens id i det anropande pluginet.
     * @param array      $args        title, date (YYYY-MM-DD) och event_type (händelsetypens id).
     * @return int|WP_Error Händelsens post-id.
     */
    public static function upsert($source, $external_id, $args) {
        $source = sanitize_key($source);
        $external_id = sanitize_text_field((string) $external_id);
        $args = wp_parse_args($args, array('title' => '', 'date' => '', 'event_type' => null));
        
        $title = sanitize_text_field($args['title']);
        $date = is_string($args['date']) ? $args['date'] : '';
        $event_type = is_numeric($args['event_type']) ? (int) $args['event_type'] : null;
        
        if ('' === $source || '' === $external_id) {
            return new WP_Error('cypl_invalid_link', __('A source and an external ID are required.', 'circular-year-planner'));
        }
        if ('' === $title) {
            return new WP_Error('cypl_invalid_title', __('The event needs a title.', 'circular-year-planner'));
        }
        if (!CYPL_Recurrence::parse_date($date)) {
            return new WP_Error('cypl_invalid_date', __('The date must be in the format YYYY-MM-DD.', 'circular-year-planner'));
        }
        if (null === $event_type || !array_key_exists($event_type, self::get_event_types())) {
            return new WP_Error('cypl_invalid_event_type', __('The event type does not exist.', 'circular-year-planner'));
        }
        
        $post_id = self::find($source, $external_id);
        $postarr = array(
            'post_type' => 'cypl_event',
            'post_title' => $title,
            'post_status' => 'publish',
        );
        
        if ($post_id) {
            $postarr['ID'] = $post_id;
            $result = wp_update_post($postarr, true);
        } else {
            $result = wp_insert_post($postarr, true);
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $post_id = (int) $result;
        update_post_meta($post_id, '_cypl_start_date', $date);
        update_post_meta($post_id, '_cypl_end_date', $date);
        update_post_meta($post_id, '_cypl_event_type', (string) $event_type);
        update_post_meta($post_id, self::META_SOURCE, $source);
        update_post_meta($post_id, self::META_ID, $external_id);
        CYPL_Recurrence::clear($post_id);
        
        return $post_id;
    }
    
    /**
     * Ta bort händelsen som är kopplad till en extern post
     */
    public static function delete($source, $external_id) {
        $post_id = self::find(sanitize_key($source), sanitize_text_field((string) $external_id));
        if ($post_id) {
            wp_delete_post($post_id, true);
        }
        return (bool) $post_id;
    }
    
    /**
     * Ta bort alla händelser från en källa
     *
     * @return int Antal borttagna händelser.
     */
    public static function delete_all($source) {
        $post_ids = self::query_ids(sanitize_key($source));
        foreach ($post_ids as $post_id) {
            wp_delete_post($post_id, true);
        }
        return count($post_ids);
    }
    
    /**
     * Externa id för alla händelser från en källa, som post-id => externt id
     */
    public static function get_linked_ids($source) {
        $linked = array();
        foreach (self::query_ids(sanitize_key($source)) as $post_id) {
            $linked[$post_id] = (string) get_post_meta($post_id, self::META_ID, true);
        }
        return $linked;
    }
    
    private static function find($source, $external_id) {
        if ('' === $source || '' === $external_id) {
            return 0;
        }
        $post_ids = self::query_ids($source, $external_id);
        return $post_ids ? (int) $post_ids[0] : 0;
    }
    
    private static function query_ids($source, $external_id = null) {
        if ('' === $source) {
            return array();
        }
        
        $meta_query = array(
            array('key' => self::META_SOURCE, 'value' => $source),
        );
        if (null !== $external_id) {
            $meta_query[] = array('key' => self::META_ID, 'value' => $external_id);
        }
        
        $query = new WP_Query(array(
            'post_type' => 'cypl_event',
            'post_status' => array('publish', 'pending', 'draft', 'future', 'private', 'trash'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'orderby' => 'ID',
            'order' => 'ASC',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Körs bara när ett annat plugin synkar.
            'meta_query' => $meta_query,
        ));
        
        return array_map('intval', $query->posts);
    }
}

/**
 * Händelsetyper i Planly som id => namn
 *
 * @return array
 */
function cypl_get_event_types() {
    return CYPL_External_Events::get_event_types();
}

/**
 * Skapa eller uppdatera en endagshändelse som ägs av ett annat plugin
 *
 * @param string     $source      Kort nyckel för det anropande pluginet.
 * @param string|int $external_id Postens id i det anropande pluginet.
 * @param array      $args        title, date (YYYY-MM-DD) och event_type (händelsetypens id).
 * @return int|WP_Error
 */
function cypl_upsert_linked_event($source, $external_id, $args) {
    return CYPL_External_Events::upsert($source, $external_id, $args);
}

/**
 * Ta bort en händelse som ägs av ett annat plugin
 *
 * @return bool
 */
function cypl_delete_linked_event($source, $external_id) {
    return CYPL_External_Events::delete($source, $external_id);
}

/**
 * Ta bort alla händelser från en källa
 *
 * @return int
 */
function cypl_delete_linked_events($source) {
    return CYPL_External_Events::delete_all($source);
}

/**
 * Externa id för alla händelser från en källa, som händelsens post-id => externt id
 *
 * @return array
 */
function cypl_get_linked_events($source) {
    return CYPL_External_Events::get_linked_ids($source);
}
