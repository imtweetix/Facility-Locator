<?php

/**
 * Admin settings page
 */

$google_maps_api_key = get_option('facility_locator_google_maps_api_key', '');
$map_zoom = get_option('facility_locator_map_zoom', 10);
$map_height = get_option('facility_locator_map_height', 500);
$cta_text = get_option('facility_locator_cta_text', 'Find a Facility');
$cta_color = get_option('facility_locator_cta_color', '#007bff');
$default_pin_image = get_option('facility_locator_default_pin_image', '');
?>

<div class="wrap">
    <h1>Facility Locator Settings</h1>

    <form method="post" action="options.php">
        <?php settings_fields('facility_locator_settings'); ?>

        <div class="metabox-holder">
            <div class="postbox">
                <h2 class="hndle">General Settings</h2>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="facility_locator_google_maps_api_key">Google Maps API Key</label></th>
                            <td>
                                <input type="text" id="facility_locator_google_maps_api_key" name="facility_locator_google_maps_api_key" class="regular-text" value="<?php echo esc_attr($google_maps_api_key); ?>">
                                <p class="description">Get a Google Maps API key <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">here</a>.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="facility_locator_map_zoom">Default Map Zoom Level</label></th>
                            <td>
                                <input type="number" id="facility_locator_map_zoom" name="facility_locator_map_zoom" class="small-text" value="<?php echo esc_attr($map_zoom); ?>" min="1" max="20">
                                <p class="description">1 = Zoomed out, 20 = Zoomed in</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="facility_locator_map_height">Map Height (px)</label></th>
                            <td>
                                <input type="number" id="facility_locator_map_height" name="facility_locator_map_height" class="small-text" value="<?php echo esc_attr($map_height); ?>" min="200" max="1000">
                                <p class="description">Height of the map in pixels</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <h2 class="hndle">CTA Button</h2>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="facility_locator_cta_text">Button Text</label></th>
                            <td>
                                <input type="text" id="facility_locator_cta_text" name="facility_locator_cta_text" class="regular-text" value="<?php echo esc_attr($cta_text); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="facility_locator_cta_color">Button Color</label></th>
                            <td>
                                <input type="color" id="facility_locator_cta_color" name="facility_locator_cta_color" value="<?php echo esc_attr($cta_color); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="facility_locator_default_pin_image">Default Map Pin</label></th>
                            <td>
                                <input type="hidden" id="facility_locator_default_pin_image" name="facility_locator_default_pin_image" value="<?php echo esc_attr($default_pin_image); ?>">
                                <div id="default-pin-preview" style="margin-bottom: 10px;">
                                    <?php if (!empty($default_pin_image)) : ?>
                                        <img src="<?php echo esc_url($default_pin_image); ?>" style="max-width: 50px; max-height: 50px; display: block; margin-bottom: 5px;">
                                    <?php endif; ?>
                                </div>
                                <button type="button" id="upload-default-pin-button" class="button">Choose Default Pin Image</button>
                                <button type="button" id="remove-default-pin-button" class="button" <?php echo empty($default_pin_image) ? 'style="display:none;"' : ''; ?>>Remove Image</button>
                                <p class="description">Upload a PNG image to use as the default map pin. This will be used for facilities that don't have a custom pin image.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        var defaultPinUploader;

        $('#upload-default-pin-button').click(function(e) {
            e.preventDefault();

            // If the media frame already exists, reopen it.
            if (defaultPinUploader) {
                defaultPinUploader.open();
                return;
            }

            // Create a new media frame
            defaultPinUploader = wp.media({
                title: 'Choose Default Pin Image',
                button: {
                    text: 'Choose Pin'
                },
                multiple: false,
                library: {
                    type: 'image/png'
                }
            });

            // When an image is selected in the media frame...
            defaultPinUploader.on('select', function() {
                var attachment = defaultPinUploader.state().get('selection').first().toJSON();

                // Update hidden field
                $('#facility_locator_default_pin_image').val(attachment.url);

                // Update preview
                $('#default-pin-preview').html('<img src="' + attachment.url + '" style="max-width: 50px; max-height: 50px; display: block; margin-bottom: 5px;">');

                // Show remove button
                $('#remove-default-pin-button').show();
            });

            // Open the modal
            defaultPinUploader.open();
        });

        $('#remove-default-pin-button').click(function(e) {
            e.preventDefault();

            // Clear hidden field
            $('#facility_locator_default_pin_image').val('');

            // Clear preview
            $('#default-pin-preview').empty();

            // Hide remove button
            $(this).hide();
        });
    });
</script>