<?php


    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    session_start();
    require_once 'php/connection.php';
 if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }
   

    $userId = $_SESSION['user_id'];

    // get user and profile info
    $stmt = $conn->prepare("
        SELECT u.first_name, u.last_name, u.email, u.created_at,
            p.phone_number,  p.city, p.province, p.postal_code
        FROM users u
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE u.id = ? LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // check if business permit already exists
    $hasPermit = false;
    $permitData = [];

    $stmt = $conn->prepare("
        SELECT * FROM business_permits 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $permitData = $result->fetch_assoc();
        $hasPermit = true;
    }

        $query = "SELECT name, value FROM settings";
    $results = $conn->query($query);

    $settings = [];

    if ($results) {
        while ($row = $results->fetch_assoc()) {
            $settings[$row['name']] = $row['value'];
        }
    }

    $stmt->close();
    $conn->close();

    $isVerified = ($hasPermit && strtolower($permitData['verification_status']) === 'approved');
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#4a8c33">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Tanza">
    <title>Vendor Dashboard - Tanza Public Market</title>
    <link rel="stylesheet" href="CSS/vendor-dashboard.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="CSS/price-update-notification.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    .disabled-link {
    pointer-events: none; /* prevents clicking */
    opacity: 0.5;         /* visually indicates disabled state */
    cursor: not-allowed;
}

    /* Fix header to be above all other elements including sidebar */
    .header {
        position: fixed !important;
        z-index: 10200 !important;
    }
    
    .header .navbar {
        position: relative;
        z-index: 10200;
    }
    
    .header .nav-container {
        position: relative;
        z-index: 10200;
    }
    
    .header .nav-actions {
        position: relative;
        z-index: 10200;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .header .user-account-container,
    .header #headerAccountContainer {
        position: relative;
        z-index: 10200;
    }
    
    .header #headerAccountBtn {
        pointer-events: auto !important;
        cursor: pointer !important;
        position: relative;
        z-index: 10200;
    }
    
    .header .dropdown-menu {
        z-index: 10300 !important;
        position: absolute !important;
    }
    
    .header #notificationContainer {
        position: relative;
        z-index: 10200;
    }
    
    .header #notificationBell {
        pointer-events: auto !important;
        cursor: pointer !important;
        position: relative;
        z-index: 10200;
    }
    
    .header #notificationDropdown {
        z-index: 10300 !important;
    }
    
    /* Mobile responsive styles */
    @media (max-width: 992px) {
        .dashboard-container {
            flex-direction: column;
        }
        
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        .content-wrapper {
            padding: 15px !important;
        }
        
        /* Hide nav menu on mobile */
        .nav-menu {
            display: none !important;
        }
    }
    
    @media (max-width: 576px) {
        .nav-container {
            padding: 0 10px;
        }
        
        .logo span {
            display: none;
        }
    }

</style>
<body>
    <!-- Header Section -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <!-- Logo Left Side -->
                <div class="logo">
                    <a href="pricefront.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-seedling"></i>
                        <span><?php echo $settings['system_name'];?></span>
                    </a>
                </div>
                
                <!-- Navigation Center -->
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a href="pricefront.php"  style="color: black; text-decoration: none;">PRICES</a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php#weather" class="nav-link">WEATHER</a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php#about" class="nav-link">ABOUT</a>
                    </li>
                </ul>
                
                <!-- Right Side Actions -->
                <div class="nav-actions">
                   <div class="position-relative" id="notificationContainer">
                        <button class="btn btn-outline-primary position-relative rounded-circle shadow-sm" id="notificationBell"
                                style="width:45px;height:45px;">
                            <i class="fas fa-bell fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                id="notificationBadge"></span>
                        </button>

                        <div id="notificationDropdown"
                            class="card border-0 shadow-lg position-absolute end-0 mt-3 rounded-4 overflow-hidden"
                            style="width:400px; display:none; z-index:2000;">
                        </div>
                    </div>
                    <div class="user-account-container" id="headerAccountContainer">
                        <div class="dropdown">
                            <button class="btn btn-light d-flex align-items-center" id="headerAccountBtn" data-bs-toggle="dropdown" aria-expanded="false" style="padding:6px 10px;">
                                <i class="fas fa-user-circle"></i>
                                <span class="ms-1"><?php echo htmlspecialchars(($user['first_name'] ?? '')) ?></span>
                                <i class="fas fa-caret-down ms-1"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="headerAccountBtn">
                                <li><a class="dropdown-item" href="vendor-profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="php/logout.php">Logout</a></li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </nav>
    </header>

    <!-- Mobile Menu Toggle Button -->
    <button class="toggle-sidebar" id="toggleSidebar" aria-label="Toggle sidebar menu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            
            <!-- Navigation Links -->
            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <li class="nav-item active">
                        <a href="vendor-dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>DASHBOARD</span>
                        </a>
                    </li>
                    <li class="nav-item ">
                        <a href="vendor-business-permit.php" class="nav-link">
                            <i class="fas fa-certificate"></i>
                            <span>BUSINESS PERMIT</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="vendor-events.php" 
                        class="nav-link <?php echo !$isVerified ? 'disabled-link' : ''; ?>" 
                        <?php echo !$isVerified ? 'onclick="return false;"' : ''; ?>>
                            <i class="fas fa-calendar-alt"></i>
                            <span>EVENTS</span>
                        </a>
                    </li>
                    <li class="nav-item ">
                            <a href="vendor-notices.php" class="nav-link <?php echo !$isVerified ? 'disabled-link' : ''; ?>" <?php echo !$isVerified ? 'onclick="return false;"' : ''; ?>>
                                <i class="fas fa-bullhorn"></i>
                                <span>NOTICES</span>
                            </a>
                        </li>

                    <li class="nav-item">
                        <a href="vendor-survey-form.php" 
                        class="nav-link <?php echo !$isVerified ? 'disabled-link' : ''; ?>" 
                        <?php echo !$isVerified ? 'onclick="return false;"' : ''; ?>>
                            <i class="fas fa-clipboard-list"></i>
                            <span>SUBMIT SURVEY RESPONSE</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="vendor-cleaning.php" 
                        class="nav-link <?php echo !$isVerified ? 'disabled-link' : ''; ?>" 
                        <?php echo !$isVerified ? 'onclick="return false;"' : ''; ?>>
                            <i class="fas fa-broom"></i>
                            <span>REQUEST CLEANING</span>
                        </a>
                    </li>

                </ul>
            </nav>
            
            <!-- Logout at the bottom -->
            <div class="sidebar-footer">
                <a href="/php/logout.php" id="logoutBtn" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>LOG OUT</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Top Navigation Bar -->
            <header class="top-navbar">
                <div class="date-time">
                    <span id="currentDate"></span>
                </div>
            </header>

            
            <!-- Logout Modal -->
            <!-- <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Logout</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        Are you sure you want to log out?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmLogout">Yes, Log Out</button>
                    </div>
                    </div>
                </div>
            </div> -->

            <!-- Dashboard Content -->
            <div class="content-wrapper">
                <!-- Verification Banner for Unverified Vendors -->
                <div id="verificationBanner" class="verification-banner" style="display: none;">
                    <div class="banner-content">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="banner-text">
                            <h4>Account Verification Required</h4>
                            <p>Please upload your business permit to access all features. <a href="#business-permit" class="banner-link">Upload Now</a></p>
                        </div>
                    </div>
                    <button class="banner-close" id="closeBanner">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <section class="content-section active" id="dashboard-section">
                    <div class="section-header">
                        <h2>Vendor Dashboard</h2>
                        <p>Welcome back, <span><?php echo htmlspecialchars(($user['first_name'] ?? '')) ?></span>! </p>
                    </div>
                    
                    <!-- Stats Overview Cards 
                    <div class="stats-cards">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <h3>Today's Sales</h3>
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-card-body">
                                <h2>₱12,450</h2>
                                <p class="stat-trend positive">+8.5% from yesterday</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <h3></h3>
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div class="stat-card-body">
                                <h2>24 Items</h2>
                                <p class="stat-info">3 items low on stock</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <h3></h3>
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-card-body">
                                <h2>85</h2>
                                <p class="stat-trend positive">+12% from yesterday</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <h3></h3>
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="stat-card-body">
                                <h2></h2>
                                <p class="stat-info">Top 5% of produce vendors</p>
                            </div>
                        </div>
                    </div> IGNORE THIS -->
                    
                    <!-- Market Announcements Section -->
                    <div class="dashboard-sections">
                        <div class="dashboard-section full-width">
                            <h3>Market Announcements</h3>
                            <div class="announcements-list">
                                <div class="announcement-item">
                                    <div class="announcement-icon">
                                        <i class="fas fa-bullhorn"></i>
                                    </div>
                                    <div class="announcement-details">
                                        <h4>Market Cleaning Day</h4>
                                        <p>Please be reminded that request cleaning should be done 1 day before the desired date.</p>
                                    </div>
                                </div>
                                
                                <div class="announcement-item">
                                    <div class="announcement-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="announcement-details">
                                        <h4>Event Planning</h4>
                                        <p>Eligible Vendors are highly encourage to attend events planned by Department of Agriculture. </p>
                                    </div>
                                </div>
                                
                                <div class="announcement-item">
                                    <div class="announcement-icon">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                    <div class="announcement-details">
                                        <h4>Price Reporting Reminder</h4>
                                        <p>All vendors must submit updated price lists by end of day every Monday and Thursday.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
               

                <section class="content-section" id="profile-section">
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="profile-banner"></div>
                        <div class="profile-info-header">
                            <div class="profile-avatar">
                                <img src="" alt="Profile Photo" id="profileAvatarImg">
                                <button class="avatar-edit-btn" id="changeAvatarBtn">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <div class="profile-basic-info">
                                <h1 id="profileFullName"></h1>
                                <p class="profile-role"><i class="fas fa-store"></i> Produce Vendor</p>
                                <p class="profile-member-since"><i class="fas fa-calendar"></i> Member since <?php echo htmlspecialchars(($user['created_at'] ?? '')) ?></p>
                            </div>
                            <div class="profile-actions">
                                <button class="btn-edit-profile" id="editProfileBtn">
                                    <i class="fas fa-edit"></i> Edit Profile
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Content -->
                    <div class="profile-content">
                        <!-- Personal Information Card -->
                        <div class="profile-card">
                            <div class="profile-card-header">
                                <h3><i class="fas fa-user"></i> Personal Information</h3>
                            </div>
                            <div class="profile-card-body">
                                <div class="profile-info-grid">
                                    <div class="profile-info-item">
                                        <label>First Name</label>
                                        <p id="displayFirstName">Maria</p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Last Name</label>
                                        <p id="displayLastName">Santos</p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Email Address</label>
                                        <p id="displayEmail">maria.santos@gmail.com</p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Phone Number</label>
                                        <p id="displayPhone">+63 915 555 1234</p>
                                    </div>
                                    <div class="profile-info-item full-width">
                                        <label>Home Address</label>
                                        <p id="displayAddress">123 Market Street, Tanza, Cavite 4108</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vendor Information Card -->
                        <div class="profile-card">
                            <div class="profile-card-header">
                                <h3><i class="fas fa-store-alt"></i> Vendor Information</h3>
                            </div>
                            <div class="profile-card-body">
                                <div class="profile-info-grid">
                                    <div class="profile-info-item">
                                        <label>Stall Number</label>
                                        <p id="displayStallNumber">A-15</p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Vendor Type</label>
                                        <p id="displayVendorType">Produce Vendor</p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Business Name</label>
                                        <p id="displayBusinessName">Maria's Fresh Produce</p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Verification Status</label>
                                        <p>
                                            <span class="status-badge pending" id="displayVerificationStatus">
                                                <i class="fas fa-clock"></i> Pending Verification
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Emergency Contact Card -->
                        <div class="profile-card">
                            <div class="profile-card-header">
                                <h3><i class="fas fa-phone-alt"></i> Emergency Contact</h3>
                            </div>
                            <div class="profile-card-body">
                                <div class="profile-info-grid">
                                    <div class="profile-info-item">
                                        <label>Contact Name</label>
                                        <p id="displayEmergencyName">Juan Santos</p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Relationship</label>
                                        <p id="displayEmergencyRelationship">Spouse</p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Contact Number</label>
                                        <p id="displayEmergencyPhone">+63 917 555 4321</p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Alternate Number</label>
                                        <p id="displayEmergencyAltPhone">+63 922 555 8765</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Account Settings Card -->
                        <div class="profile-card">
                            <div class="profile-card-header">
                                <h3><i class="fas fa-cog"></i> Account Settings</h3>
                            </div>
                            <div class="profile-card-body">
                                <div class="settings-list">
                                    <div class="settings-item">
                                        <div class="settings-info">
                                            <i class="fas fa-key"></i>
                                            <div>
                                                <h4>Change Password</h4>
                                                <p>Update your password to keep your account secure</p>
                                            </div>
                                        </div>
                                        <button class="btn-settings" id="changePasswordBtn">Change</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <section class="content-section" id="events-section">
                    <div class="section-header">
                        <h2>Market Events</h2>
                        <p>Upcoming events and activities at Tanza Public Market.</p>
                    </div>
                    <div class="section-content">
                        <!-- Events Categories Tabs -->
                        <div class="events-tabs">
                            <button class="event-tab active" data-tab="all">All Events</button>
                            <button class="event-tab" data-tab="face-to-face">Face-to-Face</button>
                            <button class="event-tab" data-tab="online">Online/Webinar</button>
                        </div>

                        <div class="events-container">
                            <!-- Face-to-Face Events -->
                            <div class="event-card face-to-face">
                                <div class="event-header">
                                    <span class="event-badge face-to-face">Face-to-Face</span>
                                    <h3 class="event-title">Harvest Festival 2025</h3>
                                </div>
                                <div class="event-details">
                                    <div class="event-info">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>October 10-12, 2025 | 8:00 AM - 8:00 PM</span>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>Tanza Public Market, Main Square</span>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Annual celebration showcasing local farmers' produce with cultural performances, cooking demonstrations, and promotional sales.</span>
                                    </div>
                                    <div class="event-actions">
                                        <a href="#register" class="event-action-btn">Register Stall</a>
                                        <a href="#details" class="event-action-link">View Details</a>
                                    </div>
                                </div>
                            </div>

                            <div class="event-card face-to-face">
                                <div class="event-header">
                                    <span class="event-badge face-to-face">Face-to-Face</span>
                                    <h3 class="event-title">Vendor Training Workshop</h3>
                                </div>
                                <div class="event-details">
                                    <div class="event-info">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>September 28, 2025 | 1:00 PM - 5:00 PM</span>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>Tanza Public Market, Conference Room B</span>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Hands-on training for market vendors on produce display techniques, food safety, and customer service excellence.</span>
                                    </div>
                                    <div class="event-actions">
                                        <a href="#register" class="event-action-btn">Register</a>
                                        <a href="#details" class="event-action-link">View Details</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="event-card face-to-face">
                                <div class="event-header">
                                    <span class="event-badge face-to-face">Face-to-Face</span>
                                    <h3 class="event-title">Local Producers Fair</h3>
                                </div>
                                <div class="event-details">
                                    <div class="event-info">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>October 5, 2025 | 9:00 AM - 4:00 PM</span>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>Tanza Public Market, East Wing</span>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Special event connecting local farmers directly with customers. Featuring unique produce and farm products from Cavite region.</span>
                                    </div>
                                    <div class="event-actions">
                                        <a href="#register" class="event-action-btn">Apply for Booth</a>
                                        <a href="#details" class="event-action-link">View Details</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Online/Webinar Events -->
                            <div class="event-card online">
                                <div class="event-header">
                                    <span class="event-badge online">Online/Webinar</span>
                                    <h3 class="event-title">Digital Marketing for Market Vendors</h3>
                                </div>
                                <div class="event-details">
                                    <div class="event-info">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>September 30, 2025 | 7:00 PM - 8:30 PM</span>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-link"></i>
                                        <a href="https://zoom.us/j/marketvendors" target="_blank">https://zoom.us/j/marketvendors</a>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Learn how to promote your products online, use social media effectively, and reach more customers through digital platforms.</span>
                                    </div>
                                    <div class="event-actions">
                                        <a href="#register" class="event-action-btn">Register</a>
                                        <a href="#download" class="event-action-link">Download Materials</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="event-card online">
                                <div class="event-header">
                                    <span class="event-badge online">Online/Webinar</span>
                                    <h3 class="event-title">Sustainable Farming Practices Webinar</h3>
                                </div>
                                <div class="event-details">
                                    <div class="event-info">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>October 7, 2025 | 6:00 PM - 7:30 PM</span>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-link"></i>
                                        <a href="https://meet.google.com/sustainable-farming" target="_blank">https://meet.google.com/sustainable-farming</a>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Expert-led session on eco-friendly farming methods, organic pest control, and maximizing crop yields sustainably.</span>
                                    </div>
                                    <div class="event-actions">
                                        <a href="#register" class="event-action-btn">Register</a>
                                        <a href="#details" class="event-action-link">View Details</a>
                                    </div>
                                </div>
                            </div>

                            <div class="event-card online">
                                <div class="event-header">
                                    <span class="event-badge online">Online/Webinar</span>
                                    <h3 class="event-title">Market Vendor Financial Management</h3>
                                </div>
                                <div class="event-details">
                                    <div class="event-info">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>October 15, 2025 | 7:00 PM - 9:00 PM</span>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-link"></i>
                                        <a href="https://webinar.farmfresh.com/financial-mgmt" target="_blank">https://webinar.farmfresh.com/financial-mgmt</a>
                                    </div>
                                    <div class="event-info">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Essential financial skills for vendors: bookkeeping, managing cash flow, pricing strategies, and saving for business growth.</span>
                                    </div>
                                    <div class="event-actions">
                                        <a href="#register" class="event-action-btn">Register</a>
                                        <a href="#download" class="event-action-link">Download Workbook</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <section class="content-section" id="notices-section">
                    <div class="section-header">
                        <h2>Market Notices</h2>
                        <p>Important notices, reports, and complaints.</p>
                        
                        <!-- Status Legend -->
                        <div class="vendor-status-legend">
                            <div class="legend-item">
                                <span class="legend-indicator suspended"></span>
                                <span class="legend-label">Suspended</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-indicator complaint"></span>
                                <span class="legend-label">Has Complaint</span>
                            </div>
                        </div>
                    </div>
                    <div class="section-content">
                        <!-- Notice Filters -->
                        <div class="notice-filters">
                            <div class="filter-group">
                                <label for="noticeTypeFilter">Filter by Type:</label>
                                <select id="noticeTypeFilter">
                                    <option value="all">All Types</option>
                                    <option value="complaint">Complaints</option>
                                    <option value="report">Reports</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="noticeDateFilter">Filter by Date:</label>
                                <select id="noticeDateFilter">
                                    <option value="all">All Dates</option>
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                </select>
                            </div>
                            <div class="filter-group search">
                                <input type="text" id="noticeSearchInput" placeholder="Search notices...">
                                <button class="search-btn"><i class="fas fa-search"></i></button>
                            </div>
                        </div>

                        <!-- Notices List -->
                        <div class="notices-container">
                            <!-- Complaint -->
                            <div class="notice-item complaint">
                                <div class="notice-header">
                                    <span class="notice-badge complaint">Complaint</span>
                                    <h3 class="notice-title">Customer Complaint: Product Quality</h3>
                                    <span class="notice-date">September 22, 2025</span>
                                </div>
                                <div class="notice-content">
                                    <p>A customer has reported receiving overripe mangoes from your stall. Please review your quality control process for fresh produce.</p>
                                    <div class="notice-actions">
                                        <a href="#respond" class="notice-action">Respond</a>
                                        <a href="#details" class="notice-action">View Details</a>
                                        <span class="notice-status read">Read</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Report -->
                            <div class="notice-item report">
                                <div class="notice-header">
                                    <span class="notice-badge report">Report</span>
                                    <h3 class="notice-title">Monthly Sales Report</h3>
                                    <span class="notice-date">September 20, 2025</span>
                                </div>
                                <div class="notice-content">
                                    <p>Your monthly sales report for August 2025 is now available. Your sales increased by 12% compared to the previous month.</p>
                                    <div class="notice-actions">
                                        <a href="#download" class="notice-action">Download Report</a>
                                        <span class="notice-status read">Read</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Complaint -->
                            <div class="notice-item complaint">
                                <div class="notice-header">
                                    <span class="notice-badge complaint">Complaint</span>
                                    <h3 class="notice-title">Neighbor Stall: Noise Complaint</h3>
                                    <span class="notice-date">September 10, 2025</span>
                                </div>
                                <div class="notice-content">
                                    <p>A neighboring vendor has complained about excessive noise from your sound system during morning hours. Please ensure volume levels comply with market regulations.</p>
                                    <div class="notice-actions">
                                        <a href="#respond" class="notice-action">Respond</a>
                                        <a href="#details" class="notice-action">View Details</a>
                                        <span class="notice-status read">Read</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Report -->
                            <div class="notice-item report">
                                <div class="notice-header">
                                    <span class="notice-badge report">Report</span>
                                    <h3 class="notice-title">Inventory Audit Results</h3>
                                    <span class="notice-date">September 5, 2025</span>
                                </div>
                                <div class="notice-content">
                                    <p>Results from your quarterly inventory audit are now available. There are some discrepancies that require your attention.</p>
                                    <div class="notice-actions">
                                        <a href="#download" class="notice-action">View Report</a>
                                        <span class="notice-status read">Read</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div class="notices-pagination">
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn">2</button>
                            <button class="pagination-btn">3</button>
                            <span class="pagination-separator">...</span>
                            <button class="pagination-btn">8</button>
                            <button class="pagination-btn next"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </section>
                
                <section class="content-section" id="survey-section">
                    <div class="section-header">
                        <h2>Submit Survey Response</h2>
                        <p>Provide feedback about market operations and conditions.</p>
                    </div>
                    <!-- Verification Required Message -->
                    <div class="verification-required-overlay" id="surveyVerificationOverlay" style="display: none;">
                        <div class="verification-message-box">
                            <i class="fas fa-lock" style="font-size: 48px; color: #f39c12; margin-bottom: 20px;"></i>
                            <h3>Verification Required</h3>
                            <p>You need to be verified before you can access the survey form.</p>
                            <p>Please upload your business permit to complete the verification process.</p>
                            <a href="#business-permit" class="btn-verify" onclick="navigateToBusinessPermit()">
                                <i class="fas fa-file-upload"></i> Upload Business Permit
                            </a>
                        </div>
                    </div>
                    <!-- Survey Content will go here -->
                    <div class="section-content" id="surveyContent">
                        <p>Survey response form will be implemented here.</p>
                    </div>
                </section>
                
                <section class="content-section" id="cleaning-section">
                    <div class="section-header">
                        <h2>Request Cleaning</h2>
                        <p>Submit a cleaning request for your stall or surrounding area.</p>
                    </div>
                    <!-- Verification Required Message -->
                    <div class="verification-required-overlay" id="cleaningVerificationOverlay" style="display: none;">
                        <div class="verification-message-box">
                            <i class="fas fa-lock" style="font-size: 48px; color: #f39c12; margin-bottom: 20px;"></i>
                            <h3>Verification Required</h3>
                            <p>You need to be verified before you can submit cleaning requests.</p>
                            <p>Please upload your business permit to complete the verification process.</p>
                            <a href="#business-permit" class="btn-verify" onclick="navigateToBusinessPermit()">
                                <i class="fas fa-file-upload"></i> Upload Business Permit
                            </a>
                        </div>
                    </div>
                    <!-- Cleaning Request Status Section -->
                    <div class="section-content" id="cleaningContent">
                        <div class="cleaning-status-section">
                            <h3>My Cleaning Requests</h3>
                            <div id="cleaningRequestsStatus" class="cleaning-requests-status">
                                <!-- Cleaning request statuses will be loaded here -->
                                <div class="no-requests-message">
                                    <i class="fas fa-broom"></i>
                                    <p>No cleaning requests submitted yet.</p>
                                    <small>Submit your first cleaning request below.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Simplified Cleaning Request Form -->
                        <div class="cleaning-form-section">
                            <h3>Submit New Cleaning Request</h3>
                            <form class="cleaning-request-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="requestType">Cleaning Request Type</label>
                                    <select id="requestType" name="requestType" required>
                                        <option value="" disabled selected>Select request type</option>
                                        <option value="regular">Regular Stall Cleaning</option>
                                        <option value="deep">Deep Cleaning Service</option>
                                        <option value="emergency">Emergency Clean-up</option>
                                        <option value="pest">Pest Control</option>
                                        <option value="waste">Waste Disposal</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="preferredDate">Preferred Date</label>
                                    <input type="date" id="preferredDate" name="preferredDate" min="2025-10-04" required>
                                </div>
                                <div class="form-group">
                                    <label for="preferredTime">Preferred Time</label>
                                    <select id="preferredTime" name="preferredTime" required>
                                        <option value="" disabled selected>Select preferred time</option>
                                        <option value="early">Early Morning (4:00 AM - 6:00 AM)</option>
                                        <option value="morning">Morning (8:00 AM - 10:00 AM)</option>
                                        <option value="afternoon">Afternoon (2:00 PM - 4:00 PM)</option>
                                        <option value="evening">Evening (8:00 PM - 10:00 PM)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="stallNumber">Stall Number</label>
                                    <input type="text" id="stallNumber" name="stallNumber" placeholder="Stall Number" value="A-15" required>
                                </div>
                                <div class="form-group">
                                    <label for="marketSection">Market Section</label>
                                    <select id="marketSection" name="marketSection" required>
                                        <option value="" disabled selected>Select market section</option>
                                        <option value="sectionA">Section A - Produce</option>
                                        <option value="sectionB">Section B - Meat/Fish</option>
                                        <option value="sectionC">Section C - Dry Goods</option>
                                        <option value="sectionD">Section D - Mixed Items</option>
                                        <option value="sectionE">Section E - Food Stalls</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="requestDescription">Additional Details (Optional)</label>
                                    <textarea id="requestDescription" name="requestDescription" rows="3" placeholder="Please describe any specific cleaning needs or areas of concern..."></textarea>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn-secondary" id="resetCleaningForm">Clear Form</button>
                                <button type="submit" class="btn-primary" id="submitCleaningRequest">Submit Request</button>
                            </div>
                        </form>
                        </div>
                    </div>
                </section>
                
                <!-- Business Permit Section -->
                <section class="content-section" id="business-permit-section">
                    <div class="section-header">
                        <h2><i class="fas fa-certificate"></i> Business Permit Verification</h2>
                        <p>Upload your business permit for account verification.</p>
                    </div>
                    <div class="section-content">
                        <div class="business-permit-container">
                            <!-- Current Status Card -->
                            <div class="permit-status-card">
                                <h3>Current Verification Status</h3>
                                <div class="status-display" id="permitStatusDisplay">
                                    <div class="status-icon pending">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="status-info">
                                        <h4 id="permitStatusTitle">Pending Verification</h4>
                                        <p id="permitStatusMessage">No business permit uploaded yet. Please upload your permit to get verified.</p>
                                        <span class="status-badge pending" id="permitStatusBadge">Not Verified</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Upload Form -->
                            <?php if (!$hasPermit): ?>
                            <div class="permit-upload-card" id="permitUploadCard">
                                <h3>Upload Business Permit</h3>
                                <form class="permit-upload-form" id="businessPermitForm"
                                  enctype="multipart/form-data"
                                  method="POST" action="php/save_business_permit.php">
                                    <input type="hidden" name="userId" value="<?php echo $_SESSION['user_id']; ?>">


                                    <div class="upload-area" id="uploadArea">
                                        <div class="upload-placeholder">
                                            <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #27ae60; margin-bottom: 15px;"></i>
                                            <h4>Drag & Drop or Click to Upload</h4>
                                            <p>Upload your business permit (PDF, JPG, PNG - Max 5MB)</p>
                                            <input type="file" id="businessPermitFile" name="businessPermit" accept="image/*,.pdf" style="display: none;">
                                            <button type="button" class="btn-upload" onclick="document.getElementById('businessPermitFile').click()">
                                                <i class="fas fa-folder-open"></i> Choose File
                                            </button>
                                        </div>
                                        <div class="file-preview" id="filePreview" style="display: none;">
                                            <i class="fas fa-file-alt" style="font-size: 36px; color: #27ae60;"></i>
                                            <p id="fileName"></p>
                                            <p id="fileSize" style="font-size: 0.85rem; color: #666;"></p>
                                            <button type="button" class="btn-remove" id="removeFile">
                                                <i class="fas fa-times"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="permitNumber">Business Permit Number</label>
                                        <input type="text" id="permitNumber" name="permitNumber" class="form-input" placeholder="Enter your permit number" required>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="permitIssueDate">Issue Date</label>
                                            <input type="date" id="permitIssueDate" name="issueDate" class="form-input" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="permitExpiryDate">Expiry Date</label>
                                            <input type="date" id="permitExpiryDate" name="expiryDate" class="form-input" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="businessName">Business Name</label>
                                        <input type="text" id="businessName" name="businessName" class="form-input" placeholder="Enter your business name" required>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="button" class="btn-secondary" id="cancelPermitUpload">Cancel</button>
                                        <button type="submit" class="btn-primary" id="submitPermit">
                                            <i class="fas fa-paper-plane"></i> Submit for Verification
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <?php else: ?>
                            <!-- Uploaded Permit Display -->
                            <div class="permit-display-card" id="permitDisplayCard">
                                <h3>Uploaded Business Permit</h3>
                            <div class="permit-details">
                                <div class="permit-image-container">
                                    <?php if (preg_match('/\.(jpg|jpeg|png)$/i', $permit['file_name'])): ?>
                                        <img src="<?php echo htmlspecialchars($permit['file_path']); ?>" alt="Business Permit" style="max-width:100%;border-radius:10px;">
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($permit['file_path']); ?>" target="_blank">View PDF</a>
                                    <?php endif; ?>
                                    </div>
                                    <div class="permit-info">
                                    <div class="info-row"><strong>Permit Number:</strong> <span><?php echo htmlspecialchars($permit['permit_number']); ?></span></div>
                                    <div class="info-row"><strong>Business Name:</strong> <span><?php echo htmlspecialchars($permit['business_name']); ?></span></div>
                                    <div class="info-row"><strong>Issue Date:</strong> <span><?php echo htmlspecialchars($permit['issue_date']); ?></span></div>
                                    <div class="info-row"><strong>Expiry Date:</strong> <span><?php echo htmlspecialchars($permit['expiry_date']); ?></span></div>
                                    <div class="info-row"><strong>Uploaded On:</strong> <span><?php echo htmlspecialchars($permit['created_at']); ?></span></div>
                                </div>
                            </div>
                            <button type="button" class="btn-secondary" id="updatePermit">
                                <i class="fas fa-edit"></i> Update Permit
                            </button>
                            </div>
                            
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
    
    <!-- Shared JS for all vendor pages -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="JS/vendor-dashboard-shared.js" defer></script>
    <!--<script src="../JS/vendor-access-control.js"></script>
    <script src="../JS/vendor-dashboard.js"></script>-->
    <!--<script src="../JS/business-permit-handler.js"></script>
    <script src="../JS/profile-view.js"></script>-->
        <script src="../JS/price-update-notification.js" defer></script>
        <script src="../JS/notification.js"></script>
    
    <script>
        document.getElementById("logoutBtn").addEventListener("click", function(e) {
            e.preventDefault();
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "php/logout.php";
            }
        });


        document.getElementById('updatePermit')?.addEventListener('click', async () => {
            const res = await fetch('php/get_business_permit.php');
            const data = await res.json();

            if (!data.success) return alert('Failed to load permit data.');

            document.getElementById('permitDisplayCard').style.display = 'none';
            document.getElementById('permitUploadCard').style.display = 'block';

            document.getElementById('permitNumber').value = data.permit.permit_number;
            document.getElementById('permitIssueDate').value = data.permit.issue_date;
            document.getElementById('permitExpiryDate').value = data.permit.expiry_date;
            document.getElementById('businessName').value = data.permit.business_name;

            // change submit button text
            document.getElementById('submitPermit').innerHTML = '<i class="fas fa-save"></i> Update Permit';
            document.getElementById('businessPermitForm').action = 'php/update_business_permit.php';
        });
    </script>

        <script>
            // Populate current date in header
            document.addEventListener('DOMContentLoaded', function(){
                const el = document.getElementById('currentDate');
                if (!el) return;
                const today = new Date();
                const opts = { year: 'numeric', month: 'long', day: 'numeric' };
                el.textContent = today.toLocaleDateString(undefined, opts);
            });
        </script>

</body>
</html>
