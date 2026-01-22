// Copied vendor-dashboard-shared.js into JS/ to satisfy includes

// Shared JavaScript for all vendor dashboard pages

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.getElementById('toggleSidebar') || document.getElementById('mobileSidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const headerAccountBtn = document.getElementById('headerAccountBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const currentDateSpan = document.getElementById('currentDate');

    // --- Floating Sidebar Button for Mobile ---
    initVendorFloatingButton();

    // --- Sidebar Toggle Logic (Mobile) ---
    if (toggleSidebar) {
        toggleSidebar.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            // Update floating button icon if exists
            var floatBtn = document.getElementById('vendorFloatingSidebarBtn');
            if (floatBtn) {
                floatBtn.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });
    }

    // --- Active Sidebar Link Highlighting ---
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-list .nav-item a');
    const currentPath = window.location.pathname.split('/').pop();

    navLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        // Check if the link's href matches the current file name
        if (linkHref === currentPath) {
            // Remove 'active' from all and add to the current one's parent li
            document.querySelectorAll('.sidebar-nav .nav-list .nav-item').forEach(item => {
                item.classList.remove('active');
            });
            link.closest('.nav-item').classList.add('active');
        }
    });

    // --- Logout Button Placeholder Logic ---
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // In a real application, this would contain the actual logout logic (e.g., API call, redirect)
            alert('Logging out...');
            // Example: window.location.href = 'login.html';
        });
    }

    // --- Header Account Button Logic ---
    if (headerAccountBtn) {
        // Since the sidebar link for "Edit Profile" is now a direct link in the header, 
        // we just ensure the href is set correctly in the HTML template (which it is: edit-profile.html)
    }

    // --- Date and Time Display ---
    function updateDateTime() {
        const now = new Date();
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        currentDateSpan.textContent = now.toLocaleDateString('en-US', options);
    }
    
    if (currentDateSpan) {
        updateDateTime();
    }

    // --- Notification Dropdown Logic ---
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const markAllRead = document.getElementById('markAllRead');
    const notificationBadge = document.getElementById('notificationBadge');

    if (notificationBell && notificationDropdown) {
        notificationBell.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent the document click listener from firing immediately
            notificationDropdown.classList.toggle('active');
            // Hide badge when dropdown is opened
            if (notificationDropdown.classList.contains('active')) {
                notificationBadge.style.display = 'none';
            } else {
                // Re-show badge if there are unread notifications (placeholder logic)
                if (parseInt(notificationBadge.textContent) > 0) {
                    notificationBadge.style.display = 'inline-block';
                }
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationBell.contains(e.target) && !notificationDropdown.contains(e.target)) {
                notificationDropdown.classList.remove('active');
                // Re-show badge if there are unread notifications (placeholder logic)
                if (parseInt(notificationBadge.textContent) > 0) {
                    notificationBadge.style.display = 'inline-block';
                }
            }
        });
    }

    if (markAllRead) {
        markAllRead.addEventListener('click', function() {
            // Placeholder for marking all notifications as read
            alert('All notifications marked as read.');
            notificationBadge.textContent = '0';
            notificationBadge.style.display = 'none';
            // In a real app, you would update the server/local storage
        });
    }
});

// --- Vendor Floating Sidebar Button ---
function initVendorFloatingButton() {
    // Only run on mobile/tablet
    function isMobile() {
        return window.innerWidth <= 992;
    }
    
    // Don't create if already exists
    if (document.getElementById('vendorFloatingSidebarBtn')) return;
    
    // Inject CSS for floating button
    var style = document.createElement('style');
    style.id = 'vendorFloatingBtnStyles';
    style.textContent = `
        /* Vendor Floating sidebar button - hidden on desktop */
        .vendor-floating-sidebar-btn {
            display: none;
        }
        
        /* Show floating button on mobile/tablet */
        @media (max-width: 992px) {
            .vendor-floating-sidebar-btn {
                display: flex !important;
                position: fixed !important;
                bottom: 25px !important;
                right: 20px !important;
                z-index: 99999 !important;
                width: 56px !important;
                height: 56px !important;
                background: linear-gradient(135deg, #4a8c33 0%, #3d7429 100%) !important;
                color: white !important;
                border: none !important;
                border-radius: 50% !important;
                cursor: pointer !important;
                align-items: center !important;
                justify-content: center !important;
                box-shadow: 0 4px 15px rgba(74, 140, 51, 0.4), 
                            0 2px 6px rgba(0, 0, 0, 0.2) !important;
                transition: all 0.3s ease !important;
            }
            
            .vendor-floating-sidebar-btn i {
                font-size: 1.4rem !important;
                color: white !important;
            }
            
            .vendor-floating-sidebar-btn:hover {
                transform: scale(1.08) !important;
            }
            
            /* Hide the old toggle button on mobile when floating button is present */
            .toggle-sidebar {
                display: none !important;
            }
            
            /* Sidebar mobile styles */
            .sidebar {
                position: fixed !important;
                top: 0 !important;
                left: -100% !important;
                width: 280px !important;
                max-width: 80vw !important;
                height: 100vh !important;
                z-index: 10100 !important;
                background: white !important;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1) !important;
                transition: left 0.3s ease !important;
                overflow-y: auto !important;
                display: flex !important;
                flex-direction: column !important;
            }
            
            .sidebar.active {
                left: 0 !important;
                box-shadow: 5px 0 15px rgba(0, 0, 0, 0.2) !important;
            }
            
            /* Sidebar Overlay */
            .sidebar-overlay {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background-color: rgba(0, 0, 0, 0.3) !important;
                z-index: 10090 !important;
                display: none !important;
                opacity: 0 !important;
                transition: opacity 0.3s ease !important;
                pointer-events: none !important;
            }
            
            .sidebar-overlay.active {
                display: block !important;
                opacity: 1 !important;
                pointer-events: auto !important;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Create the floating button
    var btn = document.createElement('button');
    btn.className = 'vendor-floating-sidebar-btn';
    btn.id = 'vendorFloatingSidebarBtn';
    btn.setAttribute('aria-label', 'Open menu');
    btn.innerHTML = '<i class="fas fa-bars"></i>';
    
    // Append to body
    document.body.appendChild(btn);
    
    var sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
    var overlay = document.getElementById('sidebarOverlay') || document.querySelector('.sidebar-overlay');
    
    // Create overlay if it doesn't exist
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.id = 'sidebarOverlay';
        document.body.insertBefore(overlay, document.body.firstChild);
    }
    
    // Function to close sidebar
    function closeSidebar() {
        if (sidebar) {
            sidebar.classList.remove('active');
        }
        if (overlay) {
            overlay.classList.remove('active');
        }
        document.body.classList.remove('sidebar-open');
        btn.innerHTML = '<i class="fas fa-bars"></i>';
    }
    
    // Function to open sidebar
    function openSidebar() {
        if (sidebar) {
            sidebar.classList.add('active');
        }
        if (overlay) {
            overlay.classList.add('active');
        }
        document.body.classList.add('sidebar-open');
        btn.innerHTML = '<i class="fas fa-times"></i>';
    }
    
    // Add click handler to button
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        e.preventDefault();
        
        if (sidebar && sidebar.classList.contains('active')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });
    
    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            e.stopPropagation();
            closeSidebar();
        });
    }
    
    // Hide sidebar on mobile by default on page load
    if (isMobile() && sidebar) {
        sidebar.classList.remove('active');
        if (overlay) {
            overlay.classList.remove('active');
        }
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (!isMobile()) {
            // Desktop - reset sidebar
            if (sidebar) {
                sidebar.style.left = '';
                sidebar.style.position = '';
                sidebar.classList.remove('active');
            }
            if (overlay) {
                overlay.classList.remove('active');
            }
            btn.innerHTML = '<i class="fas fa-bars"></i>';
        }
    });
}