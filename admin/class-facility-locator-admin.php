<?php

/**
 * The admin-specific functionality with full taxonomy support
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

        // AJAX actions are hooked via Facility_Locator_Loader
    }

    /**
     * Register all AJAX actions
     */
    private function register_ajax_actions()
    {
        // Facility AJAX actions
        add_action('wp_ajax_save_facility', array($this, 'ajax_save_facility'));
        add_action('wp_ajax_delete_facility', array($this, 'ajax_delete_facility'));

        // Taxonomy AJAX actions
        $taxonomy_types = $this->taxonomy_manager->get_taxonomy_types();
        foreach ($taxonomy_types as $type) {
            add_action('wp_ajax_save_taxonomy_' . $type, array($this, 'ajax_save_taxonomy'));
            add_action('wp_ajax_delete_taxonomy_' . $type, array($this, 'ajax_delete_taxonomy'));
        }
    }

    /**
     * Create tables if they don't exist
     */
    private function maybe_create_tables()
    {
        global $wpdb;

        $taxonomies_table = $wpdb->prefix . 'facility_locator_taxonomies';
        $taxonomies_exists = $wpdb->get_var("SHOW TABLES LIKE '$taxonomies_table'");

        if (!$taxonomies_exists) {
            if (WP_DEBUG) {
                error_log('Facility Locator: Creating taxonomies table');
            }
            Facility_Locator_Base_Taxonomy::create_table();
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
            $api_key = get_option('facility_locator_google_maps_api_key', '');
            if (!empty($api_key)) {
                $google_maps_url = add_query_arg(array(
                    'key' => $api_key,
                    'libraries' => 'places',
                    'callback' => 'initFacilityMap',
                    'v' => 'weekly'
                ), 'https://maps.googleapis.com/maps/api/js');

                wp_enqueue_script('google-maps-admin', $google_maps_url, array(), null, false);

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

        // Localize script
        wp_localize_script($this->plugin_name, 'facilityLocator', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('facility_locator_nonce'),
            'hasApiKey' => !empty($api_key),
            'debugMode' => WP_DEBUG ? true : false,
        ));
    }

    /**
     * Add menu items
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

        // Add taxonomy submenus
        $taxonomies = $this->taxonomy_manager->get_all_taxonomies();
        foreach ($taxonomies as $type => $taxonomy) {
            add_submenu_page(
                'facility-locator',
                $taxonomy->get_display_name(),
                $taxonomy->get_display_name(),
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
     * Display main admin page
     */
    public function display_admin_page()
    {
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
            $facility = $this->facilities->get_facility($id);
        }

        include FACILITY_LOCATOR_PATH . 'admin/partials/facility-locator-admin-facility-form.php';
    }

    /**
     * Display taxonomy management page
     */
    public function display_taxonomy_page()
    {
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        $type = str_replace(array('facility-locator-', '-'), array('', '_'), $page);
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $taxonomy = $this->taxonomy_manager->get_taxonomy($type);
        if (!$taxonomy) {
            echo '<div class="wrap"><h1>Invalid taxonomy type</h1></div>';
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
        if (WP_DEBUG) {
            error_log('Facility Locator: AJAX save facility called');
            error_log('POST data: ' . print_r($_POST, true));
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
            error_log('Facility data: ' . print_r($data, true));
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
    }

    /**
     * AJAX handler for deleting a facility
     */
    public function ajax_delete_facility()
    {
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
    }

    /**
     * AJAX handler for saving taxonomy items
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
    }
}
