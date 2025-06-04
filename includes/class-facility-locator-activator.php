<?php

/**
 * Fired during plugin activation and deactivation
 */
class Facility_Locator_Activator
{

    /**
     * Run on plugin activation
     */
    public static function activate()
    {
        // Create database tables
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-facility-locator-facilities.php';
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
    }

    /**
     * Run on plugin deactivation
     */
    public static function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
