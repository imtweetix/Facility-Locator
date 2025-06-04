/**
 * Admin-specific JavaScript for the plugin - Updated with jQuery and ES6
 */
(function ($) {
  'use strict';

  // Initialize the admin functionality
  const init = () => {
    // Facility form handling
    if ($('#facility-form').length) {
      initFacilityForm();
    }

    // Facility list handling
    if ($('.wp-list-table').length) {
      initFacilityList();
    }
  };

  /**
   * Initialize facility form
   */
  const initFacilityForm = () => {
    const $form = $('#facility-form');

    // Form submission
    $form.on('submit', (e) => {
      e.preventDefault();

      console.log('Facility Locator: Form submission started');

      // Validate form
      if (!validateForm($form)) {
        console.log('Facility Locator: Form validation failed');
        return;
      }

      // Disable submit button and show spinner
      const $submitBtn = $('#publish');
      $submitBtn.prop('disabled', true);
      $submitBtn.prev('.spinner').addClass('is-active');

      // Collect form data
      const formData = new FormData(e.target);
      formData.append('action', 'save_facility');
      formData.append('nonce', facilityLocator.nonce);

      // Debug: Log form data
      console.log('Facility Locator: Form data being sent:');
      for (const [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
      }

      // Send AJAX request
      $.ajax({
        url: facilityLocator.ajaxUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: (response) => {
          console.log('Facility Locator: AJAX response:', response);

          if (response.success) {
            // Redirect to facilities list
            window.location.href = 'admin.php?page=facility-locator&saved=true';
          } else {
            // Show error message
            alert(`Error: ${response.data || 'Unknown error occurred'}`);

            // Enable submit button and hide spinner
            $submitBtn.prop('disabled', false);
            $submitBtn.prev('.spinner').removeClass('is-active');
          }
        },
        error: (xhr, status, error) => {
          console.error('Facility Locator: AJAX error:', {
            xhr,
            status,
            error,
            responseText: xhr.responseText,
          });

          // Show error message
          alert(`AJAX error: ${error}\nResponse: ${xhr.responseText}`);

          // Enable submit button and hide spinner
          $submitBtn.prop('disabled', false);
          $submitBtn.prev('.spinner').removeClass('is-active');
        },
      });
    });

    // Add new level of care
    $('#add-level-btn').on('click', () => {
      console.log('Add level button clicked');
      const $newLevelInput = $('#new-level');
      const newLevel = $newLevelInput.val().trim();

      console.log('New level value:', newLevel);

      if (newLevel) {
        // Check if level already exists
        let exists = false;
        $('.facility-levels-container input[type="checkbox"]').each(function () {
          if ($(this).val() === newLevel) {
            exists = true;
            return false;
          }
        });

        console.log('Level exists:', exists);

        if (!exists) {
          // Create new level checkbox
          const $levelLabel = $('<label>');

          $levelLabel.append(
            $('<input>')
              .attr({
                type: 'checkbox',
                name: 'levels_of_care[]',
                value: newLevel,
              })
              .prop('checked', true)
          );

          $levelLabel.append(` ${newLevel}`);

          // Add to container before the add new field
          $levelLabel.insertBefore('.add-new-level');

          console.log('Level added successfully');

          // Clear input
          $newLevelInput.val('');
        } else {
          alert('This level of care already exists!');
        }
      } else {
        console.log('No level value entered');
      }
    });

    // Add new program feature
    $('#add-feature-btn').on('click', () => {
      console.log('Add feature button clicked');
      const $newFeatureInput = $('#new-feature');
      const newFeature = $newFeatureInput.val().trim();

      console.log('New feature value:', newFeature);

      if (newFeature) {
        // Check if feature already exists
        let exists = false;
        $('.facility-features-container input[type="checkbox"]').each(function () {
          if ($(this).val() === newFeature) {
            exists = true;
            return false;
          }
        });

        console.log('Feature exists:', exists);

        if (!exists) {
          // Create new feature checkbox
          const $featureLabel = $('<label>');

          $featureLabel.append(
            $('<input>')
              .attr({
                type: 'checkbox',
                name: 'program_features[]',
                value: newFeature,
              })
              .prop('checked', true)
          );

          $featureLabel.append(` ${newFeature}`);

          // Add to container before the add new field
          $featureLabel.insertBefore('.add-new-feature');

          console.log('Feature added successfully');

          // Clear input
          $newFeatureInput.val('');
        } else {
          alert('This program feature already exists!');
        }
      } else {
        console.log('No feature value entered');
      }
    });

    // Custom pin image upload functionality
    let customUploader;

    $('#upload-pin-button').on('click', (e) => {
      e.preventDefault();

      console.log('Upload pin button clicked');

      // If the media frame already exists, reopen it.
      if (customUploader) {
        customUploader.open();
        return;
      }

      // Check if wp.media is available
      if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
        alert('WordPress media library is not available. Please refresh the page and try again.');
        return;
      }

      // Create a new media frame
      customUploader = wp.media({
        title: 'Choose Pin Image',
        button: {
          text: 'Choose Pin',
        },
        multiple: false,
        library: {
          type: 'image',
        },
      });

      // When an image is selected in the media frame...
      customUploader.on('select', () => {
        const attachment = customUploader.state().get('selection').first().toJSON();

        console.log('Image selected:', attachment);

        // Update hidden field
        $('#facility-custom-pin').val(attachment.url);

        // Update preview
        $('#custom-pin-preview').html(
          `<img src="${attachment.url}" style="max-width: 50px; max-height: 50px; display: block; margin-bottom: 5px;">`
        );

        // Show remove button
        $('#remove-pin-button').show();
      });

      // Open the modal
      customUploader.open();
    });

    $('#remove-pin-button').on('click', (e) => {
      e.preventDefault();

      // Clear hidden field
      $('#facility-custom-pin').val('');

      // Clear preview
      $('#custom-pin-preview').empty();

      // Hide remove button
      $(e.target).hide();
    });
  };

  /**
   * Validate facility form
   */
  const validateForm = ($form) => {
    let valid = true;

    // Check required fields
    $form.find('[required]').each(function () {
      const $field = $(this);

      if (!$field.val()) {
        valid = false;

        // Highlight field
        $field.addClass('error');

        // Add error message if not already present
        if (!$field.next('.error-message').length) {
          $field.after('<span class="error-message">This field is required.</span>');
        }
      } else {
        // Remove error highlight
        $field.removeClass('error');
        $field.next('.error-message').remove();
      }
    });

    // Check lat/lng
    const lat = $('#facility-lat').val();
    const lng = $('#facility-lng').val();

    if (!lat || !lng) {
      valid = false;

      // Highlight map container
      $('#facility-map').addClass('error');

      // Add error message if not already present
      if (!$('#facility-map').next('.error-message').length) {
        $('#facility-map').after('<span class="error-message">Please select a location on the map.</span>');
      }

      // Highlight address field
      $('#facility-address').addClass('error');
    } else {
      // Remove error highlight
      $('#facility-map').removeClass('error');
      $('#facility-map').next('.error-message').remove();
      $('#facility-address').removeClass('error');
    }

    return valid;
  };

  /**
   * Initialize facility list
   */
  const initFacilityList = () => {
    // Search functionality
    $('#facility-search').on('keyup', function () {
      const searchText = $(this).val().toLowerCase();

      $('.wp-list-table tbody tr').each(function () {
        const name = $(this).find('td:first').text().toLowerCase();
        const address = $(this).find('td:nth-child(2)').text().toLowerCase();
        const categories = $(this).find('td:nth-child(3)').text().toLowerCase();

        if (name.includes(searchText) || address.includes(searchText) || categories.includes(searchText)) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    });

    // Delete facility
    $('.delete-facility').on('click', function () {
      const id = $(this).data('id');
      const $modal = $('#delete-facility-modal');

      // Store facility ID in the modal
      $modal.data('id', id);

      // Show modal
      $modal.show();
    });

    // Cancel delete
    $('#cancel-delete').on('click', () => {
      $('#delete-facility-modal').hide();
    });

    // Confirm delete
    $('#confirm-delete').on('click', function () {
      const $modal = $('#delete-facility-modal');
      const id = $modal.data('id');

      // Disable button and show loading state
      const $confirmBtn = $(this);
      $confirmBtn.prop('disabled', true).text('Deleting...');

      // Send AJAX request
      $.ajax({
        url: facilityLocator.ajaxUrl,
        type: 'POST',
        data: {
          action: 'delete_facility',
          nonce: facilityLocator.nonce,
          id: id,
        },
        success: (response) => {
          if (response.success) {
            // Remove table row
            $(`tr[data-id="${id}"]`).fadeOut(300, function () {
              $(this).remove();

              // Check if table is empty
              if ($('.wp-list-table tbody tr').length === 0) {
                $('.wp-list-table tbody').html(
                  '<tr><td colspan="4">No facilities found. <a href="admin.php?page=facility-locator-add-new">Add your first facility</a>.</td></tr>'
                );
              }
            });

            // Hide modal
            $modal.hide();
          } else {
            // Show error message
            alert(`Error: ${response.data}`);

            // Reset button
            $confirmBtn.prop('disabled', false).text('Delete');
          }
        },
        error: (xhr, status, error) => {
          // Show error message
          alert(`AJAX error: ${error}`);

          // Reset button
          $confirmBtn.prop('disabled', false).text('Delete');
        },
      });
    });

    // Close modal when clicking outside
    $(window).on('click', (e) => {
      const $modal = $('#delete-facility-modal');

      if ($(e.target).is($modal)) {
        $modal.hide();
      }
    });

    // Check for saved message
    if (window.location.search.includes('saved=true')) {
      // Show success notice
      const $notice = $('<div class="notice notice-success is-dismissible"><p>Facility saved successfully.</p></div>');

      $('.wrap h1.wp-heading-inline').after($notice);

      // Remove notice after 3 seconds
      setTimeout(() => {
        $notice.fadeOut(300, function () {
          $(this).remove();
        });
      }, 3000);

      // Update URL without the saved parameter
      const url = window.location.href
        .replace('&saved=true', '')
        .replace('?saved=true&', '?')
        .replace('?saved=true', '');
      window.history.replaceState({}, document.title, url);
    }
  };

  // Initialize when document is ready
  $(document).ready(init);
})(jQuery);
