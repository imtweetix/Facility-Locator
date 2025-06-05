<?php

/**
 * Fired during plugin activation and deactivation
 * Enhanced with database table creation and default taxonomy setup
 */
class Facility_Locator_Activator
{

    /**
     * Run on plugin activation
     */
    public static function activate()
    {
        // Create database tables
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-facility-locator-taxonomies.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-facility-locator-facilities.php';

        // Create taxonomy table first
        Facility_Locator_Base_Taxonomy::create_table();

        // Create facilities table
        Facility_Locator_Facilities::create_table();

        // Set default options if they don't exist
        if (!get_option('facility_locator_map_zoom')) {
            update_option('facility_locator_map_zoom', 10);
        }

        if (!get_option('facility_locator_map_height')) {
            update_option('facility_locator_map_height', 500);
        }

        if (!get_option('facility_locator_cta_text')) {
            update_option('facility_locator_cta_text', 'Find a Facility');
        }

        if (!get_option('facility_locator_cta_color')) {
            update_option('facility_locator_cta_color', '#007bff');
        }

        if (!get_option('facility_locator_default_pin_image')) {
            update_option('facility_locator_default_pin_image', '');
        }

        // Empty form steps for new installation
        if (!get_option('facility_locator_form_steps')) {
            update_option('facility_locator_form_steps', json_encode(array()));
        }

        // Flush rewrite rules
        flush_rewrite_rules();

        if (WP_DEBUG) {
            error_log('Facility Locator: Plugin activation completed - clean slate ready');
        }
    }

    /**
     * Run on plugin deactivation
     */
    public static function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Clear scheduled events
        wp_clear_scheduled_hook('facility_locator_cache_maintenance');
        wp_clear_scheduled_hook('facility_locator_warm_caches');

        // Clear all plugin caches
        if (class_exists('Facility_Locator_Cache_Manager')) {
            Facility_Locator_Cache_Manager::clear_all_caches();
        }

        if (WP_DEBUG) {
            error_log('Facility Locator: Plugin deactivated, caches cleared');
        }
    }

    /**
     * Check database tables and create if missing
     */
    public static function check_database_tables()
    {
        global $wpdb;

        $facilities_table = $wpdb->prefix . 'facility_locator_facilities';
        $taxonomies_table = $wpdb->prefix . 'facility_locator_taxonomies';

        $facilities_exists = $wpdb->get_var("SHOW TABLES LIKE '$facilities_table'");
        $taxonomies_exists = $wpdb->get_var("SHOW TABLES LIKE '$taxonomies_table'");

        $tables_created = false;

        if (!$taxonomies_exists) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-facility-locator-taxonomies.php';
            Facility_Locator_Base_Taxonomy::create_table();
            $tables_created = true;
        }

        if (!$facilities_exists) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-facility-locator-facilities.php';
            Facility_Locator_Facilities::create_table();
            $tables_created = true;
        }

        return $tables_created;
    }

    /**
     * Verify plugin requirements
     */
    public static function check_requirements()
    {
        $errors = array();

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.2', '<')) {
            $errors[] = 'PHP 7.2 or higher is required. You are running ' . PHP_VERSION;
        }

        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            $errors[] = 'WordPress 5.0 or higher is required. You are running ' . get_bloginfo('version');
        }

        // Check database permissions
        global $wpdb;
        $test_table = $wpdb->prefix . 'facility_locator_test';
        $wpdb->query("CREATE TABLE IF NOT EXISTS $test_table (id INT AUTO_INCREMENT PRIMARY KEY)");

        if ($wpdb->last_error) {
            $errors[] = 'Database table creation failed. Please check database permissions.';
        } else {
            // Clean up test table
            $wpdb->query("DROP TABLE IF EXISTS $test_table");
        }

        return $errors;
    }

    /**
     * Run upgrade routines if needed
     */
    public static function maybe_upgrade()
    {
        $current_version = get_option('facility_locator_version', '0.0.0');
        $plugin_version = FACILITY_LOCATOR_VERSION;

        if (version_compare($current_version, $plugin_version, '<')) {
            // Run upgrade routines
            self::upgrade_database();

            // Update version
            update_option('facility_locator_version', $plugin_version);

            if (WP_DEBUG) {
                error_log("Facility Locator: Upgraded from {$current_version} to {$plugin_version}");
            }
        }
    }

    /**
     * Upgrade database structure if needed
     */
    private static function upgrade_database()
    {
        global $wpdb;

        // Check if we need to add new indexes or columns
        $taxonomies_table = $wpdb->prefix . 'facility_locator_taxonomies';
        $facilities_table = $wpdb->prefix . 'facility_locator_facilities';

        // Example: Add fulltext index if it doesn't exist
        $indexes = $wpdb->get_results("SHOW INDEX FROM $taxonomies_table WHERE Key_name = 'search_idx'");
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE $taxonomies_table ADD FULLTEXT KEY search_idx (name, description)");
        }

        $indexes = $wpdb->get_results("SHOW INDEX FROM $facilities_table WHERE Key_name = 'search_idx'");
        if (empty($indexes)) {
            $wpdb->query("ALTER TABLE $facilities_table ADD FULLTEXT KEY search_idx (name, address, description)");
        }
    }
}
