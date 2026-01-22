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

        // check business permit status to allow/disable links
        $stmtPerm = $conn->prepare("SELECT verification_status FROM business_permits WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        if ($stmtPerm) {
            $stmtPerm->bind_param("i", $userId);
            $stmtPerm->execute();
            $resPerm = $stmtPerm->get_result();
            $permRow = $resPerm->fetch_assoc();
            $isVerified = ($permRow && isset($permRow['verification_status']) && strtolower($permRow['verification_status']) === 'approved');
            $stmtPerm->close();
        } else {
            $isVerified = false;
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
        <link rel="stylesheet" href="CSS/vendor-dashboard.css?v=2">
        <link rel="stylesheet" href="CSS/price-update-notification.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                            <li class="nav-item">
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
                            <li class="nav-item">
                                <a href="vendor-notices.php" class="nav-link <?php echo !$isVerified ? 'disabled-link' : ''; ?>" <?php echo !$isVerified ? 'onclick="return false;"' : ''; ?>>
                                    <i class="fas fa-bullhorn"></i>
                                    <span>NOTICES</span>
                                </a>
                            </li>
                            <li class="nav-item active">
                                <a href="vendor-survey-form.php" class="nav-link <?php echo !$isVerified ? 'disabled-link' : ''; ?>" <?php echo !$isVerified ? 'onclick="return false;"' : ''; ?>>
                                    <i class="fas fa-clipboard-list"></i>
                                    <span>SUBMIT SURVEY RESPONSE</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="vendor-cleaning.php" class="nav-link <?php echo !$isVerified ? 'disabled-link' : ''; ?>" <?php echo !$isVerified ? 'onclick="return false;"' : ''; ?>>
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
                            <h2>Price Survey List</h2>
                            <p>View and manage all submitted surveys.</p>
                        </div>

                        <!-- Add New Survey Button -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Your Submitted Surveys</h5>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSurveyModal">
                                <i class="fas fa-plus-circle me-2"></i>Add New Survey
                            </button>
                        </div>

                        <!-- Table List of Surveys -->
                        <div class="table-responsive shadow-sm rounded-3 bg-white p-3">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-success text-center">
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Commodity</th>
                                        <th>Price (₱)</th>
                                        <th>Date Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                <?php
                                        require 'php/connection.php';

                                        // Get selected product from URL (if any)
                                        $productFilter = isset($_GET['product']) ? trim($_GET['product']) : '';

                                        if ($productFilter !== '') {
                                            // If a product name is specified, show only that product's surveys
                                            $surveyQuery = $conn->prepare("
                                                SELECT 
                                                    id, 
                                                    product_name, 
                                                    commodity_type, 
                                                    variety,
                                                    price, 
                                                    quantity_type,
                                                    quality,
                                                    note,
                                                    product_image,
                                                    created_at
                                                FROM surveys
                                                WHERE user_id = ? AND product_name = ?
                                                ORDER BY created_at DESC
                                            ");
                                            $surveyQuery->bind_param("is", $userId, $productFilter);
                                        } else {
                                            // Otherwise, show all surveys for the user
                                            $surveyQuery = $conn->prepare("
                                                SELECT 
                                                    id, 
                                                    product_name, 
                                                    commodity_type, 
                                                    variety,
                                                    price, 
                                                    quantity_type,
                                                    quality,
                                                    note,
                                                    product_image,
                                                    created_at
                                                FROM surveys
                                                WHERE user_id = ?
                                                ORDER BY created_at DESC
                                            ");
                                            $surveyQuery->bind_param("i", $userId);
                                        }

                                        $surveyQuery->execute();
                                        $surveyResult = $surveyQuery->get_result();
                                        $i = 1;

                                        if ($surveyResult->num_rows > 0):
                                            while ($row = $surveyResult->fetch_assoc()):
                                    ?>


                                            <tr>
                                                <td><?= $i++; ?></td>
                                                <td><?= htmlspecialchars($row['product_name']); ?></td>
                                                <td><?= htmlspecialchars($row['commodity_type']); ?></td>
                                                <td>₱<?= number_format($row['price'], 2); ?></td>
                                                <td><?= date("M d, Y", strtotime($row['created_at'])); ?></td>
                                                
                                                <td>
                                                    <button class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form action="php/delete_survey.php" method="POST" 
                                                        onsubmit="return confirm('Are you sure you want to delete this survey?');" 
                                                        style="display:inline;">

                                                        <input type="hidden" name="survey_id" value="<?= htmlspecialchars($row['id']) ?>">
                                                        <input type="hidden" name="product" value="<?= htmlspecialchars($_GET['product'] ?? $row['product_name']) ?>">

                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>


                                                </td>
                                            </tr>
                                    <?php
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-clipboard-list fa-2x mb-2"></i><br>
                                                No surveys submitted yet.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Add Survey Modal -->
                <div class="modal fade" id="addSurveyModal" tabindex="-1" aria-labelledby="addSurveyModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered"> <!-- XXL width for more space -->
                        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">

                            <!-- Header -->
                            <div class="modal-header bg-success text-white py-3 px-4">
                                <h5 class="modal-title fw-bold fs-5 mb-0" id="addSurveyModalLabel">
                                <i class="fas fa-clipboard-list me-2"></i> Take Product Survey
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <!-- Form -->
                            <form id="surveyForm" action="php/submit_survey.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body bg-light p-4">
                                <div class="row g-4">

                                    <!-- LEFT COLUMN: Product Image -->
                                    <div class="col-lg-5">
                                    <div class="card border-0 shadow-sm h-100 rounded-4">
                                        <div class="card-header bg-white border-0 pb-2">
                                        <h6 class="fw-bold text-success mb-0">
                                            <i class="fas fa-image me-2"></i> Product Image Preview
                                        </h6>
                                        </div>
                                        <div class="card-body text-center">
                                        
                                        <div>
                                            <label class="form-label fw-semibold text-success">Upload Product Image</label>
                                            <input class="form-control border-success rounded-3" type="file" name="product_image" id="productImage" accept="image/*">
                                        </div>
                                        <br>
                                        <div class="d-flex justify-content-center mb-3">
                                            <img id="previewImage"
                                                src="https://via.placeholder.com/450x300?text=No+Image"
                                                class="img-fluid rounded-4 border border-success shadow-sm"
                                                style="width: 100%; max-height: 330px; object-fit: contain;"
                                                alt="Product Preview">
                                        </div>
                                        </div>
                                    </div>
                                    </div>

                                    <!-- RIGHT COLUMN: Survey Fields -->
                                    <div class="col-lg-7">
                                    <div class="card border-0 shadow-sm h-100 rounded-4">
                                        <div class="card-header bg-white border-0 pb-2">
                                        <h6 class="fw-bold text-success mb-0">
                                            <i class="fas fa-info-circle me-2"></i> Survey Details
                                        </h6>
                                        </div>

                                        <div class="card-body px-4">
                                        <div class="text-center mb-4">
                                            <h4 class="fw-bold text-dark mb-1">
                                            <?= htmlspecialchars($_GET['product'] ?? '') ?>

                                            </h4>
                                            <p class="text-muted small mb-0">Please complete the product survey details below.</p>
                                        </div>

                                        <div class="row g-4">
                                            <?php
                                                $productNameFromUrl = isset($_GET['product']) ? trim($_GET['product']) : '';
                                            ?>
                                            <input type="hidden" name="product_name" value="<?= htmlspecialchars($productNameFromUrl ?: 'default_product') ?>">

                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars(($user['user_id'] ?? '')) ?>">
                                            <input type="hidden" name="full_name" value="<?= htmlspecialchars($user['first_name'] ?? '')?> <?= htmlspecialchars($user['last_name'] ?? '')?>">
                                            <input type="hidden" name="address" value="<?= htmlspecialchars(($user['barangay'] ?? '')) ?>, <?= htmlspecialchars(($user['city'] ?? '')) ?>, <?= htmlspecialchars(($user['province'] ?? '')) ?>">
                                            <!-- Commodity -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Commodity Type</label>
                                                <select class="form-select border-success rounded-3" name="commodity_type" required>
                                                    <option value="" disabled selected>Select Commodity</option>
                                                    <?php
                                                    $productNameFromUrl = isset($_GET['product']) ? trim($_GET['product']) : '';
                                                    if (isset($commoditiesPerCategory[$productNameFromUrl])) {
                                                        $uniqueCommodities = array_unique($commoditiesPerCategory[$productNameFromUrl]);
                                                        foreach ($uniqueCommodities as $commodity) {
                                                            echo '<option value="' . htmlspecialchars($commodity) . '">' . htmlspecialchars($commodity) . '</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <!-- Brand -->
                                            <div class="col-md-6">
                                            <label class="form-label fw-semibold">Brand / Variety</label>
                                            <input type="text" class="form-control border-success rounded-3" name="variety" placeholder="Enter brand or variety">
                                            </div>

                                            <!-- Price -->
                                            <div class="col-md-6">
                                            <label class="form-label fw-semibold">Price (₱)</label>
                                            <input type="number" step="0.01" class="form-control border-success rounded-3" name="price" placeholder="Enter product price" required>
                                            </div>

                                            <!-- Quantity -->
                                            <div class="col-md-6">
                                            <label class="form-label fw-semibold">Quantity Type</label>
                                            <select class="form-select border-success rounded-3" name="quantity_type" required>
                                                <option value="" disabled selected>Select Quantity Type</option>
                                                <?php
                                                if (isset($quantitiesPerProduct[$productNameFromUrl])) {
                                                    $uniqueQuantities = array_unique($quantitiesPerProduct[$productNameFromUrl]);
                                                    foreach ($uniqueQuantities as $quantity) {
                                                        echo '<option value="' . htmlspecialchars($quantity) . '">' . htmlspecialchars($quantity) . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                            </div>

                                            <!-- Quality -->
                                            <div class="col-md-6">
                                            <label class="form-label fw-semibold">Product Quality</label>
                                            <select class="form-select border-success rounded-3" name="quality" required>
                                                <option value="" disabled selected>Select Quality</option>
                                                <option value="Excellent">Excellent</option>
                                                <option value="Good">Good</option>
                                                <option value="Average">Average</option>
                                                <option value="Poor">Poor</option>
                                            </select>
                                            </div>

                                            <!-- Notes -->
                                            <div class="col-md-6">
                                            <label class="form-label fw-semibold">Additional Notes</label>
                                            <textarea class="form-control border-success rounded-3" name="note" rows="3" placeholder="Optional notes..."></textarea>
                                            </div>

                                            <!-- Hidden Info -->
                                            <input type="hidden" name="product_name" value="<?= htmlspecialchars($_GET['product'] ?? '') ?>">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars(($user['user_id'] ?? '')) ?>">
                                            <input type="hidden" name="address" value="<?= htmlspecialchars(($user['barangay'] ?? '')) ?>, <?= htmlspecialchars(($user['city'] ?? '')) ?>, <?= htmlspecialchars(($user['province'] ?? '')) ?>">
                                        </div>
                                        </div>
                                    </div>
                                    </div>

                                </div>
                                </div>

                                <!-- Footer -->
                                <div class="modal-footer bg-white d-flex justify-content-end py-3">
                                <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-success rounded-3 px-4">
                                    <i class="fas fa-paper-plane me-1"></i> Submit Survey
                                </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Survey Modal -->
                <div class="modal fade" id="editSurveyModal" tabindex="-1" aria-labelledby="editSurveyModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
                        <div class="modal-header bg-primary text-white py-3 px-4">
                            <h5 class="modal-title fw-bold fs-5 mb-0" id="editSurveyModalLabel">
                            <i class="fas fa-edit me-2"></i> Edit Survey
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form id="editSurveyForm" action="php/update_survey.php" method="POST" enctype="multipart/form-data">
                            <div class="modal-body bg-light p-4">
                            <div class="row g-4">

                                <!-- LEFT COLUMN: Image -->
                                <div class="col-lg-5">
                                <div class="card border-0 shadow-sm h-100 rounded-4">
                                    <div class="card-header bg-white border-0 pb-2">
                                    <h6 class="fw-bold text-primary mb-0">
                                        <i class="fas fa-image me-2"></i> Product Image
                                    </h6>
                                    </div>
                                    <div class="card-body text-center">
                                    <div>
                                        <label class="form-label fw-semibold text-primary">Update Image (optional)</label>
                                        <input class="form-control border-primary rounded-3" type="file" name="product_image" id="editProductImage" accept="image/*">
                                    </div>
                                    <br>
                                    <div class="d-flex justify-content-center mb-3">
                                        <img id="editPreviewImage" 
                                            src="https://via.placeholder.com/450x300?text=No+Image"
                                            class="img-fluid rounded-4 border border-primary shadow-sm"
                                            style="width: 100%; max-height: 330px; object-fit: contain;"
                                            alt="Product Preview">
                                    </div>
                                    </div>
                                </div>
                                </div>

                                <!-- RIGHT COLUMN -->
                                <div class="col-lg-7">
                                <div class="card border-0 shadow-sm h-100 rounded-4">
                                    <div class="card-header bg-white border-0 pb-2">
                                    <h6 class="fw-bold text-primary mb-0">
                                        <i class="fas fa-info-circle me-2"></i> Survey Details
                                    </h6>
                                    </div>

                                    <div class="card-body px-4">
                                    <div class="row g-4">
                                        <input type="hidden" name="survey_id" id="editSurveyId">
                                        
                                        <input type="text" value="<?= htmlspecialchars($user['user_id'] ?? '')?>">

                                        <input type="text" value="<?= htmlspecialchars($user['first_name'] ?? '')?> <?= htmlspecialchars($user['last_name'] ?? '')?>">
                                        <div class="col-md-6">
                                        <label class="form-label fw-semibold">Product</label>
                                        <input type="text" class="form-control border-primary rounded-3" name="product_name" id="editProductName" readonly>
                                        </div>

                                        <!-- Commodity -->
                                        <div class="col-md-6">
                                        <label class="form-label fw-semibold">Commodity Type</label>
                                        <select class="form-select border-primary rounded-3" name="commodity_type" id="editCommodityType" required>
                                            <option value="" disabled selected>Select Commodity</option>
                                            <?php
                                            $productNameFromUrl = isset($_GET['product']) ? trim($_GET['product']) : '';
                                            if (isset($commoditiesPerCategory[$productNameFromUrl])) {
                                                $uniqueCommodities = array_unique($commoditiesPerCategory[$productNameFromUrl]);
                                                foreach ($uniqueCommodities as $commodity) {
                                                    echo '<option value="' . htmlspecialchars($commodity) . '">' . htmlspecialchars($commodity) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                        </div>

                                        <div class="col-md-6">
                                        <label class="form-label fw-semibold">Brand / Variety</label>
                                        <input type="text" class="form-control border-primary rounded-3" name="variety" id="editVariety">
                                        </div>

                                        <div class="col-md-6">
                                        <label class="form-label fw-semibold">Price (₱)</label>
                                        <input type="number" step="0.01" class="form-control border-primary rounded-3" name="price" id="editPrice">
                                        </div>

                                        <!-- Quantity -->
                                        <div class="col-md-6">
                                        <label class="form-label fw-semibold">Quantity Type</label>
                                        <select class="form-select border-primary rounded-3" name="quantity_type" id="editQuantityType" required>
                                            <option value="" disabled selected>Select Quantity Type</option>
                                            <?php
                                            if (isset($quantitiesPerProduct[$productNameFromUrl])) {
                                                $uniqueQuantities = array_unique($quantitiesPerProduct[$productNameFromUrl]);
                                                foreach ($uniqueQuantities as $quantity) {
                                                    echo '<option value="' . htmlspecialchars($quantity) . '">' . htmlspecialchars($quantity) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                        </div>

                                        <div class="col-md-6">
                                        <label class="form-label fw-semibold">Quality</label>
                                        <select class="form-select border-primary rounded-3" name="quality" id="editQuality">
                                            <option value="Excellent">Excellent</option>
                                            <option value="Good">Good</option>
                                            <option value="Average">Average</option>
                                            <option value="Poor">Poor</option>
                                        </select>
                                        </div>

                                        <div class="col-12">
                                        <label class="form-label fw-semibold">Notes</label>
                                        <textarea class="form-control border-primary rounded-3" name="note" id="editNote" rows="3"></textarea>
                                        </div>

                                    </div>
                                    </div>
                                </div>
                                </div>
                            </div>
                            </div>

                            <div class="modal-footer bg-white d-flex justify-content-end py-3">
                            <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary rounded-3 px-4">
                                <i class="fas fa-save me-1"></i> Update Survey
                            </button>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>

            </main>
        </div>
        
        <!-- *** PAGE SPECIFIC SCRIPTS GO HERE (if any) *** -->
        <script src="JS/price-update-notification.js" defer></script>
        
        <script src="../JS/survey-form.js"></script>
        <script src="survey-form-specific.js"></script>
        <script src="../JS/survey-forms.js"></script>
        <script src="../JS/notification.js"></script>

        <!-- Image Preview Script -->
        <script>
            
        </script>                          


    </body>
    </html>
