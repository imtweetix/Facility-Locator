<?php

/**
 * Handle CRUD operations for facilities
 */
class Facility_Locator_Facilities
{

    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'facility_locator_facilities';
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
            levels_of_care text,
            program_features text,
            custom_pin_image varchar(255),
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Create levels of care table
        self::create_levels_of_care_table();

        // Create program features table  
        self::create_program_features_table();

        // Debug: Log if table creation fails
        if (WP_DEBUG) {
            error_log('Facility Locator: All tables creation completed');

            // Check if all tables exist
            $facilities_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            $levels_exists = $wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "facility_locator_levels_of_care'");
            $features_exists = $wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "facility_locator_program_features'");

            error_log('Facility Locator: Tables exist - Facilities: ' . ($facilities_exists ? 'YES' : 'NO') .
                ', Levels: ' . ($levels_exists ? 'YES' : 'NO') .
                ', Features: ' . ($features_exists ? 'YES' : 'NO'));
        }

        // Check if we need to migrate existing data or add new columns
        self::maybe_update_table();
    }

    /**
     * Create levels of care table
     */
    public static function create_levels_of_care_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'facility_locator_levels_of_care';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create program features table
     */
    public static function create_program_features_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'facility_locator_program_features';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Update table structure if needed (for existing installations)
     */
    public static function maybe_update_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'facility_locator_facilities';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            return;
        }

        // Get current columns
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $column_names = array();
        foreach ($columns as $column) {
            $column_names[] = $column->Field;
        }

        // Add new columns if they don't exist
        if (!in_array('levels_of_care', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN levels_of_care text AFTER website");
        }

        if (!in_array('program_features', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN program_features text AFTER levels_of_care");
        }

        if (!in_array('custom_pin_image', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN custom_pin_image varchar(255) AFTER program_features");
        }

        // Remove old columns if they exist
        if (in_array('categories', $column_names)) {
            // Migrate data first
            $wpdb->query("UPDATE $table_name SET levels_of_care = categories WHERE levels_of_care IS NULL OR levels_of_care = ''");
            $wpdb->query("ALTER TABLE $table_name DROP COLUMN categories");
        }

        if (in_array('attributes', $column_names)) {
            // Migrate data first
            $wpdb->query("UPDATE $table_name SET program_features = attributes WHERE program_features IS NULL OR program_features = ''");
            $wpdb->query("ALTER TABLE $table_name DROP COLUMN attributes");
        }

        // Remove unnecessary columns
        if (in_array('hours', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name DROP COLUMN hours");
        }

        if (in_array('email', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name DROP COLUMN email");
        }
    }

    /**
     * Get all facilities
     */
    public function get_facilities($args = array())
    {
        global $wpdb;

        $defaults = array(
            'levels_of_care' => array(),
            'program_features' => array(),
        );

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM {$this->table_name}";
        $where_clauses = array();
        $where_values = array();

        // Filter by levels of care if provided
        if (!empty($args['levels_of_care'])) {
            $level_ids = array_map('intval', $args['levels_of_care']);
            $level_conditions = array();

            foreach ($level_ids as $level_id) {
                $level_conditions[] = "levels_of_care LIKE '%\"" . $level_id . "\"%'";
            }

            if (!empty($level_conditions)) {
                $where_clauses[] = '(' . implode(' OR ', $level_conditions) . ')';
            }
        }

        // Filter by program features if provided
        if (!empty($args['program_features'])) {
            $feature_ids = array_map('intval', $args['program_features']);
            $feature_conditions = array();

            foreach ($feature_ids as $feature_id) {
                $feature_conditions[] = "program_features LIKE '%\"" . $feature_id . "\"%'";
            }

            if (!empty($feature_conditions)) {
                $where_clauses[] = '(' . implode(' OR ', $feature_conditions) . ')';
            }
        }

        // Add WHERE clause if needed
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }

        // Get results
        $facilities = $wpdb->get_results($query);

        // Format data
        if ($facilities) {
            foreach ($facilities as &$facility) {
                $facility->levels_of_care = json_decode($facility->levels_of_care, true);
                $facility->program_features = json_decode($facility->program_features, true);

                // Ensure arrays are never null
                if (!is_array($facility->levels_of_care)) {
                    $facility->levels_of_care = array();
                }
                if (!is_array($facility->program_features)) {
                    $facility->program_features = array();
                }
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
            $facility->levels_of_care = json_decode($facility->levels_of_care, true);
            $facility->program_features = json_decode($facility->program_features, true);

            // Ensure arrays are never null
            if (!is_array($facility->levels_of_care)) {
                $facility->levels_of_care = array();
            }
            if (!is_array($facility->program_features)) {
                $facility->program_features = array();
            }
        }

        return $facility;
    }

    /**
     * Add a new facility
     */
    public function add_facility($data)
    {
        global $wpdb;

        // Debug logging
        if (WP_DEBUG) {
            error_log('Facility Locator: Adding new facility');
            error_log('Raw data: ' . print_r($data, true));
        }

        // Prepare data
        $prepared_data = $this->prepare_facility_data($data);

        if (WP_DEBUG) {
            error_log('Prepared data: ' . print_r($prepared_data, true));
        }

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
        if (!$table_exists) {
            if (WP_DEBUG) {
                error_log('Facility Locator: ERROR - Table does not exist: ' . $this->table_name);
            }
            return false;
        }

        // Insert
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

        // Debug logging
        if (WP_DEBUG) {
            error_log('Facility Locator: Updating facility ID: ' . $id);
            error_log('Raw data: ' . print_r($data, true));
        }

        // Prepare data
        $prepared_data = $this->prepare_facility_data($data);

        if (WP_DEBUG) {
            error_log('Prepared data: ' . print_r($prepared_data, true));
        }

        // Update
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

        if ($result === false) {
            if (WP_DEBUG) {
                error_log('Facility Locator: Update failed. Error: ' . $wpdb->last_error);
            }
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

        // Handle arrays - now expecting IDs instead of names
        if (isset($data['levels_of_care']) && is_array($data['levels_of_care'])) {
            // Ensure all values are integers (IDs)
            $level_ids = array_map('intval', array_filter($data['levels_of_care']));
            $prepared['levels_of_care'] = json_encode($level_ids);
        } else {
            $prepared['levels_of_care'] = json_encode(array());
        }

        if (isset($data['program_features']) && is_array($data['program_features'])) {
            // Ensure all values are integers (IDs)
            $feature_ids = array_map('intval', array_filter($data['program_features']));
            $prepared['program_features'] = json_encode($feature_ids);
        } else {
            $prepared['program_features'] = json_encode(array());
        }

        return $prepared;
    }
}
