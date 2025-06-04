<?php
/**
 * Public-facing view for the shortcode
 */
?>

<div id="<?php echo esc_attr($id); ?>" class="facility-locator-container">
    <div class="facility-locator-cta">
        <button 
            class="facility-locator-cta-button" 
            style="background-color: <?php echo esc_attr($cta_color); ?>;"
            data-target="<?php echo esc_attr($id); ?>-popup"
        >
            <?php echo esc_html($cta_text); ?>
        </button>
    </div>
    
    <div class="facility-locator-map-container" style="display: none;">
        <div class="facility-locator-filters">
            <!-- Filters will be dynamically added here -->
        </div>
        
        <div 
            id="<?php echo esc_attr($id); ?>-map" 
            class="facility-locator-map"
            style="height: <?php echo esc_attr(get_option('facility_locator_map_height', 500)); ?>px;"
        ></div>
        
        <div class="facility-locator-list">
            <!-- Facility list will be dynamically added here -->
        </div>
    </div>
    
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
