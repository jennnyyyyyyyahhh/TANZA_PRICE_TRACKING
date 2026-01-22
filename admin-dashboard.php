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

        $query = "SELECT name, value FROM settings";
    $result = $conn->query($query);

    $settings = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['name']] = $row['value'];
        }
    }
    // get user role
    $stmt = $conn->prepare("SELECT role FROM admin WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();

    if (!$userData || $userData['role'] !== 'admin') {
        header("Location: /login.php");
        exit;
    }

    // continue loading page data
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

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_vendors
        FROM admin
        WHERE role = 'vendor'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $totalVendors = $result->fetch_assoc()['total_vendors'];

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_pending
        FROM business_permits
        WHERE verification_status = 'pending'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $totalPendingPermits = $result->fetch_assoc()['total_pending'];


    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_reports
        FROM reports
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $totalReports = $result->fetch_assoc()['total_reports'];
    // total cleaning requests
    $cleaningRes = $conn->query("SELECT COUNT(*) AS total_cleaning FROM cleaning_requests");
    $totalCleaningRequests = 0;
    if ($cleaningRes) {
        $totalCleaningRequests = (int)$cleaningRes->fetch_assoc()['total_cleaning'];
    }

    // total events
    $eventsRes = $conn->query("SELECT COUNT(*) AS total_events FROM events");
    $totalEvents = 0;
    if ($eventsRes) {
        $totalEvents = (int)$eventsRes->fetch_assoc()['total_events'];
    }

    $stmt->close();
    $conn->close();

    // Default week range (Sunday - Saturday) containing today
    $today = new DateTime();
    $dayOfWeek = (int)$today->format('w'); // 0 (Sun) - 6 (Sat)
    $sunday = clone $today;
    $sunday->modify('-' . $dayOfWeek . ' days');
    $saturday = clone $sunday;
    $saturday->modify('+6 days');
    $defaultFrom = $sunday->format('Y-m-d');
    $defaultTo = $saturday->format('Y-m-d');

    // Use GET params if provided, otherwise default to current week
    $fromDate = !empty($_GET['from_date']) ? $_GET['from_date'] : $defaultFrom;
    $toDate = !empty($_GET['to_date']) ? $_GET['to_date'] : $defaultTo;

    // Display range text for the analytics header
    $displayRange = date('F j, Y', strtotime($fromDate)) . ' to ' . date('F j, Y', strtotime($toDate));


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Tanza Public Market</title>
    
    <!-- Bootstrap 5 - Load first -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS - Load after Bootstrap to override -->
    <!-- Inline styles moved to `CSS/admin-dashboard.css` on 2025-11-22 -->
    <!-- See: `CSS/admin-dashboard.css` -->
    <link rel="stylesheet" href="CSS/admin-dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
                        <span class="logo-text"><?php echo $settings['system_name'];?></span>
                    </a>
                </div>
                
                <!-- Navigation Center -->
                <ul class="nav-menu" id="navMenu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a href="pricefront.php"  style="color: white; text-decoration: none;">PRICES</a>
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
                    <?php include 'admin-notifications.php'; ?>

                    <!-- User Account Button with Dropdown -->
                   <div class="user-account-container dropdown" id="headerAccountContainer">
                        <a
                            href="#profile"
                            class="btn-user-account"
                            id="adminAccountBtn"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <i class="fas fa-user-shield"></i>
                            <span><?php echo htmlspecialchars(($user['first_name'] ?? '')) ?></span>
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
                    </div>
                </div>
        </nav>
    </header>

    <!-- Admin Dashboard Container -->
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <!-- Sidebar Header -->
            <!-- <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-shield-alt"></i>
                    <span>Admin Panel</span>
                </div>
                <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div> -->
            
           
            <?php include __DIR__ . '/admin-sidebar.php'; ?>
            <!-- Logout at the bottom -->
            <!-- <div class="sidebar-footer">
                <a href="logout.php" id="logoutBtn" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>LOG OUT</span>
                </a>
            </div> -->
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Top Navigation Bar -->
            <header class="top-navbar">
                <!-- <div class="top-nav-left">
                    <h1 class="page-title">Admin Dashboard</h1>
                </div> -->
                <div class="date-time">
            <span id="currentDate"><?= date('F d, Y') ?></span>
        </div>
            </header>

            <!-- Dashboard Content -->
            <div class="content-wrapper">
                <section class="content-section active" id="dashboard-section">
                    <div class="section-header">
                        <h2>Admin Dashboard</h2>
                        <p>Welcome back, <span id="">Admin <?php echo htmlspecialchars(($user['first_name'] ?? '')) ?></span>! Here's your market overview.</p>
                    </div>
                    
                    <!-- Stats Overview Cards (4-per-row) -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="stat-card">
                                <div class="stat-card-header">
                                    <h3>Total Vendors</h3>
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-card-body">
                                    <h2><?php echo htmlspecialchars($totalVendors); ?></h2>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="stat-card">
                                <div class="stat-card-header">
                                    <h3>Total Reports</h3>
                                    <i class="fas fa-store"></i>
                                </div>
                                <div class="stat-card-body">
                                    <h2><?php echo htmlspecialchars($totalReports); ?></h2>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="stat-card">
                                <div class="stat-card-header">
                                    <h3>Cleaning Requests</h3>
                                    <i class="fas fa-broom"></i>
                                </div>
                                <div class="stat-card-body">
                                    <h2><?php echo htmlspecialchars($totalCleaningRequests); ?></h2>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="stat-card">
                                <div class="stat-card-header">
                                    <h3>Total Events</h3>
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="stat-card-body">
                                    <h2><?php echo htmlspecialchars($totalEvents); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Market Price Analytics -->
                    <div class="dashboard-section">
                        <h3>Market Price Analytics</h3>
                        <div class="market-analytics">
                            <div style=" margin-bottom:0.5rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                                <div class="date-display" style="flex:1 1 auto; min-width:200px;">
                                    <span>As of: </span><strong id="priceDate"><?php echo htmlspecialchars($displayRange); ?></strong>
                                </div>

                                <div style="flex:0 0 auto;">
                                    <form method="get" class="d-flex align-items-center" style="gap:0.5rem; flex-wrap:nowrap; align-items:center;" id="chartFilterForm">
                                        <label style="font-size:0.95rem; margin-bottom:0;">From:</label>
                                        <input type="date" id="filterFrom" name="from_date" value="<?php echo htmlspecialchars($fromDate ?? ''); ?>">
                                        <label style="font-size:0.95rem; margin-bottom:0;">To:</label>
                                        <input type="date" id="filterTo" name="to_date" value="<?php echo htmlspecialchars($toDate ?? ''); ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                                        <a href="admin-dashboard.php" class="btn btn-sm btn-secondary" style="margin-left:0.25rem;">Clear</a>
                                    </form>
                                </div>

                                <script>
                                    (function(){
                                        var from = document.getElementById('filterFrom');
                                        var to = document.getElementById('filterTo');
                                        if (!from || !to) return;
                                        // When from changes, set minimum selectable date for to
                                        from.addEventListener('change', function(){
                                            try{
                                                if (from.value) {
                                                    to.min = from.value;
                                                    if (to.value && to.value < from.value) {
                                                        // adjust to-date to be not earlier than from-date
                                                        to.value = from.value;
                                                    }
                                                } else {
                                                    to.min = '';
                                                }
                                            }catch(e){}
                                        });
                                        // initialize on load
                                        try{
                                            if (from.value) {
                                                to.min = from.value;
                                                if (to.value && to.value < from.value) {
                                                    to.value = from.value;
                                                }
                                            }
                                        }catch(e){}
                                    })();
                                </script>
                            </div>

                            <?php
                        // Re-create a DB connection (top-level connection was closed earlier)
                        $conn2 = new mysqli($servername, $username, $password, $database);
                        $charts = [];
                        if (!$conn2->connect_error) {
                            // Get distinct product names from `products` table
                            $prodStmt = $conn2->prepare("SELECT DISTINCT product_name FROM products");
                            if ($prodStmt) {
                                $prodStmt->execute();
                                $prodRes = $prodStmt->get_result();
                                $products = [];
                                while ($p = $prodRes->fetch_assoc()) {
                                    $products[] = $p['product_name'];
                                }
                                $prodStmt->close();

                                // Date range already set earlier in the page (defaults to current week)
                                $useDate = (!empty($fromDate) && !empty($toDate));

                                // For each product, compute the modal (most frequent) price per commodity from surveys
                                // Use a derived-table approach to reliably pick the price with the highest count per commodity
                                $dateCondition = '';
                                if ($useDate) {
                                    // created_at assumed to be stored as DATETIME or DATE
                                    $dateCondition = " AND created_at BETWEEN ? AND ?";
                                }

                                $surveySql = "SELECT t.commodity_type, t.price FROM (\n"
                                    . "  SELECT commodity_type, price, COUNT(*) AS cnt\n"
                                    . "  FROM surveys\n"
                                    . "  WHERE product_name = ?" . $dateCondition . "\n"
                                    . "  GROUP BY commodity_type, price\n"
                                    . ") t\n"
                                    . "JOIN (\n"
                                    . "  SELECT commodity_type, MAX(cnt) AS mx FROM (\n"
                                    . "    SELECT commodity_type, price, COUNT(*) AS cnt\n"
                                    . "    FROM surveys\n"
                                    . "    WHERE product_name = ?" . $dateCondition . "\n"
                                    . "    GROUP BY commodity_type, price\n"
                                    . "  ) tt\n"
                                    . "  GROUP BY commodity_type\n"
                                    . ") m ON t.commodity_type = m.commodity_type AND t.cnt = m.mx";

                                $surveyStmt = $conn2->prepare($surveySql);

                                // Prepare statement to fetch commodities for a given product from `products` table
                                $commSql = "SELECT DISTINCT product_commodity FROM products WHERE product_name = ?";
                                $commStmt = $conn2->prepare($commSql);

                                if ($surveyStmt) {
                                    foreach ($products as $prod) {
                                        // get modal prices per commodity from surveys (reliable selection)
                                                // bind product (and optionally dates) for the derived-table query
                                                if ($useDate) {
                                                    // product, from, to  - repeated for inner and outer parts
                                                    $surveyStmt->bind_param('ssssss', $prod, $fromDate, $toDate, $prod, $fromDate, $toDate);
                                                } else {
                                                    $surveyStmt->bind_param('ss', $prod, $prod);
                                                }
                                        $surveyStmt->execute();
                                        $res = $surveyStmt->get_result();
                                        $topPrices = [];
                                        while ($row = $res->fetch_assoc()) {
                                            $commodity = $row['commodity_type'] ?? 'Unknown';
                                            // store the modal price for this commodity
                                            $topPrices[$commodity] = (float) $row['price'];
                                        }

                                        // get list of commodities for this product (show these even if no survey price exists)
                                        $labels = [];
                                        if ($commStmt) {
                                            $commStmt->bind_param('s', $prod);
                                            $commStmt->execute();
                                            $cres = $commStmt->get_result();
                                            while ($crow = $cres->fetch_assoc()) {
                                                $labels[] = $crow['product_commodity'];
                                            }
                                        }

                                        // fallback: if no commodities found in products table, try distinct commodity_type from surveys
                                        if (empty($labels)) {
                                            $labels = array_values(array_unique(array_keys($topPrices)));
                                        }

                                        // If we still have labels, map each to its modal price (include zeros)
                                        if (!empty($labels)) {
                                            $dataVals = [];
                                            $finalLabels = [];
                                            foreach ($labels as $lab) {
                                                $val = isset($topPrices[$lab]) ? $topPrices[$lab] : 0;
                                                $finalLabels[] = $lab;
                                                $dataVals[] = $val;
                                            }

                                            // Create a chart even if some or all values are zero so commodity names remain visible
                                            $charts[] = [
                                                'product' => $prod,
                                                'labels' => array_values($finalLabels),
                                                'data' => $dataVals
                                            ];
                                        }
                                    }
                                    $surveyStmt->close();
                                }
                                if ($commStmt) $commStmt->close();
                            }
                        }
                        $conn2->close();
                        ?>

                        

                        <!-- One chart per product: x = commodity, y = modal price -->
                        <div class="commodity-price-graphs" style="margin-top:1.25rem; display:grid; grid-template-columns: repeat(2, 1fr); gap:1rem; align-items:start;">
                        <?php foreach ($charts as $cidx => $chart):
                            $safeId = 'commodityChart_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $chart['product']);
                        ?>
                            <div class="commodity-price-graph" style="padding:0.25rem;">
                                <h4 style="margin-bottom:0.25rem;">Product: <?php echo htmlspecialchars($chart['product']); ?></h4>
                                <canvas id="<?php echo $safeId; ?>" style="width:100%;height:150px;max-width:100%;display:block;"></canvas>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        
                        <!-- Mobile responsive style for commodity graphs -->
                        <style>
                            @media (max-width: 768px) {
                                .commodity-price-graphs {
                                    grid-template-columns: 1fr !important;
                                }
                                .commodity-price-graph {
                                    width: 100% !important;
                                }
                            }
                        </style>

                        <?php if (!empty($charts)): ?>
                            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                            <script>
                                (function(){
                                    const charts = <?php echo json_encode($charts); ?>;
                                    charts.forEach((c) => {
                                        const safeId = 'commodityChart_' + c.product.replace(/[^A-Za-z0-9_\-]/g, '_');
                                        const el = document.getElementById(safeId);
                                        if (!el) return;
                                        const ctx = el.getContext('2d');
                                        // compute per-bar colors so zero-values appear lighter but still leave label space
                                        const bgColors = c.data.map(v => v > 0 ? 'rgba(62,149,205,0.85)' : 'rgba(200,200,200,0.25)');
                                        const borderColors = c.data.map(v => v > 0 ? 'rgba(62,149,205,1)' : 'rgba(200,200,200,0.4)');

                                        // Force canvas to a fixed pixel height so Chart.js doesn't expand it based on container
                                        // Set element height attribute (not just CSS) and set width to current client width
                                        try {
                                            el.style.height = '150px';
                                            el.height = 150; // explicit pixel height used by Chart.js when responsive=false
                                            el.width = el.clientWidth;
                                        } catch (e) {
                                            // ignore if unable to set
                                        }

                                        new Chart(ctx, {
                                            type: 'bar',
                                            data: {
                                                labels: c.labels,
                                                datasets: [{
                                                    label: 'Most Frequent Price',
                                                    data: c.data,
                                                    backgroundColor: bgColors,
                                                    borderColor: borderColors,
                                                    borderWidth: 1,
                                                    minBarLength: 6
                                                }]
                                            },
                                            options: {
                                                // Use fixed canvas size we've set on the element to avoid layout-driven resizing
                                                responsive: false,
                                                maintainAspectRatio: false,
                                                plugins: {
                                                    legend: { display: false }
                                                },
                                                scales: {
                                                    y: {
                                                            beginAtZero: true,
                                                            suggestedMin: 0,
                                                            max: 200,
                                                            ticks: { stepSize: 50, callback: function(value){ return '₱' + value; }, font: { size: 11 } },
                                                            title: { display: true, text: 'Price (PHP)', font: { size: 11 } },
                                                            grid: { drawTicks: true, color: function(context){ return context.tick.value % 50 === 0 ? '#e0e0e0' : 'transparent'; } }
                                                        },
                                                    x: {
                                                        title: { display: true, text: 'Commodity', font: { size: 11 } },
                                                        ticks: { autoSkip: false, maxRotation: 45, minRotation: 0, font: { size: 11 } }
                                                    }
                                                }
                                            }
                                        });
                                    });
                                })();
                            </script>
                        <?php endif; ?>
                    </div>
                        </div>
                        
                </section>
                
                <!-- Other Content Sections (hidden by default) -->
                

                <!-- Vendor Suspension Modal -->
                <div id="suspensionModal" class="suspension-modal" style="display: none;">
                    <div class="suspension-modal-overlay"></div>
                    <div class="suspension-modal-content">
                        <div class="suspension-modal-header">
                            <h3>Suspend Vendor</h3>
                            <button class="close-suspension-modal" id="closeSuspensionModal">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="suspension-modal-body">
                            <div class="vendor-info">
                                <div class="vendor-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="vendor-details">
                                    <h4 id="suspendVendorName">Vendor Name</h4>
                                    <p id="suspendVendorId">ID: V001</p>
                                </div>
                            </div>
                            
                            <div class="suspension-form">
                                <div class="form-group">
                                    <label for="suspensionDuration">Suspension Duration:</label>
                                    <select id="suspensionDuration" class="suspension-select" required>
                                        <option value="">Select Duration</option>
                                        <option value="1-day">1 Day</option>
                                        <option value="3-days">3 Days</option>
                                        <option value="1-week">1 Week</option>
                                        <option value="1-month">1 Month</option>
                                        <option value="indefinite">Indefinite (Admin Review Required)</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="suspensionReason">Reason for Suspension:</label>
                                    <select id="suspensionReason" class="suspension-select" required>
                                        <option value="">Select Reason</option>
                                        <option value="hygiene-violation">Hygiene Violation</option>
                                        <option value="quality-issues">Product Quality Issues</option>
                                        <option value="customer-complaints">Multiple Customer Complaints</option>
                                        <option value="payment-issues">Payment/Fee Issues</option>
                                        <option value="safety-violation">Safety Regulation Violation</option>
                                        <option value="unauthorized-products">Selling Unauthorized Products</option>
                                        <option value="disruptive-behavior">Disruptive Behavior</option>
                                        <option value="permit-issues">Permit/License Issues</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="suspensionNotes">Additional Notes:</label>
                                    <textarea id="suspensionNotes" class="suspension-textarea" placeholder="Enter detailed explanation for the suspension..." rows="4"></textarea>
                                </div>
                                
                                <div class="suspension-summary">
                                    <div class="summary-item">
                                        <span class="summary-label">Suspension Start:</span>
                                        <span class="summary-value" id="suspensionStartDate">Immediate</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Suspension End:</span>
                                        <span class="summary-value" id="suspensionEndDate">-</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Status After Suspension:</span>
                                        <span class="summary-value">Requires Admin Review</span>
                                    </div>
                                </div>
                                
                                <div class="suspension-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div class="warning-text">
                                        <strong>Warning:</strong> This action will immediately suspend the vendor's access to the market. 
                                        The vendor will be notified via email and SMS about the suspension details.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="suspension-modal-footer">
                            <button class="btn-secondary" id="cancelSuspension">Cancel</button>
                            <button class="btn-danger" id="confirmSuspension">
                                <i class="fas fa-pause"></i> Suspend Vendor
                            </button>
                        </div>
                    </div>
                </div>
                
                <section class="content-section" id="survey-form-section">
                    <div class="section-header">
                        <h2>Submit Survey Form</h2>
                        <p>Create and distribute market price survey forms to vendors.</p>
                    </div>
                    <div class="section-content">
                        <div class="survey-form-container">
                            <form id="marketPriceSurveyForm" class="market-survey-form">
                                <div class="form-header">
                                    <div class="form-group">
                                        <label for="surveyDate">Survey Date:</label>
                                        <input type="date" id="surveyDate" name="surveyDate" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="marketLocation">Market Location:</label>
                                        <select id="marketLocation" name="marketLocation" required>
                                            <option value="">Select Barangay</option>
                                            <option value="amaya-1">Amaya I</option>
                                            <option value="amaya-2">Amaya II</option>
                                            <option value="amaya-3">Amaya III</option>
                                            <option value="amaya-4">Amaya IV</option>
                                            <option value="amaya-5">Amaya V</option>
                                            <option value="amaya-6">Amaya VI</option>
                                            <option value="amaya-7">Amaya VII</option>
                                            <option value="amaya-8">Amaya VIII</option>
                                            <option value="amaya-9">Amaya IX</option>
                                            <option value="amaya-10">Amaya X</option>
                                            <option value="amaya-11">Amaya XI</option>
                                            <option value="bagtas">Bagtas</option>
                                            <option value="biga">Biga</option>
                                            <option value="bucal">Bucal</option>
                                            <option value="bunducan">Bunducan</option>
                                            <option value="capipisa">Capipisa</option>
                                            <option value="daang-amaya-1">Daang Amaya I</option>
                                            <option value="daang-amaya-2">Daang Amaya II</option>
                                            <option value="daang-amaya-3">Daang Amaya III</option>
                                            <option value="halayhay">Halayhay</option>
                                            <option value="julugan-1">Julugan I</option>
                                            <option value="julugan-2">Julugan II</option>
                                            <option value="julugan-3">Julugan III</option>
                                            <option value="julugan-4">Julugan IV</option>
                                            <option value="julugan-5">Julugan V</option>
                                            <option value="julugan-6">Julugan VI</option>
                                            <option value="julugan-7">Julugan VII</option>
                                            <option value="julugan-8">Julugan VIII</option>
                                            <option value="lambingan">Lambingan</option>
                                            <option value="muzon">Muzon</option>
                                            <option value="pasong-langka">Pasong Langka</option>
                                            <option value="poblacion-1">Poblacion I</option>
                                            <option value="poblacion-2">Poblacion II</option>
                                            <option value="poblacion-3">Poblacion III</option>
                                            <option value="poblacion-4">Poblacion IV</option>
                                            <option value="sahud-ulan">Sahud-Ulan</option>
                                            <option value="santol">Santol</option>
                                            <option value="tres-cruses">Tres Cruses</option>
                                            <option value="vibora">Vibora</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="vendorName">Vendor Name:</label>
                                        <input type="text" id="vendorName" name="vendorName" placeholder="Enter vendor name">
                                    </div>
                                    <div class="form-group">
                                        <label for="surveyType">Survey Type:</label>
                                        <select id="surveyType" name="surveyType" required>
                                            <option value="weekly">Weekly Price Survey</option>
                                            <option value="monthly">Monthly Price Survey</option>
                                            <option value="special">Special Event Survey</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Rice Category -->
                                <div class="commodity-category">
                                    <h3>Rice</h3>
                                    <div class="commodity-items" id="riceItems">
                                        <div class="commodity-item">
                                            <div class="item-header">
                                                <div class="item-name">
                                                    <select class="commodity-select">
                                                        <option value="">Select Rice Type</option>
                                                        <option value="regular-milled">Regular Milled Rice</option>
                                                        <option value="well-milled">Well-Milled Rice</option>
                                                        <option value="fancy-rice">Fancy Rice</option>
                                                        <option value="premium">Premium Rice</option>
                                                        <option value="special-rice">Special Rice</option>
                                                        <option value="brown-rice">Brown Rice</option>
                                                        <option value="black-rice">Black Rice</option>
                                                        <option value="red-rice">Red Rice</option>
                                                        <option value="glutinous-rice">Glutinous Rice (Malagkit)</option>
                                                    </select>
                                                </div>
                                                <div class="item-variant">
                                                    <input type="text" placeholder="Variant/Brand (optional)" class="variant-input">
                                                </div>
                                                <div class="remove-item">
                                                    <button type="button" class="remove-btn"><i class="fas fa-times"></i></button>
                                                </div>
                                            </div>
                                            <div class="item-details">
                                                <div class="price-input">
                                                    <label>Price (₱):</label>
                                                    <input type="number" min="0" step="0.01" placeholder="0.00" class="price-field">
                                                </div>
                                                <div class="quantity-input">
                                                    <label>Quantity Unit:</label>
                                                    <select class="unit-select">
                                                        <option value="per-kilo">Per Kilo</option>
                                                        <option value="per-sack-25kg">Per Sack (25kg)</option>
                                                        <option value="per-sack-50kg">Per Sack (50kg)</option>
                                                        <option value="per-ganta">Per Ganta</option>
                                                    </select>
                                                </div>
                                                <div class="quality-input">
                                                    <label>Quality:</label>
                                                    <select class="quality-select">
                                                        <option value="excellent">Excellent</option>
                                                        <option value="good" selected>Good</option>
                                                        <option value="average">Average</option>
                                                        <option value="poor">Poor</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="item-notes">
                                                <label>Notes:</label>
                                                <textarea placeholder="Additional notes about this item"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="add-item-btn" data-category="rice">
                                        <i class="fas fa-plus"></i> Add Rice Item
                                    </button>
                                </div>

                                <!-- Fruits Category -->
                                <div class="commodity-category">
                                    <h3>Fruits</h3>
                                    <div class="commodity-items" id="fruitItems">
                                        <div class="commodity-item">
                                            <div class="item-header">
                                                <div class="item-name">
                                                    <select class="commodity-select">
                                                        <option value="">Select Fruit</option>
                                                        <option value="apple">Apple</option>
                                                        <option value="banana">Banana</option>
                                                        <option value="orange">Orange</option>
                                                        <option value="mango">Mango</option>
                                                        <option value="papaya">Papaya</option>
                                                        <option value="pineapple">Pineapple</option>
                                                        <option value="watermelon">Watermelon</option>
                                                        <option value="grapes">Grapes</option>
                                                        <option value="avocado">Avocado</option>
                                                        <option value="lemon">Lemon/Calamansi</option>
                                                        <option value="strawberry">Strawberry</option>
                                                    </select>
                                                </div>
                                                <div class="item-variant">
                                                    <input type="text" placeholder="Variety (optional)" class="variant-input">
                                                </div>
                                                <div class="remove-item">
                                                    <button type="button" class="remove-btn"><i class="fas fa-times"></i></button>
                                                </div>
                                            </div>
                                            <div class="item-details">
                                                <div class="price-input">
                                                    <label>Price (₱):</label>
                                                    <input type="number" min="0" step="0.01" placeholder="0.00" class="price-field">
                                                </div>
                                                <div class="quantity-input">
                                                    <label>Quantity Unit:</label>
                                                    <select class="unit-select">
                                                        <option value="per-kilo">Per Kilo</option>
                                                        <option value="per-piece">Per Piece</option>
                                                        <option value="per-dozen">Per Dozen</option>
                                                        <option value="per-box">Per Box</option>
                                                    </select>
                                                </div>
                                                <div class="quality-input">
                                                    <label>Quality:</label>
                                                    <select class="quality-select">
                                                        <option value="excellent">Excellent</option>
                                                        <option value="good" selected>Good</option>
                                                        <option value="average">Average</option>
                                                        <option value="poor">Poor</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="item-notes">
                                                <label>Notes:</label>
                                                <textarea placeholder="Additional notes about this item"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="add-item-btn" data-category="fruits">
                                        <i class="fas fa-plus"></i> Add Fruit Item
                                    </button>
                                </div>

                                <!-- Vegetables Category -->
                                <div class="commodity-category">
                                    <h3>Vegetables</h3>
                                    <div class="commodity-items" id="vegetableItems">
                                        <div class="commodity-item">
                                            <div class="item-header">
                                                <div class="item-name">
                                                    <select class="commodity-select">
                                                        <option value="">Select Vegetable</option>
                                                        <option value="tomato">Tomato</option>
                                                        <option value="potato">Potato</option>
                                                        <option value="onion">Onion</option>
                                                        <option value="garlic">Garlic</option>
                                                        <option value="carrot">Carrot</option>
                                                        <option value="cabbage">Cabbage</option>
                                                        <option value="eggplant">Eggplant</option>
                                                        <option value="bell-pepper">Bell Pepper</option>
                                                        <option value="squash">Squash</option>
                                                        <option value="string-beans">String Beans (Sitaw)</option>
                                                        <option value="bitter-gourd">Bitter Gourd (Ampalaya)</option>
                                                    </select>
                                                </div>
                                                <div class="item-variant">
                                                    <input type="text" placeholder="Variety (optional)" class="variant-input">
                                                </div>
                                                <div class="remove-item">
                                                    <button type="button" class="remove-btn"><i class="fas fa-times"></i></button>
                                                </div>
                                            </div>
                                            <div class="item-details">
                                                <div class="price-input">
                                                    <label>Price (₱):</label>
                                                    <input type="number" min="0" step="0.01" placeholder="0.00" class="price-field">
                                                </div>
                                                <div class="quantity-input">
                                                    <label>Quantity Unit:</label>
                                                    <select class="unit-select">
                                                        <option value="per-kilo">Per Kilo</option>
                                                        <option value="per-piece">Per Piece</option>
                                                        <option value="per-bundle">Per Bundle</option>
                                                        <option value="per-sack">Per Sack</option>
                                                    </select>
                                                </div>
                                                <div class="quality-input">
                                                    <label>Quality:</label>
                                                    <select class="quality-select">
                                                        <option value="excellent">Excellent</option>
                                                        <option value="good" selected>Good</option>
                                                        <option value="average">Average</option>
                                                        <option value="poor">Poor</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="item-notes">
                                                <label>Notes:</label>
                                                <textarea placeholder="Additional notes about this item"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="add-item-btn" data-category="vegetables">
                                        <i class="fas fa-plus"></i> Add Vegetable Item
                                    </button>
                                </div>

                                <!-- Other Categories Button -->
                                <div class="add-category-container">
                                    <button type="button" id="addCategoryBtn" class="add-category-btn">
                                        <i class="fas fa-plus-circle"></i> Add New Category
                                    </button>
                                </div>

                                <!-- Survey Notes -->
                                <div class="survey-notes">
                                    <label for="surveyNotes">Survey Notes / Observations:</label>
                                    <textarea id="surveyNotes" name="surveyNotes" placeholder="Enter any additional notes, market observations, or trends..."></textarea>
                                </div>

                                <!-- Form Controls -->
                                <div class="form-controls">
                                    <button type="submit" class="btn-primary">Submit Survey</button>
                                    <button type="button" class="btn-secondary" id="saveDraftBtn">Save as Draft</button>
                                    <button type="reset" class="btn-secondary">Clear Form</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
                
                <section class="content-section" id="report-management-section">
                    <div class="section-header">
                        <h2>Report Management</h2>
                        <p>Handle incident and customer reports about vendors.</p>
                    </div>
                    <div class="section-content">
                        <div class="report-management-controls">
                            <div class="filter-options">
                                <select id="reportStatusFilter">
                                    <option value="all">All Statuses</option>
                                    <option value="new">New</option>
                                    <option value="investigating">Investigating</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="dismissed">Dismissed</option>
                                </select>
                                <select id="reportCategoryFilter">
                                    <option value="all">All Categories</option>
                                    <option value="product-quality">Product Quality</option>
                                    <option value="pricing">Pricing Issues</option>
                                    <option value="hygiene">Hygiene Concerns</option>
                                    <option value="service">Customer Service</option>
                                    <option value="safety">Safety Issues</option>
                                    <option value="fraud">Fraudulent Practices</option>
                                </select>
                            </div>
                            <div class="report-actions-global">
                                <button class="btn-sm btn-primary"><i class="fas fa-file-export"></i> Export Reports</button>
                                <button class="btn-sm btn-secondary"><i class="fas fa-print"></i> Print List</button>
                            </div>
                        </div>

                        <div class="report-list-container">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAllReports"></th>
                                        <th>ID</th>
                                        <th>Report Date</th>
                                        <th>Customer</th>
                                        <th>Vendor Name</th>
                                        <th>Stall</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="checkbox" class="report-select"></td>
                                        <td>R001</td>
                                        <td>Sep 24, 2025</td>
                                        <td>Elena Cruz</td>
                                        <td>Maria Santos</td>
                                        <td>A-12</td>
                                        <td><span class="category-badge quality">Product Quality</span></td>
                                        <td><span class="status-badge new">New</span></td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Investigate"><i class="fas fa-search"></i></button>
                                            <button class="btn-icon" title="Dismiss"><i class="fas fa-times"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" class="report-select"></td>
                                        <td>R002</td>
                                        <td>Sep 23, 2025</td>
                                        <td>Marco Diaz</td>
                                        <td>John Rodriguez</td>
                                        <td>B-05</td>
                                        <td><span class="category-badge pricing">Pricing Issues</span></td>
                                        <td><span class="status-badge investigating">Investigating</span></td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Mark Resolved"><i class="fas fa-check"></i></button>
                                            <button class="btn-icon" title="Dismiss"><i class="fas fa-times"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" class="report-select"></td>
                                        <td>R003</td>
                                        <td>Sep 22, 2025</td>
                                        <td>Sofia Reyes</td>
                                        <td>Ana Reyes</td>
                                        <td>C-08</td>
                                        <td><span class="category-badge hygiene">Hygiene Concerns</span></td>
                                        <td><span class="status-badge investigating">Investigating</span></td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Mark Resolved"><i class="fas fa-check"></i></button>
                                            <button class="btn-icon" title="Dismiss"><i class="fas fa-times"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" class="report-select"></td>
                                        <td>R004</td>
                                        <td>Sep 21, 2025</td>
                                        <td>Tomas Garcia</td>
                                        <td>Carlos Mendoza</td>
                                        <td>D-03</td>
                                        <td><span class="category-badge service">Customer Service</span></td>
                                        <td><span class="status-badge resolved">Resolved</span></td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Reopen"><i class="fas fa-redo"></i></button>
                                            <button class="btn-icon" title="Archive"><i class="fas fa-archive"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" class="report-select"></td>
                                        <td>R005</td>
                                        <td>Sep 20, 2025</td>
                                        <td>Miguel Santos</td>
                                        <td>Luisa Tan</td>
                                        <td>E-10</td>
                                        <td><span class="category-badge safety">Safety Issues</span></td>
                                        <td><span class="status-badge new">New</span></td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Investigate"><i class="fas fa-search"></i></button>
                                            <button class="btn-icon" title="Dismiss"><i class="fas fa-times"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" class="report-select"></td>
                                        <td>R006</td>
                                        <td>Sep 19, 2025</td>
                                        <td>Isabella Lopez</td>
                                        <td>Pedro Dominguez</td>
                                        <td>A-05</td>
                                        <td><span class="category-badge fraud">Fraudulent Practices</span></td>
                                        <td><span class="status-badge dismissed">Dismissed</span></td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Reopen"><i class="fas fa-redo"></i></button>
                                            <button class="btn-icon" title="Archive"><i class="fas fa-archive"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="report-details-container" style="display: none;">
                            <div class="details-header">
                                <h3>Report Details <span id="reportIdDisplay">R001</span></h3>
                                <button class="btn-icon" id="closeReportDetails" title="Close"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="details-content">
                                <div class="detail-row">
                                    <div class="detail-label">Report Date:</div>
                                    <div class="detail-value" id="reportDateDisplay">Sep 24, 2025</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Status:</div>
                                    <div class="detail-value">
                                        <select id="reportStatusUpdate">
                                            <option value="new">New</option>
                                            <option value="investigating">Investigating</option>
                                            <option value="resolved">Resolved</option>
                                            <option value="dismissed">Dismissed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Customer:</div>
                                    <div class="detail-value" id="customerNameDisplay">Elena Cruz</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Customer Contact:</div>
                                    <div class="detail-value" id="customerContactDisplay">0912-345-6789</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Vendor:</div>
                                    <div class="detail-value" id="vendorNameDisplay">Maria Santos</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Stall:</div>
                                    <div class="detail-value" id="stallDisplay">A-12</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Category:</div>
                                    <div class="detail-value">
                                        <select id="reportCategoryUpdate">
                                            <option value="product-quality">Product Quality</option>
                                            <option value="pricing">Pricing Issues</option>
                                            <option value="hygiene">Hygiene Concerns</option>
                                            <option value="service">Customer Service</option>
                                            <option value="safety">Safety Issues</option>
                                            <option value="fraud">Fraudulent Practices</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="detail-row full-width">
                                    <div class="detail-label">Description:</div>
                                    <div class="detail-value" id="reportDescriptionDisplay">
                                        <p>The produce sold to me yesterday was not fresh. Some of the vegetables were already beginning to rot, and this was not visible during purchase as they were packed in a way that hid the bad parts. I would like a refund or replacement with fresh produce.</p>
                                    </div>
                                </div>
                                <div class="detail-row full-width">
                                    <div class="detail-label">Admin Notes:</div>
                                    <div class="detail-value">
                                        <textarea id="adminNotesInput" placeholder="Add notes about this report..."></textarea>
                                    </div>
                                </div>
                                <div class="detail-row full-width">
                                    <div class="detail-label">Actions Taken:</div>
                                    <div class="detail-value" id="actionsTakenDisplay">
                                        <ul class="actions-list">
                                            <li><span class="action-date">Sep 24, 2025 10:15 AM</span> - Report received and registered.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="details-footer">
                                <button class="btn-sm btn-primary" id="saveReportChanges">Save Changes</button>
                                <button class="btn-sm btn-secondary" id="contactCustomer">Contact Customer</button>
                                <button class="btn-sm btn-secondary" id="contactVendor">Contact Vendor</button>
                            </div>
                        </div>

                        <div class="report-pagination">
                            <button class="pagination-btn" disabled><i class="fas fa-chevron-left"></i></button>
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn">2</button>
                            <button class="pagination-btn">3</button>
                            <span class="pagination-dots">...</span>
                            <button class="pagination-btn">8</button>
                            <button class="pagination-btn"><i class="fas fa-chevron-right"></i></button>
                        </div>

                        <div class="report-bulk-actions">
                            <select id="bulkReportActionSelect">
                                <option value="">Bulk Actions</option>
                                <option value="mark-investigating">Mark as Investigating</option>
                                <option value="mark-resolved">Mark as Resolved</option>
                                <option value="mark-dismissed">Mark as Dismissed</option>
                                <option value="export-selected">Export Selected</option>
                            </select>
                            <button class="btn-sm btn-secondary" id="applyBulkReportAction">Apply</button>
                        </div>
                    </div>
                </section>
                
                <section class="content-section" id="cleaning-management-section">
                    <div class="section-header">
                        <h2>Cleaning Management</h2>
                        <p>Manage vendor cleaning requests and track completion status.</p>
                    </div>
                    <div class="section-content">
                        <div class="cleaning-management-controls">
                            <div class="filter-options">
                                <select id="cleaningStatusFilter">
                                    <option value="all">All Requests</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                </select>
                                <input type="date" id="requestDateFilter" class="date-filter" title="Filter by request date">
                            </div>
                            <div class="cleaning-actions-global">
                                <button class="btn-sm btn-secondary" id="exportCleaningReportsBtn"><i class="fas fa-file-export"></i> Export Reports</button>
                                <button class="btn-sm btn-info" id="cleaningScheduleBtn"><i class="fas fa-calendar-alt"></i> View Schedule</button>
                            </div>
                        </div>

                        <div class="cleaning-stats">
                            <div class="stat-item">
                                <span class="stat-label">Total Requests:</span>
                                <span class="stat-value" id="totalCleaningRequests">6</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Pending:</span>
                                <span class="stat-value pending" id="pendingCleaningRequests">3</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Completed:</span>
                                <span class="stat-value completed" id="completedCleaningRequests">3</span>
                            </div>
                        </div>

                        <div class="cleaning-list-container">
                            <table class="cleaning-table">
                                <thead>
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Vendor Name</th>
                                        <th>Stall Number</th>
                                        <th>Request Date</th>
                                        <th>Scheduled Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="cleaningRequestsTableBody">
                                    <tr data-request-id="CR001">
                                        <td data-label="Request ID">CR001</td>
                                        <td data-label="Vendor Name">Maria Santos</td>
                                        <td data-label="Stall Number">A-12</td>
                                        <td data-label="Request Date">Oct 4, 2025</td>
                                        <td data-label="Scheduled Time">9:00 AM</td>
                                        <td data-label="Status"><span class="status-badge pending">Pending</span></td>
                                        <td data-label="Actions" class="action-buttons">
                                            <button class="btn-action btn-toggle" title="Mark Complete" onclick="toggleCleaningStatus('CR001')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr data-request-id="CR002">
                                        <td data-label="Request ID">CR002</td>
                                        <td data-label="Vendor Name">John Rodriguez</td>
                                        <td data-label="Stall Number">B-05</td>
                                        <td data-label="Request Date">Oct 4, 2025</td>
                                        <td data-label="Scheduled Time">10:30 AM</td>
                                        <td data-label="Status"><span class="status-badge pending">Pending</span></td>
                                        <td data-label="Actions" class="action-buttons">
                                            <button class="btn-action btn-toggle" title="Mark Complete" onclick="toggleCleaningStatus('CR002')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr data-request-id="CR003">
                                        <td data-label="Request ID">CR003</td>
                                        <td data-label="Vendor Name">Elena Santos</td>
                                        <td data-label="Stall Number">C-08</td>
                                        <td data-label="Request Date">Oct 3, 2025</td>
                                        <td data-label="Scheduled Time">2:00 PM</td>
                                        <td data-label="Status"><span class="status-badge completed">Completed</span></td>
                                        <td data-label="Actions" class="action-buttons">
                                            <button class="btn-action btn-toggle" title="Mark Pending" onclick="toggleCleaningStatus('CR003')">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr data-request-id="CR004">
                                        <td data-label="Request ID">CR004</td>
                                        <td data-label="Vendor Name">Robert Cruz</td>
                                        <td data-label="Stall Number">D-15</td>
                                        <td data-label="Request Date">Oct 4, 2025</td>
                                        <td data-label="Scheduled Time">1:00 PM</td>
                                        <td data-label="Status"><span class="status-badge pending">Pending</span></td>
                                        <td data-label="Actions" class="action-buttons">
                                            <button class="btn-action btn-toggle" title="Mark Complete" onclick="toggleCleaningStatus('CR004')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr data-request-id="CR005">
                                        <td data-label="Request ID">CR005</td>
                                        <td data-label="Vendor Name">Ana Reyes</td>
                                        <td data-label="Stall Number">E-03</td>
                                        <td data-label="Request Date">Oct 4, 2025</td>
                                        <td data-label="Scheduled Time">3:30 PM</td>
                                        <td data-label="Status"><span class="status-badge completed">Completed</span></td>
                                        <td data-label="Actions" class="action-buttons">
                                            <button class="btn-action btn-toggle" title="Mark Pending" onclick="toggleCleaningStatus('CR005')">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr data-request-id="CR006">
                                        <td data-label="Request ID">CR006</td>
                                        <td data-label="Vendor Name">Luis Mendoza</td>
                                        <td data-label="Stall Number">A-07</td>
                                        <td data-label="Request Date">Oct 4, 2025</td>
                                        <td data-label="Scheduled Time">11:00 AM</td>
                                        <td data-label="Status"><span class="status-badge completed">Completed</span></td>
                                        <td data-label="Actions" class="action-buttons">
                                            <button class="btn-action btn-toggle" title="Mark Pending" onclick="toggleCleaningStatus('CR006')">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="cleaning-pagination">
                            <button class="pagination-btn" disabled><i class="fas fa-chevron-left"></i></button>
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn">2</button>
                            <button class="pagination-btn">3</button>
                            <span class="pagination-dots">...</span>
                            <button class="pagination-btn">7</button>
                            <button class="pagination-btn"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </section>
                
                <section class="content-section" id="vendor-requests-section">
                    <div class="section-header">
                        <h2>Vendor Requests</h2>
                        <p>View and process vendor applications and requests.</p>
                    </div>
                    <div class="section-content">
                        <div class="vendor-requests-controls">
                            <div class="filter-options">
                                <select id="requestStatusFilter">
                                    <option value="all">All Requests</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="declined">Declined</option>
                                </select>
                            </div>
                            <div class="request-actions-global">
                                <button id="refreshVendorRequests" class="btn-sm btn-primary"><i class="fas fa-sync-alt"></i> Refresh List</button>
                                <button class="btn-sm btn-secondary"><i class="fas fa-file-export"></i> Export List</button>
                            </div>
                        </div>

                        <div class="request-list-container">
                            <div id="no-vendor-requests" style="display: none; text-align: center; padding: 40px 20px;">
                                <i class="fas fa-user-plus" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                                <h3>No Vendor Requests Found</h3>
                                <p>There are currently no vendor registration requests to process.</p>
                            </div>
                            
                            <table class="request-table" id="vendor-requests-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="vendor-requests-tbody">
                                    <!-- Dynamic content will load here -->
                                    <tr id="sample-row" style="display: none;">
                                        <td>VND123</td>
                                        <td>Oct 10, 2025</td>
                                        <td>Roberto Martinez</td>
                                        <td>roberto@example.com</td>
                                        <td><span class="status-badge pending">Pending</span></td>
                                        <td class="action-buttons">
                                            <button class="btn-icon view-btn" title="View Details" data-id="VND123"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon approve-btn" title="Approve" data-id="VND123"><i class="fas fa-check"></i></button>
                                            <button class="btn-icon decline-btn" title="Decline" data-id="VND123"><i class="fas fa-times"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Modal for vendor details -->
                        <div id="vendor-detail-modal" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3>Vendor Request Details</h3>
                                    <span class="modal-close">&times;</span>
                                </div>
                                <div class="modal-body" id="vendor-detail-content">
                                    <!-- Content will be populated dynamically -->
                                    <div class="vendor-detail-grid">
                                        <div class="vendor-detail-field">
                                            <label>Registration Date</label>
                                            <div class="value" id="detail-registration-date">Oct 9, 2025</div>
                                        </div>
                                        <div class="vendor-detail-field">
                                            <label>Status</label>
                                            <div class="value" id="detail-status"><span class="status-badge pending">Pending</span></div>
                                        </div>
                                        <div class="vendor-detail-field">
                                            <label>First Name</label>
                                            <div class="value" id="detail-first-name">-</div>
                                        </div>
                                        <div class="vendor-detail-field">
                                            <label>Last Name</label>
                                            <div class="value" id="detail-last-name">-</div>
                                        </div>
                                        <div class="vendor-detail-field">
                                            <label>Email</label>
                                            <div class="value" id="detail-email">-</div>
                                        </div>
                                        <div class="vendor-detail-field">
                                            <label>Business Name</label>
                                            <div class="value" id="detail-business-name">-</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-secondary modal-close">Close</button>
                                    <button class="btn btn-success" id="modal-approve-btn">Approve Vendor</button>
                                    <button class="btn btn-danger" id="modal-decline-btn">Decline Vendor</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal styles -->
                        <style>
                            .modal {
                                display: none;
                                position: fixed;
                                z-index: 1050;
                                left: 0;
                                top: 0;
                                width: 100%;
                                height: 100%;
                                overflow: auto;
                                background-color: rgba(0,0,0,0.5);
                            }
                            .modal-content {
                                background-color: #fefefe;
                                margin: 50px auto;
                                padding: 0;
                                border: 1px solid #ddd;
                                border-radius: 6px;
                                width: 80%;
                                max-width: 800px;
                                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                                animation: modalOpen 0.3s ease;
                            }
                            @keyframes modalOpen {
                                from {opacity: 0; transform: translateY(-20px);}
                                to {opacity: 1; transform: translateY(0);}
                            }
                            .modal-header {
                                display: flex;
                                align-items: center;
                                justify-content: space-between;
                                padding: 15px 20px;
                                background-color: #f8f9fa;
                                border-bottom: 1px solid #e9ecef;
                                border-top-left-radius: 6px;
                                border-top-right-radius: 6px;
                            }
                            .modal-header h3 {
                                margin: 0;
                                color: #333;
                            }
                            .modal-close {
                                color: #aaa;
                                font-size: 24px;
                                font-weight: bold;
                                cursor: pointer;
                            }
                            .modal-close:hover {
                                color: #555;
                            }
                            .modal-body {
                                padding: 20px;
                                max-height: 60vh;
                                overflow-y: auto;
                            }
                            .modal-footer {
                                padding: 15px 20px;
                                background-color: #f8f9fa;
                                border-top: 1px solid #e9ecef;
                                display: flex;
                                justify-content: flex-end;
                                gap: 10px;
                            }
                            
                            .vendor-detail-grid {
                                display: grid;
                                grid-template-columns: 1fr 1fr;
                                gap: 15px;
                            }
                            .vendor-detail-field {
                                margin-bottom: 15px;
                            }
                            .vendor-detail-field label {
                                display: block;
                                font-weight: bold;
                                color: #555;
                                margin-bottom: 5px;
                            }
                            .vendor-detail-field .value {
                                padding: 8px 12px;
                                background-color: #f8f9fa;
                                border: 1px solid #e9ecef;
                                border-radius: 4px;
                                color: #333;
                            }
                        </style>
                    </div>
                </section>
                
                <section class="content-section" id="stall-management-section">
                    <div class="section-header">
                        <h2>Stall Management</h2>
                        <p>Manage stall assignments, availability and maintenance.</p>
                    </div>
                    <div class="section-content">
                        <div class="stall-management-controls">
                            <div class="filter-options">
                                <select id="stallSectionFilter">
                                    <option value="all">All Sections</option>
                                    <option value="section-a">Section A</option>
                                    <option value="section-b">Section B</option>
                                    <option value="section-c">Section C</option>
                                    <option value="section-d">Section D</option>
                                    <option value="section-e">Section E</option>
                                </select>
                                <select id="stallStatusFilter">
                                    <option value="all">All Statuses</option>
                                    <option value="occupied">Occupied</option>
                                    <option value="available">Available</option>
                                    <option value="maintenance">Under Maintenance</option>
                                    <option value="reserved">Reserved</option>
                                </select>
                                <select id="stallCategoryFilter">
                                    <option value="all">All Categories</option>
                                    <option value="produce">Produce</option>
                                    <option value="meat">Meat & Poultry</option>
                                    <option value="seafood">Seafood</option>
                                    <option value="dry-goods">Dry Goods</option>
                                    <option value="food-stalls">Food Stalls</option>
                                </select>
                            </div>
                            <div class="stall-actions-global">
                                <button class="btn-sm btn-primary"><i class="fas fa-plus"></i> Add New Stall</button>
                                <button class="btn-sm btn-secondary"><i class="fas fa-file-export"></i> Export List</button>
                            </div>
                        </div>

                        <div class="stall-list-container">
                            <h4>Stall Directory</h4>
                            <table class="stall-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAllStalls"></th>
                                        <th>Stall ID</th>
                                        <th>Section</th>
                                        <th>Category</th>
                                        <th>Size (sqm)</th>
                                        <th>Monthly Fee</th>
                                        <th>Status</th>
                                        <th>Owner/Vendor</th>
                                        <th>Contract Until</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="checkbox" class="stall-select"></td>
                                        <td>A-12</td>
                                        <td>Section A</td>
                                        <td>Produce</td>
                                        <td>15</td>
                                        <td>$450</td>
                                        <td><span class="status-badge occupied">Occupied</span></td>
                                        <td>Maria Santos</td>
                                        <td>Dec 31, 2025</td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon" title="Manage Contract"><i class="fas fa-file-contract"></i></button>
                                            <button class="btn-icon status-action" title="Mark as Available"><i class="fas fa-store-slash"></i></button>
                                            <button class="btn-icon status-action" title="Mark Under Maintenance"><i class="fas fa-tools"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" class="stall-select"></td>
                                        <td>B-05</td>
                                        <td>Section B</td>
                                        <td>Meat & Poultry</td>
                                        <td>20</td>
                                        <td>$600</td>
                                        <td><span class="status-badge occupied">Occupied</span></td>
                                        <td>John Rodriguez</td>
                                        <td>Nov 15, 2025</td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon" title="Manage Contract"><i class="fas fa-file-contract"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" class="stall-select"></td>
                                        <td>C-08</td>
                                        <td>Section C</td>
                                        <td>Seafood</td>
                                        <td>18</td>
                                        <td>$550</td>
                                        <td><span class="status-badge occupied">Occupied</span></td>
                                        <td>Ana Reyes</td>
                                        <td>Oct 31, 2025</td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon" title="Manage Contract"><i class="fas fa-file-contract"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" class="stall-select"></td>
                                        <td>A-05</td>
                                        <td>Section A</td>
                                        <td>Produce</td>
                                        <td>15</td>
                                        <td>$450</td>
                                        <td><span class="status-badge available">Available</span></td>
                                        <td>—</td>
                                        <td>—</td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon primary-action" title="Assign Stall"><i class="fas fa-user-plus"></i></button>
                                            <button class="btn-icon status-action" title="Mark as Reserved"><i class="fas fa-bookmark"></i></button>
                                            <button class="btn-icon status-action" title="Mark Under Maintenance"><i class="fas fa-tools"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" class="stall-select"></td>
                                        <td>D-03</td>
                                        <td>Section D</td>
                                        <td>Dry Goods</td>
                                        <td>12</td>
                                        <td>$400</td>
                                        <td><span class="status-badge occupied">Occupied</span></td>
                                        <td>Carlos Mendoza</td>
                                        <td>Feb 28, 2026</td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon" title="Manage Contract"><i class="fas fa-file-contract"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" class="stall-select"></td>
                                        <td>E-10</td>
                                        <td>Section E</td>
                                        <td>Food Stalls</td>
                                        <td>25</td>
                                        <td>$750</td>
                                        <td><span class="status-badge occupied">Occupied</span></td>
                                        <td>Luisa Tan</td>
                                        <td>Jan 15, 2026</td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon" title="Manage Contract"><i class="fas fa-file-contract"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" class="stall-select"></td>
                                        <td>B-09</td>
                                        <td>Section B</td>
                                        <td>Meat & Poultry</td>
                                        <td>20</td>
                                        <td>$600</td>
                                        <td><span class="status-badge maintenance">Under Maintenance</span></td>
                                        <td>—</td>
                                        <td>—</td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon primary-action" title="Maintenance Log"><i class="fas fa-clipboard-list"></i></button>
                                            <button class="btn-icon status-action" title="Mark as Available"><i class="fas fa-check-circle"></i></button>
                                            <button class="btn-icon status-action" title="Schedule Repair"><i class="fas fa-hammer"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="checkbox" class="stall-select"></td>
                                        <td>C-03</td>
                                        <td>Section C</td>
                                        <td>Seafood</td>
                                        <td>18</td>
                                        <td>$550</td>
                                        <td><span class="status-badge reserved">Reserved</span></td>
                                        <td>Roberto Martinez (Pending)</td>
                                        <td>Starts Oct 1, 2025</td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon primary-action" title="Application Status"><i class="fas fa-clipboard-check"></i></button>
                                            <button class="btn-icon status-action" title="Confirm Occupancy"><i class="fas fa-user-check"></i></button>
                                            <button class="btn-icon status-action" title="Cancel Reservation"><i class="fas fa-times-circle"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="stall-details-container" style="display: none;">
                            <div class="details-header">
                                <h3>Stall Details <span id="stallIdDisplay">A-12</span></h3>
                                <button class="btn-icon" id="closeStallDetails" title="Close"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="details-content">
                                <!-- Stall details content will load here -->
                            </div>
                        </div>

                        <div class="stall-pagination">
                            <button class="pagination-btn" disabled><i class="fas fa-chevron-left"></i></button>
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn">2</button>
                            <button class="pagination-btn">3</button>
                            <span class="pagination-dots">...</span>
                            <button class="pagination-btn">12</button>
                            <button class="pagination-btn"><i class="fas fa-chevron-right"></i></button>
                        </div>

                        <div class="stall-bulk-actions">
                            <select id="bulkStallActionSelect">
                                <option value="">Bulk Actions</option>
                                <option value="mark-available">Mark as Available</option>
                                <option value="mark-maintenance">Mark Under Maintenance</option>
                                <option value="export-selected">Export Selected</option>
                                <option value="print-selected">Print Selected</option>
                            </select>
                            <button class="btn-sm btn-secondary" id="applyBulkStallAction">Apply</button>
                        </div>

                        <div class="stall-summary">
                            <div class="summary-item">
                                <span class="summary-label">Total Stalls:</span>
                                <span class="summary-value">150</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Occupied:</span>
                                <span class="summary-value">142</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Available:</span>
                                <span class="summary-value">5</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Under Maintenance:</span>
                                <span class="summary-value">2</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Reserved:</span>
                                <span class="summary-value">1</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Occupancy Rate:</span>
                                <span class="summary-value">95%</span>
                            </div>
                        </div>
                    </div>
                </section>
                
                <section class="content-section" id="notification-management-section">
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

                <!-- Business Permit Section -->
                <section class="content-section" id="business-permit-section">
                    <div class="section-header">
                        <h2>Business Permit Management</h2>
                        <p>Review and manage vendor business permit applications and renewals.</p>
                    </div>
                    <div class="section-content">
                        <div class="permit-management-controls">
                            <div class="filter-options">
                                <select id="permitStatusFilter">
                                    <option value="all">All Status</option>
                                    <option value="pending">Pending Review</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>
                            <div class="permit-actions-global">
                                <button class="btn-sm btn-secondary"><i class="fas fa-file-export"></i> Export List</button>
                            </div>
                        </div>

                        <div class="permit-stats">
                            <div class="stat-item">
                                <span class="stat-label">Total Permits:</span>
                                <span class="stat-value" id="totalPermits">124</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Pending:</span>
                                <span class="stat-value pending" id="pendingPermits">8</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Approved:</span>
                                <span class="stat-value approved" id="approvedPermits">110</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Expired:</span>
                                <span class="stat-value expired" id="expiredPermits">6</span>
                            </div>
                        </div>

                        <div class="permit-list-container">
                            <table class="permit-table">
                                <thead>
                                    <tr>
                                        <th>Permit ID</th>
                                        <th>Vendor Name</th>
                                        <th>Business Name</th>
                                        <th>Stall</th>
                                        <th>Submit Date</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>BP001</td>
                                        <td>Maria Santos</td>
                                        <td>Maria's Fresh Produce</td>
                                        <td>A-12</td>
                                        <td>Jan 15, 2025</td>
                                        <td>Jan 15, 2026</td>
                                        <td><span class="status-badge approved">Approved</span></td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="View"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon" title="Download"><i class="fas fa-download"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>BP002</td>
                                        <td>John Rodriguez</td>
                                        <td>Fresh Meat Shop</td>
                                        <td>B-05</td>
                                        <td>Oct 1, 2025</td>
                                        <td>-</td>
                                        <td><span class="status-badge pending">Pending</span></td>
                                        <td class="action-buttons">
                                            <button class="btn-icon" title="Review"><i class="fas fa-check"></i></button>
                                            <button class="btn-icon" title="Reject"><i class="fas fa-times"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Events Section -->
                <section class="content-section" id="events-section">
                    <div class="section-header">
                        <h2>Events Management</h2>
                        <p>Create and manage market events, workshops, and activities.</p>
                    </div>
                    <div class="section-content">
                        <div class="event-management-controls">
                            <div class="filter-options">
                                <select id="eventTypeFilter">
                                    <option value="all">All Types</option>
                                    <option value="face-to-face">Face-to-Face</option>
                                    <option value="online">Online/Webinar</option>
                                </select>
                                <select id="eventStatusFilter">
                                    <option value="all">All Status</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="event-actions-global">
                                <button class="btn-sm btn-primary"><i class="fas fa-plus"></i> Add New Event</button>
                                <button class="btn-sm btn-secondary"><i class="fas fa-file-export"></i> Export List</button>
                            </div>
                        </div>

                        <div class="events-container">
                            <div class="event-card">
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
                                        <span>Annual celebration showcasing local farmers' produce.</span>
                                    </div>
                                    <div class="event-actions">
                                        <button class="btn-sm btn-primary">Edit Event</button>
                                        <button class="btn-sm btn-secondary">View Registrations</button>
                                        <button class="btn-sm btn-danger">Cancel Event</button>
                                    </div>
                                </div>
                            </div>

                            <div class="event-card">
                                <div class="event-header">
                                    <span class="event-badge online">Online/Webinar</span>
                                    <h3 class="event-title">Digital Marketing for Vendors</h3>
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
                                        <span>Learn how to promote products online and use social media effectively.</span>
                                    </div>
                                    <div class="event-actions">
                                        <button class="btn-sm btn-primary">Edit Event</button>
                                        <button class="btn-sm btn-secondary">View Registrations</button>
                                        <button class="btn-sm btn-danger">Cancel Event</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Notices Section -->
                <section class="content-section" id="notices-section">
                    <div class="section-header">
                        <h2>Notices & Announcements</h2>
                        <p>Manage important notices and announcements for vendors and customers.</p>
                    </div>
                    <div class="section-content">
                        <div class="notice-management-controls">
                            <div class="filter-options">
                                <select id="noticeTypeFilter">
                                    <option value="all">All Types</option>
                                    <option value="general">General</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="policy">Policy Update</option>
                                </select>
                                <select id="noticeStatusFilter">
                                    <option value="all">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                            <div class="notice-actions-global">
                                <button class="btn-sm btn-primary"><i class="fas fa-plus"></i> Add New Notice</button>
                                <button class="btn-sm btn-secondary"><i class="fas fa-file-export"></i> Export List</button>
                            </div>
                        </div>

                        <div class="notices-list">
                            <div class="notice-item">
                                <div class="notice-icon urgent">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                                <div class="notice-details">
                                    <div class="notice-header">
                                        <h4>Market Cleaning Day</h4>
                                        <span class="notice-badge urgent">Urgent</span>
                                    </div>
                                    <p>General cleaning on September 25. Please secure your goods by 8 PM on Sept 24.</p>
                                    <div class="notice-meta">
                                        <span><i class="fas fa-calendar"></i> Posted: Sep 20, 2025</span>
                                        <span><i class="fas fa-eye"></i> 124 views</span>
                                    </div>
                                </div>
                                <div class="notice-actions">
                                    <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn-icon" title="Archive"><i class="fas fa-archive"></i></button>
                                    <button class="btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>

                            <div class="notice-item">
                                <div class="notice-icon general">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <div class="notice-details">
                                    <div class="notice-header">
                                        <h4>Harvest Festival Planning</h4>
                                        <span class="notice-badge general">General</span>
                                    </div>
                                    <p>Vendors meeting on Sept 28 at 4 PM to discuss the upcoming Harvest Festival.</p>
                                    <div class="notice-meta">
                                        <span><i class="fas fa-calendar"></i> Posted: Sep 18, 2025</span>
                                        <span><i class="fas fa-eye"></i> 98 views</span>
                                    </div>
                                </div>
                                <div class="notice-actions">
                                    <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn-icon" title="Archive"><i class="fas fa-archive"></i></button>
                                    <button class="btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>

                            <div class="notice-item">
                                <div class="notice-icon policy">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="notice-details">
                                    <div class="notice-header">
                                        <h4>Price Reporting Reminder</h4>
                                        <span class="notice-badge policy">Policy</span>
                                    </div>
                                    <p>All vendors must submit updated price lists by end of day every Monday and Thursday.</p>
                                    <div class="notice-meta">
                                        <span><i class="fas fa-calendar"></i> Posted: Sep 15, 2025</span>
                                        <span><i class="fas fa-eye"></i> 156 views</span>
                                    </div>
                                </div>
                                <div class="notice-actions">
                                    <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn-icon" title="Archive"><i class="fas fa-archive"></i></button>
                                    <button class="btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Account dropdown is now placed within the header nav area -->

    <!-- <script src="../JS/price-data.js"></script>
    <script src="../JS/report-management.js"></script>
    <script src="../JS/vendor-requests.js"></script>
    <script src="../JS/admin-dashboard.js"></script>
    <script src="../JS/market-price-analytics.js"></script>
    <script src="../JS/stall-management.js"></script>
    <script src="../JS/survey-form.js"></script>
    <script src="../JS/price-management.js"></script>
    <script src="../JS/cleaning-management.js"></script>
    <script src="../JS/vendor-suspension.js"></script>
    <script src="../JS/notification-management.js"></script>
    <script src="../JS/auth-integration.js"></script>
    <script src="../JS/vendor-approval.js"></script>
    <script src="../JS/vendor-access-control.js"></script> -->

    <!-- Responsive JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle functionality
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const navMenu = document.getElementById('navMenu');
            
            if (mobileMenuToggle && navMenu) {
                mobileMenuToggle.addEventListener('click', function() {
                    this.classList.toggle('active');
                    navMenu.classList.toggle('active');
                });
                
                // Close mobile menu when clicking on nav links
                const navLinks = navMenu.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        mobileMenuToggle.classList.remove('active');
                        navMenu.classList.remove('active');
                    });
                });
            }
            
            // Sidebar toggle functionality
            const mobileSidebarTrigger = document.getElementById('mobileSidebarTrigger');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebarClose = document.getElementById('sidebarClose');
            
            // Function to open sidebar
            function openSidebar() {
                if (sidebar && sidebarOverlay) {
                    sidebar.classList.add('active');
                    sidebarOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            }
            
            // Function to close sidebar
            function closeSidebar() {
                if (sidebar && sidebarOverlay) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
            
            // Event listeners for sidebar
            if (mobileSidebarTrigger) {
                mobileSidebarTrigger.addEventListener('click', openSidebar);
            }
            
            if (sidebarClose) {
                sidebarClose.addEventListener('click', closeSidebar);
            }
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
            }
            
            // Close sidebar when clicking on sidebar nav links (mobile)
            const sidebarNavLinks = sidebar?.querySelectorAll('.nav-link');
            if (sidebarNavLinks) {
                sidebarNavLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        if (window.innerWidth <= 992) {
                            closeSidebar();
                        }
                    });
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                // Close mobile menus on desktop
                if (window.innerWidth > 992) {
                    closeSidebar();
                    if (mobileMenuToggle && navMenu) {
                        mobileMenuToggle.classList.remove('active');
                        navMenu.classList.remove('active');
                    }
                }
            });
            
            // Touch gesture support for sidebar (swipe to close)
            let touchStartX = 0;
            let touchEndX = 0;
            
            if (sidebar) {
                sidebar.addEventListener('touchstart', function(e) {
                    touchStartX = e.changedTouches[0].screenX;
                });
                
                sidebar.addEventListener('touchend', function(e) {
                    touchEndX = e.changedTouches[0].screenX;
                    handleSwipe();
                });
                
                function handleSwipe() {
                    const swipeThreshold = 50;
                    const diff = touchStartX - touchEndX;
                    
                    // Swipe left to close sidebar
                    if (diff > swipeThreshold && sidebar.classList.contains('active')) {
                        closeSidebar();
                    }
                }
            }
            
            // Auto-close dropdowns on mobile when clicking outside
            document.addEventListener('click', function(e) {
                const notificationDropdown = document.getElementById('notificationDropdown');
                const accountDropdown = document.getElementById('accountDropdown');
                const notificationBell = document.getElementById('notificationBell');
                const accountBtn = document.getElementById('adminAccountBtn');
                
                // Close notification dropdown
                if (notificationDropdown && !notificationBell?.contains(e.target) && !notificationDropdown.contains(e.target)) {
                    notificationDropdown.style.display = 'none';
                }
                
                // Close account dropdown
                if (accountDropdown && !accountBtn?.contains(e.target) && !accountDropdown.contains(e.target)) {
                    accountDropdown.style.display = 'none';
                }
            });
            
            // Improve dropdown positioning on mobile
            function adjustDropdownPosition() {
                const dropdowns = document.querySelectorAll('.notification-dropdown, .account-dropdown');
                
                dropdowns.forEach(dropdown => {
                    if (window.innerWidth <= 768) {
                        const rect = dropdown.getBoundingClientRect();
                        const viewportWidth = window.innerWidth;
                        
                        // Adjust if dropdown goes off screen
                        if (rect.right > viewportWidth) {
                            dropdown.style.left = '10px';
                            dropdown.style.right = '10px';
                            dropdown.style.width = 'auto';
                        }
                    }
                });
            }
            
            // Call on load and resize
            adjustDropdownPosition();
            window.addEventListener('resize', adjustDropdownPosition);
            
            // Enhanced form validation for mobile
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input, select, textarea');
                
                inputs.forEach(input => {
                    // Add touch-friendly focus styles
                    input.addEventListener('focus', function() {
                        this.parentElement?.classList.add('focused');
                    });
                    
                    input.addEventListener('blur', function() {
                        this.parentElement?.classList.remove('focused');
                    });
                    
                    // Prevent zoom on iOS
                    if (window.innerWidth <= 768) {
                        input.addEventListener('touchstart', function() {
                            if (this.type === 'text' || this.type === 'email' || this.type === 'tel') {
                                this.style.fontSize = '16px';
                            }
                        });
                    }
                });
            });
            
            // Table scroll indicators for mobile
            const tableContainers = document.querySelectorAll('.vendor-list-container, .report-list-container, .request-list-container, .stall-list-container, .cleaning-list-container');
            
            tableContainers.forEach(container => {
                const table = container.querySelector('table');
                if (table && window.innerWidth <= 992) {
                    // Add scroll indicator
                    const scrollIndicator = document.createElement('div');
                    scrollIndicator.innerHTML = '<i class="fas fa-arrow-right"></i> Scroll for more';
                    scrollIndicator.style.cssText = `
                        text-align: center;
                        padding: 10px;
                        background: #f8f9fa;
                        color: #666;
                        font-size: 12px;
                        border-top: 1px solid #dee2e6;
                    `;
                    
                    container.appendChild(scrollIndicator);
                    
                    // Hide indicator when scrolled
                    container.addEventListener('scroll', function() {
                        if (this.scrollLeft > 10) {
                            scrollIndicator.style.display = 'none';
                        } else {
                            scrollIndicator.style.display = 'block';
                        }
                    });
                }
            });
            
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
            
            console.log('📱 Responsive admin dashboard initialized successfully!');
        });
    </script>
</body>
</html>
