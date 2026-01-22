
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
    $conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Vendor Dashboard - Tanza Public Market</title>
    <link rel="stylesheet" href="CSS/minimalist-responsive.css">
    <link rel="stylesheet" href="CSS/vendor-dashboard.css">
    <link rel="stylesheet" href="CSS/survey-form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!--<script src="../JS/disable-console-logs.js"></script>
    <script src="../JS/disable-all-notifications.js"></script>
    <script src="../JS/disable-login-requirements.js"></script>
  Shared JS for all pages 
    <script src="../JS/vendor-dashboard-shared.js" defer></script>-->
</head>
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
                        <a href="business-permit.html" class="nav-link">
                            <i class="fas fa-certificate"></i>
                            <span>BUSINESS PERMIT</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="events.html" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>EVENTS</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="notices.html" class="nav-link">
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
                        <a href="cleaning.html" class="nav-link">
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
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                            <i class="fas fa-broom text-blue-500"></i>
                            My Cleaning Requests
                            </h3>
                            
                        </div>

                        <div id="cleaningRequestsStatus" class="cleaning-requests-status grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            <?php if (empty($requests)): ?>
                            <div class="no-requests-message col-span-full text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-200">
                                <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                                <h4 class="text-xl font-semibold text-gray-700">No Cleaning Requests Yet</h4>
                                <p class="text-gray-500 text-sm mt-2">Start by submitting your first cleaning request below.</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                                <div class="cleaning-request-card relative group bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
                                <!-- Top Accent -->
                                <div class="absolute top-0 left-0 w-full h-1.5 
                                    <?= strtolower($req['status']) === 'pending' ? 'bg-yellow-400' : '' ?>
                                    <?= strtolower($req['status']) === 'completed' ? 'bg-green-500' : '' ?>
                                    <?= strtolower($req['status']) === 'cancelled' ? 'bg-red-500' : '' ?>">
                                </div>

                                <div class="p-5">
                                    <div class="flex justify-between items-center mb-3">
                                    <h4 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-spray-can-sparkles text-blue-500"></i>
                                        <?= ucfirst(htmlspecialchars($req['request_type'])) ?>
                                    </h4>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold capitalize 
                                        <?= strtolower($req['status']) === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                        <?= strtolower($req['status']) === 'completed' ? 'bg-green-100 text-green-800' : '' ?>
                                        <?= strtolower($req['status']) === 'cancelled' ? 'bg-red-100 text-red-800' : '' ?>">
                                        <?= htmlspecialchars($req['status']) ?>
                                    </span>
                                    </div>

                                    <ul class="text-sm text-gray-600 space-y-2">
                                    <li><i class="far fa-calendar-alt text-blue-500 mr-1"></i> <strong>Date:</strong> <?= htmlspecialchars($req['preferred_date']) ?></li>
                                    <li><i class="far fa-clock text-blue-500 mr-1"></i> <strong>Time:</strong> <?= htmlspecialchars($req['preferred_time']) ?></li>
                                    <li><i class="fas fa-store text-blue-500 mr-1"></i> <strong>Stall:</strong> <?= htmlspecialchars($req['stall_number']) ?> (<?= htmlspecialchars($req['market_section']) ?>)</li>
                                    <?php if (!empty($req['request_description'])): ?>
                                        <li><i class="fas fa-align-left text-blue-500 mr-1"></i> <strong>Note:</strong> <?= htmlspecialchars($req['request_description']) ?></li>
                                    <?php endif; ?>
                                    </ul>

                                    <div class="mt-4 pt-3 border-t border-gray-100 text-xs text-gray-500 flex justify-between items-center">
                                    <span><i class="far fa-clock mr-1"></i> <?= date("M j, Y g:i A", strtotime($req['created_at'])) ?></span>
                                    
                                    </div>
                                </div>
                                </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        </div>



                        <!-- Simplified Cleaning Request Form -->
                        <div class="cleaning-form-section">
                            <h3>Submit New Cleaning Request</h3>
                            <form class="cleaning-request-form" method="post" action="php/submit_cleaning_request.php">
                            <input type="text" name="userId" value="<?php echo htmlspecialchars($userId); ?>">
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
                
            </div>
        </main>
    </div>
    
    <!-- *** PAGE SPECIFIC SCRIPTS GO HERE (if any) ***
    <script src="../JS/vendor-dashboard.js"></script>
    <script src="cleaning-specific.js"></script> -->
    
</body>
</html>
