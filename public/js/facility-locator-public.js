/**
 * Public-facing JavaScript for the plugin
 */
(function ($) {
  'use strict';

  // Store map instances
  const maps = {};
  const markers = {};
  const infoWindows = {};

  /**
   * Initialize the plugin
   */
  function init() {
    // Initialize each facility locator container
    $('.facility-locator-container').each(function () {
      const $container = $(this);
      const id = $container.attr('id');

      // Set up event listeners
      initEventListeners($container, id);

      // Build form steps
      buildFormSteps($container, id);
    });
  }

  /**
   * Initialize event listeners
   */
  function initEventListeners($container, id) {
    const $ctaButton = $container.find('.facility-locator-cta-button');
    const $popup = $('#' + $ctaButton.data('target'));
    const $closeButton = $popup.find('.facility-locator-popup-close');
    const $form = $popup.find('form');
    const $nextBtn = $popup.find('.facility-locator-next-btn');
    const $prevBtn = $popup.find('.facility-locator-prev-btn');
    const $submitBtn = $popup.find('.facility-locator-submit-btn');
    const $skipLink = $popup.find('.facility-locator-skip-link');

    // Open popup when CTA button is clicked
    $ctaButton.on('click', function (e) {
      e.preventDefault();
      $popup.fadeIn(300);
      $('body').css('overflow', 'hidden');
    });

    // Close popup
    $closeButton.on('click', function () {
      $popup.fadeOut(300);
      $('body').css('overflow', 'auto');
    });

    // Close popup when clicking outside content
    $popup.on('click', function (e) {
      if ($(e.target).hasClass('facility-locator-popup')) {
        $popup.fadeOut(300);
        $('body').css('overflow', 'auto');
      }
    });

    // Next button
    $nextBtn.on('click', function () {
      navigateStep($container, 'next');
    });

    // Previous button
    $prevBtn.on('click', function () {
      navigateStep($container, 'prev');
    });

    // Form submission
    $form.on('submit', function (e) {
      e.preventDefault();
      submitForm($container, id);
    });

    // Skip link
    $skipLink.on('click', function (e) {
      e.preventDefault();
      showAllFacilities($container, id);
    });
  }

  /**
   * Build form steps based on settings
   */
  function buildFormSteps($container, id) {
    const formSteps = facilityLocator.formSteps;
    const $stepsContainer = $container.find('.facility-locator-steps');

    if (!formSteps || formSteps.length === 0) {
      // If no steps are configured, show a message and skip directly to map
      console.log('No form steps configured, skipping form');

      // Hide the form popup elements
      const $popup = $container.find('.facility-locator-popup');
      const $skipLink = $popup.find('.facility-locator-skip-link');

      // Update CTA button to skip form entirely
      const $ctaButton = $container.find('.facility-locator-cta-button');
      $ctaButton.off('click').on('click', function (e) {
        e.preventDefault();
        showAllFacilities($container, id);
      });

      return;
    }

    // Build each step
    $.each(formSteps, function (index, step) {
      buildStep($stepsContainer, step, index);
    });

    // Show the first step
    $container.find('.facility-locator-step').first().addClass('active');

    // Update navigation buttons
    updateNavButtons($container);
  }

  /**
   * Build a single step
   */
  function buildStep($stepsContainer, step, index) {
    const $step = $('<div>').addClass('facility-locator-step').attr('data-step', index);

    // Add step title
    $step.append($('<h2>').text(step.title));

    // Add fields
    if (step.fields && step.fields.length > 0) {
      $.each(step.fields, function (fieldIndex, field) {
        $step.append(buildField(field));
      });
    }

    // Add to container
    $stepsContainer.append($step);
  }

  /**
   * Build a form field
   */
  function buildField(field) {
    const $field = $('<div>').addClass('facility-locator-field');

    // Add label
    $field.append(
      $('<label>')
        .attr('for', field.id)
        .text(field.label + (field.required ? ' *' : ''))
    );

    // Build field based on type
    switch (field.type) {
      case 'text':
        $field.append(
          $('<input>').attr({
            type: 'text',
            id: field.id,
            name: field.id,
            required: field.required || false,
          })
        );
        break;

      case 'select':
        const $select = $('<select>').attr({
          id: field.id,
          name: field.id,
          required: field.required || false,
        });

        // Add options
        if (field.options) {
          $.each(field.options, function (value, label) {
            $select.append(
              $('<option>')
                .attr('value', value)
                .text(label)
                .prop('selected', value === field.default)
            );
          });
        }

        $field.append($select);
        break;

      case 'radio':
        const $radioContainer = $('<div>').addClass('facility-locator-field-options');

        if (field.options) {
          $.each(field.options, function (value, label) {
            const $option = $('<div>').addClass('facility-locator-field-option');

            $option.append(
              $('<input>')
                .attr({
                  type: 'radio',
                  id: field.id + '_' + value,
                  name: field.id,
                  value: value,
                  required: field.required || false,
                })
                .prop('checked', value === field.default)
            );

            $option.append(
              $('<label>')
                .attr('for', field.id + '_' + value)
                .text(label)
            );

            $radioContainer.append($option);
          });
        }

        $field.append($radioContainer);
        break;

      case 'checkbox':
        const $checkboxContainer = $('<div>').addClass('facility-locator-field-options');

        if (field.options) {
          $.each(field.options, function (value, label) {
            const $option = $('<div>').addClass('facility-locator-field-option');

            $option.append(
              $('<input>').attr({
                type: 'checkbox',
                id: field.id + '_' + value,
                name: field.id + '[]',
                value: value,
              })
            );

            $option.append(
              $('<label>')
                .attr('for', field.id + '_' + value)
                .text(label)
            );

            $checkboxContainer.append($option);
          });
        }

        $field.append($checkboxContainer);
        break;
    }

    return $field;
  }

  /**
   * Navigate between steps
   */
  function navigateStep($container, direction) {
    const $steps = $container.find('.facility-locator-step');
    const $currentStep = $steps.filter('.active');
    const currentIndex = parseInt($currentStep.data('step'));

    // Validate current step if moving forward
    if (direction === 'next' && !validateStep($currentStep)) {
      return;
    }

    // Determine new step index
    let newIndex;
    if (direction === 'next') {
      newIndex = currentIndex + 1;
    } else {
      newIndex = currentIndex - 1;
    }

    // Check if new index is valid
    if (newIndex >= 0 && newIndex < $steps.length) {
      // Hide current step
      $currentStep.removeClass('active');

      // Show new step
      $steps.eq(newIndex).addClass('active');

      // Update navigation buttons
      updateNavButtons($container);
    }
  }

  /**
   * Validate the current step
   */
  function validateStep($step) {
    const $requiredFields = $step.find('[required]');
    let valid = true;

    $requiredFields.each(function () {
      const $field = $(this);

      if ($field.is(':checkbox, :radio')) {
        // For checkboxes and radio buttons, check if any in the group is checked
        const name = $field.attr('name').replace('[]', '');
        if (!$step.find(`[name^="${name}"]:checked`).length) {
          valid = false;

          // Highlight the field container
          $field.closest('.facility-locator-field').addClass('error');
        }
      } else {
        // For other inputs, check if they have a value
        if (!$field.val()) {
          valid = false;

          // Highlight the field
          $field.addClass('error');
        }
      }
    });

    return valid;
  }

  /**
   * Update navigation buttons based on current step
   */
  function updateNavButtons($container) {
    const $steps = $container.find('.facility-locator-step');
    const $currentStep = $steps.filter('.active');
    const currentIndex = parseInt($currentStep.data('step'));
    const isFirstStep = currentIndex === 0;
    const isLastStep = currentIndex === $steps.length - 1;

    const $prevBtn = $container.find('.facility-locator-prev-btn');
    const $nextBtn = $container.find('.facility-locator-next-btn');
    const $submitBtn = $container.find('.facility-locator-submit-btn');

    // Show/hide previous button
    if (isFirstStep) {
      $prevBtn.hide();
    } else {
      $prevBtn.show();
    }

    // Show/hide next and submit buttons
    if (isLastStep) {
      $nextBtn.hide();
      $submitBtn.show();
    } else {
      $nextBtn.show();
      $submitBtn.hide();
    }
  }

  /**
   * Submit the form and display facilities
   */
  function submitForm($container, id) {
    const $form = $container.find('form');
    const formData = $form.serializeArray();
    const data = {};

    // Process form data
    $.each(formData, function (i, field) {
      if (field.name.endsWith('[]')) {
        // Handle array fields (like checkboxes)
        const fieldName = field.name.slice(0, -2);
        if (!data[fieldName]) {
          data[fieldName] = [];
        }
        data[fieldName].push(field.value);
      } else {
        data[field.name] = field.value;
      }
    });

    // Close popup
    $container.find('.facility-locator-popup').fadeOut(300);
    $('body').css('overflow', 'auto');

    // Show map container
    $container.find('.facility-locator-map-container').show();

    // Fetch facilities
    fetchFacilities($container, id, data);
  }

  /**
   * Show all facilities without filtering
   */
  function showAllFacilities($container, id) {
    // Close popup
    $container.find('.facility-locator-popup').fadeOut(300);
    $('body').css('overflow', 'auto');

    // Show map container
    $container.find('.facility-locator-map-container').show();

    // Fetch all facilities
    fetchFacilities($container, id, {});
  }

  /**
   * Fetch facilities via AJAX
   */
  function fetchFacilities($container, id, formData) {
    // Show loading state
    $container.find('.facility-locator-map-container').addClass('loading');

    // Make AJAX request
    $.ajax({
      url: facilityLocator.ajaxUrl,
      type: 'POST',
      data: {
        action: 'get_facilities',
        nonce: facilityLocator.nonce,
        form_data: formData,
      },
      success: function (response) {
        if (response.success) {
          // Render map with facilities
          renderMap($container, id, response.data.facilities);

          // Render filter bar
          renderFilters($container, id, response.data.filters, formData);

          // Render facility list
          renderFacilityList($container, id, response.data.facilities);
        } else {
          // Show error message
          console.error('Error fetching facilities:', response.data);
        }

        // Remove loading state
        $container.find('.facility-locator-map-container').removeClass('loading');
      },
      error: function (xhr, status, error) {
        console.error('AJAX error:', error);

        // Remove loading state
        $container.find('.facility-locator-map-container').removeClass('loading');
      },
    });
  }

  /**
   * Render Google Map with facilities
   */
  function renderMap($container, id, facilities) {
    const $mapContainer = $container.find('#' + id + '-map');
    const mapHeight = facilityLocator.settings.mapHeight || 500;
    const mapZoom = parseInt(facilityLocator.settings.mapZoom) || 10;

    // Set map height
    $mapContainer.css('height', mapHeight + 'px');

    // Initialize map if not already created
    if (!maps[id]) {
      // Create map
      maps[id] = new google.maps.Map($mapContainer[0], {
        zoom: mapZoom,
        center: { lat: 40.7128, lng: -74.006 }, // Default to New York
        mapTypeControl: true,
        scrollwheel: false,
        streetViewControl: false,
        fullscreenControl: true,
      });

      // Initialize markers array
      markers[id] = [];
      infoWindows[id] = [];
    }

    // Clear existing markers
    clearMarkers(id);

    // Handle case with no facilities
    if (!facilities || facilities.length === 0) {
      $container.find('.facility-locator-list').html('<p>No facilities found. Please adjust your search criteria.</p>');
      return;
    }

    // Add markers for each facility
    const bounds = new google.maps.LatLngBounds();

    $.each(facilities, function (index, facility) {
      const position = {
        lat: parseFloat(facility.lat),
        lng: parseFloat(facility.lng),
      };

      // Create marker
      const marker = new google.maps.Marker({
        position: position,
        map: maps[id],
        title: facility.name,
        animation: google.maps.Animation.DROP,
      });

      // Create info window content
      const infoWindowContent = `
                <div class="facility-info-window">
                    <h3>${facility.name}</h3>
                    <p>${facility.address}</p>
                    ${facility.phone ? `<p>Phone: ${facility.phone}</p>` : ''}
                    ${facility.email ? `<p>Email: ${facility.email}</p>` : ''}
                    ${facility.website ? `<p><a href="${facility.website}" target="_blank">Website</a></p>` : ''}
                </div>
            `;

      // Create info window
      const infoWindow = new google.maps.InfoWindow({
        content: infoWindowContent,
      });

      // Add click listener to marker
      marker.addListener('click', function () {
        // Close all open info windows
        $.each(infoWindows[id], function (i, window) {
          window.close();
        });

        // Open this info window
        infoWindow.open(maps[id], marker);

        // Scroll to the corresponding facility in the list
        const $facilityList = $container.find('.facility-locator-list');
        const $facilityItem = $facilityList.find(`[data-id="${facility.id}"]`);

        if ($facilityItem.length) {
          $facilityList.animate(
            {
              scrollTop: $facilityItem.position().top + $facilityList.scrollTop(),
            },
            500
          );

          // Highlight the facility
          $facilityList.find('.facility-item').removeClass('highlighted');
          $facilityItem.addClass('highlighted');
        }
      });

      // Add marker and info window to arrays
      markers[id].push(marker);
      infoWindows[id].push(infoWindow);

      // Extend bounds
      bounds.extend(position);
    });

    // Fit map to bounds
    maps[id].fitBounds(bounds);

    // If only one marker, zoom in more
    if (facilities.length === 1) {
      maps[id].setZoom(15);
    }
  }

  /**
   * Clear all markers from the map
   */
  function clearMarkers(id) {
    if (markers[id]) {
      $.each(markers[id], function (i, marker) {
        marker.setMap(null);
      });
      markers[id] = [];
    }

    if (infoWindows[id]) {
      $.each(infoWindows[id], function (i, infoWindow) {
        infoWindow.close();
      });
      infoWindows[id] = [];
    }
  }

  /**
   * Render filter bar
   */
  function renderFilters($container, id, filters, formData) {
    const $filtersContainer = $container.find('.facility-locator-filters');
    $filtersContainer.empty();

    // Create category filter
    if (filters.categories && filters.categories.length > 0) {
      const $filter = $('<div>').addClass('facility-locator-filter');

      $filter.append($('<label>').text('Categories'));

      const $select = $('<select>')
        .addClass('facility-filter-categories')
        .attr('multiple', 'multiple')
        .attr('data-placeholder', 'All Categories');

      // Add options
      $select.append($('<option>').val('').text('All Categories'));

      $.each(filters.categories, function (i, category) {
        const $option = $('<option>').val(category).text(category);

        // Set selected based on form data
        if (formData.categories && formData.categories.includes(category)) {
          $option.prop('selected', true);
        }

        $select.append($option);
      });

      $filter.append($select);
      $filtersContainer.append($filter);
    }

    // Create attributes filter
    if (filters.attributes && filters.attributes.length > 0) {
      const $filter = $('<div>').addClass('facility-locator-filter');

      $filter.append($('<label>').text('Features'));

      const $select = $('<select>')
        .addClass('facility-filter-attributes')
        .attr('multiple', 'multiple')
        .attr('data-placeholder', 'All Features');

      // Add options
      $select.append($('<option>').val('').text('All Features'));

      $.each(filters.attributes, function (i, attribute) {
        const $option = $('<option>').val(attribute).text(attribute);

        // Set selected based on form data
        if (formData.attributes && formData.attributes.includes(attribute)) {
          $option.prop('selected', true);
        }

        $select.append($option);
      });

      $filter.append($select);
      $filtersContainer.append($filter);
    }

    // Add filter button
    const $filterButton = $('<button>')
      .addClass('facility-locator-filter-button')
      .text('Apply Filters')
      .on('click', function () {
        const filterData = {};

        // Get category filter values
        const categories = $container.find('.facility-filter-categories').val();
        if (categories && categories.length > 0 && categories[0] !== '') {
          filterData.categories = categories;
        }

        // Get attribute filter values
        const attributes = $container.find('.facility-filter-attributes').val();
        if (attributes && attributes.length > 0 && attributes[0] !== '') {
          filterData.attributes = attributes;
        }

        // Keep other form data values
        $.each(formData, function (key, value) {
          if (key !== 'categories' && key !== 'attributes') {
            filterData[key] = value;
          }
        });

        // Fetch facilities with new filters
        fetchFacilities($container, id, filterData);
      });

    $filtersContainer.append($filterButton);

    // Initialize select2 for multiple select if available
    if ($.fn.select2) {
      $container.find('.facility-filter-categories, .facility-filter-attributes').select2({
        width: '100%',
        minimumResultsForSearch: 5,
      });
    }
  }

  /**
   * Render facility list
   */
  function renderFacilityList($container, id, facilities) {
    const $listContainer = $container.find('.facility-locator-list');
    $listContainer.empty();

    // Handle case with no facilities
    if (!facilities || facilities.length === 0) {
      $listContainer.html('<p>No facilities found. Please adjust your search criteria.</p>');
      return;
    }

    // Create list
    const $list = $('<div>').addClass('facility-locator-items');

    // Add facilities to list
    $.each(facilities, function (index, facility) {
      const $item = $('<div>').addClass('facility-item').attr('data-id', facility.id);

      // Add facility details
      $item.append($('<h3>').text(facility.name));
      $item.append($('<p>').text(facility.address));

      if (facility.phone) {
        $item.append($('<p>').html('<strong>Phone:</strong> ' + facility.phone));
      }

      if (facility.email) {
        $item.append($('<p>').html('<strong>Email:</strong> ' + facility.email));
      }

      // Add categories
      if (facility.categories && facility.categories.length > 0) {
        const $categories = $('<div>').addClass('facility-item-categories');
        $categories.append($('<p>').html('<strong>Categories:</strong>'));

        $.each(facility.categories, function (i, category) {
          $categories.append($('<span>').text(category));
        });

        $item.append($categories);
      }

      // Add attributes
      if (facility.attributes && facility.attributes.length > 0) {
        const $attributes = $('<div>').addClass('facility-item-attributes');
        $attributes.append($('<p>').html('<strong>Features:</strong>'));

        $.each(facility.attributes, function (i, attribute) {
          $attributes.append($('<span>').text(attribute));
        });

        $item.append($attributes);
      }

      // Add actions
      const $actions = $('<div>').addClass('facility-item-actions');

      // View on map action
      $actions.append(
        $('<a>')
          .addClass('facility-item-action')
          .attr('href', '#')
          .text('View on Map')
          .on('click', function (e) {
            e.preventDefault();

            // Trigger click on the corresponding marker
            if (markers[id] && markers[id][index]) {
              google.maps.event.trigger(markers[id][index], 'click');

              // Center map on marker
              maps[id].panTo(markers[id][index].getPosition());
              maps[id].setZoom(15);
            }
          })
      );

      // Get directions action
      $actions.append(
        $('<a>')
          .addClass('facility-item-action')
          .attr('href', `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(facility.address)}`)
          .attr('target', '_blank')
          .text('Get Directions')
      );

      // Add actions to item
      $item.append($actions);

      // Add hover effect
      $item
        .on('mouseenter', function () {
          if (markers[id] && markers[id][index]) {
            markers[id][index].setAnimation(google.maps.Animation.BOUNCE);
          }
        })
        .on('mouseleave', function () {
          if (markers[id] && markers[id][index]) {
            markers[id][index].setAnimation(null);
          }
        });

      // Add item to list
      $list.append($item);
    });

    // Add list to container
    $listContainer.append($list);
  }

  // Initialize when document is ready
  $(document).ready(init);
})(jQuery);
