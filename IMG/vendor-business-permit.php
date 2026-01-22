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
        SELECT u.first_name, u.last_name, u.email, u.created_at,
            p.phone_number, p.city, p.province, p.postal_code
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

    $stmt->close();
    $conn->close();

    // determine permit status display
    $statusTitle = "Pending Verification";
    $statusMessage = "No business permit uploaded yet. Please upload your permit to get verified.";
    $statusBadge = "Not Verified";
    $statusClass = "pending";
    $statusIcon = "fas fa-clock";

    if ($hasPermit) {
    $permitStatus = isset($permitData['verification_status']) ? strtolower($permitData['verification_status']) : '';
    $expiryDate = isset($permitData['expiry_date']) ? $permitData['expiry_date'] : null;

    // Check for expiration first
    if ($expiryDate && strtotime($expiryDate) < time()) {
        $statusTitle = "Expired";
        $statusMessage = "Your business permit has expired. Please upload a renewed permit.";
        $statusBadge = "Expired";
        $statusClass = "expired";
        $statusIcon = "fas fa-exclamation-triangle";
    } elseif ($permitStatus === 'pending') {
        $statusTitle = "Pending Approval";
        $statusMessage = "Your business permit has been uploaded. Please wait for admin approval.";
        $statusBadge = "Pending";
        $statusClass = "pending";
        $statusIcon = "fas fa-hourglass-half";
    } elseif ($permitStatus === 'approved') {
        $statusTitle = "Approved";
        $statusMessage = "Your business permit has been verified by the admin.";
        $statusBadge = "Approved";
        $statusClass = "approved";
        $statusIcon = "fas fa-check-circle";
    } elseif ($permitStatus === 'rejected') {
        $statusTitle = "Rejected";
        $statusMessage = "Your business permit was rejected. Please re-upload a valid one.";
        $statusBadge = "Rejected";
        $statusClass = "rejected";
        $statusIcon = "fas fa-times-circle";
    }
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
    <title>Business Permit - Tanza Public Market</title>
    <link rel="stylesheet" href="../CSS/minimalist-responsive.css">
    <link rel="stylesheet" href="../CSS/vendor-dashboard.css">
    <link rel="stylesheet" href="../CSS/survey-form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/disable-console-logs.js"></script>
    <script src="../JS/disable-all-notifications.js"></script>
    <script src="../JS/disable-login-requirements.js"></script>
    <!-- Shared JS for all pages -->
    <script src="../JS/vendor-dashboard-shared.js" defer></script>
</head>


<!-- Wide, clean layout -->
<style>
    .permit-upload-card {
        background: #fff;
        padding: 32px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        width: 100%;
        max-width: 100%;
        margin: 20px auto;
        box-sizing: border-box;
    }
    .permit-upload-card h3 {
        text-align: center;
        margin-bottom: 24px;
        color: #333;
    }
    .upload-area {
        border: 2px dashed #27ae60;
        border-radius: 12px;
        text-align: center;
        padding: 24px;
        margin-bottom: 24px;
    }
    .upload-icon {
        font-size: 48px;
        color: #27ae60;
        margin-bottom: 10px;
    }
    .file-icon {
        font-size: 36px;
        color: #27ae60;
    }
    .file-size {
        font-size: 0.85rem;
        color: #666;
    }
    .form-group {
        margin-bottom: 18px;
        flex: 1;
    }
    .form-row {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .form-input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 15px;
        box-sizing: border-box;
    }
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 24px;
    }
    .btn-primary, .btn-secondary, .btn-upload, .btn-remove {
        padding: 10px 18px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
    }
    .btn-primary {
        background: #27ae60;
        color: #fff;
    }
    .btn-secondary {
        background: #999;
        color: #fff;
    }
    .btn-upload {
        background: #27ae60;
        color: #fff;
    }
    .btn-remove {
        background: #e74c3c;
        color: #fff;
    }
    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
        }
        .permit-upload-card {
            padding: 20px;
        }
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 12px;
        color: #fff;
        font-weight: 600;
        display: inline-block;
    }

    .status-icon i {
        font-size: 32px;
    }

    .status-icon.pending i {
        color: #f1c40f;
    }
    .status-badge.pending {
        background-color: #f1c40f;
    }

    .status-icon.approved i {
        color: #2ecc71;
    }
    .status-badge.approved {
        background-color: #2ecc71;
    }

    .status-icon.rejected i {
        color: #e74c3c;
    }
    .status-badge.rejected {
        background-color: #e74c3c;
    }

    .status-icon.expired i {
        color: #e67e22;
    }
    .status-badge.expired {
        background-color: #e67e22;
    }

</style>
<body>
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
                <!--message notification-->
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

                <!-- Right Side Actions -->
                <div class="nav-actions">
                    <div class="notification-container">
                        <i class="fas fa-bell notification-bell" id="notificationBell"></i>
                        <span class="notification-badge" id="notificationBadge">5</span>
                        <!-- Notification Dropdown -->
                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <h4>Market Price Alerts</h4>
                                <span class="mark-read" id="markAllRead">Mark all as read</span>
                            </div>
                            <div id="cleaningNotifications">
                                <!-- Cleaning request notifications will be inserted here -->
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
                    <!-- User Account Button -->
                    <div class="user-account-container" id="headerAccountContainer">
                        <a href="profile.php" class="btn-user-account" >
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
                        <a href="vendor-dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>DASHBOARD</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="vendor-business-permit.html" class="nav-link">
                            <i class="fas fa-certificate"></i>
                            <span>BUSINESS PERMIT</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="vendor-events.html" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>EVENTS</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="vendor-notices.html" class="nav-link">
                            <i class="fas fa-bullhorn"></i>
                            <span>NOTICES</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="survey-form.html" class="nav-link">
                            <i class="fas fa-clipboard-list"></i>
                            <span>SUBMIT SURVEY RESPONSE</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="cleaning.php" class="nav-link">
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
                    <span id="currentDate"></span>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="content-wrapper">
                <!-- Vendor Information Section (Moved from vendor-edit-profile.html) -->
                    

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
                                    <div class="status-icon <?php echo $statusClass; ?>">
                                        <i class="<?php echo $statusIcon; ?>"></i>
                                    </div>
                                    <div class="status-info">
                                        <h4 id="permitStatusTitle"><?php echo htmlspecialchars($statusTitle); ?></h4>
                                        <p id="permitStatusMessage"><?php echo htmlspecialchars($statusMessage); ?></p>
                                        <span class="status-badge <?php echo $statusClass; ?>" id="permitStatusBadge">
                                            <?php echo htmlspecialchars($statusBadge); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php if (!$hasPermit): ?>
                           

                            
                            <!-- Upload Form -->
                            <div class="permit-upload-card" id="permitUploadCard">
                                <h3>Upload Business Permit</h3>
                                <form class="permit-upload-form" id="businessPermitForm" action="php/save_business_permit.php" method="POST" enctype="multipart/form-data">
                                    
                                    <!-- Upload Area -->
                                    <div class="upload-area" id="uploadArea">
                                        <div class="upload-placeholder">
                                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                            <h4>Drag & Drop or Click to Upload</h4>
                                            <p>Upload your business permit (PDF, JPG, PNG - Max 5MB)</p>
                                            <input type="file" id="businessPermitFile" name="businessPermit" accept="image/*,.pdf" hidden>
                                            <button type="button" class="btn-upload" onclick="document.getElementById('businessPermitFile').click()">
                                                <i class="fas fa-folder-open"></i> Choose File
                                            </button>
                                        </div>

                                        <div class="file-preview" id="filePreview" style="display: none;">
                                            <i class="fas fa-file-alt file-icon"></i>
                                            <p id="fileName"></p>
                                            <p id="fileSize" class="file-size"></p>
                                            <button type="button" class="btn-remove" id="removeFile">
                                                <i class="fas fa-times"></i> Remove
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Business Info -->
                                    <div class="form-group">
                                        <input type="text" value="<?php echo htmlspecialchars($userId); ?>" id="userId" name="userId" >
                                        <label for="permitNumber">Business Permit Number</label>
                                        <input type="text" id="permitNumber" name="permitNumber" class="form-input" placeholder="Enter permit number" required>
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
                                        <input type="text" id="businessName" name="businessName" class="form-input" placeholder="Enter business name" required>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="vendorType">Vendor Type</label>
                                            <select id="vendorType" name="vendorType" class="form-input" required>
                                                <option value="">Select vendor type</option>
                                                <option value="food">Food Vendor</option>
                                                <option value="retail">Retail Vendor</option>
                                                <option value="service">Service Provider</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="stallNumber">Stall Number</label>
                                            <input type="text" id="stallNumber" name="stallNumber" class="form-input" placeholder="Enter stall number" required>
                                        </div>
                                    </div>

                                    <!-- Actions -->
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
                            <div class="card shadow-lg border-0 rounded-4 mb-4" id="permitDisplayCard">
                                <!-- Header -->
                                <div class="card-header bg-primary bg-gradient text-white py-3">
                                    <h4 class="fw-bold mb-0">
                                    <i class="fas fa-file-contract me-2"></i>Business Permit Information
                                    </h4>
                                </div>

                                <!-- Body -->
                                <div class="card-body bg-light p-4">
                                    <div class="row g-4 align-items-stretch">

                                    <!-- Left Column: Permit Preview -->
                                    <div class="col-md-6 d-flex">
                                        <div class="card border-0 shadow-sm flex-fill">
                                        <div class="card-header bg-white fw-semibold text-primary border-bottom">
                                            <i class="fas fa-eye me-2"></i> Permit Preview
                                        </div>
                                        <div class="card-body bg-white d-flex align-items-center justify-content-center">
                                            <?php if (str_ends_with(strtolower($permitData['file_name']), '.pdf')): ?>
                                            <iframe src="<?php echo htmlspecialchars($permitData['file_path']); ?>"
                                                    class="w-100 rounded shadow-sm"
                                                    style="height:100%; min-height:450px;"></iframe>
                                            <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($permitData['file_path']); ?>"
                                                alt="Business Permit"
                                                class="img-fluid rounded shadow-sm border">
                                            <?php endif; ?>
                                        </div>
                                        </div>
                                    </div>

                                    <!-- Right Column: Permit Information -->
                                    <div class="col-md-6 d-flex">
                                        <div class="card border-0 shadow-sm flex-fill">
                                        <div class="card-header bg-white fw-semibold text-primary border-bottom">
                                            <i class="fas fa-circle-info me-2"></i> Business Details
                                        </div>
                                        <div class="card-body bg-white d-flex flex-column justify-content-between">
                                            <div>
                                            <table class="table table-borderless align-middle mb-3">
                                                <tbody>
                                                <tr class="border-bottom">
                                                    <th class="text-muted" style="width:40%;">Permit Number</th>
                                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($permitData['permit_number']); ?></td>
                                                </tr>
                                                <tr class="border-bottom">
                                                    <th class="text-muted">Business Name</th>
                                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($permitData['business_name']); ?></td>
                                                </tr>
                                                <tr class="border-bottom">
                                                    <th class="text-muted">Vendor Type</th>
                                                    <td><?php echo htmlspecialchars($permitData['permit_type']); ?></td>
                                                </tr>
                                                <tr class="border-bottom">
                                                    <th class="text-muted">Issue Date</th>
                                                    <td><?php echo htmlspecialchars($permitData['issue_date']); ?></td>
                                                </tr>
                                                <tr class="border-bottom">
                                                    <th class="text-muted">Expiry Date</th>
                                                    <td class="text-danger fw-semibold"><?php echo htmlspecialchars($permitData['expiry_date']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Uploaded On</th>
                                                    <td><?php echo htmlspecialchars($permitData['created_at']); ?></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            </div>
                                            <div class="text-end">
                                                <button type="button" class="btn btn-primary btn-lg px-4 shadow-sm" id="updatePermit">
                                                    <i class="fas fa-pen-to-square me-2"></i> Update Permit
                                                </button>

                                            </div>
                                        </div>
                                        </div>
                                    </div>

                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="card-footer bg-white border-0 text-center text-muted small py-3">
                                    <i class="fas fa-shield-alt text-secondary me-1"></i>
                                    Your uploaded permit is securely stored and managed in the system.
                                </div>
                            </div>

                            <!-- Update Permit Card -->
                            <div class="permit-update-card" id="permitUpdateCard" style="display:none;">
                                <h3>Update Business Permit</h3>
                                
                                <form id="updatePermitForm" action="php/update_business_permit.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($permitData['id']); ?>">
                                    <!-- Upload Area -->
                                    <div class="upload-area" id="uploadArea">
                                        <div class="upload-placeholder">
                                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                            <h4>Drag & Drop or Click to Upload</h4>
                                            <p>Upload your business permit (PDF, JPG, PNG - Max 5MB)</p>
                                            <input type="file" id="businessPermitFile" name="businessPermit" accept="image/*,.pdf" hidden>
                                            <button type="button" class="btn-upload" onclick="document.getElementById('businessPermitFile').click()">
                                                <i class="fas fa-folder-open"></i> Choose File
                                            </button>
                                        </div>

                                        <div class="file-preview" id="filePreview" style="display: none;">
                                            <i class="fas fa-file-alt file-icon"></i>
                                            <p id="fileName"></p>
                                            <p id="fileSize" class="file-size"></p>
                                            <button type="button" class="btn-remove" id="removeFile">
                                                <i class="fas fa-times"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="updatePermitNumber">Permit Number</label>
                                        <input type="text" id="updatePermitNumber" name="permitNumber" class="form-input"
                                            value="<?php echo htmlspecialchars($permitData['permit_number']); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="updateBusinessName">Business Name</label>
                                        <input type="text" id="updateBusinessName" name="businessName" class="form-input"
                                            value="<?php echo htmlspecialchars($permitData['business_name']); ?>" required>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="updateIssueDate">Issue Date</label>
                                            <input type="date" id="updateIssueDate" name="issueDate" class="form-input"
                                                value="<?php echo htmlspecialchars($permitData['issue_date']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="updateExpiryDate">Expiry Date</label>
                                            <input type="date" id="updateExpiryDate" name="expiryDate" class="form-input"
                                                value="<?php echo htmlspecialchars($permitData['expiry_date']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="updateVendorType">Vendor Type</label>
                                        <select id="updateVendorType" name="vendorType" class="form-input" required>
                                            <option value="">Select vendor type</option>
                                            <option value="food" <?php if($permitData['permit_type']=='food') echo 'selected'; ?>>Food Vendor</option>
                                            <option value="retail" <?php if($permitData['permit_type']=='retail') echo 'selected'; ?>>Retail Vendor</option>
                                            <option value="service" <?php if($permitData['permit_type']=='service') echo 'selected'; ?>>Service Provider</option>
                                            <option value="other" <?php if($permitData['permit_type']=='other') echo 'selected'; ?>>Other</option>
                                        </select>
                                    </div>

                                    <div class="form-actions">
                                        <button type="button" class="btn-secondary" id="cancelUpdate">Cancel</button>
                                        <button type="submit" class="btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                
            </div>
        </main>
    </div>
    
    <!-- *** PAGE SPECIFIC SCRIPTS GO HERE (if any) *** -->
    <script>
        document.getElementById('updatePermit')?.addEventListener('click', () => {
            document.getElementById('permitDisplayCard').style.display = 'none';
            document.getElementById('permitUpdateCard').style.display = 'block';
        });

        document.getElementById('cancelUpdate')?.addEventListener('click', () => {
            document.getElementById('permitUpdateCard').style.display = 'none';
            document.getElementById('permitDisplayCard').style.display = 'block';
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

        // Detect query params and show notification
        const params = new URLSearchParams(window.location.search);
        if (params.get('status') === 'success') showNotif('Business permit saved successfully', 'success');
        if (params.get('updated') === '1') showNotif('Business permit updated successfully', 'success');
        if (params.get('error')) showNotif(params.get('error'), 'error');
    </script>
</body>
</html>
