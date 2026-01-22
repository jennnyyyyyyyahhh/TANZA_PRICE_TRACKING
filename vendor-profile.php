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
    // check latest business permit verification status for this user
    $permitApproved = false;
    $stmt2 = $conn->prepare("SELECT verification_status FROM business_permits WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    if ($stmt2) {
        $stmt2->bind_param("i", $userId);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $permit = $res2->fetch_assoc();
        if ($permit && isset($permit['verification_status']) && $permit['verification_status'] === 'approved') {
            $permitApproved = true;
        }
        $stmt2->close();
    }

        $query = "SELECT name, value FROM settings";
    $results = $conn->query($query);

    $settings = [];

    if ($results) {
        while ($row = $results->fetch_assoc()) {
            $settings[$row['name']] = $row['value'];
        }
    }
   

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
    <link rel="stylesheet" href="CSS/vendor-dashboard.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="CSS/price-update-notification.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Shared JS for all vendor pages -->
    <script src="JS/vendor-dashboard-shared.js" defer></script>

    <style>
    /* disabled link style used when permit is not approved */
    .disabled-link { pointer-events: none; opacity: 0.55; cursor: not-allowed; }
    .disabled-note { color: #b91c1c; font-size: 0.95rem; margin-top: .4rem; }
    </style>

</head>
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
                        <a href="landingtrial.php" class="nav-link">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a href="pricefront.php"  style="color: black; text-decoration: none;">PRICES</a>
                    </li>
                    <li class="nav-item">
                        <a href="landingtrial.php#weather" class="nav-link">WEATHER</a>
                    </li>
                    <li class="nav-item">
                        <a href="landingtrial.php#about" class="nav-link">ABOUT</a>
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
                        <?php if (!empty(
                            $permitApproved
                        )): ?>
                            <a href="vendor-events.php" class="nav-link">
                                <i class="fas fa-calendar-alt"></i>
                                <span>EVENTS</span>
                            </a>
                        <?php else: ?>
                            <a href="#" class="nav-link disabled-link" title="Upload business permit to access Events">
                                <i class="fas fa-calendar-alt"></i>
                                <span>EVENTS</span>
                            </a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <?php if (!empty($permitApproved)): ?>
                            <a href="#notices" class="nav-link">
                                <i class="fas fa-bullhorn"></i>
                                <span>NOTICES</span>
                            </a>
                        <?php else: ?>
                            <a href="#" class="nav-link disabled-link" title="Upload business permit to access Notices" onclick="return false;">
                                <i class="fas fa-bullhorn"></i>
                                <span>NOTICES</span>
                            </a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <?php if (!empty($permitApproved)): ?>
                            <a href="vendor-survey-form.php" class="nav-link">
                                <i class="fas fa-clipboard-list"></i>
                                <span>SUBMIT SURVEY RESPONSE</span>
                            </a>
                        <?php else: ?>
                            <a href="#" class="nav-link disabled-link" title="Upload business permit to submit survey">
                                <i class="fas fa-clipboard-list"></i>
                                <span>SUBMIT SURVEY RESPONSE</span>
                            </a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <?php if (!empty($permitApproved)): ?>
                            <a href="vendor-cleaning.php" class="nav-link">
                                <i class="fas fa-broom"></i>
                                <span>REQUEST CLEANING</span>
                            </a>
                        <?php else: ?>
                            <a href="#" class="nav-link disabled-link" title="Upload business permit to request cleaning">
                                <i class="fas fa-broom"></i>
                                <span>REQUEST CLEANING</span>
                            </a>
                        <?php endif; ?>
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
                <div id="verificationBanner" class="verification-banner" style="<?php echo !empty($permitApproved) ? 'display: none;' : 'display: block;'; ?>">
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
                                            // Make sure you have a working database connection in $conn
                                            $sql = "SELECT `name` FROM `barangays` ORDER BY `name` ASC";
                                            $result = $conn->query($sql);

                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $brgy = $row['name'];
                                                    $selected = ($user['barangay'] === $brgy) ? 'selected' : '';
                                                    echo "<option value=\"$brgy\" $selected>$brgy</option>";
                                                }
                                            } else {
                                                echo "<option value=\"\">No barangays found</option>";
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
    
    <div id="notif" 
        style="position: fixed; top: 20px; right: 20px; 
            min-width: 250px; max-width: 350px;
            padding: 14px 18px; border-radius: 10px;
            color: #fff; font-weight: 500;
            display: none; z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            opacity: 0; transition: opacity 0.3s ease;">
        <span id="notifMsg"></span>
        <button id="notifClose" 
            style="background: transparent; border: none; color: #fff;
                float: right; font-size: 18px; cursor: pointer; 
                margin-left: 10px; line-height: 1;">×</button>
    </div>
    <script src="../JS/price-update-notification.js" defer></script>
    <script src="../JS/notification.js"></script>
    
    <script>
        // --- Notification Functions ---
        document.getElementById("logoutBtn").addEventListener("click", function(e) {
            e.preventDefault();
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "php/logout.php";
            }
        });
        function showNotif(message, type = 'success') {
            const notif = document.getElementById('notif');
            const notifMsg = document.getElementById('notifMsg');
            const closeBtn = document.getElementById('notifClose');

            notifMsg.textContent = message;
            notif.style.backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';
            notif.style.display = 'block';
            setTimeout(() => notif.style.opacity = '1', 10);

            const timer = setTimeout(() => hideNotif(true), 3000);
            closeBtn.onclick = () => {
                clearTimeout(timer);
                hideNotif(true);
            };
        }

        function hideNotif(clearUrl = false) {
            const notif = document.getElementById('notif');
            notif.style.opacity = '0';
            setTimeout(() => {
                notif.style.display = 'none';
                if (clearUrl) {
                    const baseUrl = window.location.origin + window.location.pathname;
                    window.history.replaceState({}, document.title, baseUrl);
                }
            }, 300);
        }

        // --- URL Params Handling ---
        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);

            // Profile update notification
            if (params.get('updated') === '1') {
                showNotif('Profile updated successfully', 'success');
            }

            if (params.get('password_updated') === '1') {
                showNotif('Password updated successfully', 'success');
            }

            // Cleaning request notifications
            if (params.get('status') === 'success') showNotif('Cleaning request submitted successfully', 'success');
            if (params.get('status') === 'deleted') showNotif('Cleaning request deleted', 'success');
            if (params.get('status') === 'updated') showNotif('Cleaning request updated', 'success');

            // Error notification
            if (params.get('error')) showNotif(params.get('error'), 'error');

            // --- Existing edit modal logic ---
            const editButtons = document.querySelectorAll('.edit-btn');
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));

            editButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('editRequestId').value = this.dataset.id;
                    document.getElementById('editRequestType').value = this.dataset.type;
                    document.getElementById('editPreferredDate').value = this.dataset.date;
                    document.getElementById('editPreferredTime').value = this.dataset.time;
                    document.getElementById('editStallNumber').value = this.dataset.stall;
                    document.getElementById('editMarketSection').value = this.dataset.section;
                    document.getElementById('editRequestDescription').value = this.dataset.desc;
                    editModal.show();
                });
            });
        });
    </script>





</body>
</html>
