<?php
    // Top of file - before DOCTYPE
    include 'php/connection.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Management - Tanza Public Market</title>
    <!-- <link rel="stylesheet" href="../CSS/minimalist-responsive.css"> -->
    <link rel="stylesheet" href="../CSS/admin-dashboard.css">
    <!-- <link rel="stylesheet" href="../CSS/admin-dashboard-responsive.css"> -->
    <link rel="stylesheet" href="../CSS/notification-management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <!-- Bootstrap 5 -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Font Awesome -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="../JS/disable-console-logs.js"></script>
    <script src="../JS/disable-all-notifications.js"></script>
    <script src="../JS/disable-login-requirements.js"></script>
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                
                <div class="logo">
                    <a href="pricefront.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-seedling"></i>
                        <span class="logo-text">Tanza Public Market</span>
                    </a>
                </div>
                <ul class="nav-menu" id="navMenu">
                    <li class="nav-item"><a href="index.php" class="nav-link">HOME</a></li>
                    <li class="nav-item"><a href="index.php#weather" class="nav-link">WEATHER</a></li>
                    <li class="nav-item"><a href="index.php#about" class="nav-link">ABOUT</a></li>
                </ul>
                <div class="nav-actions">
                    <?php include 'admin-notifications.php'; ?>
                    <div class="user-account-container" id="headerAccountContainer">
                        <a href="#profile" class="btn-user-account" id="adminAccountBtn">
                            <i class="fas fa-user-shield"></i>
                            <span id="headerUserName">Admin</span>
                        </a>
                        <div class="account-dropdown" id="accountDropdown">
                            <div class="account-dropdown-header">
                                <div class="account-avatar"><i class="fas fa-user-shield"></i></div>
                                <div class="account-user-info">
                                    <span class="user-type">Administrator</span>
                                </div>
                            </div>
                            <div class="account-menu">
                                <a href="#" class="account-menu-item" data-action="profile"><i class="fas fa-user-shield"></i><span>ADMIN PROFILE</span></a>
                                <a href="#" class="account-menu-item" data-action="settings"><i class="fas fa-cog"></i><span>SYSTEM SETTINGS</span></a>
                                <a href="logout.php" class="account-menu-item logout" data-action="logout"><i class="fas fa-sign-out-alt"></i><span>LOGOUT</span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <button class="mobile-sidebar-toggle" id="mobileSidebarToggle" aria-label="Toggle sidebar menu">
        <i class="fas fa-bars"></i>
        <span>Menu</span>
    </button>
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <!-- <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-shield-alt"></i>
                    <span>Admin Panel</span>
                </div>
                <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div> -->
            <nav class="sidebar-nav">
                                <ul class="nav-list">
                    <li class="nav-item"><a href="admin-dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>DASHBOARD</span></a></li>
                    <li class="nav-item"><a href="admin-vendor-management.php" class="nav-link"><i class="fas fa-users"></i><span>VENDOR MANAGEMENT</span></a></li>
                    <li class="nav-item"><a href="admin-survey-form.php" class="nav-link"><i class="fas fa-clipboard-list"></i><span>SUBMIT SURVEY FORM</span></a></li>
                    <li class="nav-item"><a href="admin-report-management.php" class="nav-link"><i class="fas fa-exclamation-triangle"></i><span>REPORT MANAGEMENT</span></a></li>
                    <li class="nav-item"><a href="admin-cleaning-management.php" class="nav-link"><i class="fas fa-broom"></i><span>CLEANING MANAGEMENT</span></a></li>
                    <!-- <li class="nav-item"><a href="admin-vendor-requests.php" class="nav-link"><i class="fas fa-file-alt"></i><span>VENDOR REQUESTS</span></a></li>
                    <li class="nav-item"><a href="admin-stall-management.php" class="nav-link"><i class="fas fa-store"></i><span>STALL MANAGEMENT</span></a></li> -->
                    <li class="nav-item"><a href="admin-business-permit.php" class="nav-link"><i class="fas fa-certificate"></i><span>BUSINESS PERMIT</span></a></li>
                    <li class="nav-item"><a href="admin-events.php" class="nav-link"><i class="fas fa-calendar-alt"></i><span>EVENTS</span></a></li>
                    <!-- <li class="nav-item"><a href="admin-notices.php" class="nav-link"><i class="fas fa-bullhorn"></i><span>NOTICES</span></a></li>
                    <li class="nav-item"><a href="admin-notification-management.php" class="nav-link"><i class="fas fa-bell"></i><span>NOTIFICATION MANAGEMENT</span></a></li> -->
                </ul>
            </nav>
            <!-- <div class="sidebar-footer">
                <a href="logout.php" id="logoutBtn" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>LOG OUT</span>
                </a>
            </div> -->
        </aside>

        <main class="main-content">
            <header class="top-navbar">
                <div class="top-nav-left">
                    <button class="mobile-sidebar-trigger" id="mobileSidebarTrigger" aria-label="Open sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Notification Management</h1>
                </div>
                <div class="date-time">
                    <span id="currentDate">October 23, 2025</span>
                </div>
            </header>

            <div class="content-wrapper">
                <section class="content-section active" id="notification-management-section">
                    <div class="section-header">
                        <h2>Notification Management</h2>
                        <p>Create, edit, and manage notifications that appear to users across the platform.</p>
                    </div>
                    <div class="section-content">
                        <div class="notification-management-container">
                            <!-- Notification Management Controls -->
                            <div class="notification-management-controls">
                                <div class="filter-options">
                                    <select id="notificationTypeFilter">
                                        <option value="all">All Types</option>
                                        <option value="price-alert">Price Alerts</option>
                                        <option value="market-update">Market Updates</option>
                                        <option value="promotion">Promotions</option>
                                        <option value="system">System Messages</option>
                                        <option value="maintenance">Maintenance</option>
                                    </select>
                                    <select id="notificationStatusFilter">
                                        <option value="all">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="scheduled">Scheduled</option>
                                    </select>
                                </div>
                                <div class="notification-actions-global">
                                    <button class="btn-sm btn-primary" id="addNewNotificationBtn">
                                        <i class="fas fa-plus"></i> Add New Notification
                                    </button>
                                    <button class="btn-sm btn-secondary" id="exportNotificationsBtn">
                                        <i class="fas fa-file-export"></i> Export List
                                    </button>
                                </div>
                            </div>

                            <!-- Notification Statistics -->
                            <div class="notification-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Total Notifications:</span>
                                    <span class="stat-value" id="totalNotifications">12</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Active:</span>
                                    <span class="stat-value active" id="activeNotifications">8</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Inactive:</span>
                                    <span class="stat-value inactive" id="inactiveNotifications">3</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Scheduled:</span>
                                    <span class="stat-value scheduled" id="scheduledNotifications">1</span>
                                </div>
                            </div>

                            <!-- Current Notifications List -->
                            <div class="notification-list-container">
                                <h3>Current Notifications</h3>
                                <table class="notification-table">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="selectAllNotifications"></th>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Title</th>
                                            <th>Message Preview</th>
                                            <th>Target Pages</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="notificationTableBody">
                                        <tr data-notification-id="N001">
                                            <td><input type="checkbox" class="notification-select" value="N001"></td>
                                            <td>N001</td>
                                            <td><span class="notification-type price-alert">Price Alert</span></td>
                                            <td>Fresh Tomatoes - Tanza Market</td>
                                            <td>Now ₱65/kg (was ₱85/kg) - Save 24%...</td>
                                            <td>All Pages</td>
                                            <td><span class="status-badge active">Active</span></td>
                                            <td>Oct 8, 2025</td>
                                            <td>
                                                <button class="btn-icon btn-edit" data-action="edit" data-id="N001" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon btn-toggle" data-action="toggle" data-id="N001" title="Toggle Status">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                                <button class="btn-icon btn-delete" data-action="delete" data-id="N001" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr data-notification-id="N002">
                                            <td><input type="checkbox" class="notification-select" value="N002"></td>
                                            <td>N002</td>
                                            <td><span class="notification-type price-alert">Price Alert</span></td>
                                            <td>Local Rice - Farmer's Direct</td>
                                            <td>Now ₱48/kg (was ₱55/kg) - Save 13%...</td>
                                            <td>All Pages</td>
                                            <td><span class="status-badge active">Active</span></td>
                                            <td>Oct 8, 2025</td>
                                            <td>
                                                <button class="btn-icon btn-edit" data-action="edit" data-id="N002" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon btn-toggle" data-action="toggle" data-id="N002" title="Toggle Status">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                                <button class="btn-icon btn-delete" data-action="delete" data-id="N002" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr data-notification-id="N003">
                                            <td><input type="checkbox" class="notification-select" value="N003"></td>
                                            <td>N003</td>
                                            <td><span class="notification-type market-update">Market Update</span></td>
                                            <td>Organic Apples - Limited Stock</td>
                                            <td>₱95/kg - Only 10kg left at Public Market</td>
                                            <td>All Pages</td>
                                            <td><span class="status-badge active">Active</span></td>
                                            <td>Oct 7, 2025</td>
                                            <td>
                                                <button class="btn-icon btn-edit" data-action="edit" data-id="N003" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon btn-toggle" data-action="toggle" data-id="N003" title="Toggle Status">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                                <button class="btn-icon btn-delete" data-action="delete" data-id="N003" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr data-notification-id="N004">
                                            <td><input type="checkbox" class="notification-select" value="N004"></td>
                                            <td>N004</td>
                                            <td><span class="notification-type promotion">Promotion</span></td>
                                            <td>Weekend Market Special</td>
                                            <td>Get 20% off on all fresh vegetables...</td>
                                            <td>Landing Page</td>
                                            <td><span class="status-badge scheduled">Scheduled</span></td>
                                            <td>Oct 9, 2025</td>
                                            <td>
                                                <button class="btn-icon btn-edit" data-action="edit" data-id="N004" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon btn-toggle" data-action="toggle" data-id="N004" title="Toggle Status">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                                <button class="btn-icon btn-delete" data-action="delete" data-id="N004" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr data-notification-id="N005">
                                            <td><input type="checkbox" class="notification-select" value="N005"></td>
                                            <td>N005</td>
                                            <td><span class="notification-type system">System</span></td>
                                            <td>Market Hours Update</td>
                                            <td>New market hours: 5:00 AM - 8:00 PM daily</td>
                                            <td>All Pages</td>
                                            <td><span class="status-badge inactive">Inactive</span></td>
                                            <td>Oct 5, 2025</td>
                                            <td>
                                                <button class="btn-icon btn-edit" data-action="edit" data-id="N005" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon btn-toggle" data-action="toggle" data-id="N005" title="Toggle Status">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                                <button class="btn-icon btn-delete" data-action="delete" data-id="N005" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Notification Form (Add/Edit) -->
                            <div class="notification-form-container" id="notificationFormContainer" style="display: none;">
                                <div class="form-header">
                                    <h3 id="notificationFormTitle">Add New Notification</h3>
                                    <button class="btn-icon" id="closeNotificationForm" title="Close">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                
                                <form id="notificationForm" class="notification-form">
                                    <input type="hidden" id="notificationId" name="notificationId">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="notificationType">Notification Type:</label>
                                            <select id="notificationType" name="notificationType" required>
                                                <option value="">Select Type</option>
                                                <option value="price-alert">Price Alert</option>
                                                <option value="market-update">Market Update</option>
                                                <option value="promotion">Promotion</option>
                                                <option value="system">System Message</option>
                                                <option value="maintenance">Maintenance Notice</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="notificationStatus">Status:</label>
                                            <select id="notificationStatus" name="notificationStatus" required>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="scheduled">Scheduled</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="notificationTitle">Notification Title:</label>
                                        <input type="text" id="notificationTitle" name="notificationTitle" placeholder="Enter notification title" required maxlength="100">
                                        <small class="form-help">Maximum 100 characters</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="notificationMessage">Notification Message:</label>
                                        <textarea id="notificationMessage" name="notificationMessage" placeholder="Enter detailed notification message" required maxlength="500" rows="4"></textarea>
                                        <small class="form-help">Maximum 500 characters</small>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="notificationIcon">Icon (FontAwesome class):</label>
                                            <select id="notificationIcon" name="notificationIcon" required>
                                                <option value="">Select Icon</option>
                                                <option value="fas fa-arrow-down">Price Drop (↓)</option>
                                                <option value="fas fa-arrow-up">Price Rise (↑)</option>
                                                <option value="fas fa-bell">General Alert (🔔)</option>
                                                <option value="fas fa-exclamation-triangle">Warning (⚠️)</option>
                                                <option value="fas fa-info-circle">Information (ℹ️)</option>
                                                <option value="fas fa-star">Featured (⭐)</option>
                                                <option value="fas fa-tag">Promotion (🏷️)</option>
                                                <option value="fas fa-clock">Time Sensitive (🕐)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="notificationPriority">Priority:</label>
                                            <select id="notificationPriority" name="notificationPriority" required>
                                                <option value="low">Low</option>
                                                <option value="medium" selected>Medium</option>
                                                <option value="high">High</option>
                                                <option value="urgent">Urgent</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="targetPages">Target Pages:</label>
                                        <div class="checkbox-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="targetPages" value="all" id="targetAll" checked>
                                                <span>All Pages</span>
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="targetPages" value="landing">
                                                <span>Landing Page</span>
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="targetPages" value="dashboard">
                                                <span>Price Dashboard</span>
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="targetPages" value="products">
                                                <span>Product Pages</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row" id="scheduleSection" style="display: none;">
                                        <div class="form-group">
                                            <label for="scheduleDate">Schedule Date:</label>
                                            <input type="date" id="scheduleDate" name="scheduleDate">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="scheduleTime">Schedule Time:</label>
                                            <input type="time" id="scheduleTime" name="scheduleTime">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="expiryDate">Expiry Date (Optional):</label>
                                        <input type="date" id="expiryDate" name="expiryDate">
                                        <small class="form-help">Leave empty for permanent notification</small>
                                    </div>
                                    
                                    <div class="notification-preview">
                                        <h4>Notification Preview:</h4>
                                        <div class="preview-notification" id="notificationPreview">
                                            <i class="fas fa-bell" id="previewIcon"></i>
                                            <div class="preview-content">
                                                <p id="previewTitle"><strong>Notification Title</strong></p>
                                                <small id="previewMessage">Notification message will appear here...</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-controls">
                                        <button type="button" class="btn-secondary" id="cancelNotificationForm">Cancel</button>
                                        <button type="submit" class="btn-primary" id="saveNotificationBtn">
                                            <i class="fas fa-save"></i> Save Notification
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Bulk Actions -->
                            <div class="notification-bulk-actions">
                                <select id="bulkNotificationActionSelect">
                                    <option value="">Bulk Actions</option>
                                    <option value="activate">Activate Selected</option>
                                    <option value="deactivate">Deactivate Selected</option>
                                    <option value="delete">Delete Selected</option>
                                    <option value="export-selected">Export Selected</option>
                                </select>
                                <button class="btn-sm btn-secondary" id="applyBulkNotificationAction">Apply</button>
                            </div>

                            <!-- Pagination -->
                            <div class="notification-pagination">
                                <button class="pagination-btn" disabled><i class="fas fa-chevron-left"></i></button>
                                <button class="pagination-btn active">1</button>
                                <button class="pagination-btn">2</button>
                                <button class="pagination-btn">3</button>
                                <span class="pagination-dots">...</span>
                                <button class="pagination-btn">5</button>
                                <button class="pagination-btn"><i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Floating Sidebar Toggle Button (Mobile Only) -->
    <button class="floating-sidebar-btn" id="floatingSidebarBtn" aria-label="Open menu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Mobile View CSS -->
    <link rel="stylesheet" href="CSS/admin-mobile-view.css">

    <!-- Floating Button & Modal Fix Styles -->
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
            }
            
            .floating-sidebar-btn i {
                font-size: 1.4rem !important;
                color: white !important;
            }
        }
        
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

    <script src="../JS/admin-sidebar.js"></script>
    <script src="../JS/admin-dashboard.js"></script>
</body>
</html>
