// Shared JavaScript for all vendor dashboard pages

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.getElementById('toggleSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const headerAccountBtn = document.getElementById('headerAccountBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const currentDateSpan = document.getElementById('currentDate');

    // --- Sidebar Toggle Logic (Mobile) ---
    if (toggleSidebar) {
        toggleSidebar.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('active');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
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
