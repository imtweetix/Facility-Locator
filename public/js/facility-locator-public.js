/**
 * Public-facing JavaScript for the plugin - Updated with jQuery and ES6
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
  const init = () => {
    // Initialize each facility locator container
    $('.facility-locator-container').each(function () {
      const $container = $(this);
      const id = $container.attr('id');

      // Set up event listeners
      initEventListeners($container, id);

      // Build form steps
      buildFormSteps($container, id);
    });
  };

  /**
   * Initialize event listeners
   */
  const initEventListeners = ($container, id) => {
    const $ctaButton = $container.find('.facility-locator-cta-button');
    const $popup = $(`#${$ctaButton.data('target')}`);
    const $closeButton = $popup.find('.facility-locator-popup-close');
    const $form = $popup.find('form');
    const $nextBtn = $popup.find('.facility-locator-next-btn');
    const $prevBtn = $popup.find('.facility-locator-prev-btn');
    const $submitBtn = $popup.find('.facility-locator-submit-btn');
    const $skipLink = $popup.find('.facility-locator-skip-link');

    // Open popup when CTA button is clicked
    $ctaButton.on('click', (e) => {
      e.preventDefault();
      $popup.fadeIn(300);
      $('body').css('overflow', 'hidden');
    });

    // Close popup
    $closeButton.on('click', () => {
      $popup.fadeOut(300);
      $('body').css('overflow', 'auto');
    });

    // Close popup when clicking outside content
    $popup.on('click', (e) => {
      if ($(e.target).hasClass('facility-locator-popup')) {
        $popup.fadeOut(300);
        $('body').css('overflow', 'auto');
      }
    });

    // Next button
    $nextBtn.on('click', () => {
      navigateStep($container, 'next');
    });

    // Previous button
    $prevBtn.on('click', () => {
      navigateStep($container, 'prev');
    });

    // Form submission
    $form.on('submit', (e) => {
      e.preventDefault();
      submitForm($container, id);
    });

    // Skip link
    $skipLink.on('click', (e) => {
      e.preventDefault();
      showAllFacilities($container, id);
    });
  };

  /**
   * Build form steps based on settings
   */
  const buildFormSteps = ($container, id) => {
    const formSteps = facilityLocator.formSteps;
    const $stepsContainer = $container.find('.facility-locator-steps');

    if (!formSteps || formSteps.length === 0) {
      // If no steps are configured, show a message and skip directly to map
      console.log('No form steps configured, skipping form');

      // Hide the form popup elements
      const $popup = $container.find('.facility-locator-popup');

      // Update CTA button to skip form entirely
      const $ctaButton = $container.find('.facility-locator-cta-button');
      $ctaButton.off('click').on('click', (e) => {
        e.preventDefault();
        showAllFacilities($container, id);
      });

      return;
    }

    // Build each step
    formSteps.forEach((step, index) => {
      buildStep($stepsContainer, step, index);
    });

    // Show the first step
    $container.find('.facility-locator-step').first().addClass('active');

    // Update navigation buttons
    updateNavButtons($container);
  };

  /**
   * Build a single step
   */
  const buildStep = ($stepsContainer, step, index) => {
    const $step = $('<div>').addClass('facility-locator-step').attr('data-step', index);

    // Add step title
    $step.append($('<h2>').text(step.title));

    // Add columns
    if (step.columns && step.columns.length > 0) {
      step.columns.forEach((column) => {
        $step.append(buildColumn(column));
      });
    }

    // Add to container
    $stepsContainer.append($step);
  };

  /**
   * Build a form column
   */
  const buildColumn = (column) => {
    const $column = $('<div>').addClass('facility-locator-column');

    // Add column header
    if (column.header) {
      $column.append($('<h3>').text(column.header));
    }

    // Build column based on type
    const fieldId = `column_${Math.random().toString(36).substr(2, 9)}`;

    switch (column.type) {
      case 'radio':
        $column.append(buildRadioField(column, fieldId));
        break;
      case 'checkbox':
        $column.append(buildCheckboxField(column, fieldId));
        break;
      case 'dropdown':
        $column.append(buildDropdownField(column, fieldId));
        break;
    }

    return $column;
  };

  /**
   * Build a radio field
   */
  const buildRadioField = (column, fieldId) => {
    const $container = $('<div>').addClass('facility-locator-field-options');

    if (column.options) {
      // Handle both array format (new) and object format (backward compatibility)
      if (Array.isArray(column.options)) {
        // New array format - preserves exact order
        column.options.forEach((option) => {
          const optionId = `${fieldId}_${option.value}`;
          const $option = $('<div>').addClass('facility-locator-field-option');

          $option.append(
            $('<input>').attr({
              type: 'radio',
              id: optionId,
              name: fieldId,
              value: option.value,
            })
          );

          $option.append($('<label>').attr('for', optionId).text(option.label));

          $container.append($option);
        });
      } else {
        // Old object format - for backward compatibility
        Object.entries(column.options).forEach(([value, label]) => {
          const optionId = `${fieldId}_${value}`;
          const $option = $('<div>').addClass('facility-locator-field-option');

          $option.append(
            $('<input>').attr({
              type: 'radio',
              id: optionId,
              name: fieldId,
              value: value,
            })
          );

          $option.append($('<label>').attr('for', optionId).text(label));

          $container.append($option);
        });
      }
    }

    return $container;
  };

  /**
   * Build a checkbox field
   */
  const buildCheckboxField = (column, fieldId) => {
    const $container = $('<div>').addClass('facility-locator-field-options');

    if (column.options) {
      // Handle both array format (new) and object format (backward compatibility)
      if (Array.isArray(column.options)) {
        // New array format - preserves exact order
        column.options.forEach((option) => {
          const optionId = `${fieldId}_${option.value}`;
          const $option = $('<div>').addClass('facility-locator-field-option');

          $option.append(
            $('<input>').attr({
              type: 'checkbox',
              id: optionId,
              name: `${fieldId}[]`,
              value: option.value,
            })
          );

          $option.append($('<label>').attr('for', optionId).text(option.label));

          $container.append($option);
        });
      } else {
        // Old object format - for backward compatibility
        Object.entries(column.options).forEach(([value, label]) => {
          const optionId = `${fieldId}_${value}`;
          const $option = $('<div>').addClass('facility-locator-field-option');

          $option.append(
            $('<input>').attr({
              type: 'checkbox',
              id: optionId,
              name: `${fieldId}[]`,
              value: value,
            })
          );

          $option.append($('<label>').attr('for', optionId).text(label));

          $container.append($option);
        });
      }
    }

    return $container;
  };

  /**
   * Build a dropdown field
   */
  const buildDropdownField = (column, fieldId) => {
    const $select = $('<select>')
      .attr({
        id: fieldId,
        name: fieldId,
      })
      .addClass('facility-locator-select');

    // Add default option
    $select.append(
      $('<option>')
        .val('')
        .text(`Select ${column.header || 'Option'}`)
    );

    if (column.options) {
      // Handle both array format (new) and object format (backward compatibility)
      if (Array.isArray(column.options)) {
        // New array format - preserves exact order
        column.options.forEach((option) => {
          $select.append($('<option>').val(option.value).text(option.label));
        });
      } else {
        // Old object format - for backward compatibility
        Object.entries(column.options).forEach(([value, label]) => {
          $select.append($('<option>').val(value).text(label));
        });
      }
    }

    return $select;
  };

  /**
   * Navigate between steps
   */
  const navigateStep = ($container, direction) => {
    const $steps = $container.find('.facility-locator-step');
    const $currentStep = $steps.filter('.active');
    const currentIndex = parseInt($currentStep.data('step'));

    // Validate current step if moving forward
    if (direction === 'next' && !validateStep($currentStep)) {
      return;
    }

    // Determine new step index
    const newIndex = direction === 'next' ? currentIndex + 1 : currentIndex - 1;

    // Check if new index is valid
    if (newIndex >= 0 && newIndex < $steps.length) {
      // Hide current step
      $currentStep.removeClass('active');

      // Show new step
      $steps.eq(newIndex).addClass('active');

      // Update navigation buttons
      updateNavButtons($container);
    }
  };

  /**
   * Validate the current step
   */
  const validateStep = ($step) => {
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
  };

  /**
   * Update navigation buttons based on current step
   */
  const updateNavButtons = ($container) => {
    const $steps = $container.find('.facility-locator-step');
    const $currentStep = $steps.filter('.active');
    const currentIndex = parseInt($currentStep.data('step'));
    const isFirstStep = currentIndex === 0;
    const isLastStep = currentIndex === $steps.length - 1;

    const $prevBtn = $container.find('.facility-locator-prev-btn');
    const $nextBtn = $container.find('.facility-locator-next-btn');
    const $submitBtn = $container.find('.facility-locator-submit-btn');

    // Show/hide previous button
    $prevBtn.toggle(!isFirstStep);

    // Show/hide next and submit buttons
    if (isLastStep) {
      $nextBtn.hide();
      $submitBtn.show();
    } else {
      $nextBtn.show();
      $submitBtn.hide();
    }
  };

  /**
   * Submit the form and display facilities
   */
  const submitForm = ($container, id) => {
    const $form = $container.find('form');
    const formData = $form.serializeArray();
    const data = {};

    // Process form data
    formData.forEach((field) => {
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
  };

  /**
   * Show all facilities without filtering
   */
  const showAllFacilities = ($container, id) => {
    // Close popup
    $container.find('.facility-locator-popup').fadeOut(300);
    $('body').css('overflow', 'auto');

    // Show map container
    $container.find('.facility-locator-map-container').show();

    // Fetch all facilities
    fetchFacilities($container, id, {});
  };

  /**
   * Fetch facilities via AJAX
   */
  const fetchFacilities = ($container, id, formData) => {
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
      success: (response) => {
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
      error: (xhr, status, error) => {
        console.error('AJAX error:', error);

        // Remove loading state
        $container.find('.facility-locator-map-container').removeClass('loading');
      },
    });
  };

  /**
   * Render Google Map with facilities
   */
  const renderMap = ($container, id, facilities) => {
    const $mapContainer = $container.find(`#${id}-map`);
    const mapHeight = facilityLocator.settings.mapHeight || 500;
    const mapZoom = parseInt(facilityLocator.settings.mapZoom) || 10;

    // Set map height
    $mapContainer.css('height', `${mapHeight}px`);

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

    facilities.forEach((facility, index) => {
      const position = {
        lat: parseFloat(facility.lat),
        lng: parseFloat(facility.lng),
      };

      // Create marker
      const marker = new google.maps.Marker({
        position,
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
      marker.addListener('click', () => {
        // Close all open info windows
        infoWindows[id].forEach((window) => {
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
  };

  /**
   * Clear all markers from the map
   */
  const clearMarkers = (id) => {
    if (markers[id]) {
      markers[id].forEach((marker) => {
        marker.setMap(null);
      });
      markers[id] = [];
    }

    if (infoWindows[id]) {
      infoWindows[id].forEach((infoWindow) => {
        infoWindow.close();
      });
      infoWindows[id] = [];
    }
  };

  /**
   * Render filter bar
   */
  const renderFilters = ($container, id, filters, formData) => {
    const $filtersContainer = $container.find('.facility-locator-filters');
    $filtersContainer.empty();

    // Create taxonomy filters
    Object.entries(filters).forEach(([taxonomyType, taxonomyItems]) => {
      if (taxonomyItems && taxonomyItems.length > 0) {
        const $filter = $('<div>').addClass('facility-locator-filter');

        // Get taxonomy display name
        const taxonomyDisplayName = facilityLocator.availableTaxonomies[taxonomyType]?.label || taxonomyType;
        $filter.append($('<label>').text(taxonomyDisplayName));

        const $select = $('<select>')
          .addClass(`facility-filter-${taxonomyType}`)
          .attr('multiple', 'multiple')
          .attr('data-placeholder', `All ${taxonomyDisplayName}`);

        // Add options
        $select.append($('<option>').val('').text(`All ${taxonomyDisplayName}`));

        taxonomyItems.forEach((item) => {
          const $option = $('<option>').val(item.id).text(item.name);

          // Set selected based on form data
          if (formData[taxonomyType] && formData[taxonomyType].includes(item.id.toString())) {
            $option.prop('selected', true);
          }

          $select.append($option);
        });

        $filter.append($select);
        $filtersContainer.append($filter);
      }
    });

    // Add filter button
    const $filterButton = $('<button>')
      .addClass('facility-locator-filter-button')
      .text('Apply Filters')
      .on('click', () => {
        const filterData = {};

        // Get filter values for all taxonomies
        Object.keys(filters).forEach((taxonomyType) => {
          const values = $container.find(`.facility-filter-${taxonomyType}`).val();
          if (values && values.length > 0 && values[0] !== '') {
            filterData[taxonomyType] = values;
          }
        });

        // Keep other form data values
        Object.entries(formData).forEach(([key, value]) => {
          if (!Object.keys(filters).includes(key)) {
            filterData[key] = value;
          }
        });

        // Fetch facilities with new filters
        fetchFacilities($container, id, filterData);
      });

    $filtersContainer.append($filterButton);

    // Initialize select2 for multiple select if available
    if ($.fn.select2) {
      $container.find('[class*="facility-filter-"]').select2({
        width: '100%',
        minimumResultsForSearch: 5,
      });
    }
  };

  /**
   * Render facility list
   */
  const renderFacilityList = ($container, id, facilities) => {
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
    facilities.forEach((facility, index) => {
      const $item = $('<div>').addClass('facility-item').attr('data-id', facility.id);

      // Add facility details
      $item.append($('<h3>').text(facility.name));
      $item.append($('<p>').text(facility.address));

      if (facility.phone) {
        $item.append($('<p>').html(`<strong>Phone:</strong> ${facility.phone}`));
      }

      if (facility.email) {
        $item.append($('<p>').html(`<strong>Email:</strong> ${facility.email}`));
      }

      // Add taxonomy information
      Object.keys(facilityLocator.availableTaxonomies).forEach((taxonomyType) => {
        const taxonomyNames = facility[`${taxonomyType}_names`];
        if (taxonomyNames && taxonomyNames.length > 0) {
          const taxonomyDisplayName = facilityLocator.availableTaxonomies[taxonomyType].label;
          const $taxonomyDiv = $('<div>').addClass(`facility-item-${taxonomyType}`);
          $taxonomyDiv.append($('<p>').html(`<strong>${taxonomyDisplayName}:</strong>`));

          taxonomyNames.forEach((name) => {
            $taxonomyDiv.append($('<span>').text(name));
          });

          $item.append($taxonomyDiv);
        }
      });

      // Add actions
      const $actions = $('<div>').addClass('facility-item-actions');

      // View on map action
      $actions.append(
        $('<a>')
          .addClass('facility-item-action')
          .attr('href', '#')
          .text('View on Map')
          .on('click', (e) => {
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
        .on('mouseenter', () => {
          if (markers[id] && markers[id][index]) {
            markers[id][index].setAnimation(google.maps.Animation.BOUNCE);
          }
        })
        .on('mouseleave', () => {
          if (markers[id] && markers[id][index]) {
            markers[id][index].setAnimation(null);
          }
        });

      // Add item to list
      $list.append($item);
    });

    // Add list to container
    $listContainer.append($list);
  };

  // Initialize when document is ready
  $(document).ready(init);
})(jQuery);
