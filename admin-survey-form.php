<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    session_start();
    require 'php/connection.php';

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

    // Pagination setup
    $limit = 8;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Count total products
    $totalQuery = $conn->query("SELECT COUNT(DISTINCT product_name) AS total FROM products");
    $totalRow = $totalQuery->fetch_assoc();
    $totalProducts = $totalRow['total'];
    $totalPages = ceil($totalProducts / $limit);

    // Fetch products for current page
    $stmt = $conn->prepare("
        SELECT id, product_name, product_commodity, image
        FROM products
        GROUP BY product_name
        ORDER BY id ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price Survey Form - Tanza Public Market</title>
    
    <!-- Bootstrap CSS - Load first -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS - Load after Bootstrap to override -->
    <link rel="stylesheet" href="CSS/admin-dashboard.css">
    <link rel="stylesheet" href="CSS/notification-management.css">
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/disable-console-logs.js"></script>
    <script src="JS/disable-all-notifications.js"></script>
    <script src="JS/disable-login-requirements.js"></script>
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                
                <div class="logo">
                    <a href="pricefront.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-seedling"></i>
                        <span class="logo-text"><?php echo $settings['system_name'];?></span>
                    </a>
                </div>
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

    <!-- <button class="mobile-sidebar-toggle" id="mobileSidebarToggle" aria-label="Toggle sidebar menu">
        <i class="fas fa-bars"></i>
        <span>Menu</span>
    </button> -->
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
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="">
        <aside class="sidebar" id="sidebar">
            <!-- <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-shield-alt"></i>
                    <span>Admin Panel</span>
                </div>
                <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div> -->
             <!--<nav class="sidebar-nav">
                                <ul class="nav-list">
                    <li class="nav-item"><a href="admin-dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                    <li class="nav-item"><a href="admin-vendor-management.php" class="nav-link"><i class="fas fa-users"></i><span>Vendor Management</span></a></li>
                    <li class="nav-item active"><a href="admin-survey-form.php" class="nav-link"><i class="fas fa-clipboard-list"></i><span>Submit Survey Form</span></a></li>
                    <li class="nav-item"><a href="admin-report-management.php" class="nav-link"><i class="fas fa-exclamation-triangle"></i><span>Report Management</span></a></li>
                    <li class="nav-item"><a href="admin-cleaning-management.php" class="nav-link"><i class="fas fa-broom"></i><span>Cleaning Management</span></a></li>
                    <li class="nav-item"><a href="admin-business-permit.php" class="nav-link"><i class="fas fa-certificate"></i><span>Business Permit</span></a></li>
                    <li class="nav-item"><a href="admin-events.php" class="nav-link"><i class="fas fa-calendar-alt"></i><span>Events</span></a></li>
                    <li class="nav-item"><a href="admin-notices.php" class="nav-link"><i class="fas fa-bullhorn"></i><span>NOTICES</span></a></li>
                    <li class="nav-item"><a href="admin-notification-management.php" class="nav-link"><i class="fas fa-bell"></i><span>NOTIFICATION MANAGEMENT</span></a></li>
                </ul>
            </nav> -->

            <?php include __DIR__ . '/admin-sidebar.php'; ?>
            <!-- <div class="sidebar-footer">
                <a href="logout.php" id="logoutBtn" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>LOG OUT</span>
                </a>
            </div> -->
        </aside>

        <main class="main-content bg-body-tertiary min-vh-100">
    <!-- Top Navbar -->
    <header class="top-navbar">
        <!-- <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary" id="mobileSidebarTrigger" aria-label="Open sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="h5 fw-semibold m-0 text-primary">Submit Price Survey</h1>
        </div> -->
        <div class="date-time">
            <span id="currentDate"><?= date('F d, Y') ?></span>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container-fluid py-4">
        <section class="card shadow-lg border-0 rounded-4 p-4 bg-white">
            <!-- Header -->
            <div class="card-header bg-white border-0 pb-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h2 class="h4 fw-bold text-dark mb-1">Submit Price Survey</h2>
                        <p class="text-muted mb-0">Record commodity prices to track market trends.</p>
                    </div>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus me-2"></i> Add New Product
                    </button>
                </div>
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
                                <div class="card h-100 border-0 shadow-sm rounded-4">
                                    <img src="<?= htmlspecialchars($category['image']) ?>"
                                         alt="<?= htmlspecialchars($category['product_name']) ?>"
                                         class="card-img-top rounded-top"
                                         onerror="this.src='uploads/products/default.png';"
                                         style="height: 200px; object-fit: cover;">

                                    <div class="card-body d-flex flex-column justify-content-between">
                                        <div class="text-center">
                                            <h6 class="fw-semibold mb-2 text-dark"><?= htmlspecialchars($category['product_name']) ?></h6>
                                        </div>

                                        <div class="mt-3">
                                            <div class="d-grid mb-2">
                                                <a href="admin-survey-forms-list.php?product=<?= urlencode($category['product_name']) ?>"
                                                       class="btn btn-success btn-sm shadow-sm take-survey-btn"
                                                       data-product="<?= htmlspecialchars($category['product_name']) ?>">
                                                        <i class="fas fa-clipboard-check me-1"></i> Take Survey
                                                    </a>
                                            </div>
                                            <div class="d-flex justify-content-center gap-2">
                                                <button class="btn btn-warning btn-sm shadow-sm editBtn"
                                                        data-id="<?= $category['id'] ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editProductModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm shadow-sm deleteBtn"
                                                        data-id="<?= $category['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <div class="vendor-pagination text-center mt-4">
                        <?php if ($page > 1): ?>
                            <button class="pagination-btn" onclick="goToPage(<?= $page - 1 ?>)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                        <?php else: ?>
                            <button class="pagination-btn" disabled>
                                <i class="fas fa-chevron-left"></i>
                            </button>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <button class="pagination-btn <?= $i == $page ? 'active' : '' ?>"
                                    onclick="goToPage(<?= $i ?>)">
                                <?= $i ?>
                            </button>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <button class="pagination-btn" onclick="goToPage(<?= $page + 1 ?>)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        <?php else: ?>
                            <button class="pagination-btn" disabled>
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        <?php endif; ?>
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

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="php/admin-add-product.php" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Product Name</label>
                        <input type="text" name="product_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Commodity Type</label>
                        <small class="text-muted d-block mb-2">Each commodity can have its own image (optional)</small>
                        <div id="commodityContainer"></div>
                        <button type="button" id="addCommodityBtn" class="btn btn-outline-primary btn-sm mt-2">Add Commodity</button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity</label>
                        <div id="quantityContainer"></div>
                        <button type="button" id="addQuantityBtn" class="btn btn-outline-primary btn-sm mt-2">Add Quantity</button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Calamity (optional)</label>
                        <input type="text" name="calamity" id="productCalamity" class="form-control" placeholder="e.g., Flood, Typhoon, Drought - leave blank if not applicable">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Default Product Image</label>
                        <small class="text-muted d-block mb-1">Used for commodities without specific images</small>
                        <input type="file" name="product_image" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="editProductForm" action="php/admin-edit-product.php" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Product Name</label>
                        <input type="text" name="product_name" id="edit_product_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Commodity Type</label>
                        <small class="text-muted d-block mb-2">Each commodity can have its own image (optional)</small>
                        <div id="editCommodityContainer"></div>
                        <button type="button" id="editAddCommodityBtn" class="btn btn-outline-warning btn-sm mt-2">Add Commodity</button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity</label>
                        <div id="editQuantityContainer"></div>
                        <button type="button" id="editAddQuantityBtn" class="btn btn-outline-warning btn-sm mt-2">Add Quantity</button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Image</label>
                        <img id="edit_product_image_preview" src="" class="img-fluid rounded mb-2" style="max-height:150px; object-fit:cover;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload New Image</label>
                        <input type="file" name="product_image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Calamity (optional)</label>
                        <input type="text" name="calamity" id="edit_product_calamity" class="form-control" placeholder="e.g., Flood, Typhoon, Drought">
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</main>




    </div>

    <script src="JS/admin-sidebar.js"></script>
    <script src="JS/admin-dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /* ---------- ADD PRODUCT MODAL ---------- */
            const addCommodityBtn = document.getElementById('addCommodityBtn');
            const commodityContainer = document.getElementById('commodityContainer');
            let commodityIndex = 0;
            function addCommodityField(value = '') {
                const group = document.createElement('div');
                group.className = 'card p-2 mb-2 commodity-group';
                const previewId = 'preview_' + commodityIndex;
                group.innerHTML = `
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <input type="text" name="product_commodity[]" class="form-control commodity-input"
                            placeholder="Commodity (e.g., Red Onion)" value="${value}" required>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-commodity-btn">&times;</button>
                    </div>
                    <div class="mb-2 commodity-preview" id="${previewId}" style="display:none;">
                        <img src="" class="img-thumbnail" style="max-height:60px;">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">Image:</small>
                        <input type="file" name="commodity_images[]" class="form-control form-control-sm commodity-image-input" accept="image/*" data-preview="${previewId}">
                    </div>
                `;
                commodityContainer.appendChild(group);
                group.querySelector('.remove-commodity-btn').addEventListener('click', () => group.remove());
                
                // Add preview functionality
                const fileInput = group.querySelector('.commodity-image-input');
                fileInput.addEventListener('change', function() {
                    const previewDiv = document.getElementById(this.dataset.preview);
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewDiv.querySelector('img').src = e.target.result;
                            previewDiv.style.display = 'block';
                        };
                        reader.readAsDataURL(this.files[0]);
                    } else {
                        previewDiv.style.display = 'none';
                    }
                });
                
                commodityIndex++;
            }
            addCommodityField();
            addCommodityBtn.addEventListener('click', () => addCommodityField());

            /* ---------- QUANTITY FIELDS ---------- */
            const addQuantityBtn = document.getElementById('addQuantityBtn');
            const quantityContainer = document.getElementById('quantityContainer');
            function addQuantityField(value = '') {
                const group = document.createElement('div');
                group.className = 'input-group mb-2 quantity-group';
                group.innerHTML = `
                    <input type="text" name="product_quantity[]" class="form-control quantity-input"
                        placeholder="Quantity (e.g., 1 kilo)" value="${value}" required>
                    <button type="button" class="btn btn-outline-danger remove-quantity-btn">&times;</button>
                `;
                quantityContainer.appendChild(group);
                group.querySelector('.remove-quantity-btn').addEventListener('click', () => group.remove());
            }
            addQuantityField();
            addQuantityBtn.addEventListener('click', () => addQuantityField());

            /* ---------- EDIT MODAL ---------- */
            const editCommodityContainer = document.getElementById('editCommodityContainer');
            const editQuantityContainer = document.getElementById('editQuantityContainer');
            const editAddCommodityBtn = document.getElementById('editAddCommodityBtn');
            const editAddQuantityBtn = document.getElementById('editAddQuantityBtn');
            let editCommodityIndex = 0;
            function addEditCommodityField(value = '', imgSrc = '') {
                const group = document.createElement('div');
                group.className = 'card p-2 mb-2';
                const previewId = 'edit_preview_' + editCommodityIndex;
                group.innerHTML = `
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <input type="text" name="product_commodity[]" class="form-control" value="${value}" required>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-field">&times;</button>
                    </div>
                    <input type="hidden" name="existing_commodity_images[]" value="${imgSrc}">
                    <div class="mb-2 commodity-preview" id="${previewId}" style="${imgSrc ? 'display:block;' : 'display:none;'}">
                        <img src="${imgSrc}" class="img-thumbnail" style="max-height:60px;">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">Image:</small>
                        <input type="file" name="commodity_images[]" class="form-control form-control-sm edit-commodity-image-input" accept="image/*" data-preview="${previewId}">
                    </div>
                `;
                editCommodityContainer.appendChild(group);
                group.querySelector('.remove-field').addEventListener('click', () => group.remove());
                
                // Add preview functionality
                const fileInput = group.querySelector('.edit-commodity-image-input');
                fileInput.addEventListener('change', function() {
                    const previewDiv = document.getElementById(this.dataset.preview);
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewDiv.querySelector('img').src = e.target.result;
                            previewDiv.style.display = 'block';
                        };
                        reader.readAsDataURL(this.files[0]);
                    }
                });
                
                editCommodityIndex++;
            }
            function addEditQuantityField(value = '') {
                const group = document.createElement('div');
                group.className = 'input-group mb-2';
                group.innerHTML = `
                    <input type="text" name="product_quantity[]" class="form-control" value="${value}" required>
                    <button type="button" class="btn btn-outline-danger remove-field">&times;</button>
                `;
                editQuantityContainer.appendChild(group);
            }
            editAddCommodityBtn.addEventListener('click', () => addEditCommodityField());
            editAddQuantityBtn.addEventListener('click', () => addEditQuantityField());

            /* ---------- FETCH DATA FOR EDIT MODAL ---------- */
            document.querySelectorAll('.editBtn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    editCommodityIndex = 0; // Reset index for edit modal
                    fetch('php/get-product-data.php?id=' + id)
                        .then(res => res.json())
                        .then(data => {
                            document.getElementById('edit_product_id').value = data.id;
                            document.getElementById('edit_product_name').value = data.product_name;
                            document.getElementById('edit_product_image_preview').src =
                                data.image || 'uploads/products/default.png';
                            // populate calamity field if present
                            const editCal = document.getElementById('edit_product_calamity');
                            if (editCal) editCal.value = data.calamity || '';
                            editCommodityContainer.innerHTML = '';
                            editQuantityContainer.innerHTML = '';
                            // Handle commodities with their images
                            if (Array.isArray(data.commodities_with_images)) {
                                data.commodities_with_images.forEach(item => {
                                    addEditCommodityField(item.commodity, item.image || '');
                                });
                            } else if (Array.isArray(data.commodities)) {
                                data.commodities.forEach(c => addEditCommodityField(c, ''));
                            }
                            if (Array.isArray(data.quantities)) data.quantities.forEach(addEditQuantityField);
                        })
                        .catch(err => console.error('Error loading product data:', err));
                });
            });

            /* ---------- DELETE PRODUCT ---------- */
            document.querySelectorAll('.deleteBtn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    if (!confirm('Are you sure you want to delete this product?')) return;
                    fetch('php/admin-delete-product.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id=' + encodeURIComponent(id)
                    })
                    .then(() => location.reload())
                    .catch(err => console.error('Delete failed:', err));
                });
            });

            /* ---------- TAKE SURVEY: mark commodity notifications read ---------- */
            document.querySelectorAll('.take-survey-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const product = this.dataset.product;
                    const href = this.href;
                    // Inform server to mark this commodity's survey notifications as read
                    fetch('admin-notifications.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'mark_survey=' + encodeURIComponent(product)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.success) {
                            const badge = document.getElementById('notificationBadge');
                            if (badge) {
                                // Update badge to reflect returned totalUnread when available
                                if (typeof data.totalUnread !== 'undefined') {
                                    if (data.totalUnread > 0) badge.textContent = data.totalUnread;
                                    else badge.remove();
                                } else {
                                    // fallback: decrement by 1
                                    const v = Math.max(0, parseInt(badge.textContent || '0') - 1);
                                    if (v > 0) badge.textContent = v; else badge.remove();
                                }
                            }
                        }
                    })
                    .catch(() => {})
                    .finally(() => { window.location.href = href; });
                });
            });

            /* ---------- UNIVERSAL REMOVE ---------- */
            document.addEventListener('click', e => {
                if (e.target.classList.contains('remove-field')) e.target.parentElement.remove();
            });

            /* ---------- NOTIFICATION ---------- */
            const notif = document.getElementById('notif');
            const notifMsg = document.getElementById('notifMsg');
            const notifClose = document.getElementById('notifClose');

            function showNotification(message, type = 'success') {
                if (!notif || !notifMsg) return;
                notifMsg.textContent = message;
                const colors = {
                    success: '#28a745',
                    error: '#dc3545',
                    info: '#0d6efd',
                    warning: '#ffc107'
                };
                notif.style.background = colors[type] || '#0d6efd';
                notif.style.display = 'block';
                setTimeout(() => notif.style.opacity = 1, 10);
                setTimeout(() => {
                    notif.style.opacity = 0;
                    setTimeout(() => notif.style.display = 'none', 300);
                }, 4000);
            }

            notifClose?.addEventListener('click', () => {
                notif.style.opacity = 0;
                setTimeout(() => notif.style.display = 'none', 300);
            });

            const urlParams = new URLSearchParams(window.location.search);
            // Show notification if redirected from add-product page
            if (sessionStorage.getItem('product_added') === '1') {
                showNotification('Product added successfully', 'success');
                sessionStorage.removeItem('product_added');
            }

            if (sessionStorage.getItem('product_updated') === '1') {
                showNotification('Product updated successfully', 'success');
                sessionStorage.removeItem('product_updated');
            }
            if (urlParams.get('deleted') === '1') showNotification('Product deleted successfully', 'warning');
        });


        function goToPage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }

        // Update current date with weekday
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
    </script>


</body>
</html>
