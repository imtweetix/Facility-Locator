<?php

/**
 * The public-facing functionality of the plugin
 */
class Facility_Locator_Public
{

    private $plugin_name;
    private $version;
    private $facilities;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->facilities = new Facility_Locator_Facilities();
    }

    /**
     * Register the stylesheets for the public-facing side of the site
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, FACILITY_LOCATOR_URL . 'public/css/facility-locator-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, FACILITY_LOCATOR_URL . 'public/js/facility-locator-public.js', array('jquery'), $this->version, false);

        // Google Maps API
        $api_key = get_option('facility_locator_google_maps_api_key', '');
        if (!empty($api_key)) {
            wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places", array(), null, true);
        }

        // Localize script
        wp_localize_script($this->plugin_name, 'facilityLocator', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('facility_locator_public_nonce'),
            'settings' => array(
                'mapZoom' => get_option('facility_locator_map_zoom', 10),
                'mapHeight' => get_option('facility_locator_map_height', 500),
                'ctaText' => get_option('facility_locator_cta_text', 'Find a Facility'),
                'ctaColor' => get_option('facility_locator_cta_color', '#007bff'),
            ),
            'formSteps' => json_decode(get_option('facility_locator_form_steps', '[]')),
        ));
    }

    /**
     * Shortcode output
     */
    public function shortcode_output($atts)
    {
        $atts = shortcode_atts(array(
            'id' => uniqid('facility-locator-'),
        ), $atts, 'facility_locator');

        $id = sanitize_html_class($atts['id']);

        // Get settings
        $cta_text = get_option('facility_locator_cta_text', 'Find a Facility');
        $cta_color = get_option('facility_locator_cta_color', '#007bff');

        // Start output buffering
        ob_start();

        // Include template using template loader
        Facility_Locator_Template_Loader::get_template(
            'public/public-template.php',
            array(
                'id' => $id,
                'cta_text' => $cta_text,
                'cta_color' => $cta_color,
            )
        );

        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * AJAX handler for getting facilities
     */
    public function ajax_get_facilities()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'facility_locator_public_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Get form data
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();

        // Extract filter criteria
        $filter_criteria = array();

        // Categories
        if (isset($form_data['categories']) && is_array($form_data['categories'])) {
            $filter_criteria['categories'] = array_map('sanitize_text_field', $form_data['categories']);
        }

        // Attributes
        if (isset($form_data['attributes']) && is_array($form_data['attributes'])) {
            $filter_criteria['attributes'] = array_map('sanitize_text_field', $form_data['attributes']);
        }

        // Get facilities
        $facilities = $this->facilities->get_facilities($filter_criteria);

        // Get all available categories and attributes for filter dropdowns
        $all_categories = $this->facilities->get_categories();
        $all_attributes = $this->facilities->get_attributes();

        // Format response
        $response = array(
            'facilities' => $facilities,
            'filters' => array(
                'categories' => $all_categories,
                'attributes' => $all_attributes,
            ),
        );

        // Send response
        wp_send_json_success($response);
    }
}
