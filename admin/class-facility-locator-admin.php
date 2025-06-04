<?php

/**
 * The admin-specific functionality of the plugin
 */
class Facility_Locator_Admin
{

    private $plugin_name;
    private $version;
    private $facilities;
    private $levels_of_care;
    private $program_features;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->facilities = new Facility_Locator_Facilities();
        $this->levels_of_care = new Facility_Locator_Levels_Of_Care();
        $this->program_features = new Facility_Locator_Program_Features();

        // Ensure tables exist
        $this->maybe_create_tables();

        // Register AJAX actions directly as backup
        add_action('wp_ajax_save_level_of_care', array($this, 'ajax_save_level_of_care'));
        add_action('wp_ajax_delete_level_of_care', array($this, 'ajax_delete_level_of_care'));
        add_action('wp_ajax_save_program_feature', array($this, 'ajax_save_program_feature'));
        add_action('wp_ajax_delete_program_feature', array($this, 'ajax_delete_program_feature'));
        add_action('wp_ajax_save_facility', array($this, 'ajax_save_facility'));
        add_action('wp_ajax_delete_facility', array($this, 'ajax_delete_facility'));
    }

    /**
     * Create tables if they don't exist
     */
    private function maybe_create_tables()
    {
        global $wpdb;

        $levels_table = $wpdb->prefix . 'facility_locator_levels_of_care';
        $features_table = $wpdb->prefix . 'facility_locator_program_features';

        // Check if tables exist
        $levels_exists = $wpdb->get_var("SHOW TABLES LIKE '$levels_table'");
        $features_exists = $wpdb->get_var("SHOW TABLES LIKE '$features_table'");

        if (!$levels_exists) {
            if (WP_DEBUG) {
                error_log('Facility Locator: Creating levels of care table');
            }
            Facility_Locator_Facilities::create_levels_of_care_table();
        }

        if (!$features_exists) {
            if (WP_DEBUG) {
                error_log('Facility Locator: Creating program features table');
            }
            Facility_Locator_Facilities::create_program_features_table();
        }
    }

    /**
     * Register the stylesheets for the admin area
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, FACILITY_LOCATOR_URL . 'admin/css/facility-locator-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area
     */
    public function enqueue_scripts()
    {
        $current_screen = get_current_screen();

        wp_enqueue_script($this->plugin_name, FACILITY_LOCATOR_URL . 'admin/js/facility-locator-admin.js', array('jquery'), $this->version, false);

        // Enqueue WordPress media uploader on facility and settings pages
        if ($current_screen && (strpos($current_screen->id, 'facility-locator') !== false)) {
            wp_enqueue_media();
        }

        // Only load Google Maps on facility pages
        if ($current_screen && (strpos($current_screen->id, 'facility-locator') !== false)) {
            // Google Maps API
            $api_key = get_option('facility_locator_google_maps_api_key', '');
            if (!empty($api_key)) {
                // Add callback parameter and libraries
                $google_maps_url = add_query_arg(array(
                    'key' => $api_key,
                    'libraries' => 'places',
                    'callback' => 'initFacilityMap',
                    'v' => 'weekly' // Use weekly version for stability
                ), 'https://maps.googleapis.com/maps/api/js');

                // Enqueue with no dependencies to load as early as possible
                wp_enqueue_script('google-maps-admin', $google_maps_url, array(), null, false);

                // Add debugging info if WP_DEBUG is enabled
                if (WP_DEBUG) {
                    error_log('Facility Locator: Loading Google Maps with URL: ' . $google_maps_url);
                }
            } else {
                // Show error message if no API key
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p><strong>Facility Locator:</strong> Google Maps API key is missing. Please add it in <a href="' . admin_url('admin.php?page=facility-locator-settings') . '">Facility Locator Settings</a> to enable map functionality.</p></div>';
                });

                if (WP_DEBUG) {
                    error_log('Facility Locator: No Google Maps API key found');
                }
            }
        }

        // Localize script
        wp_localize_script($this->plugin_name, 'facilityLocator', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('facility_locator_nonce'),
            'hasApiKey' => !empty($api_key),
            'apiKey' => !empty($api_key) ? $api_key : '',
            'debugMode' => WP_DEBUG ? true : false,
        ));
    }

    /**
     * Add menu item
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

        add_submenu_page(
            'facility-locator',
            'Levels of Care',
            'Levels of Care',
            'manage_options',
            'facility-locator-levels-of-care',
            array($this, 'display_levels_of_care_page')
        );

        add_submenu_page(
            'facility-locator',
            'Program Features',
            'Program Features',
            'manage_options',
            'facility-locator-program-features',
            array($this, 'display_program_features_page')
        );

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

        // Form configuration settings
        register_setting('facility_locator_form_config', 'facility_locator_form_steps', array(
            'default' => json_encode(array()),
            'sanitize_callback' => function ($value) {
                return sanitize_text_field($value);
            },
        ));
    }

    /**
     * Display main admin page
     */
    public function display_admin_page()
    {
        $facilities = $this->facilities->get_facilities();

        // Include template using template loader
        Facility_Locator_Template_Loader::get_template(
            'admin/facilities-list.php',
            array(
                'facilities' => $facilities
            )
        );
    }

    /**
     * Display add/edit facility page
     */
    public function display_add_facility_page()
    {
        $facility = null;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($id > 0) {
            $facility = $this->facilities->get_facility($id);
        }

        include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-facility-form.php';
    }

    /**
     * Display levels of care page
     */
    public function display_levels_of_care_page()
    {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($action === 'edit' && $id > 0) {
            $level = $this->levels_of_care->get_by_id($id);
            include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-level-form.php';
        } elseif ($action === 'add') {
            $level = null;
            include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-level-form.php';
        } else {
            $levels = $this->levels_of_care->get_all();
            include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-levels-list.php';
        }
    }

    /**
     * Display program features page
     */
    public function display_program_features_page()
    {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($action === 'edit' && $id > 0) {
            $feature = $this->program_features->get_by_id($id);
            include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-feature-form.php';
        } elseif ($action === 'add') {
            $feature = null;
            include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-feature-form.php';
        } else {
            $features = $this->program_features->get_all();
            include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-features-list.php';
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
     * AJAX handler for saving a facility
     */
    public function ajax_save_facility()
    {
        // Debug logging
        if (WP_DEBUG) {
            error_log('Facility Locator: AJAX save facility called');
            error_log('POST data: ' . print_r($_POST, true));
        }

        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'facility_locator_nonce')) {
            if (WP_DEBUG) {
                error_log('Facility Locator: Nonce verification failed');
            }
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            if (WP_DEBUG) {
                error_log('Facility Locator: User lacks permissions');
            }
            wp_send_json_error('Insufficient permissions');
            return;
        }

        // Validate required fields
        $required_fields = array('name', 'address', 'lat', 'lng');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error("Missing required field: $field");
                return;
            }
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $data = $_POST;

        // Debug: Log the data being saved
        if (WP_DEBUG) {
            error_log('Facility Locator: Saving facility with ID: ' . $id);
            error_log('Facility data: ' . print_r($data, true));
        }

        // Save facility
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
                // Get the last database error
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
    }

    /**
     * AJAX handler for deleting a facility
     */
    public function ajax_delete_facility()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'facility_locator_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Check permissions
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
    }

    /**
     * AJAX handler for saving a level of care
     */
    public function ajax_save_level_of_care()
    {
        // Prevent any output before our JSON response
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Check if this is actually an AJAX request
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_send_json_error('Not an AJAX request');
            return;
        }

        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'facility_locator_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        // Validate required fields
        if (empty($_POST['name'])) {
            wp_send_json_error('Name is required');
            return;
        }

        // Check if levels_of_care object exists
        if (!$this->levels_of_care) {
            wp_send_json_error('Levels of care manager not available');
            return;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $data = $_POST;

        try {
            // Save level of care
            if ($id > 0) {
                $result = $this->levels_of_care->update($id, $data);
            } else {
                $result = $this->levels_of_care->add($data);
            }

            if ($result) {
                wp_send_json_success(array(
                    'id' => is_numeric($result) ? $result : $id,
                    'message' => 'Level of care saved successfully',
                ));
            } else {
                wp_send_json_error('Failed to save level of care');
            }
        } catch (Exception $e) {
            wp_send_json_error('An error occurred while saving');
        }

        wp_die();
    }

    /**
     * AJAX handler for deleting a level of care
     */
    public function ajax_delete_level_of_care()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'facility_locator_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id > 0) {
            $result = $this->levels_of_care->delete($id);

            if ($result) {
                wp_send_json_success(array(
                    'message' => 'Level of care deleted successfully',
                ));
            } else {
                wp_send_json_error('Failed to delete level of care');
            }
        } else {
            wp_send_json_error('Invalid level of care ID');
        }
    }

    /**
     * AJAX handler for saving a program feature
     */
    public function ajax_save_program_feature()
    {
        // Prevent any output before our JSON response
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Check if this is actually an AJAX request
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_send_json_error('Not an AJAX request');
            return;
        }

        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'facility_locator_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        // Validate required fields
        if (empty($_POST['name'])) {
            wp_send_json_error('Name is required');
            return;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $data = $_POST;

        try {
            // Save program feature
            if ($id > 0) {
                $result = $this->program_features->update($id, $data);
            } else {
                $result = $this->program_features->add($data);
            }

            if ($result) {
                wp_send_json_success(array(
                    'id' => is_numeric($result) ? $result : $id,
                    'message' => 'Program feature saved successfully',
                ));
            } else {
                wp_send_json_error('Failed to save program feature');
            }
        } catch (Exception $e) {
            wp_send_json_error('An error occurred while saving');
        }

        wp_die();
    }

    /**
     * AJAX handler for deleting a program feature
     */
    public function ajax_delete_program_feature()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'facility_locator_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id > 0) {
            $result = $this->program_features->delete($id);

            if ($result) {
                wp_send_json_success(array(
                    'message' => 'Program feature deleted successfully',
                ));
            } else {
                wp_send_json_error('Failed to delete program feature');
            }
        } else {
            wp_send_json_error('Invalid program feature ID');
        }
    }
}
