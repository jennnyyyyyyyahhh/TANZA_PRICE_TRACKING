// Admin Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Current date display
    updateCurrentDate();
    
    // Set admin name
  
    
    // Notification bell functionality
    setupNotifications();
    
    // Setup account modal functionality
    setupAccountModal();
    
    // Sidebar navigation
    setupSidebarNavigation();
    
    // Vendor management functionality
    setupVendorManagement();
    
    // Report management functionality
    if (typeof setupReportManagement === 'function') {
        setupReportManagement();
    }
    
    // Vendor requests functionality
    if (typeof setupVendorRequestsManagement === 'function') {
        setupVendorRequestsManagement();
    }
    
    // Market Price Analytics functionality
    setupMarketPriceAnalytics();
    
    // Logout functionality
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        
        // This would typically connect to an actual logout API endpoint
        if (confirm('Are you sure you want to log out?')) {
            // Simulate logout by redirecting to login page
            alert('You have been logged out successfully.');
            // window.location.href = 'login.html'; // Uncomment when login page exists
        }
    });
});

// Update the current date display
function updateCurrentDate() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
}

// Setup notification dropdown functionality
function setupNotifications() {
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationBadge = document.getElementById('notificationBadge');
    let notificationCount = parseInt(notificationBadge.textContent) || 5;
    
    // Toggle notification dropdown
    notificationBell.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.classList.toggle('show');
        
        // Add click animation
        notificationBell.style.transform = 'scale(1.2)';
        setTimeout(() => {
            notificationBell.style.transform = 'scale(1)';
        }, 150);
    });
    
    // Close notification dropdown when clicking elsewhere
    document.addEventListener('click', function(e) {
        if (!notificationDropdown.contains(e.target) && e.target !== notificationBell) {
            notificationDropdown.classList.remove('show');
        }
    });
    
    // Mark all as read functionality
    const markReadBtn = document.querySelector('.mark-read');
    markReadBtn.addEventListener('click', function() {
        notificationCount = 0;
        updateNotificationBadge();
        showToast('All notifications marked as read!', 'success');
    });
    
    // Individual notification clicks
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach((item, index) => {
        item.addEventListener('click', function() {
            item.style.opacity = '0.6';
            setTimeout(() => {
                item.remove();
                notificationCount--;
                updateNotificationBadge();
                showToast('Notification dismissed', 'info');
            }, 300);
        });
    });
    
    // Helper function to update badge display
    function updateNotificationBadge() {
        if (notificationCount > 0) {
            notificationBadge.textContent = notificationCount;
            notificationBadge.style.display = 'flex';
        } else {
            notificationBadge.style.display = 'none';
        }
    }
    
    // Add toast notification functionality
    if (!window.showToastAdded) {
        window.showToastAdded = true;
        
        // Add CSS animations for toast
        const toastStyles = document.createElement('style');
        toastStyles.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .toast-content {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .toast-close {
                background: none;
                border: none;
                color: white;
                font-size: 1.2rem;
                cursor: pointer;
                padding: 0;
                margin-left: 10px;
            }
        `;
        document.head.appendChild(toastStyles);
    }
}

// Setup sidebar navigation
function setupSidebarNavigation() {
    const navItems = document.querySelectorAll('.sidebar .nav-item');
    const contentSections = document.querySelectorAll('.content-section');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all nav items
            navItems.forEach(navItem => {
                navItem.classList.remove('active');
            });
            
            // Add active class to clicked nav item
            item.classList.add('active');
            
            // Get the target section ID from the href attribute
            const targetId = item.querySelector('a').getAttribute('href').substring(1);
            const targetSection = document.getElementById(`${targetId}-section`);
            
            // Hide all content sections
            contentSections.forEach(section => {
                section.classList.remove('active');
            });
            
            // Show the target section
            if (targetSection) {
                targetSection.classList.add('active');
            }
        });
    });
}

// For the future: Add data visualization functionality
function initializeCharts() {
    // This function would initialize charts and graphs when implemented
    // It would use a charting library like Chart.js, D3.js, or similar
    console.log('Chart initialization will be implemented here');
}

// Setup account dropdown functionality
function setupAccountModal() {
    // Initialize account dropdown
    const accountDropdown = document.getElementById('accountDropdown');
    
    // Setup admin account button to toggle dropdown
    const adminAccountBtn = document.getElementById('adminAccountBtn');
    
    if (adminAccountBtn && accountDropdown) {
        adminAccountBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle the dropdown
            accountDropdown.classList.toggle('show');
            
            // Add click animation
            adminAccountBtn.style.transform = 'scale(1.1)';
            setTimeout(() => {
                adminAccountBtn.style.transform = 'scale(1)';
            }, 150);
        });
        
        // Close dropdown when clicking elsewhere
        document.addEventListener('click', function(e) {
            if (!accountDropdown.contains(e.target) && e.target !== adminAccountBtn) {
                accountDropdown.classList.remove('show');
            }
        });
        
        // Setup menu item click handlers
        setupAccountMenuItems();
    }
}

// Setup account menu item functionality
function setupAccountMenuItems() {
    const menuItems = document.querySelectorAll('.account-menu-item');
    const accountDropdown = document.getElementById('accountDropdown');
    
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.getAttribute('data-action');
            
            // Close the dropdown
            accountDropdown.classList.remove('show');
            
            // Handle different menu item actions
            switch(action) {
                case 'profile':
                    showToast('Admin Profile - Feature coming soon!', 'info');
                    break;
                case 'settings':
                    showToast('System Settings - Feature coming soon!', 'info');
                    break;
                case 'users':
                    showToast('User Management - Feature coming soon!', 'info');
                    break;
                case 'security':
                    showToast('Security Settings - Feature coming soon!', 'info');
                    break;
                case 'notifications':
                    showToast('Notification Preferences - Feature coming soon!', 'info');
                    break;
                case 'help':
                    showToast('Admin Help & Resources - Feature coming soon!', 'info');
                    break;
                case 'logout':
                    if (confirm('Are you sure you want to log out?')) {
                        showToast('You have been logged out successfully.', 'success');
                        setTimeout(() => {
                            // window.location.href = 'login.html'; // Uncomment when login page exists
                        }, 1500);
                    }
                    break;
                default:
                    break;
            }
        });
    });
}

// Example function to load vendor data
function loadVendorData() {
    // This would typically fetch data from an API
    // For now, it's a placeholder for future implementation
    console.log('Vendor data loading will be implemented here');
    
    // Simulated data for development
    return {
        totalVendors: 124,
        pendingApplications: 5,
        activeVendors: 119,
        categoriesByCount: {
            'Produce': 52,
            'Meat & Poultry': 28,
            'Seafood': 18,
            'Dry Goods': 15,
            'Food Stalls': 11
        }
    };
}

// For handling stall management functionality
function initializeStallMap() {
    // This would initialize an interactive stall map
    // Using SVG or Canvas-based visualization
    console.log('Stall map initialization will be implemented here');
}

// Function to handle form submissions
function handleFormSubmission(formId, endpoint) {
    const form = document.getElementById(formId);
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Form validation would go here
            
            // Form submission logic (AJAX or fetch)
            console.log(`Submitting form to ${endpoint}`);
            
            // Show success message
            alert('Form submitted successfully!');
            form.reset();
        });
    }
}

// Vendor Management Functionality
function setupVendorManagement() {
    // Check if vendor management section exists
    const vendorManagementSection = document.getElementById('vendor-management-section');
    if (!vendorManagementSection) return;

    // Select All Vendors Checkbox
    const selectAllVendors = document.getElementById('selectAllVendors');
    if (selectAllVendors) {
        selectAllVendors.addEventListener('change', function() {
            const vendorCheckboxes = document.querySelectorAll('.vendor-select');
            vendorCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    // Vendor Status Filter
    const vendorStatusFilter = document.getElementById('vendorStatusFilter');
    if (vendorStatusFilter) {
        vendorStatusFilter.addEventListener('change', function() {
            filterVendors();
        });
    }

    // Vendor Category Filter
    const vendorCategoryFilter = document.getElementById('vendorCategoryFilter');
    if (vendorCategoryFilter) {
        vendorCategoryFilter.addEventListener('change', function() {
            filterVendors();
        });
    }

    // Apply Bulk Action
    const applyBulkAction = document.getElementById('applyBulkAction');
    if (applyBulkAction) {
        applyBulkAction.addEventListener('click', function() {
            const selectedAction = document.getElementById('bulkActionSelect').value;
            if (!selectedAction) {
                alert('Please select an action to perform');
                return;
            }

            const selectedVendors = document.querySelectorAll('.vendor-select:checked');
            if (selectedVendors.length === 0) {
                alert('Please select at least one vendor');
                return;
            }

            // Get vendor IDs
            const vendorIds = [];
            selectedVendors.forEach(checkbox => {
                const vendorRow = checkbox.closest('tr');
                const vendorId = vendorRow.querySelector('td:nth-child(2)').textContent;
                vendorIds.push(vendorId);
            });

            // Confirm action
            if (confirm(`Are you sure you want to ${selectedAction} ${vendorIds.length} vendor(s)?`)) {
                console.log(`Applying ${selectedAction} to vendors:`, vendorIds);
                
                // Simulate API call
                setTimeout(() => {
                    alert(`Successfully applied ${selectedAction} to ${vendorIds.length} vendor(s)`);
                    // Here you would typically refresh the vendor list
                }, 500);
            }
        });
    }

    // Setup action buttons
    setupVendorActionButtons();
}

// Filter vendors based on filter selections
function filterVendors() {
    const statusFilter = document.getElementById('vendorStatusFilter').value;
    const categoryFilter = document.getElementById('vendorCategoryFilter').value;
    
    const vendorRows = document.querySelectorAll('.vendor-table tbody tr');
    
    vendorRows.forEach(row => {
        const vendorCategory = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
        const statusBadge = row.querySelector('.status-badge');
        const vendorStatus = statusBadge ? statusBadge.textContent.toLowerCase() : '';
        
        const matchesStatus = statusFilter === 'all' || vendorStatus === statusFilter;
        const matchesCategory = categoryFilter === 'all' || vendorCategory.toLowerCase().includes(categoryFilter.replace('-', ' '));
        
        if (matchesStatus && matchesCategory) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Setup vendor action buttons (edit, activate/deactivate, delete)
function setupVendorActionButtons() {
    // Edit buttons
    document.querySelectorAll('.btn-icon[title="Edit"]').forEach(button => {
        button.addEventListener('click', function() {
            const vendorRow = this.closest('tr');
            const vendorId = vendorRow.querySelector('td:nth-child(2)').textContent;
            const vendorName = vendorRow.querySelector('td:nth-child(3)').textContent;
            
            console.log(`Editing vendor ${vendorId}: ${vendorName}`);
            alert(`Edit functionality for ${vendorName} will be implemented here`);
        });
    });
    
    // Suspend buttons
    document.querySelectorAll('.btn-icon[title="Suspend"]').forEach(button => {
        button.addEventListener('click', function() {
            const vendorRow = this.closest('tr');
            const vendorId = vendorRow.querySelector('td:nth-child(2)').textContent;
            const vendorName = vendorRow.querySelector('td:nth-child(3)').textContent;
            
            if (confirm(`Are you sure you want to suspend vendor ${vendorName}?`)) {
                console.log(`Suspending vendor ${vendorId}: ${vendorName}`);
                alert(`${vendorName} has been suspended`);
                
                // Update the status badge
                const statusBadge = vendorRow.querySelector('.status-badge');
                statusBadge.textContent = 'Suspended';
                statusBadge.className = 'status-badge suspended';
            }
        });
    });
    
    // Activate buttons
    document.querySelectorAll('.btn-icon[title="Activate"]').forEach(button => {
        button.addEventListener('click', function() {
            const vendorRow = this.closest('tr');
            const vendorId = vendorRow.querySelector('td:nth-child(2)').textContent;
            const vendorName = vendorRow.querySelector('td:nth-child(3)').textContent;
            
            console.log(`Activating vendor ${vendorId}: ${vendorName}`);
            alert(`${vendorName} has been activated`);
            
            // Update the status badge
            const statusBadge = vendorRow.querySelector('.status-badge');
            statusBadge.textContent = 'Active';
            statusBadge.className = 'status-badge active';
        });
    });
    
    // Deactivate buttons
    document.querySelectorAll('.btn-icon[title="Deactivate"]').forEach(button => {
        button.addEventListener('click', function() {
            const vendorRow = this.closest('tr');
            const vendorId = vendorRow.querySelector('td:nth-child(2)').textContent;
            const vendorName = vendorRow.querySelector('td:nth-child(3)').textContent;
            
            if (confirm(`Are you sure you want to deactivate vendor ${vendorName}?`)) {
                console.log(`Deactivating vendor ${vendorId}: ${vendorName}`);
                alert(`${vendorName} has been deactivated`);
                
                // Update the status badge
                const statusBadge = vendorRow.querySelector('.status-badge');
                statusBadge.textContent = 'Inactive';
                statusBadge.className = 'status-badge inactive';
            }
        });
    });
    
    // Delete buttons
    document.querySelectorAll('.btn-icon[title="Delete"]').forEach(button => {
        button.addEventListener('click', function() {
            const vendorRow = this.closest('tr');
            const vendorId = vendorRow.querySelector('td:nth-child(2)').textContent;
            const vendorName = vendorRow.querySelector('td:nth-child(3)').textContent;
            
            if (confirm(`Are you sure you want to delete vendor ${vendorName}? This action cannot be undone.`)) {
                console.log(`Deleting vendor ${vendorId}: ${vendorName}`);
                showToast(`${vendorName} has been deleted`, 'success');
                
                // Remove the row
                vendorRow.remove();
            }
        });
    });
}

// Toast notification system
function showToast(message, type = 'info') {
    // Remove existing toast
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas ${getToastIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="toast-close">&times;</button>
    `;

    // Add toast styles
    toast.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${getToastColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-width: 300px;
        animation: slideInRight 0.3s ease-out;
        font-family: 'Segoe UI', sans-serif;
        font-weight: 600;
    `;

    document.body.appendChild(toast);

    // Close button functionality
    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', () => {
        toast.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => toast.remove(), 300);
    });

    // Auto remove after 4 seconds
    setTimeout(() => {
        if (document.body.contains(toast)) {
            toast.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => toast.remove(), 300);
        }
    }, 4000);
}

function getToastIcon(type) {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    return icons[type] || icons.info;
}

function getToastColor(type) {
    const colors = {
        success: '#4caf50',
        error: '#f44336',
        warning: '#ff7043',
        info: '#2196f3'
    };
    return colors[type] || colors.info;
}
