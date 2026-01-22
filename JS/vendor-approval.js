/**
 * Vendor Approval System
 * Handles vendor approval requests and status management
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on admin dashboard page
    const vendorRequestsSection = document.getElementById('vendor-requests-section');
    if (vendorRequestsSection) {
        // Initialize vendor approval system
        initVendorApprovalSystem();
    }
});

function initVendorApprovalSystem() {
    // Load vendor request table
    loadVendorRequests();
    
    // Set up approval action handlers
    setupApprovalActions();
    
    // Set up filter handlers
    setupRequestFilters();
    
    // Set up refresh button
    const refreshBtn = document.getElementById('refreshVendorRequests');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            loadVendorRequests();
        });
    }
    
    // Set up modal functionality
    setupVendorDetailModal();
}

/**
 * Load vendor signup requests from localStorage into the vendor requests table
 */
function loadVendorRequests() {
    const requestsTableBody = document.getElementById('vendor-requests-tbody');
    if (!requestsTableBody) return;
    
    // Clear existing rows
    requestsTableBody.innerHTML = '';
    
    // Get all users from localStorage
    const allUsers = getAllUsersFromStorage();
    
    // Filter for pending vendor requests
    const pendingVendors = allUsers.filter(user => 
        user.user_type === 'pending_vendor' || 
        (user.is_vendor === true && user.approval_status === 'pending')
    );
    
    // Show no requests message if no pending vendors
    const noRequestsMessage = document.getElementById('no-vendor-requests');
    if (noRequestsMessage) {
        if (pendingVendors.length === 0) {
            noRequestsMessage.style.display = 'block';
            if (document.getElementById('vendor-requests-table')) {
                document.getElementById('vendor-requests-table').style.display = 'none';
            }
        } else {
            noRequestsMessage.style.display = 'none';
            if (document.getElementById('vendor-requests-table')) {
                document.getElementById('vendor-requests-table').style.display = 'table';
            }
        }
    }
    
    // Add each pending vendor to the table
    pendingVendors.forEach((vendor, index) => {
        const requestId = vendor.id || `VND${String(index + 1).padStart(3, '0')}`;
        const registrationDate = vendor.registration_date ? 
            new Date(vendor.registration_date).toLocaleDateString('en-US', {
                year: 'numeric', 
                month: 'short', 
                day: 'numeric'
            }) : 
            new Date().toLocaleDateString('en-US', {
                year: 'numeric', 
                month: 'short', 
                day: 'numeric'
            });
        
        const row = document.createElement('tr');
        row.dataset.userId = vendor.id || `user_${Date.now()}`;
        row.dataset.email = vendor.email;
        row.dataset.vendorData = JSON.stringify(vendor);
        
        row.innerHTML = `
            <td>${requestId}</td>
            <td>${registrationDate}</td>
            <td>${vendor.first_name} ${vendor.last_name}</td>
            <td>${vendor.email}</td>
            <td><span class="status-badge pending">Pending</span></td>
            <td class="action-buttons">
                <button class="btn-icon view-btn" title="View Details" data-id="${vendor.id}"><i class="fas fa-eye"></i></button>
                <button class="btn-icon approve-btn" title="Approve" data-id="${vendor.id}"><i class="fas fa-check"></i></button>
                <button class="btn-icon decline-btn" title="Decline" data-id="${vendor.id}"><i class="fas fa-times"></i></button>
            </td>
        `;
        
        requestsTableBody.appendChild(row);
    });
}

/**
 * Set up action handlers for approve/decline buttons
 */
function setupApprovalActions() {
    const requestsTable = document.getElementById('vendor-requests-table');
    if (!requestsTable) return;
    
    requestsTable.addEventListener('click', function(e) {
        // Handle approve button click
        if (e.target.closest('.approve-btn')) {
            const button = e.target.closest('.approve-btn');
            const userId = button.getAttribute('data-id');
            const row = button.closest('tr');
            
            if (userId) {
                if (confirm('Are you sure you want to approve this vendor?')) {
                    approveVendor(userId);
                    row.querySelector('.status-badge').textContent = 'Approved';
                    row.querySelector('.status-badge').classList.replace('pending', 'approved');
                    
                    // Update action buttons
                    const actionCell = row.querySelector('.action-buttons');
                    actionCell.innerHTML = `
                        <button class="btn-icon view-btn" title="View Details" data-id="${userId}"><i class="fas fa-eye"></i></button>
                        <button class="btn-icon" title="Send Welcome Email" data-id="${userId}"><i class="fas fa-envelope"></i></button>
                    `;
                }
            }
        }
        
        // Handle decline button click
        if (e.target.closest('.decline-btn')) {
            const button = e.target.closest('.decline-btn');
            const userId = button.getAttribute('data-id');
            const row = button.closest('tr');
            
            if (userId) {
                if (confirm('Are you sure you want to decline this vendor?')) {
                    declineVendor(userId);
                    row.querySelector('.status-badge').textContent = 'Declined';
                    row.querySelector('.status-badge').classList.replace('pending', 'declined');
                    
                    // Update action buttons
                    const actionCell = row.querySelector('.action-buttons');
                    actionCell.innerHTML = `
                        <button class="btn-icon view-btn" title="View Details" data-id="${userId}"><i class="fas fa-eye"></i></button>
                        <button class="btn-icon" title="Reconsider" data-id="${userId}"><i class="fas fa-redo"></i></button>
                    `;
                }
            }
        }
        
        // Handle view details button click
        if (e.target.closest('.view-btn')) {
            const button = e.target.closest('.view-btn');
            const userId = button.getAttribute('data-id');
            const row = button.closest('tr');
            
            if (userId && row.dataset.vendorData) {
                const vendorData = JSON.parse(row.dataset.vendorData);
                showVendorDetailModal(vendorData);
            }
        }
    });
    
    // Set up modal approve/decline buttons
    const modalApproveBtn = document.getElementById('modal-approve-btn');
    const modalDeclineBtn = document.getElementById('modal-decline-btn');
    
    if (modalApproveBtn) {
        modalApproveBtn.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            if (userId) {
                approveVendor(userId);
                closeVendorDetailModal();
                loadVendorRequests(); // Refresh the table
            }
        });
    }
    
    if (modalDeclineBtn) {
        modalDeclineBtn.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            if (userId) {
                declineVendor(userId);
                closeVendorDetailModal();
                loadVendorRequests(); // Refresh the table
            }
        });
    }
}

/**
 * Set up the vendor detail modal
 */
function setupVendorDetailModal() {
    const modal = document.getElementById('vendor-detail-modal');
    if (!modal) return;
    
    // Close modal when clicking the X button or Close button
    const closeButtons = modal.querySelectorAll('.modal-close');
    closeButtons.forEach(button => {
        button.addEventListener('click', closeVendorDetailModal);
    });
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeVendorDetailModal();
        }
    });
}

/**
 * Show vendor details in a modal
 */
function showVendorDetailModal(vendorData) {
    const modal = document.getElementById('vendor-detail-modal');
    if (!modal || !vendorData) return;
    
    // Fill in vendor details
    document.getElementById('detail-registration-date').textContent = 
        vendorData.registration_date ? 
        new Date(vendorData.registration_date).toLocaleDateString('en-US', {
            year: 'numeric', 
            month: 'short', 
            day: 'numeric'
        }) : 'Not available';
    
    document.getElementById('detail-status').innerHTML = 
        `<span class="status-badge pending">Pending</span>`;
    
    document.getElementById('detail-first-name').textContent = 
        vendorData.first_name || 'Not available';
    
    document.getElementById('detail-last-name').textContent = 
        vendorData.last_name || 'Not available';
    
    document.getElementById('detail-email').textContent = 
        vendorData.email || 'Not available';
    
    document.getElementById('detail-business-name').textContent = 
        vendorData.business_name || 'Not available';
    
    // Set user ID for modal buttons
    document.getElementById('modal-approve-btn').setAttribute('data-id', vendorData.id);
    document.getElementById('modal-decline-btn').setAttribute('data-id', vendorData.id);
    
    // Show the modal
    modal.style.display = 'block';
}

/**
 * Close the vendor detail modal
 */
function closeVendorDetailModal() {
    const modal = document.getElementById('vendor-detail-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Set up filter handlers for the requests table
 */
function setupRequestFilters() {
    const statusFilter = document.getElementById('requestStatusFilter');
    if (!statusFilter) return;
    
    statusFilter.addEventListener('change', function() {
        filterVendorRequests(this.value);
    });
}

/**
 * Filter vendor requests by status
 */
function filterVendorRequests(status) {
    const rows = document.querySelectorAll('#vendor-requests-tbody tr');
    
    rows.forEach(row => {
        const statusBadge = row.querySelector('.status-badge');
        if (!statusBadge) return;
        
        const rowStatus = statusBadge.textContent.toLowerCase();
        
        if (status === 'all' || rowStatus === status.toLowerCase()) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
    if (statusFilter) {
        statusFilter.addEventListener('change', filterRequests);
    }
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterRequests);
    }
}

/**
 * Filter the requests table based on selected filters
 */
function filterRequests() {
    const statusFilter = document.getElementById('requestStatusFilter').value;
    const categoryFilter = document.getElementById('requestCategoryFilter').value;
    
    const rows = document.querySelectorAll('#vendor-requests-section table tbody tr');
    
    rows.forEach(row => {
        let statusMatch = true;
        let categoryMatch = true;
        
        // Check status filter
        if (statusFilter !== 'all') {
            const statusBadge = row.querySelector('.status-badge');
            statusMatch = statusBadge && statusBadge.classList.contains(statusFilter);
        }
        
        // Check category filter
        if (categoryFilter !== 'all') {
            const category = row.querySelector('td:nth-child(6)').textContent.toLowerCase();
            categoryMatch = category.includes(categoryFilter.toLowerCase());
        }
        
        // Show/hide row
        row.style.display = statusMatch && categoryMatch ? '' : 'none';
    });
}

/**
 * Update counts in the dashboard stats
 */
function updateRequestCounts() {
    // Get pending requests count
    const pendingRequests = document.querySelectorAll('#vendor-requests-section .status-badge.pending').length;
    
    // Update stats card if exists
    const pendingRequestsElement = document.querySelector('.stat-card:nth-child(3) .stat-card-body h2');
    if (pendingRequestsElement) {
        pendingRequestsElement.textContent = pendingRequests;
    }
}

/**
 * Approve vendor and update their status in localStorage
 */
/**
 * Approve vendor and update their status in localStorage
 * @param {string} userId - ID of the vendor to approve
 */
function approveVendor(userId) {
    // Get all users
    const allUsers = getAllUsersFromStorage();
    
    // Find the vendor to approve
    const userIndex = allUsers.findIndex(user => user.id === userId);
    
    if (userIndex !== -1) {
        // Update user status
        allUsers[userIndex].user_type = 'vendor';
        allUsers[userIndex].approval_status = 'approved';
        allUsers[userIndex].is_approved = true;
        
        // Store the vendor email for updating current user if needed
        const vendorEmail = allUsers[userIndex].email;
        
        // Update currently logged-in user if it's the same user
        const currentUser = JSON.parse(localStorage.getItem('user'));
        if (currentUser && vendorEmail && currentUser.email === vendorEmail) {
            currentUser.user_type = 'vendor';
            currentUser.approval_status = 'approved';
            currentUser.is_approved = true;
            localStorage.setItem('user', JSON.stringify(currentUser));
        }
        
        // Save all users back to storage
        localStorage.setItem('all_users', JSON.stringify(allUsers));
        
        // Show success message
        showNotification('Vendor approved successfully');
        return true;
    }
    
    return false;
}

/**
 * Decline vendor and update their status in localStorage
 * @param {string} userId - ID of the vendor to decline
 */
function declineVendor(userId) {
    // Get all users
    const allUsers = getAllUsersFromStorage();
    
    // Find the vendor to decline
    const userIndex = allUsers.findIndex(user => user.id === userId);
    
    if (userIndex !== -1) {
        // Update user status
        allUsers[userIndex].user_type = 'vendor';
        allUsers[userIndex].approval_status = 'declined';
        allUsers[userIndex].is_approved = false;
        
        // Store the vendor email for updating current user if needed
        const vendorEmail = allUsers[userIndex].email;
        
        // Update currently logged-in user if it's the same user
        const currentUser = JSON.parse(localStorage.getItem('user'));
        if (currentUser && vendorEmail && currentUser.email === vendorEmail) {
            currentUser.user_type = 'vendor';
            currentUser.approval_status = 'declined';
            currentUser.is_approved = false;
            localStorage.setItem('user', JSON.stringify(currentUser));
        }
        
        // Save all users back to storage
        localStorage.setItem('all_users', JSON.stringify(allUsers));
        
        // Show success message
        showNotification('Vendor request declined');
        return true;
    }
    
    return false;
}

/**
 * Show vendor details in the details pane
 */
function showVendorDetails(userId, email) {
    // Get the user data
    const allUsers = getAllUsersFromStorage();
    const vendor = allUsers.find(user => 
        (user.id && user.id === userId) || 
        (user.email && user.email === email)
    );
    
    if (!vendor) return;
    
    // Get the details container
    const detailsContainer = document.querySelector('.request-details-container');
    if (!detailsContainer) return;
    
    // Set request ID in header
    const requestIdDisplay = document.getElementById('requestIdDisplay');
    if (requestIdDisplay) {
        const requestId = document.querySelector(`tr[data-user-id="${userId}"] td:nth-child(2)`).textContent;
        requestIdDisplay.textContent = requestId;
    }
    
    // Set request date
    const requestDateDisplay = document.getElementById('requestDateDisplay');
    if (requestDateDisplay) {
        requestDateDisplay.textContent = new Date(vendor.registration_date || Date.now()).toLocaleDateString('en-US', {
            year: 'numeric', 
            month: 'short', 
            day: 'numeric'
        });
    }
    
    // Show the details container
    detailsContainer.style.display = 'block';
    
    // Set up close button
    const closeBtn = document.getElementById('closeRequestDetails');
    if (closeBtn) {
        closeBtn.onclick = function() {
            detailsContainer.style.display = 'none';
        };
    }
}

/**
 * Show a notification message
 */
function showNotification(message, type = 'success') {
    // Check if notification container exists
    let notificationContainer = document.querySelector('.notification-container');
    
    // Create if it doesn't exist
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <span class="notification-message">${message}</span>
        <button class="notification-close"><i class="fas fa-times"></i></button>
    `;
    
    // Add to container
    notificationContainer.appendChild(notification);
    
    // Set timeout to remove
    setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
    
    // Add close button handler
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.classList.add('hide');
        setTimeout(() => {
            notification.remove();
        }, 300);
    });
}

/**
 * Get all users from localStorage or return empty array
 */
/**
 * Get all users from localStorage
 * @returns {Array} Array of user objects
 */
function getAllUsersFromStorage() {
    const usersString = localStorage.getItem('all_users');
    if (!usersString) {
        // Initialize with current user if exists
        const currentUser = JSON.parse(localStorage.getItem('user'));
        if (currentUser) {
            localStorage.setItem('all_users', JSON.stringify([currentUser]));
            return [currentUser];
        }
        return [];
    }
    
    try {
        return JSON.parse(usersString);
    } catch (e) {
        console.error('Error parsing all_users from localStorage:', e);
        return [];
    }
}

/**
 * Show a notification message to the user
 * @param {string} message - Message to display
 * @param {string} type - Type of message (success, error, info)
 */
function showNotification(message, type = 'success') {
    // Use existing notification system if available
    if (window.showToast) {
        window.showToast(message, type);
        return;
    }
    
    // Create our own notification if needed
    let notification = document.getElementById('approval-notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'approval-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 10000;
            color: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transform: translateY(-100%);
            transition: transform 0.3s ease;
        `;
        document.body.appendChild(notification);
    }
    
    // Set colors based on type
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        info: '#17a2b8'
    };
    notification.style.backgroundColor = colors[type] || colors.info;
    
    // Set message
    notification.textContent = message;
    
    // Show notification
    setTimeout(() => {
        notification.style.transform = 'translateY(0)';
    }, 100);
    
    // Hide after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateY(-100%)';
    }, 3000);
}