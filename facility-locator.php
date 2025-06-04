<?php

/**
 * Plugin Name: Facility Locator (DEV)
 * Description: A WordPress plugin that displays facilities on a map based on user preferences through a multi-step form.
 * Version: 1.0.0
 * Author: Guardian Recovery
 * Author URI: https://guardianrecovery.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: facility-locator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FACILITY_LOCATOR_VERSION', '1.0.0');
define('FACILITY_LOCATOR_PATH', plugin_dir_path(__FILE__));
define('FACILITY_LOCATOR_URL', plugin_dir_url(__FILE__));

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

// Include required files
require_once FACILITY_LOCATOR_PATH . 'includes/class-facility-locator.php';

// Start the plugin
function run_facility_locator()
{
    $plugin = new Facility_Locator();
    $plugin->run();
}
run_facility_locator();
