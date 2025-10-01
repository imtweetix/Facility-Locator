/**
 * Modern Admin JavaScript for Facility Locator Plugin
 * Refactored with ES6+ features and modular architecture
 */

import FacilityFormHandler from './modules/facility-form';
import FacilityListHandler from './modules/facility-list';
import FormBuilder from './modules/form-builder';
import TaxonomyManager from './modules/taxonomy-manager';
import SettingsManager from './modules/settings-manager';
import NotificationManager from './modules/notifications';

class FacilityLocatorAdmin {
  constructor() {
    this.handlers = new Map();
    this.config = window.facilityLocatorAdmin || {};
    this.init();
  }

  /**
   * Initialize the admin interface
   */
  init() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.setup());
    } else {
      this.setup();
    }
  }

  /**
   * Setup admin handlers based on current page
   */
  setup() {
    try {
      // Initialize notification system first
      this.notifications = new NotificationManager();

      // Determine current admin page and initialize appropriate handlers
      const currentPage = this.getCurrentPage();

      switch (currentPage) {
        case 'facility-form':
          this.initializeFacilityForm();
          break;
        case 'facility-list':
          this.initializeFacilityList();
          break;
        case 'form-builder':
          this.initializeFormBuilder();
          break;
        case 'taxonomy-manager':
          this.initializeTaxonomyManager();
          break;
        case 'settings':
          this.initializeSettings();
          break;
        default:
          this.initializeGlobalHandlers();
      }

      this.bindGlobalEvents();

    } catch (error) {
      console.error('Facility Locator Admin initialization failed:', error);
      this.notifications?.showError('Failed to initialize admin interface');
    }
  }

  /**
   * Determine current admin page
   * @returns {string} Page identifier
   */
  getCurrentPage() {
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');

    if (!page?.includes('facility-locator')) {
      return 'other';
    }

    // Check for specific subpages
    if (document.getElementById('facility-form')) {
      return 'facility-form';
    }

    if (document.querySelector('.wp-list-table.facilities')) {
      return 'facility-list';
    }

    if (document.getElementById('form-builder')) {
      return 'form-builder';
    }

    if (document.getElementById('taxonomy-manager')) {
      return 'taxonomy-manager';
    }

    if (document.getElementById('facility-locator-settings')) {
      return 'settings';
    }

    return 'other';
  }

  /**
   * Initialize facility form handler
   */
  initializeFacilityForm() {
    const formElement = document.getElementById('facility-form');
    if (formElement) {
      const handler = new FacilityFormHandler(formElement, this.config);
      this.handlers.set('facility-form', handler);
    }
  }

  /**
   * Initialize facility list handler
   */
  initializeFacilityList() {
    const listElement = document.querySelector('.wp-list-table.facilities');
    if (listElement) {
      const handler = new FacilityListHandler(listElement, this.config);
      this.handlers.set('facility-list', handler);
    }
  }

  /**
   * Initialize form builder
   */
  initializeFormBuilder() {
    const builderElement = document.getElementById('form-builder');
    if (builderElement) {
      const handler = new FormBuilder(builderElement, this.config);
      this.handlers.set('form-builder', handler);
    }
  }

  /**
   * Initialize taxonomy manager
   */
  initializeTaxonomyManager() {
    const managerElement = document.getElementById('taxonomy-manager');
    if (managerElement) {
      const handler = new TaxonomyManager(managerElement, this.config);
      this.handlers.set('taxonomy-manager', handler);
    }
  }

  /**
   * Initialize settings manager
   */
  initializeSettings() {
    const settingsElement = document.getElementById('facility-locator-settings');
    if (settingsElement) {
      const handler = new SettingsManager(settingsElement, this.config);
      this.handlers.set('settings', handler);
    }
  }

  /**
   * Initialize global handlers that work across all pages
   */
  initializeGlobalHandlers() {
    // Global AJAX error handling
    this.setupGlobalAjaxHandling();

    // Global form validation
    this.setupGlobalValidation();

    // Global keyboard shortcuts
    this.setupKeyboardShortcuts();
  }

  /**
   * Setup global AJAX handling
   */
  setupGlobalAjaxHandling() {
    // jQuery AJAX error handling
    if (window.jQuery) {
      jQuery(document).ajaxError((event, xhr, settings, error) => {
        if (settings.url?.includes('admin-ajax.php') &&
            settings.data?.includes('facility_locator')) {
          console.error('Facility Locator AJAX Error:', {
            url: settings.url,
            data: settings.data,
            error,
            response: xhr.responseText
          });

          this.notifications?.showError(
            'An error occurred while communicating with the server. Please try again.'
          );
        }
      });
    }

    // Fetch API error handling
    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
      try {
        const response = await originalFetch(...args);
        if (!response.ok && args[0]?.includes('admin-ajax.php')) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response;
      } catch (error) {
        console.error('Facility Locator Fetch Error:', error);
        this.notifications?.showError('Network error occurred. Please check your connection.');
        throw error;
      }
    };
  }

  /**
   * Setup global form validation
   */
  setupGlobalValidation() {
    document.addEventListener('submit', (event) => {
      const form = event.target;
      if (!form.classList.contains('facility-locator-form')) {
        return;
      }

      // Basic validation for required fields
      const requiredFields = form.querySelectorAll('[required]');
      let isValid = true;

      requiredFields.forEach(field => {
        if (!field.value.trim()) {
          this.markFieldInvalid(field, 'This field is required');
          isValid = false;
        } else {
          this.markFieldValid(field);
        }
      });

      // Email validation
      const emailFields = form.querySelectorAll('input[type="email"]');
      emailFields.forEach(field => {
        if (field.value && !this.isValidEmail(field.value)) {
          this.markFieldInvalid(field, 'Please enter a valid email address');
          isValid = false;
        }
      });

      if (!isValid) {
        event.preventDefault();
        this.notifications?.showError('Please correct the errors below');
      }
    });
  }

  /**
   * Setup keyboard shortcuts
   */
  setupKeyboardShortcuts() {
    document.addEventListener('keydown', (event) => {
      // Ctrl/Cmd + S to save forms
      if ((event.ctrlKey || event.metaKey) && event.key === 's') {
        const activeForm = document.querySelector('form.facility-locator-form:focus-within');
        if (activeForm) {
          event.preventDefault();
          const submitButton = activeForm.querySelector('input[type="submit"], button[type="submit"]');
          if (submitButton && !submitButton.disabled) {
            submitButton.click();
          }
        }
      }

      // Escape to close modals
      if (event.key === 'Escape') {
        const openModal = document.querySelector('.modal-overlay.open');
        if (openModal) {
          const closeButton = openModal.querySelector('.modal-close, .close-button');
          if (closeButton) {
            closeButton.click();
          }
        }
      }
    });
  }

  /**
   * Bind global events
   */
  bindGlobalEvents() {
    // Handle notices dismissal
    document.addEventListener('click', (event) => {
      if (event.target.classList.contains('notice-dismiss')) {
        const notice = event.target.closest('.notice');
        if (notice) {
          notice.style.transition = 'opacity 0.3s ease';
          notice.style.opacity = '0';
          setTimeout(() => notice.remove(), 300);
        }
      }
    });

    // Handle tab navigation
    document.addEventListener('click', (event) => {
      if (event.target.classList.contains('nav-tab')) {
        event.preventDefault();
        this.handleTabClick(event.target);
      }
    });

    // Handle collapsible sections
    document.addEventListener('click', (event) => {
      if (event.target.classList.contains('toggle-section')) {
        event.preventDefault();
        this.toggleSection(event.target);
      }
    });
  }

  /**
   * Handle tab navigation
   * @param {HTMLElement} tabElement
   */
  handleTabClick(tabElement) {
    const tabGroup = tabElement.closest('.nav-tab-wrapper');
    if (!tabGroup) return;

    // Remove active class from all tabs
    tabGroup.querySelectorAll('.nav-tab').forEach(tab => {
      tab.classList.remove('nav-tab-active');
    });

    // Add active class to clicked tab
    tabElement.classList.add('nav-tab-active');

    // Show corresponding tab content
    const targetId = tabElement.getAttribute('href')?.substring(1) ||
                    tabElement.dataset.tab;

    if (targetId) {
      document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
      });

      const targetContent = document.getElementById(targetId);
      if (targetContent) {
        targetContent.style.display = 'block';
      }
    }
  }

  /**
   * Toggle collapsible sections
   * @param {HTMLElement} toggleElement
   */
  toggleSection(toggleElement) {
    const section = toggleElement.closest('.collapsible-section');
    if (!section) return;

    const content = section.querySelector('.section-content');
    const icon = toggleElement.querySelector('.toggle-icon');

    if (content.style.display === 'none') {
      content.style.display = 'block';
      section.classList.add('open');
      if (icon) icon.textContent = '▼';
    } else {
      content.style.display = 'none';
      section.classList.remove('open');
      if (icon) icon.textContent = '▶';
    }
  }

  /**
   * Mark field as invalid
   * @param {HTMLElement} field
   * @param {string} message
   */
  markFieldInvalid(field, message) {
    field.classList.add('error');

    // Remove existing error message
    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) {
      existingError.remove();
    }

    // Add new error message
    const errorElement = document.createElement('div');
    errorElement.className = 'error-message';
    errorElement.textContent = message;
    field.parentNode.appendChild(errorElement);
  }

  /**
   * Mark field as valid
   * @param {HTMLElement} field
   */
  markFieldValid(field) {
    field.classList.remove('error');

    const errorMessage = field.parentNode.querySelector('.error-message');
    if (errorMessage) {
      errorMessage.remove();
    }
  }

  /**
   * Validate email address
   * @param {string} email
   * @returns {boolean}
   */
  isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  /**
   * Get handler instance
   * @param {string} name
   * @returns {Object|null}
   */
  getHandler(name) {
    return this.handlers.get(name) || null;
  }

  /**
   * Destroy all handlers and cleanup
   */
  destroy() {
    this.handlers.forEach(handler => {
      if (typeof handler.destroy === 'function') {
        handler.destroy();
      }
    });

    this.handlers.clear();

    if (this.notifications) {
      this.notifications.destroy();
    }
  }
}

// Initialize when script loads
const adminInstance = new FacilityLocatorAdmin();

// Export for global access
window.FacilityLocatorAdmin = adminInstance;

// Export the class for module usage
export default FacilityLocatorAdmin;