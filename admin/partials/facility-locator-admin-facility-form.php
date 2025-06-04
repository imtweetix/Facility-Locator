<?php

/**
 * Form for adding/editing a facility with all taxonomies
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

                    <!-- Taxonomies Section -->
                    <div class="postbox">
                        <h2 class="hndle">Categories & Features</h2>
                        <div class="inside">
                            <table class="form-table">
                                <?php foreach ($all_taxonomies as $type => $taxonomy) : ?>
                                    <?php $items = $taxonomy->get_all(); ?>
                                    <tr>
                                        <th scope="row"><label><?php echo esc_html($taxonomy->get_display_name()); ?></label></th>
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
                                    </tr>
                                <?php endforeach; ?>
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

<!-- Map JavaScript remains the same -->
<script>
    // Map initialization code (same as before)
    window.initFacilityMap = function() {
        if (window.facilityMapInitialized) {
            console.log('Map already initialized, skipping...');
            return;
        }

        console.log('initFacilityMap callback called by Google Maps API');

        var lat = <?php echo !empty($facility_data['lat']) ? $facility_data['lat'] : 40.7128; ?>;
        var lng = <?php echo !empty($facility_data['lng']) ? $facility_data['lng'] : -74.0060; ?>;

        var mapElement = document.getElementById('facility-map');
        if (!mapElement) {
            console.error('Map element not found');
            return;
        }

        var mapOptions = {
            center: {
                lat: lat,
                lng: lng
            },
            zoom: 15,
            mapTypeControl: true,
            streetViewControl: true,
            fullscreenControl: true
        };

        try {
            window.facilityMap = new google.maps.Map(mapElement, mapOptions);
            window.geocoder = new google.maps.Geocoder();

            window.facilityMarker = new google.maps.Marker({
                position: {
                    lat: lat,
                    lng: lng
                },
                map: window.facilityMap,
                draggable: true,
                title: 'Drag to adjust location'
            });

            google.maps.event.addListener(window.facilityMarker, 'dragend', function(event) {
                console.log('Marker dragged to:', event.latLng.lat(), event.latLng.lng());

                var latInput = document.getElementById('facility-lat');
                var lngInput = document.getElementById('facility-lng');

                if (latInput) latInput.value = event.latLng.lat();
                if (lngInput) lngInput.value = event.latLng.lng();

                var manualLatInput = document.getElementById('manual-lat');
                var manualLngInput = document.getElementById('manual-lng');
                if (manualLatInput) manualLatInput.value = event.latLng.lat();
                if (manualLngInput) manualLngInput.value = event.latLng.lng();

                window.geocoder.geocode({
                    'location': event.latLng
                }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        var addressInput = document.getElementById('facility-address');
                        if (addressInput) {
                            addressInput.value = results[0].formatted_address;
                            console.log('Address updated:', results[0].formatted_address);
                        }
                    }
                });
            });

            window.setupAddressAutocomplete();
            window.facilityMapInitialized = true;
            console.log('Map initialized successfully via callback');
            mapElement.classList.add('gm-style');

        } catch (error) {
            console.error('Error initializing map:', error);
            mapElement.innerHTML = '<div style="padding: 20px; text-align: center; color: #d63638;">Error loading map: ' + error.message + '</div>';
        }
    };

    window.setupAddressAutocomplete = function() {
        var addressInput = document.getElementById('facility-address');
        if (!addressInput) {
            console.error('Address input not found');
            return;
        }

        try {
            var autocomplete = new google.maps.places.Autocomplete(addressInput);

            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                console.log('Place selected:', place);

                if (place.geometry && window.facilityMap && window.facilityMarker) {
                    window.facilityMap.setCenter(place.geometry.location);
                    window.facilityMarker.setPosition(place.geometry.location);

                    var latInput = document.getElementById('facility-lat');
                    var lngInput = document.getElementById('facility-lng');
                    var manualLatInput = document.getElementById('manual-lat');
                    var manualLngInput = document.getElementById('manual-lng');

                    var lat = place.geometry.location.lat();
                    var lng = place.geometry.location.lng();

                    if (latInput) latInput.value = lat;
                    if (lngInput) lngInput.value = lng;
                    if (manualLatInput) manualLatInput.value = lat;
                    if (manualLngInput) manualLngInput.value = lng;

                    console.log('Map updated with new location');
                }
            });

            console.log('Address autocomplete setup successfully');
        } catch (error) {
            console.error('Error setting up address autocomplete:', error);
        }
    };

    window.facilityMap = null;
    window.facilityMarker = null;
    window.geocoder = null;
    window.facilityMapInitialized = false;

    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, setting up fallback initialization...');

        setTimeout(function() {
            if (!window.facilityMapInitialized && typeof google !== 'undefined' && google.maps) {
                console.log('Google Maps loaded but callback not called, initializing manually...');
                window.initFacilityMap();
            } else if (!window.facilityMapInitialized) {
                console.log('Google Maps not loaded, waiting...');

                var checkInterval = setInterval(function() {
                    if (typeof google !== 'undefined' && google.maps && !window.facilityMapInitialized) {
                        console.log('Google Maps finally loaded, initializing...');
                        clearInterval(checkInterval);
                        window.initFacilityMap();
                    }
                }, 500);

                setTimeout(function() {
                    if (!window.facilityMapInitialized) {
                        console.error('Google Maps failed to load within 15 seconds');
                        clearInterval(checkInterval);

                        var mapElement = document.getElementById('facility-map');
                        if (mapElement) {
                            mapElement.innerHTML = '<div style="padding: 20px; text-align: center; color: #d63638; border: 1px solid #ddd;">Google Maps failed to load. Please check your API key and internet connection.<br><small>You can still enter coordinates manually below.</small></div>';
                        }
                    }
                }, 15000);
            }
        }, 1000);
    });

    function updateCoordinatesFromManual() {
        var manualLat = document.getElementById('manual-lat');
        var manualLng = document.getElementById('manual-lng');
        var facilityLat = document.getElementById('facility-lat');
        var facilityLng = document.getElementById('facility-lng');

        if (manualLat && facilityLat) {
            facilityLat.value = manualLat.value;
        }

        if (manualLng && facilityLng) {
            facilityLng.value = manualLng.value;
        }

        if (window.facilityMap && window.facilityMarker && manualLat.value && manualLng.value) {
            var newPos = {
                lat: parseFloat(manualLat.value),
                lng: parseFloat(manualLng.value)
            };

            window.facilityMap.setCenter(newPos);
            window.facilityMarker.setPosition(newPos);

            console.log('Map updated from manual coordinates');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var facilityLat = document.getElementById('facility-lat');
        var facilityLng = document.getElementById('facility-lng');
        var manualLat = document.getElementById('manual-lat');
        var manualLng = document.getElementById('manual-lng');

        if (facilityLat && manualLat && facilityLat.value) {
            manualLat.value = facilityLat.value;
        }

        if (facilityLng && manualLng && facilityLng.value) {
            manualLng.value = facilityLng.value;
        }
    });
</script>