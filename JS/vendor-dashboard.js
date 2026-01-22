// Vendor Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const sidebarToggle = document.querySelector('.toggle-sidebar');
    const sidebar = document.querySelector('.sidebar');
    const navLinks = document.querySelectorAll('.nav-link');
    const contentSections = document.querySelectorAll('.content-section');
    const logoutBtn = document.getElementById('logoutBtn');
    const currentDateEl = document.getElementById('currentDate');
    
    // Notification Elements
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    // Account Elements
    const vendorAccountBtn = document.getElementById('vendorAccountBtn');
    const accountDropdown = document.getElementById('accountDropdown');
    
    // Header Account Elements
    const headerAccountBtn = document.getElementById('headerAccountBtn');
    // Dropdown removed as requested
    
    // Set current date
    const now = new Date();
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    currentDateEl.textContent = now.toLocaleDateString('en-US', options);
    
    // ALL RESTRICTIONS REMOVED - Full vendor access granted
    // Vendors now have unrestricted access to all dashboard features
    
    // Set vendor name across the dashboard
    const vendorName = "Maria";
    // document.getElementById("headerUserName").textContent = vendorName;
    // document.getElementById("welcomeVendorName").textContent = vendorName;
    
    // Mobile Sidebar Toggle Functionality
    const toggleSidebarBtn = document.getElementById('toggleSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarElement = document.getElementById('sidebar');
    
    if (toggleSidebarBtn && sidebarElement && sidebarOverlay) {
        // Toggle sidebar on button click
        toggleSidebarBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebarElement.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });
        
        // Close sidebar when clicking overlay
        sidebarOverlay.addEventListener('click', function() {
            sidebarElement.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992) {
                if (!sidebarElement.contains(e.target) && 
                    !toggleSidebarBtn.contains(e.target) &&
                    sidebarElement.classList.contains('active')) {
                    sidebarElement.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                }
            }
        });
    }
    
    // Navigation functionality
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the target section ID from the href attribute
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(`${targetId}-section`);
            
            // Remove active class from all nav items and sections
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            contentSections.forEach(section => section.classList.remove('active'));
            
            // Add active class to clicked nav item and target section
            this.parentElement.classList.add('active');
            if (targetSection) {
                targetSection.classList.add('active');
            }
            
            // Close sidebar on mobile after navigation
            if (window.innerWidth <= 992) {
                if (sidebarElement) {
                    sidebarElement.classList.remove('active');
                }
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('active');
                }
            }
        });
    });
    
    // Notification Bell toggle
    if (notificationBell && notificationDropdown) {
        notificationBell.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
            
            // Close account dropdown if open
            if (accountDropdown.classList.contains('show')) {
                accountDropdown.classList.remove('show');
                vendorAccountBtn.classList.remove('active');
            }
        });
    }
    
    // Account Button toggle
    if (vendorAccountBtn && accountDropdown) {
        vendorAccountBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            accountDropdown.classList.toggle('show');
            this.classList.toggle('active');
            
            // Close notification dropdown if open
            if (notificationDropdown.classList.contains('show')) {
                notificationDropdown.classList.remove('show');
            }
        });
    }
    
    // Header Account Button - Navigate to profile
    if (headerAccountBtn) {
        headerAccountBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all nav items and sections
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            contentSections.forEach(section => section.classList.remove('active'));
            
            // Show profile section
            const profileSection = document.getElementById('profile-section');
            if (profileSection) {
                profileSection.classList.add('active');
            }
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    if (document.getElementById('helpCenterBtn')) {
        document.getElementById('helpCenterBtn').addEventListener('click', function() {
            headerAccountDropdown.classList.remove('show');
            headerAccountBtn.classList.remove('active');
            // Navigate to help center or show help modal
        });
    }
    
    if (document.getElementById('headerLogoutBtn')) {
        document.getElementById('headerLogoutBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to log out?')) {
                // Handle logout
                window.location.href = '../HTML/landingtrial.html';
            }
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (notificationDropdown && !notificationDropdown.contains(e.target) && !notificationBell.contains(e.target)) {
            notificationDropdown.classList.remove('show');
        }
        
        if (accountDropdown && !accountDropdown.contains(e.target) && !vendorAccountBtn.contains(e.target)) {
            accountDropdown.classList.remove('show');
            vendorAccountBtn.classList.remove('active');
        }
        
        // Dropdown for header account button has been removed
    });
    
    /* Logout functionality
    logoutBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to log out?')) {
            // Clear any auth tokens or session data
            localStorage.removeItem('vendor_token');
            localStorage.removeItem('vendor_data');
            
            // Redirect to login page
            window.location.href = '../HTML/landingtrial.html';
        }
    });*/
    
    // Initialize charts and data (placeholder)
    initDashboardData();
});

// Initialize Dashboard Data (placeholder)
function initDashboardData() {
    // This function would normally fetch data from an API
    // and populate the dashboard with real-time information
    
    // For demo purposes, we're using static data already in the HTML
}

// Market Trends and Analytics (placeholder)
function loadMarketTrends() {
    // This function would fetch and display market trends
    // For now, it's just a placeholder
}

// Helper Functions
function formatCurrency(amount) {
    return '₱' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function getRelativeTimeString(date) {
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'just now';
    }
    
    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) {
        return `${diffInMinutes} minute${diffInMinutes > 1 ? 's' : ''} ago`;
    }
    
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) {
        return `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`;
    }
    
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 30) {
        return `${diffInDays} day${diffInDays > 1 ? 's' : ''} ago`;
    }
    
    const options = { month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Event Tabs Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get all event tab buttons and event cards
    const eventTabs = document.querySelectorAll('.event-tab');
    const eventCards = document.querySelectorAll('.event-card');
    
    // Add click event to each tab button
    eventTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabType = this.dataset.tab;
            
            // Remove active class from all tabs and add to clicked tab
            eventTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show all events if "All" tab is clicked, otherwise filter by type
            eventCards.forEach(card => {
                if (tabType === 'all') {
                    card.style.display = 'block';
                } else {
                    if (card.classList.contains(tabType)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        });
    });
    
    // Notices Functionality
    const noticeTypeFilter = document.getElementById('noticeTypeFilter');
    const noticeDateFilter = document.getElementById('noticeDateFilter');
    const noticeSearchInput = document.getElementById('noticeSearchInput');
    const noticeItems = document.querySelectorAll('.notice-item');
    
    // Filter notices
    function filterNotices() {
        const typeValue = noticeTypeFilter ? noticeTypeFilter.value : 'all';
        const dateValue = noticeDateFilter ? noticeDateFilter.value : 'all';
        const searchValue = noticeSearchInput ? noticeSearchInput.value.toLowerCase() : '';
        
        noticeItems.forEach(item => {
            // Type filter
            const typeMatch = typeValue === 'all' || item.classList.contains(typeValue);
            
            // Date filter (simplified for demonstration)
            // In a real app, you would compare actual dates
            const dateMatch = dateValue === 'all' || true;
            
            // Search filter
            const titleElement = item.querySelector('.notice-title');
            const contentElement = item.querySelector('.notice-content p');
            const title = titleElement ? titleElement.textContent.toLowerCase() : '';
            const content = contentElement ? contentElement.textContent.toLowerCase() : '';
            const searchMatch = searchValue === '' || 
                                title.includes(searchValue) || 
                                content.includes(searchValue);
            
            // Show item if it matches all filters
            item.style.display = typeMatch && dateMatch && searchMatch ? 'block' : 'none';
        });
    }
    
    // Add event listeners to filters
    if (noticeTypeFilter) {
        noticeTypeFilter.addEventListener('change', filterNotices);
    }
    
    if (noticeDateFilter) {
        noticeDateFilter.addEventListener('change', filterNotices);
    }
    
    if (noticeSearchInput) {
        noticeSearchInput.addEventListener('input', filterNotices);
    }
    
    // Mark notice as read
    document.querySelectorAll('.notice-action').forEach(action => {
        action.addEventListener('click', function() {
            const noticeItem = this.closest('.notice-item');
            const statusElement = noticeItem.querySelector('.notice-status');
            
            if (statusElement && statusElement.classList.contains('unread')) {
                statusElement.classList.remove('unread');
                statusElement.classList.add('read');
                statusElement.textContent = 'Read';
            }
        });
    });
    
    // Simplified Cleaning Request Form Functionality
    const cleaningRequestForm = document.querySelector('.cleaning-request-form');
    if (cleaningRequestForm) {
        const resetCleaningFormBtn = document.getElementById('resetCleaningForm');
        const submitCleaningRequestBtn = document.getElementById('submitCleaningRequest');
        const preferredDateInput = document.getElementById('preferredDate');
        
        // Set minimum date to today
        if (preferredDateInput) {
            const today = new Date().toISOString().split('T')[0];
            preferredDateInput.setAttribute('min', today);
        }
        
        // Reset form
        if (resetCleaningFormBtn) {
            resetCleaningFormBtn.addEventListener('click', function() {
                if (confirm("Are you sure you want to clear all form fields?")) {
                    cleaningRequestForm.reset();
                    
                    // Reset any custom styling
                    const inputs = cleaningRequestForm.querySelectorAll('input, select');
                    inputs.forEach(input => {
                        input.style.borderColor = '';
                    });
                }
            });
        }
        
        // Submit form
        if (cleaningRequestForm) {
            cleaningRequestForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate form
                let isValid = true;
                const requiredInputs = cleaningRequestForm.querySelectorAll('[required]');
                
                requiredInputs.forEach(input => {
                    if (!input.value.trim()) {
                        input.style.borderColor = 'var(--danger-color)';
                        isValid = false;
                    } else {
                        input.style.borderColor = '';
                    }
                });
                
                if (!isValid) {
                    alert("Please fill in all required fields.");
                    return;
                }
                
                // Collect form data
                const formData = {
                    requestType: document.getElementById('requestType').value,
                    preferredDate: document.getElementById('preferredDate').value,
                    preferredTime: document.getElementById('preferredTime').value,
                    stallNumber: document.getElementById('stallNumber').value,
                    marketSection: document.getElementById('marketSection').value,
                    description: document.getElementById('requestDescription').value
                };
                
                // Save cleaning request and show success
                submitCleaningRequestBtn.disabled = true;
                submitCleaningRequestBtn.textContent = 'Submitting...';
                
                setTimeout(() => {
                    const requestId = saveCleaningRequest(formData);
                    
                    // Create success notification
                    const formContainer = cleaningRequestForm.parentElement;
                    formContainer.innerHTML = `
                        <div class="success-message">
                            <div class="success-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3>Cleaning Request Submitted!</h3>
                            <p>Your request has been successfully submitted and is being processed.</p>
                            <div class="request-details">
                                <p><strong>Request ID:</strong> ${requestId}</p>
                                <p><strong>Request Type:</strong> ${document.getElementById('requestType').options[document.getElementById('requestType').selectedIndex].text}</p>
                                <p><strong>Stall:</strong> ${formData.stallNumber}, ${document.getElementById('marketSection').options[document.getElementById('marketSection').selectedIndex].text}</p>
                                <p><strong>Scheduled:</strong> ${formData.preferredDate}, ${document.getElementById('preferredTime').options[document.getElementById('preferredTime').selectedIndex].text}</p>
                                <p><strong>Status:</strong> <span class="request-status pending">Pending</span></p>
                            </div>
                            <p class="note">You will be notified when your request has been approved by the administration.</p>
                            <div class="form-actions">
                                <button type="button" class="btn-primary" onclick="window.location.reload()">Submit Another Request</button>
                            </div>
                        </div>
                    `;
                }, 1500);
            });
        }
    }

    // Profile Form Functionality
    const profileForm = document.querySelector('.profile-edit-form');
    if (profileForm) {
        const resetFormBtn = document.getElementById('resetForm');
        const saveProfileBtn = document.getElementById('saveProfile');
        const profileImageInput = document.getElementById('profileImage');
        const profilePreview = document.getElementById('profilePreview');
        
        // Store original form data
        const originalFormData = new FormData(profileForm);
        
        // Handle image preview
        if (profileImageInput) {
            profileImageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
        
        // Reset form to original values
        if (resetFormBtn) {
            resetFormBtn.addEventListener('click', function() {
                // Confirm before resetting
                if (confirm("Are you sure you want to reset all changes?")) {
                    // Reset all form fields to their original values
                    for (const [key, value] of originalFormData.entries()) {
                        const field = profileForm.elements[key];
                        if (field) {
                            field.value = value;
                        }
                    }
                    // Reset profile image preview
                    profilePreview.src = "../IMG/default-profile.png";
                }
            });
        }
        
        // Save profile information
        if (saveProfileBtn) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Here you would typically make an API call to save the data
                // For this example, we'll just show a success message
                
                // Validate form
                let isValid = true;
                const requiredFields = ['firstName', 'lastName', 'email', 'phoneNumber', 'stallNumber', 'address'];
                
                requiredFields.forEach(field => {
                    const input = document.getElementById(field);
                    if (input && !input.value.trim()) {
                        input.style.borderColor = 'var(--danger-color)';
                        isValid = false;
                    } else if (input) {
                        input.style.borderColor = 'var(--border-color)';
                    }
                });
                
                if (!isValid) {
                    alert("Please fill in all required fields.");
                    return;
                }
                
                // Simulate API call with a timeout
                saveProfileBtn.disabled = true;
                saveProfileBtn.innerHTML = 'Saving...';
                
                setTimeout(() => {
                    alert("Profile updated successfully!");
                    saveProfileBtn.disabled = false;
                    saveProfileBtn.innerHTML = 'Save Profile';
                    
                    // Update original form data
                    const newFormData = new FormData(profileForm);
                    for (const [key, value] of newFormData.entries()) {
                        originalFormData.set(key, value);
                    }
                    
                    // Update vendor name in the welcome message and header
                    const firstName = document.getElementById('firstName').value;
                    const lastName = document.getElementById('lastName').value;
                    const fullName = `${firstName} ${lastName}`;
                    
                    const welcomeNameEl = document.getElementById('welcomeVendorName');
                    const headerNameEl = document.getElementById('headerUserName');
                    
                    if (welcomeNameEl) welcomeNameEl.textContent = fullName;
                    if (headerNameEl) headerNameEl.textContent = firstName;
                    
                }, 1500);
            });
        }
    }
});

// Function to save cleaning request to localStorage
function saveCleaningRequest(formData) {
    // Generate unique request ID
    const requestId = `CR${String(Date.now()).slice(-6)}`;
    
    // Get current vendor info
    const vendorName = document.getElementById('headerUserName').textContent;
    
    // Convert time slot to actual time
    const timeSlotMap = {
        'early': '5:00 AM',
        'morning': '9:00 AM', 
        'afternoon': '3:00 PM',
        'evening': '9:00 PM'
    };
    
    // Create new cleaning request object
    const newRequest = {
        id: requestId,
        vendorName: vendorName,
        stallNumber: formData.stallNumber,
        requestDate: formData.preferredDate,
        scheduledTime: timeSlotMap[formData.preferredTime] || formData.preferredTime,
        status: 'pending',
        description: formData.description || `${getRequestTypeDescription(formData.requestType)} requested by ${vendorName}`,
        requestedBy: vendorName,
        contactNumber: '0915-555-1234', // This would normally come from user profile
        completedAt: null,
        notes: `Request submitted on ${new Date().toLocaleDateString()}`
    };
    
    // Get existing cleaning requests from localStorage
    let existingRequests = [];
    const saved = localStorage.getItem('farmfresh_cleaning_requests');
    if (saved) {
        existingRequests = JSON.parse(saved);
    }
    
    // Add new request to the list
    existingRequests.push(newRequest);
    
    // Save back to localStorage
    localStorage.setItem('farmfresh_cleaning_requests', JSON.stringify(existingRequests));
    
    return requestId;
}

// Helper function to convert request type to description
function getRequestTypeDescription(requestType) {
    const descriptions = {
        'regular': 'Regular stall cleaning',
        'deep': 'Deep cleaning service', 
        'emergency': 'Emergency clean-up',
        'pest': 'Pest control treatment',
        'waste': 'Waste disposal service'
    };
    return descriptions[requestType] || 'Cleaning service';
}

// Cleaning Request Status Management
class VendorCleaningStatus {
    constructor() {
        this.vendorName = document.getElementById('headerUserName')?.textContent || 'Vendor';
        this.initializeStatusDisplay();
        this.initializeNotifications();
        this.setupStorageListener();
        this.loadCleaningStatus();
        this.loadNotifications();
    }

    initializeStatusDisplay() {
        // Initialize cleaning request status section
        const statusSection = document.getElementById('cleaningRequestsStatus');
        if (statusSection) {
            this.renderCleaningStatus();
        }
    }

    initializeNotifications() {
        // Initialize notification system
        const notificationBell = document.getElementById('notificationBell');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const markAllRead = document.getElementById('markAllRead');

        if (notificationBell && notificationDropdown) {
            notificationBell.addEventListener('click', () => {
                notificationDropdown.style.display = 
                    notificationDropdown.style.display === 'block' ? 'none' : 'block';
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.notification-container')) {
                    notificationDropdown.style.display = 'none';
                }
            });
        }

        if (markAllRead) {
            markAllRead.addEventListener('click', () => {
                this.markAllNotificationsAsRead();
            });
        }
    }

    setupStorageListener() {
        // Listen for changes to cleaning requests (when admin updates status)
        window.addEventListener('storage', (e) => {
            if (e.key === 'farmfresh_cleaning_requests') {
                this.checkForStatusUpdates();
            }
        });

        // Also check periodically for same-window updates
        setInterval(() => {
            this.checkForStatusUpdates();
        }, 3000);
    }

    checkForStatusUpdates() {
        const currentRequests = this.getVendorRequests();
        const storedLastCheck = localStorage.getItem(`vendor_last_check_${this.vendorName}`);
        const lastCheckTime = storedLastCheck ? new Date(storedLastCheck) : new Date(0);
        
        currentRequests.forEach(request => {
            const requestTime = new Date(request.completedAt || request.requestDate);
            if (request.status === 'completed' && requestTime > lastCheckTime) {
                this.addNotification(request);
            }
        });

        // Update last check time
        localStorage.setItem(`vendor_last_check_${this.vendorName}`, new Date().toISOString());
        this.renderCleaningStatus();
    }

    getVendorRequests() {
        const saved = localStorage.getItem('farmfresh_cleaning_requests');
        if (!saved) return [];
        
        const allRequests = JSON.parse(saved);
        return allRequests.filter(request => 
            request.vendorName === this.vendorName || request.requestedBy === this.vendorName
        );
    }

    addNotification(request) {
        const notifications = this.getNotifications();
        const notificationId = `cleaning_${request.id}`;
        
        // Check if notification already exists
        if (notifications.find(n => n.id === notificationId)) {
            return;
        }

        const newNotification = {
            id: notificationId,
            type: 'cleaning_completed',
            title: 'Cleaning Request Completed',
            message: `Your cleaning request ${request.id} has been completed`,
            details: `Stall ${request.stallNumber} - ${request.description}`,
            timestamp: new Date().toISOString(),
            read: false,
            requestId: request.id
        };

        notifications.unshift(newNotification);
        this.saveNotifications(notifications);
        this.updateNotificationBadge();
        this.renderNotifications();
    }

    getNotifications() {
        const saved = localStorage.getItem(`vendor_notifications_${this.vendorName}`);
        return saved ? JSON.parse(saved) : [];
    }

    saveNotifications(notifications) {
        localStorage.setItem(`vendor_notifications_${this.vendorName}`, JSON.stringify(notifications));
    }

    loadNotifications() {
        this.renderNotifications();
        this.updateNotificationBadge();
    }

    renderNotifications() {
        const notifications = this.getNotifications();
        const cleaningNotificationsContainer = document.getElementById('cleaningNotifications');
        
        if (!cleaningNotificationsContainer) return;

        const cleaningNotifications = notifications.filter(n => n.type === 'cleaning_completed');
        
        if (cleaningNotifications.length === 0) {
            cleaningNotificationsContainer.innerHTML = '';
            return;
        }

        cleaningNotificationsContainer.innerHTML = `
            <div class="notification-separator">
                <h5>Cleaning Updates</h5>
            </div>
            ${cleaningNotifications.map(notification => `
                <div class="notification-item ${notification.read ? 'read' : 'unread'}" data-notification-id="${notification.id}">
                    <i class="fas fa-check-circle cleaning-completed"></i>
                    <div class="notification-content">
                        <p><strong>${notification.title}</strong></p>
                        <small>${notification.details}</small>
                        <div class="notification-time">${this.formatNotificationTime(notification.timestamp)}</div>
                    </div>
                    <button class="mark-notification-read" onclick="vendorCleaningStatus.markNotificationAsRead('${notification.id}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('')}
        `;
    }

    renderCleaningStatus() {
        const requests = this.getVendorRequests();
        const statusContainer = document.getElementById('cleaningRequestsStatus');
        
        if (!statusContainer) return;

        if (requests.length === 0) {
            statusContainer.innerHTML = `
                <div class="no-requests-message">
                    <i class="fas fa-broom"></i>
                    <p>No cleaning requests submitted yet.</p>
                    <small>Submit your first cleaning request below.</small>
                </div>
            `;
            return;
        }

        // Sort by request date (newest first)
        requests.sort((a, b) => new Date(b.requestDate) - new Date(a.requestDate));

        statusContainer.innerHTML = requests.map(request => `
            <div class="cleaning-request-status-item ${request.status}" data-request-id="${request.id}">
                <div class="status-header">
                    <div class="request-info">
                        <h4>${request.id}</h4>
                        <span class="status-badge ${request.status}">${this.getStatusLabel(request.status)}</span>
                    </div>
                    <div class="request-date">
                        ${this.formatDate(request.requestDate)} at ${request.scheduledTime}
                    </div>
                </div>
                <div class="status-details">
                    <div class="detail-row">
                        <span class="label">Stall:</span>
                        <span class="value">${request.stallNumber}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Description:</span>
                        <span class="value">${request.description}</span>
                    </div>
                    ${request.status === 'completed' ? `
                        <div class="completion-notice">
                            <i class="fas fa-check-circle"></i>
                            <span>Completed on ${this.formatDateTime(request.completedAt)}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    getStatusLabel(status) {
        const labels = {
            'pending': 'Pending',
            'completed': 'Completed'
        };
        return labels[status] || status;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    formatDateTime(dateTimeString) {
        const date = new Date(dateTimeString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    formatNotificationTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        return date.toLocaleDateString();
    }

    markNotificationAsRead(notificationId) {
        const notifications = this.getNotifications();
        const notification = notifications.find(n => n.id === notificationId);
        
        if (notification) {
            notification.read = true;
            this.saveNotifications(notifications);
            this.renderNotifications();
            this.updateNotificationBadge();
        }
    }

    markAllNotificationsAsRead() {
        const notifications = this.getNotifications();
        notifications.forEach(n => n.read = true);
        this.saveNotifications(notifications);
        this.renderNotifications();
        this.updateNotificationBadge();
    }

    updateNotificationBadge() {
        const notifications = this.getNotifications();
        const unreadCount = notifications.filter(n => !n.read).length;
        const badge = document.getElementById('notificationBadge');
        
        if (badge) {
            badge.textContent = unreadCount || '';
            badge.style.display = unreadCount > 0 ? 'block' : 'none';
        }
    }

    loadCleaningStatus() {
        this.renderCleaningStatus();
    }
}

// Initialize vendor cleaning status when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('cleaningRequestsStatus')) {
        window.vendorCleaningStatus = new VendorCleaningStatus();
    }
    
    // Handle verification banner "Upload Now" link clicks
    const bannerLinks = document.querySelectorAll('.banner-link');
    bannerLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the target section ID from the href attribute
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(`${targetId}-section`);
            
            // Remove active class from all nav items and sections
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            document.querySelectorAll('.content-section').forEach(section => section.classList.remove('active'));
            
            // Find and activate the corresponding nav item
            const correspondingNavLink = document.querySelector(`.nav-link[href="#${targetId}"]`);
            if (correspondingNavLink) {
                correspondingNavLink.parentElement.classList.add('active');
            }
            
            // Add active class to target section
            if (targetSection) {
                targetSection.classList.add('active');
            }
            
            // Scroll to top of page for better UX
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
});
