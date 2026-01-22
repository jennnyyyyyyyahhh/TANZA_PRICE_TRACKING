<?php
// Reusable admin sidebar with notification badges
// Requires: php/connection.php must be available
include_once __DIR__ . '/php/connection.php';

$vendorPending = 0;
$surveyNew = 0;
$reportNew = 0;
$cleaningPending = 0;
$permitPending = 0;

try {
    // vendor requests: admin table entries with role='vendor' and is_active = 0
    $r = $conn->query("SELECT COUNT(*) AS c FROM admin WHERE role='vendor' AND is_active = 0");
    if ($r) $vendorPending = (int)$r->fetch_assoc()['c'];

    // surveys: count all surveys (assumed new ones need review)
    $r = $conn->query("SELECT COUNT(*) AS c FROM surveys");
    if ($r) $surveyNew = (int)$r->fetch_assoc()['c'];

    // reports: status = 'new'
    $r = $conn->query("SELECT COUNT(*) AS c FROM reports WHERE status = 'new'");
    if ($r) $reportNew = (int)$r->fetch_assoc()['c'];

    // cleaning requests pending
    $r = $conn->query("SELECT COUNT(*) AS c FROM cleaning_requests WHERE status = 'pending'");
    if ($r) $cleaningPending = (int)$r->fetch_assoc()['c'];

    // business permits pending
    $r = $conn->query("SELECT COUNT(*) AS c FROM business_permits WHERE verification_status = 'pending'");
    if ($r) $permitPending = (int)$r->fetch_assoc()['c'];

} catch (Exception $e) {
    // if anything fails, keep counts at 0
}

?>

<nav class="sidebar-nav">
    <ul class="nav-list">
        <li class="nav-item"><a href="admin-dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
        <li class="nav-item"><a href="admin-vendor-management.php" class="nav-link"><i class="fas fa-users"></i><span>Vendor Management</span>
         
        </a></li>

        <li class="nav-item"><a href="admin-survey-form.php" id="sidebarSurveyLink" class="nav-link"><i class="fas fa-clipboard-list"></i><span>Submit Survey Form</span>
            <?php if($surveyNew>0): ?>
                <span class="badge bg-danger ms-2" id="sidebarSurveyBadge"><?= $surveyNew ?></span>
            <?php endif; ?>
        </a></li>

        <li class="nav-item"><a href="admin-report-management.php" class="nav-link"><i class="fas fa-exclamation-triangle"></i><span>Report Management</span>
            <?php if($reportNew>0): ?>
                <span class="badge bg-danger ms-2"><?= $reportNew ?></span>
            <?php endif; ?>
        </a></li>

        <li class="nav-item"><a href="admin-cleaning-management.php" class="nav-link"><i class="fas fa-broom"></i><span>Cleaning Management</span>
            <?php if($cleaningPending>0): ?>
                <span class="badge bg-danger ms-2"><?= $cleaningPending ?></span>
            <?php endif; ?>
        </a></li>

        <li class="nav-item "><a href="admin-business-permit.php" class="nav-link"><i class="fas fa-certificate"></i><span>Business Permit</span>
            <?php if($permitPending>0): ?>
                <span class="badge bg-danger ms-2"><?= $permitPending ?></span>
            <?php endif; ?>
        </a></li>

        <li class="nav-item"><a href="admin-events.php" class="nav-link"><i class="fas fa-calendar-alt"></i><span>Events</span></a></li>
    </ul>
</nav>

<style>
    .nav-list .badge { font-size: 0.75rem; vertical-align: middle; }
    
    /* Ensure all sidebar nav links are left-aligned */
    .nav-list .nav-link {
        display: flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        text-align: left !important;
        gap: 0.75rem !important;
    }
    
    .nav-list .nav-link i {
        width: 20px !important;
        text-align: center !important;
        flex-shrink: 0 !important;
    }
    
    .nav-list .nav-link span {
        flex-grow: 1 !important;
    }
</style>

<script>
    (function(){
        // When admin clicks the sidebar "Submit Survey Form" link,
        // mark survey notifications as read and update badges client-side.
        const link = document.getElementById('sidebarSurveyLink');
        const sidebarBadge = document.getElementById('sidebarSurveyBadge');
        if (!link) return;

        link.addEventListener('click', function(e){
            // If there is no badge, nothing to mark; allow normal navigation.
            if (!sidebarBadge) return;

            e.preventDefault();
            const badgeCount = parseInt(sidebarBadge.textContent || '0') || 0;
            const href = this.href;

            // POST mark_read to admin-notifications.php to clear unread notifications
            fetch('admin-notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'mark_read=1'
            }).then(res => res.text())
            .catch(() => '')
            .finally(() => {
                // Remove sidebar badge
                sidebarBadge.remove();

                // Update global notification bell (if present)
                try {
                    const bell = document.getElementById('notificationBadge');
                    if (bell) {
                        const current = parseInt(bell.textContent || '0') || 0;
                        const newVal = Math.max(0, current - badgeCount);
                        if (newVal > 0) bell.textContent = newVal; else bell.remove();
                    }
                } catch (err) { console.warn('Failed to update global badge', err); }

                // Continue navigation to the survey page
                window.location.href = href;
            });
        });
    })();
</script>

<!-- Mobile View CSS -->
<link rel="stylesheet" href="CSS/admin-mobile-view.css">

<!-- Floating Button Styles (inline to ensure visibility) -->
<style>
    /* Floating sidebar button - hidden on desktop */
    .floating-sidebar-btn {
        display: none;
    }
    
    /* Show floating button on mobile/tablet */
    @media (max-width: 992px) {
        .floating-sidebar-btn {
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
        
        .floating-sidebar-btn i {
            font-size: 1.4rem !important;
            color: white !important;
        }
        
        .floating-sidebar-btn:hover {
            transform: scale(1.08) !important;
        }
    }
    
    /* Force hidden modals to be completely out of the way */
    .modal:not(.show),
    div.modal:not(.show),
    div.modal.fade:not(.show) {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -9999 !important;
        position: fixed !important;
        left: -99999px !important;
        top: -99999px !important;
        width: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
    }
    
    .modal:not(.show) *,
    div.modal:not(.show) *,
    div.modal.fade:not(.show) * {
        pointer-events: none !important;
        display: none !important;
    }
    
    .modal-backdrop:not(.show) {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -9999 !important;
    }
</style>

<!-- Create floating button via JavaScript to ensure it's appended to body -->
<script>
(function() {
    // Run immediately when this script loads
    function initFloatingButton() {
        // Only create if not already exists
        if (document.getElementById('floatingSidebarBtn')) return;
        
        // Check if we're on mobile/tablet
        function isMobile() {
            return window.innerWidth <= 992;
        }
        
        // Create the floating button
        var btn = document.createElement('button');
        btn.className = 'floating-sidebar-btn';
        btn.id = 'floatingSidebarBtn';
        btn.setAttribute('aria-label', 'Open menu');
        btn.innerHTML = '<i class="fas fa-bars"></i>';
        
        // Append to body (outside sidebar)
        document.body.appendChild(btn);
        
        // Create overlay if it doesn't exist
        var overlay = document.getElementById('sidebarOverlay') || document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            overlay.id = 'sidebarOverlay';
            document.body.insertBefore(overlay, document.body.firstChild);
        }
        
        var sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
        
        // Function to close sidebar
        function closeSidebar() {
            if (sidebar) {
                sidebar.classList.remove('active');
                sidebar.style.left = '-100%';
            }
            if (overlay) {
                overlay.classList.remove('active');
                overlay.style.display = 'none';
                overlay.style.opacity = '0';
                overlay.style.pointerEvents = 'none';
            }
            document.body.classList.remove('sidebar-open');
            btn.innerHTML = '<i class="fas fa-bars"></i>';
        }
        
        // Function to open sidebar
        function openSidebar() {
            if (sidebar) {
                sidebar.classList.add('active');
                sidebar.style.position = 'fixed';
                sidebar.style.top = '0';
                sidebar.style.left = '0';
                sidebar.style.height = '100vh';
                sidebar.style.width = '280px';
                sidebar.style.maxWidth = '80vw';
                sidebar.style.zIndex = '10100';
                sidebar.style.display = 'flex';
                sidebar.style.flexDirection = 'column';
                sidebar.style.background = '#ffffff';
                sidebar.style.boxShadow = '5px 0 15px rgba(0, 0, 0, 0.2)';
                sidebar.style.overflowY = 'auto';
            }
            if (overlay) {
                overlay.classList.add('active');
                overlay.style.display = 'block';
                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100%';
                overlay.style.height = '100%';
                overlay.style.background = 'rgba(0,0,0,0.3)';
                overlay.style.zIndex = '10090';
                overlay.style.opacity = '1';
                overlay.style.pointerEvents = 'auto';
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
            sidebar.style.left = '-100%';
            if (overlay) {
                overlay.classList.remove('active');
                overlay.style.display = 'none';
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
                    overlay.style.display = 'none';
                }
                btn.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });
    }
    
    // Run when DOM is ready or immediately if already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFloatingButton);
    } else {
        // DOM already loaded, run immediately
        initFloatingButton();
    }
})();
</script>
