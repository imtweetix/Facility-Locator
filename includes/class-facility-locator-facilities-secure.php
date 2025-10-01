<?php

/**
 * Secure CRUD operations for facilities with modern PHP features
 * Implements critical security fixes and optimizations
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Facility_Locator_Facilities_Secure
{
    private string $table_name;
    private Facility_Locator_Taxonomy_Manager $taxonomy_manager;
    private Facility_Locator_Cache_Manager $cache_manager;

    // Cache constants
    private const CACHE_GROUP = 'facility_locator_facilities';
    private const CACHE_EXPIRATION = 3600; // 1 hour
    private const CACHE_VERSION = '1.1';

    // Cache keys
    private const CACHE_KEY_ALL_FACILITIES = 'all_facilities';
    private const CACHE_KEY_FACILITY_PREFIX = 'facility_';
    private const CACHE_KEY_FILTERED_PREFIX = 'filtered_';

    // Validation constants
    private const MAX_IMAGES = 5;
    private const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const MAX_NAME_LENGTH = 255;
    private const MAX_ADDRESS_LENGTH = 255;
    private const MAX_PHONE_LENGTH = 50;
    private const MAX_WEBSITE_LENGTH = 255;

    // Allowed taxonomy types for security
    private const ALLOWED_TAXONOMY_TYPES = [
        'levels_of_care',
        'features',
        'therapies',
        'environment',
        'location',
        'insurance_providers'
    ];

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'facility_locator_facilities';
        $this->taxonomy_manager = new Facility_Locator_Taxonomy_Manager();
        $this->cache_manager = new Facility_Locator_Cache_Manager();
    }

    /**
     * Create the database table with security optimizations
     */
    public static function create_table(): bool
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'facility_locator_facilities';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address varchar(255) NOT NULL,
            lat decimal(10,8) NOT NULL,
            lng decimal(11,8) NOT NULL,
            phone varchar(50),
            website varchar(255),
            description text,
            taxonomies longtext,
            custom_pin_image varchar(255),
            images longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_lat_lng (lat, lng),
            INDEX idx_name (name),
            FULLTEXT idx_search (name, address, description)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        return dbDelta($sql) !== false;
    }

    /**
     * Get all facilities with secure filtering
     */
    public function get_facilities(array $args = []): array
    {
        // Validate and sanitize arguments
        $args = $this->validate_get_facilities_args($args);

        // Try to get from cache first
        $cache_key = $this->generate_cache_key($args);
        $cached_facilities = $this->cache_manager->get($cache_key, self::CACHE_GROUP);

        if ($cached_facilities !== false) {
            return $cached_facilities;
        }

        global $wpdb;

        try {
            // Build secure query
            $query_parts = $this->build_secure_query($args);

            $sql = "SELECT * FROM {$this->table_name} {$query_parts['where']} {$query_parts['order']} {$query_parts['limit']}";

            $results = $wpdb->get_results(
                $wpdb->prepare($sql, ...$query_parts['params']),
                ARRAY_A
            );

            if ($wpdb->last_error) {
                error_log('Facility Locator DB Error: ' . $wpdb->last_error);
                throw new Exception('Database query failed');
            }

            // Process and format results
            $facilities = array_map([$this, 'format_facility_output'], $results ?: []);

            // Cache the results
            $this->cache_manager->set($cache_key, $facilities, self::CACHE_GROUP, self::CACHE_EXPIRATION);

            return $facilities;

        } catch (Exception $e) {
            error_log('Facility Locator Error in get_facilities: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Build secure query with proper escaping and validation
     */
    private function build_secure_query(array $args): array
    {
        $where_clauses = ['1=1'];
        $params = [];

        // Search functionality with proper escaping
        if (!empty($args['search'])) {
            $search_term = sanitize_text_field($args['search']);
            $where_clauses[] = "(name LIKE %s OR address LIKE %s OR description LIKE %s)";
            $search_pattern = '%' . $wpdb->esc_like($search_term) . '%';
            $params[] = $search_pattern;
            $params[] = $search_pattern;
            $params[] = $search_pattern;
        }

        // Taxonomy filters with strict validation
        foreach (self::ALLOWED_TAXONOMY_TYPES as $taxonomy_type) {
            if (!empty($args[$taxonomy_type]) && is_array($args[$taxonomy_type])) {
                $taxonomy_ids = array_filter(
                    array_map('intval', $args[$taxonomy_type]),
                    function($id) { return $id > 0; }
                );

                if (!empty($taxonomy_ids)) {
                    $conditions = [];
                    foreach ($taxonomy_ids as $taxonomy_id) {
                        $conditions[] = "JSON_CONTAINS(taxonomies, JSON_OBJECT(%s, JSON_ARRAY(%d)))";
                        $params[] = esc_sql($taxonomy_type);
                        $params[] = $taxonomy_id;
                    }
                    if (!empty($conditions)) {
                        $where_clauses[] = '(' . implode(' OR ', $conditions) . ')';
                    }
                }
            }
        }

        // Geographic bounds filtering
        if (isset($args['bounds']) && is_array($args['bounds'])) {
            $bounds = $this->validate_bounds($args['bounds']);
            if ($bounds) {
                $where_clauses[] = "lat BETWEEN %f AND %f AND lng BETWEEN %f AND %f";
                $params[] = $bounds['south'];
                $params[] = $bounds['north'];
                $params[] = $bounds['west'];
                $params[] = $bounds['east'];
            }
        }

        // Build ORDER BY clause
        $order_by = 'ORDER BY name ASC';
        if (!empty($args['orderby'])) {
            $allowed_order_fields = ['name', 'created_at', 'updated_at'];
            $orderby = sanitize_text_field($args['orderby']);
            if (in_array($orderby, $allowed_order_fields, true)) {
                $order = (!empty($args['order']) && $args['order'] === 'DESC') ? 'DESC' : 'ASC';
                $order_by = "ORDER BY {$orderby} {$order}";
            }
        }

        // Build LIMIT clause
        $limit = '';
        if (!empty($args['limit'])) {
            $limit_num = min(max(1, intval($args['limit'])), 1000); // Max 1000 results
            $offset = max(0, intval($args['offset'] ?? 0));
            $limit = "LIMIT {$offset}, {$limit_num}";
        }

        return [
            'where' => empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses),
            'order' => $order_by,
            'limit' => $limit,
            'params' => $params
        ];
    }

    /**
     * Validate geographic bounds
     */
    private function validate_bounds(array $bounds): ?array
    {
        $required_keys = ['north', 'south', 'east', 'west'];

        foreach ($required_keys as $key) {
            if (!isset($bounds[$key]) || !is_numeric($bounds[$key])) {
                return null;
            }
        }

        $validated = [
            'north' => (float) $bounds['north'],
            'south' => (float) $bounds['south'],
            'east' => (float) $bounds['east'],
            'west' => (float) $bounds['west']
        ];

        // Validate coordinate ranges
        if ($validated['north'] < -90 || $validated['north'] > 90 ||
            $validated['south'] < -90 || $validated['south'] > 90 ||
            $validated['east'] < -180 || $validated['east'] > 180 ||
            $validated['west'] < -180 || $validated['west'] > 180) {
            return null;
        }

        return $validated;
    }

    /**
     * Get single facility by ID with security validation
     */
    public function get_facility(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $cache_key = self::CACHE_KEY_FACILITY_PREFIX . $id;
        $cached_facility = $this->cache_manager->get($cache_key, self::CACHE_GROUP);

        if ($cached_facility !== false) {
            return $cached_facility;
        }

        global $wpdb;

        try {
            $result = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
                ARRAY_A
            );

            if ($wpdb->last_error) {
                error_log('Facility Locator DB Error: ' . $wpdb->last_error);
                return null;
            }

            if (!$result) {
                return null;
            }

            $facility = $this->format_facility_output($result);
            $this->cache_manager->set($cache_key, $facility, self::CACHE_GROUP, self::CACHE_EXPIRATION);

            return $facility;

        } catch (Exception $e) {
            error_log('Facility Locator Error in get_facility: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Add new facility with comprehensive validation and security
     */
    public function add_facility(array $data): ?int
    {
        try {
            // Validate and sanitize input data
            $validated_data = $this->validate_facility_data($data);

            global $wpdb;

            $result = $wpdb->insert(
                $this->table_name,
                [
                    'name' => $validated_data['name'],
                    'address' => $validated_data['address'],
                    'lat' => $validated_data['lat'],
                    'lng' => $validated_data['lng'],
                    'phone' => $validated_data['phone'],
                    'website' => $validated_data['website'],
                    'description' => $validated_data['description'],
                    'taxonomies' => $validated_data['taxonomies'],
                    'custom_pin_image' => $validated_data['custom_pin_image'],
                    'images' => $validated_data['images']
                ],
                [
                    '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s'
                ]
            );

            if ($result === false) {
                error_log('Facility Locator DB Error: ' . $wpdb->last_error);
                throw new Exception('Failed to insert facility');
            }

            $facility_id = $wpdb->insert_id;

            // Clear relevant caches
            $this->clear_facility_caches();

            // Fire action hook for extensibility
            do_action('facility_locator_facility_added', $facility_id, $validated_data);

            return $facility_id;

        } catch (Exception $e) {
            error_log('Facility Locator Error in add_facility: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update facility with security validation
     */
    public function update_facility(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            // Validate and sanitize input data
            $validated_data = $this->validate_facility_data($data, $id);

            global $wpdb;

            $result = $wpdb->update(
                $this->table_name,
                [
                    'name' => $validated_data['name'],
                    'address' => $validated_data['address'],
                    'lat' => $validated_data['lat'],
                    'lng' => $validated_data['lng'],
                    'phone' => $validated_data['phone'],
                    'website' => $validated_data['website'],
                    'description' => $validated_data['description'],
                    'taxonomies' => $validated_data['taxonomies'],
                    'custom_pin_image' => $validated_data['custom_pin_image'],
                    'images' => $validated_data['images']
                ],
                ['id' => $id],
                [
                    '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s'
                ],
                ['%d']
            );

            if ($result === false) {
                error_log('Facility Locator DB Error: ' . $wpdb->last_error);
                throw new Exception('Failed to update facility');
            }

            // Clear relevant caches
            $this->clear_facility_caches($id);

            // Fire action hook for extensibility
            do_action('facility_locator_facility_updated', $id, $validated_data);

            return true;

        } catch (Exception $e) {
            error_log('Facility Locator Error in update_facility: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete facility with security checks
     */
    public function delete_facility(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        global $wpdb;

        try {
            // Get facility data before deletion for cleanup
            $facility = $this->get_facility($id);
            if (!$facility) {
                return false;
            }

            $result = $wpdb->delete(
                $this->table_name,
                ['id' => $id],
                ['%d']
            );

            if ($result === false) {
                error_log('Facility Locator DB Error: ' . $wpdb->last_error);
                throw new Exception('Failed to delete facility');
            }

            // Clean up uploaded images
            $this->cleanup_facility_images($facility);

            // Clear relevant caches
            $this->clear_facility_caches($id);

            // Fire action hook for extensibility
            do_action('facility_locator_facility_deleted', $id, $facility);

            return true;

        } catch (Exception $e) {
            error_log('Facility Locator Error in delete_facility: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate and sanitize facility data with comprehensive security checks
     */
    private function validate_facility_data(array $data, ?int $facility_id = null): array
    {
        $validated = [];

        // Validate required fields
        if (empty($data['name'])) {
            throw new InvalidArgumentException('Facility name is required');
        }
        $validated['name'] = sanitize_text_field(substr($data['name'], 0, self::MAX_NAME_LENGTH));

        if (empty($data['address'])) {
            throw new InvalidArgumentException('Facility address is required');
        }
        $validated['address'] = sanitize_text_field(substr($data['address'], 0, self::MAX_ADDRESS_LENGTH));

        // Validate coordinates
        if (!isset($data['lat']) || !is_numeric($data['lat'])) {
            throw new InvalidArgumentException('Valid latitude is required');
        }
        $lat = (float) $data['lat'];
        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException('Latitude must be between -90 and 90');
        }
        $validated['lat'] = $lat;

        if (!isset($data['lng']) || !is_numeric($data['lng'])) {
            throw new InvalidArgumentException('Valid longitude is required');
        }
        $lng = (float) $data['lng'];
        if ($lng < -180 || $lng > 180) {
            throw new InvalidArgumentException('Longitude must be between -180 and 180');
        }
        $validated['lng'] = $lng;

        // Validate optional fields
        $validated['phone'] = '';
        if (!empty($data['phone'])) {
            $phone = sanitize_text_field(substr($data['phone'], 0, self::MAX_PHONE_LENGTH));
            // Basic phone validation
            if (preg_match('/^[\d\s\-\+\(\)\.]+$/', $phone)) {
                $validated['phone'] = $phone;
            }
        }

        $validated['website'] = '';
        if (!empty($data['website'])) {
            $website = esc_url_raw(substr($data['website'], 0, self::MAX_WEBSITE_LENGTH));
            if (filter_var($website, FILTER_VALIDATE_URL)) {
                $validated['website'] = $website;
            }
        }

        $validated['description'] = '';
        if (!empty($data['description'])) {
            $validated['description'] = wp_kses_post($data['description']);
        }

        // Validate and process taxonomies
        $validated['taxonomies'] = $this->validate_taxonomies($data);

        // Validate custom pin image
        $validated['custom_pin_image'] = '';
        if (!empty($data['custom_pin_image'])) {
            $validated['custom_pin_image'] = $this->validate_image_url($data['custom_pin_image']);
        }

        // Validate images array
        $validated['images'] = $this->validate_images($data['images'] ?? []);

        return $validated;
    }

    /**
     * Validate taxonomy data with security checks
     */
    private function validate_taxonomies(array $data): string
    {
        $taxonomies = [];

        foreach (self::ALLOWED_TAXONOMY_TYPES as $taxonomy_type) {
            if (isset($data[$taxonomy_type]) && is_array($data[$taxonomy_type])) {
                $taxonomy_ids = array_filter(
                    array_map('intval', $data[$taxonomy_type]),
                    function($id) { return $id > 0; }
                );

                if (!empty($taxonomy_ids)) {
                    // Verify that taxonomy IDs exist in database
                    $verified_ids = $this->taxonomy_manager->verify_taxonomy_ids($taxonomy_type, $taxonomy_ids);
                    if (!empty($verified_ids)) {
                        $taxonomies[$taxonomy_type] = $verified_ids;
                    }
                }
            }
        }

        return wp_json_encode($taxonomies);
    }

    /**
     * Validate image URL with security checks
     */
    private function validate_image_url(string $url): string
    {
        $clean_url = esc_url_raw($url);

        if (!filter_var($clean_url, FILTER_VALIDATE_URL)) {
            return '';
        }

        // Check if it's an image URL
        $parsed_url = parse_url($clean_url);
        if (!$parsed_url || !isset($parsed_url['path'])) {
            return '';
        }

        $extension = strtolower(pathinfo($parsed_url['path'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_IMAGE_TYPES, true)) {
            return '';
        }

        return $clean_url;
    }

    /**
     * Validate images array with security checks
     */
    private function validate_images(array $images): string
    {
        $validated_images = [];

        foreach (array_slice($images, 0, self::MAX_IMAGES) as $image) {
            if (!empty($image)) {
                $validated_url = $this->validate_image_url($image);
                if ($validated_url) {
                    $validated_images[] = $validated_url;
                }
            }
        }

        return wp_json_encode($validated_images);
    }

    /**
     * Format facility output with proper escaping
     */
    private function format_facility_output(array $facility): array
    {
        return [
            'id' => (int) $facility['id'],
            'name' => esc_html($facility['name']),
            'address' => esc_html($facility['address']),
            'lat' => (float) $facility['lat'],
            'lng' => (float) $facility['lng'],
            'phone' => esc_html($facility['phone'] ?? ''),
            'website' => esc_url($facility['website'] ?? ''),
            'description' => wp_kses_post($facility['description'] ?? ''),
            'taxonomies' => json_decode($facility['taxonomies'] ?? '{}', true) ?: [],
            'custom_pin_image' => esc_url($facility['custom_pin_image'] ?? ''),
            'images' => json_decode($facility['images'] ?? '[]', true) ?: [],
            'created_at' => $facility['created_at'] ?? '',
            'updated_at' => $facility['updated_at'] ?? ''
        ];
    }

    /**
     * Validate get_facilities arguments
     */
    private function validate_get_facilities_args(array $args): array
    {
        $validated = [];

        // Sanitize search term
        if (!empty($args['search'])) {
            $validated['search'] = sanitize_text_field($args['search']);
        }

        // Validate taxonomy filters
        foreach (self::ALLOWED_TAXONOMY_TYPES as $taxonomy_type) {
            if (!empty($args[$taxonomy_type]) && is_array($args[$taxonomy_type])) {
                $validated[$taxonomy_type] = array_filter(
                    array_map('intval', $args[$taxonomy_type]),
                    function($id) { return $id > 0; }
                );
            }
        }

        // Validate geographic bounds
        if (!empty($args['bounds']) && is_array($args['bounds'])) {
            $validated['bounds'] = $args['bounds'];
        }

        // Validate ordering
        if (!empty($args['orderby'])) {
            $validated['orderby'] = sanitize_text_field($args['orderby']);
        }

        if (!empty($args['order'])) {
            $validated['order'] = strtoupper(sanitize_text_field($args['order']));
        }

        // Validate pagination
        if (!empty($args['limit'])) {
            $validated['limit'] = min(max(1, intval($args['limit'])), 1000);
        }

        if (!empty($args['offset'])) {
            $validated['offset'] = max(0, intval($args['offset']));
        }

        return $validated;
    }

    /**
     * Generate cache key for query
     */
    private function generate_cache_key(array $args): string
    {
        $key_parts = [self::CACHE_KEY_FILTERED_PREFIX, md5(serialize($args))];
        return implode('_', $key_parts);
    }

    /**
     * Clear facility-related caches
     */
    private function clear_facility_caches(?int $facility_id = null): void
    {
        $this->cache_manager->flush_group(self::CACHE_GROUP);

        if ($facility_id) {
            $cache_key = self::CACHE_KEY_FACILITY_PREFIX . $facility_id;
            $this->cache_manager->delete($cache_key, self::CACHE_GROUP);
        }
    }

    /**
     * Clean up facility images when facility is deleted
     */
    private function cleanup_facility_images(array $facility): void
    {
        // This would implement image cleanup logic
        // For now, we'll just log the cleanup requirement
        if (!empty($facility['custom_pin_image']) || !empty($facility['images'])) {
            error_log('Facility Locator: Image cleanup required for facility ID ' . $facility['id']);
        }
    }

    /**
     * Get facility count for dashboard
     */
    public function get_facility_count(): int
    {
        global $wpdb;

        try {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
            return (int) $count;
        } catch (Exception $e) {
            error_log('Facility Locator Error in get_facility_count: ' . $e->getMessage());
            return 0;
        }
    }
}