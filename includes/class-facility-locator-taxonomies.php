<?php

/**
 * Comprehensive Taxonomies System for Facility Locator
 * 
 * This file contains all taxonomy classes for managing:
 * - Levels of Care
 * - Features (formerly Program Features)
 * - Therapies
 * - Environment
 * - Location
 * - Insurance Providers
 */

/**
 * Base Taxonomy Class
 * Provides common functionality for all taxonomies
 */
abstract class Facility_Locator_Base_Taxonomy
{
    protected $table_name;
    protected $taxonomy_type;

    public function __construct($taxonomy_type)
    {
        global $wpdb;
        $this->taxonomy_type = $taxonomy_type;
        $this->table_name = $wpdb->prefix . 'facility_locator_taxonomies';
    }

    /**
     * Get all items for this taxonomy type
     */
    public function get_all()
    {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE taxonomy_type = %s ORDER BY name ASC",
            $this->taxonomy_type
        ));
    }

    /**
     * Get a single item by ID
     */
    public function get_by_id($id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d AND taxonomy_type = %s",
            $id,
            $this->taxonomy_type
        ));
    }

    /**
     * Get an item by slug
     */
    public function get_by_slug($slug)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE slug = %s AND taxonomy_type = %s",
            $slug,
            $this->taxonomy_type
        ));
    }

    /**
     * Add a new item
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
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update an existing item
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

        return $result !== false;
    }

    /**
     * Delete an item
     */
    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array('id' => $id, 'taxonomy_type' => $this->taxonomy_type),
            array('%d', '%s')
        );
    }

    /**
     * Generate unique slug
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
     * Check if slug exists
     */
    private function slug_exists($slug, $exclude_id = null)
    {
        global $wpdb;

        $query = "SELECT id FROM {$this->table_name} WHERE slug = %s AND taxonomy_type = %s";
        $params = array($slug, $this->taxonomy_type);

        if ($exclude_id) {
            $query .= " AND id != %d";
            $params[] = $exclude_id;
        }

        return $wpdb->get_var($wpdb->prepare($query, $params)) !== null;
    }

    /**
     * Get items for dropdown
     */
    public function get_for_dropdown()
    {
        $items = $this->get_all();
        $options = array();

        foreach ($items as $item) {
            $options[$item->id] = $item->name;
        }

        return $options;
    }

    /**
     * Get count of facilities using this taxonomy item
     */
    public function get_usage_count($id)
    {
        global $wpdb;
        $facilities_table = $wpdb->prefix . 'facility_locator_facilities';

        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$facilities_table} 
            WHERE taxonomies LIKE %s
        ", '%"' . $this->taxonomy_type . '"%' . $id . '%'));

        return intval($count);
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
     * Create the taxonomies table
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
            KEY taxonomy_type (taxonomy_type)
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
 * Taxonomy Manager
 * Provides centralized access to all taxonomies
 */
class Facility_Locator_Taxonomy_Manager
{
    private $taxonomies = array();

    public function __construct()
    {
        $this->taxonomies = array(
            'levels_of_care' => new Facility_Locator_Levels_Of_Care(),
            'features' => new Facility_Locator_Features(),
            'therapies' => new Facility_Locator_Therapies(),
            'environment' => new Facility_Locator_Environment(),
            'location' => new Facility_Locator_Location(),
            'insurance_providers' => new Facility_Locator_Insurance_Providers(),
        );
    }

    /**
     * Get a specific taxonomy
     */
    public function get_taxonomy($type)
    {
        return isset($this->taxonomies[$type]) ? $this->taxonomies[$type] : null;
    }

    /**
     * Get all taxonomies
     */
    public function get_all_taxonomies()
    {
        return $this->taxonomies;
    }

    /**
     * Get all taxonomy types
     */
    public function get_taxonomy_types()
    {
        return array_keys($this->taxonomies);
    }

    /**
     * Get all items for all taxonomies (for filtering)
     */
    public function get_all_for_filters()
    {
        $filters = array();

        foreach ($this->taxonomies as $type => $taxonomy) {
            $filters[$type] = $taxonomy->get_all();
        }

        return $filters;
    }

    /**
     * Get taxonomy items by IDs and type
     */
    public function get_items_by_ids($type, $ids)
    {
        if (!isset($this->taxonomies[$type]) || empty($ids)) {
            return array();
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'facility_locator_taxonomies';
        
        $id_placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE taxonomy_type = %s AND id IN ({$id_placeholders}) ORDER BY name ASC",
            array_merge(array($type), $ids)
        );

        return $wpdb->get_results($query);
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
}