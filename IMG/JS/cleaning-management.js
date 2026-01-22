// Cleaning Management JavaScript for Admin Dashboard - Simplified Version
class CleaningManager {
    constructor() {
        this.cleaningRequests = this.loadCleaningRequests();
        this.initializeEventListeners();
        this.updateStats();
        this.renderTable();
    }

    // Load cleaning requests from localStorage or use sample data
    loadCleaningRequests() {
        const saved = localStorage.getItem('farmfresh_cleaning_requests');
        if (saved) {
            return JSON.parse(saved);
        }
        
        // Sample cleaning requests data
        return [
            {
                id: 'CR001',
                vendorName: 'Maria Santos',
                stallNumber: 'A-12',
                requestDate: '2025-10-04',
                scheduledTime: '9:00 AM',
                status: 'pending',
                description: 'Regular daily cleaning of produce stall and surrounding area',
                requestedBy: 'Maria Santos',
                contactNumber: '0915-555-1234',
                completedAt: null,
                notes: ''
            },
            {
                id: 'CR002',
                vendorName: 'John Rodriguez',
                stallNumber: 'B-05',
                requestDate: '2025-10-04',
                scheduledTime: '10:30 AM',
                status: 'pending',
                description: 'Deep cleaning of meat processing area, equipment sanitization',
                requestedBy: 'John Rodriguez',
                contactNumber: '0917-555-5678',
                completedAt: null,
                notes: ''
            },
            {
                id: 'CR003',
                vendorName: 'Elena Santos',
                stallNumber: 'C-08',
                requestDate: '2025-10-03',
                scheduledTime: '2:00 PM',
                status: 'completed',
                description: 'Emergency sanitization after seafood spillage incident',
                requestedBy: 'Elena Santos',
                contactNumber: '0918-555-9012',
                completedAt: '2025-10-03T15:45:00',
                notes: 'Completed successfully. All safety protocols followed.'
            },
            {
                id: 'CR004',
                vendorName: 'Robert Cruz',
                stallNumber: 'D-15',
                requestDate: '2025-10-04',
                scheduledTime: '1:00 PM',
                status: 'pending',
                description: 'Pest control treatment for reported rodent activity',
                requestedBy: 'Robert Cruz',
                contactNumber: '0919-555-3456',
                completedAt: null,
                notes: 'Urgent - requires immediate attention'
            },
            {
                id: 'CR005',
                vendorName: 'Ana Reyes',
                stallNumber: 'E-03',
                requestDate: '2025-10-04',
                scheduledTime: '3:30 PM',
                status: 'completed',
                description: 'Maintenance cleaning of cooking equipment and ventilation',
                requestedBy: 'Ana Reyes',
                contactNumber: '0920-555-7890',
                completedAt: '2025-10-04T16:00:00',
                notes: 'Completed on schedule'
            },
            {
                id: 'CR006',
                vendorName: 'Luis Mendoza',
                stallNumber: 'A-07',
                requestDate: '2025-10-04',
                scheduledTime: '11:00 AM',
                status: 'completed',
                description: 'Emergency cleaning due to produce contamination',
                requestedBy: 'Luis Mendoza',
                contactNumber: '0921-555-2468',
                completedAt: '2025-10-04T12:30:00',
                notes: 'Emergency response - contaminated produce removed'
            }
        ];
    }

    // Save cleaning requests to localStorage
    saveCleaningRequests() {
        localStorage.setItem('farmfresh_cleaning_requests', JSON.stringify(this.cleaningRequests));
    }

    // Initialize event listeners
    initializeEventListeners() {
        // Filter event listeners
        document.getElementById('cleaningStatusFilter')?.addEventListener('change', () => this.applyFilters());
        document.getElementById('requestDateFilter')?.addEventListener('change', () => this.applyFilters());

        // Global action buttons
        document.getElementById('exportCleaningReportsBtn')?.addEventListener('click', this.exportReports.bind(this));
        document.getElementById('cleaningScheduleBtn')?.addEventListener('click', this.showScheduleView.bind(this));

        // Listen for localStorage changes (new vendor requests)
        window.addEventListener('storage', (e) => {
            if (e.key === 'farmfresh_cleaning_requests') {
                this.refreshData();
            }
        });

        // Also check for updates periodically (in case of same-window updates)
        setInterval(() => {
            this.refreshData();
        }, 5000); // Check every 5 seconds
    }

    // Refresh data from localStorage
    refreshData() {
        const newRequests = this.loadCleaningRequests();
        if (JSON.stringify(newRequests) !== JSON.stringify(this.cleaningRequests)) {
            this.cleaningRequests = newRequests;
            this.renderTable();
            this.updateStats();
            this.showNotification('New cleaning request received!', 'info');
        }
    }

    // Apply filters to the table
    applyFilters() {
        const statusFilter = document.getElementById('cleaningStatusFilter')?.value || 'all';
        const dateFilter = document.getElementById('requestDateFilter')?.value || '';

        let filteredRequests = this.cleaningRequests;

        if (statusFilter !== 'all') {
            filteredRequests = filteredRequests.filter(req => req.status === statusFilter);
        }

        if (dateFilter) {
            filteredRequests = filteredRequests.filter(req => req.requestDate === dateFilter);
        }

        this.renderTable(filteredRequests);
        this.updateStats();
    }

    // Render the cleaning requests table
    renderTable(requests = this.cleaningRequests) {
        const tbody = document.getElementById('cleaningRequestsTableBody');
        if (!tbody) return;

        tbody.innerHTML = requests.map(request => this.renderTableRow(request)).join('');
    }

    // Render a single table row with responsive data labels
    renderTableRow(request) {
        const statusClass = request.status.replace('-', '');

        return `
            <tr data-request-id="${request.id}">
                <td data-label="Request ID">${request.id}</td>
                <td data-label="Vendor Name">${request.vendorName}</td>
                <td data-label="Stall Number">${request.stallNumber}</td>
                <td data-label="Request Date">${this.formatDate(request.requestDate)}</td>
                <td data-label="Scheduled Time">${request.scheduledTime}</td>
                <td data-label="Status"><span class="status-badge ${statusClass}">${this.getStatusLabel(request.status)}</span></td>
                <td data-label="Actions" class="action-buttons">
                    ${this.renderActionButtons(request)}
                </td>
            </tr>
        `;
    }

    // Render action buttons based on request status
    renderActionButtons(request) {
        if (request.status === 'pending') {
            return `
                <button class="btn-action btn-toggle" title="Mark Complete" onclick="cleaningManager.toggleCleaningStatus('${request.id}')">
                    <i class="fas fa-check"></i>
                </button>
            `;
        } else {
            return `
                <button class="btn-action btn-toggle" title="Mark Pending" onclick="cleaningManager.toggleCleaningStatus('${request.id}')">
                    <i class="fas fa-undo"></i>
                </button>
            `;
        }
    }

    // Toggle cleaning status between pending and completed
    toggleCleaningStatus(requestId) {
        const request = this.cleaningRequests.find(req => req.id === requestId);
        if (!request) return;

        // Find and disable the button while processing
        const button = document.querySelector(`[onclick="cleaningManager.toggleCleaningStatus('${requestId}')"]`);
        if (button) {
            button.classList.add('loading');
            button.style.pointerEvents = 'none';
        }

        // Simulate processing delay for better UX
        setTimeout(() => {
            const wasCompleted = request.status === 'completed';

            if (request.status === 'pending') {
                request.status = 'completed';
                request.completedAt = new Date().toISOString();
                this.showNotification(`✅ Cleaning request ${requestId} marked as completed`, 'success');
                
                // Trigger vendor notification for completion
                this.notifyVendorCompletion(request);
            } else {
                request.status = 'pending';
                request.completedAt = null;
                this.showNotification(`⏳ Cleaning request ${requestId} marked as pending`, 'info');
                
                // Notify vendor of status change back to pending
                this.notifyVendorStatusChange(request);
            }

            this.saveCleaningRequests();
            this.renderTable();
            this.updateStats();
            
            // Re-enable the button
            if (button) {
                button.classList.remove('loading');
                button.style.pointerEvents = 'auto';
            }
        }, 800); // 800ms delay for realistic processing feel
    }

    // Notify vendor when their cleaning request is completed
    notifyVendorCompletion(request) {
        // Create a completion notification event for the vendor
        const completionEvent = {
            type: 'cleaning_completion',
            requestId: request.id,
            vendorName: request.vendorName,
            stallNumber: request.stallNumber,
            completedAt: request.completedAt,
            timestamp: new Date().toISOString(),
            title: '🧹 Cleaning Request Completed',
            message: `Your cleaning request ${request.id} has been completed`,
            details: `Stall ${request.stallNumber} - ${request.description || 'Cleaning service completed'}`
        };

        // Store vendor-specific notification
        this.storeVendorNotification(request.vendorName, completionEvent);
        
        // Update global vendor events
        const vendorEvents = JSON.parse(localStorage.getItem('vendor_completion_events') || '[]');
        vendorEvents.push(completionEvent);
        localStorage.setItem('vendor_completion_events', JSON.stringify(vendorEvents));

        // Trigger real-time update for vendor dashboard
        this.triggerVendorUpdate();
    }

    // Notify vendor of any status change
    notifyVendorStatusChange(request) {
        const statusEvent = {
            type: 'cleaning_status_change',
            requestId: request.id,
            vendorName: request.vendorName,
            stallNumber: request.stallNumber,
            newStatus: request.status,
            timestamp: new Date().toISOString(),
            title: '📋 Cleaning Request Status Updated',
            message: `Your cleaning request ${request.id} status changed to ${request.status.toUpperCase()}`,
            details: `Stall ${request.stallNumber} - Status: ${request.status}`
        };

        // Store vendor-specific notification
        this.storeVendorNotification(request.vendorName, statusEvent);
        
        // Trigger real-time update for vendor dashboard
        this.triggerVendorUpdate();
    }

    // Store notification for specific vendor
    storeVendorNotification(vendorName, notification) {
        const notificationId = `cleaning_${notification.requestId}_${Date.now()}`;
        notification.id = notificationId;
        notification.read = false;

        // Get existing notifications for this vendor
        const vendorNotifications = JSON.parse(localStorage.getItem(`vendor_notifications_${vendorName}`) || '[]');
        vendorNotifications.unshift(notification); // Add to beginning
        
        // Keep only latest 50 notifications per vendor
        if (vendorNotifications.length > 50) {
            vendorNotifications.splice(50);
        }
        
        localStorage.setItem(`vendor_notifications_${vendorName}`, JSON.stringify(vendorNotifications));
    }

    // Trigger real-time update for vendor dashboards
    triggerVendorUpdate() {
        // Update the main cleaning requests data
        localStorage.setItem('farmfresh_cleaning_requests', JSON.stringify(this.cleaningRequests));
        
        // Trigger storage events for real-time updates
        window.dispatchEvent(new StorageEvent('storage', {
            key: 'farmfresh_cleaning_requests',
            newValue: JSON.stringify(this.cleaningRequests)
        }));
        
        // Also trigger vendor notification update event
        window.dispatchEvent(new StorageEvent('storage', {
            key: 'vendor_notifications_update',
            newValue: Date.now().toString()
        }));
    }

    // Update statistics display
    updateStats() {
        const total = this.cleaningRequests.length;
        const pending = this.cleaningRequests.filter(req => req.status === 'pending').length;
        const completed = this.cleaningRequests.filter(req => req.status === 'completed').length;

        document.getElementById('totalCleaningRequests').textContent = total;
        document.getElementById('pendingCleaningRequests').textContent = pending;
        document.getElementById('completedCleaningRequests').textContent = completed;
    }

    // Get status label for display
    getStatusLabel(status) {
        switch (status) {
            case 'pending':
                return 'Pending';
            case 'completed':
                return 'Completed';
            default:
                return status;
        }
    }

    // Format date for display
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    // Export reports functionality
    exportReports() {
        const csv = this.generateCSVReport();
        this.downloadCSV(csv, 'cleaning-requests-report.csv');
    }

    // Generate CSV report
    generateCSVReport() {
        const headers = ['Request ID', 'Vendor Name', 'Stall Number', 'Request Date', 'Scheduled Time', 'Status'];
        const rows = this.cleaningRequests.map(request => [
            request.id,
            request.vendorName,
            request.stallNumber,
            request.requestDate,
            request.scheduledTime,
            request.status
        ]);

        return [headers, ...rows].map(row => row.join(',')).join('\n');
    }

    // Download CSV file
    downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('href', url);
        a.setAttribute('download', filename);
        a.click();
        window.URL.revokeObjectURL(url);
    }

    // Show schedule view
    showScheduleView() {
        alert('Schedule view functionality would be implemented here');
    }

    // Show enhanced notification with animations
    showNotification(message, type = 'info') {
        // Remove any existing notifications
        const existingNotifications = document.querySelectorAll('.admin-notification');
        existingNotifications.forEach(notif => notif.remove());
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `admin-notification ${type}`;
        
        // Add icon based on type
        const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : '📋';
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${icon}</span>
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        // Style notification
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? 'linear-gradient(135deg, #4CAF50, #45a049)' : 
                        type === 'error' ? 'linear-gradient(135deg, #f44336, #d32f2f)' : 
                        'linear-gradient(135deg, #2196F3, #1976d2)'};
            color: white;
            border-radius: 8px;
            z-index: 10000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            min-width: 300px;
            max-width: 400px;
            transform: translateX(100%);
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border-left: 4px solid rgba(255,255,255,0.3);
        `;
        
        // Style notification content
        const content = notification.querySelector('.notification-content');
        content.style.cssText = `
            display: flex;
            align-items: center;
            padding: 12px 16px;
            gap: 10px;
        `;
        
        // Style notification icon
        const iconEl = notification.querySelector('.notification-icon');
        iconEl.style.cssText = `
            font-size: 18px;
            flex-shrink: 0;
        `;
        
        // Style notification message
        const messageEl = notification.querySelector('.notification-message');
        messageEl.style.cssText = `
            flex: 1;
            font-weight: 500;
            font-size: 14px;
            line-height: 1.4;
        `;
        
        // Style close button
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.style.cssText = `
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            margin-left: 8px;
            opacity: 0.7;
            transition: opacity 0.2s;
        `;
        
        closeBtn.onmouseover = () => closeBtn.style.opacity = '1';
        closeBtn.onmouseout = () => closeBtn.style.opacity = '0.7';
        
        document.body.appendChild(notification);
        
        // Animate in
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
        });
        
        // Auto-remove notification after 4 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }, 4000);
    }
}

// Initialize cleaning management when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on the cleaning management section
    if (document.getElementById('cleaningRequestsTableBody')) {
        window.cleaningManager = new CleaningManager();
    }
});

// Global function for onclick handlers
function toggleCleaningStatus(requestId) {
    if (window.cleaningManager) {
        window.cleaningManager.toggleCleaningStatus(requestId);
    }
}