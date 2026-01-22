/**
 * Real-time Notification Synchronization System
 * This script handles real-time updates of user notifications across all pages
 * when admin makes changes in the notification management system
 */

class NotificationSync {
    constructor() {
        this.storageKey = 'farmfresh_notifications';
        this.triggerKey = 'farmfresh_notifications_trigger';
        this.currentPage = this.detectCurrentPage();
        this.lastUpdateTime = 0;
        
        this.init();
    }

    init() {
        // Listen for storage changes (cross-tab communication)
        window.addEventListener('storage', (e) => {
            if (e.key === this.triggerKey) {
                this.handleNotificationUpdate();
            }
        });

        // Listen for custom events (same-page communication)
        window.addEventListener('farmfresh_notifications_updated', (e) => {
            this.handleNotificationUpdate(e.detail);
        });

        // Initial load
        this.loadAndRenderNotifications();

        // Periodic check for updates (fallback)
        setInterval(() => {
            this.checkForUpdates();
        }, 5000); // Check every 5 seconds

        console.log(`🔄 Notification sync initialized for page: ${this.currentPage}`);
    }

    detectCurrentPage() {
        const pathname = window.location.pathname;
        const filename = pathname.split('/').pop().toLowerCase();
        
        if (filename.includes('admin-dashboard')) return 'admin';
        if (filename.includes('price-dashboard') || filename.includes('pricefront')) return 'dashboard';
        if (filename.includes('landing')) return 'landing';
        if (filename.includes('fish') || filename.includes('meat') || filename.includes('vegetables') || filename.includes('fruits') || filename.includes('rice') || filename.includes('other')) return 'products';
        
        return 'all';
    }

    handleNotificationUpdate(detail = null) {
        // Prevent duplicate updates
        const currentTime = Date.now();
        if (currentTime - this.lastUpdateTime < 1000) return;
        
        this.lastUpdateTime = currentTime;
        this.loadAndRenderNotifications();
    }

    checkForUpdates() {
        // Check if notifications were updated recently
        const triggerTime = localStorage.getItem(this.triggerKey);
        if (triggerTime && parseInt(triggerTime) > this.lastUpdateTime) {
            this.handleNotificationUpdate();
        }
    }

    loadAndRenderNotifications() {
        const notifications = this.getNotificationsForCurrentPage();
        this.renderNotifications(notifications);
        this.updateNotificationBadge(notifications.length);
    }

    getNotificationsForCurrentPage() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            if (!stored) return [];
            
            const allNotifications = JSON.parse(stored);
            return allNotifications.filter(notification => 
                notification.status === 'active' && 
                (notification.targetPages.includes('all') || 
                 notification.targetPages.includes(this.currentPage) ||
                 (this.currentPage === 'products' && notification.targetPages.includes('products')))
            );
        } catch (error) {
            console.warn('Error loading notifications:', error);
            return [];
        }
    }

    renderNotifications(notifications) {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;

        // Find notification items container or create structure
        let container = dropdown;
        const header = dropdown.querySelector('.notification-header');
        
        // Remove existing notification items (but keep header)
        const existingItems = dropdown.querySelectorAll('.notification-item');
        existingItems.forEach(item => item.remove());

        // Add new notifications
        if (notifications.length === 0) {
            this.addEmptyState(container, header);
        } else {
            notifications.forEach(notification => {
                const notificationElement = this.createNotificationElement(notification);
                if (header) {
                    header.insertAdjacentHTML('afterend', notificationElement);
                } else {
                    container.insertAdjacentHTML('beforeend', notificationElement);
                }
            });
        }

        // Update header if it exists
        if (header) {
            const headerTitle = header.querySelector('h4');
            if (headerTitle) {
                headerTitle.textContent = notifications.length > 0 ? 'Market Price Alerts' : 'No New Notifications';
            }
        }
    }

    createNotificationElement(notification) {
        const iconClass = this.getIconClass(notification);
        
        return `
            <div class="notification-item" data-notification-id="${notification.id}" data-priority="${notification.priority}">
                <i class="${iconClass}"></i>
                <div class="notification-content">
                    <p><strong>${this.escapeHtml(notification.title)}</strong></p>
                    <small>${this.escapeHtml(notification.message)}</small>
                    ${notification.priority === 'urgent' ? '<span class="urgent-indicator">URGENT</span>' : ''}
                </div>
                ${this.shouldShowTimestamp(notification) ? `<small class="notification-time">${this.formatTime(notification.createdDate)}</small>` : ''}
            </div>
        `;
    }

    getIconClass(notification) {
        // Map notification types to appropriate icon classes
        const iconMap = {
            'price-alert': notification.icon || 'fas fa-arrow-down price-drop',
            'market-update': notification.icon || 'fas fa-bell price-alert',
            'promotion': notification.icon || 'fas fa-tag promotion',
            'system': notification.icon || 'fas fa-info-circle system-info',
            'maintenance': notification.icon || 'fas fa-exclamation-triangle maintenance-alert'
        };
        
        return iconMap[notification.type] || notification.icon || 'fas fa-bell';
    }

    addEmptyState(container, header) {
        const emptyState = `
            <div class="notification-item notification-empty">
                <i class="fas fa-check-circle" style="color: #4caf50;"></i>
                <div class="notification-content">
                    <p><strong>All caught up!</strong></p>
                    <small>No new notifications at the moment.</small>
                </div>
            </div>
        `;
        
        if (header) {
            header.insertAdjacentHTML('afterend', emptyState);
        } else {
            container.insertAdjacentHTML('beforeend', emptyState);
        }
    }

    updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
            
            // Add pulsing animation for new notifications
            if (count > 0) {
                badge.style.animation = 'pulse 2s infinite';
            } else {
                badge.style.animation = 'none';
            }
        }

        // Update document title with notification count (optional)
        if (count > 0 && !document.title.includes('(')) {
            document.title = `(${count}) ${document.title}`;
        } else if (count === 0 && document.title.includes('(')) {
            document.title = document.title.replace(/^\(\d+\)\s/, '');
        }
    }

    shouldShowTimestamp(notification) {
        // Show timestamp for urgent notifications or recent ones
        if (notification.priority === 'urgent') return true;
        
        const notificationTime = new Date(notification.createdDate);
        const now = new Date();
        const hoursDiff = (now - notificationTime) / (1000 * 60 * 60);
        
        return hoursDiff < 24; // Show for notifications less than 24 hours old
    }

    formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
        const diffMinutes = Math.floor(diffMs / (1000 * 60));

        if (diffMinutes < 1) return 'Just now';
        if (diffMinutes < 60) return `${diffMinutes}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        
        return date.toLocaleDateString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Public methods for manual refresh
    refresh() {
        this.loadAndRenderNotifications();
    }

    getNotificationCount() {
        return this.getNotificationsForCurrentPage().length;
    }

    // Method to mark notifications as read
    markAsRead(notificationIds = null) {
        try {
            const stored = localStorage.getItem(this.storageKey);
            if (!stored) return;
            
            const notifications = JSON.parse(stored);
            let updated = false;

            notifications.forEach(notification => {
                if (!notificationIds || notificationIds.includes(notification.id)) {
                    if (!notification.readBy) notification.readBy = [];
                    if (!notification.readBy.includes('user')) {
                        notification.readBy.push('user');
                        notification.readAt = new Date().toISOString();
                        updated = true;
                    }
                }
            });

            if (updated) {
                localStorage.setItem(this.storageKey, JSON.stringify(notifications));
                this.loadAndRenderNotifications();
            }
        } catch (error) {
            console.warn('Error marking notifications as read:', error);
        }
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're not on the admin dashboard (to avoid conflicts)
    if (!window.location.pathname.includes('admin-dashboard')) {
        window.notificationSync = new NotificationSync();
        
        // Add event listener for "Mark all as read" functionality
        const markReadBtn = document.querySelector('.notification-header .mark-read');
        if (markReadBtn) {
            markReadBtn.addEventListener('click', () => {
                window.notificationSync.markAsRead();
            });
        }

        // Add click handlers for individual notification items
        document.addEventListener('click', function(e) {
            const notificationItem = e.target.closest('.notification-item');
            if (notificationItem && notificationItem.dataset.notificationId) {
                // Mark individual notification as read when clicked
                window.notificationSync.markAsRead([notificationItem.dataset.notificationId]);
            }
        });
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationSync;
} else {
    window.NotificationSync = NotificationSync;
}