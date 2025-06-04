<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * This file is responsible for cleaning up when the plugin is deleted from WordPress.
 * It removes all plugin-related data from the database to ensure no orphaned data remains.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('facility_locator_google_maps_api_key');
delete_option('facility_locator_map_zoom');
delete_option('facility_locator_map_height');
delete_option('facility_locator_cta_text');
delete_option('facility_locator_cta_color');
delete_option('facility_locator_default_pin_image');
delete_option('facility_locator_form_steps');

// Drop custom database tables
global $wpdb;

// Drop facilities table
$facilities_table = $wpdb->prefix . 'facility_locator_facilities';
$wpdb->query("DROP TABLE IF EXISTS $facilities_table");

// Drop new unified taxonomies table
$taxonomies_table = $wpdb->prefix . 'facility_locator_taxonomies';
$wpdb->query("DROP TABLE IF EXISTS $taxonomies_table");

// Drop old taxonomy tables if they exist (for cleanup from previous versions)
$old_levels_table = $wpdb->prefix . 'facility_locator_levels_of_care';
$old_features_table = $wpdb->prefix . 'facility_locator_program_features';
$wpdb->query("DROP TABLE IF EXISTS $old_levels_table");
$wpdb->query("DROP TABLE IF EXISTS $old_features_table");

// Remove uploaded pin files
$upload_dir = wp_upload_dir();
$facility_pins_dir = $upload_dir['basedir'] . '/facility-pins';

if (file_exists($facility_pins_dir)) {
    // Remove all files in the directory
    $files = glob($facility_pins_dir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }

    // Remove the directory
    rmdir($facility_pins_dir);
}

// Clear any cached data that might be related to this plugin
wp_cache_flush();
