<?php

/**
 * The public-facing functionality with taxonomy support
 */
class Facility_Locator_Public
{
    private $plugin_name;
    private $version;
    private $facilities;
    private $taxonomy_manager;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->facilities = new Facility_Locator_Facilities();
        $this->taxonomy_manager = new Facility_Locator_Taxonomy_Manager();
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

        // Get available taxonomies for form configuration
        $available_taxonomies = array();
        $all_taxonomies = $this->taxonomy_manager->get_all_taxonomies();

        foreach ($all_taxonomies as $type => $taxonomy) {
            $items = $taxonomy->get_all();
            $available_taxonomies[$type] = array(
                'label' => $taxonomy->get_display_name(),
                'items' => array_map(function ($item) {
                    return array(
                        'id' => $item->id,
                        'name' => $item->name,
                        'slug' => $item->slug
                    );
                }, $items)
            );
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
            'availableTaxonomies' => $available_taxonomies,
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

        // Extract filter criteria for all taxonomies
        $filter_criteria = array();
        $taxonomy_types = $this->taxonomy_manager->get_taxonomy_types();

        foreach ($taxonomy_types as $type) {
            if (isset($form_data[$type]) && is_array($form_data[$type])) {
                $filter_criteria[$type] = array_map('sanitize_text_field', $form_data[$type]);
            }
        }

        // Get facilities
        $facilities = $this->facilities->get_facilities($filter_criteria);

        // Get all available taxonomy options for filter dropdowns
        $all_taxonomies = $this->taxonomy_manager->get_all_for_filters();

        // Format response
        $response = array(
            'facilities' => $facilities,
            'filters' => $all_taxonomies,
        );

        // Send response
        wp_send_json_success($response);
    }
}
