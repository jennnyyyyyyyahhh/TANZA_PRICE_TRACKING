
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

    // Fetch user's first name
    $stmt = $conn->prepare("
        SELECT first_name
        FROM users
        WHERE id = ? 
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Fetch user's cleaning requests
    $requests = [];
    $stmt2 = $conn->prepare("
        SELECT id, request_type, preferred_date, preferred_time, stall_number, market_section, request_description, status, created_at 
        FROM cleaning_requests 
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt2->bind_param("i", $userId);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    while ($row = $result2->fetch_assoc()) {
        $requests[] = $row;
    }

    $stmt2->close();

    $stmt = $conn->prepare("SELECT verification_status FROM business_permits WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $permit = $result->fetch_assoc();
    $stmt->close();

    if (!$permit || $permit['verification_status'] !== 'approved') {
        // redirect or show error if not approved
        header("Location: vendor-dashboard.php?error=permit_not_verified");
        exit;
    }

        $query = "SELECT name, value FROM settings";
    $results = $conn->query($query);

    $settings = [];

    if ($results) {
        while ($row = $results->fetch_assoc()) {
            $settings[$row['name']] = $row['value'];
        }
    }

    $conn->close();

    // also set a boolean for template use
    $isVerified = ($permit && isset($permit['verification_status']) && strtolower($permit['verification_status']) === 'approved');
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
    <link rel="stylesheet" href="CSS/minimalist-responsive.css">
    <link rel="stylesheet" href="CSS/vendor-dashboard.css?v=2">
    <link rel="stylesheet" href="CSS/survey-form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="CSS/price-update-notification.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Shared JS for all vendor pages -->
    <script src="JS/vendor-dashboard-shared.js" defer></script>
    
    <style>
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
            
            .section-header h2 {
                font-size: 1.3rem;
            }
            
            .section-header p {
                font-size: 0.9rem;
            }
            
            /* Card grid responsive */
            #cleaningRequestsStatus {
                grid-template-columns: 1fr !important;
            }
            
            .d-flex.justify-content-between.align-items-center.mb-4 {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start !important;
            }
            
            .d-flex.justify-content-between.align-items-center.mb-4 h3 {
                font-size: 1.2rem;
            }
            
            .d-flex.justify-content-between.align-items-center.mb-4 button {
                width: 100%;
            }
            
            /* Hide nav menu on mobile */
            .nav-menu {
                display: none !important;
            }
        }
        
        @media (max-width: 576px) {
            .card-body {
                padding: 1rem !important;
            }
            
            .modal-dialog {
                margin: 10px;
            }
            
            .modal-body {
                padding: 1rem !important;
            }
            
            .row.g-3 .col-md-6 {
                margin-bottom: 0.5rem;
            }
            
            /* Smaller header on mobile */
            .nav-container {
                padding: 0 10px;
            }
            
            .logo span {
                display: none;
            }
        }
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
                        <a href="index.php" class="nav-link">HOME</a>
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
                    <li class="nav-item">
                        <a href="vendor-dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>DASHBOARD</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="vendor-business-permit.php" class="nav-link">
                            <i class="fas fa-certificate"></i>
                            <span>BUSINESS PERMIT</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="vendor-events.php" class="nav-link <?php echo empty($isVerified) ? 'disabled-link' : ''; ?>" <?php echo empty($isVerified) ? 'onclick="return false;"' : ''; ?>>
                            <i class="fas fa-calendar-alt"></i>
                            <span>EVENTS</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="vendor-notices.php" class="nav-link <?php echo empty($isVerified) ? 'disabled-link' : ''; ?>" <?php echo empty($isVerified) ? 'onclick="return false;"' : ''; ?>>
                            <i class="fas fa-bullhorn"></i>
                            <span>NOTICES</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="vendor-survey-form.php" class="nav-link <?php echo empty($isVerified) ? 'disabled-link' : ''; ?>" <?php echo empty($isVerified) ? 'onclick="return false;"' : ''; ?>>
                            <i class="fas fa-clipboard-list"></i>
                            <span>SUBMIT SURVEY RESPONSE</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="vendor-cleaning.php" class="nav-link <?php echo empty($isVerified) ? 'disabled-link' : ''; ?>" <?php echo empty($isVerified) ? 'onclick="return false;"' : ''; ?>>
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
                    <div class="section-header">
                        <h2>Request Cleaning</h2>
                        <p>Submit a cleaning request for your stall or surrounding area.</p>
                    </div>
                    <div class="cleaning-status-section py-10">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="fw-bold text-success mb-0 d-flex align-items-center">
                                <i class="fas fa-broom me-2"></i>
                                My Cleaning Requests
                            </h3>
                            <button type="button" class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#cleaningModal">
                                <i class="fas fa-plus-circle me-1"></i> New Cleaning Request
                            </button>
                        </div>




                        <!-- Simplified Cleaning Request Form -->
                        <!-- Trigger Button -->
                        <div class="text-end mb-3">
                            

                            <div id="cleaningRequestsStatus" class="row g-4">
                            <?php if (empty($requests)): ?>
                                <div class="col-12">
                                <div class="text-center py-5 bg-white rounded-3 shadow-sm border border-light">
                                    <i class="fas fa-box-open text-success fs-1 mb-3"></i>
                                    <h5 class="fw-semibold text-secondary">No Cleaning Requests Yet</h5>
                                    <p class="text-muted small">Start by submitting your first cleaning request below.</p>
                                </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($requests as $req): ?>
                                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                                    <div class="card border-0 shadow-sm h-100">
                                    <!-- Status Color Bar -->
                                    <div class="position-absolute top-0 start-0 w-100" style="height:4px;
                                        <?php if (strtolower($req['status'])==='pending') echo 'background:#ffc107;';
                                            elseif (strtolower($req['status'])==='completed') echo 'background:#28a745;';
                                            elseif (strtolower($req['status'])==='cancelled') echo 'background:#dc3545;'; ?>">
                                    </div>

                                    <div class="card-body pt-4">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="fw-semibold text-success">
                                            <i class="fas fa-broom me-2"></i><?= ucfirst(htmlspecialchars($req['request_type'])) ?>
                                        </h6>
                                        <span class="badge rounded-pill text-capitalize 
                                            <?php if (strtolower($req['status'])==='pending') echo 'bg-warning text-dark';
                                                elseif (strtolower($req['status'])==='completed') echo 'bg-success';
                                                elseif (strtolower($req['status'])==='cancelled') echo 'bg-danger'; ?>">
                                            <i class="<?php 
                                            if (strtolower($req['status'])==='pending') echo 'fas fa-hourglass-half';
                                            elseif (strtolower($req['status'])==='completed') echo 'fas fa-check-circle';
                                            elseif (strtolower($req['status'])==='cancelled') echo 'fas fa-times-circle'; ?>"></i>
                                            <?= htmlspecialchars($req['status']) ?>
                                        </span>
                                        </div>

                                        <ul class="list-unstyled small text-muted mb-4">
                                        <li><i class="far fa-calendar-alt text-success me-2"></i><strong>Date:</strong> <?= htmlspecialchars($req['preferred_date']) ?></li>
                                        <li><i class="far fa-clock text-success me-2"></i><strong>Time:</strong> <?= htmlspecialchars($req['preferred_time']) ?></li>
                                        <li><i class="fas fa-store text-success me-2"></i><strong>Stall:</strong> <?= htmlspecialchars($req['stall_number']) ?> (<?= htmlspecialchars($req['market_section']) ?>)</li>
                                        <?php if (!empty($req['request_description'])): ?>
                                            <li><i class="fas fa-align-left text-success me-2"></i><strong>Note:</strong> <?= htmlspecialchars($req['request_description']) ?></li>
                                        <?php endif; ?>
                                        </ul>

                                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                        <span class="small text-secondary"><i class="far fa-clock me-1"></i><?= date("M j, Y g:i A", strtotime($req['created_at'])) ?></span>
                                        <div class="btn-group">
                                            <button type="button" 
                                                class="btn btn-sm btn-outline-success edit-btn"
                                                data-id="<?= $req['id'] ?>"
                                                data-type="<?= htmlspecialchars($req['request_type']) ?>"
                                                data-date="<?= htmlspecialchars($req['preferred_date']) ?>"
                                                data-time="<?= htmlspecialchars($req['preferred_time']) ?>"
                                                data-stall="<?= htmlspecialchars($req['stall_number']) ?>"
                                                data-section="<?= htmlspecialchars($req['market_section']) ?>"
                                                data-desc="<?= htmlspecialchars($req['request_description']) ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <a href="php/delete_request.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></a>
                                        </div>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                            <!-- Modal -->
                            <div class="modal fade" id="cleaningModal" tabindex="-1" aria-labelledby="cleaningModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                    <!-- Header -->
                                    <div class="modal-header bg-gradient bg-success text-white rounded-top-4">
                                        <h5 class="modal-title fw-semibold d-flex align-items-center" id="cleaningModalLabel">
                                        <i class="fas fa-leaf me-2"></i> New Cleaning Request
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <!-- Form -->
                                    <form method="post" action="php/submit_cleaning_request.php" class="needs-validation" novalidate>
                                        <div class="modal-body p-4">
                                        <input type="hidden" name="userId" value="<?php echo htmlspecialchars($userId); ?>">

                                        <!-- Request Type -->
                                        <div class="mb-4">
                                            <label class="form-label fw-semibold text-start d-block">Request Type</label>
                                            <select name="requestType" class="form-select border-0 shadow-sm rounded-3" required>
                                            <option value="" disabled selected>Select request type</option>
                                            <option value="regular">Regular Stall Cleaning</option>
                                            <option value="deep">Deep Cleaning Service</option>
                                            <option value="emergency">Emergency Clean-up</option>
                                            <option value="pest">Pest Control</option>
                                            <option value="waste">Waste Disposal</option>
                                            </select>
                                        </div>

                                        <!-- Date and Time -->
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                            <label class="form-label fw-semibold text-start d-block">Preferred Date</label>
                                            <input type="date" name="preferredDate" class="form-control border-0 shadow-sm rounded-3" required>
                                            </div>
                                            <div class="col-md-6">
                                            <label class="form-label fw-semibold text-start d-block">Preferred Time</label>
                                            <select name="preferredTime" class="form-select border-0 shadow-sm rounded-3" required>
                                                <option value="" disabled selected>Select preferred time</option>
                                                <option value="early">Early Morning (4:00–6:00 AM)</option>
                                                <option value="morning">Morning (8:00–10:00 AM)</option>
                                                <option value="afternoon">Afternoon (2:00–4:00 PM)</option>
                                                <option value="evening">Evening (8:00–10:00 PM)</option>
                                            </select>
                                            </div>
                                        </div>

                                        <!-- Stall and Section -->
                                        <div class="row g-3 mt-3">
                                            <div class="col-md-6">
                                            <label class="form-label fw-semibold text-start d-block">Stall Number</label>
                                            <input type="text" name="stallNumber"  placeholder="Enter stall number" class="form-control border-0 shadow-sm rounded-3" required>
                                            </div>
                                            <div class="col-md-6">
                                            <label class="form-label fw-semibold text-start d-block">Market Section</label>
                                            <select name="marketSection" class="form-select border-0 shadow-sm rounded-3" required>
                                                <option value="" disabled selected>Select market section</option>
                                                <option value="sectionA">Section A – Produce</option>
                                                <option value="sectionB">Section B – Meat/Fish</option>
                                                <option value="sectionC">Section C – Dry Goods</option>
                                                <option value="sectionD">Section D – Mixed Items</option>
                                                <option value="sectionE">Section E – Food Stalls</option>
                                            </select>
                                            </div>
                                        </div>

                                        <!-- Description -->
                                        <div class="mt-4">
                                            <label class="form-label fw-semibold text-start d-block">Additional Details (Optional)</label>
                                            <textarea name="requestDescription" rows="3" class="form-control border-0 shadow-sm rounded-3" placeholder="Describe any specific cleaning needs or concerns..."></textarea>
                                        </div>
                                        </div>

                                        <!-- Footer -->
                                        <div class="modal-footer border-0 pt-0 pb-4 px-4">
                                            <button type="reset" class="btn btn-outline-success rounded-3 px-4">Clear</button>
                                            <button type="submit" class="btn btn-success rounded-3 px-4 text-white fw-semibold">Submit Request</button>
                                        </div>
                                    </form>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                
            </div>
        </main>
    </div>

    <!-- Edit Cleaning Request Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-gradient bg-success text-white rounded-top-4">
                    <h5 class="modal-title fw-semibold d-flex align-items-center" id="editModalLabel">
                    <i class="fas fa-edit me-2"></i> Edit Cleaning Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="editForm" method="post" action="php/update_request.php">
                    <div class="modal-body p-4">
                    <input type="hidden" name="requestId" id="editRequestId">

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-start d-block">Request Type</label>
                        <select name="requestType" id="editRequestType" class="form-select border-0 shadow-sm rounded-3" required>
                        <option value="regular">Regular Stall Cleaning</option>
                        <option value="deep">Deep Cleaning Service</option>
                        <option value="emergency">Emergency Clean-up</option>
                        <option value="pest">Pest Control</option>
                        <option value="waste">Waste Disposal</option>
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                        <label class="form-label fw-semibold text-start d-block">Preferred Date</label>
                        <input type="date" name="preferredDate" id="editPreferredDate" class="form-control border-0 shadow-sm rounded-3" required>
                        </div>
                        <div class="col-md-6">
                        <label class="form-label fw-semibold text-start d-block">Preferred Time</label>
                        <select name="preferredTime" id="editPreferredTime" class="form-select border-0 shadow-sm rounded-3" required>
                            <option value="early">Early Morning (4:00–6:00 AM)</option>
                            <option value="morning">Morning (8:00–10:00 AM)</option>
                            <option value="afternoon">Afternoon (2:00–4:00 PM)</option>
                            <option value="evening">Evening (8:00–10:00 PM)</option>
                        </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                        <label class="form-label fw-semibold text-start d-block">Stall Number</label>
                        <input type="text" name="stallNumber" id="editStallNumber" class="form-control border-0 shadow-sm rounded-3" required>
                        </div>
                        <div class="col-md-6">
                        <label class="form-label fw-semibold text-start d-block">Market Section</label>
                        <select name="marketSection" id="editMarketSection" class="form-select border-0 shadow-sm rounded-3" required>
                            <option value="sectionA">Section A – Produce</option>
                            <option value="sectionB">Section B – Meat/Fish</option>
                            <option value="sectionC">Section C – Dry Goods</option>
                            <option value="sectionD">Section D – Mixed Items</option>
                            <option value="sectionE">Section E – Food Stalls</option>
                        </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-semibold text-start d-block">Additional Details (Optional)</label>
                        <textarea name="requestDescription" id="editRequestDescription" rows="3" class="form-control border-0 shadow-sm rounded-3"></textarea>
                    </div>
                    </div>

                    <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-3 px-4 text-white fw-semibold">Save Changes</button>
                    </div>
                </form>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/price-update-notification.js" defer></script>
    <script src="../JS/notification.js"></script>
    <script>
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

        const params = new URLSearchParams(window.location.search);
                if (params.get('status') === 'success') showNotif('Cleaning request submitted successfully', 'success');
                if (params.get('status') === 'deleted') showNotif('Cleaning request deleted', 'success');
                if (params.get('status') === 'updated') showNotif('Cleaning request updated', 'success');

                if (params.get('error')) showNotif(params.get('error'), 'error');

                document.addEventListener('DOMContentLoaded', function() {
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
        // Ensure preferredDate cannot be today or earlier. Set min to tomorrow.
        (function enforceFutureDates() {
            function toDateInputString(d) {
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            }

            const today = new Date();
            const tomorrow = new Date(today.getTime() + 24 * 60 * 60 * 1000);
            const minStr = toDateInputString(tomorrow);

            // New request date input(s)
            const newDateInputs = document.querySelectorAll('input[name="preferredDate"]');
            newDateInputs.forEach(function(inp) {
                inp.min = minStr;
            });

            // Edit modal date input
            const editDate = document.getElementById('editPreferredDate');
            if (editDate) {
                editDate.min = minStr;

                // When edit modal is opened, if the populated date is in the past, adjust it to tomorrow
                const originalButtons = document.querySelectorAll('.edit-btn');
                originalButtons.forEach(btn => {
                    btn.addEventListener('click', function() {
                        setTimeout(() => {
                            const val = editDate.value;
                            if (!val) return;
                            const valDate = new Date(val + 'T00:00:00');
                            if (valDate < tomorrow) {
                                editDate.value = minStr;
                                showNotif('Selected date was in the past and has been adjusted to the next available date.', 'error');
                            }
                        }, 50);
                    });
                });
            }
        })();
        });
    </script>
    <script>
    (function(){
        const existingRequests = <?php echo json_encode($requests); ?> || [];
        const requestsByDate = {};

        existingRequests.forEach(function(r){
            if (!r || !r.preferred_date) return;
            if (!requestsByDate[r.preferred_date]) requestsByDate[r.preferred_date] = [];
            if (r.preferred_time && !requestsByDate[r.preferred_date].includes(r.preferred_time)) {
                requestsByDate[r.preferred_date].push(r.preferred_time);
            }
        });

        function preserveOriginalTexts(selectEl) {
            if (!selectEl) return;
            Array.from(selectEl.options).forEach(function(opt){
                if (!opt.getAttribute('data-original-text')) opt.setAttribute('data-original-text', opt.textContent);
            });
        }

        function updateTimeOptionsForDate(dateStr, selectEl) {
            if (!selectEl) return;
            const used = requestsByDate[dateStr] || [];
            Array.from(selectEl.options).forEach(function(opt){
                // skip placeholder options without a value
                if (!opt.value) return;
                if (used.includes(opt.value)) {
                    opt.disabled = true;
                    if (!opt.textContent.includes('(Already requested)')) {
                        opt.textContent = (opt.getAttribute('data-original-text') || opt.textContent) + ' (Already requested)';
                    }
                } else {
                    opt.disabled = false;
                    if (opt.getAttribute('data-original-text')) opt.textContent = opt.getAttribute('data-original-text');
                }
            });

            // if the currently selected option is now disabled, clear selection
            if (selectEl.value && selectEl.options[selectEl.selectedIndex] && selectEl.options[selectEl.selectedIndex].disabled) {
                selectEl.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function(){
            const newDateInput = document.querySelector('input[name="preferredDate"]');
            const newTimeSelect = document.querySelector('select[name="preferredTime"]');
            preserveOriginalTexts(newTimeSelect);

            if (newDateInput && newTimeSelect) {
                // update when date changes
                newDateInput.addEventListener('change', function(){
                    updateTimeOptionsForDate(this.value, newTimeSelect);
                });

                // when modal opens, update options based on prefilled date (if any)
                const cleaningModalEl = document.getElementById('cleaningModal');
                if (cleaningModalEl) {
                    cleaningModalEl.addEventListener('show.bs.modal', function(){
                        updateTimeOptionsForDate(newDateInput.value, newTimeSelect);
                    });

                    // when modal hides, restore original option texts and enable all
                    cleaningModalEl.addEventListener('hidden.bs.modal', function(){
                        Array.from(newTimeSelect.options).forEach(function(opt){
                            if (opt.getAttribute('data-original-text')) opt.textContent = opt.getAttribute('data-original-text');
                            opt.disabled = false;
                        });
                        if (newTimeSelect) newTimeSelect.value = '';
                    });
                }
            }
        });
    })();
    </script>
</body>
</html>
