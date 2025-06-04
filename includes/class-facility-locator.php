<?php

/**
 * The core plugin class with taxonomy system
 */
class Facility_Locator
{
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct()
    {
        $this->plugin_name = 'facility-locator';
        $this->version = FACILITY_LOCATOR_VERSION;

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies()
    {
        require_once FACILITY_LOCATOR_PATH . 'includes/class-facility-locator-loader.php';
        require_once FACILITY_LOCATOR_PATH . 'includes/class-facility-locator-taxonomies.php';
        require_once FACILITY_LOCATOR_PATH . 'includes/class-facility-locator-facilities.php';
        require_once FACILITY_LOCATOR_PATH . 'includes/class-facility-locator-template-loader.php';
        require_once FACILITY_LOCATOR_PATH . 'admin/class-facility-locator-admin.php';
        require_once FACILITY_LOCATOR_PATH . 'public/class-facility-locator-public.php';

        $this->loader = new Facility_Locator_Loader();
    }

    private function define_admin_hooks()
    {
        $plugin_admin = new Facility_Locator_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');

        // Facility AJAX actions
        $this->loader->add_action('wp_ajax_save_facility', $plugin_admin, 'ajax_save_facility');
        $this->loader->add_action('wp_ajax_delete_facility', $plugin_admin, 'ajax_delete_facility');

        // Taxonomy AJAX actions - dynamically register for all taxonomy types
        $taxonomy_manager = new Facility_Locator_Taxonomy_Manager();
        $taxonomy_types = $taxonomy_manager->get_taxonomy_types();

        foreach ($taxonomy_types as $type) {
            $this->loader->add_action('wp_ajax_save_taxonomy_' . $type, $plugin_admin, 'ajax_save_taxonomy');
            $this->loader->add_action('wp_ajax_delete_taxonomy_' . $type, $plugin_admin, 'ajax_delete_taxonomy');
        }
    }

    private function define_public_hooks()
    {
        $plugin_public = new Facility_Locator_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_shortcode('facility_locator', $plugin_public, 'shortcode_output');
        $this->loader->add_action('wp_ajax_get_facilities', $plugin_public, 'ajax_get_facilities');
        $this->loader->add_action('wp_ajax_nopriv_get_facilities', $plugin_public, 'ajax_get_facilities');
    }

    public function run()
    {
        $this->loader->run();
    }

    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    public function get_version()
    {
        return $this->version;
    }
}
