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
delete_option('facility_locator_default_pin');

// Drop custom database table
global $wpdb;
$table_name = $wpdb->prefix . 'facility_locator_facilities';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

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
