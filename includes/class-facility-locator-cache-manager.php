<?php

/**
 * Cache Management System for Facility Locator Plugin
 * Centralized cache management with different cache strategies
 */
class Facility_Locator_Cache_Manager
{
    // Cache groups
    const CACHE_GROUP_FACILITIES = 'facility_locator_facilities';
    const CACHE_GROUP_TAXONOMIES = 'facility_locator_taxonomies';
    const CACHE_GROUP_FRONTEND = 'facility_locator_frontend';
    
    // Cache durations
    const CACHE_DURATION_SHORT = 300;    // 5 minutes
    const CACHE_DURATION_MEDIUM = 1800;  // 30 minutes
    const CACHE_DURATION_LONG = 3600;    // 1 hour
    const CACHE_DURATION_EXTENDED = 7200; // 2 hours
    
    // Cache version
    const CACHE_VERSION = '1.0';

    /**
     * Initialize cache management
     */
    public static function init()
    {
        // Hook into WordPress events that should clear caches
        add_action('save_post', array(__CLASS__, 'clear_related_caches'));
        add_action('deleted_post', array(__CLASS__, 'clear_related_caches'));
        add_action('switch_theme', array(__CLASS__, 'clear_all_caches'));
        add_action('upgrader_process_complete', array(__CLASS__, 'clear_all_caches'));
        
        // Hook into plugin-specific events
        add_action('facility_locator_facility_saved', array(__CLASS__, 'clear_facility_caches'));
        add_action('facility_locator_facility_deleted', array(__CLASS__, 'clear_facility_caches'));
        add_action('facility_locator_taxonomy_saved', array(__CLASS__, 'clear_taxonomy_caches'));
        add_action('facility_locator_taxonomy_deleted', array(__CLASS__, 'clear_taxonomy_caches'));
        add_action('facility_locator_settings_updated', array(__CLASS__, 'clear_frontend_caches'));
    }

    /**
     * Get cached data with fallback
     */
    public static function get($key, $group = self::CACHE_GROUP_FACILITIES, $default = false)
    {
        $versioned_key = self::get_versioned_key($key);
        
        // Try object cache first (memcache/redis if available)
        $data = wp_cache_get($versioned_key, $group);
        
        if ($data === false) {
            // Fallback to transients for longer-term storage
            $transient_key = self::get_transient_key($group, $key);
            $data = get_transient($transient_key);
        }
        
        return $data !== false ? $data : $default;
    }

    /**
     * Set cached data with multiple cache layers
     */
    public static function set($key, $data, $group = self::CACHE_GROUP_FACILITIES, $duration = self::CACHE_DURATION_MEDIUM)
    {
        $versioned_key = self::get_versioned_key($key);
        
        // Set in object cache (fast access)
        wp_cache_set($versioned_key, $data, $group, $duration);
        
        // Also set in transients for persistence across requests
        $transient_key = self::get_transient_key($group, $key);
        set_transient($transient_key, $data, $duration);
        
        return true;
    }

    /**
     * Delete cached data from all cache layers
     */
    public static function delete($key, $group = self::CACHE_GROUP_FACILITIES)
    {
        $versioned_key = self::get_versioned_key($key);
        
        // Delete from object cache
        wp_cache_delete($versioned_key, $group);
        
        // Delete from transients
        $transient_key = self::get_transient_key($group, $key);
        delete_transient($transient_key);
        
        return true;
    }

    /**
     * Clear all caches for a specific group
     */
    public static function clear_group($group)
    {
        // Flush object cache group
        wp_cache_flush_group($group);
        
        // Clear related transients
        self::clear_transients_by_pattern($group);
        
        if (WP_DEBUG) {
            error_log("Facility Locator: Cleared cache group: {$group}");
        }
    }

    /**
     * Clear all plugin caches
     */
    public static function clear_all_caches()
    {
        self::clear_group(self::CACHE_GROUP_FACILITIES);
        self::clear_group(self::CACHE_GROUP_TAXONOMIES);
        self::clear_group(self::CACHE_GROUP_FRONTEND);
        
        // Clear specific transients
        delete_transient('facility_locator_available_taxonomies');
        delete_transient('facility_locator_form_steps');
        
        if (WP_DEBUG) {
            error_log('Facility Locator: All caches cleared');
        }
    }

    /**
     * Clear facility-related caches
     */
    public static function clear_facility_caches()
    {
        self::clear_group(self::CACHE_GROUP_FACILITIES);
        self::clear_group(self::CACHE_GROUP_FRONTEND);
        
        // Clear specific facility-related transients
        delete_transient('facility_locator_available_taxonomies');
        
        if (WP_DEBUG) {
            error_log('Facility Locator: Facility caches cleared');
        }
    }

    /**
     * Clear taxonomy-related caches
     */
    public static function clear_taxonomy_caches()
    {
        self::clear_group(self::CACHE_GROUP_TAXONOMIES);
        self::clear_group(self::CACHE_GROUP_FRONTEND);
        
        // Clear specific taxonomy-related transients
        delete_transient('facility_locator_available_taxonomies');
        
        if (WP_DEBUG) {
            error_log('Facility Locator: Taxonomy caches cleared');
        }
    }

    /**
     * Clear frontend-specific caches
     */
    public static function clear_frontend_caches()
    {
        self::clear_group(self::CACHE_GROUP_FRONTEND);
        
        // Clear frontend transients
        delete_transient('facility_locator_available_taxonomies');
        delete_transient('facility_locator_form_steps');
        
        if (WP_DEBUG) {
            error_log('Facility Locator: Frontend caches cleared');
        }
    }

    /**
     * Clear related caches on general WordPress events
     */
    public static function clear_related_caches()
    {
        // Clear frontend caches when content changes
        self::clear_frontend_caches();
    }

    /**
     * Get cache statistics for admin display
     */
    public static function get_cache_stats()
    {
        global $wpdb;
        
        $stats = array(
            'object_cache_available' => wp_using_ext_object_cache(),
            'transients_count' => 0,
            'cache_groups' => array(
                self::CACHE_GROUP_FACILITIES,
                self::CACHE_GROUP_TAXONOMIES,
                self::CACHE_GROUP_FRONTEND
            )
        );
        
        // Count plugin-related transients
        $transient_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_facility_locator_%'"
        );
        
        $stats['transients_count'] = intval($transient_count);
        
        return $stats;
    }

    /**
     * Optimize database for better cache performance
     */
    public static function optimize_database()
    {
        global $wpdb;
        
        // Add indexes if they don't exist
        $facilities_table = $wpdb->prefix . 'facility_locator_facilities';
        $taxonomies_table = $wpdb->prefix . 'facility_locator_taxonomies';
        
        // Check and add missing indexes
        $indexes_to_add = array(
            $facilities_table => array(
                'lat_lng_idx' => '(lat, lng)',
                'name_idx' => '(name)',
            ),
            $taxonomies_table => array(
                'taxonomy_type_idx' => '(taxonomy_type)',
                'name_idx' => '(name)',
            )
        );
        
        foreach ($indexes_to_add as $table => $indexes) {
            foreach ($indexes as $index_name => $columns) {
                $existing_index = $wpdb->get_var(
                    $wpdb->prepare(
                        "SHOW INDEX FROM {$table} WHERE Key_name = %s",
                        $index_name
                    )
                );
                
                if (!$existing_index) {
                    $wpdb->query("ALTER TABLE {$table} ADD INDEX {$index_name} {$columns}");
                    
                    if (WP_DEBUG) {
                        error_log("Facility Locator: Added index {$index_name} to {$table}");
                    }
                }
            }
        }
    }

    /**
     * Generate versioned cache key
     */
    private static function get_versioned_key($key)
    {
        return self::CACHE_VERSION . '_' . $key;
    }

    /**
     * Generate transient key
     */
    private static function get_transient_key($group, $key)
    {
        return 'facility_locator_' . $group . '_' . md5($key);
    }

    /**
     * Clear transients by pattern
     */
    private static function clear_transients_by_pattern($pattern)
    {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                 WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_facility_locator_' . $pattern . '_%',
                '_transient_timeout_facility_locator_' . $pattern . '_%'
            )
        );
    }

    /**
     * Warm up critical caches
     */
    public static function warm_up_caches()
    {
        if (WP_DEBUG) {
            error_log('Facility Locator: Warming up caches...');
        }
        
        // Warm up facilities cache
        $facilities_instance = new Facility_Locator_Facilities();
        $facilities_instance->get_facilities();
        
        // Warm up taxonomy cache
        $taxonomy_manager = new Facility_Locator_Taxonomy_Manager();
        $taxonomy_manager->get_all_for_filters();
        
        if (WP_DEBUG) {
            error_log('Facility Locator: Cache warm-up complete');
        }
    }

    /**
     * Schedule cache maintenance
     */
    public static function schedule_cache_maintenance()
    {
        if (!wp_next_scheduled('facility_locator_cache_maintenance')) {
            wp_schedule_event(time(), 'daily', 'facility_locator_cache_maintenance');
        }
        
        add_action('facility_locator_cache_maintenance', array(__CLASS__, 'daily_cache_maintenance'));
    }

    /**
     * Daily cache maintenance
     */
    public static function daily_cache_maintenance()
    {
        // Clear expired transients
        self::clear_expired_transients();
        
        // Optimize database
        self::optimize_database();
        
        // Warm up critical caches during low-traffic hours
        self::warm_up_caches();
        
        if (WP_DEBUG) {
            error_log('Facility Locator: Daily cache maintenance completed');
        }
    }

    /**
     * Clear expired transients
     */
    private static function clear_expired_transients()
    {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_facility_locator_%' 
             AND option_name NOT LIKE '_transient_timeout_%'
             AND NOT EXISTS (
                 SELECT 1 FROM {$wpdb->options} t2 
                 WHERE t2.option_name = CONCAT('_transient_timeout_', SUBSTRING(option_name, 12))
                 AND t2.option_value > UNIX_TIMESTAMP()
             )"
        );
    }
}