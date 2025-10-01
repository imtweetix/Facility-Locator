<?php

/**
 * Plugin Name: Facility Locator (DEV)
 * Description: A modern WordPress plugin that displays facilities on a map with advanced filtering, image galleries, and Google Maps integration.
 * Version: 1.1.0
 * Author: Guardian Recovery
 * Author URI: https://guardianrecovery.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: facility-locator
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.2
 * Network: false
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FACILITY_LOCATOR_VERSION', '1.1.0');
define('FACILITY_LOCATOR_PATH', plugin_dir_path(__FILE__));
define('FACILITY_LOCATOR_URL', plugin_dir_url(__FILE__));
define('FACILITY_LOCATOR_BASENAME', plugin_basename(__FILE__));

// Minimum requirements
define('FACILITY_LOCATOR_MIN_PHP', '7.2');
define('FACILITY_LOCATOR_MIN_WP', '5.0');

/**
 * The code that runs during plugin activation.
 */
function activate_facility_locator()
{
    require_once FACILITY_LOCATOR_PATH . 'includes/class-facility-locator-activator.php';
    Facility_Locator_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_facility_locator()
{
    require_once FACILITY_LOCATOR_PATH . 'includes/class-facility-locator-activator.php';
    Facility_Locator_Activator::deactivate();
}

register_activation_hook(__FILE__, 'activate_facility_locator');
register_deactivation_hook(__FILE__, 'deactivate_facility_locator');

// Check minimum requirements
if (!facility_locator_check_requirements()) {
    return;
}

// Include required files
require_once FACILITY_LOCATOR_PATH . 'includes/class-facility-locator.php';

// Start the plugin
function run_facility_locator()
{
    $plugin = new Facility_Locator();
    $plugin->run();
}

/**
 * Check if minimum requirements are met
 */
function facility_locator_check_requirements(): bool
{
    // Check PHP version
    if (version_compare(PHP_VERSION, FACILITY_LOCATOR_MIN_PHP, '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            printf(
                esc_html__('Facility Locator requires PHP %s or higher. You are running PHP %s.', 'facility-locator'),
                FACILITY_LOCATOR_MIN_PHP,
                PHP_VERSION
            );
            echo '</p></div>';
        });
        return false;
    }

    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, FACILITY_LOCATOR_MIN_WP, '<')) {
        add_action('admin_notices', function() {
            global $wp_version;
            echo '<div class="notice notice-error"><p>';
            printf(
                esc_html__('Facility Locator requires WordPress %s or higher. You are running WordPress %s.', 'facility-locator'),
                FACILITY_LOCATOR_MIN_WP,
                $wp_version
            );
            echo '</p></div>';
        });
        return false;
    }

    return true;
}

run_facility_locator();
