/**
 * Admin Sidebar Mobile Toggle Functionality
 * Handles sidebar visibility on mobile devices
 * Includes floating button support for bottom-left menu trigger
 */

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    const floatingSidebarBtn = document.getElementById('floatingSidebarBtn');
    const sidebarClose = document.getElementById('sidebarClose');

    // Toggle sidebar on mobile (old button)
    if (mobileSidebarToggle) {
        mobileSidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Toggle sidebar with floating button (new bottom-left button)
    if (floatingSidebarBtn) {
        floatingSidebarBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
            // Toggle active class for icon rotation
            this.classList.toggle('active', sidebar?.classList.contains('active'));
        });

        // Add subtle pulse animation on first load (only once)
        setTimeout(() => {
            floatingSidebarBtn.classList.add('pulse-once');
            // Remove class after animation completes
            setTimeout(() => {
                floatingSidebarBtn.classList.remove('pulse-once');
            }, 2000);
        }, 1000);
    }

    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            closeSidebar();
        });
    }

    // Close button in sidebar (if exists)
    if (sidebarClose) {
        sidebarClose.addEventListener('click', function() {
            closeSidebar();
        });
    }

    // Close sidebar when clicking a nav link on mobile
    const sidebarLinks = sidebar?.querySelectorAll('.nav-link');
    if (sidebarLinks) {
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    closeSidebar();
                }
            });
        });
    }

    // Close sidebar on window resize if moving to desktop view
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            closeSidebar();
        }
    });

    // Functions
    function toggleSidebar() {
        if (sidebar && sidebarOverlay) {
            var isActive = sidebar.classList.contains('active');
            
            if (isActive) {
                // Close sidebar
                sidebar.classList.remove('active');
                sidebar.style.left = '-100%';
                sidebarOverlay.classList.remove('active');
                sidebarOverlay.style.display = 'none';
                document.body.classList.remove('sidebar-open');
            } else {
                // Open sidebar with inline styles for mobile
                sidebar.classList.add('active');
                sidebar.style.left = '0';
                sidebar.style.position = 'fixed';
                sidebar.style.top = '0';
                sidebar.style.height = '100vh';
                sidebar.style.width = '280px';
                sidebar.style.maxWidth = '80vw';
                sidebar.style.zIndex = '10100';
                sidebar.style.display = 'flex';
                sidebar.style.flexDirection = 'column';
                sidebar.style.background = '#ffffff';
                sidebar.style.boxShadow = '5px 0 15px rgba(0, 0, 0, 0.2)';
                sidebar.style.overflowY = 'auto';
                
                sidebarOverlay.classList.add('active');
                sidebarOverlay.style.display = 'block';
                sidebarOverlay.style.position = 'fixed';
                sidebarOverlay.style.top = '0';
                sidebarOverlay.style.left = '0';
                sidebarOverlay.style.width = '100%';
                sidebarOverlay.style.height = '100%';
                sidebarOverlay.style.background = 'rgba(0,0,0,0.5)';
                sidebarOverlay.style.zIndex = '10090';
                
                document.body.classList.add('sidebar-open');
            }
            
            // Update floating button state and icon
            if (floatingSidebarBtn) {
                floatingSidebarBtn.classList.toggle('active', sidebar.classList.contains('active'));
                if (sidebar.classList.contains('active')) {
                    floatingSidebarBtn.innerHTML = '<i class="fas fa-times"></i>';
                } else {
                    floatingSidebarBtn.innerHTML = '<i class="fas fa-bars"></i>';
                }
            }
        }
    }

    function closeSidebar() {
        if (sidebar && sidebarOverlay) {
            sidebar.classList.remove('active');
            sidebar.style.left = '-100%';
            sidebarOverlay.classList.remove('active');
            sidebarOverlay.style.display = 'none';
            document.body.classList.remove('sidebar-open');
            // Update floating button state
            if (floatingSidebarBtn) {
                floatingSidebarBtn.classList.remove('active');
                floatingSidebarBtn.innerHTML = '<i class="fas fa-bars"></i>';
            }
        }
    }

    // Prevent body scroll when sidebar is open on mobile
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                if (document.body.classList.contains('sidebar-open')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }
        });
    });

    observer.observe(document.body, {
        attributes: true
    });
});
