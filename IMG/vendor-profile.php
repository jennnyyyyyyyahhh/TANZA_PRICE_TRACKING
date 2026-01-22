<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    session_start();
    require_once 'php/connection.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.html");
        exit;
    }

    $userId = $_SESSION['user_id'];

    // get user and profile info
    $stmt = $conn->prepare("
    SELECT 
        u.first_name, 
        u.last_name, 
        u.email, 
        u.created_at,
        p.phone_number, 
        p.barangay, 
        p.city, 
        p.province, 
        p.postal_code,
        p.profile_image_path,
        p.date_of_birth,
        p.gender,
        e.contact_name AS emergency_name,
        e.relationship AS emergency_relationship,
        e.contact_number AS emergency_phone,
        e.alt_contact_number AS emergency_alt_phone
    FROM users u
    LEFT JOIN user_profiles p ON u.id = p.user_id
    LEFT JOIN emergency_contacts e ON u.id = e.user_id
    WHERE u.id = ?
    LIMIT 1
");


    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    function format_phone($number) {
    $digits = preg_replace('/\D/', '', $number); // keep only numbers
    if (strpos($digits, '63') === 0) {
        $digits = '+' . $digits;
    } elseif (strpos($digits, '0') === 0) {
        $digits = '+63' . substr($digits, 1);
    } elseif (strpos($digits, '9') === 0) {
        $digits = '+63' . $digits;
    } else {
        return htmlspecialchars($number ?? '');
    }
    return preg_replace('/(\+63)(\d{3})(\d{3})(\d{4})/', '$1 $2 $3 $4', $digits);
}
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
    <link rel="stylesheet" href="../CSS/vendor-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body>
<?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3"
         role="alert" style="z-index:1050;min-width:250px;">
        Profile updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php elseif (isset($_GET['password_updated']) && $_GET['password_updated'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3"
         role="alert" style="z-index:1050;min-width:250px;">
        Password changed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php elseif (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'wrong_old_password'): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3"
             role="alert" style="z-index:1050;min-width:250px;">
            Incorrect old password.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($_GET['error'] === 'password_mismatch'): ?>
        <div class="alert alert-warning alert-dismissible fade show position-fixed top-0 end-0 m-3"
             role="alert" style="z-index:1050;min-width:250px;">
            New passwords do not match.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($_GET['error'] === 'empty_fields'): ?>
        <div class="alert alert-warning alert-dismissible fade show position-fixed top-0 end-0 m-3"
             role="alert" style="z-index:1050;min-width:250px;">
            Please fill in all required fields.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['updated']) || isset($_GET['password_updated']) || isset($_GET['error'])): ?>
    <script>
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 3000);
    </script>
<?php endif; ?>
<?php if (isset($_GET['password_updated']) && $_GET['password_updated'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" 
         role="alert" style="z-index: 1050; min-width: 250px;">
        Password changed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php elseif (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'wrong_old_password'): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" 
             role="alert" style="z-index: 1050; min-width: 250px;">
            Incorrect old password.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($_GET['error'] === 'password_mismatch'): ?>
        <div class="alert alert-warning alert-dismissible fade show position-fixed top-0 end-0 m-3" 
             role="alert" style="z-index: 1050; min-width: 250px;">
            New passwords do not match.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($_GET['error'] === 'empty_fields'): ?>
        <div class="alert alert-warning alert-dismissible fade show position-fixed top-0 end-0 m-3" 
             role="alert" style="z-index: 1050; min-width: 250px;">
            Please fill in all required fields.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['password_updated']) || isset($_GET['error'])): ?>
    <script>
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 3000);
    </script>
<?php endif; ?>


    <!-- Header Section -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <!-- Logo Left Side -->
                <div class="logo">
                    <a href="pricefront.html" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-seedling"></i>
                        <span>Tanza Public Market</span>
                    </a>
                </div>
                
                <!-- Navigation Center -->
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="landingtrial.html" class="nav-link">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a href="landingtrial.html#weather" class="nav-link">WEATHER</a>
                    </li>
                    <li class="nav-item">
                        <a href="landingtrial.html#about" class="nav-link">ABOUT</a>
                    </li>
                </ul>
                
                <!-- Right Side Actions -->
                <div class="nav-actions">
                    <div class="notification-container">
                        <i class="fas fa-bell notification-bell" id="notificationBell"></i>
                        <span class="notification-badge" id="notificationBadge">5</span>
                        <!-- Notification Dropdown -->
                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <h4>Notifications</h4>
                                <span class="mark-read" id="markAllRead">Mark all as read</span>
                            </div>
                            <div id="cleaningNotifications">
                                <!-- Cleaning request notifications will be inserted here -->
                            </div>
                            <div class="notification-separator">
                                <h5>Market Price Alerts</h5>
                            </div>
                            <div class="notification-item">
                                <i class="fas fa-arrow-down price-drop"></i>
                                <div class="notification-content">
                                    <p><strong>Fresh Tomatoes - Tanza Market</strong></p>
                                    <small>Now ₱65/kg (was ₱85/kg) - Save 24% at Local Farm</small>
                                </div>
                            </div>
                            <div class="notification-item">
                                <i class="fas fa-arrow-down price-drop"></i>
                                <div class="notification-content">
                                    <p><strong>Local Rice - Farmer's Direct</strong></p>
                                    <small>Now ₱48/kg (was ₱55/kg) - Save 13% from Laguna Farms</small>
                                </div>
                            </div>
                            <div class="notification-item">
                                <i class="fas fa-bell price-alert"></i>
                                <div class="notification-content">
                                    <p><strong>Organic Apples - Limited Stock</strong></p>
                                    <small>₱95/kg - Only 10kg left at Public Market</small>
                                </div>
                            </div>
                            <div class="notification-item">
                                <i class="fas fa-arrow-down price-drop"></i>
                                <div class="notification-content">
                                    <p><strong>Ripe Bananas - Morning Harvest</strong></p>
                                    <small>Now ₱35/kg (was ₱45/kg) - Fresh from Batangas Farms</small>
                                </div>
                            </div>
                            <div class="notification-item">
                                <i class="fas fa-arrow-down price-drop"></i>
                                <div class="notification-content">
                                    <p><strong>Carrots - Bulk Discount</strong></p>
                                    <small>Now ₱50/kg (was ₱65/kg) - Highland Vegetables</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="user-account-container" id="headerAccountContainer">
                        <a href="vendor-dashboard.php#profile-section" class="btn-user-account" id="headerAccountBtn">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars(($user['first_name'] ?? '')) ?></span>
                        </a>
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
                        <a href="#dashboard" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>DASHBOARD</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#business-permit" class="nav-link">
                            <i class="fas fa-certificate"></i>
                            <span>BUSINESS PERMIT</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#events" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>EVENTS</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#notices" class="nav-link">
                            <i class="fas fa-bullhorn"></i>
                            <span>NOTICES</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#survey" class="nav-link">
                            <i class="fas fa-clipboard-list"></i>
                            <span>SUBMIT SURVEY RESPONSE</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#cleaning" class="nav-link">
                            <i class="fas fa-broom"></i>
                            <span>REQUEST CLEANING</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Logout at the bottom -->
            <div class="sidebar-footer">
                <a href="#" id="logoutBtn" class="logout-btn">
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
                    <span id="currentDate">September 24, 2025</span>
                </div>
            </header>
            
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

                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="profile-banner"></div>
                        <div class="profile-info-header">
                            <div class="profile-avatar">
                               <img src="<?php echo htmlspecialchars($user['profile_image_path']); ?>" alt="Profile" class="img-thumbnail mt-2" width="120">
                                <button class="avatar-edit-btn" id="changeAvatarBtn">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <div class="profile-basic-info">
                                <h1 id="profileFullName"></h1>
                                <p class="profile-role"><i class="fas fa-store"></i> Produce Vendor</p>
                                <p class="profile-member-since"><i class="fas fa-calendar">
                                    </i> Member since
                                        <?php
                                            echo htmlspecialchars(date('F j, Y', strtotime($user['created_at'] ?? '')));
                                        ?>
                                </p>
                            </div>
                            <div class="profile-actions">
                                <button type="button" class="btn-edit-profile btn btn-primary" id="editProfileBtn" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    <i class="fas fa-edit"></i> Edit Profile
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                            <form id="editProfileForm" method="POST" action="php/update_profile.php" enctype="multipart/form-data">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <!-- Basic Info -->
                                    <div class="mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" name="phone_number" value="<?php echo format_phone($user['phone_number'] ?? ''); ?>" required>
                                    </div>

                                    <!-- Profile Image -->
                                    <div class="mb-3">
                                        <label class="form-label">Profile Image</label>
                                        <input type="file" class="form-control" name="profile_image" accept="image/*">
                                        <?php if (!empty($user['profile_image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($user['profile_image_path']); ?>" alt="Profile" class="img-thumbnail mt-2" width="120">
                                        <?php endif; ?>
                                    </div>

                                    <!-- Date of Birth -->
                                    <div class="mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" name="date_of_birth" 
                                            value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">

                                    </div>

                                    <!-- Gender -->
                                    <div class="mb-3">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?php echo ($user['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($user['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($user['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>

                                    </div>

                                    <!-- Address Section -->
                                    <div class="mb-3">
                                        <label class="form-label">Barangay</label>
                                        <select class="form-select" name="barangay" required>
                                            <?php
                                            $barangays = [
                                                "Amaya I","Amaya II","Amaya III","Amaya IV","Amaya V","Amaya VI",
                                                "Bagtas","Biga","Bucal","Bunga","Calibuyo","Capipisa",
                                                "Daang Amaya I","Daang Amaya II","Daang Amaya III",
                                                "Halayhay","Julugan I","Julugan II","Julugan III","Julugan IV",
                                                "Julugan V","Julugan VI","Julugan VII","Julugan VIII",
                                                "Mulawin","Paradahan I","Paradahan II","Punta I","Punta II",
                                                "Sahud Ulan","Santol","Tanauan","Tres Cruses"
                                            ];
                                            foreach ($barangays as $brgy) {
                                                $selected = ($user['barangay'] === $brgy) ? 'selected' : '';
                                                echo "<option value=\"$brgy\" $selected>$brgy</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="city" value="Tanza" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Province</label>
                                        <input type="text" class="form-control" name="province" value="Cavite" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code']); ?>">
                                    </div>

                                    <!-- Emergency Contact -->
                                    <hr>
                                    <h6>Emergency Contact Information</h6>
                                    <div class="mb-3">
                                        <label class="form-label">Contact Name</label>
                                        <input type="text" class="form-control" name="emergency_name" value="<?php echo htmlspecialchars($user['emergency_name'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Relationship</label>
                                        <input type="text" class="form-control" name="emergency_relationship" value="<?php echo htmlspecialchars($user['emergency_relationship'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" name="emergency_phone" value="<?php echo htmlspecialchars($user['emergency_phone'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Alternate Number</label>
                                        <input type="text" class="form-control" name="emergency_alt_phone" value="<?php echo htmlspecialchars($user['emergency_alt_phone'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
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
                                        <p id="displayFirstName"><?php echo htmlspecialchars(($user['first_name'] ?? '')) ?></p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Last Name</label>
                                        <p id="displayLastName"><?php echo htmlspecialchars(($user['last_name'] ?? '')) ?></p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Email Address</label>
                                        <p id="displayEmail"><?php echo htmlspecialchars(($user['email'] ?? '')) ?></p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Phone Number</label>
                                        <p id="displayPhone"><?php echo format_phone($user['phone_number'] ?? ''); ?></p>
                                    </div>
                                    <div class="profile-info-item full-width">
                                        <label>Home Address</label>
                                        <p id="displayAddress"><?php echo htmlspecialchars(($user['barangay'] ?? '')) ?> ,<?php echo htmlspecialchars(($user['city'] ?? '')) ?> ,<?php echo htmlspecialchars(($user['province'] ?? '')) ?> , <?php echo htmlspecialchars(($user['postal_code'] ?? '')) ?></p>
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
                                        <p id="displayEmergencyName"><?php echo htmlspecialchars($user['emergency_name'] ?? ''); ?></p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Relationship</label>
                                        <p id="displayEmergencyRelationship"><?php echo htmlspecialchars($user['emergency_relationship'] ?? ''); ?></p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Contact Number</label>
                                        <p id="displayEmergencyAltPhone">
                                            <?php echo format_phone($user['emergency_phone'] ?? ''); ?>
                                        </p>
                                    </div>
                                    <div class="profile-info-item">
                                        <label>Alternate Number</label>
                                        <p id="displayEmergencyAltPhone">
                                            <?php echo format_phone($user['emergency_alt_phone'] ?? ''); ?>
                                        </p>
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
                                        <button type="button" class="btn btn-primary btn-settings" 
                                                id="changePasswordBtn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#changePasswordModal">
                                            Change
                                        </button>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                
                
                <!-- Change Password Modal -->
                <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="changePasswordForm" method="POST" action="php/update_password.php">
                                <div class="modal-header">
                                <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Old Password</label>
                                    <input type="password" class="form-control" name="old_password" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" id="newPassword" required>

                                    <ul id="passwordCriteria" class="mt-2 small" style="display: none;">
                                        <li id="len" class="text-danger"><span class="icon">❌</span> At least 8 characters</li>
                                        <li id="upper" class="text-danger"><span class="icon">❌</span> One uppercase letter</li>
                                        <li id="lower" class="text-danger"><span class="icon">❌</span> One lowercase letter</li>
                                        <li id="num" class="text-danger"><span class="icon">❌</span> One number</li>
                                        <li id="special" class="text-danger"><span class="icon">❌</span> One special character (!@#$%^&*)</li>
                                    </ul>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" id="confirmPassword" required>
                                    <p id="passwordMatch" class="small mt-2" style="display: none;"></p>
                                </div>

                                </div>

                                <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                
                

                
                
                
                
                
            </div>
        </main>
    </div>
    
    
    <script src="JS/vendor-access-control.js"></script>
    <script src="JS/vendor-dashboard.js"></script>
    <!--<script src="../JS/business-permit-handler.js"></script>
    <script src="JS/profile-view.js"></script>-->
    <script src="JS/notification-sync.js"></script>
    
    <script>
        document.getElementById("logoutBtn").addEventListener("click", function(e) {
            e.preventDefault();
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "/php/logout.php";
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
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        const criteriaList = document.getElementById('passwordCriteria');
        const passwordMatch = document.getElementById('passwordMatch');

        const criteria = {
        len: document.getElementById('len'),
        upper: document.getElementById('upper'),
        lower: document.getElementById('lower'),
        num: document.getElementById('num'),
        special: document.getElementById('special')
        };

        newPassword.addEventListener('input', () => {
        const val = newPassword.value.trim();
        criteriaList.style.display = val ? 'block' : 'none';

        const checks = {
            len: val.length >= 8,
            upper: /[A-Z]/.test(val),
            lower: /[a-z]/.test(val),
            num: /[0-9]/.test(val),
            special: /[!@#$%^&*]/.test(val)
        };

        for (const key in checks) {
            const icon = criteria[key].querySelector('.icon');
            if (checks[key]) {
            criteria[key].classList.remove('text-danger');
            criteria[key].classList.add('text-success');
            icon.textContent = '✅';
            } else {
            criteria[key].classList.remove('text-success');
            criteria[key].classList.add('text-danger');
            icon.textContent = '❌';
            }
        }
        });

        // Confirm password checker
        confirmPassword.addEventListener('input', () => {
        const confirmVal = confirmPassword.value.trim();
        passwordMatch.style.display = confirmVal ? 'block' : 'none';

        if (confirmVal === newPassword.value && confirmVal !== '') {
            passwordMatch.textContent = '✅ Passwords match';
            passwordMatch.classList.remove('text-danger');
            passwordMatch.classList.add('text-success');
        } else {
            passwordMatch.textContent = '❌ Passwords do not match';
            passwordMatch.classList.remove('text-success');
            passwordMatch.classList.add('text-danger');
        }
        });
    </script>



</body>
</html>
