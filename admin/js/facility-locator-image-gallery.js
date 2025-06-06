/**
 * Image Gallery functionality for Facility Form
 * Pure jQuery implementation for image gallery management
 */
(function ($) {
  'use strict';

  $(document).ready(() => {
    let imageUploader;
    let imageCount = facilityFormData.imageCount;
    const maxImages = facilityFormData.maxImages;

    // Make images sortable
    $('#facility-images-container').sortable({
      handle: '.drag-handle',
      update: () => {
        updateImageOrder();
      },
    });

    // Add image button
    $('#add-facility-image').on('click', (e) => {
      e.preventDefault();

      if (imageCount >= maxImages) {
        alert('Maximum of 5 images allowed.');
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

    const updateImageCount = () => {
      $('#image-count').text(`(${imageCount}/${maxImages})`);
    };

    const updateAddButtonState = () => {
      $('#add-facility-image').prop('disabled', imageCount >= maxImages);
    };

    const updateImageOrder = () => {
      $('#facility-images-container .facility-image-item').each((index, element) => {
        $(element).attr('data-index', index);
      });
    };

    // Initialize button state
    updateAddButtonState();
  });
})(jQuery);
