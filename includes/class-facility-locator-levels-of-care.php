<?php

/**
 * Handle CRUD operations for Levels of Care
 */
class Facility_Locator_Levels_Of_Care
{

    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'facility_locator_levels_of_care';
    }

    /**
     * Get all levels of care
     */
    public function get_all()
    {
        global $wpdb;

        return $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY name ASC");
    }

    /**
     * Get a single level of care by ID
     */
    public function get_by_id($id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id)
        );
    }

    /**
     * Get a level of care by slug
     */
    public function get_by_slug($slug)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE slug = %s", $slug)
        );
    }

    /**
     * Add a new level of care
     */
    public function add($data)
    {
        global $wpdb;

        // Validate required fields
        if (empty($data['name'])) {
            return false;
        }

        // Generate slug
        $slug = $this->generate_slug($data['name']);

        $prepared_data = array(
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
     * Update an existing level of care
     */
    public function update($id, $data)
    {
        global $wpdb;

        // Get existing record to check if slug needs updating
        $existing = $this->get_by_id($id);
        if (!$existing) {
            return false;
        }

        // Generate new slug if name changed
        $slug = $existing->slug;
        if ($existing->name !== $data['name']) {
            $slug = $this->generate_slug($data['name']);
        }

        $prepared_data = array(
            'name' => sanitize_text_field($data['name']),
            'slug' => $slug,
            'description' => isset($data['description']) ? wp_kses_post($data['description']) : '',
        );

        $result = $wpdb->update(
            $this->table_name,
            $prepared_data,
            array('id' => $id),
            null,
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Delete a level of care
     */
    public function delete($id)
    {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
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

        // Ensure slug is unique
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

        $query = "SELECT id FROM {$this->table_name} WHERE slug = %s";
        $params = array($slug);

        if ($exclude_id) {
            $query .= " AND id != %d";
            $params[] = $exclude_id;
        }

        return $wpdb->get_var($wpdb->prepare($query, $params)) !== null;
    }

    /**
     * Get levels of care for dropdown
     */
    public function get_for_dropdown()
    {
        $levels = $this->get_all();
        $options = array();

        foreach ($levels as $level) {
            $options[$level->id] = $level->name;
        }

        return $options;
    }

    /**
     * Get count of facilities using this level of care
     */
    public function get_usage_count($id)
    {
        global $wpdb;
        $facilities_table = $wpdb->prefix . 'facility_locator_facilities';

        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$facilities_table} 
            WHERE levels_of_care LIKE %s
        ", '%"' . $id . '"%'));

        return intval($count);
    }
}
