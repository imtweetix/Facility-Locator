<?php

/**
 * Handle CRUD operations for facilities with new taxonomy system and caching
 * Performance optimized with multiple cache layers
 */
class Facility_Locator_Facilities
{
    private $table_name;
    private $taxonomy_manager;

    // Cache constants
    const CACHE_GROUP = 'facility_locator';
    const CACHE_EXPIRATION = 3600; // 1 hour
    const CACHE_VERSION = '1.0';

    // Cache keys
    const CACHE_KEY_ALL_FACILITIES = 'all_facilities';
    const CACHE_KEY_FACILITY_PREFIX = 'facility_';
    const CACHE_KEY_FILTERED_PREFIX = 'filtered_';
    const CACHE_KEY_TAXONOMY_FILTERS = 'taxonomy_filters';

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'facility_locator_facilities';
        $this->taxonomy_manager = new Facility_Locator_Taxonomy_Manager();
    }

    /**
     * Create the database table on plugin activation
     */
    public static function create_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'facility_locator_facilities';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address varchar(255) NOT NULL,
            lat decimal(10,8) NOT NULL,
            lng decimal(11,8) NOT NULL,
            phone varchar(50),
            website varchar(255),
            taxonomies text,
            custom_pin_image varchar(255),
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY lat_lng (lat, lng),
            KEY name_idx (name),
            FULLTEXT KEY search_idx (name, address, description)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Create taxonomies table
        Facility_Locator_Base_Taxonomy::create_table();

        if (WP_DEBUG) {
            error_log('Facility Locator: Tables creation completed');
        }
    }

    /**
     * Get all facilities with optional filtering and caching
     */
    public function get_facilities($args = array())
    {
        // Create cache key based on arguments
        $cache_key = empty($args) ?
            self::CACHE_KEY_ALL_FACILITIES :
            self::CACHE_KEY_FILTERED_PREFIX . md5(serialize($args));

        // Try to get from cache first
        $cached_facilities = $this->get_cache($cache_key);
        if ($cached_facilities !== false) {
            return $cached_facilities;
        }

        global $wpdb;

        // Optimized query with proper indexing
        $query = "SELECT * FROM {$this->table_name}";
        $where_clauses = array();
        $query_params = array();

        // Build WHERE clauses for taxonomy filtering
        if (!empty($args)) {
            foreach ($args as $taxonomy_type => $taxonomy_ids) {
                if (!empty($taxonomy_ids) && is_array($taxonomy_ids)) {
                    $taxonomy_conditions = array();
                    foreach ($taxonomy_ids as $taxonomy_id) {
                        $taxonomy_conditions[] = "taxonomies LIKE %s";
                        $query_params[] = '%"' . $taxonomy_type . '"%' . intval($taxonomy_id) . '%';
                    }
                    if (!empty($taxonomy_conditions)) {
                        $where_clauses[] = '(' . implode(' OR ', $taxonomy_conditions) . ')';
                    }
                }
            }
        }

        // Add WHERE clause if needed
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }

        $query .= " ORDER BY name ASC";

        // Prepare and execute query
        if (!empty($query_params)) {
            $prepared_query = $wpdb->prepare($query, $query_params);
        } else {
            $prepared_query = $query;
        }

        $facilities = $wpdb->get_results($prepared_query);

        // Format data and add taxonomy details
        if ($facilities) {
            foreach ($facilities as &$facility) {
                $facility = $this->format_facility_data($facility);
            }
        }

        // Cache the results
        $this->set_cache($cache_key, $facilities);

        return $facilities;
    }

    /**
     * Get a single facility by ID with caching
     */
    public function get_facility($id)
    {
        $cache_key = self::CACHE_KEY_FACILITY_PREFIX . $id;

        // Try cache first
        $cached_facility = $this->get_cache($cache_key);
        if ($cached_facility !== false) {
            return $cached_facility;
        }

        global $wpdb;

        $facility = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id)
        );

        if ($facility) {
            $facility = $this->format_facility_data($facility);

            // Cache the individual facility
            $this->set_cache($cache_key, $facility);
        }

        return $facility;
    }

    /**
     * Add a new facility with cache invalidation
     */
    public function add_facility($data)
    {
        global $wpdb;

        if (WP_DEBUG) {
            error_log('Facility Locator: Adding new facility');
        }

        $prepared_data = $this->prepare_facility_data($data);
        $result = $wpdb->insert($this->table_name, $prepared_data);

        if ($result !== false) {
            $new_id = $wpdb->insert_id;

            // Invalidate relevant caches
            $this->invalidate_facility_caches();

            return $new_id;
        }

        return false;
    }

    /**
     * Update an existing facility with cache invalidation
     */
    public function update_facility($id, $data)
    {
        global $wpdb;

        if (WP_DEBUG) {
            error_log('Facility Locator: Updating facility ID: ' . $id);
        }

        $prepared_data = $this->prepare_facility_data($data);
        $result = $wpdb->update(
            $this->table_name,
            $prepared_data,
            array('id' => $id),
            null,
            array('%d')
        );

        if ($result !== false) {
            // Invalidate caches for this facility and all facilities
            $this->invalidate_facility_caches($id);
            return true;
        }

        return false;
    }

    /**
     * Delete a facility with cache invalidation
     */
    public function delete_facility($id)
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );

        if ($result !== false) {
            // Invalidate all relevant caches
            $this->invalidate_facility_caches($id);
            return true;
        }

        return false;
    }

    /**
     * Get all available taxonomy options for filters with caching
     */
    public function get_taxonomy_filters()
    {
        // Try cache first
        $cached_filters = $this->get_cache(self::CACHE_KEY_TAXONOMY_FILTERS);
        if ($cached_filters !== false) {
            return $cached_filters;
        }

        $filters = $this->taxonomy_manager->get_all_for_filters();

        // Cache the filters
        $this->set_cache(self::CACHE_KEY_TAXONOMY_FILTERS, $filters);

        return $filters;
    }

    /**
     * Prepare facility data for database with validation
     */
    private function prepare_facility_data($data)
    {
        $prepared = array(
            'name' => sanitize_text_field($data['name']),
            'address' => sanitize_text_field($data['address']),
            'lat' => floatval($data['lat']),
            'lng' => floatval($data['lng']),
            'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : '',
            'website' => isset($data['website']) ? esc_url_raw($data['website']) : '',
            'custom_pin_image' => isset($data['custom_pin_image']) ? esc_url_raw($data['custom_pin_image']) : '',
            'description' => isset($data['description']) ? wp_kses_post($data['description']) : '',
        );

        // Handle taxonomies with validation
        $taxonomies = array();
        $taxonomy_types = $this->taxonomy_manager->get_taxonomy_types();

        foreach ($taxonomy_types as $type) {
            if (isset($data[$type]) && is_array($data[$type])) {
                $taxonomy_ids = array_map('intval', array_filter($data[$type]));
                if (!empty($taxonomy_ids)) {
                    $taxonomies[$type] = $taxonomy_ids;
                }
            }
        }

        $prepared['taxonomies'] = json_encode($taxonomies);

        return $prepared;
    }

    /**
     * Format facility data after retrieval from database with optimization
     */
    private function format_facility_data($facility)
    {
        // Use static cache for taxonomy manager to avoid repeated instantiation
        static $taxonomy_types = null;
        if ($taxonomy_types === null) {
            $taxonomy_types = $this->taxonomy_manager->get_taxonomy_types();
        }

        // Decode taxonomies
        $taxonomies = json_decode($facility->taxonomies, true);
        if (!is_array($taxonomies)) {
            $taxonomies = array();
        }

        // Add individual taxonomy properties for backward compatibility and display
        foreach ($taxonomy_types as $type) {
            $facility->{$type} = isset($taxonomies[$type]) ? $taxonomies[$type] : array();

            // Add taxonomy details for display (with caching)
            if (!empty($facility->{$type})) {
                $items = $this->get_cached_taxonomy_items($type, $facility->{$type});
                $facility->{$type . '_details'} = $items;
                $facility->{$type . '_names'} = array_map(function ($item) {
                    return $item->name;
                }, $items);
            } else {
                $facility->{$type . '_details'} = array();
                $facility->{$type . '_names'} = array();
            }
        }

        // Add legacy properties for backward compatibility
        $facility->categories = $facility->levels_of_care_names;
        $facility->attributes = $facility->features_names;

        return $facility;
    }

    /**
     * Get taxonomy items with caching
     */
    private function get_cached_taxonomy_items($type, $ids)
    {
        $cache_key = "taxonomy_items_{$type}_" . md5(serialize($ids));

        $cached_items = $this->get_cache($cache_key);
        if ($cached_items !== false) {
            return $cached_items;
        }

        $items = $this->taxonomy_manager->get_items_by_ids($type, $ids);

        // Cache for shorter time since taxonomies change less frequently
        $this->set_cache($cache_key, $items, self::CACHE_EXPIRATION * 2);

        return $items;
    }

    /**
     * Cache management methods
     */
    private function get_cache($key)
    {
        $versioned_key = $this->get_versioned_cache_key($key);
        return wp_cache_get($versioned_key, self::CACHE_GROUP);
    }

    private function set_cache($key, $data, $expiration = null)
    {
        if ($expiration === null) {
            $expiration = self::CACHE_EXPIRATION;
        }

        $versioned_key = $this->get_versioned_cache_key($key);
        return wp_cache_set($versioned_key, $data, self::CACHE_GROUP, $expiration);
    }

    private function delete_cache($key)
    {
        $versioned_key = $this->get_versioned_cache_key($key);
        return wp_cache_delete($versioned_key, self::CACHE_GROUP);
    }

    private function get_versioned_cache_key($key)
    {
        return self::CACHE_VERSION . '_' . $key;
    }

    /**
     * Invalidate facility-related caches
     */
    private function invalidate_facility_caches($facility_id = null)
    {
        // Delete all facilities cache
        $this->delete_cache(self::CACHE_KEY_ALL_FACILITIES);

        // Delete specific facility cache if ID provided
        if ($facility_id) {
            $this->delete_cache(self::CACHE_KEY_FACILITY_PREFIX . $facility_id);
        }

        // Delete taxonomy filters cache
        $this->delete_cache(self::CACHE_KEY_TAXONOMY_FILTERS);

        // Flush all filtered results cache (pattern-based)
        wp_cache_flush_group(self::CACHE_GROUP);

        if (WP_DEBUG) {
            error_log('Facility Locator: Cache invalidated for facility operations');
        }
    }

    /**
     * Get categories for backward compatibility with caching
     */
    public function get_categories()
    {
        $cache_key = 'legacy_categories';
        $cached_categories = $this->get_cache($cache_key);

        if ($cached_categories !== false) {
            return $cached_categories;
        }

        $levels_taxonomy = $this->taxonomy_manager->get_taxonomy('levels_of_care');
        $categories = $levels_taxonomy ? $levels_taxonomy->get_all() : array();

        $this->set_cache($cache_key, $categories, self::CACHE_EXPIRATION * 2);

        return $categories;
    }

    /**
     * Get attributes for backward compatibility with caching
     */
    public function get_attributes()
    {
        $cache_key = 'legacy_attributes';
        $cached_attributes = $this->get_cache($cache_key);

        if ($cached_attributes !== false) {
            return $cached_attributes;
        }

        $features_taxonomy = $this->taxonomy_manager->get_taxonomy('features');
        $attributes = $features_taxonomy ? $features_taxonomy->get_all() : array();

        $this->set_cache($cache_key, $attributes, self::CACHE_EXPIRATION * 2);

        return $attributes;
    }

    /**
     * Clear all plugin caches (for maintenance)
     */
    public function clear_all_caches()
    {
        wp_cache_flush_group(self::CACHE_GROUP);

        if (WP_DEBUG) {
            error_log('Facility Locator: All caches cleared');
        }
    }
}
