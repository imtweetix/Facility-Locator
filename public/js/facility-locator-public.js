/**
 * Enhanced Frontend JavaScript for Recovery.com + Google Maps style interface
 * Complete rewrite with proper jQuery implementation and ES6 features
 */
(($) => {
  'use strict';

  // Store map instances and data
  const maps = {};
  const markers = {};
  const markerClusterer = {};
  const infoWindows = {};
  const facilitiesData = {};
  const activeFilters = {};
  let currentFacilityId = null;

  // Google Maps initialization checker
  let mapInitRetries = 0;
  const maxMapInitRetries = 10;

  /**
   * Check if Google Maps is loaded and ready
   */
  const checkGoogleMapsReady = () => {
    return new Promise((resolve, reject) => {
      if (window.google && window.google.maps) {
        resolve(true);
        return;
      }

      mapInitRetries++;
      if (mapInitRetries >= maxMapInitRetries) {
        reject(new Error('Google Maps failed to load after maximum retries'));
        return;
      }

      setTimeout(() => {
        checkGoogleMapsReady().then(resolve).catch(reject);
      }, 500);
    });
  };

  /**
   * Validate that all required template elements exist
   */
  const validateTemplateElements = ($container, id) => {
    const requiredElements = [
      '.facility-locator-cta-button',
      '.facility-locator-popup',
      '.facility-locator-popup-close',
      '.facility-locator-steps',
      '.facility-locator-form-navigation',
      '.facility-locator-main-interface',
    ];

    const missingElements = [];

    requiredElements.forEach((selector) => {
      if ($container.find(selector).length === 0) {
        missingElements.push(selector);
      }
    });

    if (missingElements.length > 0) {
      console.error('Missing template elements in container', id, ':', missingElements);
      return false;
    }

    console.log('All template elements found for container:', id);
    return true;
  };

  /**
   * Initialize the plugin
   */
  const init = () => {
    console.log('Facility Locator: Initializing plugin');

    // Check if facilityLocator global is available
    if (typeof facilityLocator === 'undefined') {
      console.error('Facility Locator: facilityLocator global not found. Check script localization.');
      return;
    }

    $('.facility-locator-container').each(function () {
      const $container = $(this);
      const id = $container.attr('id');

      console.log('Initializing container:', id);

      // Validate template elements
      if (!validateTemplateElements($container, id)) {
        console.error('Template validation failed for container:', id);
        return; // Skip this container
      }

      // Initialize data storage
      facilitiesData[id] = [];
      activeFilters[id] = {};

      // Set up event listeners first
      initEventListeners($container, id);

      // Build form steps after event listeners
      buildFormSteps($container, id);

      console.log('Container initialized successfully:', id);
    });

    if ($('.facility-locator-container').length === 0) {
      console.warn('Facility Locator: No containers found on page');
    } else {
      console.log('Facility Locator: All containers initialized');
    }
  };

  /**
   * Initialize event listeners with proper jQuery syntax
   */
  const initEventListeners = ($container, id) => {
    const $ctaButton = $container.find('.facility-locator-cta-button');
    const $popup = $container.find('.facility-locator-popup');
    const $closeButton = $popup.find('.facility-locator-popup-close');
    const $form = $popup.find('form');
    const $nextBtn = $popup.find('.facility-locator-next-btn');
    const $prevBtn = $popup.find('.facility-locator-prev-btn');
    const $submitBtn = $popup.find('.facility-locator-submit-btn');
    const $skipLink = $popup.find('.facility-locator-skip-link');

    console.log('Initializing event listeners for container:', id);
    console.log('CTA Button found:', $ctaButton.length);
    console.log('Popup found:', $popup.length);

    // CTA button click - show popup
    $ctaButton.on('click', (e) => {
      e.preventDefault();
      console.log('CTA button clicked, showing popup');

      // Ensure popup is visible with proper z-index
      $popup
        .css({
          display: 'block',
          'z-index': '9999',
          position: 'fixed',
        })
        .fadeIn(300);

      $('body').css('overflow', 'hidden');
    });

    // Close button click
    $closeButton.on('click', (e) => {
      e.preventDefault();
      console.log('Popup close button clicked');
      $popup.fadeOut(300);
      $('body').css('overflow', 'auto');
    });

    // Close popup when clicking outside
    $popup.on('click', (e) => {
      if ($(e.target).hasClass('facility-locator-popup')) {
        console.log('Clicked outside popup, closing');
        $popup.fadeOut(300);
        $('body').css('overflow', 'auto');
      }
    });

    // Form navigation
    $nextBtn.on('click', (e) => {
      e.preventDefault();
      console.log('Next button clicked');
      navigateStep($container, 'next');
    });

    $prevBtn.on('click', (e) => {
      e.preventDefault();
      console.log('Previous button clicked');
      navigateStep($container, 'prev');
    });

    $form.on('submit', (e) => {
      e.preventDefault();
      console.log('Form submitted');
      submitForm($container, id);
    });

    $skipLink.on('click', (e) => {
      e.preventDefault();
      console.log('Skip link clicked');
      showAllFacilities($container, id);
    });

    // Mobile and filter interactions (use delegated events for dynamic content)
    initDelegatedEventListeners($container, id);
  };

  /**
   * Initialize delegated event listeners for dynamic content
   */
  const initDelegatedEventListeners = ($container, id) => {
    // Filter interactions
    $(document).on('click', '.filter-dropdown-button', function (e) {
      e.stopPropagation();
      const $dropdown = $(this).closest('.filter-dropdown');
      const isOpen = $dropdown.hasClass('open');

      // Close all other dropdowns
      $('.filter-dropdown').removeClass('open');

      // Toggle current dropdown
      if (!isOpen) {
        $dropdown.addClass('open');
      }
    });

    // Close dropdowns when clicking outside
    $(document).on('click', (e) => {
      if (!$(e.target).closest('.filter-dropdown').length) {
        $('.filter-dropdown').removeClass('open');
      }
    });

    // Filter option selection
    $(document).on('change', '.filter-option input[type="checkbox"]', function () {
      const $container = $(this).closest('.facility-locator-container');
      const id = $container.attr('id');
      updateFilters($container, id);
    });

    // Clear all filters
    $(document).on('click', '.clear-all-filters', function () {
      const $container = $(this).closest('.facility-locator-container');
      const id = $container.attr('id');
      clearAllFilters($container, id);
    });

    // Facility card interactions
    $(document).on('click', '.facility-card', function () {
      const facilityId = $(this).data('facility-id');
      const $container = $(this).closest('.facility-locator-container');
      const id = $container.attr('id');

      // Highlight card
      $('.facility-card').removeClass('highlighted');
      $(this).addClass('highlighted');

      // Highlight map pin and show details
      highlightMapPin(id, facilityId);
      showFacilityDetails($container, facilityId);
    });

    // Carousel navigation
    $(document).on('click', '.carousel-prev', function (e) {
      e.stopPropagation();
      navigateCarousel($(this), -1);
    });

    $(document).on('click', '.carousel-next', function (e) {
      e.stopPropagation();
      navigateCarousel($(this), 1);
    });

    $(document).on('click', '.carousel-indicator', function (e) {
      e.stopPropagation();
      const index = $(this).data('index');
      goToCarouselSlide($(this), index);
    });

    // Detailed modal interactions
    $(document).on('click', '.facility-detail-close', function () {
      const $modal = $(this).closest('.facility-detail-modal');
      $modal.removeClass('open');
      $('.facility-card').removeClass('highlighted');
      clearHighlightedPin();
    });

    // Mobile interactions
    $(document).on('click', '.mobile-filter-trigger', function () {
      $('.mobile-filter-drawer').addClass('open');
    });

    $(document).on('click', '.mobile-filter-close', function () {
      $('.mobile-filter-drawer').removeClass('open');
    });

    $(document).on('click', '.apply-mobile-filters', function () {
      $('.mobile-filter-drawer').removeClass('open');
      const $container = $(this).closest('.facility-locator-container');
      const id = $container.attr('id');
      updateFilters($container, id);
    });
  };

  /**
   * Build form steps
   */
  const buildFormSteps = ($container, id) => {
    const formSteps = facilityLocator.formSteps;
    const $stepsContainer = $container.find('.facility-locator-steps');

    // Clear any existing steps
    $stepsContainer.empty();

    if (!formSteps || formSteps.length === 0) {
      // No form steps configured - create a simple "get started" step
      const $simpleStep = $('<div>').addClass('facility-locator-step active').attr('data-step', 0);
      $simpleStep.append(`
        <h2>Find Facilities Near You</h2>
        <p style="margin: 20px 0; color: #6b7280; font-size: 16px; text-align: center;">
          Browse all available facilities or use our search filters to find exactly what you're looking for.
        </p>
      `);
      $stepsContainer.append($simpleStep);

      // Update the navigation to skip directly to facilities
      const $submitBtn = $container.find('.facility-locator-submit-btn');
      const $nextBtn = $container.find('.facility-locator-next-btn');

      $nextBtn.hide();
      $submitBtn.show().text('Browse Facilities');

      console.log('Form steps: No steps configured, showing simple popup');
      return;
    }

    // Build form steps normally
    formSteps.forEach((step, index) => {
      buildStep($stepsContainer, step, index);
    });

    $container.find('.facility-locator-step').first().addClass('active');
    updateNavButtons($container);

    console.log('Form steps: Built', formSteps.length, 'steps');
  };

  /**
   * Build a single step with columns
   */
  const buildStep = ($stepsContainer, step, index) => {
    const $step = $('<div>').addClass('facility-locator-step').attr('data-step', index);
    $step.append($('<h2>').text(`Step ${index + 1}: ${step.title}`));

    if (step.columns && step.columns.length > 0) {
      const $columnsContainer = $('<div>').addClass('form-columns-container');

      step.columns.forEach((column) => {
        $columnsContainer.append(buildColumn(column));
      });

      $step.append($columnsContainer);
    }

    $stepsContainer.append($step);
  };

  /**
   * Build a form column
   */
  const buildColumn = (column) => {
    const $column = $('<div>').addClass('facility-locator-column');
    const fieldId = `column_${Math.random().toString(36).substr(2, 9)}`;

    if (column.header) {
      $column.append($('<h3>').text(column.header));
    }

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
   * Build radio field
   */
  const buildRadioField = (column, fieldId) => {
    const $container = $('<div>').addClass('facility-locator-field-options');

    if (column.options && Array.isArray(column.options)) {
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
    }

    return $container;
  };

  /**
   * Build checkbox field
   */
  const buildCheckboxField = (column, fieldId) => {
    const $container = $('<div>').addClass('facility-locator-field-options');

    if (column.options && Array.isArray(column.options)) {
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
    }

    return $container;
  };

  /**
   * Build dropdown field
   */
  const buildDropdownField = (column, fieldId) => {
    const $select = $('<select>')
      .attr({
        id: fieldId,
        name: fieldId,
      })
      .addClass('facility-locator-select');

    $select.append(
      $('<option>')
        .val('')
        .text(`Select ${column.header || 'Option'}`)
    );

    if (column.options && Array.isArray(column.options)) {
      column.options.forEach((option) => {
        $select.append($('<option>').val(option.value).text(option.label));
      });
    }

    return $select;
  };

  /**
   * Navigate between form steps
   */
  const navigateStep = ($container, direction) => {
    const $steps = $container.find('.facility-locator-step');
    const $currentStep = $steps.filter('.active');
    const currentIndex = parseInt($currentStep.data('step'));

    if (direction === 'next' && !validateStep($currentStep)) {
      return;
    }

    const newIndex = direction === 'next' ? currentIndex + 1 : currentIndex - 1;

    if (newIndex >= 0 && newIndex < $steps.length) {
      $currentStep.removeClass('active');
      $steps.eq(newIndex).addClass('active');
      updateNavButtons($container);
    }
  };

  /**
   * Validate current step
   */
  const validateStep = ($step) => {
    const $requiredFields = $step.find('[required]');
    let valid = true;

    $requiredFields.each(function () {
      const $field = $(this);
      if ($field.is(':checkbox, :radio')) {
        const name = $field.attr('name').replace('[]', '');
        if (!$step.find(`[name^="${name}"]:checked`).length) {
          valid = false;
          $field.closest('.facility-locator-field').addClass('error');
        }
      } else {
        if (!$field.val()) {
          valid = false;
          $field.addClass('error');
        }
      }
    });

    return valid;
  };

  /**
   * Update navigation buttons
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

    $prevBtn.toggle(!isFirstStep);

    if (isLastStep) {
      $nextBtn.hide();
      $submitBtn.show();
    } else {
      $nextBtn.show();
      $submitBtn.hide();
    }
  };

  /**
   * Submit form and show main interface
   */
  const submitForm = ($container, id) => {
    console.log('Submitting form for container:', id);

    const $form = $container.find('form');
    const formData = $form.serializeArray();
    const data = {};

    formData.forEach((field) => {
      if (field.name.endsWith('[]')) {
        const fieldName = field.name.slice(0, -2);
        if (!data[fieldName]) {
          data[fieldName] = [];
        }
        data[fieldName].push(field.value);
      } else {
        data[field.name] = field.value;
      }
    });

    console.log('Form data collected:', data);

    // Close popup and show main interface
    const $popup = $container.find('.facility-locator-popup');
    $popup.fadeOut(300, () => {
      showMainInterface($container, id, data);
    });

    $('body').css('overflow', 'auto');
  };

  /**
   * Show all facilities without filtering
   */
  const showAllFacilities = ($container, id) => {
    console.log('Showing all facilities for container:', id);

    // Close popup with fade effect
    const $popup = $container.find('.facility-locator-popup');
    $popup.fadeOut(300, () => {
      // Show main interface after popup closes
      showMainInterface($container, id, {});
    });

    $('body').css('overflow', 'auto');
  };

  /**
   * Show main interface
   */
  const showMainInterface = ($container, id, formData = {}) => {
    console.log('Showing main interface for container:', id);

    // Hide CTA section
    const $cta = $container.find('.facility-locator-cta');
    $cta.fadeOut(300);

    // Show main interface
    const $mainInterface = $container.find('.facility-locator-main-interface');
    $mainInterface.addClass('active').fadeIn(300);

    // Store initial form data as filters
    activeFilters[id] = { ...formData };

    console.log('Active filters set:', activeFilters[id]);

    // Fetch facilities and render interface
    fetchFacilities($container, id);
  };

  /**
   * Fetch facilities via AJAX
   */
  const fetchFacilities = ($container, id) => {
    // Show loading state
    const $cardsContainer = $container.find('.facility-cards-container');
    $cardsContainer.addClass('loading').html('<div class="loading-spinner"></div>');

    $.ajax({
      url: facilityLocator.ajaxUrl,
      type: 'POST',
      data: {
        action: 'get_facilities',
        nonce: facilityLocator.nonce,
        form_data: activeFilters[id],
      },
      success: (response) => {
        $cardsContainer.removeClass('loading');

        if (response.success) {
          facilitiesData[id] = response.data.facilities;

          renderFilterBar($container, id, response.data.filters);
          renderFacilityCards($container, id, response.data.facilities);
          renderMap($container, id, response.data.facilities);
        } else {
          console.error('Error fetching facilities:', response.data);
          $cardsContainer.html('<div class="no-results">Error loading facilities. Please try again.</div>');
        }
      },
      error: (xhr, status, error) => {
        console.error('AJAX error:', error);
        $cardsContainer
          .removeClass('loading')
          .html('<div class="no-results">Error loading facilities. Please try again.</div>');
      },
    });
  };

  /**
   * Render filter bar with Recovery.com styling
   */
  const renderFilterBar = ($container, id, filters) => {
    const $filterItems = $container.find('.filter-items');
    $filterItems.empty();

    // Add mobile filter trigger
    if (window.innerWidth <= 768) {
      $filterItems.append(`
        <button class="mobile-filter-trigger">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/>
          </svg>
          Filters
        </button>
      `);
    }

    Object.entries(filters).forEach(([taxonomyType, taxonomyItems]) => {
      if (taxonomyItems && taxonomyItems.length > 0) {
        const taxonomyDisplayName = facilityLocator.availableTaxonomies[taxonomyType]?.label || taxonomyType;
        const selectedCount = getSelectedFilterCount(id, taxonomyType);
        const buttonText = selectedCount > 0 ? `${taxonomyDisplayName} (${selectedCount})` : taxonomyDisplayName;

        const $dropdown = $(`
          <div class="filter-dropdown" data-taxonomy="${taxonomyType}">
            <button class="filter-dropdown-button ${selectedCount > 0 ? 'active' : ''}">
              <span>${buttonText}</span>
              <svg class="filter-dropdown-arrow" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7 10l5 5 5-5z"/>
              </svg>
            </button>
            <div class="filter-dropdown-menu">
              ${taxonomyItems
                .map(
                  (item) => `
                <div class="filter-option">
                  <input type="checkbox" id="filter_${taxonomyType}_${item.id}" 
                         value="${item.id}" data-taxonomy="${taxonomyType}"
                         ${isFilterSelected(id, taxonomyType, item.id) ? 'checked' : ''}>
                  <label for="filter_${taxonomyType}_${item.id}">${item.name}</label>
                </div>
              `
                )
                .join('')}
            </div>
          </div>
        `);

        $filterItems.append($dropdown);
      }
    });

    // Render mobile filter drawer
    renderMobileFilterDrawer($container, id, filters);
  };

  /**
   * Render mobile filter drawer - FIXED WITH ID PARAMETER
   */
  const renderMobileFilterDrawer = ($container, id, filters) => {
    let $drawer = $container.find('.mobile-filter-drawer');

    if ($drawer.length === 0) {
      $drawer = $(`
      <div class="mobile-filter-drawer">
        <div class="mobile-filter-header">
          <h3>Filters</h3>
          <button class="mobile-filter-close">&times;</button>
        </div>
        <div class="mobile-filter-content"></div>
        <div class="mobile-filter-footer">
          <button class="clear-all-filters">Clear All</button>
          <button class="apply-mobile-filters">Apply Filters</button>
        </div>
      </div>
    `);
      $container.append($drawer);
    }

    const $mobileContent = $drawer.find('.mobile-filter-content');
    $mobileContent.empty();

    Object.entries(filters).forEach(([taxonomyType, taxonomyItems]) => {
      if (taxonomyItems && taxonomyItems.length > 0) {
        const taxonomyDisplayName = facilityLocator.availableTaxonomies[taxonomyType]?.label || taxonomyType;

        const $section = $(`
        <div class="mobile-filter-section">
          <h4>${taxonomyDisplayName}</h4>
          <div class="mobile-filter-options">
            ${taxonomyItems
              .map(
                (item) => `
              <div class="filter-option">
                <input type="checkbox" id="mobile_filter_${taxonomyType}_${item.id}" 
                       value="${item.id}" data-taxonomy="${taxonomyType}"
                       ${isFilterSelected(id, taxonomyType, item.id) ? 'checked' : ''}>
                <label for="mobile_filter_${taxonomyType}_${item.id}">${item.name}</label>
              </div>
            `
              )
              .join('')}
          </div>
        </div>
      `);

        $mobileContent.append($section);
      }
    });
  };

  /**
   * Render facility cards with Recovery.com styling
   */
  const renderFacilityCards = ($container, id, facilities) => {
    const $cardsContainer = $container.find('.facility-cards-container');
    const $resultsCount = $container.find('.results-count');

    $resultsCount.text(`${facilities.length} facilities found`);
    $cardsContainer.empty();

    if (!facilities || facilities.length === 0) {
      $cardsContainer.html('<div class="no-results">No facilities found. Please adjust your search criteria.</div>');
      return;
    }

    facilities.forEach((facility) => {
      const $card = createFacilityCard(facility);
      $cardsContainer.append($card);
    });
  };

  /**
   * Create a facility card with image gallery support
   */
  const createFacilityCard = (facility) => {
    const images = facility.images || [];
    const hasImages = images.length > 0;
    const maxImages = Math.min(images.length, 5);

    const $card = $(`
      <div class="facility-card" data-facility-id="${facility.id}">
        <div class="facility-card-header">
          <h3 class="facility-name">${facility.name}</h3>
          <span class="facility-type">${facility.levels_of_care_names?.[0] || 'Facility'}</span>
        </div>
        <div class="facility-address">${facility.address}</div>
        ${
          hasImages
            ? createImageCarousel(images, maxImages)
            : '<div class="facility-image-carousel"><div class="carousel-placeholder">No images available</div></div>'
        }
        ${createFeatureTags(facility)}
        <div class="facility-actions">
          ${
            facility.phone
              ? `<a href="tel:${facility.phone}" class="facility-action-btn primary">${facility.phone}</a>`
              : ''
          }
          ${
            facility.website
              ? `<a href="${facility.website}" target="_blank" class="facility-action-btn">Visit Website</a>`
              : ''
          }
        </div>
      </div>
    `);

    return $card;
  };

  /**
   * Create image carousel with 300x206 aspect ratio
   */
  const createImageCarousel = (images, maxImages) => {
    const limitedImages = images.slice(0, maxImages);

    return `
      <div class="facility-image-carousel" data-current-slide="0">
        <div class="carousel-container">
          <div class="carousel-slides">
            ${limitedImages
              .map(
                (image) => `
              <div class="carousel-slide">
                <img src="${image}" alt="Facility image" loading="lazy">
              </div>
            `
              )
              .join('')}
          </div>
          ${
            limitedImages.length > 1
              ? `
            <button class="carousel-nav carousel-prev">‹</button>
            <button class="carousel-nav carousel-next">›</button>
            <div class="carousel-indicators">
              ${limitedImages
                .map(
                  (_, index) => `
                <div class="carousel-indicator ${index === 0 ? 'active' : ''}" data-index="${index}"></div>
              `
                )
                .join('')}
            </div>
          `
              : ''
          }
        </div>
      </div>
    `;
  };

  /**
   * Create feature tags
   */
  const createFeatureTags = (facility) => {
    const features = [];

    // Collect features from all taxonomies
    Object.keys(facilityLocator.availableTaxonomies).forEach((taxonomyType) => {
      const names = facility[`${taxonomyType}_names`];
      if (names && names.length > 0) {
        features.push(...names.slice(0, 3)); // Limit to 3 per taxonomy
      }
    });

    if (features.length === 0) return '';

    return `
      <div class="facility-features">
        <div class="feature-tags">
          ${features
            .slice(0, 6)
            .map((feature) => `<span class="feature-tag">${feature}</span>`)
            .join('')}
        </div>
      </div>
    `;
  };

  /**
   * Navigate carousel
   */
  const navigateCarousel = ($button, direction) => {
    const $carousel = $button.closest('.facility-image-carousel');
    const $slides = $carousel.find('.carousel-slides');
    const $indicators = $carousel.find('.carousel-indicator');
    const totalSlides = $carousel.find('.carousel-slide').length;

    let currentSlide = parseInt($carousel.data('current-slide')) || 0;
    currentSlide = (currentSlide + direction + totalSlides) % totalSlides;

    $carousel.data('current-slide', currentSlide);
    $slides.css('transform', `translateX(-${currentSlide * 100}%)`);

    $indicators.removeClass('active');
    $indicators.eq(currentSlide).addClass('active');
  };

  /**
   * Go to specific carousel slide
   */
  const goToCarouselSlide = ($indicator, index) => {
    const $carousel = $indicator.closest('.facility-image-carousel');
    const $slides = $carousel.find('.carousel-slides');
    const $indicators = $carousel.find('.carousel-indicator');

    $carousel.data('current-slide', index);
    $slides.css('transform', `translateX(-${index * 100}%)`);

    $indicators.removeClass('active');
    $indicators.eq(index).addClass('active');
  };

  /**
   * Render Google Map with enhanced pin functionality
   */
  const renderMap = async ($container, id, facilities) => {
    const $mapContainer = $container.find(`#${id}-map`);

    // Check if API key is available
    if (!facilityLocator.hasApiKey) {
      $mapContainer.html(`
      <div style="padding: 40px; text-align: center; background: #f8f9fa; border-radius: 8px;">
        <h3 style="color: #6b7280; margin: 0 0 8px 0;">Google Maps API Key Required</h3>
        <p style="color: #9ca3af; margin: 0;">Please configure your Google Maps API key to view the map.</p>
      </div>
    `);
      return;
    }

    try {
      // Wait for Google Maps to be ready
      await checkGoogleMapsReady();

      if (!maps[id]) {
        maps[id] = new google.maps.Map($mapContainer[0], {
          zoom: parseInt(facilityLocator.settings?.mapZoom) || 10,
          center: { lat: 40.7128, lng: -74.006 },
          mapTypeControl: true,
          scrollwheel: true,
          streetViewControl: false,
          fullscreenControl: true,
          styles: [
            {
              featureType: 'poi',
              elementType: 'labels',
              stylers: [{ visibility: 'off' }],
            },
          ],
        });

        markers[id] = [];
        infoWindows[id] = [];
      }

      // Clear existing markers and clusterer
      clearMarkers(id);

      if (!facilities || facilities.length === 0) {
        return;
      }

      const bounds = new google.maps.LatLngBounds();
      const defaultPinImage = facilityLocator.settings?.defaultPinImage;

      // Create markers with custom pins
      facilities.forEach((facility, index) => {
        const position = {
          lat: parseFloat(facility.lat),
          lng: parseFloat(facility.lng),
        };

        // Determine pin image
        let pinIcon = null;
        if (facility.custom_pin_image && facility.custom_pin_image.trim() !== '') {
          // Use facility's custom pin
          pinIcon = {
            url: facility.custom_pin_image,
            scaledSize: new google.maps.Size(32, 40),
            anchor: new google.maps.Point(16, 40),
          };
        } else if (defaultPinImage && defaultPinImage.trim() !== '') {
          // Use default custom pin
          pinIcon = {
            url: defaultPinImage,
            scaledSize: new google.maps.Size(32, 40),
            anchor: new google.maps.Point(16, 40),
          };
        } else {
          // Use default Google Maps pin with custom color
          pinIcon = {
            url:
              'data:image/svg+xml;charset=UTF-8,' +
              encodeURIComponent(`
            <svg width="32" height="40" viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg">
              <path d="M16 0C7.2 0 0 7.2 0 16c0 16 16 24 16 24s16-8 16-24c0-8.8-7.2-16-16-16z" fill="#3b82f6"/>
              <circle cx="16" cy="16" r="8" fill="white"/>
            </svg>
          `),
            scaledSize: new google.maps.Size(32, 40),
            anchor: new google.maps.Point(16, 40),
          };
        }

        const marker = new google.maps.Marker({
          position,
          title: facility.name,
          icon: pinIcon,
        });

        // Create info window
        const infoWindow = new google.maps.InfoWindow({
          content: `
          <div style="padding: 8px; min-width: 200px;">
            <h4 style="margin: 0 0 8px 0; font-size: 16px;">${facility.name}</h4>
            <p style="margin: 0 0 8px 0; color: #666; font-size: 14px;">${facility.address}</p>
            ${
              facility.phone
                ? `<p style="margin: 0; font-size: 14px;"><strong>Phone:</strong> ${facility.phone}</p>`
                : ''
            }
          </div>
        `,
        });

        // Add click listener
        marker.addListener('click', () => {
          // Close all info windows
          infoWindows[id].forEach((window) => window.close());

          // Open this info window
          infoWindow.open(maps[id], marker);

          // Highlight facility card and show details
          highlightFacilityCard(facility.id);
          showFacilityDetails($container, facility.id);
        });

        markers[id].push(marker);
        infoWindows[id].push(infoWindow);
        bounds.extend(position);
      });

      // Add marker clustering if available
      if (window.MarkerClusterer && window.markerClusterer) {
        if (markerClusterer[id]) {
          markerClusterer[id].clearMarkers();
        }

        markerClusterer[id] = new markerClusterer.MarkerClusterer({
          map: maps[id],
          markers: markers[id],
        });
      } else {
        // Set markers on map if no clustering
        markers[id].forEach((marker) => marker.setMap(maps[id]));
      }

      // Fit map to bounds with auto-zoom
      if (facilities.length === 1) {
        maps[id].setCenter(bounds.getCenter());
        maps[id].setZoom(15);
      } else if (facilities.length > 1) {
        maps[id].fitBounds(bounds);

        // Ensure minimum zoom level
        google.maps.event.addListenerOnce(maps[id], 'bounds_changed', function () {
          if (maps[id].getZoom() > 15) {
            maps[id].setZoom(15);
          }
        });
      }
    } catch (error) {
      console.error('Google Maps error:', error);
      $mapContainer.html(`
      <div style="padding: 40px; text-align: center; background: #fee2e2; border-radius: 8px;">
        <h3 style="color: #dc2626; margin: 0 0 8px 0;">Map Loading Error</h3>
        <p style="color: #991b1b; margin: 0;">Unable to load Google Maps. Please check your API key and internet connection.</p>
      </div>
    `);
    }
  };

  /**
   * Clear markers
   */
  const clearMarkers = (id) => {
    if (markerClusterer[id]) {
      markerClusterer[id].clearMarkers();
    }

    if (markers[id]) {
      markers[id].forEach((marker) => marker.setMap(null));
      markers[id] = [];
    }

    if (infoWindows[id]) {
      infoWindows[id].forEach((window) => window.close());
      infoWindows[id] = [];
    }
  };

  /**
   * Highlight facility card
   */
  const highlightFacilityCard = (facilityId) => {
    $('.facility-card').removeClass('highlighted');
    $(`.facility-card[data-facility-id="${facilityId}"]`).addClass('highlighted');

    // Scroll to highlighted card
    const $card = $(`.facility-card[data-facility-id="${facilityId}"]`);
    if ($card.length) {
      const $container = $card.closest('.facility-cards-container');
      const cardTop = $card.position().top;
      const scrollTop = $container.scrollTop();
      $container.animate(
        {
          scrollTop: scrollTop + cardTop - 20,
        },
        300
      );
    }
  };

  /**
   * Highlight map pin
   */
  const highlightMapPin = (id, facilityId) => {
    // Find the facility in our data
    const facility = facilitiesData[id].find((f) => f.id == facilityId);
    if (!facility) return;

    // Find corresponding marker and trigger click
    const facilityIndex = facilitiesData[id].findIndex((f) => f.id == facilityId);
    if (markers[id] && markers[id][facilityIndex]) {
      google.maps.event.trigger(markers[id][facilityIndex], 'click');
    }
  };

  /**
   * Clear highlighted pin
   */
  const clearHighlightedPin = () => {
    // Close all info windows
    Object.values(infoWindows).forEach((windowArray) => {
      windowArray.forEach((window) => window.close());
    });
  };

  /**
   * Show facility details with image gallery support
   */
  const showFacilityDetails = ($container, facilityId) => {
    const id = $container.attr('id');
    const facility = facilitiesData[id].find((f) => f.id == facilityId);
    if (!facility) return;

    currentFacilityId = facilityId;

    let $modal = $container.find('.facility-detail-modal');
    if ($modal.length === 0) {
      $modal = $(`
        <div class="facility-detail-modal">
          <div class="facility-detail-content">
            <button class="facility-detail-close">&times;</button>
            <div class="facility-detail-body"></div>
          </div>
        </div>
      `);
      $container.append($modal);
    }

    const $body = $modal.find('.facility-detail-body');

    // Create detailed content with image gallery
    const detailContent = createDetailedFacilityContent(facility);
    $body.html(detailContent);

    // Show modal
    $modal.addClass('open');
  };

  /**
   * Create detailed facility content with image gallery
   */
  const createDetailedFacilityContent = (facility) => {
    const images = facility.images || [];
    const hasImages = images.length > 0;

    return `
      <div class="facility-detail-header">
        <h2>${facility.name}</h2>
        <div class="facility-detail-type">${facility.levels_of_care_names?.[0] || 'Facility'}</div>
      </div>
      
      ${
        hasImages
          ? createImageCarousel(images, 5)
          : '<div class="facility-image-carousel"><div class="carousel-placeholder">No images available</div></div>'
      }
      
      <div class="facility-detail-info">
        <div class="facility-detail-section">
          <h4>Contact Information</h4>
          <p><strong>Address:</strong> ${facility.address}</p>
          ${
            facility.phone ? `<p><strong>Phone:</strong> <a href="tel:${facility.phone}">${facility.phone}</a></p>` : ''
          }
          ${
            facility.website
              ? `<p><strong>Website:</strong> <a href="${facility.website}" target="_blank">${facility.website}</a></p>`
              : ''
          }
        </div>
        
        ${
          facility.description
            ? `
          <div class="facility-detail-section">
            <h4>About</h4>
            <div class="facility-description">${facility.description}</div>
          </div>
        `
            : ''
        }
        
        ${createDetailedFeaturesSections(facility)}
        
        <div class="facility-detail-actions">
          ${
            facility.phone
              ? `<a href="tel:${facility.phone}" class="facility-action-btn primary">Call ${facility.phone}</a>`
              : ''
          }
          ${
            facility.website
              ? `<a href="${facility.website}" target="_blank" class="facility-action-btn">Visit Website</a>`
              : ''
          }
          <a href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(
            facility.address
          )}" target="_blank" class="facility-action-btn">Get Directions</a>
        </div>
      </div>
    `;
  };

  /**
   * Create detailed features sections
   */
  const createDetailedFeaturesSections = (facility) => {
    let sections = '';

    Object.keys(facilityLocator.availableTaxonomies).forEach((taxonomyType) => {
      const names = facility[`${taxonomyType}_names`];
      if (names && names.length > 0) {
        const taxonomyDisplayName = facilityLocator.availableTaxonomies[taxonomyType].label;
        sections += `
          <div class="facility-detail-section">
            <h4>${taxonomyDisplayName}</h4>
            <div class="feature-tags">
              ${names.map((name) => `<span class="feature-tag">${name}</span>`).join('')}
            </div>
          </div>
        `;
      }
    });

    return sections;
  };

  /**
   * Update filters with AND logic
   */
  const updateFilters = ($container, id) => {
    // Collect selected filters
    const filters = {};

    $container
      .find(
        '.filter-option input[type="checkbox"]:checked, .mobile-filter-drawer .filter-option input[type="checkbox"]:checked'
      )
      .each(function () {
        const taxonomyType = $(this).data('taxonomy');
        const value = $(this).val();

        if (!filters[taxonomyType]) {
          filters[taxonomyType] = [];
        }
        if (filters[taxonomyType].indexOf(value) === -1) {
          filters[taxonomyType].push(value);
        }
      });

    activeFilters[id] = filters;

    // Update filter button states
    updateFilterButtonStates($container, id);

    // Fetch updated facilities
    fetchFacilities($container, id);
  };

  /**
   * Update filter button states
   */
  const updateFilterButtonStates = ($container, id) => {
    $container.find('.filter-dropdown').each(function () {
      const $dropdown = $(this);
      const taxonomyType = $dropdown.data('taxonomy');
      const selectedCount = getSelectedFilterCount(id, taxonomyType);
      const taxonomyDisplayName = facilityLocator.availableTaxonomies[taxonomyType]?.label || taxonomyType;
      const buttonText = selectedCount > 0 ? `${taxonomyDisplayName} (${selectedCount})` : taxonomyDisplayName;

      const $button = $dropdown.find('.filter-dropdown-button');
      $button.find('span').text(buttonText);
      $button.toggleClass('active', selectedCount > 0);
    });
  };

  /**
   * Get selected filter count
   */
  const getSelectedFilterCount = (id, taxonomyType) => {
    return activeFilters[id] && activeFilters[id][taxonomyType] ? activeFilters[id][taxonomyType].length : 0;
  };

  /**
   * Check if filter is selected
   */
  const isFilterSelected = (id, taxonomyType, itemId) => {
    return (
      activeFilters[id] &&
      activeFilters[id][taxonomyType] &&
      activeFilters[id][taxonomyType].includes(itemId.toString())
    );
  };

  /**
   * Clear all filters
   */
  const clearAllFilters = ($container, id) => {
    activeFilters[id] = {};
    $container
      .find('.filter-option input[type="checkbox"], .mobile-filter-drawer .filter-option input[type="checkbox"]')
      .prop('checked', false);
    updateFilterButtonStates($container, id);
    fetchFacilities($container, id);
  };

  // Initialize when document is ready
  $(document).ready(init);
})(jQuery);
