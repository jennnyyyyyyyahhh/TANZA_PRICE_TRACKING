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
    } elseif ($permitStatus === 'renewed') {
        $statusTitle = "Renewed";
        $statusMessage = "You have renewed your business permit. Please wait for admin re-verification.";
        $statusBadge = "Renewed";
        $statusClass = "renewed";
        $statusIcon = "fas fa-sync-alt";
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
    <title>Business Permit - Tanza Public Market</title>
    <link rel="stylesheet" href="CSS/minimalist-responsive.css">
    <link rel="stylesheet" href="CSS/vendor-dashboard.css?v=2">
    <link rel="stylesheet" href="CSS/survey-form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="CSS/price-update-notification.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/disable-console-logs.js"></script>
    <script src="JS/disable-all-notifications.js"></script>
    <script src="JS/disable-login-requirements.js"></script>
    <!-- Shared JS for all vendor pages -->
    <script src="JS/vendor-dashboard-shared.js" defer></script>
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
    .status-icon.renewed i {
        color: #2563eb;
    }
    .status-badge.renewed {
        background-color: #2563eb;
    }
    .disabled-link {
    pointer-events: none;
    opacity: 0.5;
    cursor: not-allowed;
}
 .notification-container {
    position: relative;
    cursor: pointer;
}

.notification-bell {
    font-size: 20px;
    color: #333;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: red;
    color: white;
    font-size: 12px;
    border-radius: 50%;
    padding: 2px 6px;
}

.notification-dropdown {
    display: none;
    position: absolute;
    top: 30px;
    right: 0;
    background: #fff;
    border: 1px solid #ddd;
    width: 300px;
    max-height: 400px;
    overflow-y: auto;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    z-index: 1000;
    border-radius: 8px;
}

.notification-dropdown.show {
    display: block;
}

.notification-item {
    padding: 10px;
    font-size: 14px;
    color: #333;
    border-bottom: 1px solid #eee;
    transition: background 0.2s;
}

.notification-item:hover {
    background: #f7f7f7;
    cursor: pointer;
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
                        <a href="index.php#weather" class="nav-link">WEATHER</a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php#about" class="nav-link">ABOUT</a>
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



                    <!-- User Account Button -->
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
                    <li class="nav-item ">
                        <a href="vendor-dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>DASHBOARD</span>
                        </a>
                    </li>
                    <li class="nav-item active">
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
                <a href="php/logout.php" id="logoutBtn" class="logout-btn">
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
                                        <input type="hidden" value="<?php echo htmlspecialchars($userId); ?>" id="userId" name="userId" >
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
                                                <option value="puregold">Puregold</option>
                                                <option value="savemore">Savemore</option>
                                                <option value="dali">Dali</option>
                                                <option value="waltermart">Waltermart</option>
                                                <option value="alphamart">Alphamart</option>
                                                <option value="divimart">Divimart</option>
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
                                    <div class="upload-area" id="uploadAreaUpdate">
                                        <div class="upload-placeholder" id="uploadPlaceholderUpdate">
                                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                            <h4>Drag & Drop or Click to Upload</h4>
                                            <p>Upload your business permit (PDF, JPG, PNG - Max 5MB)</p>
                                            <input type="file" id="businessPermitFileUpdate" name="businessPermit" accept="image/*,.pdf" hidden>
                                            <button type="button" class="btn-upload" onclick="document.getElementById('businessPermitFileUpdate').click()">
                                                <i class="fas fa-folder-open"></i> Choose File
                                            </button>
                                        </div>

                                        <div class="file-preview" id="filePreviewUpdate" style="display: none;">
                                            <i class="fas fa-file-alt file-icon"></i>
                                            <p id="fileNameUpdate"></p>
                                            <p id="fileSizeUpdate" class="file-size"></p>
                                            <button type="button" class="btn-remove" id="removeFileUpdate">
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
                                            <option value="puregold" <?php if($permitData['permit_type']=='puregold') echo 'selected'; ?>>Puregold</option>
                                            <option value="savemore" <?php if($permitData['permit_type']=='savemore') echo 'selected'; ?>>Savemore</option>
                                            <option value="dali" <?php if($permitData['permit_type']=='dali') echo 'selected'; ?>>Dali</option>
                                            <option value="waltermart" <?php if($permitData['permit_type']=='waltermart') echo 'selected'; ?>>Waltermart</option>
                                            <option value="alphamart" <?php if($permitData['permit_type']=='alphamart') echo 'selected'; ?>>Alphamart</option>
                                            <option value="divimart" <?php if($permitData['permit_type']=='divimart') echo 'selected'; ?>>Divimart</option>
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
    
    <script src="../JS/price-update-notification.js" defer></script>
    <script src="../JS/notification.js"></script>
    <script>
        document.getElementById("logoutBtn").addEventListener("click", function(e) {
            e.preventDefault();
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "php/logout.php";
            }
        });
        // File upload handling
        const fileInput = document.getElementById('businessPermitFile');
        const uploadArea = document.querySelector('.upload-area');
        const uploadPlaceholder = document.querySelector('.upload-placeholder');
        const filePreview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const removeFileBtn = document.getElementById('removeFile');
        const businessPermitForm = document.getElementById('businessPermitForm');
        const updatePermitForm = document.getElementById('updatePermitForm');
        const updatePermitBtn = document.getElementById('updatePermit');
        const permitUpdateCard = document.getElementById('permitUpdateCard');
        const permitDisplayCard = document.getElementById('permitDisplayCard');
        const cancelUpdateBtn = document.getElementById('cancelUpdate');
        const notif = document.getElementById('notif');
        const notifMsg = document.getElementById('notifMsg');
        const notifClose = document.getElementById('notifClose');

        // Show notification
        function showNotif(message, type = 'success') {
            notifMsg.textContent = message;
            notif.style.backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';
            notif.style.display = 'block';
            setTimeout(() => notif.style.opacity = '1', 10);
            
            setTimeout(() => {
                notif.style.opacity = '0';
                setTimeout(() => notif.style.display = 'none', 300);
            }, 3000);
        }

        // Close notification
        if (notifClose) {
            notifClose.addEventListener('click', () => {
                notif.style.opacity = '0';
                setTimeout(() => notif.style.display = 'none', 300);
            });
        }

        // File input change handler
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file size (5MB max)
                    if (file.size > 5 * 1024 * 1024) {
                        showNotif('File size must not exceed 5MB', 'error');
                        fileInput.value = '';
                        return;
                    }

                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                    if (!validTypes.includes(file.type)) {
                        showNotif('Please upload a valid file (JPG, PNG, or PDF)', 'error');
                        fileInput.value = '';
                        return;
                    }

                    // Display file info
                    fileName.textContent = file.name;
                    fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
                    uploadPlaceholder.style.display = 'none';
                    filePreview.style.display = 'block';
                }
            });
        }

        // Remove file handler
        if (removeFileBtn) {
            removeFileBtn.addEventListener('click', function() {
                fileInput.value = '';
                uploadPlaceholder.style.display = 'block';
                filePreview.style.display = 'none';
            });
        }

        // Drag and drop handlers
        if (uploadArea) {
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#27ae60';
                uploadArea.style.backgroundColor = '#f0f9f4';
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.style.borderColor = '#27ae60';
                uploadArea.style.backgroundColor = 'transparent';
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#27ae60';
                uploadArea.style.backgroundColor = 'transparent';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        }

        // Form submission handler
        if (businessPermitForm) {
            businessPermitForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validate file is selected
                if (!fileInput.files || fileInput.files.length === 0) {
                    showNotif('Please select a file to upload', 'error');
                    return;
                }

                // Validate dates: expiry must be after issue
                const issueEl = document.getElementById('permitIssueDate');
                const expiryEl = document.getElementById('permitExpiryDate');
                if (issueEl && expiryEl) {
                    const issueVal = issueEl.value;
                    const expiryVal = expiryEl.value;
                    if (!issueVal || !expiryVal) {
                        showNotif('Please enter both issue and expiry dates', 'error');
                        return;
                    }
                    const issueDate = new Date(issueVal);
                    const expiryDate = new Date(expiryVal);
                    if (expiryDate <= issueDate) {
                        showNotif('Expiry date must be after the issue date', 'error');
                        return;
                    }
                }

                // Create FormData and submit
                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotif(data.message || 'Business permit uploaded successfully!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotif(data.message || 'Failed to upload permit', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotif('An error occurred while uploading', 'error');
                });
            });
        }

        // Update permit button
        if (updatePermitBtn) {
            updatePermitBtn.addEventListener('click', function() {
                if (permitDisplayCard) permitDisplayCard.style.display = 'none';
                if (permitUpdateCard) permitUpdateCard.style.display = 'block';
            });
        }

        // Cancel update button
        if (cancelUpdateBtn) {
            cancelUpdateBtn.addEventListener('click', function() {
                if (permitUpdateCard) permitUpdateCard.style.display = 'none';
                if (permitDisplayCard) permitDisplayCard.style.display = 'block';
            });
        }

        // Update form submission
        if (updatePermitForm) {
            // Handle file input for update form
            const fileInputUpdate = document.getElementById('businessPermitFileUpdate');
            const uploadAreaUpdate = document.getElementById('uploadAreaUpdate');
            const uploadPlaceholderUpdate = document.getElementById('uploadPlaceholderUpdate');
            const filePreviewUpdate = document.getElementById('filePreviewUpdate');
            const fileNameUpdate = document.getElementById('fileNameUpdate');
            const fileSizeUpdate = document.getElementById('fileSizeUpdate');
            const removeFileUpdateBtn = document.getElementById('removeFileUpdate');

            if (fileInputUpdate) {
                fileInputUpdate.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Validate file size (5MB max)
                        if (file.size > 5 * 1024 * 1024) {
                            showNotif('File size must not exceed 5MB', 'error');
                            fileInputUpdate.value = '';
                            return;
                        }

                        // Validate file type
                        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                        if (!validTypes.includes(file.type)) {
                            showNotif('Please upload a valid file (JPG, PNG, or PDF)', 'error');
                            fileInputUpdate.value = '';
                            return;
                        }

                        // Display file info
                        fileNameUpdate.textContent = file.name;
                        fileSizeUpdate.textContent = (file.size / 1024).toFixed(2) + ' KB';
                        uploadPlaceholderUpdate.style.display = 'none';
                        filePreviewUpdate.style.display = 'block';
                    }
                });
            }

            if (removeFileUpdateBtn) {
                removeFileUpdateBtn.addEventListener('click', function() {
                    fileInputUpdate.value = '';
                    uploadPlaceholderUpdate.style.display = 'block';
                    filePreviewUpdate.style.display = 'none';
                });
            }

            // Drag and drop for update form
            if (uploadAreaUpdate) {
                uploadAreaUpdate.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadAreaUpdate.style.borderColor = '#27ae60';
                    uploadAreaUpdate.style.backgroundColor = '#f0f9f4';
                });

                uploadAreaUpdate.addEventListener('dragleave', () => {
                    uploadAreaUpdate.style.borderColor = '#27ae60';
                    uploadAreaUpdate.style.backgroundColor = 'transparent';
                });

                uploadAreaUpdate.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadAreaUpdate.style.borderColor = '#27ae60';
                    uploadAreaUpdate.style.backgroundColor = 'transparent';
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        fileInputUpdate.files = files;
                        fileInputUpdate.dispatchEvent(new Event('change'));
                    }
                });
            }

            updatePermitForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validate dates in update form
                const issueElU = document.getElementById('updateIssueDate');
                const expiryElU = document.getElementById('updateExpiryDate');
                if (issueElU && expiryElU) {
                    const issueValU = issueElU.value;
                    const expiryValU = expiryElU.value;
                    if (!issueValU || !expiryValU) {
                        showNotif('Please enter both issue and expiry dates', 'error');
                        return;
                    }
                    const issueDateU = new Date(issueValU);
                    const expiryDateU = new Date(expiryValU);
                    if (expiryDateU <= issueDateU) {
                        showNotif('Expiry date must be after the issue date', 'error');
                        return;
                    }
                }

                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotif(data.message || 'Business permit updated successfully!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotif(data.message || 'Failed to update permit', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotif('An error occurred while updating', 'error');
                });
            });
        }

        // Update current date
        const currentDateElement = document.getElementById('currentDate');
        if (currentDateElement) {
            const now = new Date();
            const options = { 
                weekday: 'long',
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            currentDateElement.textContent = now.toLocaleDateString('en-US', options);
        }

        // Keep expiry date >= issue date (expiry must be after issue)
        function setExpiryMinFromIssue(issueEl, expiryEl) {
            if (!issueEl || !expiryEl) return;

            function toDateInputString(d) {
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            }

            issueEl.addEventListener('change', function() {
                if (!this.value) return;
                const issue = new Date(this.value + 'T00:00:00');
                // expiry must be at least one day after issue (strictly greater)
                const minExpiry = new Date(issue.getTime() + 24 * 60 * 60 * 1000);
                const minStr = toDateInputString(minExpiry);
                expiryEl.min = minStr;

                // If expiry is empty or earlier/equal than min, set it to min
                if (!expiryEl.value || new Date(expiryEl.value + 'T00:00:00') <= issue) {
                    expiryEl.value = minStr;
                }
            });

            // Also ensure user cannot pick expiry earlier than min manually
            expiryEl.addEventListener('change', function() {
                if (!this.value) return;
                const issueVal = issueEl.value ? new Date(issueEl.value + 'T00:00:00') : null;
                const expiryVal = new Date(this.value + 'T00:00:00');
                if (issueVal && expiryVal <= issueVal) {
                    // correct it to at least one day after issue
                    const fix = new Date(issueVal.getTime() + 24 * 60 * 60 * 1000);
                    this.value = toDateInputString(fix);
                    showNotif('Expiry date must be after the issue date', 'error');
                }
            });
        }

        // Initialize for create form
        setExpiryMinFromIssue(document.getElementById('permitIssueDate'), document.getElementById('permitExpiryDate'));
        // Initialize for update form
        setExpiryMinFromIssue(document.getElementById('updateIssueDate'), document.getElementById('updateExpiryDate'));

        // If permit is expired, show the update form (so user renews the existing permit)
        (function showUpdateIfExpired() {
            const statusClass = <?php echo json_encode($statusClass); ?>;
            if (statusClass === 'expired') {
                const displayCard = document.getElementById('permitDisplayCard');
                const updateCard = document.getElementById('permitUpdateCard');
                if (displayCard) displayCard.style.display = 'none';
                if (updateCard) updateCard.style.display = 'block';
                // scroll into view
                if (updateCard) updateCard.scrollIntoView({ behavior: 'smooth' });
            }
        })();
    </script>
</body>
</html>
