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

    // get user and profile info
    $stmt = $conn->prepare("\n        SELECT  u.first_name, u.last_name, u.email, u.created_at,\n            p.phone_number,p.barangay,  p.city, p.province, p.postal_code,p.user_id\n        FROM users u\n        LEFT JOIN user_profiles p ON u.id = p.user_id\n        WHERE u.id = ? LIMIT 1\n    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    // load products used to build commodity and quantity lists
    $products = [];
    $prodResult = $conn->query("SELECT id, product_name, product_commodity, image FROM products");
    if ($prodResult) {
        while ($r = $prodResult->fetch_assoc()) {
            $products[] = $r;
        }
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

    // get all user names for dropdown - only include users who have completed their address
    $userNames = [];
        $userQuery = "SELECT u.id, COALESCE(u.user_code, '') AS user_code, CONCAT(u.first_name, ' ', u.last_name) AS full_name
                  FROM users u
                  INNER JOIN user_profiles p ON u.id = p.user_id
                  WHERE COALESCE(p.barangay,'') <> ''
                    AND COALESCE(p.city,'') <> ''
                    AND COALESCE(p.province,'') <> ''
                    AND COALESCE(p.postal_code,'') <> ''
                  ORDER BY full_name ASC";
    $result = $conn->query($userQuery);
    while ($row = $result->fetch_assoc()) {
        $userNames[] = $row;
    }


    $editSurvey = null;
    if (isset($_GET['edit_id'])) {
        $editId = intval($_GET['edit_id']);
        $stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editSurvey = $result->fetch_assoc();
    }

    // Keep product parameter in the URL
    $currentUrl = strtok($_SERVER["REQUEST_URI"], '?');
    $productParam = isset($_GET['product']) ? '&product=' . urlencode($_GET['product']) : '';

        $query = "SELECT name, value FROM settings";
    $result = $conn->query($query);

    $settings = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['name']] = $row['value'];
        }
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="CSS/vendor-dashboard.css">
    <link rel="stylesheet" href="CSS/ss.css">
    <link rel="stylesheet" href="CSS/admin-mobile-view.css">
    
    <!-- Bootstrap JS -->
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
                <ul class="nav-menu" id="navMenu">
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

    <!-- Mobile Menu Toggle Button -->
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="dashboard-container">
            <!-- Sidebar -->
            <aside class="sidebar" id="sidebar">
                
                <!-- Navigation Links -->
                <!--<nav class="sidebar-nav">
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
                </nav>-->
                <?php include __DIR__ . '/admin-sidebar.php'; ?>
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

                                    $productFilter = isset($_GET['product']) ? trim($_GET['product']) : '';

                                    if ($productFilter !== '') {
                                        $surveyQuery = $conn->prepare("
                                            SELECT s.*, CONCAT(u.first_name, ' ', u.last_name) AS submitted_by
                                            FROM surveys s
                                            LEFT JOIN users u ON s.user_id = u.id
                                            WHERE s.product_name = ?
                                            ORDER BY s.created_at DESC
                                        ");
                                        $surveyQuery->bind_param("s", $productFilter);
                                    } else {
                                        $surveyQuery = $conn->prepare("
                                            SELECT s.*, CONCAT(u.first_name, ' ', u.last_name) AS submitted_by
                                            FROM surveys s
                                            LEFT JOIN users u ON s.user_id = u.id
                                            ORDER BY s.created_at DESC
                                        ");
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
                                               <?php
                                                    $productParam = isset($_GET['product']) ? '&product=' . urlencode($_GET['product']) : '';
                                                ?>
                                                <a href="admin-survey-forms-list.php?edit_id=<?= $row['id'] . $productParam ?>" 
                                                    class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-outline-danger btn-sm delete-survey" data-id="<?= $row['id'] ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
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
                        <form id="surveyForm" action="php/admin-submit_survey.php" method="POST" enctype="multipart/form-data">
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
                                        <?= htmlspecialchars($_GET['product'] ?? 'Unknown Product') ?>
                                        </h4>
                                        <p class="text-muted small mb-0">Please complete the product survey details below.</p>
                                    </div>
                                    

                                    <div class="row g-4">

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Select Name</label>
                                        <!-- Searchable input (datalist) to help quickly find names -->
                                        <input id="userNameSearch" list="userNameList" class="form-control mb-2 border-success rounded-3" placeholder="Type ID or name to search">
                                        <datalist id="userNameList">
                                            <?php foreach ($userNames as $u): ?>
                                                <option data-id="<?= htmlspecialchars($u['id']) ?>" data-code="<?= htmlspecialchars($u['user_code'] ?? '') ?>" value="<?= htmlspecialchars(($u['user_code'] ?? $u['id']) . ' - ' . $u['full_name']) ?>"></option>
                                            <?php endforeach; ?>
                                        </datalist>

                                        <select id="userNameDropdown" class="form-select border-success rounded-3" name="user_id" required>
                                            <option value="" disabled selected>Select Name</option>
                                            <?php foreach ($userNames as $u): ?>
                                                <option value="<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars(($u['user_code'] ?? $u['id']) . ' - ' . $u['full_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                   
                                        <input type="hidden" id="userIdField" class="form-control border-success rounded-3" name="user_id_display" readonly>
                                 
                                    <!-- Barangay Dropdown -->
                                   <div class="col-md-6">
    <label class="form-label fw-semibold">Barangay (Tanza, Cavite)</label>
    <select id="barangayDropdown" class="form-select border-success rounded-3" name="address" required>
        <option value="" disabled selected>Select Barangay</option>

        <?php
        // Sample query
        $sql = "SELECT id, code, name FROM barangays ORDER BY name ASC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<option value="' . $row['name'] . '">' . $row['name'] . '</option>';
            }
        }
        ?>
    </select>
</div>


                                       
                                       
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

            <div class="modal fade <?php if ($editSurvey) echo 'show'; ?>" id="editSurveyModal" tabindex="-1" aria-labelledby="editSurveyModalLabel" aria-hidden="true" <?php if ($editSurvey) echo 'style="display:block;"'; ?>>
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">

                        <div class="modal-header bg-primary text-white py-3 px-4">
                            <h5 class="modal-title fw-bold fs-5 mb-0" id="editSurveyModalLabel">
                                <i class="fas fa-edit me-2"></i> Edit Survey
                            </h5>
                            <a href="<?= $currentUrl . '?product=' . urlencode($_GET['product'] ?? '') ?>" class="btn-close btn-close-white" aria-label="Close"></a>
                        </div>

                        <form id="editSurveyForm" action="php/admin-update-survey.php" method="POST" enctype="multipart/form-data">
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
                                                    <input class="form-control border-primary rounded-3" type="file" name="product_image" accept="image/*">
                                                </div>
                                                <br>
                                                <div class="d-flex justify-content-center mb-3">
                                                    <img src="<?= isset($editSurvey['product_image']) ? 'uploads/' . htmlspecialchars($editSurvey['product_image']) : 'https://via.placeholder.com/450x300?text=No+Image' ?>"
                                                        class="img-fluid rounded-4 border border-primary shadow-sm"
                                                        style="width:100%; max-height:330px; object-fit:contain;" alt="Product Preview">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- RIGHT COLUMN: Survey Fields -->
                                    <div class="col-lg-7">
                                        <div class="card border-0 shadow-sm h-100 rounded-4">
                                            <div class="card-header bg-white border-0 pb-2">
                                                <h6 class="fw-bold text-primary mb-0">
                                                    <i class="fas fa-info-circle me-2"></i> Survey Details
                                                </h6>
                                            </div>

                                            <div class="card-body px-4">
                                                <div class="row g-4">

                                                    <input type="text" name="survey_id" value="<?= htmlspecialchars($editSurvey['id'] ?? '') ?>">
                                                    <!-- Hidden field for product name -->
                                                    <input type="text" name="product_name" value="<?= htmlspecialchars($editSurvey['product_name'] ?? '') ?>">

                                                    <!-- Name -->
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Select Name</label>
                                                        <select id="editUserNameDropdown" class="form-select border-primary rounded-3" name="user_id" required>
                                                            <option value="" disabled>Select Name</option>
                                                            <?php foreach ($userNames as $u): 
                                                                $selected = ($editSurvey['user_id'] ?? '') == $u['id'] ? 'selected' : '';
                                                            ?>
                                                                <option value="<?= htmlspecialchars($u['id']) ?>" <?= $selected ?>><?= htmlspecialchars(($u['user_code'] ?? $u['id']) . ' - ' . $u['full_name']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <!-- Hidden user id field -->
                                                    <input type="hidden" id="editUserIdField" name="user_id_display" value="<?= htmlspecialchars($editSurvey['user_id'] ?? '') ?>">

                                                    <!-- Barangay -->
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Barangay (Tanza, Cavite)</label>
                                                        <select id="editBarangayDropdown" class="form-select border-primary rounded-3" name="address" required>
                                                            <option value="" disabled>Select Barangay</option>
                                                            <?php
                                                            $barangays = ["Amaya I","Amaya II","Amaya III","Bagtas","Biga","Biwas","Bucal","Bunga","Calibuyo","Capipisa East","Capipisa West","Daang Amaya I","Daang Amaya II","Daang Amaya III","Halayhay","Julugan I","Julugan II","Julugan III","Julugan IV","Julugan V","Julugan VI","Julugan VII","Julugan VIII","Julugan IX","Julugan X","Julugan XI","Julugan XII","Mulawin","Paradahan I","Paradahan II","Punta I","Punta II","Sahud Ulan","Sanja Mayor","Santol","Sapang","Tanauan","Tres Cruses","Tulay B","Tulay Silangan","Tulay Kanluran"];
                                                            foreach ($barangays as $b) {
                                                                $selected = ($editSurvey['barangay'] ?? '') == $b ? 'selected' : '';
                                                                echo "<option value='$b' $selected>$b</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>

                                                    <!-- Product & Commodity -->
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Commodity Type</label>
                                                        <select class="form-select border-primary rounded-3" name="commodity_type" required>
                                                            <option value="" disabled <?= !isset($editSurvey['commodity_type']) ? 'selected' : '' ?>>Select Commodity</option>
                                                            <?php
                                                            $productName = $editSurvey['product_name'] ?? '';
                                                            if (isset($commoditiesPerCategory[$productName])) {
                                                                foreach (array_unique($commoditiesPerCategory[$productName]) as $commodity) {
                                                                    $selected = ($editSurvey['commodity_type'] ?? '') === $commodity ? 'selected' : '';
                                                                    echo '<option value="' . htmlspecialchars($commodity) . '" ' . $selected . '>' . htmlspecialchars($commodity) . '</option>';
                                                                }
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>

                                                    <!-- Brand, Price, Quantity, Quality, Notes -->
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Brand / Variety</label>
                                                        <input type="text" class="form-control border-primary rounded-3" name="variety" value="<?= htmlspecialchars($editSurvey['variety'] ?? '') ?>">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Price (₱)</label>
                                                        <input type="number" step="0.01" class="form-control border-primary rounded-3" name="price" value="<?= htmlspecialchars($editSurvey['price'] ?? '') ?>">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Quantity Type</label>
                                                        <select class="form-select border-primary rounded-3" name="quantity_type" required>
                                                            <option value="" disabled>Select Quantity Type</option>
                                                            <?php
                                                            if (isset($quantitiesPerProduct[$productName])) {
                                                                foreach (array_unique($quantitiesPerProduct[$productName]) as $quantity) {
                                                                    $selected = ($editSurvey['quantity_type'] ?? '') === $quantity ? 'selected' : '';
                                                                    echo '<option value="' . htmlspecialchars($quantity) . '" ' . $selected . '>' . htmlspecialchars($quantity) . '</option>';
                                                                }
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Product Quality</label>
                                                        <select class="form-select border-primary rounded-3" name="quality" required>
                                                            <?php
                                                            $qualities = ['Excellent','Good','Average','Poor'];
                                                            foreach ($qualities as $q) {
                                                                $selected = ($editSurvey['quality'] ?? '') === $q ? 'selected' : '';
                                                                echo "<option value='$q' $selected>$q</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">Additional Notes</label>
                                                        <textarea class="form-control border-primary rounded-3" name="note" rows="3"><?= htmlspecialchars($editSurvey['note'] ?? '') ?></textarea>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="modal-footer bg-white d-flex justify-content-end py-3">
                                <a href="<?= $currentUrl . '?product=' . urlencode($_GET['product'] ?? '') ?>" class="btn btn-outline-secondary rounded-3 px-4">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
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
    <script src="JS/admin-sidebar.js"></script>
    <script src="JS/vendor-dashboard.js"></script>
    <script src="JS/survey-form.js"></script>
    <script src="survey-form-specific.js"></script>
    
  

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const nameDropdown = document.getElementById("userNameDropdown");
            const barangayDropdown = document.getElementById("barangayDropdown");

            if (!nameDropdown) return; // nothing to wire on this page

            // Implement inline typing search on the name dropdown itself.
            // Build a small query buffer from keystrokes while the select has focus.
            (function makeSelectSearchable(selectEl) {
                if (!selectEl) return;
                let buffer = '';
                let timer = null;

                function resetBuffer() {
                    buffer = '';
                    if (timer) { clearTimeout(timer); timer = null; }
                }

                selectEl.addEventListener('keydown', (e) => {
                    // Allow navigation keys to behave normally
                    const navKeys = ['ArrowUp','ArrowDown','Enter','Tab','Escape','Home','End'];
                    if (navKeys.includes(e.key)) return;

                    // Backspace: remove last char
                    if (e.key === 'Backspace') {
                        buffer = buffer.slice(0, -1);
                        e.preventDefault();
                    } else if (e.key.length === 1) {
                        buffer += e.key;
                        e.preventDefault();
                    } else {
                        return; // ignore other keys
                    }

                    // reset buffer after 900ms of inactivity
                    if (timer) clearTimeout(timer);
                    timer = setTimeout(resetBuffer, 900);

                    const q = buffer.trim().toLowerCase();
                    Array.from(selectEl.options).forEach(opt => {
                        if (!opt.value) { opt.hidden = false; return; }
                        const txt = (opt.textContent || '').toLowerCase();
                        const id = (opt.value || '').toString();
                        const match = q === '' || txt.includes(q) || id.includes(q);
                        opt.hidden = !match;
                    });

                    // If only one visible option (excluding placeholder), select it
                    const visibleOptions = Array.from(selectEl.options).filter(o => !o.hidden && o.value);
                    if (visibleOptions.length === 1) {
                        selectEl.value = visibleOptions[0].value;
                        selectEl.dispatchEvent(new Event('change'));
                        resetBuffer();
                    }
                });

                // When the select loses focus, clear any hiding and buffer
                selectEl.addEventListener('blur', () => {
                    Array.from(selectEl.options).forEach(opt => opt.hidden = false);
                    resetBuffer();
                });
            })(nameDropdown);

            // Wire the datalist/text-search input to the select so typing selects the matching user
            const nameSearch = document.getElementById('userNameSearch');
            const nameList = document.getElementById('userNameList');
            if (nameSearch) {
                // Helper to determine if a datalist option matches the query.
                function datalistMatchesOption(opt, q) {
                    const v = (opt.value || '').toLowerCase(); // full display text, e.g. "code - full name"
                    const id = (opt.getAttribute('data-id') || '').toLowerCase();
                    const code = (opt.getAttribute('data-code') || '').toLowerCase();
                    return v === q || id === q || code === q || v.startsWith(q) || v.includes(q);
                }

                nameSearch.addEventListener('input', () => {
                    const q = (nameSearch.value || '').trim().toLowerCase();

                    // If the query is empty, do not auto-select — show all options and clear selection
                    if (q === '') {
                        Array.from(nameDropdown.options).forEach(opt => opt.hidden = false);
                        nameDropdown.value = '';
                        // keep the visible select unchanged otherwise
                        return;
                    }

                    // If the datalist contains a clear match (id/code or prefix), select that user
                    if (nameList && nameList.options) {
                        const exact = Array.from(nameList.options).find(o => datalistMatchesOption(o, q));
                        if (exact) {
                            const id = exact.getAttribute('data-id');
                            if (id) {
                                nameDropdown.value = id;
                                nameDropdown.dispatchEvent(new Event('change'));
                                return;
                            }
                        }
                    }

                    // Otherwise, filter visible options in the select to help pick
                    Array.from(nameDropdown.options).forEach(opt => {
                        if (!opt.value) { opt.hidden = false; return; }
                        const txt = (opt.textContent || '').toLowerCase();
                        const id = (opt.value || '').toString();
                        const match = txt.includes(q) || id.includes(q);
                        opt.hidden = !match;
                    });
                });

                nameSearch.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const visible = Array.from(nameDropdown.options).filter(o => !o.hidden && o.value);
                        if (visible.length === 1) {
                            nameDropdown.value = visible[0].value;
                            nameDropdown.dispatchEvent(new Event('change'));
                        } else if (nameList && nameList.options) {
                            const exact = Array.from(nameList.options).find(o => datalistMatchesOption(o, (nameSearch.value||'').trim().toLowerCase()));
                            if (exact) {
                                const id = exact.getAttribute('data-id');
                                if (id) { nameDropdown.value = id; nameDropdown.dispatchEvent(new Event('change')); }
                            }
                        }
                    }
                });

                // Keep the search input in sync when user picks from the select
                nameDropdown.addEventListener('change', () => {
                    const sel = nameDropdown.selectedOptions && nameDropdown.selectedOptions[0];
                    if (sel) nameSearch.value = sel.textContent || '';
                });
            }

            nameDropdown.addEventListener("change", () => {
                const userId = nameDropdown.value;
                if (!userId) return;

                if (!barangayDropdown) return;

                fetch(`php/get_user_address.php?id=${userId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data || !data.barangay) return;

                        // Set barangay dropdown to match user's barangay
                        for (let option of barangayDropdown.options) {
                            option.selected = option.value === data.barangay;
                        }
                    })
                    .catch(err => console.error("Error fetching address:", err));
            });
        });
        
        document.addEventListener("DOMContentLoaded", () => {
            const nameDropdown = document.getElementById("userNameDropdown");
            const userIdField = document.getElementById("userIdField");
            if (!nameDropdown || !userIdField) return;

            nameDropdown.addEventListener("change", () => {
                userIdField.value = nameDropdown.value;
            });
        });

        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".delete-survey").forEach(btn => {
                btn.addEventListener("click", function() {
                    if (!confirm("Are you sure you want to delete this survey?")) return;

                    const surveyId = this.dataset.id;
                    fetch("php/admin-delete_survey.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `survey_id=${surveyId}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert("Survey deleted successfully!");
                            location.reload();
                        } else {
                            alert("Error deleting survey: " + (data.error || "Unknown error"));
                        }
                    }).catch(err => {
                        console.error('Delete request failed', err);
                        alert('Network error while deleting survey');
                    });
                });
            });
        });

        document.addEventListener("DOMContentLoaded", () => {
            const editNameDropdown = document.getElementById("editUserNameDropdown");
            const editBarangayDropdown = document.getElementById("editBarangayDropdown");
            const editUserIdField = document.getElementById("editUserIdField");

            if(editNameDropdown){
                // make edit dropdown searchable (inline typing) similar to create modal
                (function makeSelectSearchable(selectEl) {
                    if (!selectEl) return;
                    let buffer = '';
                    let timer = null;

                    function resetBuffer() { buffer = ''; if (timer) { clearTimeout(timer); timer = null; } }

                    selectEl.addEventListener('keydown', (e) => {
                        const navKeys = ['ArrowUp','ArrowDown','Enter','Tab','Escape','Home','End'];
                        if (navKeys.includes(e.key)) return;
                        if (e.key === 'Backspace') { buffer = buffer.slice(0, -1); e.preventDefault(); }
                        else if (e.key.length === 1) { buffer += e.key; e.preventDefault(); } else return;

                        if (timer) clearTimeout(timer);
                        timer = setTimeout(resetBuffer, 900);

                        const q = buffer.trim().toLowerCase();
                        Array.from(selectEl.options).forEach(opt => {
                            if (!opt.value) { opt.hidden = false; return; }
                            const txt = (opt.textContent || '').toLowerCase();
                            const id = (opt.value || '').toString();
                            const match = q === '' || txt.includes(q) || id.includes(q);
                            opt.hidden = !match;
                        });

                        const visibleOptions = Array.from(selectEl.options).filter(o => !o.hidden && o.value);
                        if (visibleOptions.length === 1) {
                            selectEl.value = visibleOptions[0].value;
                            selectEl.dispatchEvent(new Event('change'));
                            resetBuffer();
                        }
                    });

                    selectEl.addEventListener('blur', () => { Array.from(selectEl.options).forEach(opt => opt.hidden = false); resetBuffer(); });
                })(editNameDropdown);

                // existing change handler to fetch barangay and set hidden field
                editNameDropdown.addEventListener("change", () => {
                    const userId = editNameDropdown.value;
                    if (!userId) return;

                    fetch(`php/get_user_address.php?id=${userId}`)
                        .then(res => res.json())
                        .then(data => {
                            if(!data || !data.barangay) return;
                            for(let option of editBarangayDropdown.options){
                                option.selected = option.value === data.barangay;
                            }
                        }).catch(()=>{});

                    editUserIdField.value = userId;
                });
            }
        });


        document.addEventListener("DOMContentLoaded", () => {
            const notif = document.getElementById("notif");
            const notifMsg = document.getElementById("notifMsg");
            const notifClose = document.getElementById("notifClose");

            <?php if (!empty($_SESSION['notif_message'])): ?>
                notifMsg.textContent = "<?= addslashes($_SESSION['notif_message']) ?>";
                notif.style.display = "block";
                notif.style.backgroundColor = "#28a745"; // green color
                setTimeout(() => notif.style.opacity = 1, 50);
                setTimeout(() => notif.style.opacity = 0, 4000);
                setTimeout(() => notif.style.display = "none", 4500);
            <?php unset($_SESSION['notif_message']); endif; ?>

            notifClose.addEventListener("click", () => {
                notif.style.opacity = 0;
                setTimeout(() => notif.style.display = "none", 300);
            });
        });

        document.addEventListener("DOMContentLoaded", () => {
            const notif = document.getElementById("notif");
            const notifMsg = document.getElementById("notifMsg");
            const notifClose = document.getElementById("notifClose");

            // Parse URL parameters
            const params = new URLSearchParams(window.location.search);
            const message = params.get('notif');
            const type = params.get('type') || 'success'; // default color green

            if (message) {
                notifMsg.textContent = message;
                notif.style.display = 'block';
                notif.style.backgroundColor = type === 'danger' ? '#dc3545' : '#28a745';
                setTimeout(() => notif.style.opacity = 1, 50);
                setTimeout(() => notif.style.opacity = 0, 4000);
                setTimeout(() => notif.style.display = 'none', 4500);

                // Remove notif params from URL so refreshing doesn't show again
                params.delete('notif');
                params.delete('type');
                window.history.replaceState({}, '', window.location.pathname + '?' + params.toString());
            }

            notifClose.addEventListener("click", () => {
                notif.style.opacity = 0;
                setTimeout(() => notif.style.display = "none", 300);
            });
        });
    </script>
                        
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const notif = document.getElementById("notif");
        const notifMsg = document.getElementById("notifMsg");
        const notifClose = document.getElementById("notifClose");

        notifMsg.textContent = "Survey updated successfully!";
        notif.style.display = "block";
        notif.style.backgroundColor = "#28a745"; // green
        setTimeout(() => notif.style.opacity = 1, 50);
        setTimeout(() => notif.style.opacity = 0, 4000);
        setTimeout(() => notif.style.display = "none", 4500);

        notifClose.addEventListener("click", () => {
            notif.style.opacity = 0;
            setTimeout(() => notif.style.display = "none", 300);
        });
    });
    </script>
    <?php endif; ?>


</body>
</html>
