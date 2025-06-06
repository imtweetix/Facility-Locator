<?php

/**
 * The admin-specific functionality with performance optimization
 * Production version - debug code removed
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
     * Create tables if they don't exist
     */
    private function maybe_create_tables()
    {
        global $wpdb;

        $facilities_table = $wpdb->prefix . 'facility_locator_facilities';
        $taxonomies_table = $wpdb->prefix . 'facility_locator_taxonomies';

        $facilities_exists = $wpdb->get_var("SHOW TABLES LIKE '$facilities_table'");
        $taxonomies_exists = $wpdb->get_var("SHOW TABLES LIKE '$taxonomies_table'");

        if (!$taxonomies_exists) {
            Facility_Locator_Base_Taxonomy::create_table();
        }

        if (!$facilities_exists) {
            Facility_Locator_Facilities::create_table();
        }

        // Show admin notice if tables couldn't be created
        if (
            !$wpdb->get_var("SHOW TABLES LIKE '$facilities_table'") ||
            !$wpdb->get_var("SHOW TABLES LIKE '$taxonomies_table'")
        ) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p><strong>Facility Locator:</strong> Database tables could not be created. Please check your database permissions.</p></div>';
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

        // Enqueue image gallery CSS on facility pages
        $current_screen = get_current_screen();
        if ($current_screen && $this->is_facility_form_page($current_screen)) {
            wp_enqueue_style(
                $this->plugin_name . '-image-gallery',
                FACILITY_LOCATOR_URL . 'admin/css/facility-locator-image-gallery.css',
                array($this->plugin_name),
                $this->version,
                'all'
            );
        }
    }

    /**
     * Register the JavaScript for the admin area
     */
    public function enqueue_scripts()
    {
        $current_screen = get_current_screen();

        // Base admin script
        wp_enqueue_script(
            $this->plugin_name,
            FACILITY_LOCATOR_URL . 'admin/js/facility-locator-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Enqueue media uploader on facility and settings pages
        if ($current_screen && (strpos($current_screen->id, 'facility-locator') !== false)) {
            wp_enqueue_media();
        }

        // Get API key for localization
        $api_key = get_option('facility_locator_google_maps_api_key', '');

        // Load Google Maps and facility form script only on facility pages
        if ($current_screen && $this->is_facility_form_page($current_screen)) {

            if (!empty($api_key)) {
                // Enqueue facility form script first
                wp_enqueue_script(
                    $this->plugin_name . '-facility-form',
                    FACILITY_LOCATOR_URL . 'admin/js/facility-locator-facility-form.js',
                    array('jquery'),
                    $this->version,
                    false
                );

                // Enqueue image gallery script
                wp_enqueue_script(
                    $this->plugin_name . '-image-gallery',
                    FACILITY_LOCATOR_URL . 'admin/js/facility-locator-image-gallery.js',
                    array('jquery', 'jquery-ui-sortable'),
                    $this->version,
                    false
                );

                // Enqueue Google Maps with proper callback
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
                    true // Load in footer
                );
            } else {
                // Still enqueue image gallery script even without Google Maps
                wp_enqueue_script(
                    $this->plugin_name . '-image-gallery',
                    FACILITY_LOCATOR_URL . 'admin/js/facility-locator-image-gallery.js',
                    array('jquery', 'jquery-ui-sortable'),
                    $this->version,
                    false
                );
            }
        }

        // Localize script with proper API key check
        wp_localize_script($this->plugin_name, 'facilityLocator', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('facility_locator_nonce'),
            'hasApiKey' => !empty($api_key),
            'currentScreen' => $current_screen ? $current_screen->id : '',
            'googleMapsApiKey' => !empty($api_key) ? $api_key : '',
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

        // Taxonomy submenus
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
            'sanitize_callback' => 'sanitize_text_field',
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

        try {
            $taxonomy = $this->taxonomy_manager->get_taxonomy($type);

            if (!$taxonomy) {
                echo '<div class="wrap">';
                echo '<h1>Taxonomy Error</h1>';
                echo '<div class="notice notice-error">';
                echo '<p>Unable to load the requested taxonomy. Please try refreshing the page.</p>';
                echo '</div>';
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
            echo '<h1>Error</h1>';
            echo '<div class="notice notice-error"><p>An error occurred while loading the taxonomy page. Please try again.</p></div>';
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
     * AJAX handler for saving/updating a facility
     */
    public function ajax_save_facility()
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

        // Validate required fields
        if (empty($_POST['name']) || empty($_POST['address']) || empty($_POST['lat']) || empty($_POST['lng'])) {
            wp_send_json_error('Name, address, and location coordinates are required');
            return;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $data = $_POST;

        try {
            if ($id > 0) {
                // Update existing facility
                $result = $this->facilities->update_facility($id, $data);
                if ($result) {
                    do_action('facility_locator_facility_saved', $id);
                    wp_send_json_success(array(
                        'id' => $id,
                        'message' => 'Facility updated successfully',
                    ));
                } else {
                    wp_send_json_error('Failed to update facility');
                }
            } else {
                // Create new facility
                $result = $this->facilities->add_facility($data);
                if ($result && is_numeric($result)) {
                    do_action('facility_locator_facility_saved', $result);
                    wp_send_json_success(array(
                        'id' => $result,
                        'message' => 'Facility created successfully',
                    ));
                } else {
                    wp_send_json_error('Failed to create facility');
                }
            }
        } catch (Exception $e) {
            wp_send_json_error('Database error: ' . $e->getMessage());
        }

        wp_die();
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
