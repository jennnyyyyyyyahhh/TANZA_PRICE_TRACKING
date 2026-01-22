/**
 * Notification Management JavaScript
 * Handles CRUD operations for admin notification management
 */

class NotificationManager {
    constructor() {
        // Load notifications from localStorage or use defaults
        this.notifications = this.loadNotifications() || [
            {
                id: 'N001',
                type: 'price-alert',
                title: 'Fresh Tomatoes - Tanza Market',
                message: 'Now ₱65/kg (was ₱85/kg) - Save 24% at Maria\'s Farm',
                icon: 'fas fa-arrow-down',
                priority: 'medium',
                status: 'active',
                targetPages: ['all'],
                createdDate: '2025-10-08',
                expiryDate: null
            },
            {
                id: 'N002',
                type: 'price-alert',
                title: 'Local Rice - Farmer\'s Direct',
                message: 'Now ₱48/kg (was ₱55/kg) - Save 13% from Laguna Farms',
                icon: 'fas fa-arrow-down',
                priority: 'medium',
                status: 'active',
                targetPages: ['all'],
                createdDate: '2025-10-08',
                expiryDate: null
            },
            {
                id: 'N003',
                type: 'market-update',
                title: 'Organic Apples - Limited Stock',
                message: '₱95/kg - Only 10kg left at Public Market',
                icon: 'fas fa-bell',
                priority: 'high',
                status: 'active',
                targetPages: ['all'],
                createdDate: '2025-10-07',
                expiryDate: null
            },
            {
                id: 'N004',
                type: 'promotion',
                title: 'Weekend Market Special',
                message: 'Get 20% off on all fresh vegetables this weekend only!',
                icon: 'fas fa-tag',
                priority: 'medium',
                status: 'scheduled',
                targetPages: ['landing'],
                createdDate: '2025-10-09',
                scheduleDate: '2025-10-12',
                scheduleTime: '06:00',
                expiryDate: '2025-10-14'
            },
            {
                id: 'N005',
                type: 'system',
                title: 'Market Hours Update',
                message: 'New market hours: 5:00 AM - 8:00 PM daily starting next week',
                icon: 'fas fa-info-circle',
                priority: 'high',
                status: 'inactive',
                targetPages: ['all'],
                createdDate: '2025-10-05',
                expiryDate: null
            }
        ];
        
        this.currentEditingId = null;
        this.storageKey = 'farmfresh_notifications';
        this.init();
    }

    init() {
        this.bindEvents();
        this.renderNotifications();
        this.updateStatistics();
        this.saveNotifications(); // Save initial notifications to localStorage
    }

    // Load notifications from localStorage
    loadNotifications() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            return stored ? JSON.parse(stored) : null;
        } catch (error) {
            console.warn('Error loading notifications from localStorage:', error);
            return null;
        }
    }

    // Save notifications to localStorage
    saveNotifications() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.notifications));
            // Trigger custom event to notify other pages
            this.broadcastNotificationUpdate();
        } catch (error) {
            console.warn('Error saving notifications to localStorage:', error);
        }
    }

    // Broadcast notification updates to other pages
    broadcastNotificationUpdate() {
        // Use storage event to communicate between tabs/windows
        window.dispatchEvent(new CustomEvent('farmfresh_notifications_updated', {
            detail: {
                notifications: this.getActiveNotifications(),
                timestamp: Date.now()
            }
        }));

        // Also trigger storage event for cross-tab communication
        localStorage.setItem('farmfresh_notifications_trigger', Date.now().toString());
    }

    bindEvents() {
        // Navigation
        const navLink = document.querySelector('a[href="#notification-management"]');
        if (navLink) {
            navLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.showNotificationManagement();
            });
        }

        // Add new notification button
        const addBtn = document.getElementById('addNewNotificationBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showNotificationForm());
        }

        // Close form button
        const closeBtn = document.getElementById('closeNotificationForm');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hideNotificationForm());
        }

        // Cancel form button
        const cancelBtn = document.getElementById('cancelNotificationForm');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.hideNotificationForm());
        }

        // Form submission
        const form = document.getElementById('notificationForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // Status filter for scheduled notifications
        const statusSelect = document.getElementById('notificationStatus');
        if (statusSelect) {
            statusSelect.addEventListener('change', (e) => this.toggleScheduleSection(e.target.value));
        }

        // Target pages checkbox handling
        const targetAllCheckbox = document.getElementById('targetAll');
        if (targetAllCheckbox) {
            targetAllCheckbox.addEventListener('change', (e) => this.handleTargetAllChange(e));
        }

        // Form field changes for preview update
        const previewFields = ['notificationTitle', 'notificationMessage', 'notificationIcon'];
        previewFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', () => this.updatePreview());
            }
        });

        // Filters
        const typeFilter = document.getElementById('notificationTypeFilter');
        const statusFilter = document.getElementById('notificationStatusFilter');
        if (typeFilter) typeFilter.addEventListener('change', () => this.applyFilters());
        if (statusFilter) statusFilter.addEventListener('change', () => this.applyFilters());

        // Bulk actions
        const bulkActionSelect = document.getElementById('bulkNotificationActionSelect');
        const applyBulkBtn = document.getElementById('applyBulkNotificationAction');
        if (applyBulkBtn) {
            applyBulkBtn.addEventListener('click', () => this.applyBulkAction());
        }

        // Select all checkbox
        const selectAllCheckbox = document.getElementById('selectAllNotifications');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => this.handleSelectAll(e));
        }
    }

    showNotificationManagement() {
        // Hide all other sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });
        
        // Show notification management section
        const section = document.getElementById('notification-management-section');
        if (section) {
            section.classList.add('active');
        }

        // Update sidebar active state
        document.querySelectorAll('.sidebar .nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const navItem = document.querySelector('a[href="#notification-management"]')?.closest('.nav-item');
        if (navItem) {
            navItem.classList.add('active');
        }
    }

    renderNotifications() {
        const tbody = document.getElementById('notificationTableBody');
        if (!tbody) return;

        tbody.innerHTML = this.notifications.map(notification => `
            <tr data-notification-id="${notification.id}">
                <td><input type="checkbox" class="notification-select" value="${notification.id}"></td>
                <td>${notification.id}</td>
                <td><span class="notification-type ${notification.type}">${this.formatType(notification.type)}</span></td>
                <td>${notification.title}</td>
                <td>${this.truncateMessage(notification.message)}</td>
                <td>${this.formatTargetPages(notification.targetPages)}</td>
                <td><span class="status-badge ${notification.status}">${notification.status}</span></td>
                <td>${this.formatDate(notification.createdDate)}</td>
                <td>
                    <button class="btn-icon btn-edit" data-action="edit" data-id="${notification.id}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-toggle" data-action="toggle" data-id="${notification.id}" title="Toggle Status">
                        <i class="fas fa-power-off"></i>
                    </button>
                    <button class="btn-icon btn-delete" data-action="delete" data-id="${notification.id}" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        // Bind action buttons
        this.bindActionButtons();
    }

    bindActionButtons() {
        const actionButtons = document.querySelectorAll('[data-action]');
        actionButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = e.currentTarget.dataset.action;
                const id = e.currentTarget.dataset.id;
                
                switch(action) {
                    case 'edit':
                        this.editNotification(id);
                        break;
                    case 'toggle':
                        this.toggleNotificationStatus(id);
                        break;
                    case 'delete':
                        this.deleteNotification(id);
                        break;
                }
            });
        });
    }

    showNotificationForm(notification = null) {
        const container = document.getElementById('notificationFormContainer');
        const title = document.getElementById('notificationFormTitle');
        
        if (notification) {
            this.currentEditingId = notification.id;
            title.textContent = 'Edit Notification';
            this.populateForm(notification);
        } else {
            this.currentEditingId = null;
            title.textContent = 'Add New Notification';
            this.resetForm();
        }
        
        container.style.display = 'block';
        container.scrollIntoView({ behavior: 'smooth' });
    }

    hideNotificationForm() {
        const container = document.getElementById('notificationFormContainer');
        container.style.display = 'none';
        this.currentEditingId = null;
    }

    populateForm(notification) {
        document.getElementById('notificationId').value = notification.id;
        document.getElementById('notificationType').value = notification.type;
        document.getElementById('notificationStatus').value = notification.status;
        document.getElementById('notificationTitle').value = notification.title;
        document.getElementById('notificationMessage').value = notification.message;
        document.getElementById('notificationIcon').value = notification.icon;
        document.getElementById('notificationPriority').value = notification.priority;

        // Handle target pages
        document.querySelectorAll('input[name="targetPages"]').forEach(checkbox => {
            checkbox.checked = notification.targetPages.includes(checkbox.value);
        });

        // Handle schedule fields
        if (notification.scheduleDate) {
            document.getElementById('scheduleDate').value = notification.scheduleDate;
        }
        if (notification.scheduleTime) {
            document.getElementById('scheduleTime').value = notification.scheduleTime;
        }
        if (notification.expiryDate) {
            document.getElementById('expiryDate').value = notification.expiryDate;
        }

        this.toggleScheduleSection(notification.status);
        this.updatePreview();
    }

    resetForm() {
        const form = document.getElementById('notificationForm');
        form.reset();
        
        // Set defaults
        document.getElementById('notificationStatus').value = 'active';
        document.getElementById('notificationPriority').value = 'medium';
        document.getElementById('targetAll').checked = true;
        
        this.toggleScheduleSection('active');
        this.updatePreview();
    }

    handleFormSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const notificationData = {
            id: this.currentEditingId || this.generateId(),
            type: formData.get('notificationType'),
            title: formData.get('notificationTitle'),
            message: formData.get('notificationMessage'),
            icon: formData.get('notificationIcon'),
            priority: formData.get('notificationPriority'),
            status: formData.get('notificationStatus'),
            targetPages: formData.getAll('targetPages'),
            createdDate: this.currentEditingId ? 
                this.notifications.find(n => n.id === this.currentEditingId)?.createdDate : 
                new Date().toISOString().split('T')[0],
            scheduleDate: formData.get('scheduleDate') || null,
            scheduleTime: formData.get('scheduleTime') || null,
            expiryDate: formData.get('expiryDate') || null
        };

        if (this.currentEditingId) {
            this.updateNotification(notificationData);
        } else {
            this.addNotification(notificationData);
        }

        this.hideNotificationForm();
    }

    addNotification(data) {
        this.notifications.unshift(data);
        this.saveNotifications(); // Save to localStorage and broadcast
        this.renderNotifications();
        this.updateStatistics();
        this.showSuccessMessage('Notification added successfully!');
    }

    updateNotification(data) {
        const index = this.notifications.findIndex(n => n.id === data.id);
        if (index !== -1) {
            this.notifications[index] = data;
            this.saveNotifications(); // Save to localStorage and broadcast
            this.renderNotifications();
            this.updateStatistics();
            this.showSuccessMessage('Notification updated successfully!');
        }
    }

    editNotification(id) {
        const notification = this.notifications.find(n => n.id === id);
        if (notification) {
            this.showNotificationForm(notification);
        }
    }

    toggleNotificationStatus(id) {
        const notification = this.notifications.find(n => n.id === id);
        if (notification) {
            notification.status = notification.status === 'active' ? 'inactive' : 'active';
            this.saveNotifications(); // Save to localStorage and broadcast
            this.renderNotifications();
            this.updateStatistics();
            this.showSuccessMessage(`Notification ${notification.status === 'active' ? 'activated' : 'deactivated'}!`);
        }
    }

    deleteNotification(id) {
        if (confirm('Are you sure you want to delete this notification?')) {
            this.notifications = this.notifications.filter(n => n.id !== id);
            this.saveNotifications(); // Save to localStorage and broadcast
            this.renderNotifications();
            this.updateStatistics();
            this.showSuccessMessage('Notification deleted successfully!');
        }
    }

    generateId() {
        const maxId = Math.max(...this.notifications.map(n => parseInt(n.id.substring(1)))) || 0;
        return 'N' + String(maxId + 1).padStart(3, '0');
    }

    toggleScheduleSection(status) {
        const scheduleSection = document.getElementById('scheduleSection');
        if (scheduleSection) {
            scheduleSection.style.display = status === 'scheduled' ? 'flex' : 'none';
        }
    }

    handleTargetAllChange(e) {
        const otherCheckboxes = document.querySelectorAll('input[name="targetPages"]:not(#targetAll)');
        otherCheckboxes.forEach(checkbox => {
            checkbox.disabled = e.target.checked;
            if (e.target.checked) {
                checkbox.checked = false;
            }
        });
    }

    updatePreview() {
        const title = document.getElementById('notificationTitle').value || 'Notification Title';
        const message = document.getElementById('notificationMessage').value || 'Notification message will appear here...';
        const icon = document.getElementById('notificationIcon').value || 'fas fa-bell';

        document.getElementById('previewTitle').innerHTML = `<strong>${title}</strong>`;
        document.getElementById('previewMessage').textContent = message;
        document.getElementById('previewIcon').className = icon;
    }

    updateStatistics() {
        const stats = {
            total: this.notifications.length,
            active: this.notifications.filter(n => n.status === 'active').length,
            inactive: this.notifications.filter(n => n.status === 'inactive').length,
            scheduled: this.notifications.filter(n => n.status === 'scheduled').length
        };

        document.getElementById('totalNotifications').textContent = stats.total;
        document.getElementById('activeNotifications').textContent = stats.active;
        document.getElementById('inactiveNotifications').textContent = stats.inactive;
        document.getElementById('scheduledNotifications').textContent = stats.scheduled;
    }

    applyFilters() {
        const typeFilter = document.getElementById('notificationTypeFilter').value;
        const statusFilter = document.getElementById('notificationStatusFilter').value;
        
        const rows = document.querySelectorAll('#notificationTableBody tr');
        
        rows.forEach(row => {
            const notification = this.notifications.find(n => n.id === row.dataset.notificationId);
            if (!notification) return;
            
            const showType = typeFilter === 'all' || notification.type === typeFilter;
            const showStatus = statusFilter === 'all' || notification.status === statusFilter;
            
            row.style.display = showType && showStatus ? '' : 'none';
        });
    }

    handleSelectAll(e) {
        const checkboxes = document.querySelectorAll('.notification-select');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    }

    applyBulkAction() {
        const action = document.getElementById('bulkNotificationActionSelect').value;
        const selectedIds = Array.from(document.querySelectorAll('.notification-select:checked')).map(cb => cb.value);
        
        if (!action || selectedIds.length === 0) {
            this.showErrorMessage('Please select an action and at least one notification.');
            return;
        }

        switch(action) {
            case 'activate':
                this.bulkUpdateStatus(selectedIds, 'active');
                break;
            case 'deactivate':
                this.bulkUpdateStatus(selectedIds, 'inactive');
                break;
            case 'delete':
                this.bulkDelete(selectedIds);
                break;
            case 'export-selected':
                this.exportNotifications(selectedIds);
                break;
        }
    }

    bulkUpdateStatus(ids, status) {
        ids.forEach(id => {
            const notification = this.notifications.find(n => n.id === id);
            if (notification) {
                notification.status = status;
            }
        });
        
        this.saveNotifications(); // Save to localStorage and broadcast
        this.renderNotifications();
        this.updateStatistics();
        this.showSuccessMessage(`${ids.length} notification(s) ${status === 'active' ? 'activated' : 'deactivated'}!`);
    }

    bulkDelete(ids) {
        if (confirm(`Are you sure you want to delete ${ids.length} notification(s)?`)) {
            this.notifications = this.notifications.filter(n => !ids.includes(n.id));
            this.saveNotifications(); // Save to localStorage and broadcast
            this.renderNotifications();
            this.updateStatistics();
            this.showSuccessMessage(`${ids.length} notification(s) deleted!`);
        }
    }

    exportNotifications(ids = null) {
        const notificationsToExport = ids ? 
            this.notifications.filter(n => ids.includes(n.id)) : 
            this.notifications;
        
        const csv = this.convertToCSV(notificationsToExport);
        this.downloadCSV(csv, 'notifications.csv');
    }

    convertToCSV(notifications) {
        const headers = ['ID', 'Type', 'Title', 'Message', 'Status', 'Target Pages', 'Created Date'];
        const rows = notifications.map(n => [
            n.id,
            n.type,
            n.title,
            n.message,
            n.status,
            n.targetPages.join('; '),
            n.createdDate
        ]);
        
        return [headers, ...rows].map(row => 
            row.map(field => `"${field}"`).join(',')
        ).join('\n');
    }

    downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', filename);
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    // Utility methods
    formatType(type) {
        return type.split('-').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }

    truncateMessage(message, length = 50) {
        return message.length > length ? message.substring(0, length) + '...' : message;
    }

    formatTargetPages(pages) {
        if (pages.includes('all')) return 'All Pages';
        return pages.map(page => 
            page.charAt(0).toUpperCase() + page.slice(1)
        ).join(', ');
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    showSuccessMessage(message) {
        this.showMessage(message, 'success');
    }

    showErrorMessage(message) {
        this.showMessage(message, 'error');
    }

    showMessage(message, type = 'success') {
        // Create a simple toast notification
        const toast = document.createElement('div');
        toast.className = `notification-toast ${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#4caf50' : '#f44336'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        `;
        
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.transform = 'translateX(400px)';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    // Public method to get notifications for other pages
    getActiveNotifications(targetPage = 'all') {
        return this.notifications.filter(notification => 
            notification.status === 'active' && 
            (notification.targetPages.includes('all') || notification.targetPages.includes(targetPage))
        );
    }

    // Static method to get notifications from localStorage (for use by other pages)
    static getStoredNotifications(targetPage = 'all') {
        try {
            const stored = localStorage.getItem('farmfresh_notifications');
            if (!stored) return [];
            
            const notifications = JSON.parse(stored);
            return notifications.filter(notification => 
                notification.status === 'active' && 
                (notification.targetPages.includes('all') || notification.targetPages.includes(targetPage))
            );
        } catch (error) {
            console.warn('Error loading notifications:', error);
            return [];
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.notificationManager = new NotificationManager();
    console.log('🔔 Notification Management System initialized successfully!');
});