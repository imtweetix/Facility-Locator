/**
 * Image Gallery functionality for Facility Form
 * Pure jQuery implementation with proper dependency handling
 */
(($) => {
  'use strict';

  /**
   * Initialize image gallery functionality
   */
  const initImageGallery = () => {
    // Check if facilityFormData is available
    if (typeof facilityFormData === 'undefined') {
      console.warn('Image Gallery: facilityFormData not available, retrying...');

      // Retry after a short delay
      setTimeout(initImageGallery, 100);
      return;
    }

    let imageUploader;
    let imageCount = facilityFormData.imageCount || 0;
    const maxImages = facilityFormData.maxImages || 5;

    console.log('Image Gallery: Initializing with', imageCount, 'existing images');

    // Make images sortable if container exists
    const $imagesContainer = $('#facility-images-container');
    if ($imagesContainer.length) {
      $imagesContainer.sortable({
        handle: '.drag-handle',
        update: () => {
          updateImageOrder();
        },
      });
    }

    // Add image button
    $('#add-facility-image').on('click', (e) => {
      e.preventDefault();

      if (imageCount >= maxImages) {
        alert(`Maximum of ${maxImages} images allowed.`);
        return;
      }

      // Check if wp.media is available
      if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
        alert('WordPress media library is not available. Please refresh the page and try again.');
        return;
      }

      if (imageUploader) {
        imageUploader.open();
        return;
      }

      imageUploader = wp.media({
        title: 'Choose Facility Images',
        button: {
          text: 'Add Images',
        },
        multiple: true,
        library: {
          type: 'image',
        },
      });

      imageUploader.on('select', () => {
        const attachments = imageUploader.state().get('selection');
        const remainingSlots = maxImages - imageCount;

        let addedCount = 0;
        attachments.each((attachment) => {
          if (addedCount >= remainingSlots) {
            return false;
          }

          const attachmentData = attachment.toJSON();
          addImageToGallery(attachmentData.url, imageCount);
          imageCount++;
          addedCount++;
        });

        updateImageCount();
        updateAddButtonState();
      });

      imageUploader.open();
    });

    // Remove image
    $(document).on('click', '.remove-image', (e) => {
      e.preventDefault();
      $(e.target).closest('.facility-image-item').remove();
      imageCount--;
      updateImageCount();
      updateAddButtonState();
      updateImageOrder();
    });

    /**
     * Add image to gallery
     */
    const addImageToGallery = (imageUrl, index) => {
      const imageHtml = `
        <div class="facility-image-item" data-index="${index}">
          <img src="${imageUrl}" style="max-width: 150px; max-height: 100px; object-fit: cover;">
          <input type="hidden" name="images[]" value="${imageUrl}">
          <button type="button" class="button remove-image">Remove</button>
          <span class="drag-handle">⋮⋮</span>
        </div>
      `;
      $('#facility-images-container').append(imageHtml);
    };

    /**
     * Update image count display
     */
    const updateImageCount = () => {
      const $countElement = $('#image-count');
      if ($countElement.length) {
        $countElement.text(`(${imageCount}/${maxImages})`);
      }
    };

    /**
     * Update add button state
     */
    const updateAddButtonState = () => {
      const $addButton = $('#add-facility-image');
      if ($addButton.length) {
        $addButton.prop('disabled', imageCount >= maxImages);

        if (imageCount >= maxImages) {
          $addButton.text(`Maximum ${maxImages} images reached`);
        } else {
          $addButton.text('Add Image');
        }
      }
    };

    /**
     * Update image order after sorting
     */
    const updateImageOrder = () => {
      $('#facility-images-container .facility-image-item').each((index, element) => {
        $(element).attr('data-index', index);
      });
    };

    // Initialize button state
    updateAddButtonState();
    updateImageCount();

    console.log('Image Gallery: Initialization complete');
  };

  /**
   * DOM ready initialization with error handling
   */
  $(() => {
    // Check if we're on a facility form page
    if ($('#facility-form').length === 0) {
      console.log('Image Gallery: Not on facility form page, skipping initialization');
      return;
    }

    // Start initialization
    initImageGallery();
  });
})(jQuery);
