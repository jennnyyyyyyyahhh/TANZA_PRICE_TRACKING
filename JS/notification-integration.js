/**
 * Notification Integration Script
 * This script demonstrates how to integrate the admin-managed notifications
 * into user-facing pages like price dashboard, landing page, etc.
 */

class NotificationIntegration {
    constructor() {
        // Sample notifications that would normally come from the admin dashboard
        this.notifications = [
            {
                id: 'N001',
                type: 'price-alert',
                title: 'Fresh Tomatoes - Tanza Market',
                message: 'Now ₱65/kg (was ₱85/kg) - Save 24% at Maria\'s Farm',
                icon: 'fas fa-arrow-down price-drop',
                priority: 'medium',
                status: 'active',
                targetPages: ['all'],
                createdDate: '2025-10-08'
            },
            {
                id: 'N002',
                type: 'price-alert',
                title: 'Local Rice - Farmer\'s Direct',
                message: 'Now ₱48/kg (was ₱55/kg) - Save 13% from Laguna Farms',
                icon: 'fas fa-arrow-down price-drop',
                priority: 'medium',
                status: 'active',
                targetPages: ['all'],
                createdDate: '2025-10-08'
            },
            {
                id: 'N003',
                type: 'market-update',
                title: 'Organic Apples - Limited Stock',
                message: '₱95/kg - Only 10kg left at Public Market',
                icon: 'fas fa-bell price-alert',
                priority: 'high',
                status: 'active',
                targetPages: ['all'],
                createdDate: '2025-10-07'
            },
            {
                id: 'N004',
                type: 'promotion',
                title: 'Weekend Market Special',
                message: 'Get 20% off on all fresh vegetables this weekend only!',
                icon: 'fas fa-tag promotion',
                priority: 'medium',
                status: 'active',
                targetPages: ['landing'],
                createdDate: '2025-10-09'
            }
        ];
    }

    // Method to get notifications for a specific page
    getNotificationsForPage(targetPage = 'all') {
        return this.notifications.filter(notification => 
            notification.status === 'active' && 
            (notification.targetPages.includes('all') || notification.targetPages.includes(targetPage))
        );
    }

    // Method to render notifications in existing dropdown structure
    renderNotifications(containerId = 'notificationDropdown', targetPage = 'all') {
        const container = document.getElementById(containerId);
        if (!container) {
            console.warn(`Notification container ${containerId} not found`);
            return;
        }

        const notifications = this.getNotificationsForPage(targetPage);
        
        // Update notification count badge
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            badge.textContent = notifications.length;
        }

        // Find or create notification items container
        let itemsContainer = container.querySelector('.notification-items');
        if (!itemsContainer) {
            // Look for existing notification items and replace them
            const existingItems = container.querySelectorAll('.notification-item');
            if (existingItems.length > 0) {
                // Remove existing items
                existingItems.forEach(item => item.remove());
            }
            
            // Add new items after header
            const header = container.querySelector('.notification-header');
            if (header) {
                notifications.forEach(notification => {
                    const item = this.createNotificationItem(notification);
                    header.insertAdjacentHTML('afterend', item);
                });
            }
        } else {
            // Replace content in items container
            itemsContainer.innerHTML = notifications.map(notification => 
                this.createNotificationItem(notification)
            ).join('');
        }
    }

    // Create HTML for a notification item
    createNotificationItem(notification) {
        return `
            <div class="notification-item" data-notification-id="${notification.id}">
                <i class="${notification.icon}"></i>
                <div class="notification-content">
                    <p><strong>${notification.title}</strong></p>
                    <small>${notification.message}</small>
                </div>
            </div>
        `;
    }

    // Method to initialize notifications on page load
    initialize(targetPage = 'all') {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.renderNotifications('notificationDropdown', targetPage);
            });
        } else {
            this.renderNotifications('notificationDropdown', targetPage);
        }

        console.log(`🔔 Notification Integration initialized for page: ${targetPage}`);
    }

    // Method to manually refresh notifications (for real-time updates)
    refreshNotifications(targetPage = 'all') {
        this.renderNotifications('notificationDropdown', targetPage);
    }

    // Method to add a new notification programmatically
    addNotification(notification) {
        this.notifications.unshift(notification);
        this.refreshNotifications();
    }

    // Method to remove a notification
    removeNotification(id) {
        this.notifications = this.notifications.filter(n => n.id !== id);
        this.refreshNotifications();
    }

    // Method to get notification count for badge
    getNotificationCount(targetPage = 'all') {
        return this.getNotificationsForPage(targetPage).length;
    }
}

// Example usage for different pages:

// For price dashboard:
// const notifications = new NotificationIntegration();
// notifications.initialize('dashboard');

// For landing page:
// const notifications = new NotificationIntegration();
// notifications.initialize('landing');

// For product pages:
// const notifications = new NotificationIntegration();
// notifications.initialize('products');

// For all pages (default):
// const notifications = new NotificationIntegration();
// notifications.initialize('all');

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationIntegration;
} else {
    window.NotificationIntegration = NotificationIntegration;
}