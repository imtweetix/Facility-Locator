<?php

/**
 * Public-facing template for the facility locator with Recovery.com + Google Maps style
 * Optimized version with external CSS
 *
 * @var string $id          Unique identifier for this instance
 * @var string $cta_text    Text for the CTA button
 * @var string $cta_color   Background color for the CTA button
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="<?php echo esc_attr($id); ?>" class="facility-locator-container">
    <!-- CTA Button (will be hidden once form is submitted) -->
    <div class="facility-locator-cta">
        <button
            class="facility-locator-cta-button"
            style="background-color: <?php echo esc_attr($cta_color); ?>;"
            data-target="<?php echo esc_attr($id); ?>-popup">
            <?php echo esc_html($cta_text); ?>
        </button>
    </div>

    <!-- Main Interface (hidden initially) -->
    <div class="facility-locator-main-interface" style="display: none;">
        <!-- Sticky Filter Bar -->
        <div class="facility-locator-filter-bar">
            <div class="filter-scroll-container">
                <div class="filter-items">
                    <!-- Filters will be dynamically added here -->
                </div>
                <div class="filter-actions">
                    <button class="clear-all-filters">Clear All</button>
                </div>
            </div>
        </div>

        <!-- Map and Sidebar Container -->
        <div class="facility-locator-map-sidebar-container">
            <!-- Sidebar with Facility Cards -->
            <div class="facility-locator-sidebar">
                <div class="sidebar-header">
                    <h3 class="results-count">Loading facilities...</h3>
                </div>
                <div class="facility-cards-container">
                    <!-- Facility cards will be dynamically added here -->
                </div>
            </div>

            <!-- Map Container -->
            <div class="facility-locator-map-wrapper">
                <div
                    id="<?php echo esc_attr($id); ?>-map"
                    class="facility-locator-map"
                    style="height: 100vh;"></div>
            </div>

            <!-- Detailed Facility Modal -->
            <div class="facility-detail-modal">
                <div class="facility-detail-content">
                    <button class="facility-detail-close">&times;</button>
                    <div class="facility-detail-body">
                        <!-- Detailed facility content will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Filter Drawer -->
    <div class="mobile-filter-drawer">
        <div class="mobile-filter-header">
            <h3>Filters</h3>
            <button class="mobile-filter-close">&times;</button>
        </div>
        <div class="mobile-filter-content">
            <!-- Mobile filters will be populated here -->
        </div>
        <div class="mobile-filter-footer">
            <button class="clear-all-filters">Clear All</button>
            <button class="apply-mobile-filters">Apply Filters</button>
        </div>
    </div>

    <!-- Multi-step Form Popup (for initial form) -->
    <div id="<?php echo esc_attr($id); ?>-popup" class="facility-locator-popup">
        <div class="facility-locator-popup-content">
            <button type="button" class="facility-locator-popup-close">&times;</button>

            <div class="facility-locator-form">
                <form id="<?php echo esc_attr($id); ?>-form">
                    <div class="facility-locator-steps">
                        <!-- Steps will be dynamically added here -->
                    </div>

                    <div class="facility-locator-form-navigation">
                        <button type="button" class="facility-locator-prev-btn" style="display: none;">Previous</button>
                        <button type="button" class="facility-locator-next-btn">Next</button>
                        <button type="submit" class="facility-locator-submit-btn" style="display: none;">Find Facilities</button>
                    </div>

                    <div class="facility-locator-form-skip">
                        <a href="#" class="facility-locator-skip-link">Skip to see all facilities</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>