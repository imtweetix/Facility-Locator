/**
 * Optimized Google Maps integration for facility form
 * Cached and performance-focused implementation
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
      mapElement: null,
      addressInput: null,
      latInput: null,
      lngInput: null,
      manualLatInput: null,
      manualLngInput: null
    }
  };

  // Configuration
  const config = {
    mapOptions: {
      zoom: 15,
      mapTypeControl: true,
      streetViewControl: true,
      fullscreenControl: true,
      gestureHandling: 'cooperative'
    },
    markerOptions: {
      draggable: true,
      title: 'Drag to adjust location'
    },
    autocompleteOptions: {
      types: ['establishment', 'geocode']
    }
  };

  /**
   * Initialize map functionality with performance optimizations
   */
  window.initFacilityMap = function() {
    if (cache.isInitialized) {
      console.log('Facility Map: Already initialized, skipping...');
      return;
    }

    console.log('Facility Map: Initializing...');

    try {
      // Cache DOM elements
      cacheDOMElements();

      if (!cache.elements.mapElement) {
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
      cache.elements.mapElement.classList.add('gm-style');

      console.log('Facility Map: Initialization complete');

    } catch (error) {
      console.error('Facility Map: Initialization error:', error);
      handleMapError(error);
    }
  };

  /**
   * Cache DOM elements for performance
   */
  function cacheDOMElements() {
    cache.elements.mapElement = document.getElementById('facility-map');
    cache.elements.addressInput = document.getElementById('facility-address');
    cache.elements.latInput = document.getElementById('facility-lat');
    cache.elements.lngInput = document.getElementById('facility-lng');
    cache.elements.manualLatInput = document.getElementById('manual-lat');
    cache.elements.manualLngInput = document.getElementById('manual-lng');
  }

  /**
   * Get initial coordinates with validation
   */
  function getInitialCoordinates() {
    const lat = parseFloat(cache.elements.latInput?.value) || 40.7128;
    const lng = parseFloat(cache.elements.lngInput?.value) || -74.0060;

    // Validate coordinates
    if (isValidCoordinate(lat, lng)) {
      return { lat, lng };
    }

    console.warn('Facility Map: Invalid coordinates, using defaults');
    return { lat: 40.7128, lng: -74.0060 };
  }

  /**
   * Validate coordinate values
   */
  function isValidCoordinate(lat, lng) {
    return lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
  }

  /**
   * Initialize map with performance optimizations
   */
  function initializeMap(coords) {
    const mapOptions = {
      ...config.mapOptions,
      center: coords
    };

    cache.map = new google.maps.Map(cache.elements.mapElement, mapOptions);
  }

  /**
   * Create and configure marker with caching
   */
  function createMarker(coords) {
    cache.marker = new google.maps.Marker({
      ...config.markerOptions,
      position: coords,
      map: cache.map
    });

    // Optimized drag event with debouncing
    let dragTimeout;
    cache.marker.addListener('dragend', function(event) {
      clearTimeout(dragTimeout);
      dragTimeout = setTimeout(() => {
        handleMarkerDrag(event);
      }, 150); // Debounce drag events
    });
  }

  /**
   * Handle marker drag with optimized updates
   */
  function handleMarkerDrag(event) {
    const lat = event.latLng.lat();
    const lng = event.latLng.lng();

    // Batch DOM updates
    requestAnimationFrame(() => {
      updateCoordinateFields(lat, lng);
    });

    // Throttled reverse geocoding
    throttledReverseGeocode(event.latLng);
  }

  /**
   * Throttled reverse geocoding to improve performance
   */
  const throttledReverseGeocode = throttle(function(latLng) {
    if (!cache.geocoder) return;

    cache.geocoder.geocode({ location: latLng }, function(results, status) {
      if (status === 'OK' && results[0] && cache.elements.addressInput) {
        cache.elements.addressInput.value = results[0].formatted_address;
      }
    });
  }, 500);

  /**
   * Update coordinate fields efficiently
   */
  function updateCoordinateFields(lat, lng) {
    const fields = [
      { element: cache.elements.latInput, value: lat },
      { element: cache.elements.lngInput, value: lng },
      { element: cache.elements.manualLatInput, value: lat },
      { element: cache.elements.manualLngInput, value: lng }
    ];

    fields.forEach(({ element, value }) => {
      if (element) element.value = value;
    });
  }

  /**
   * Setup autocomplete with performance optimizations
   */
  function setupAutocomplete() {
    if (!cache.elements.addressInput) {
      console.error('Facility Map: Address input not found');
      return;
    }

    try {
      cache.autocomplete = new google.maps.places.Autocomplete(
        cache.elements.addressInput,
        config.autocompleteOptions
      );

      // Optimized place changed listener
      cache.autocomplete.addListener('place_changed', handlePlaceChanged);

    } catch (error) {
      console.error('Facility Map: Autocomplete setup error:', error);
    }
  }

  /**
   * Handle place selection with error handling
   */
  function handlePlaceChanged() {
    try {
      const place = cache.autocomplete.getPlace();

      if (!place.geometry) {
        console.warn('Facility Map: No geometry data for selected place');
        return;
      }

      const location = place.geometry.location;

      // Batch updates for better performance
      requestAnimationFrame(() => {
        updateMapAndMarker(location);
        updateCoordinateFields(location.lat(), location.lng());
      });

    } catch (error) {
      console.error('Facility Map: Place change error:', error);
    }
  }

  /**
   * Update map and marker positions efficiently
   */
  function updateMapAndMarker(location) {
    if (cache.map && cache.marker) {
      cache.map.setCenter(location);
      cache.marker.setPosition(location);
    }
  }

  /**
   * Manual coordinate update with validation
   */
  window.updateCoordinatesFromManual = function() {
    const lat = parseFloat(cache.elements.manualLatInput?.value);
    const lng = parseFloat(cache.elements.manualLngInput?.value);

    if (!isValidCoordinate(lat, lng)) {
      console.warn('Facility Map: Invalid manual coordinates');
      return;
    }

    // Update hidden fields
    if (cache.elements.latInput) cache.elements.latInput.value = lat;
    if (cache.elements.lngInput) cache.elements.lngInput.value = lng;

    // Update map if available
    if (cache.map && cache.marker) {
      const newPos = { lat, lng };
      cache.map.setCenter(newPos);
      cache.marker.setPosition(newPos);
    }
  };

  /**
   * Handle map errors with fallback
   */
  function handleMapError(error) {
    const mapElement = cache.elements.mapElement;
    if (!mapElement) return;

    mapElement.innerHTML = `
      <div style="padding: 20px; text-align: center; color: #d63638; border: 1px solid #ddd;">
        <strong>Map Loading Error</strong><br>
        ${error.message}<br>
        <small>You can still enter coordinates manually below.</small>
      </div>
    `;
  }

  /**
   * Throttle utility function for performance
   */
  function throttle(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  /**
   * Initialize with fallback and retry logic
   */
  function initializeWithFallback() {
    if (window.google && window.google.maps) {
      window.initFacilityMap();
    } else if (cache.retryCount < cache.maxRetries) {
      cache.retryCount++;
      setTimeout(initializeWithFallback, 1000);
    } else {
      console.error('Facility Map: Google Maps failed to load after retries');
      handleMapError(new Error('Google Maps API failed to load'));
    }
  }

  /**
   * DOM ready initialization
   */
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize manual coordinate fields from hidden fields
    const lat = document.getElementById('facility-lat')?.value;
    const lng = document.getElementById('facility-lng')?.value;
    
    if (lat && cache.elements.manualLatInput) cache.elements.manualLatInput.value = lat;
    if (lng && cache.elements.manualLngInput) cache.elements.manualLngInput.value = lng;

    // Start initialization with delay for better performance
    setTimeout(initializeWithFallback, 500);
  });

})(jQuery);