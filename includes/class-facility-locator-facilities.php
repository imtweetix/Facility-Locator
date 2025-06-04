<?php

/**
 * Handle CRUD operations for facilities with new taxonomy system
 */
class Facility_Locator_Facilities
{
    private $table_name;
    private $taxonomy_manager;

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
            PRIMARY KEY  (id)
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
     * Get all facilities with optional filtering
     */
    public function get_facilities($args = array())
    {
        global $wpdb;

        $query = "SELECT * FROM {$this->table_name}";
        $where_clauses = array();

        // Build WHERE clauses for taxonomy filtering
        if (!empty($args)) {
            foreach ($args as $taxonomy_type => $taxonomy_ids) {
                if (!empty($taxonomy_ids) && is_array($taxonomy_ids)) {
                    $taxonomy_conditions = array();
                    foreach ($taxonomy_ids as $taxonomy_id) {
                        $taxonomy_conditions[] = $wpdb->prepare(
                            "taxonomies LIKE %s",
                            '%"' . $taxonomy_type . '"%' . intval($taxonomy_id) . '%'
                        );
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

        // Get results
        $facilities = $wpdb->get_results($query);

        // Format data and add taxonomy details
        if ($facilities) {
            foreach ($facilities as &$facility) {
                $facility = $this->format_facility_data($facility);
            }
        }

        return $facilities;
    }

    /**
     * Get a single facility by ID
     */
    public function get_facility($id)
    {
        global $wpdb;

        $facility = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id)
        );

        if ($facility) {
            $facility = $this->format_facility_data($facility);
        }

        return $facility;
    }

    /**
     * Add a new facility
     */
    public function add_facility($data)
    {
        global $wpdb;

        if (WP_DEBUG) {
            error_log('Facility Locator: Adding new facility');
            error_log('Raw data: ' . print_r($data, true));
        }

        $prepared_data = $this->prepare_facility_data($data);

        if (WP_DEBUG) {
            error_log('Prepared data: ' . print_r($prepared_data, true));
        }

        $result = $wpdb->insert($this->table_name, $prepared_data);

        if (WP_DEBUG) {
            error_log('Insert result: ' . print_r($result, true));
            error_log('Last error: ' . $wpdb->last_error);
            error_log('Insert ID: ' . $wpdb->insert_id);
        }

        if ($result === false) {
            if (WP_DEBUG) {
                error_log('Facility Locator: Insert failed. Error: ' . $wpdb->last_error);
            }
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update an existing facility
     */
    public function update_facility($id, $data)
    {
        global $wpdb;

        if (WP_DEBUG) {
            error_log('Facility Locator: Updating facility ID: ' . $id);
            error_log('Raw data: ' . print_r($data, true));
        }

        $prepared_data = $this->prepare_facility_data($data);

        if (WP_DEBUG) {
            error_log('Prepared data: ' . print_r($prepared_data, true));
        }

        $result = $wpdb->update(
            $this->table_name,
            $prepared_data,
            array('id' => $id),
            null,
            array('%d')
        );

        if (WP_DEBUG) {
            error_log('Update result: ' . print_r($result, true));
            error_log('Last error: ' . $wpdb->last_error);
        }

        return $result !== false;
    }

    /**
     * Delete a facility
     */
    public function delete_facility($id)
    {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Prepare facility data for database
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

        // Handle taxonomies
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
     * Format facility data after retrieval from database
     */
    private function format_facility_data($facility)
    {
        // Decode taxonomies
        $taxonomies = json_decode($facility->taxonomies, true);
        if (!is_array($taxonomies)) {
            $taxonomies = array();
        }

        // Add individual taxonomy properties for backward compatibility and display
        $taxonomy_types = $this->taxonomy_manager->get_taxonomy_types();
        foreach ($taxonomy_types as $type) {
            $facility->{$type} = isset($taxonomies[$type]) ? $taxonomies[$type] : array();

            // Add taxonomy details for display
            if (!empty($facility->{$type})) {
                $items = $this->taxonomy_manager->get_items_by_ids($type, $facility->{$type});
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
     * Get all available taxonomy options for filters
     */
    public function get_taxonomy_filters()
    {
        return $this->taxonomy_manager->get_all_for_filters();
    }

    /**
     * Get categories for backward compatibility
     */
    public function get_categories()
    {
        $levels_taxonomy = $this->taxonomy_manager->get_taxonomy('levels_of_care');
        return $levels_taxonomy ? $levels_taxonomy->get_all() : array();
    }

    /**
     * Get attributes for backward compatibility  
     */
    public function get_attributes()
    {
        $features_taxonomy = $this->taxonomy_manager->get_taxonomy('features');
        return $features_taxonomy ? $features_taxonomy->get_all() : array();
    }
}
