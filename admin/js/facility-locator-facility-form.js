/**
 * Optimized Google Maps integration for facility form
 * Pure jQuery with modern ES6 implementation
 */
(function ($) {
  'use strict';

  // Cache DOM elements and configuration
  const cache = {
    map: null,
    marker: null,
    geocoder: null,
    autocomplete: null,
    isInitialized: false,
    retryCount: 0,
    maxRetries: 3,
    elements: {
      $mapElement: null,
      $addressInput: null,
      $latInput: null,
      $lngInput: null,
      $manualLatInput: null,
      $manualLngInput: null,
    },
  };

  // Configuration
  const config = {
    mapOptions: {
      zoom: 15,
      mapTypeControl: true,
      streetViewControl: true,
      fullscreenControl: true,
      gestureHandling: 'cooperative',
    },
    markerOptions: {
      draggable: true,
      title: 'Drag to adjust location',
    },
    autocompleteOptions: {
      types: ['establishment', 'geocode'],
    },
  };

  /**
   * Initialize map functionality with performance optimizations
   */
  window.initFacilityMap = () => {
    if (cache.isInitialized) {
      console.log('Facility Map: Already initialized, skipping...');
      return;
    }

    console.log('Facility Map: Initializing...');

    try {
      // Cache DOM elements using jQuery
      cacheDOMElements();

      if (!cache.elements.$mapElement.length) {
        console.error('Facility Map: Map element not found');
        return;
      }

      // Get initial coordinates with fallback
      const initialCoords = getInitialCoordinates();

      // Initialize map with cached configuration
      initializeMap(initialCoords);

      // Initialize geocoder (cached)
      cache.geocoder = cache.geocoder || new google.maps.Geocoder();

      // Create marker with caching
      createMarker(initialCoords);

      // Setup autocomplete with debouncing
      setupAutocomplete();

      // Mark as initialized
      cache.isInitialized = true;
      cache.elements.$mapElement.addClass('gm-style');

      console.log('Facility Map: Initialization complete');
    } catch (error) {
      console.error('Facility Map: Initialization error:', error);
      handleMapError(error);
    }
  };

  /**
   * Cache DOM elements for performance using jQuery
   */
  const cacheDOMElements = () => {
    cache.elements.$mapElement = $('#facility-map');
    cache.elements.$addressInput = $('#facility-address');
    cache.elements.$latInput = $('#facility-lat');
    cache.elements.$lngInput = $('#facility-lng');
    cache.elements.$manualLatInput = $('#manual-lat');
    cache.elements.$manualLngInput = $('#manual-lng');
  };

  /**
   * Get initial coordinates with validation using jQuery
   */
  const getInitialCoordinates = () => {
    const lat = parseFloat(cache.elements.$latInput.val()) || 40.7128;
    const lng = parseFloat(cache.elements.$lngInput.val()) || -74.006;

    // Validate coordinates
    if (isValidCoordinate(lat, lng)) {
      return { lat, lng };
    }

    console.warn('Facility Map: Invalid coordinates, using defaults');
    return { lat: 40.7128, lng: -74.006 };
  };

  /**
   * Validate coordinate values
   */
  const isValidCoordinate = (lat, lng) => {
    return lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
  };

  /**
   * Initialize map with performance optimizations
   */
  const initializeMap = (coords) => {
    const mapOptions = {
      ...config.mapOptions,
      center: coords,
    };

    cache.map = new google.maps.Map(cache.elements.$mapElement[0], mapOptions);
  };

  /**
   * Create and configure marker with caching
   */
  const createMarker = (coords) => {
    cache.marker = new google.maps.Marker({
      ...config.markerOptions,
      position: coords,
      map: cache.map,
    });

    // Optimized drag event with debouncing
    let dragTimeout;
    cache.marker.addListener('dragend', (event) => {
      clearTimeout(dragTimeout);
      dragTimeout = setTimeout(() => {
        handleMarkerDrag(event);
      }, 150); // Debounce drag events
    });
  };

  /**
   * Handle marker drag with optimized updates using jQuery
   */
  const handleMarkerDrag = (event) => {
    const lat = event.latLng.lat();
    const lng = event.latLng.lng();

    // Update coordinate fields using jQuery
    updateCoordinateFields(lat, lng);

    // Throttled reverse geocoding
    throttledReverseGeocode(event.latLng);
  };

  /**
   * Throttled reverse geocoding to improve performance
   */
  const throttledReverseGeocode = throttle((latLng) => {
    if (!cache.geocoder) return;

    cache.geocoder.geocode({ location: latLng }, (results, status) => {
      if (status === 'OK' && results[0] && cache.elements.$addressInput.length) {
        cache.elements.$addressInput.val(results[0].formatted_address);
      }
    });
  }, 500);

  /**
   * Update coordinate fields efficiently using jQuery
   */
  const updateCoordinateFields = (lat, lng) => {
    const fields = [
      { $element: cache.elements.$latInput, value: lat },
      { $element: cache.elements.$lngInput, value: lng },
      { $element: cache.elements.$manualLatInput, value: lat },
      { $element: cache.elements.$manualLngInput, value: lng },
    ];

    fields.forEach(({ $element, value }) => {
      if ($element && $element.length) {
        $element.val(value);
      }
    });
  };

  /**
   * Setup autocomplete with performance optimizations
   */
  const setupAutocomplete = () => {
    if (!cache.elements.$addressInput.length) {
      console.error('Facility Map: Address input not found');
      return;
    }

    try {
      cache.autocomplete = new google.maps.places.Autocomplete(
        cache.elements.$addressInput[0],
        config.autocompleteOptions
      );

      // Optimized place changed listener
      cache.autocomplete.addListener('place_changed', handlePlaceChanged);
    } catch (error) {
      console.error('Facility Map: Autocomplete setup error:', error);
    }
  };

  /**
   * Handle place selection with error handling
   */
  const handlePlaceChanged = () => {
    try {
      const place = cache.autocomplete.getPlace();

      if (!place.geometry) {
        console.warn('Facility Map: No geometry data for selected place');
        return;
      }

      const location = place.geometry.location;

      // Update map and marker positions efficiently
      updateMapAndMarker(location);
      updateCoordinateFields(location.lat(), location.lng());
    } catch (error) {
      console.error('Facility Map: Place change error:', error);
    }
  };

  /**
   * Update map and marker positions efficiently
   */
  const updateMapAndMarker = (location) => {
    if (cache.map && cache.marker) {
      cache.map.setCenter(location);
      cache.marker.setPosition(location);
    }
  };

  /**
   * Manual coordinate update with validation using jQuery
   */
  window.updateCoordinatesFromManual = () => {
    const lat = parseFloat(cache.elements.$manualLatInput.val());
    const lng = parseFloat(cache.elements.$manualLngInput.val());

    if (!isValidCoordinate(lat, lng)) {
      console.warn('Facility Map: Invalid manual coordinates');
      return;
    }

    // Update hidden fields using jQuery
    cache.elements.$latInput.val(lat);
    cache.elements.$lngInput.val(lng);

    // Update map if available
    if (cache.map && cache.marker) {
      const newPos = { lat, lng };
      cache.map.setCenter(newPos);
      cache.marker.setPosition(newPos);
    }
  };

  /**
   * Handle map errors with fallback using jQuery
   */
  const handleMapError = (error) => {
    const $mapElement = cache.elements.$mapElement;
    if (!$mapElement.length) return;

    $mapElement.html(`
      <div style="padding: 20px; text-align: center; color: #d63638; border: 1px solid #ddd;">
        <strong>Map Loading Error</strong><br>
        ${error.message}<br>
        <small>You can still enter coordinates manually below.</small>
      </div>
    `);
  };

  /**
   * Throttle utility function for performance
   */
  const throttle = (func, wait) => {
    let timeout;
    return (...args) => {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  };

  /**
   * Initialize with fallback and retry logic
   */
  const initializeWithFallback = () => {
    if (window.google && window.google.maps) {
      window.initFacilityMap();
    } else if (cache.retryCount < cache.maxRetries) {
      cache.retryCount++;
      setTimeout(initializeWithFallback, 1000);
    } else {
      console.error('Facility Map: Google Maps failed to load after retries');
      handleMapError(new Error('Google Maps API failed to load'));
    }
  };

  /**
   * DOM ready initialization using jQuery
   */
  $(() => {
    // Initialize manual coordinate fields from hidden fields using jQuery
    const lat = $('#facility-lat').val();
    const lng = $('#facility-lng').val();

    if (lat) {
      $('#manual-lat').val(lat);
    }
    if (lng) {
      $('#manual-lng').val(lng);
    }

    // Start initialization with delay for better performance
    setTimeout(initializeWithFallback, 500);
  });
})(jQuery);
