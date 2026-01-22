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
    // fetch full user info and admin flag for header usage
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

$productName = isset($_GET['product']) ? $_GET['product'] : '';

$sql = "SELECT product_name, product_commodity, image FROM products WHERE product_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $productName);
$stmt->execute();
$result = $stmt->get_result();

$commodities = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $commodities[] = $row;
    }
}

// Fetch prices for each commodity
$commodityPrices = [];
foreach ($commodities as $commodity) {
    $commodityName = $commodity['product_commodity'];
    $priceStmt = $conn->prepare("SELECT MIN(price) as min_price, MAX(price) as max_price, quantity_type FROM surveys WHERE commodity_type = ? GROUP BY quantity_type ORDER BY min_price ASC LIMIT 1");
    $priceStmt->bind_param('s', $commodityName);
    $priceStmt->execute();
    $priceResult = $priceStmt->get_result();
    if ($priceResult->num_rows > 0) {
        $priceData = $priceResult->fetch_assoc();
        $commodityPrices[$commodityName] = [
            'min_price' => $priceData['min_price'],
            'max_price' => $priceData['max_price'],
            'quantity_type' => $priceData['quantity_type']
        ];
    }
    $priceStmt->close();
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
    <title>Rice & Grains - Tanza Public Market</title>
    <link rel="stylesheet" href="CSS/rice-grains.css">
    <link rel="stylesheet" href="CSS/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="CSS/price-update-notification.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>

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
        </div>
    </nav>
</header>

<!-- Search Section -->
<section class="py-4 bg-light">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="input-group w-50">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="Search products">
            <button class="btn btn-success" id="searchBtn"><i class="fas fa-search"></i></button>
        </div>
        <a href="pricefront.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
</section>

<!-- Main Content -->
<main class="container py-4">
    <h2 class="mb-4">
        <?= !empty($commodities) ? htmlspecialchars(ucwords($commodities[0]['product_name'])) : 'Product' ?>
    </h2>

    <div class="row g-3" id="productsGrid">
        <?php if (!empty($commodities)): ?>
            <?php foreach ($commodities as $commodity): ?>
                <?php 
                    $commName = $commodity['product_commodity'];
                    $hasPrice = isset($commodityPrices[$commName]);
                    $minPrice = $hasPrice ? $commodityPrices[$commName]['min_price'] : null;
                    $maxPrice = $hasPrice ? $commodityPrices[$commName]['max_price'] : null;
                    $qtyType = $hasPrice ? $commodityPrices[$commName]['quantity_type'] : '';
                ?>
                <div class="col-md-3 col-sm-6">
                    <div class="card product-card h-100 shadow-sm"
                         data-commodity="<?= htmlspecialchars($commodity['product_commodity']) ?>"
                         data-image="<?= htmlspecialchars($commodity['image']) ?>"
                         style="cursor:pointer;">
                        <img src="<?= htmlspecialchars($commodity['image']) ?>"
                             class="card-img-top"
                             alt="<?= htmlspecialchars($commodity['product_commodity']) ?>"
                             onerror="this.src='data:image/svg+xml;base64,...'"
                             style="height: 300px; width: 100%; object-fit: cover;">
                        <div class="card-body text-center">
                            <h6 class="card-title"><?= htmlspecialchars($commodity['product_commodity']) ?></h6>
                            <?php if ($hasPrice): ?>
                                <div class="price-display mt-2">
                                    <?php if ($minPrice == $maxPrice): ?>
                                        <span class="badge bg-success fs-6">₱<?= number_format($minPrice, 2) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success fs-6">₱<?= number_format($minPrice, 2) ?> - ₱<?= number_format($maxPrice, 2) ?></span>
                                    <?php endif; ?>
                                    <small class="text-muted d-block mt-1">per <?= htmlspecialchars($qtyType) ?></small>
                                </div>
                            <?php else: ?>
                                <div class="price-display mt-2">
                                    <span class="badge bg-secondary">No price data</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">No commodities available.</p>
        <?php endif; ?>
    </div>
</main>

<!-- Price Modal -->
<div class="modal fade" id="priceModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="modalTitle"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h4 class="card-title mb-2">
                            <i class="fas fa-balance-scale"></i> Compare Prices Across Stores
                        </h4>
                        <p class="text-muted small mb-0" id="latestUpdateText">Latest update: --</p>
                    </div>
                </div>
                <div id="priceList"></div>
                
                <!-- Price History Section -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fas fa-history text-primary"></i> Price History</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="priceHistoryTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Quality</th>
                                        <th>Vendor</th>
                                    </tr>
                                </thead>
                                <tbody id="priceHistoryBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const priceModal = new bootstrap.Modal(document.getElementById('priceModal'));

// Search functionality
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');
const productCards = document.querySelectorAll('.product-card');

function filterProducts() {
    const query = searchInput.value.trim().toLowerCase();
    
    productCards.forEach(card => {
        const commodityName = card.dataset.commodity.toLowerCase();
        const cardContainer = card.closest('.col-md-3');
        
        if (commodityName.includes(query)) {
            cardContainer.style.display = '';
        } else {
            cardContainer.style.display = 'none';
        }
    });
}

// Filter on button click
searchBtn.addEventListener('click', filterProducts);

// Filter on Enter key press
searchInput.addEventListener('keyup', (e) => {
    if (e.key === 'Enter') {
        filterProducts();
    }
});

// Real-time filtering as user types
searchInput.addEventListener('input', filterProducts);

document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', async () => {
        const commodity = card.dataset.commodity;
        document.getElementById('modalTitle').textContent = commodity;

        const res = await fetch(`php/get_prices.php?commodity=${encodeURIComponent(commodity)}`);
        const response = await res.json();
        const grouped = response.grouped_prices;
        const bestDeals = response.best_deals;
        const priceHistory = response.price_history || [];

        const listContainer = document.getElementById('priceList');
        const historyBody = document.getElementById('priceHistoryBody');
        listContainer.innerHTML = '';
        historyBody.innerHTML = '';

        // Update latest update text
        const latestUpdateText = document.getElementById('latestUpdateText');
        if (priceHistory.length > 0 && priceHistory[0].created_at) {
            const latestDate = new Date(priceHistory[0].created_at);
            latestUpdateText.textContent = `Latest update: ${latestDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })} at ${latestDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`;
        } else {
            latestUpdateText.textContent = 'Latest update: --';
        }

        if (Object.keys(grouped).length === 0) {
            listContainer.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No price data available.</p>
                </div>`;
            priceModal.show();
            return;
        }

        // helper to detect chain vendor type from seller name
        function detectVendorType(name) {
            if (!name) return 'Public Market';
            const lookup = {
                'puregold': 'Puregold',
                'savemore': 'Savemore',
                'dali': 'Dali',
                'waltermart': 'Waltermart',
                'alphamart': 'Alphamart',
                'divimart': 'Divimart'
            };
            const n = name.toLowerCase();
            for (const key of Object.keys(lookup)) {
                if (n.includes(key)) return lookup[key];
            }
            return 'Public Market';
        }

        for (const [qtyType, entriesRaw] of Object.entries(grouped)) {
            // copy entries and attach vendorType and numeric price
            const entries = entriesRaw.map(e => ({
                ...e,
                price: parseFloat(e.price),
                vendorType: detectVendorType(e.name)
            }));

            // sort: chain vendors first, then by price ascending
            entries.sort((a, b) => {
                const aIsChain = a.vendorType !== 'Public Market';
                const bIsChain = b.vendorType !== 'Public Market';
                if (aIsChain && !bIsChain) return -1;
                if (!aIsChain && bIsChain) return 1;
                return a.price - b.price;
            });

            const prices = entries.map(p => p.price);
            const minPrice = Math.min(...prices);
            const maxPrice = Math.max(...prices);

            const section = document.createElement('div');
            section.classList.add('mb-4', 'fade-in');
            section.innerHTML = `
    <div class="price-section-header bg-light rounded-3 px-3 py-2 mb-3 d-flex justify-content-between align-items-center shadow-sm">
        <div class="d-flex align-items-center">
            <i class="fas fa-tag text-success me-2"></i>
            <h6 class="fw-bold text-success mb-0">${qtyType.toUpperCase()}</h6>
        </div>
        <span class="text-muted small fw-semibold">₱${minPrice} – ₱${maxPrice} / ${qtyType}</span>
    </div>
    <div class="row g-3"></div>
`;


            const grid = section.querySelector('.row');

            entries.forEach(p => {
                const isBest = p.price == bestDeals[qtyType];
                const col = document.createElement('div');
                col.className = 'col-6 col-md-4 col-lg-3';
                col.innerHTML = `
                    <div class="price-tile card border-0 shadow-sm text-center p-3 ${isBest ? 'best-tile' : ''}">
                        ${isBest ? '<div class="position-absolute top-0 end-0 m-2"><i class="fas fa-trophy text-warning"></i></div>' : ''}
                        <h5 class="fw-bold text-success mb-1">₱${p.price}</h5>
                        <p class="small text-muted mb-1">${p.quantity_type}</p>
                        <p class="small text-secondary mb-2">Vendor Type: ${p.vendorType}</p>
                        <span class="badge ${isBest ? 'bg-success' : 'bg-secondary'}">${p.quality}</span>
                    </div>
                `;
                grid.appendChild(col);
            });

            listContainer.appendChild(section);
        }

        // Populate price history table
        if (priceHistory.length > 0) {
            priceHistory.forEach(entry => {
                const date = new Date(entry.created_at);
                const formattedDate = date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit'
                });
                const vendorType = detectVendorType(entry.name);
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><small>${formattedDate}</small></td>
                    <td><span class="fw-bold text-success">₱${parseFloat(entry.price).toFixed(2)}</span></td>
                    <td>${entry.quantity_type}</td>
                    <td><span class="badge bg-secondary">${entry.quality || 'N/A'}</span></td>
                    <td><small>${vendorType}</small></td>
                `;
                historyBody.appendChild(row);
            });
        } else {
            historyBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No price history available</td></tr>';
        }

        priceModal.show();
    });
});

</script>

</body>
</html>
    <script src="JS/price-update-notification.js" defer></script>
    <script src="JS/notification.js"></script>
