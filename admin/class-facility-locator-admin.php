<?php

/**
 * The admin-specific functionality with performance optimization
 * Includes external script loading and caching improvements
 */
class Facility_Locator_Admin
{
    private $plugin_name;
    private $version;
    private $facilities;
    private $taxonomy_manager;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->facilities = new Facility_Locator_Facilities();
        $this->taxonomy_manager = new Facility_Locator_Taxonomy_Manager();

        // Ensure tables exist
        $this->maybe_create_tables();
    }

    /**
     * Create tables if they don't exist with improved error handling
     */
    private function maybe_create_tables()
    {
        global $wpdb;

        $facilities_table = $wpdb->prefix . 'facility_locator_facilities';
        $taxonomies_table = $wpdb->prefix . 'facility_locator_taxonomies';

        $facilities_exists = $wpdb->get_var("SHOW TABLES LIKE '$facilities_table'");
        $taxonomies_exists = $wpdb->get_var("SHOW TABLES LIKE '$taxonomies_table'");

        if (!$taxonomies_exists) {
            if (WP_DEBUG) {
                error_log('Facility Locator: Creating taxonomies table');
            }
            Facility_Locator_Base_Taxonomy::create_table();
        }

        if (!$facilities_exists) {
            if (WP_DEBUG) {
                error_log('Facility Locator: Creating facilities table');
            }
            Facility_Locator_Facilities::create_table();
        }

        // Verify tables were created
        $facilities_exists_after = $wpdb->get_var("SHOW TABLES LIKE '$facilities_table'");
        $taxonomies_exists_after = $wpdb->get_var("SHOW TABLES LIKE '$taxonomies_table'");

        if (!$facilities_exists_after || !$taxonomies_exists_after) {
            if (WP_DEBUG) {
                error_log('Facility Locator: Table creation failed - facilities: ' . ($facilities_exists_after ? 'OK' : 'FAILED') . ', taxonomies: ' . ($taxonomies_exists_after ? 'OK' : 'FAILED'));
            }

            // Show admin notice
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p><strong>Facility Locator:</strong> Database tables could not be created. Please check your database permissions or contact your administrator.</p></div>';
            });
        }
    }

    /**
     * Register the stylesheets for the admin area
     */
    public function enqueue_styles()
    {
        wp_enqueue_style(
            $this->plugin_name,
            FACILITY_LOCATOR_URL . 'admin/css/facility-locator-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area with performance optimization
     */
    public function enqueue_scripts()
    {
        $current_screen = get_current_screen();

        // Base admin script for all admin pages
        wp_enqueue_script(
            $this->plugin_name,
            FACILITY_LOCATOR_URL . 'admin/js/facility-locator-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Enqueue WordPress media uploader on facility and settings pages
        if ($current_screen && (strpos($current_screen->id, 'facility-locator') !== false)) {
            wp_enqueue_media();
        }

        // Load Google Maps and facility form script only on facility pages
        if ($current_screen && $this->is_facility_form_page($current_screen)) {
            $api_key = get_option('facility_locator_google_maps_api_key', '');

            if (!empty($api_key)) {
                // Enqueue facility form specific script
                wp_enqueue_script(
                    $this->plugin_name . '-facility-form',
                    FACILITY_LOCATOR_URL . 'admin/js/facility-locator-facility-form.js',
                    array('jquery'),
                    $this->version,
                    false
                );

                // Enqueue Google Maps with callback
                $google_maps_url = add_query_arg(array(
                    'key' => $api_key,
                    'libraries' => 'places',
                    'callback' => 'initFacilityMap',
                    'v' => 'weekly'
                ), 'https://maps.googleapis.com/maps/api/js');

                wp_enqueue_script(
                    'google-maps-admin',
                    $google_maps_url,
                    array($this->plugin_name . '-facility-form'),
                    null,
                    false
                );

                if (WP_DEBUG) {
                    error_log('Facility Locator: Loading Google Maps with URL: ' . $google_maps_url);
                }
            } else {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p><strong>Facility Locator:</strong> Google Maps API key is missing. Please add it in <a href="' . admin_url('admin.php?page=facility-locator-settings') . '">Facility Locator Settings</a> to enable map functionality.</p></div>';
                });

                if (WP_DEBUG) {
                    error_log('Facility Locator: No Google Maps API key found');
                }
            }
        }

        // Localize script with optimized data
        wp_localize_script($this->plugin_name, 'facilityLocator', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('facility_locator_nonce'),
            'hasApiKey' => !empty($api_key ?? ''),
            'debugMode' => WP_DEBUG ? true : false,
            'currentScreen' => $current_screen ? $current_screen->id : '',
        ));
    }

    /**
     * Check if current page is a facility form page
     */
    private function is_facility_form_page($screen)
    {
        return strpos($screen->id, 'facility-locator-add-new') !== false ||
            (strpos($screen->id, 'facility-locator') !== false && isset($_GET['id']));
    }

    /**
     * Add menu items with improved error handling
     */
    public function add_admin_menu()
    {
        add_menu_page(
            'Facility Locator',
            'Facility Locator',
            'manage_options',
            'facility-locator',
            array($this, 'display_admin_page'),
            'dashicons-location',
            26
        );

        add_submenu_page(
            'facility-locator',
            'All Facilities',
            'All Facilities',
            'manage_options',
            'facility-locator',
            array($this, 'display_admin_page')
        );

        add_submenu_page(
            'facility-locator',
            'Add New Facility',
            'Add New',
            'manage_options',
            'facility-locator-add-new',
            array($this, 'display_add_facility_page')
        );

        // Always add taxonomy submenus regardless of content
        $taxonomy_definitions = array(
            'levels_of_care' => 'Levels of Care',
            'features' => 'Features',
            'therapies' => 'Therapies',
            'environment' => 'Environment',
            'location' => 'Location',
            'insurance_providers' => 'Insurance Providers'
        );

        foreach ($taxonomy_definitions as $type => $display_name) {
            add_submenu_page(
                'facility-locator',
                $display_name,
                $display_name,
                'manage_options',
                'facility-locator-' . str_replace('_', '-', $type),
                array($this, 'display_taxonomy_page')
            );
        }

        add_submenu_page(
            'facility-locator',
            'Form Configuration',
            'Form Configuration',
            'manage_options',
            'facility-locator-form-config',
            array($this, 'display_form_config_page')
        );

        add_submenu_page(
            'facility-locator',
            'Settings',
            'Settings',
            'manage_options',
            'facility-locator-settings',
            array($this, 'display_settings_page')
        );

        // Add debug menu in development
        if (WP_DEBUG) {
            add_submenu_page(
                'facility-locator',
                'Debug',
                'Debug',
                'manage_options',
                'facility-locator-debug',
                array($this, 'display_debug_page')
            );
        }
    }

    /**
     * Debug page to help troubleshoot taxonomy issues
     */
    public function display_debug_page()
    {
        global $wpdb;

        echo '<div class="wrap">';
        echo '<h1>Facility Locator Debug</h1>';

        // Check if taxonomy classes exist
        echo '<h2>Taxonomy Classes</h2>';
        $taxonomy_classes = array(
            'levels_of_care' => 'Facility_Locator_Levels_Of_Care',
            'features' => 'Facility_Locator_Features',
            'therapies' => 'Facility_Locator_Therapies',
            'environment' => 'Facility_Locator_Environment',
            'location' => 'Facility_Locator_Location',
            'insurance_providers' => 'Facility_Locator_Insurance_Providers'
        );

        foreach ($taxonomy_classes as $type => $class_name) {
            $exists = class_exists($class_name);
            echo '<p>' . $type . ' (' . $class_name . '): ' . ($exists ? '✅ EXISTS' : '❌ MISSING') . '</p>';

            if ($exists) {
                try {
                    $instance = new $class_name();
                    echo '<p>  → Instance creation: ✅ SUCCESS</p>';
                } catch (Exception $e) {
                    echo '<p>  → Instance creation: ❌ FAILED - ' . $e->getMessage() . '</p>';
                }
            }
        }

        // Check database tables
        $facilities_table = $wpdb->prefix . 'facility_locator_facilities';
        $taxonomies_table = $wpdb->prefix . 'facility_locator_taxonomies';

        $facilities_exists = $wpdb->get_var("SHOW TABLES LIKE '$facilities_table'");
        $taxonomies_exists = $wpdb->get_var("SHOW TABLES LIKE '$taxonomies_table'");

        echo '<h2>Database Tables</h2>';
        echo '<p>Facilities table: ' . ($facilities_exists ? '✅ EXISTS' : '❌ MISSING') . '</p>';
        echo '<p>Taxonomies table: ' . ($taxonomies_exists ? '✅ EXISTS' : '❌ MISSING') . '</p>';

        if ($taxonomies_exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $taxonomies_table");
            echo '<p>Taxonomy items count: ' . $count . '</p>';

            $types = $wpdb->get_results("SELECT taxonomy_type, COUNT(*) as count FROM $taxonomies_table GROUP BY taxonomy_type");
            echo '<h3>Taxonomy Types:</h3>';
            if (empty($types)) {
                echo '<p>No taxonomy items found in database.</p>';
            } else {
                foreach ($types as $type) {
                    echo '<p>' . $type->taxonomy_type . ': ' . $type->count . ' items</p>';
                }
            }
        }

        // Test taxonomy manager
        echo '<h2>Taxonomy Manager Test</h2>';
        try {
            $taxonomy_manager = new Facility_Locator_Taxonomy_Manager();
            $taxonomy_types = $taxonomy_manager->get_taxonomy_types();
            echo '<p>Available taxonomy types: ' . implode(', ', $taxonomy_types) . '</p>';

            foreach ($taxonomy_types as $type) {
                echo '<h4>Testing: ' . $type . '</h4>';
                try {
                    $taxonomy = $taxonomy_manager->get_taxonomy($type);
                    if ($taxonomy) {
                        echo '<p>  → Taxonomy object: ✅ SUCCESS</p>';

                        try {
                            $items = $taxonomy->get_all();
                            echo '<p>  → Get all items: ✅ SUCCESS (' . count($items) . ' items)</p>';
                        } catch (Exception $e) {
                            echo '<p>  → Get all items: ❌ FAILED - ' . $e->getMessage() . '</p>';
                        }
                    } else {
                        echo '<p>  → Taxonomy object: ❌ NULL RETURNED</p>';
                    }
                } catch (Exception $e) {
                    echo '<p>  → Taxonomy object: ❌ EXCEPTION - ' . $e->getMessage() . '</p>';
                }
            }
        } catch (Exception $e) {
            echo '<p>❌ Error: ' . $e->getMessage() . '</p>';
        }

        // Add manual table creation button
        echo '<h2>Manual Actions</h2>';
        echo '<form method="post">';
        echo '<input type="hidden" name="action" value="create_tables">';
        wp_nonce_field('facility_locator_debug');
        echo '<input type="submit" class="button button-primary" value="Create Tables">';
        echo '</form>';

        echo '<br>';
        echo '<form method="post">';
        echo '<input type="hidden" name="action" value="test_taxonomy_creation">';
        wp_nonce_field('facility_locator_debug');
        echo '<input type="submit" class="button" value="Test Taxonomy Creation">';
        echo '</form>';

        // Handle form submission
        if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'facility_locator_debug')) {
            if ($_POST['action'] === 'create_tables') {
                echo '<div class="notice notice-info"><p>Creating tables...</p></div>';
                Facility_Locator_Base_Taxonomy::create_table();
                Facility_Locator_Facilities::create_table();
                echo '<div class="notice notice-success"><p>Tables created. Please refresh the page.</p></div>';
            }

            if ($_POST['action'] === 'test_taxonomy_creation') {
                echo '<h3>Taxonomy Creation Test Results:</h3>';
                foreach ($taxonomy_classes as $type => $class_name) {
                    try {
                        echo '<p><strong>Testing ' . $type . ':</strong></p>';
                        $instance = new $class_name();
                        echo '<p>  → Direct instantiation: ✅ SUCCESS</p>';

                        // Test the get_all method
                        try {
                            $items = $instance->get_all();
                            echo '<p>  → get_all() method: ✅ SUCCESS (returned ' . count($items) . ' items)</p>';
                        } catch (Exception $e) {
                            echo '<p>  → get_all() method: ❌ FAILED - ' . $e->getMessage() . '</p>';
                        }

                        // Test through taxonomy manager
                        try {
                            $tm = new Facility_Locator_Taxonomy_Manager();
                            echo '<p>  → Taxonomy manager created: ✅ SUCCESS</p>';

                            // Test debug method if available
                            if (method_exists($tm, 'debug_get_taxonomy')) {
                                $taxonomy_from_manager = $tm->debug_get_taxonomy($type);
                            } else {
                                $taxonomy_from_manager = $tm->get_taxonomy($type);
                            }

                            echo '<p>  → Via taxonomy manager: ' . ($taxonomy_from_manager ? '✅ SUCCESS' : '❌ NULL RETURNED') . '</p>';

                            if ($taxonomy_from_manager) {
                                echo '<p>  → Manager object class: ' . get_class($taxonomy_from_manager) . '</p>';
                            } else {
                                // Additional debugging
                                $types = $tm->get_taxonomy_types();
                                echo '<p>  → Manager has types: ' . implode(', ', $types) . '</p>';
                                echo '<p>  → Looking for type: ' . $type . '</p>';
                                echo '<p>  → Type exists in array: ' . (in_array($type, $types) ? 'YES' : 'NO') . '</p>';
                            }
                        } catch (Exception $e) {
                            echo '<p>  → Via taxonomy manager: ❌ EXCEPTION - ' . $e->getMessage() . '</p>';
                        }

                        echo '<br>';
                    } catch (Exception $e) {
                        echo '<p>' . $type . ': ❌ Creation failed - ' . $e->getMessage() . '</p>';
                    }
                }
            }
        }

        echo '</div>';
    }

    /**
     * Get taxonomies with caching for admin menu
     */
    private function get_cached_taxonomies()
    {
        static $cached_taxonomies = null;

        if ($cached_taxonomies === null) {
            try {
                $cached_taxonomies = $this->taxonomy_manager->get_all_taxonomies();

                // Filter out any null taxonomies
                $cached_taxonomies = array_filter($cached_taxonomies, function ($taxonomy) {
                    return $taxonomy !== null;
                });
            } catch (Exception $e) {
                if (WP_DEBUG) {
                    error_log('Facility Locator: Error loading taxonomies: ' . $e->getMessage());
                }
                $cached_taxonomies = array();
            }
        }

        return $cached_taxonomies;
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('facility_locator_settings', 'facility_locator_google_maps_api_key');
        register_setting('facility_locator_settings', 'facility_locator_map_zoom', array(
            'default' => 10,
            'sanitize_callback' => 'intval',
        ));
        register_setting('facility_locator_settings', 'facility_locator_map_height', array(
            'default' => 500,
            'sanitize_callback' => 'intval',
        ));
        register_setting('facility_locator_settings', 'facility_locator_cta_text', array(
            'default' => 'Find a Facility',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('facility_locator_settings', 'facility_locator_cta_color', array(
            'default' => '#007bff',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        register_setting('facility_locator_settings', 'facility_locator_default_pin_image', array(
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ));

        register_setting('facility_locator_form_config', 'facility_locator_form_steps', array(
            'default' => json_encode(array()),
            'sanitize_callback' => function ($value) {
                return sanitize_text_field($value);
            },
        ));
    }

    /**
     * Display main admin page with cached data
     */
    public function display_admin_page()
    {
        // Use cached facilities for better performance
        $facilities = $this->facilities->get_facilities();

        include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-display.php';
    }

    /**
     * Display add/edit facility page
     */
    public function display_add_facility_page()
    {
        $facility = null;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($id > 0) {
            // Use cached facility retrieval
            $facility = $this->facilities->get_facility($id);
        }

        include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-facility-form.php';
    }

    /**
     * Display taxonomy management page with better error handling
     */
    public function display_taxonomy_page()
    {
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        $type = str_replace(array('facility-locator-', '-'), array('', '_'), $page);
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if (WP_DEBUG) {
            error_log("Facility Locator: Display taxonomy page - Page: {$page}, Type: {$type}, Action: {$action}");
        }

        // Ensure taxonomy manager and tables exist
        try {
            $this->maybe_create_tables();

            if (WP_DEBUG) {
                error_log("Facility Locator: Attempting to get taxonomy for type: {$type}");
            }

            $taxonomy = $this->taxonomy_manager->get_taxonomy($type);

            if (WP_DEBUG) {
                error_log("Facility Locator: Taxonomy object result: " . ($taxonomy ? 'SUCCESS' : 'NULL'));
            }

            if (!$taxonomy) {
                echo '<div class="wrap">';
                echo '<h1>Taxonomy Loading Issue</h1>';
                echo '<div class="notice notice-warning">';
                echo '<p>The taxonomy system is having trouble loading the <strong>' . esc_html($type) . '</strong> taxonomy.</p>';
                echo '<p>This is likely a temporary issue. Please try the following:</p>';
                echo '<ol>';
                echo '<li>Check that the database tables were created properly</li>';
                echo '<li>Try refreshing the page</li>';
                echo '<li>Check the debug information below</li>';
                echo '</ol>';
                echo '</div>';

                echo '<h2>Debug Information:</h2>';
                echo '<p><strong>Page:</strong> ' . esc_html($page) . '</p>';
                echo '<p><strong>Derived type:</strong> ' . esc_html($type) . '</p>';
                echo '<p><strong>Action:</strong> ' . esc_html($action) . '</p>';

                $available_types = $this->taxonomy_manager->get_taxonomy_types();
                echo '<p><strong>Available taxonomy types:</strong> ' . implode(', ', $available_types) . '</p>';

                // Test table existence
                global $wpdb;
                $table_name = $wpdb->prefix . 'facility_locator_taxonomies';
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
                echo '<p><strong>Taxonomy table exists:</strong> ' . ($table_exists ? 'Yes' : 'No') . '</p>';

                if ($table_exists) {
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE taxonomy_type = '" . esc_sql($type) . "'");
                    echo '<p><strong>Items in this taxonomy:</strong> ' . $count . '</p>';
                }

                echo '<p><a href="' . admin_url('admin.php?page=facility-locator-debug') . '" class="button button-primary">Go to Debug Page</a></p>';
                echo '</div>';
                return;
            }

            if ($action === 'edit' && $id > 0) {
                $item = $taxonomy->get_by_id($id);
                include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-taxonomy-form.php';
            } elseif ($action === 'add') {
                $item = null;
                include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-taxonomy-form.php';
            } else {
                $items = $taxonomy->get_all();
                include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-taxonomy-list.php';
            }
        } catch (Exception $e) {
            echo '<div class="wrap">';
            echo '<h1>Error Loading Taxonomy</h1>';
            echo '<div class="notice notice-error"><p><strong>Error:</strong> ' . esc_html($e->getMessage()) . '</p></div>';

            echo '<h2>Debug Information:</h2>';
            echo '<p><strong>Type:</strong> ' . esc_html($type) . '</p>';
            echo '<p><strong>Page:</strong> ' . esc_html($page) . '</p>';
            echo '<p><strong>Action:</strong> ' . esc_html($action) . '</p>';
            echo '<p><strong>Exception:</strong> ' . esc_html($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
            echo '<p><strong>Line:</strong> ' . esc_html($e->getLine()) . '</p>';

            echo '<p><a href="' . admin_url('admin.php?page=facility-locator-debug') . '" class="button">Go to Debug Page</a></p>';
            echo '</div>';
        }
    }

    /**
     * Display form configuration page
     */
    public function display_form_config_page()
    {
        include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-form-config.php';
    }

    /**
     * Display settings page
     */
    public function display_settings_page()
    {
        include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-settings.php';
    }

    /**
     * AJAX handler for saving a facility with performance optimization
     */
    public function ajax_save_facility()
    {
        // Performance: Clean output buffer before AJAX response
        if (ob_get_level()) {
            ob_end_clean();
        }

        if (WP_DEBUG) {
            error_log('Facility Locator: AJAX save facility called');
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'facility_locator_nonce')) {
            if (WP_DEBUG) {
                error_log('Facility Locator: Nonce verification failed');
            }
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!current_user_can('manage_options')) {
            if (WP_DEBUG) {
                error_log('Facility Locator: User lacks permissions');
            }
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $required_fields = array('name', 'address', 'lat', 'lng');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error("Missing required field: $field");
                return;
            }
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $data = $_POST;

        if (WP_DEBUG) {
            error_log('Facility Locator: Saving facility with ID: ' . $id);
        }

        try {
            if ($id > 0) {
                $result = $this->facilities->update_facility($id, $data);
                $operation = 'update';
            } else {
                $result = $this->facilities->add_facility($data);
                $operation = 'add';
            }

            if (WP_DEBUG) {
                error_log("Facility Locator: $operation result: " . print_r($result, true));
            }

            if ($result) {
                wp_send_json_success(array(
                    'id' => is_numeric($result) ? $result : $id,
                    'message' => 'Facility saved successfully',
                ));
            } else {
                global $wpdb;
                $db_error = $wpdb->last_error;

                if (WP_DEBUG) {
                    error_log('Facility Locator: Database error: ' . $db_error);
                }

                wp_send_json_error('Failed to save facility. Database error: ' . $db_error);
            }
        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log('Facility Locator: Exception: ' . $e->getMessage());
            }
            wp_send_json_error('Exception occurred: ' . $e->getMessage());
        }

        wp_die(); // Performance: Ensure clean AJAX termination
    }

    /**
     * AJAX handler for deleting a facility
     */
    public function ajax_delete_facility()
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'facility_locator_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id > 0) {
            $result = $this->facilities->delete_facility($id);

            if ($result) {
                wp_send_json_success(array(
                    'message' => 'Facility deleted successfully',
                ));
            } else {
                wp_send_json_error('Failed to delete facility');
            }
        } else {
            wp_send_json_error('Invalid facility ID');
        }

        wp_die();
    }

    /**
     * AJAX handler for saving taxonomy items with performance optimization
     */
    public function ajax_save_taxonomy()
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_send_json_error('Not an AJAX request');
            return;
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'facility_locator_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        if (empty($_POST['name'])) {
            wp_send_json_error('Name is required');
            return;
        }

        $taxonomy_type = isset($_POST['taxonomy_type']) ? $_POST['taxonomy_type'] : '';
        $taxonomy = $this->taxonomy_manager->get_taxonomy($taxonomy_type);

        if (!$taxonomy) {
            wp_send_json_error('Invalid taxonomy type');
            return;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $data = $_POST;

        try {
            if ($id > 0) {
                $result = $taxonomy->update($id, $data);
            } else {
                $result = $taxonomy->add($data);
            }

            if ($result) {
                wp_send_json_success(array(
                    'id' => is_numeric($result) ? $result : $id,
                    'message' => $taxonomy->get_display_name() . ' saved successfully',
                ));
            } else {
                wp_send_json_error('Failed to save ' . $taxonomy->get_display_name());
            }
        } catch (Exception $e) {
            wp_send_json_error('An error occurred while saving');
        }

        wp_die();
    }

    /**
     * AJAX handler for deleting taxonomy items
     */
    public function ajax_delete_taxonomy()
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'facility_locator_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $taxonomy_type = isset($_POST['taxonomy_type']) ? $_POST['taxonomy_type'] : '';
        $taxonomy = $this->taxonomy_manager->get_taxonomy($taxonomy_type);

        if (!$taxonomy) {
            wp_send_json_error('Invalid taxonomy type');
            return;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id > 0) {
            $result = $taxonomy->delete($id);

            if ($result) {
                wp_send_json_success(array(
                    'message' => $taxonomy->get_display_name() . ' deleted successfully',
                ));
            } else {
                wp_send_json_error('Failed to delete ' . $taxonomy->get_display_name());
            }
        } else {
            wp_send_json_error('Invalid ID');
        }

        wp_die();
    }
}
