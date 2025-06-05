<?php

/**
 * Comprehensive Taxonomies System for Facility Locator with Caching
 * Performance optimized with cache layers and database optimization
 */

/**
 * Base Taxonomy Class with Caching
 * Provides common functionality for all taxonomies with performance optimization
 */
abstract class Facility_Locator_Base_Taxonomy
{
    protected $table_name;
    protected $taxonomy_type;

    // Cache constants
    const CACHE_GROUP = 'facility_locator_taxonomies';
    const CACHE_EXPIRATION = 7200; // 2 hours (taxonomies change less frequently)
    const CACHE_VERSION = '1.0';

    public function __construct($taxonomy_type)
    {
        global $wpdb;
        $this->taxonomy_type = $taxonomy_type;
        $this->table_name = $wpdb->prefix . 'facility_locator_taxonomies';

        // Ensure table exists
        $this->maybe_create_table();
    }

    /**
     * Ensure the taxonomy table exists
     */
    private function maybe_create_table()
    {
        global $wpdb;

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");

        if (!$table_exists) {
            if (WP_DEBUG) {
                error_log('Facility Locator: Taxonomy table missing, creating...');
            }
            self::create_table();

            // Force a small delay to ensure table is ready
            usleep(100000); // 0.1 seconds
        }
    }

    /**
     * Get all items for this taxonomy type with caching and error handling
     */
    public function get_all()
    {
        $cache_key = $this->get_cache_key('all_' . $this->taxonomy_type);

        // Try cache first
        $cached_items = $this->get_cache($cache_key);
        if ($cached_items !== false) {
            return $cached_items;
        }

        global $wpdb;

        // Check if table exists before querying
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
        if (!$table_exists) {
            if (WP_DEBUG) {
                error_log("Facility Locator: Taxonomy table doesn't exist for type: {$this->taxonomy_type}");
            }
            return array();
        }

        try {
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE taxonomy_type = %s ORDER BY id ASC",
                $this->taxonomy_type
            ));

            if ($items === null) {
                $items = array();
            }

            // Cache the results
            $this->set_cache($cache_key, $items);

            return $items;
        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log("Facility Locator: Error getting taxonomy items for {$this->taxonomy_type}: " . $e->getMessage());
            }
            return array();
        }
    }

    /**
     * Get a single item by ID with caching
     */
    public function get_by_id($id)
    {
        $cache_key = $this->get_cache_key($this->taxonomy_type . '_id_' . $id);

        // Try cache first
        $cached_item = $this->get_cache($cache_key);
        if ($cached_item !== false) {
            return $cached_item;
        }

        global $wpdb;

        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d AND taxonomy_type = %s",
            $id,
            $this->taxonomy_type
        ));

        if ($item) {
            $this->set_cache($cache_key, $item);
        }

        return $item;
    }

    /**
     * Get an item by slug with caching
     */
    public function get_by_slug($slug)
    {
        $cache_key = $this->get_cache_key($this->taxonomy_type . '_slug_' . $slug);

        // Try cache first
        $cached_item = $this->get_cache($cache_key);
        if ($cached_item !== false) {
            return $cached_item;
        }

        global $wpdb;

        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE slug = %s AND taxonomy_type = %s",
            $slug,
            $this->taxonomy_type
        ));

        if ($item) {
            $this->set_cache($cache_key, $item);
        }

        return $item;
    }

    /**
     * Add a new item with cache invalidation
     */
    public function add($data)
    {
        global $wpdb;

        if (empty($data['name'])) {
            return false;
        }

        $slug = $this->generate_slug($data['name']);

        $prepared_data = array(
            'taxonomy_type' => $this->taxonomy_type,
            'name' => sanitize_text_field($data['name']),
            'slug' => $slug,
            'description' => isset($data['description']) ? wp_kses_post($data['description']) : '',
        );

        $result = $wpdb->insert($this->table_name, $prepared_data);

        if ($result) {
            $new_id = $wpdb->insert_id;

            // Invalidate relevant caches
            $this->invalidate_taxonomy_caches();

            return $new_id;
        }

        return false;
    }

    /**
     * Update an existing item with cache invalidation
     */
    public function update($id, $data)
    {
        global $wpdb;

        $existing = $this->get_by_id($id);
        if (!$existing) {
            return false;
        }

        $slug = $existing->slug;
        if ($existing->name !== $data['name']) {
            $slug = $this->generate_slug($data['name'], $id);
        }

        $prepared_data = array(
            'name' => sanitize_text_field($data['name']),
            'slug' => $slug,
            'description' => isset($data['description']) ? wp_kses_post($data['description']) : '',
        );

        $result = $wpdb->update(
            $this->table_name,
            $prepared_data,
            array('id' => $id, 'taxonomy_type' => $this->taxonomy_type),
            null,
            array('%d', '%s')
        );

        if ($result !== false) {
            // Invalidate caches
            $this->invalidate_taxonomy_caches($id);
            return true;
        }

        return false;
    }

    /**
     * Delete an item with cache invalidation
     */
    public function delete($id)
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id, 'taxonomy_type' => $this->taxonomy_type),
            array('%d', '%s')
        );

        if ($result !== false) {
            // Invalidate caches
            $this->invalidate_taxonomy_caches($id);
            return true;
        }

        return false;
    }

    /**
     * Generate unique slug with caching check
     */
    private function generate_slug($name, $id = null)
    {
        $slug = sanitize_title($name);
        $original_slug = $slug;
        $counter = 1;

        while ($this->slug_exists($slug, $id)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists with caching
     */
    private function slug_exists($slug, $exclude_id = null)
    {
        $cache_key = $this->get_cache_key('slug_exists_' . $slug . '_' . ($exclude_id ?: '0'));

        // Try cache first
        $cached_result = $this->get_cache($cache_key);
        if ($cached_result !== false) {
            return $cached_result;
        }

        global $wpdb;

        $query = "SELECT id FROM {$this->table_name} WHERE slug = %s AND taxonomy_type = %s";
        $params = array($slug, $this->taxonomy_type);

        if ($exclude_id) {
            $query .= " AND id != %d";
            $params[] = $exclude_id;
        }

        $exists = $wpdb->get_var($wpdb->prepare($query, $params)) !== null;

        // Cache for shorter time since this is a check operation
        $this->set_cache($cache_key, $exists, 300); // 5 minutes

        return $exists;
    }

    /**
     * Get items for dropdown with caching
     */
    public function get_for_dropdown()
    {
        $cache_key = $this->get_cache_key('dropdown_' . $this->taxonomy_type);

        // Try cache first
        $cached_options = $this->get_cache($cache_key);
        if ($cached_options !== false) {
            return $cached_options;
        }

        $items = $this->get_all();
        $options = array();

        foreach ($items as $item) {
            $options[$item->id] = $item->name;
        }

        $this->set_cache($cache_key, $options);

        return $options;
    }

    /**
     * Get count of facilities using this taxonomy item with caching
     */
    public function get_usage_count($id)
    {
        $cache_key = $this->get_cache_key('usage_count_' . $this->taxonomy_type . '_' . $id);

        // Try cache first
        $cached_count = $this->get_cache($cache_key);
        if ($cached_count !== false) {
            return $cached_count;
        }

        global $wpdb;
        $facilities_table = $wpdb->prefix . 'facility_locator_facilities';

        // Optimized query using database search
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$facilities_table} 
             WHERE taxonomies LIKE %s AND taxonomies LIKE %s",
            '%\"' . $this->taxonomy_type . '\"%',
            '%' . $id . '%'
        ));

        $count = intval($count);

        // Cache for shorter time since usage changes with facilities
        $this->set_cache($cache_key, $count, 1800); // 30 minutes

        return $count;
    }

    /**
     * Cache management methods
     */
    private function get_cache($key)
    {
        return wp_cache_get($key, self::CACHE_GROUP);
    }

    private function set_cache($key, $data, $expiration = null)
    {
        if ($expiration === null) {
            $expiration = self::CACHE_EXPIRATION;
        }

        return wp_cache_set($key, $data, self::CACHE_GROUP, $expiration);
    }

    private function delete_cache($key)
    {
        return wp_cache_delete($key, self::CACHE_GROUP);
    }

    private function get_cache_key($suffix)
    {
        return self::CACHE_VERSION . '_' . $suffix;
    }

    /**
     * Invalidate taxonomy-related caches
     */
    private function invalidate_taxonomy_caches($item_id = null)
    {
        // Delete main taxonomy cache
        $this->delete_cache($this->get_cache_key('all_' . $this->taxonomy_type));
        $this->delete_cache($this->get_cache_key('dropdown_' . $this->taxonomy_type));

        // Delete specific item caches if ID provided
        if ($item_id) {
            $this->delete_cache($this->get_cache_key($this->taxonomy_type . '_id_' . $item_id));
            $this->delete_cache($this->get_cache_key('usage_count_' . $this->taxonomy_type . '_' . $item_id));
        }

        // Flush group to clear all related caches
        wp_cache_flush_group(self::CACHE_GROUP);

        if (WP_DEBUG) {
            error_log('Facility Locator: Taxonomy caches invalidated for ' . $this->taxonomy_type);
        }
    }

    /**
     * Get taxonomy type
     */
    public function get_taxonomy_type()
    {
        return $this->taxonomy_type;
    }

    /**
     * Get display name for taxonomy type
     */
    public function get_display_name()
    {
        $display_names = array(
            'levels_of_care' => 'Levels of Care',
            'features' => 'Features',
            'therapies' => 'Therapies',
            'environment' => 'Environment',
            'location' => 'Location',
            'insurance_providers' => 'Insurance Providers',
        );

        return isset($display_names[$this->taxonomy_type]) ? $display_names[$this->taxonomy_type] : ucfirst($this->taxonomy_type);
    }

    /**
     * Create the taxonomies table with optimized indexes
     */
    public static function create_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'facility_locator_taxonomies';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            taxonomy_type varchar(50) NOT NULL,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug_type (slug, taxonomy_type),
            KEY taxonomy_type (taxonomy_type),
            KEY name_idx (name),
            FULLTEXT KEY search_idx (name, description)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

/**
 * Levels of Care Taxonomy
 */
class Facility_Locator_Levels_Of_Care extends Facility_Locator_Base_Taxonomy
{
    public function __construct()
    {
        parent::__construct('levels_of_care');
    }
}

/**
 * Features Taxonomy (formerly Program Features)
 */
class Facility_Locator_Features extends Facility_Locator_Base_Taxonomy
{
    public function __construct()
    {
        parent::__construct('features');
    }
}

/**
 * Therapies Taxonomy
 */
class Facility_Locator_Therapies extends Facility_Locator_Base_Taxonomy
{
    public function __construct()
    {
        parent::__construct('therapies');
    }
}

/**
 * Environment Taxonomy
 */
class Facility_Locator_Environment extends Facility_Locator_Base_Taxonomy
{
    public function __construct()
    {
        parent::__construct('environment');
    }
}

/**
 * Location Taxonomy
 */
class Facility_Locator_Location extends Facility_Locator_Base_Taxonomy
{
    public function __construct()
    {
        parent::__construct('location');
    }
}

/**
 * Insurance Providers Taxonomy
 */
class Facility_Locator_Insurance_Providers extends Facility_Locator_Base_Taxonomy
{
    public function __construct()
    {
        parent::__construct('insurance_providers');
    }
}

/**
 * Taxonomy Manager with Caching
 * Provides centralized access to all taxonomies with performance optimization
 * Fixed array access issue
 */
class Facility_Locator_Taxonomy_Manager
{
    private $taxonomies = array();

    // Cache constants
    const CACHE_GROUP = 'facility_locator_taxonomy_manager';
    const CACHE_EXPIRATION = 3600; // 1 hour
    const CACHE_VERSION = '1.0';

    public function __construct()
    {
        // Initialize taxonomies array with null values for lazy loading
        $this->taxonomies = array(
            'levels_of_care' => null,
            'features' => null,
            'therapies' => null,
            'environment' => null,
            'location' => null,
            'insurance_providers' => null,
        );
    }

    /**
     * Get a specific taxonomy with lazy loading - FIXED VERSION
     */
    public function get_taxonomy($type)
    {
        // Use array_key_exists instead of isset for better reliability
        if (!array_key_exists($type, $this->taxonomies)) {
            if (WP_DEBUG) {
                error_log("Facility Locator: Type {$type} not found in taxonomies array");
            }
            return null;
        }

        // Lazy load taxonomy objects
        if ($this->taxonomies[$type] === null) {
            try {
                switch ($type) {
                    case 'levels_of_care':
                        $this->taxonomies[$type] = new Facility_Locator_Levels_Of_Care();
                        break;
                    case 'features':
                        $this->taxonomies[$type] = new Facility_Locator_Features();
                        break;
                    case 'therapies':
                        $this->taxonomies[$type] = new Facility_Locator_Therapies();
                        break;
                    case 'environment':
                        $this->taxonomies[$type] = new Facility_Locator_Environment();
                        break;
                    case 'location':
                        $this->taxonomies[$type] = new Facility_Locator_Location();
                        break;
                    case 'insurance_providers':
                        $this->taxonomies[$type] = new Facility_Locator_Insurance_Providers();
                        break;
                    default:
                        if (WP_DEBUG) {
                            error_log("Facility Locator: No class defined for taxonomy type: {$type}");
                        }
                        return null;
                }

                if (WP_DEBUG) {
                    error_log("Facility Locator: Successfully created taxonomy object for: {$type}");
                }
            } catch (Exception $e) {
                if (WP_DEBUG) {
                    error_log("Facility Locator: Error creating taxonomy {$type}: " . $e->getMessage());
                }
                $this->taxonomies[$type] = null;
                return null;
            }
        }

        return $this->taxonomies[$type];
    }

    /**
     * Get all taxonomies with lazy loading and error handling
     */
    public function get_all_taxonomies()
    {
        $loaded_taxonomies = array();

        foreach (array_keys($this->taxonomies) as $type) {
            $taxonomy = $this->get_taxonomy($type);
            if ($taxonomy !== null) {
                $loaded_taxonomies[$type] = $taxonomy;
            }
        }

        return $loaded_taxonomies;
    }

    /**
     * Get all taxonomy types
     */
    public function get_taxonomy_types()
    {
        return array_keys($this->taxonomies);
    }

    /**
     * Get all items for all taxonomies (for filtering) with caching
     */
    public function get_all_for_filters()
    {
        $cache_key = $this->get_cache_key('all_for_filters');

        // Try cache first
        $cached_filters = $this->get_cache($cache_key);
        if ($cached_filters !== false) {
            return $cached_filters;
        }

        $filters = array();

        foreach ($this->get_taxonomy_types() as $type) {
            $taxonomy = $this->get_taxonomy($type);
            if ($taxonomy) {
                $filters[$type] = $taxonomy->get_all();
            }
        }

        $this->set_cache($cache_key, $filters);

        return $filters;
    }

    /**
     * Get taxonomy items by IDs and type with caching
     */
    public function get_items_by_ids($type, $ids)
    {
        if (!array_key_exists($type, $this->taxonomies) || empty($ids)) {
            return array();
        }

        // Create cache key based on type and IDs
        $cache_key = $this->get_cache_key('items_by_ids_' . $type . '_' . md5(serialize($ids)));

        // Try cache first
        $cached_items = $this->get_cache($cache_key);
        if ($cached_items !== false) {
            return $cached_items;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'facility_locator_taxonomies';

        $id_placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE taxonomy_type = %s AND id IN ({$id_placeholders}) ORDER BY FIELD(id, {$id_placeholders})",
            array_merge(array($type), $ids, $ids)
        );

        $items = $wpdb->get_results($query);

        $this->set_cache($cache_key, $items);

        return $items;
    }

    /**
     * Validate taxonomy data structure
     */
    public function validate_taxonomy_data($taxonomies_data)
    {
        if (!is_array($taxonomies_data)) {
            return array();
        }

        $validated = array();
        $valid_types = $this->get_taxonomy_types();

        foreach ($taxonomies_data as $type => $ids) {
            if (in_array($type, $valid_types) && is_array($ids)) {
                $validated[$type] = array_map('intval', array_filter($ids, 'is_numeric'));
            }
        }

        return $validated;
    }

    /**
     * Cache management methods
     */
    private function get_cache($key)
    {
        return wp_cache_get($key, self::CACHE_GROUP);
    }

    private function set_cache($key, $data, $expiration = null)
    {
        if ($expiration === null) {
            $expiration = self::CACHE_EXPIRATION;
        }

        return wp_cache_set($key, $data, self::CACHE_GROUP, $expiration);
    }

    private function get_cache_key($suffix)
    {
        return self::CACHE_VERSION . '_' . $suffix;
    }

    /**
     * Clear all taxonomy manager caches
     */
    public function clear_all_caches()
    {
        wp_cache_flush_group(self::CACHE_GROUP);

        if (WP_DEBUG) {
            error_log('Facility Locator: All taxonomy manager caches cleared');
        }
    }

    /**
     * Debug method to test taxonomy creation
     */
    public function debug_get_taxonomy($type)
    {
        if (WP_DEBUG) {
            error_log("Debug: Checking if type {$type} exists in array");
            error_log("Debug: Array keys: " . implode(', ', array_keys($this->taxonomies)));
            error_log("Debug: array_key_exists check: " . (array_key_exists($type, $this->taxonomies) ? 'true' : 'false'));
            error_log("Debug: Current value: " . ($this->taxonomies[$type] === null ? 'null' : 'not null'));
        }

        return $this->get_taxonomy($type);
    }
}
