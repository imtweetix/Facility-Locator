<?php

/**
 * The core plugin class with taxonomy system and cache management
 * Performance optimized with cache initialization and maintenance
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
        require_once FACILITY_LOCATOR_PATH . 'includes/class-facility-locator-cache-manager.php';
        require_once FACILITY_LOCATOR_PATH . 'includes/class-facility-locator-taxonomies.php';
        require_once FACILITY_LOCATOR_PATH . 'includes/class-facility-locator-facilities.php';
        require_once FACILITY_LOCATOR_PATH . 'includes/class-facility-locator-template-loader.php';
        require_once FACILITY_LOCATOR_PATH . 'admin/class-facility-locator-admin.php';
        require_once FACILITY_LOCATOR_PATH . 'public/class-facility-locator-public.php';

        $this->loader = new Facility_Locator_Loader();

        // Initialize cache management system
        Facility_Locator_Cache_Manager::init();
        Facility_Locator_Cache_Manager::schedule_cache_maintenance();

        // Load production config if it exists
        $production_config = FACILITY_LOCATOR_PATH . 'includes/production-config.php';
        if (file_exists($production_config)) {
            require_once $production_config;
        }
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

        // Taxonomy AJAX actions - dynamically register for all taxonomy types with error handling
        try {
            $taxonomy_manager = new Facility_Locator_Taxonomy_Manager();
            $taxonomy_types = $taxonomy_manager->get_taxonomy_types();

            foreach ($taxonomy_types as $type) {
                $this->loader->add_action('wp_ajax_save_taxonomy_' . $type, $plugin_admin, 'ajax_save_taxonomy');
                $this->loader->add_action('wp_ajax_delete_taxonomy_' . $type, $plugin_admin, 'ajax_delete_taxonomy');
            }
        } catch (Exception $e) {
            if (WP_DEBUG) {
                error_log('Facility Locator: Error setting up taxonomy AJAX hooks: ' . $e->getMessage());
            }
        }

        // Cache management hooks
        $this->loader->add_action('facility_locator_facility_saved', 'Facility_Locator_Cache_Manager', 'clear_facility_caches');
        $this->loader->add_action('facility_locator_facility_deleted', 'Facility_Locator_Cache_Manager', 'clear_facility_caches');
        $this->loader->add_action('facility_locator_taxonomy_saved', 'Facility_Locator_Cache_Manager', 'clear_taxonomy_caches');
        $this->loader->add_action('facility_locator_taxonomy_deleted', 'Facility_Locator_Cache_Manager', 'clear_taxonomy_caches');
        $this->loader->add_action('facility_locator_settings_updated', 'Facility_Locator_Cache_Manager', 'clear_frontend_caches');
    }

    private function define_public_hooks()
    {
        $plugin_public = new Facility_Locator_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_shortcode('facility_locator', $plugin_public, 'shortcode_output');
        $this->loader->add_action('wp_ajax_get_facilities', $plugin_public, 'ajax_get_facilities');
        $this->loader->add_action('wp_ajax_nopriv_get_facilities', $plugin_public, 'ajax_get_facilities');

        // Frontend cache clearing hook
        $this->loader->add_action('wp_footer', $plugin_public, 'clear_frontend_caches_if_needed');
    }

    public function run()
    {
        $this->loader->run();

        // Warm up critical caches on init (only for admin or if specifically requested)
        if (is_admin() || (defined('FACILITY_LOCATOR_WARM_CACHE') && FACILITY_LOCATOR_WARM_CACHE)) {
            add_action('init', array($this, 'maybe_warm_caches'), 999);
        }
    }

    /**
     * Maybe warm up caches on plugin initialization
     */
    public function maybe_warm_caches()
    {
        // Only warm caches occasionally to avoid performance impact
        $last_warm = get_transient('facility_locator_last_cache_warm');

        if ($last_warm === false) {
            // Warm caches in the background
            wp_schedule_single_event(time() + 30, 'facility_locator_warm_caches');

            // Set transient to prevent too frequent warming
            set_transient('facility_locator_last_cache_warm', time(), 3600); // 1 hour
        }

        // Hook for the background cache warming
        add_action('facility_locator_warm_caches', array('Facility_Locator_Cache_Manager', 'warm_up_caches'));
    }

    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    public function get_version()
    {
        return $this->version;
    }

    /**
     * Get loader instance (for testing or external access)
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Plugin activation hook - called from main plugin file
     */
    public static function on_activation()
    {
        // Clear all caches on activation
        Facility_Locator_Cache_Manager::clear_all_caches();

        // Optimize database
        Facility_Locator_Cache_Manager::optimize_database();

        // Schedule cache maintenance
        Facility_Locator_Cache_Manager::schedule_cache_maintenance();

        if (WP_DEBUG) {
            error_log('Facility Locator: Plugin activated with cache optimization');
        }
    }

    /**
     * Plugin deactivation hook - called from main plugin file
     */
    public static function on_deactivation()
    {
        // Clear all caches on deactivation
        Facility_Locator_Cache_Manager::clear_all_caches();

        // Clear scheduled events
        wp_clear_scheduled_hook('facility_locator_cache_maintenance');
        wp_clear_scheduled_hook('facility_locator_warm_caches');

        if (WP_DEBUG) {
            error_log('Facility Locator: Plugin deactivated, caches cleared');
        }
    }
}
