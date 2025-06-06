<?php

/**
 * The public-facing functionality with image gallery support and fixed pin functionality
 * Updated to handle multiple images and custom pin rendering
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
     * Register the stylesheets for the public-facing side
     */
    public function enqueue_styles()
    {
        // Main public CSS
        wp_enqueue_style(
            $this->plugin_name,
            FACILITY_LOCATOR_URL . 'public/css/facility-locator-public.css',
            array(),
            $this->version,
            'all'
        );

        // Frontend-specific CSS with Recovery.com + Google Maps styling
        wp_enqueue_style(
            $this->plugin_name . '-frontend',
            FACILITY_LOCATOR_URL . 'public/css/facility-locator-frontend.css',
            array($this->plugin_name),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side
     */
    public function enqueue_scripts()
    {
        // Main public JavaScript
        wp_enqueue_script(
            $this->plugin_name,
            FACILITY_LOCATOR_URL . 'public/js/facility-locator-public.js',
            array('jquery'),
            $this->version,
            true // Load in footer
        );

        // Google Maps API with performance optimization
        $api_key = get_option('facility_locator_google_maps_api_key', '');
        if (!empty($api_key)) {
            // Enqueue Google Maps with proper parameters
            $google_maps_url = add_query_arg(array(
                'key' => $api_key,
                'libraries' => 'places',
                'v' => 'weekly'
            ), 'https://maps.googleapis.com/maps/api/js');

            wp_enqueue_script(
                'google-maps-frontend',
                $google_maps_url,
                array(),
                null,
                true
            );

            // Add MarkerClusterer library for performance
            wp_enqueue_script(
                'marker-clusterer',
                'https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js',
                array('google-maps-frontend'),
                null,
                true
            );
        }

        // Get available taxonomies for form configuration with caching
        $available_taxonomies = $this->get_cached_available_taxonomies();

        // Get cached settings including default pin image
        $settings = $this->get_cached_settings();

        // Enhanced localization with proper API key handling
        wp_localize_script($this->plugin_name, 'facilityLocator', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('facility_locator_public_nonce'),
            'settings' => $settings,
            'formSteps' => $this->get_cached_form_steps(),
            'availableTaxonomies' => $available_taxonomies,
            'hasApiKey' => !empty($api_key),
            'googleMapsApiKey' => !empty($api_key) ? $api_key : '',
        ));
    }

    /**
     * Get cached available taxonomies
     */
    private function get_cached_available_taxonomies()
    {
        $cache_key = 'facility_locator_available_taxonomies';
        $cached_taxonomies = get_transient($cache_key);

        if ($cached_taxonomies !== false) {
            return $cached_taxonomies;
        }

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

        // Cache for 30 minutes
        set_transient($cache_key, $available_taxonomies, 1800);

        return $available_taxonomies;
    }

    /**
     * Get cached settings including default pin image
     */
    private function get_cached_settings()
    {
        static $cached_settings = null;

        if ($cached_settings === null) {
            $cached_settings = array(
                'mapZoom' => get_option('facility_locator_map_zoom', 10),
                'mapHeight' => get_option('facility_locator_map_height', 500),
                'ctaText' => get_option('facility_locator_cta_text', 'Find a Facility'),
                'ctaColor' => get_option('facility_locator_cta_color', '#007bff'),
                'defaultPinImage' => get_option('facility_locator_default_pin_image', ''),
            );
        }

        return $cached_settings;
    }

    /**
     * Get cached form steps
     */
    private function get_cached_form_steps()
    {
        $cache_key = 'facility_locator_form_steps';
        $cached_steps = get_transient($cache_key);

        if ($cached_steps !== false) {
            return $cached_steps;
        }

        $form_steps = json_decode(get_option('facility_locator_form_steps', '[]'), true);

        // Cache for 1 hour
        set_transient($cache_key, $form_steps, 3600);

        return $form_steps;
    }

    /**
     * Shortcode output with enhanced styling
     */
    public function shortcode_output($atts)
    {
        $atts = shortcode_atts(array(
            'id' => uniqid('facility-locator-'),
        ), $atts, 'facility_locator');

        $id = sanitize_html_class($atts['id']);

        // Get cached settings
        $settings = $this->get_cached_settings();

        // Start output buffering
        ob_start();

        // Include template using template loader
        Facility_Locator_Template_Loader::get_template(
            'public/public-template.php',
            array(
                'id' => $id,
                'cta_text' => $settings['ctaText'],
                'cta_color' => $settings['ctaColor'],
            )
        );

        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * AJAX handler for getting facilities with image gallery support
     */
    public function ajax_get_facilities()
    {
        // Clean output buffer before AJAX response
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'facility_locator_public_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Get form data with validation
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();

        // Extract filter criteria for all taxonomies with validation
        $filter_criteria = array();
        $taxonomy_types = $this->taxonomy_manager->get_taxonomy_types();

        foreach ($taxonomy_types as $type) {
            if (isset($form_data[$type]) && is_array($form_data[$type])) {
                $filter_criteria[$type] = array_map('sanitize_text_field', $form_data[$type]);
            }
        }

        // Get facilities with image gallery data
        $facilities = $this->facilities->get_facilities($filter_criteria);

        // Process facilities to ensure images are properly formatted
        foreach ($facilities as &$facility) {
            // Ensure images is an array
            if (!is_array($facility->images)) {
                $facility->images = array();
            }

            // Validate image URLs
            $facility->images = array_filter($facility->images, function ($url) {
                return !empty($url) && filter_var($url, FILTER_VALIDATE_URL);
            });

            // Ensure custom pin image is properly set
            if (empty($facility->custom_pin_image)) {
                $facility->custom_pin_image = '';
            }
        }

        // Get all available taxonomy options for filter dropdowns
        $all_taxonomies = $this->facilities->get_taxonomy_filters();

        // Format response
        $response = array(
            'facilities' => $facilities,
            'filters' => $all_taxonomies,
        );

        // Send response
        wp_send_json_success($response);

        wp_die(); // Ensure clean AJAX termination
    }

    /**
     * Clear frontend caches when needed
     */
    public function clear_frontend_caches()
    {
        delete_transient('facility_locator_available_taxonomies');
        delete_transient('facility_locator_form_steps');

        if (WP_DEBUG) {
            error_log('Facility Locator: Frontend caches cleared');
        }
    }

    /**
     * Clear frontend caches if needed (hook for footer)
     */
    public function clear_frontend_caches_if_needed()
    {
        // This method is called in the footer but doesn't automatically clear caches
        // Caches are cleared by specific actions in the cache manager
    }
}
