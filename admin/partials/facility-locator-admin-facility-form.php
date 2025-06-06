<?php

/**
 * Form for adding/editing a facility with image gallery support
 * Updated to include multiple image uploads
 */

$is_edit = $facility !== null;
$page_title = $is_edit ? 'Edit Facility' : 'Add New Facility';

// Get Google Maps API key
$api_key = get_option('facility_locator_google_maps_api_key', '');
$has_api_key = !empty($api_key);

// Get all taxonomies
$taxonomy_manager = new Facility_Locator_Taxonomy_Manager();
$all_taxonomies = $taxonomy_manager->get_all_taxonomies();

// Default values
$facility_data = array(
    'id' => $is_edit ? $facility->id : 0,
    'name' => $is_edit ? $facility->name : '',
    'address' => $is_edit ? $facility->address : '',
    'lat' => $is_edit ? $facility->lat : '',
    'lng' => $is_edit ? $facility->lng : '',
    'phone' => $is_edit ? $facility->phone : '',
    'website' => $is_edit ? $facility->website : '',
    'custom_pin_image' => $is_edit ? $facility->custom_pin_image : '',
    'description' => $is_edit ? $facility->description : '',
    'images' => $is_edit ? (isset($facility->images) ? $facility->images : array()) : array(),
);

// Add taxonomy data
foreach ($all_taxonomies as $type => $taxonomy) {
    $facility_data[$type] = $is_edit ? $facility->{$type} : array();
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=facility-locator'); ?>" class="page-title-action">Back to Facilities</a>
    <hr class="wp-header-end">

    <?php if (!$has_api_key) : ?>
        <div class="notice notice-warning">
            <p>Google Maps API key is missing. Please add it in the <a href="<?php echo admin_url('admin.php?page=facility-locator-settings'); ?>">Settings</a> page to enable map functionality.</p>
        </div>
    <?php endif; ?>

    <form id="facility-form" method="post">
        <input type="hidden" name="id" value="<?php echo esc_attr($facility_data['id']); ?>">
        <input type="hidden" name="lat" id="facility-lat" value="<?php echo esc_attr($facility_data['lat']); ?>">
        <input type="hidden" name="lng" id="facility-lng" value="<?php echo esc_attr($facility_data['lng']); ?>">

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <!-- Basic Information -->
                    <div class="postbox">
                        <h2 class="hndle">Basic Information</h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="facility-name">Name</label></th>
                                    <td>
                                        <input type="text" id="facility-name" name="name" class="regular-text" value="<?php echo esc_attr($facility_data['name']); ?>" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="facility-address">Address</label></th>
                                    <td>
                                        <input type="text" id="facility-address" name="address" class="regular-text" value="<?php echo esc_attr($facility_data['address']); ?>" required>
                                        <p class="description">Start typing to search for an address</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Map Location</th>
                                    <td>
                                        <?php if ($has_api_key) : ?>
                                            <div id="facility-map" style="height: 300px; margin-bottom: 10px;"></div>
                                            <p class="description">You can drag the marker to adjust the exact location</p>
                                        <?php else : ?>
                                            <div style="height: 300px; border: 1px solid #ddd; border-radius: 4px; background-color: #f9f9f9; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                                                <div style="text-align: center; color: #666;">
                                                    <p><strong>Google Maps API Key Required</strong></p>
                                                    <p>Please add your Google Maps API key in the <a href="<?php echo admin_url('admin.php?page=facility-locator-settings'); ?>">Settings</a> page to enable map functionality.</p>
                                                </div>
                                            </div>
                                            <p class="description">Manual coordinates entry: Enter latitude and longitude values manually</p>
                                            <p>
                                                <label for="manual-lat">Latitude:</label>
                                                <input type="number" id="manual-lat" step="any" placeholder="40.7128" style="margin-right: 10px; width: 120px;" onchange="updateCoordinatesFromManual();" onkeyup="updateCoordinatesFromManual();">
                                                <label for="manual-lng">Longitude:</label>
                                                <input type="number" id="manual-lng" step="any" placeholder="-74.0060" style="width: 120px;" onchange="updateCoordinatesFromManual();" onkeyup="updateCoordinatesFromManual();">
                                                <br><small style="color: #666;">You can find coordinates using <a href="https://www.google.com/maps" target="_blank">Google Maps</a> - right-click on a location and copy the coordinates.</small>
                                            </p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="facility-phone">Phone</label></th>
                                    <td>
                                        <input type="tel" id="facility-phone" name="phone" class="regular-text" value="<?php echo esc_attr($facility_data['phone']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="facility-website">Website</label></th>
                                    <td>
                                        <input type="url" id="facility-website" name="website" class="regular-text" value="<?php echo esc_attr($facility_data['website']); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="facility-description">Description</label></th>
                                    <td>
                                        <?php
                                        wp_editor(
                                            $facility_data['description'],
                                            'facility-description',
                                            array(
                                                'textarea_name' => 'description',
                                                'textarea_rows' => 5,
                                                'media_buttons' => false,
                                            )
                                        );
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Image Gallery -->
                    <div class="postbox">
                        <h2 class="hndle">Image Gallery</h2>
                        <div class="inside">
                            <p class="description">Add up to 5 images for this facility. Images will be displayed in a carousel on the frontend.</p>

                            <div id="facility-images-container">
                                <?php if (!empty($facility_data['images'])) : ?>
                                    <?php foreach ($facility_data['images'] as $index => $image_url) : ?>
                                        <div class="facility-image-item" data-index="<?php echo $index; ?>">
                                            <img src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; max-height: 100px; object-fit: cover;">
                                            <input type="hidden" name="images[]" value="<?php echo esc_attr($image_url); ?>">
                                            <button type="button" class="button remove-image">Remove</button>
                                            <span class="drag-handle">⋮⋮</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div id="facility-images-actions">
                                <button type="button" id="add-facility-image" class="button">Add Image</button>
                                <span id="image-count">(<?php echo count($facility_data['images']); ?>/5)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Custom Pin Image -->
                    <div class="postbox">
                        <h2 class="hndle">Custom Map Pin</h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="facility-custom-pin">Custom Map Pin</label></th>
                                    <td>
                                        <input type="hidden" id="facility-custom-pin" name="custom_pin_image" value="<?php echo esc_attr($facility_data['custom_pin_image']); ?>">
                                        <div id="custom-pin-preview" style="margin-bottom: 10px;">
                                            <?php if (!empty($facility_data['custom_pin_image'])) : ?>
                                                <img src="<?php echo esc_url($facility_data['custom_pin_image']); ?>" style="max-width: 50px; max-height: 50px; display: block; margin-bottom: 5px;">
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" id="upload-pin-button" class="button">Choose Pin Image</button>
                                        <button type="button" id="remove-pin-button" class="button" <?php echo empty($facility_data['custom_pin_image']) ? 'style="display:none;"' : ''; ?>>Remove Image</button>
                                        <p class="description">Upload a PNG image to use as the map pin for this facility. If no image is uploaded, the default pin will be used.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Taxonomies Section -->
                    <div class="postbox">
                        <h2 class="hndle">Categories & Features</h2>
                        <div class="inside">
                            <table class="form-table facility-taxonomy-table">
                                <tr>
                                    <?php foreach ($all_taxonomies as $type => $taxonomy) : ?>
                                        <th scope="row"><label><?php echo esc_html($taxonomy->get_display_name()); ?></label></th>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <?php foreach ($all_taxonomies as $type => $taxonomy) : ?>
                                        <?php $items = $taxonomy->get_all(); ?>
                                        <td>
                                            <?php if (!empty($items)) : ?>
                                                <div class="facility-taxonomy-container facility-<?php echo esc_attr($type); ?>-container">
                                                    <?php foreach ($items as $item) : ?>
                                                        <label>
                                                            <input type="checkbox"
                                                                name="<?php echo esc_attr($type); ?>[]"
                                                                value="<?php echo esc_attr($item->id); ?>"
                                                                <?php checked(in_array($item->id, $facility_data[$type])); ?>>
                                                            <?php echo esc_html($item->name); ?>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else : ?>
                                                <p>No <?php echo esc_html(strtolower($taxonomy->get_display_name())); ?> available. <a href="<?php echo admin_url('admin.php?page=facility-locator-' . str_replace('_', '-', $type) . '&action=add'); ?>" target="_blank">Add <?php echo esc_html(strtolower($taxonomy->get_display_name())); ?></a> first.</p>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle">Save</h2>
                        <div class="inside">
                            <div id="major-publishing-actions">
                                <div id="publishing-action">
                                    <span class="spinner"></span>
                                    <input type="submit" id="publish" class="button button-primary button-large" value="<?php echo $is_edit ? 'Update' : 'Publish'; ?>">
                                </div>
                                <div class="clear"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // Localize data for the external script
    var facilityFormData = {
        imageCount: <?php echo count($facility_data['images']); ?>,
        maxImages: 5
    };
</script>