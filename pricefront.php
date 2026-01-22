<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    session_start();
    require_once 'php/connection.php';

    // If user logged in, fetch their first name for header display
    $userName = null;
    if (isset($_SESSION['user_id'])) {
        $stmtUser = $conn->prepare("SELECT first_name FROM users WHERE id = ? LIMIT 1");
        $stmtUser->bind_param("i", $_SESSION['user_id']);
        $stmtUser->execute();
        $resUser = $stmtUser->get_result();
        if ($resUser && $resUser->num_rows > 0) {
            $rowU = $resUser->fetch_assoc();
            $userName = $rowU['first_name'];
        }
        // fetch full user record and admin flag for header usage
        $user = null;
        $isAdmin = false;

        $stmtUser->close();
        $stmtUserFull = $conn->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ? LIMIT 1");
        if ($stmtUserFull) {
            $stmtUserFull->bind_param('i', $_SESSION['user_id']);
            $stmtUserFull->execute();
            $resUserFull = $stmtUserFull->get_result();
            if ($resUserFull && $resUserFull->num_rows > 0) {
                $user = $resUserFull->fetch_assoc();
            }
            $stmtUserFull->close();
        }

        $stmtRole = $conn->prepare("SELECT role FROM admin WHERE user_id = ? LIMIT 1");
        if ($stmtRole) {
            $stmtRole->bind_param('i', $_SESSION['user_id']);
            $stmtRole->execute();
            $resRole = $stmtRole->get_result();
            if ($resRole && $resRole->num_rows > 0) {
                $rowRole = $resRole->fetch_assoc();
                if (!empty($rowRole['role']) && $rowRole['role'] === 'admin') {
                    $isAdmin = true;
                }
            }
            $stmtRole->close();
        }
    }

    // Query to fetch product_name and image only
    $sql = "SELECT product_name, image FROM products";
    $result = $conn->query($sql);

    $products = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }

    $sqlVendors = "
        SELECT 
            bp.id AS permit_id,
            bp.stall_number,
            bp.business_name,
            up.user_id,
            CONCAT(u.first_name, ' ', u.last_name) AS vendor_name
        FROM business_permits bp
        INNER JOIN user_profiles up ON bp.user_id = up.user_id
        INNER JOIN users u ON u.id = up.user_id
        INNER JOIN admin a ON a.user_id = u.id
        WHERE bp.verification_status = 'approved'
        AND a.role = 'vendor'
        ORDER BY bp.business_name ASC
    ";


    $resultVendors = $conn->query($sqlVendors);

    $vendors = [];
    if ($resultVendors && $resultVendors->num_rows > 0) {
        while ($row = $resultVendors->fetch_assoc()) {
            $vendors[] = $row;
        }
    }

        $query = "SELECT name, value FROM settings";
        $result = $conn->query($query);

        $settings = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['name']] = $row['value'];
            }
        }
    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commodity Price Dashboard - Tanza Public Market</title>
    <link rel="stylesheet" href="CSS/pricefront.css">
    <link rel="stylesheet" href="CSS/admin-dashboard.css">
    <link rel="stylesheet" href="CSS/export-modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="CSS/price-update-notification.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <style>
        /* Enable page scrolling */
        html, body {
            overflow-x: hidden;
            overflow-y: auto;
            height: auto;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        body {
            padding-top: 70px; /* Space for fixed header */
        }
        
        /* Fixed header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: #0b6b3a;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Ensure main content is visible and scrollable */
        main.container {
            position: relative;
            z-index: 1;
            min-height: calc(100vh - 200px);
        }
        
        /* Category Card Image Sizing */
        .category-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
        }
        
        .category-card .card-img-top {
            height: 200px;
            object-fit: cover;
            object-position: center;
            width: 100%;
        }
        
        /* Styled OTP modal (based on signup.php design) */
        #otpModalReport {
            display: none;
            position: fixed;
            z-index: 10000;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            animation: otpFadeIn 0.25s ease forwards;
        }

        #otpModalReportContent {
            background: linear-gradient(180deg, #ffffff 0%, #f7f9fa 100%);
            margin: 6% auto;
            padding: 2.2rem 2rem;
            border-radius: 22px;
            width: 95%;
            max-width: 520px;
            text-align: center;
            position: relative;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.25);
            animation: otpSlideUp 0.32s ease forwards;
            transform-origin: center;
        }

        #otpReportClose {
            position: absolute;
            top: 12px;
            right: 16px;
            font-size: 1.4rem;
            color: #666;
            cursor: pointer;
            transition: color .15s ease;
        }
        #otpReportClose:hover { color: #222; }

        #otpModalReportContent h3 { font-size: 1.6rem; margin-bottom: 0.4rem; color: #213e23; }
        .otp-instruction { color: #53606a; font-size: 0.98rem; margin-bottom: 1rem; }

        .otp-inputs { display:flex; justify-content:center; gap:14px; margin: 0.8rem 0 1rem; }
        .otp-box { width: 60px; height: 72px; border-radius: 12px; border: 2px solid #e2e8f0; background: #fff; text-align:center; font-size:1.8rem; font-weight:700; color:#133217; box-shadow: 0 6px 18px rgba(0,0,0,0.06); }
        .otp-box:focus { outline: none; border-color: #4a8c33; background: #f6fff5; box-shadow: 0 8px 22px rgba(74,140,51,0.16); transform: scale(1.03); }

        #verifyOTPReportBtn { background: linear-gradient(135deg, #4a8c33, #66bb6a); color: #fff; font-weight:700; border-radius:10px; padding: 0.9rem 1rem; border: none; }
        #verifyOTPReportBtn:hover { filter: brightness(.98); }

        @media (max-width:480px) {
            .otp-box { width:48px; height:60px; font-size:1.4rem; }
            #otpModalReportContent { padding:1.6rem; }
        }

        @keyframes otpFadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes otpSlideUp { from { opacity: 0; transform: translateY(28px) scale(.98); } to { opacity:1; transform: translateY(0) scale(1); } }
        </style>
</head>

<body>

<!-- Header -->
<header class="header">
    <nav class="navbar">
        <div class="nav-container">
            <!-- Logo Left Side -->
            <div class="logo">
                <a href="pricefront.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-seedling"></i>
                    <span class="logo-text"><?php echo $settings['system_name'];?></span>
                </a>
            </div>
            
            <!-- Navigation Center -->
            <ul class="nav-menu" id="navMenu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">HOME</a>
                </li>
                <li class="nav-item">
                    <a href="pricefront.php" style="color: white; text-decoration: none;">PRICES</a>
                </li>
                <li class="nav-item">
                    <a href="index.php#weather" class="nav-link">WEATHER</a>
                </li>
                <li class="nav-item">
                    <a href="index.php#about" class="nav-link">ABOUT</a>
                </li>
            </ul>
            
            <!-- Right Side Actions -->
            
            
        </div>
        <div class="nav-actions">
                <?php if (!empty($userName)): ?>
                    <!-- Notification Bell -->
                    <div class="position-relative" id="notificationContainer">
                        <button class="btn btn-outline-primary position-relative rounded-circle shadow-sm" id="notificationBell" style="width:45px;height:45px;">
                            <i class="fas fa-bell fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge"></span>
                        </button>

                        <div id="notificationDropdown"
                            class="card border-0 shadow-lg position-absolute end-0 mt-3 rounded-4 overflow-hidden"
                            style="width:400px; display:none; z-index:2000;">
                        </div>
                    </div>

                    <!-- User Account Button with Dropdown -->
                    <?php if (!empty($isAdmin)): ?>
                        <div class="user-account-container dropdown" id="headerAccountContainer">
                            <a
                                href="#profile"
                                class="btn-user-account"
                                id="adminAccountBtn"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <i class="fas fa-user-shield"></i>
                                <span><?php echo htmlspecialchars(($user['first_name'] ?? $userName ?? '')) ?></span>
                            </a>
                            <div
                                class="dropdown-menu account-dropdown show-on-hover shadow border-0 mt-2"
                                aria-labelledby="adminAccountBtn">
                            
                                <div class="account-dropdown-header p-3 border-bottom text-center">
                                    <div class="account-avatar mb-2">
                                        <i class="fas fa-user-shield fa-2x"></i>
                                    </div>
                                    <div class="account-user-info">
                                        <span class="badge bg-secondary" id="modalAdminType">Administrator</span>
                                    </div>
                                </div>

                                <div class="account-menu d-flex flex-column">
                                    <a href="admin-profile.php" class="account-menu-item dropdown-item py-2">
                                        <i class="fas fa-user me-2"></i> Profile
                                    </a>
                                    <a href="admin-settings.php" class="account-menu-item dropdown-item py-2">
                                        <i class="fas fa-cog me-2"></i> Settings
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a href="./php/logout.php" class="account-menu-item dropdown-item py-2 text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="user-account-container dropdown" id="headerAccountContainer">
                            <a
                                href="#profile"
                                class="btn-user-account"
                                id="vendorAccountBtn"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <i class="fas fa-user-circle"></i>
                                <span><?php echo htmlspecialchars(($user['first_name'] ?? $userName ?? '')) ?></span>
                            </a>
                            <div
                                class="dropdown-menu account-dropdown show-on-hover shadow border-0 mt-2"
                                aria-labelledby="vendorAccountBtn">
                            
                                <div class="account-dropdown-header p-3 border-bottom text-center">
                                    <div class="account-avatar mb-2">
                                        <i class="fas fa-user-circle fa-2x"></i>
                                    </div>
                                    <div class="account-user-info">
                                        <span class="badge bg-success">Vendor</span>
                                    </div>
                                </div>

                                <div class="account-menu d-flex flex-column">
                                    <a href="vendor-profile.php" class="account-menu-item dropdown-item py-2">
                                        <i class="fas fa-user me-2"></i> Profile
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a href="php/logout.php" class="account-menu-item dropdown-item py-2 text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <a href="login.php" class="btn btn-outline-success">Login</a>
                <?php endif; ?>
            </div>
    </nav>
</header>

<!-- Page Header -->
<section class="py-4 bg-light text-center">
    <div class="container">
        <h2 class="fw-bold mb-1"><i class="fas fa-store text-success"></i> Commodity Price Dashboard</h2>
        <p class="text-muted mb-0">Choose a category below to view prices</p>
    </div>
</section>

<!-- Category Grid -->
<main class="container py-5">
    <div class="row g-3">
        <?php
        $uniqueProducts = [];
        foreach ($products as $product) {
            $name = $product['product_name'];
            if (!isset($uniqueProducts[$name])) {
                $uniqueProducts[$name] = $product;
            }
        }
        ?>

        <?php if (!empty($uniqueProducts)): ?>
            <?php foreach ($uniqueProducts as $product): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card h-100 shadow-sm border-0 category-card"
                         onclick="window.location='product-commodity.php?product=<?= urlencode($product['product_name']); ?>'"
                         style="cursor:pointer;">
                        <img src="<?= htmlspecialchars($product['image']); ?>"
                             class="card-img-top"
                             alt="<?= htmlspecialchars($product['product_name']); ?>"
                             onerror="this.src='https://via.placeholder.com/300x200?text=No+Image';">
                        <div class="card-body text-center">
                            <h6 class="card-title fw-bold text-success"><?= htmlspecialchars($product['product_name']); ?></h6>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center text-muted">No products available.</p>
        <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <div class="text-center mt-5">
        <a href="export-data.php" class="btn btn-success me-2">
            <i class="fas fa-download"></i> Export Data
        </a>
        <button class="btn btn-outline-danger" id="reportOverpriceBtn">
            <i class="fas fa-exclamation-triangle"></i> Report Vendor
        </button>
    </div>
</main>


<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-bullhorn"></i> Report Vendor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="reportForm" action="php/save_report.php" method="POST" enctype="multipart/form-data">

                    <!-- Report Date -->
                    <div class="mb-3">
                        <label for="reportDate" class="form-label fw-bold">Report Date *</label>
                        <input type="date" name="reportDate" id="reportDate" class="form-control" required>
                    </div>
                        <div class="mb-3">
                            <label for="customerEmail" class="form-label fw-bold">Customer Email *</label>
                            <input type="email" name="customerEmail" id="customerEmail" class="form-control" required>
                        </div>
                    <!-- Customer Name -->
                    <div class="mb-3">
                        <label for="customerName" class="form-label fw-bold">Customer Name</label>
                        <input type="text" name="customerName" id="customerName" class="form-control">
                    </div>
                    <!-- Stall/Vendor Name Dropdown -->
                    <div class="mb-3">
                        <label for="stallName" class="form-label fw-bold">Stall/Vendor Name *</label>
                        <select name="stallName" id="stallName" class="form-select" required>
                            <option value="" disabled selected>Select Stall / Vendor</option>
                            <?php foreach ($vendors as $v): ?>
                                <option 
                                    value="<?= htmlspecialchars($v['business_name'] . ' - ' . $v['vendor_name']); ?>" 
                                    data-stall="<?= htmlspecialchars($v['stall_number']); ?>"
                                    data-userid="<?= htmlspecialchars($v['user_id']); ?>">
                                    <?= htmlspecialchars($v['business_name'] . ' - ' . $v['vendor_name']); ?>
                                </option>

                            <?php endforeach; ?>
                        </select>
                    </div>


                    <!-- Stall Location -->
                    <div class="mb-3">
                        <label for="stallLocation" class="form-label fw-bold">Stall Location *</label>
                        <input type="text" name="stallLocation" id="stallLocation" class="form-control" required>
                    </div>
                     <!-- Hidden User ID field -->
                    <input type="hidden" name="vendorUserId" id="vendorUserId">
               
                    <!-- Report Category -->
                    <div class="mb-3">
                        <label for="category" class="form-label fw-bold">Report Category *</label>
                        <select name="category" id="category" class="form-select" required>
                            <option value="" disabled selected>Select Category</option>
                            <option value="Product Quality">Product Quality</option>
                            <option value="Pricing Issues">Pricing Issues</option>
                            <option value="Hygiene Concerns">Hygiene Concerns</option>
                            <option value="Customer Service">Customer Service</option>
                            <option value="Safety Issues">Safety Issues</option>
                            <option value="Fraudulent Practices">Fraudulent Practices</option>
                            <option value="Unauthorized Selling">Unauthorized Selling</option>
                            <option value="Expired Goods">Expired Goods</option>
                            <option value="Weight Manipulation">Weight Manipulation</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Description *</label>
                        <textarea name="description" id="description" class="form-control" rows="4" required></textarea>
                    </div>

                    <!-- Evidence Upload -->
                    <div class="mb-3">
                        <label for="evidencePhoto" class="form-label fw-bold">Upload Evidence (Photo)</label>
                        <input type="file" name="evidencePhoto" id="evidencePhoto" accept="image/*" class="form-control">
                    </div>

                                        <button type="submit" class="btn btn-success w-100">Submit Report</button>
                                </form>

                                <!-- OTP Modal for report verification (signup-like) -->
                                <div id="otpModalReport" style="display:none; position: fixed; z-index: 2000; inset:0; background: rgba(0,0,0,0.55);">
                                    <div id="otpModalReportContent" style="background:#fff; margin:6% auto; padding:2rem; border-radius:25px; width:95%; max-width:520px; position:relative; text-align:center; box-shadow:0 15px 40px rgba(0,0,0,0.25);">
                                        <span id="otpReportClose" style="position:absolute; right:18px; top:12px; font-size:20px; cursor:pointer;">&times;</span>
                                        <h3>Email Verification</h3>
                                        <p class="otp-instruction">Enter the 6-digit code sent to your email</p>

                                        <div class="otp-inputs" style="display:flex; justify-content:center; gap:16px; margin:1rem 0;">
                                            <input type="text" maxlength="1" class="otp-box report-otp-box" />
                                            <input type="text" maxlength="1" class="otp-box report-otp-box" />
                                            <input type="text" maxlength="1" class="otp-box report-otp-box" />
                                            <input type="text" maxlength="1" class="otp-box report-otp-box" />
                                            <input type="text" maxlength="1" class="otp-box report-otp-box" />
                                            <input type="text" maxlength="1" class="otp-box report-otp-box" />
                                        </div>
                                        <button type="button" id="verifyOTPReportBtn" class="btn btn-success mt-3 w-100">Verify Email</button>
                                    </div>
                                </div>

            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-export"></i> Export Price Data</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="mb-3">
                    <label class="form-label fw-bold">Date Range</label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="startDate">
                        <span class="input-group-text">to</span>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">File Format</label>
                    <select id="fileFormat" class="form-select">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>
                <button class="btn btn-success w-100">Generate & Download</button>
            </div>
        </div>
    </div>
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



    <script src="JS/price-update-notification.js" defer></script>
    <script src="JS/pricefront.js"></script>
    <script src="JS/notification.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/emailjs-com@3/dist/email.min.js"></script>
    <script>
        emailjs.init("ssI9dsXkLKeH3xhwe");
        document.getElementById('reportOverpriceBtn').addEventListener('click', () => {
            new bootstrap.Modal(document.getElementById('reportModal')).show();
        });

        function showNotif(message, type = 'success') {
            const notif = document.getElementById('notif');
            const notifMsg = document.getElementById('notifMsg');
            const closeBtn = document.getElementById('notifClose');

            notifMsg.textContent = message;
            notif.style.backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';

            notif.style.display = 'block';
            setTimeout(() => notif.style.opacity = '1', 10);

            // Automatically hide after 3 seconds
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

        // Check URL params on load
        const params = new URLSearchParams(window.location.search);
        if (params.get('status') === 'success') showNotif('Report submitted successfully', 'success');
        if (params.get('error')) showNotif(params.get('error'), 'error');

        document.getElementById('stallName').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const stallNum = selected.getAttribute('data-stall') || '';
            const userId = selected.getAttribute('data-userid') || '';
            
            document.getElementById('stallLocation').value = stallNum;
            document.getElementById('vendorUserId').value = userId;
        });

        // Report form OTP handling (signup-like flow, wrapped to avoid collisions)
        (function(){
            let generatedOTP = null;
            const rfForm = document.getElementById('reportForm');
            const rfOtpModal = document.getElementById('otpModalReport');
            const rfOtpClose = document.getElementById('otpReportClose');
            const rfVerifyBtn = document.getElementById('verifyOTPReportBtn');

            function openOtpModal() {
                if (rfOtpModal) rfOtpModal.style.display = 'block';
            }
            function closeOtpModal() {
                if (rfOtpModal) rfOtpModal.style.display = 'none';
                generatedOTP = null;
                document.querySelectorAll('.report-otp-box').forEach(b => b.value = '');
            }

            if (rfOtpClose) rfOtpClose.onclick = closeOtpModal;

            if (!window.emailjs) {
                console.warn('EmailJS not loaded');
            }

            if (rfForm) {
                rfForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const email = document.getElementById('customerEmail').value.trim();
                    const stallUserId = document.getElementById('vendorUserId').value;

                    if (!email) {
                        showNotif('Please provide an email address to receive verification code.', 'error');
                        return;
                    }
                    if (!stallUserId) {
                        showNotif('Please select a vendor to report.', 'error');
                        return;
                    }

                    if (!generatedOTP) {
                        generatedOTP = Math.floor(100000 + Math.random() * 900000);
                        const customerName = document.getElementById('customerName').value.trim() || 'Customer';
                        const templateParams = {
                            to_name: customerName,
                            to_email: email,
                            otp_code: generatedOTP
                        };

                        showNotif('Sending verification code to ' + email + '. Check your inbox or spam folder.', 'success');

                        // Send using EmailJS like signup.php
                        if (window.emailjs && emailjs.send) {
                            emailjs.send('service_l9b49th', 'template_gvu8j9t', templateParams)
                                .then(function(){
                                    openOtpModal();
                                    const boxes = document.querySelectorAll('.report-otp-box');
                                    if (boxes && boxes[0]) boxes[0].focus();
                                })
                                .catch(function(err){
                                    console.error('EmailJS send error', err);
                                    showNotif('Failed to send verification code. Try again later.', 'error');
                                    generatedOTP = null;
                                });
                        } else {
                            console.warn('EmailJS not available - falling back to console OTP (dev)');
                            console.log('OTP for testing:', generatedOTP);
                            openOtpModal();
                        }
                    } else {
                        openOtpModal();
                    }
                });
            }

            if (rfVerifyBtn) {
                rfVerifyBtn.addEventListener('click', function() {
                    const enteredOTP = Array.from(document.querySelectorAll('.report-otp-box')).map(b => b.value).join('');
                    if (!generatedOTP || String(enteredOTP) !== String(generatedOTP)) {
                        showNotif('Incorrect verification code. Please try again.', 'error');
                        return;
                    }

                    // OTP correct — submit via fetch with verified flag
                    const formData = new FormData(rfForm);
                    formData.append('verified', '1');

                    fetch('php/save_report.php', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.success) {
                            closeOtpModal();
                            showNotif(data.message || 'Report submitted successfully', 'success');
                            setTimeout(() => window.location.href = 'pricefront.php?status=success', 1400);
                        } else {
                            const msg = (data && data.message) ? data.message : 'Failed to submit report.';
                            showNotif(msg, 'error');
                        }
                    })
                    .catch(err => {
                        showNotif('Error submitting report: ' + err, 'error');
                    });
                });
            }

            // OTP input autofocus & backspace navigation (signup-like)
            document.addEventListener('input', function(e) {
                if (!e.target.classList || !e.target.classList.contains('report-otp-box')) return;
                const boxes = Array.from(document.querySelectorAll('.report-otp-box'));
                const idx = boxes.indexOf(e.target);
                if (e.target.value.length === 1 && idx < boxes.length - 1) boxes[idx + 1].focus();
            });
            document.addEventListener('keydown', function(e) {
                if (!e.target.classList || !e.target.classList.contains('report-otp-box')) return;
                const boxes = Array.from(document.querySelectorAll('.report-otp-box'));
                const idx = boxes.indexOf(e.target);
                if (e.key === 'Backspace' && !e.target.value && idx > 0) boxes[idx - 1].focus();
            });
        })();

        if (rfOtpClose) rfOtpClose.onclick = () => {
            if (rfOtpModal) rfOtpModal.style.display = 'none';
            generatedReportOTP_report = null;
            document.querySelectorAll('.report-otp-box').forEach(b => b.value = '');
        };

        if (rfForm) {
            rfForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const email = document.getElementById('customerEmail').value.trim();
                const stallUserId = document.getElementById('vendorUserId').value;
                const reportDate = document.getElementById('reportDate').value;

                if (!email) {
                    showNotif('Please provide an email address to receive verification code.', 'error');
                    return;
                }

                if (!stallUserId) {
                    showNotif('Please select a vendor to report.', 'error');
                    return;
                }

                if (!generatedReportOTP_report) {
                    generatedReportOTP_report = Math.floor(100000 + Math.random() * 900000);
                    const customerName = document.getElementById('customerName').value.trim() || 'Customer';

                    const templateParams = {
                        to_name: customerName,
                        to_email: email,
                        otp_code: generatedReportOTP
                    };

                    showNotif('Sending verification code to ' + email + '. Check your inbox or spam folder.', 'success');

                    emailjs.send("service_l9b49th", "template_gvu8j9t", templateParams)
                    .then(() => {
                        if (rfOtpModal) rfOtpModal.style.display = 'block';
                        // focus first box
                        const boxes = document.querySelectorAll('.report-otp-box');
                        if (boxes && boxes[0]) boxes[0].focus();
                    })
                    .catch(err => {
                        showNotif('Failed to send verification code. Try again later.', 'error');
                        generatedReportOTP_report = null;
                    });
                } else {
                    // If OTP already generated and modal is open, do nothing — user should verify
                    if (rfOtpModal) rfOtpModal.style.display = 'block';
                }
            });
        }
        if (rfVerifyBtn) {
            rfVerifyBtn.addEventListener('click', function() {
                const enteredOTP = Array.from(document.querySelectorAll('.report-otp-box')).map(b => b.value).join('');
                if (!generatedReportOTP_report || String(enteredOTP) !== String(generatedReportOTP_report)) {
                    showNotif('Incorrect verification code. Please try again.', 'error');
                    return;
                }

                // OTP correct — submit via fetch with verified flag
                const formData = new FormData(rfForm);
                formData.append('verified', '1');

                // Send as AJAX so we can show messages without redirect
                fetch('php/save_report.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.success) {
                        if (rfOtpModal) rfOtpModal.style.display = 'none';
                        generatedReportOTP_report = null;
                        document.querySelectorAll('.report-otp-box').forEach(b => b.value = '');
                        showNotif(data.message || 'Report submitted successfully', 'success');
                        setTimeout(() => window.location.href = 'pricefront.php?status=success', 1400);
                    } else {
                        const msg = (data && data.message) ? data.message : 'Failed to submit report.';
                        showNotif(msg, 'error');
                    }
                })
                .catch(err => {
                    showNotif('Error submitting report: ' + err, 'error');
                });
            });
        }

        // OTP input autofocus & backspace navigation
        document.addEventListener('input', function(e) {
            if (!e.target.classList || !e.target.classList.contains('report-otp-box')) return;
            const boxes = Array.from(document.querySelectorAll('.report-otp-box'));
            const idx = boxes.indexOf(e.target);
            if (e.target.value.length === 1 && idx < boxes.length - 1) boxes[idx + 1].focus();
        });
        document.addEventListener('keydown', function(e) {
            if (!e.target.classList || !e.target.classList.contains('report-otp-box')) return;
            const boxes = Array.from(document.querySelectorAll('.report-otp-box'));
            const idx = boxes.indexOf(e.target);
            if (e.key === 'Backspace' && !e.target.value && idx > 0) boxes[idx - 1].focus();
        });

    </script>

</body>
</html>
