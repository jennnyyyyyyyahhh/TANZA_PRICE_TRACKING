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
        SELECT  u.first_name, u.last_name, u.email, u.created_at,
            p.phone_number,p.barangay,  p.city, p.province, p.postal_code,p.user_id
        FROM users u
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE u.id = ? LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $products = [];
    $result = $conn->query("SELECT id, product_name, product_commodity, image FROM products");
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $commoditiesPerCategory = [];
    foreach ($products as $p) {
        $commoditiesPerCategory[$p['product_name']][] = $p['product_commodity'];
    }

    $quantitiesPerProduct = [];
    $result = $conn->query("SELECT product_name, product_quantity FROM products_qty");
    while ($row = $result->fetch_assoc()) {
        $quantitiesPerProduct[$row['product_name']][] = $row['product_quantity'];
    }
    $stmt->close();
    $conn->close();
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
    <title>Price Survey - Tanza Public Market</title>
    <!-- Assuming the user wants to use the CSS files from the previous steps -->
    <link rel="stylesheet" href="../CSS/minimalist-responsive.css">
    <link rel="stylesheet" href="../CSS/vendor-dashboard.css">
    <link rel="stylesheet" href="../CSS/survey-form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/disable-console-logs.js"></script>
    <script src="../JS/disable-all-notifications.js"></script>
    <script src="../JS/disable-login-requirements.js"></script>
    <!-- Shared JS for all pages -->
    <script src="../JS/vendor-dashboard-shared.js" defer></script>
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
                    <div class="section-header">
                        <h2>Submit Price Survey</h2>
                        <p>Record commodity prices to track market trends.</p>
                    </div>

                    <div class="container my-5">
                        <div class="row g-4">
                            <?php
                            // Remove duplicates based on category name
                            $uniqueCategories = [];
                            foreach ($products as $product) {
                                if (!isset($uniqueCategories[$product['product_name']])) {
                                    $uniqueCategories[$product['product_name']] = $product;
                                }
                            }

                            foreach ($uniqueCategories as $category):
                                // Generate a unique modal ID for each product
                                $modalId = 'surveyModal_' . md5($category['product_name']);
                            ?>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card rounded-3 shadow-sm overflow-hidden category-card position-relative"
                                        style="cursor:pointer;"
                                        onclick="window.location.href='vendor-survey-forms-list.php?product=<?= urlencode($category['product_name']) ?>'">
                                        
                                        <img src="<?= htmlspecialchars($category['image']) ?>" 
                                            class="card-img-top" 
                                            alt="<?= htmlspecialchars($category['product_name']) ?>" 
                                            onerror="this.src='uploads/products/default.png';" 
                                            style="height:200px; object-fit:cover;">
                                        
                                        <div class="card-body text-center bg-white">
                                            <h5 class="card-title mb-2"><?= htmlspecialchars($category['product_name']) ?></h5>
                                            <div class="overlay position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" 
                                                style="background: rgba(0,0,0,0.5); opacity:0; transition: opacity 0.3s;">
                                                <span class="btn btn-success">Take Survey</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <!-- Modal for this specific product -->
                                
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </main>      
        </div>      
    </div>



            </div>
        </main>
    </div>
    
    <!-- *** PAGE SPECIFIC SCRIPTS GO HERE (if any) *** -->
    <script src="../JS/vendor-dashboard.js"></script>
    <script src="../JS/survey-form.js"></script>
    <script src="survey-form-specific.js"></script>
    <script rc="JS/survey-forms.js"></script>
    <script>
       document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal').forEach(modal => {
        const modalId = modal.id;
        const addBtn = modal.querySelector(`#addSurveyBtn_${modalId}`);
        const surveyContainer = modal.querySelector('#surveyContainer_' + modalId);

        if (addBtn && surveyContainer) {
            addBtn.addEventListener('click', function() {
                const firstForm = surveyContainer.querySelector('.surveyForm');
                const newForm = firstForm.cloneNode(true);

                // Reset only the user-fillable inputs
                newForm.querySelectorAll('input, textarea, select').forEach(input => {
                    // Skip hidden and readonly fields
                    if (input.type === 'hidden' || input.readOnly) return;

                    if (input.type === 'file') {
                        input.value = '';
                    } else if (input.tagName.toLowerCase() === 'select') {
                        input.selectedIndex = 0;
                    } else {
                        input.value = '';
                    }
                });

                // Ensure PHP sees inputs as arrays
                newForm.querySelectorAll('input, textarea, select').forEach(input => {
                    let baseName = input.name.replace(/\[\]$/, '');
                    input.name = baseName + '[]';
                });

                surveyContainer.appendChild(newForm);
            });
        }
    });
});



    </script>





</body>
</html>
