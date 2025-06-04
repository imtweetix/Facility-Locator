<?php
/**
 * Template Loader for Facility Locator
 *
 * This class handles template loading and allows theme overrides.
 */
class Facility_Locator_Template_Loader {

    /**
     * Get template path
     *
     * Looks for template files in the theme first, then plugin
     *
     * @param string $template_name Template file name
     * @param string $template_path Path within the template directory
     * @param string $default_path Default path if not found in theme
     * @return string Full path to template file
     */
    public static function locate_template($template_name, $template_path = '', $default_path = '') {
        // Set default path if not provided
        if (!$default_path) {
            $default_path = FACILITY_LOCATOR_PATH . 'templates/';
        }

        // Look for template in theme
        $template = locate_template(
            array(
                trailingslashit($template_path) . $template_name,
                $template_name,
            )
        );

        // Get default template if not found in theme
        if (!$template) {
            $template = trailingslashit($default_path) . $template_name;
        }

        // Return located template
        return apply_filters('facility_locator_locate_template', $template, $template_name, $template_path);
    }

    /**
     * Get and include template file
     *
     * @param string $template_name Template file name
     * @param array $args Arguments to pass to the template
     * @param string $template_path Path within the template directory
     * @param string $default_path Default path if not found in theme
     */
    public static function get_template($template_name, $args = array(), $template_path = '', $default_path = '') {
        // Locate template file
        $located = self::locate_template($template_name, $template_path, $default_path);

        // Continue only if template exists
        if (!file_exists($located)) {
            /* translators: %s template */
            _doing_it_wrong(__FUNCTION__, sprintf(__('"%s" does not exist.', 'facility-locator'), '<code>' . $located . '</code>'), '1.0.0');
            return;
        }

        // Extract args to make them available in template
        if (!empty($args) && is_array($args)) {
            extract($args);
        }

        // Include template
        include $located;
    }
}
