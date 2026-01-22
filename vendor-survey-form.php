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

    $stmt = $conn->prepare("SELECT verification_status FROM business_permits WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $permit = $result->fetch_assoc();
    $stmt->close();

    $isVerified = ($permit && isset($permit['verification_status']) && strtolower($permit['verification_status']) === 'approved');

    if (!$isVerified) {
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
    <link rel="stylesheet" href="CSS/minimalist-responsive.css">
    <link rel="stylesheet" href="CSS/vendor-dashboard.css">
    <link rel="stylesheet" href="CSS/survey-form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="CSS/price-update-notification.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/disable-console-logs.js"></script>
    <script src="JS/disable-all-notifications.js"></script>
    <script src="JS/disable-login-requirements.js"></script>
    <!-- Shared JS for all pages -->
    <script src="JS/vendor-dashboard-shared.js" defer></script>
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
                        <li class="nav-item ">
                            <a href="vendor-business-permit.php" class="nav-link">
                                <i class="fas fa-certificate"></i>
                                <span>BUSINESS PERMIT</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="vendor-events.php" class="nav-link <?php echo !$isVerified ? 'disabled-link' : ''; ?>" <?php echo !$isVerified ? 'onclick="return false;"' : ''; ?>>
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
                        <li class="nav-item active">
                            <a href="vendor-survey-form.php" class="nav-link">
                                <i class="fas fa-clipboard-list"></i>
                                <span>SUBMIT SURVEY RESPONSE</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="vendor-cleaning.php" class="nav-link">
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

            <main class="main-content bg-body-tertiary min-vh-100">
    <!-- Top Navbar -->
    <header class="top-navbar">
                <div class="date-time">
                    <span id="currentDate"></span>
                </div>
            </header>

    <!-- Dashboard Content -->
    <div class="container-fluid py-4">
        <section class="card shadow-lg border-0 rounded-4 p-4 bg-white">
            <!-- Section Header -->
            <div class="card-header bg-white border-0 pb-3">
                <h2 class="h4 fw-bold text-dark mb-1">Submit Price Survey</h2>
                <p class="text-muted mb-0">Record commodity prices to track market trends.</p>
            </div>

            <div class="card-body">
                <!-- Product Grid -->
                <?php
                $uniqueCategories = [];
                foreach ($products as $product) {
                    if (!isset($uniqueCategories[$product['product_name']])) {
                        $uniqueCategories[$product['product_name']] = $product;
                    }
                }
                ?>
                
                <?php if (!empty($uniqueCategories)): ?>
                    <div class="row g-4">
                        <?php foreach ($uniqueCategories as $category): ?>
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="card h-100 border-0 shadow-sm rounded-4 position-relative category-card"
                                     style="cursor:pointer;"
                                     onclick="window.location.href='vendor-survey-forms-list.php?product=<?= urlencode($category['product_name']) ?>'">

                                    <img src="<?= htmlspecialchars($category['image']) ?>" 
                                         class="card-img-top rounded-top" 
                                         alt="<?= htmlspecialchars($category['product_name']) ?>" 
                                         onerror="this.src='uploads/products/default.png';"
                                         style="height:200px; object-fit:cover;">

                                    <div class="card-body text-center bg-white">
                                        <h5 class="card-title mb-2 fw-semibold text-dark"><?= htmlspecialchars($category['product_name']) ?></h5>
                                        <div class="overlay position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" 
                                             style="background: rgba(0,0,0,0.5); opacity:0; transition: opacity 0.3s;">
                                            <span class="btn btn-success">Take Survey</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-box-open fa-3x mb-3"></i>
                        <p class="mb-0 fw-semibold">No products found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<!-- Optional: Add hover effect for overlay -->
<style>
.category-card:hover .overlay {
    opacity: 1;
}
</style>

    </div>
    
    <!-- *** PAGE SPECIFIC SCRIPTS GO HERE (if any) *** -->
    
    <script src="../JS/survey-form.js"></script>
    <script src="survey-form-specific.js"></script>
    <script rc="JS/survey-forms.js"></script>
    <script src="JS/price-update-notification.js" defer></script>
    <script src="../JS/notification.js"></script>
    <script>
        document.getElementById("logoutBtn").addEventListener("click", function(e) {
            e.preventDefault();
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "php/logout.php";
            }
        });
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
