/**
 * Notification Manager for Admin Interface
 * Handles success, error, warning, and info notifications
 */

class NotificationManager {
  constructor() {
    this.container = null;
    this.notifications = new Map();
    this.init();
  }

  /**
   * Initialize notification system
   */
  init() {
    this.createContainer();
    this.bindEvents();
  }

  /**
   * Create notifications container
   */
  createContainer() {
    // Check if container already exists
    this.container = document.getElementById('facility-locator-notifications');

    if (!this.container) {
      this.container = document.createElement('div');
      this.container.id = 'facility-locator-notifications';
      this.container.className = 'facility-locator-notifications';

      // Insert after the page title or at the beginning of the content
      const titleElement = document.querySelector('.wrap h1, .wrap h2');
      if (titleElement) {
        titleElement.parentNode.insertBefore(this.container, titleElement.nextSibling);
      } else {
        const wrapElement = document.querySelector('.wrap');
        if (wrapElement) {
          wrapElement.insertBefore(this.container, wrapElement.firstChild);
        } else {
          document.body.appendChild(this.container);
        }
      }
    }
  }

  /**
   * Bind global events
   */
  bindEvents() {
    // Handle dismiss buttons
    document.addEventListener('click', (event) => {
      if (event.target.classList.contains('notification-dismiss')) {
        const notification = event.target.closest('.notification');
        if (notification) {
          this.dismiss(notification.dataset.id);
        }
      }
    });

    // Auto-dismiss success notifications
    document.addEventListener('animationend', (event) => {
      if (event.target.classList.contains('notification') &&
          event.animationName === 'slideIn') {
        const notification = event.target;
        if (notification.classList.contains('success')) {
          setTimeout(() => {
            this.dismiss(notification.dataset.id);
          }, 5000);
        }
      }
    });
  }

  /**
   * Show success notification
   * @param {string} message
   * @param {Object} options
   */
  showSuccess(message, options = {}) {
    return this.show(message, 'success', options);
  }

  /**
   * Show error notification
   * @param {string} message
   * @param {Object} options
   */
  showError(message, options = {}) {
    return this.show(message, 'error', { ...options, persistent: true });
  }

  /**
   * Show warning notification
   * @param {string} message
   * @param {Object} options
   */
  showWarning(message, options = {}) {
    return this.show(message, 'warning', options);
  }

  /**
   * Show info notification
   * @param {string} message
   * @param {Object} options
   */
  showInfo(message, options = {}) {
    return this.show(message, 'info', options);
  }

  /**
   * Show notification
   * @param {string} message
   * @param {string} type
   * @param {Object} options
   */
  show(message, type = 'info', options = {}) {
    const id = this.generateId();
    const notification = this.createNotification(id, message, type, options);

    this.notifications.set(id, notification);
    this.container.appendChild(notification.element);

    // Trigger animation
    requestAnimationFrame(() => {
      notification.element.classList.add('show');
    });

    // Auto-dismiss if not persistent
    if (!options.persistent && type !== 'error') {
      const duration = options.duration || (type === 'success' ? 5000 : 8000);
      setTimeout(() => {
        this.dismiss(id);
      }, duration);
    }

    return id;
  }

  /**
   * Create notification element
   * @param {string} id
   * @param {string} message
   * @param {string} type
   * @param {Object} options
   */
  createNotification(id, message, type, options) {
    const element = document.createElement('div');
    element.className = `notification notice notice-${type} ${type}`;
    element.dataset.id = id;

    const icon = this.getIcon(type);
    const dismissible = options.dismissible !== false;

    element.innerHTML = `
      <div class="notification-content">
        <span class="notification-icon">${icon}</span>
        <div class="notification-message">${this.escapeHtml(message)}</div>
        ${dismissible ? '<button type="button" class="notification-dismiss" aria-label="Dismiss notification">×</button>' : ''}
      </div>
    `;

    return {
      element,
      type,
      message,
      options
    };
  }

  /**
   * Get icon for notification type
   * @param {string} type
   */
  getIcon(type) {
    const icons = {
      success: '✓',
      error: '⚠',
      warning: '⚠',
      info: 'ℹ'
    };
    return icons[type] || icons.info;
  }

  /**
   * Dismiss notification
   * @param {string} id
   */
  dismiss(id) {
    const notification = this.notifications.get(id);
    if (!notification) return;

    notification.element.classList.add('dismissing');

    setTimeout(() => {
      if (notification.element.parentNode) {
        notification.element.parentNode.removeChild(notification.element);
      }
      this.notifications.delete(id);
    }, 300);
  }

  /**
   * Dismiss all notifications
   */
  dismissAll() {
    this.notifications.forEach((notification, id) => {
      this.dismiss(id);
    });
  }

  /**
   * Dismiss notifications by type
   * @param {string} type
   */
  dismissByType(type) {
    this.notifications.forEach((notification, id) => {
      if (notification.type === type) {
        this.dismiss(id);
      }
    });
  }

  /**
   * Update existing notification
   * @param {string} id
   * @param {string} message
   * @param {string} type
   */
  update(id, message, type = null) {
    const notification = this.notifications.get(id);
    if (!notification) return false;

    const messageElement = notification.element.querySelector('.notification-message');
    if (messageElement) {
      messageElement.textContent = message;
    }

    if (type && type !== notification.type) {
      notification.element.className = notification.element.className
        .replace(`notice-${notification.type}`, `notice-${type}`)
        .replace(notification.type, type);

      const iconElement = notification.element.querySelector('.notification-icon');
      if (iconElement) {
        iconElement.textContent = this.getIcon(type);
      }

      notification.type = type;
    }

    notification.message = message;
    return true;
  }

  /**
   * Check if notification exists
   * @param {string} id
   */
  exists(id) {
    return this.notifications.has(id);
  }

  /**
   * Get notification count by type
   * @param {string} type
   */
  getCount(type = null) {
    if (!type) {
      return this.notifications.size;
    }

    let count = 0;
    this.notifications.forEach(notification => {
      if (notification.type === type) {
        count++;
      }
    });

    return count;
  }

  /**
   * Generate unique notification ID
   */
  generateId() {
    return 'notification_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  }

  /**
   * Escape HTML in messages
   * @param {string} text
   */
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * Destroy notification manager
   */
  destroy() {
    this.dismissAll();

    if (this.container && this.container.parentNode) {
      this.container.parentNode.removeChild(this.container);
    }

    this.notifications.clear();
  }
}

export default NotificationManager;