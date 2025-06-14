/**
 * Enhanced Frontend JavaScript for Recovery.com + Google Maps style interface
 * FIXED: Form configuration respect and filter deselection
 */
(($) => {
  ('use strict');

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
        return;
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

    // CTA button click - show popup
    $ctaButton.on('click', (e) => {
      e.preventDefault();
      console.log('CTA button clicked, showing popup');

      $popup
        .css({
          display: 'block',
          'z-index': '99999',
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
   * FIXED: Initialize delegated event listeners with proper filter handling
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

    // IMPROVED: Single checkbox handler with debouncing
    let filterUpdateTimeout;
    $(document).on('change', '.filter-option input[type="checkbox"]', function (e) {
      const $checkbox = $(this);
      const $container = $checkbox.closest('.facility-locator-container');
      const id = $container.attr('id');

      console.log('Checkbox changed:', $checkbox.val(), 'Checked:', $checkbox.is(':checked'));

      // Clear any pending updates
      clearTimeout(filterUpdateTimeout);

      // Debounce the update to prevent multiple rapid calls
      filterUpdateTimeout = setTimeout(() => {
        updateFilters($container, id);
      }, 50); // Small delay to ensure DOM is updated
    });

    // FIXED: Filter option selection with immediate state handling
    $(document).on('click', '.filter-option input[type="checkbox"]', function (e) {
      // Let the checkbox toggle naturally
      const $checkbox = $(this);
      const $container = $checkbox.closest('.facility-locator-container');
      const id = $container.attr('id');

      console.log('Checkbox clicked:', $checkbox.val(), 'Checked:', $checkbox.is(':checked'));

      // Update filters immediately without delay
      updateFilters($container, id);
    });

    // FIXED: Also handle label clicks to ensure proper toggling
    $(document).on('click', '.filter-option label', function (e) {
      // Let the label naturally toggle its associated checkbox
      const $label = $(this);
      const forValue = $label.attr('for');
      const $checkbox = $(`#${forValue}`);

      if ($checkbox.length) {
        const $container = $checkbox.closest('.facility-locator-container');
        const id = $container.attr('id');

        // Small delay to let the checkbox state update from label click
        setTimeout(() => {
          console.log('Label clicked for:', $checkbox.val(), 'Checked:', $checkbox.is(':checked'));
          updateFilters($container, id);
        }, 10);
      }
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
   * Build form steps with better error handling
   */
  const buildFormSteps = ($container, id) => {
    const formSteps = facilityLocator.formSteps;
    const $stepsContainer = $container.find('.facility-locator-steps');

    // Clear any existing steps
    $stepsContainer.empty();

    // Check if formSteps is properly defined and is an array
    if (!formSteps || !Array.isArray(formSteps) || formSteps.length === 0) {
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

    // Validate each step before building
    const validSteps = formSteps.filter((step) => {
      return step && typeof step === 'object' && step.title;
    });

    if (validSteps.length === 0) {
      console.warn('Form steps: All steps are invalid, falling back to simple step');
      // Fall back to simple step
      const $simpleStep = $('<div>').addClass('facility-locator-step active').attr('data-step', 0);
      $simpleStep.append(`
        <h2>Find Facilities Near You</h2>
        <p style="margin: 20px 0; color: #6b7280; font-size: 16px; text-align: center;">
          Browse all available facilities or use our search filters to find exactly what you're looking for.
        </p>
      `);
      $stepsContainer.append($simpleStep);

      const $submitBtn = $container.find('.facility-locator-submit-btn');
      const $nextBtn = $container.find('.facility-locator-next-btn');
      $nextBtn.hide();
      $submitBtn.show().text('Browse Facilities');
      return;
    }

    // Build form steps normally
    validSteps.forEach((step, index) => {
      try {
        buildStep($stepsContainer, step, index);
      } catch (error) {
        console.error('Error building step', index, ':', error);
      }
    });

    // Ensure at least one step is active
    const $steps = $container.find('.facility-locator-step');
    if ($steps.length > 0) {
      $steps.first().addClass('active');
      updateNavButtons($container);
    }

    console.log('Form steps: Built', validSteps.length, 'valid steps out of', formSteps.length, 'total steps');
  };

  /**
   * Build a single step with columns
   */
  const buildStep = ($stepsContainer, step, index) => {
    const $step = $('<div>').addClass('facility-locator-step').attr('data-step', index);
    $step.append($('<h2>').text(`Step ${index + 1}: ${step.title}`));

    if (step.columns && step.columns.length > 0) {
      const $columnsContainer = $('<div>').addClass('form-columns-container');

      step.columns.forEach((column, columnIndex) => {
        $columnsContainer.append(buildColumn(column, index, columnIndex));
      });

      $step.append($columnsContainer);
    }

    $stepsContainer.append($step);
  };

  /**
   * FIXED: Build a form column with proper option handling (manual + taxonomy)
   */
  const buildColumn = (column, stepIndex, columnIndex) => {
    const $column = $('<div>').addClass('facility-locator-column');

    // Generate unique field name for radio buttons to prevent conflicts
    let fieldId;
    if (column.type === 'radio') {
      fieldId = column.taxonomy
        ? `${column.taxonomy}_step${stepIndex}_col${columnIndex}`
        : `column_${stepIndex}_${columnIndex}`;
    } else {
      fieldId = column.taxonomy || `column_${stepIndex}_${columnIndex}`;
    }

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
   * FIXED: Build radio field respecting manual options AND their order
   */
  const buildRadioField = (column, fieldId) => {
    const $container = $('<div>').addClass('facility-locator-field-options');

    let options = [];

    // FIXED: Prioritize manual options if they exist, maintain their order
    if (column.options && Array.isArray(column.options) && column.options.length > 0) {
      console.log('Using manual options for column:', column.header, column.options);
      options = column.options;
    } else if (column.taxonomy && facilityLocator.availableTaxonomies[column.taxonomy]) {
      console.log('Using taxonomy options for column:', column.header);
      options = facilityLocator.availableTaxonomies[column.taxonomy].items;
    }

    // Build options in the exact order they appear
    options.forEach((option) => {
      const optionValue = option.id || option.value;
      const optionLabel = option.name || option.label;
      const optionId = `${fieldId}_${optionValue}`;

      const $option = $('<div>').addClass('facility-locator-field-option');

      const $input = $('<input>').attr({
        type: 'radio',
        id: optionId,
        name: fieldId, // Each column gets unique name
        value: optionValue,
      });

      // Store taxonomy type as data attribute for proper filtering
      if (column.taxonomy) {
        $input.attr('data-taxonomy', column.taxonomy);
      }

      $option.append($input);
      $option.append($('<label>').attr('for', optionId).text(optionLabel));

      $container.append($option);
    });

    console.log('Built radio field with', options.length, 'options for', column.header);
    return $container;
  };

  /**
   * FIXED: Build checkbox field respecting manual options AND their order
   */
  const buildCheckboxField = (column, fieldId) => {
    const $container = $('<div>').addClass('facility-locator-field-options');

    let options = [];

    // FIXED: Prioritize manual options if they exist, maintain their order
    if (column.options && Array.isArray(column.options) && column.options.length > 0) {
      console.log('Using manual options for column:', column.header, column.options);
      options = column.options;
    } else if (column.taxonomy && facilityLocator.availableTaxonomies[column.taxonomy]) {
      console.log('Using taxonomy options for column:', column.header);
      options = facilityLocator.availableTaxonomies[column.taxonomy].items;
    }

    // Build options in the exact order they appear
    options.forEach((option) => {
      const optionValue = option.id || option.value;
      const optionLabel = option.name || option.label;
      const optionId = `${fieldId}_${optionValue}`;

      const $option = $('<div>').addClass('facility-locator-field-option');

      const $input = $('<input>').attr({
        type: 'checkbox',
        id: optionId,
        name: `${fieldId}[]`,
        value: optionValue,
      });

      // Store taxonomy type as data attribute for proper filtering
      if (column.taxonomy) {
        $input.attr('data-taxonomy', column.taxonomy);
      }

      $option.append($input);
      $option.append($('<label>').attr('for', optionId).text(optionLabel));

      $container.append($option);
    });

    console.log('Built checkbox field with', options.length, 'options for', column.header);
    return $container;
  };

  /**
   * FIXED: Build dropdown field respecting manual options AND their order
   */
  const buildDropdownField = (column, fieldId) => {
    const $select = $('<select>')
      .attr({
        id: fieldId,
        name: fieldId,
      })
      .addClass('facility-locator-select');

    // Store taxonomy type as data attribute for proper filtering
    if (column.taxonomy) {
      $select.attr('data-taxonomy', column.taxonomy);
    }

    $select.append(
      $('<option>')
        .val('')
        .text(`Select ${column.header || 'Option'}`)
    );

    let options = [];

    // FIXED: Prioritize manual options if they exist, maintain their order
    if (column.options && Array.isArray(column.options) && column.options.length > 0) {
      console.log('Using manual options for dropdown:', column.header, column.options);
      options = column.options;
    } else if (column.taxonomy && facilityLocator.availableTaxonomies[column.taxonomy]) {
      console.log('Using taxonomy options for dropdown:', column.header);
      options = facilityLocator.availableTaxonomies[column.taxonomy].items;
    }

    // Build options in the exact order they appear
    options.forEach((option) => {
      const optionValue = option.id || option.value;
      const optionLabel = option.name || option.label;

      $select.append($('<option>').val(optionValue).text(optionLabel));
    });

    console.log('Built dropdown field with', options.length, 'options for', column.header);
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
   * Submit form and process data by taxonomy type
   */
  const submitForm = ($container, id) => {
    console.log('Submitting form for container:', id);

    const $form = $container.find('form');
    const processedData = {};

    // Process form data by taxonomy type instead of field name
    $form.find('input:checked, select').each(function () {
      const $input = $(this);
      const taxonomyType = $input.data('taxonomy');
      const value = $input.val();

      // Only process if taxonomy type exists and value is not empty
      if (taxonomyType && value && value.trim() !== '') {
        if (!processedData[taxonomyType]) {
          processedData[taxonomyType] = [];
        }

        // Avoid duplicates
        if (processedData[taxonomyType].indexOf(value) === -1) {
          processedData[taxonomyType].push(value);
        }
      }
    });

    console.log('Form data processed by taxonomy:', processedData);

    // Close popup and show main interface
    const $popup = $container.find('.facility-locator-popup');
    $popup.fadeOut(300, () => {
      showMainInterface($container, id, processedData);
    });

    $('body').css('overflow', 'auto');
  };

  /**
   * Show all facilities without filtering
   */
  const showAllFacilities = ($container, id) => {
    console.log('Showing all facilities for container:', id);

    const $popup = $container.find('.facility-locator-popup');
    $popup.fadeOut(300, () => {
      showMainInterface($container, id, {});
    });

    $('body').css('overflow', 'auto');
  };

  /**
   * Show main interface with proper filter initialization
   */
  const showMainInterface = ($container, id, formData = {}) => {
    console.log('Showing main interface for container:', id);
    console.log('Form data received:', formData);

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

    console.log('Fetching facilities with filters:', activeFilters[id]);

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

          console.log('Facilities received:', response.data.facilities.length);

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
   * Render filter bar with pre-selected form choices
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

    console.log('Filter bar rendered with pre-selected choices');
  };

  /**
   * Render mobile filter drawer
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
      $cardsContainer.html(
        '<div class="no-results">No facilities found matching your criteria. Please adjust your search filters.</div>'
      );
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
        features.push(...names.slice(0, 3));
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
   * FIXED: Render Google Map with proper AdvancedMarkerElement implementation
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

      // FIXED: Import the AdvancedMarkerElement library
      if (!window.google.maps.marker) {
        await window.google.maps.importLibrary('marker');
      }

      if (!maps[id]) {
        // FIXED: Create map without styles when using mapId (styles controlled via Cloud Console)
        const mapOptions = {
          zoom: parseInt(facilityLocator.settings?.mapZoom) || 10,
          center: { lat: 40.7128, lng: -74.006 },
          mapTypeControl: true,
          scrollwheel: true,
          streetViewControl: false,
          fullscreenControl: true,
          // FIXED: Use mapId without styles to avoid warning
          mapId: 'DEMO_MAP_ID', // You can create a custom mapId in Google Cloud Console
        };

        // FIXED: Only add styles if no mapId is present
        // Since we're using mapId, styles should be configured in Google Cloud Console
        // If you want to use styles, remove the mapId and uncomment the styles below:
        /*
      if (!mapOptions.mapId) {
        mapOptions.styles = [
          {
            featureType: 'poi',
            elementType: 'labels',
            stylers: [{ visibility: 'off' }],
          },
        ];
      }
      */

        maps[id] = new google.maps.Map($mapContainer[0], mapOptions);
        markers[id] = [];
        infoWindows[id] = [];
      }

      // Clear existing markers and clusterer
      clearMarkers(id);

      if (!facilities || facilities.length === 0) {
        return;
      }

      const bounds = new google.maps.LatLngBounds();

      // Create markers with FIXED AdvancedMarkerElement
      facilities.forEach((facility, index) => {
        const position = {
          lat: parseFloat(facility.lat),
          lng: parseFloat(facility.lng),
        };

        // FIXED: Create pin element for AdvancedMarkerElement
        const pinElement = createPinElement(facility);

        // FIXED: Use AdvancedMarkerElement with proper constructor
        const marker = new google.maps.marker.AdvancedMarkerElement({
          position,
          map: maps[id],
          title: facility.name,
          content: pinElement,
        });

        // Create info window
        const infoWindow = new google.maps.InfoWindow({
          content: createInfoWindowContent(facility),
        });

        // FIXED: Use proper event listener for AdvancedMarkerElement
        marker.addListener('gmp-click', () => {
          // Close all info windows
          infoWindows[id].forEach((window) => window.close());

          // Open this info window at marker position
          infoWindow.open(maps[id], marker);

          // Highlight facility card and show details
          highlightFacilityCard(facility.id);
          showFacilityDetails($container, facility.id);
        });

        markers[id].push(marker);
        infoWindows[id].push(infoWindow);
        bounds.extend(position);
      });

      // FIXED: Updated clustering - check if MarkerClusterer is available and use correct constructor
      if (typeof MarkerClusterer !== 'undefined') {
        if (markerClusterer[id]) {
          markerClusterer[id].clearMarkers();
        }

        try {
          // FIXED: Use correct MarkerClusterer constructor
          markerClusterer[id] = new MarkerClusterer({
            map: maps[id],
            markers: markers[id],
          });
        } catch (clusterError) {
          console.log('Marker clustering failed, using individual markers:', clusterError.message);
          // Markers are already added to map via AdvancedMarkerElement constructor
        }
      } else {
        console.log('MarkerClusterer not available, using individual markers');
        // Markers are already added to map via AdvancedMarkerElement constructor
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

      console.log('Map rendered successfully with AdvancedMarkerElement');
    } catch (error) {
      console.error('Google Maps error:', error);

      // FALLBACK: Use simple map without clustering if AdvancedMarkerElement fails
      console.warn('Falling back to simple map due to error:', error.message);
      renderSimpleMap($container, id, facilities);
    }
  };

  /**
   * FIXED: Create pin element for AdvancedMarkerElement with proper styling
   */
  const createPinElement = (facility) => {
    const defaultPinImage = facilityLocator.settings?.defaultPinImage;

    // Create container element
    const pinElement = document.createElement('div');
    pinElement.className = 'custom-marker';
    pinElement.style.cssText = `
    width: 32px;
    height: 40px;
    position: relative;
    cursor: pointer;
    transform-origin: bottom center;
    transition: transform 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
  `;

    // FIXED: Use proper hover events that work with AdvancedMarkerElement
    pinElement.addEventListener('mouseenter', () => {
      pinElement.style.transform = 'scale(1.1)';
      pinElement.style.zIndex = '1000';
    });

    pinElement.addEventListener('mouseleave', () => {
      pinElement.style.transform = 'scale(1)';
      pinElement.style.zIndex = 'auto';
    });

    // Determine pin image
    let pinImageUrl = null;
    if (facility.custom_pin_image && facility.custom_pin_image.trim() !== '') {
      pinImageUrl = facility.custom_pin_image;
    } else if (defaultPinImage && defaultPinImage.trim() !== '') {
      pinImageUrl = defaultPinImage;
    }

    if (pinImageUrl) {
      // Use custom image
      const imgElement = document.createElement('img');
      imgElement.src = pinImageUrl;
      imgElement.style.cssText = `
      width: 32px;
      height: 40px;
      object-fit: contain;
      display: block;
    `;
      imgElement.alt = facility.name;

      // Handle image load errors
      imgElement.onerror = () => {
        console.warn('Failed to load custom pin image:', pinImageUrl);
        // Replace with default pin
        pinElement.innerHTML = '';
        pinElement.appendChild(createDefaultPin());
      };

      pinElement.appendChild(imgElement);
    } else {
      // Use default pin
      pinElement.appendChild(createDefaultPin());
    }

    return pinElement;
  };

  /**
   * FIXED: Create default pin element
   */
  const createDefaultPin = () => {
    const pinDiv = document.createElement('div');
    pinDiv.style.cssText = `
    width: 32px;
    height: 40px;
    background: #3b82f6;
    border-radius: 50% 50% 50% 0;
    transform: rotate(-45deg);
    position: relative;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    border: 3px solid white;
  `;

    // Add inner circle
    const innerCircle = document.createElement('div');
    innerCircle.style.cssText = `
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 12px;
    height: 12px;
    background: white;
    border-radius: 50%;
  `;

    pinDiv.appendChild(innerCircle);
    return pinDiv;
  };

  /**
   * FIXED: Create info window content
   */
  const createInfoWindowContent = (facility) => {
    return `
    <div style="padding: 12px; min-width: 200px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
      <h4 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #111827;">${facility.name}</h4>
      <p style="margin: 0 0 8px 0; color: #6b7280; font-size: 14px; line-height: 1.4;">${facility.address}</p>
      ${
        facility.phone
          ? `<p style="margin: 0 0 4px 0; font-size: 14px; color: #374151;"><strong>Phone:</strong> <a href="tel:${facility.phone}" style="color: #3b82f6; text-decoration: none;">${facility.phone}</a></p>`
          : ''
      }
      ${
        facility.website
          ? `<p style="margin: 0; font-size: 14px;"><a href="${facility.website}" target="_blank" style="color: #3b82f6; text-decoration: none; font-weight: 500;">Visit Website →</a></p>`
          : ''
      }
    </div>
  `;
  };

  /**
   * Get default pin SVG
   */
  const getDefaultPinSVG = () => {
    return `
    <svg width="32" height="40" viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <filter id="pin-shadow" x="-50%" y="-50%" width="200%" height="200%">
          <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="rgba(0,0,0,0.3)"/>
        </filter>
      </defs>
      <path d="M16 0C7.2 0 0 7.2 0 16c0 16 16 24 16 24s16-8 16-24c0-8.8-7.2-16-16-16z" 
            fill="#3b82f6" filter="url(#pin-shadow)"/>
      <circle cx="16" cy="16" r="8" fill="white"/>
      <circle cx="16" cy="16" r="4" fill="#3b82f6"/>
    </svg>
  `;
  };

  /**
   * FIXED: Clear markers for AdvancedMarkerElement
   */
  const clearMarkers = (id) => {
    if (markerClusterer[id]) {
      if (typeof markerClusterer[id].clearMarkers === 'function') {
        markerClusterer[id].clearMarkers();
      }
      markerClusterer[id] = null;
    }

    if (markers[id]) {
      markers[id].forEach((marker) => {
        // FIXED: AdvancedMarkerElement uses map property
        if (marker && marker.map) {
          marker.map = null;
        }
      });
      markers[id] = [];
    }

    if (infoWindows[id]) {
      infoWindows[id].forEach((window) => {
        if (window && typeof window.close === 'function') {
          window.close();
        }
      });
      infoWindows[id] = [];
    }
  };

  /**
   * SIMPLE MAP FALLBACK: Render basic map without clustering or advanced features
   */
  const renderSimpleMap = async ($container, id, facilities) => {
    const $mapContainer = $container.find(`#${id}-map`);

    try {
      if (!maps[id]) {
        // Create simple map without mapId or advanced features
        maps[id] = new google.maps.Map($mapContainer[0], {
          zoom: parseInt(facilityLocator.settings?.mapZoom) || 10,
          center: { lat: 40.7128, lng: -74.006 },
          mapTypeControl: true,
          scrollwheel: true,
          streetViewControl: false,
          fullscreenControl: true,
          // Basic styling without mapId
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

      // Clear existing markers
      if (markers[id]) {
        markers[id].forEach((marker) => {
          if (marker && typeof marker.setMap === 'function') {
            marker.setMap(null);
          }
        });
        markers[id] = [];
      }

      if (!facilities || facilities.length === 0) {
        return;
      }

      const bounds = new google.maps.LatLngBounds();

      // Try AdvancedMarkerElement first, fallback to legacy if needed
      let useAdvancedMarkers = true;
      try {
        if (!window.google.maps.marker) {
          await window.google.maps.importLibrary('marker');
        }
      } catch (error) {
        console.warn('AdvancedMarkerElement not available, using legacy markers');
        useAdvancedMarkers = false;
      }

      // Create markers
      facilities.forEach((facility, index) => {
        const position = {
          lat: parseFloat(facility.lat),
          lng: parseFloat(facility.lng),
        };

        let marker;

        if (useAdvancedMarkers) {
          // Use AdvancedMarkerElement
          const pinElement = createPinElement(facility);

          marker = new google.maps.marker.AdvancedMarkerElement({
            position,
            map: maps[id],
            title: facility.name,
            content: pinElement,
          });
        } else {
          // Fallback to legacy markers
          const defaultPinImage = facilityLocator.settings?.defaultPinImage;
          let pinIcon = null;

          if (facility.custom_pin_image && facility.custom_pin_image.trim() !== '') {
            pinIcon = {
              url: facility.custom_pin_image,
              scaledSize: new google.maps.Size(32, 40),
              anchor: new google.maps.Point(16, 40),
            };
          } else if (defaultPinImage && defaultPinImage.trim() !== '') {
            pinIcon = {
              url: defaultPinImage,
              scaledSize: new google.maps.Size(32, 40),
              anchor: new google.maps.Point(16, 40),
            };
          }

          marker = new google.maps.Marker({
            position,
            map: maps[id],
            title: facility.name,
            icon: pinIcon,
          });
        }

        // Create info window
        const infoWindow = new google.maps.InfoWindow({
          content: createInfoWindowContent(facility),
        });

        // Add click listener
        const eventType = useAdvancedMarkers ? 'gmp-click' : 'click';
        marker.addListener(eventType, () => {
          infoWindows[id].forEach((window) => window.close());
          infoWindow.open(maps[id], marker);
          highlightFacilityCard(facility.id);
          showFacilityDetails($container, facility.id);
        });

        markers[id].push(marker);
        infoWindows[id].push(infoWindow);
        bounds.extend(position);
      });

      // Fit map to bounds
      if (facilities.length === 1) {
        maps[id].setCenter(bounds.getCenter());
        maps[id].setZoom(15);
      } else if (facilities.length > 1) {
        maps[id].fitBounds(bounds);

        google.maps.event.addListenerOnce(maps[id], 'bounds_changed', function () {
          if (maps[id].getZoom() > 15) {
            maps[id].setZoom(15);
          }
        });
      }

      console.log('Simple map rendered successfully');
    } catch (error) {
      console.error('Simple map rendering failed:', error);
      $mapContainer.html(`
    <div style="padding: 40px; text-align: center; background: #fee2e2; border-radius: 8px;">
      <h3 style="color: #dc2626; margin: 0 0 8px 0;">Map Loading Error</h3>
      <p style="color: #991b1b; margin: 0;">Unable to load Google Maps. Please check your API key and internet connection.</p>
    </div>
  `);
    }
  };

  /**
   * LEGACY FALLBACK: Render map with old google.maps.Marker
   */
  const renderMapLegacy = async ($container, id, facilities) => {
    const $mapContainer = $container.find(`#${id}-map`);

    try {
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

      // Clear existing markers
      if (markers[id]) {
        markers[id].forEach((marker) => marker.setMap(null));
        markers[id] = [];
      }

      if (!facilities || facilities.length === 0) {
        return;
      }

      const bounds = new google.maps.LatLngBounds();
      const defaultPinImage = facilityLocator.settings?.defaultPinImage;

      // Create legacy markers
      facilities.forEach((facility, index) => {
        const position = {
          lat: parseFloat(facility.lat),
          lng: parseFloat(facility.lng),
        };

        // Determine pin icon for legacy marker
        let pinIcon = null;
        if (facility.custom_pin_image && facility.custom_pin_image.trim() !== '') {
          pinIcon = {
            url: facility.custom_pin_image,
            scaledSize: new google.maps.Size(32, 40),
            anchor: new google.maps.Point(16, 40),
          };
        } else if (defaultPinImage && defaultPinImage.trim() !== '') {
          pinIcon = {
            url: defaultPinImage,
            scaledSize: new google.maps.Size(32, 40),
            anchor: new google.maps.Point(16, 40),
          };
        }

        const marker = new google.maps.Marker({
          position,
          map: maps[id],
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
            facility.phone ? `<p style="margin: 0; font-size: 14px;"><strong>Phone:</strong> ${facility.phone}</p>` : ''
          }
        </div>
      `,
        });

        marker.addListener('click', () => {
          infoWindows[id].forEach((window) => window.close());
          infoWindow.open(maps[id], marker);
          highlightFacilityCard(facility.id);
          showFacilityDetails($container, facility.id);
        });

        markers[id].push(marker);
        infoWindows[id].push(infoWindow);
        bounds.extend(position);
      });

      // Fit map to bounds
      if (facilities.length === 1) {
        maps[id].setCenter(bounds.getCenter());
        maps[id].setZoom(15);
      } else if (facilities.length > 1) {
        maps[id].fitBounds(bounds);
      }

      console.warn('Using legacy google.maps.Marker - consider updating Google Maps API setup');
    } catch (error) {
      console.error('Legacy map rendering failed:', error);
      $mapContainer.html(`
    <div style="padding: 40px; text-align: center; background: #fee2e2; border-radius: 8px;">
      <h3 style="color: #dc2626; margin: 0 0 8px 0;">Map Loading Error</h3>
      <p style="color: #991b1b; margin: 0;">Unable to load Google Maps. Please check your API key and internet connection.</p>
    </div>
  `);
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
   * FIXED: Highlight map pin for AdvancedMarkerElement
   */
  const highlightMapPin = (id, facilityId) => {
    // Find the facility in our data
    const facility = facilitiesData[id].find((f) => f.id == facilityId);
    if (!facility) return;

    // Find corresponding marker and trigger click
    const facilityIndex = facilitiesData[id].findIndex((f) => f.id == facilityId);
    if (markers[id] && markers[id][facilityIndex]) {
      const marker = markers[id][facilityIndex];

      // FIXED: Use gmp-click event for AdvancedMarkerElement
      try {
        google.maps.event.trigger(marker, 'gmp-click');
      } catch (error) {
        console.warn('Could not trigger marker click:', error);
        // Fallback: just show facility details
        showFacilityDetails($(`#${id}`), facilityId);
      }
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
   * FIXED: Update filters with proper state detection and sync
   */
  const updateFilters = ($container, id) => {
    console.log('Updating filters for container:', id);

    // Get the current state of checkboxes from PRIMARY source only
    const newFilters = {};

    // Determine which filter area to use as primary source
    const isMobileView = window.innerWidth <= 768;
    const $mobileDrawer = $container.find('.mobile-filter-drawer');
    const isDrawerOpen = $mobileDrawer.hasClass('open');

    // Use mobile filters as source if mobile view OR if drawer is open
    const useMobileAsSource = (isMobileView && $mobileDrawer.length > 0) || isDrawerOpen;

    let $sourceCheckboxes;
    if (useMobileAsSource) {
      $sourceCheckboxes = $container.find('.mobile-filter-drawer .filter-option input[type="checkbox"]');
      console.log('Using mobile checkboxes as source');
    } else {
      $sourceCheckboxes = $container.find('.filter-dropdown .filter-option input[type="checkbox"]');
      console.log('Using desktop checkboxes as source');
    }

    // Collect from the primary source only
    $sourceCheckboxes.each(function () {
      const $checkbox = $(this);
      const taxonomyType = $checkbox.data('taxonomy');
      const value = $checkbox.val();
      const isChecked = $checkbox.is(':checked');

      if (taxonomyType && value) {
        if (!newFilters[taxonomyType]) {
          newFilters[taxonomyType] = [];
        }

        if (isChecked && !newFilters[taxonomyType].includes(value)) {
          newFilters[taxonomyType].push(value);
        }
      }
    });

    console.log('New filters collected from primary source:', newFilters);

    // Now sync to the secondary source
    let $secondaryCheckboxes;
    if (useMobileAsSource) {
      $secondaryCheckboxes = $container.find('.filter-dropdown .filter-option input[type="checkbox"]');
    } else {
      $secondaryCheckboxes = $container.find('.mobile-filter-drawer .filter-option input[type="checkbox"]');
    }

    // Sync secondary checkboxes without triggering events
    $secondaryCheckboxes.each(function () {
      const $checkbox = $(this);
      const taxonomyType = $checkbox.data('taxonomy');
      const value = $checkbox.val();
      const shouldBeChecked = newFilters[taxonomyType] && newFilters[taxonomyType].includes(value);

      // Only update if different to avoid unnecessary DOM changes
      if ($checkbox.is(':checked') !== shouldBeChecked) {
        $checkbox.prop('checked', shouldBeChecked);
      }
    });

    // Clean up empty arrays from filters
    Object.keys(newFilters).forEach((key) => {
      if (newFilters[key].length === 0) {
        delete newFilters[key];
      }
    });

    // Update active filters
    activeFilters[id] = newFilters;

    console.log('Active filters updated to:', activeFilters[id]);

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
   * Check if filter is selected - handle string/number ID comparison
   */
  const isFilterSelected = (id, taxonomyType, itemId) => {
    if (!activeFilters[id] || !activeFilters[id][taxonomyType]) {
      return false;
    }

    // Convert both to strings for comparison to handle ID type mismatches
    const selectedValues = activeFilters[id][taxonomyType].map((val) => String(val));
    return selectedValues.includes(String(itemId));
  };

  /**
   * FIXED: Clear all filters with proper state reset
   */
  const clearAllFilters = ($container, id) => {
    console.log('Clearing all filters for container:', id);

    // Reset active filters
    activeFilters[id] = {};

    // Uncheck all checkboxes in both desktop and mobile areas
    $container.find('.filter-option input[type="checkbox"]').prop('checked', false);
    $container.find('.mobile-filter-drawer .filter-option input[type="checkbox"]').prop('checked', false);

    // Update button states
    updateFilterButtonStates($container, id);

    // Fetch all facilities
    fetchFacilities($container, id);
  };

  // Initialize when document is ready
  $(document).ready(init);
})(jQuery);
