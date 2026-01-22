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

    // Pagination settings
    $reportsPerPage = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $reportsPerPage;

    // Get total number of reports for this user
    $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM reports WHERE user_id = ?");
    $stmtCount->bind_param("i", $userId);
    $stmtCount->execute();
    $resultCount = $stmtCount->get_result();
    $totalReports = $resultCount->fetch_assoc()['total'];
    $stmtCount->close();

    $totalPages = ceil($totalReports / $reportsPerPage);

    // Fetch reports for the current page
    $stmtReports = $conn->prepare("
        SELECT id, report_date, customer_name, user_id, stall_name, stall_location, category, description, evidence_photo, created_at, status
        FROM reports
        WHERE user_id = ?
        ORDER BY report_date DESC
        LIMIT ? OFFSET ?
    ");
    $stmtReports->bind_param("iii", $userId, $reportsPerPage, $offset);
    $stmtReports->execute();
    $reportsResult = $stmtReports->get_result();
    $reports = $reportsResult->fetch_all(MYSQLI_ASSOC);
    $stmtReports->close();

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

        // fetch distinct categories (types) for the filter dropdown
        $categories = [];
        $stmtCats = $conn->prepare("SELECT DISTINCT category FROM reports WHERE user_id = ? AND category IS NOT NULL AND category <> ''");
        if ($stmtCats) {
            $stmtCats->bind_param("i", $userId);
            $stmtCats->execute();
            $resCats = $stmtCats->get_result();
            while ($r = $resCats->fetch_assoc()) {
                $categories[] = $r['category'];
            }
            $stmtCats->close();
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
    <title>Market Notices - Tanza Public Market</title>
    <link rel="stylesheet" href="CSS/minimalist-responsive.css">
    <link rel="stylesheet" href="CSS/vendor-dashboard.css?v=2">
    <link rel="stylesheet" href="CSS/survey-form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/disable-console-logs.js"></script>
    <script src="JS/disable-all-notifications.js"></script>
    <script src="JS/disable-login-requirements.js"></script>
    <!-- Shared JS for all pages -->
    <script src="JS/vendor-dashboard-shared.js" defer></script>
    <style>
         .notification-container {
    position: relative;
    cursor: pointer;
}

        /* Modal styling for report details */
        #reportDetailsModal .modal-content {
            border-radius: 12px;
            overflow: hidden;
            border: none;
        }

        #reportDetailsModal .modal-header {
            background: linear-gradient(90deg, #2563eb 0%, #27ae60 100%);
            color: #fff;
            border: none;
            padding: 1rem 1.25rem;
        }

        #reportDetailsModal .modal-title {
            font-weight: 700;
            font-size: 1.125rem;
        }

        #reportDetailsModal .modal-body {
            padding: 1rem 1.25rem;
        }

        #reportDetailsModal .details-grid {
            display: flex;
            gap: 1.25rem;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        #reportDetailsModal .modal-image {
            width: 100%;
            max-width: 420px;
            height: auto;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            border: 1px solid rgba(0,0,0,0.06);
        }

        #reportDetailsModal .modal-info {
            flex: 1 1 320px;
            min-width: 220px;
        }

        #reportDetailsModal .meta-row {
            display:flex;
            gap:12px;
            align-items:center;
            margin-bottom:8px;
            flex-wrap:wrap;
        }

        .status-badge {
            display:inline-block;
            padding:6px 10px;
            border-radius:999px;
            font-weight:700;
            color:#fff;
            font-size:0.9rem;
        }
        .status-approved { background:#16a34a; }
        .status-pending { background:#f59e0b; }
        .status-rejected { background:#dc2626; }
        .status-expired { background:#f97316; }
        .status-complaint { background:#6b7280; }

        #reportDetailsModal .modal-description {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            white-space: pre-wrap;
            color: #111827;
            margin-bottom: 8px;
        }

        @media (max-width: 720px) {
            #reportDetailsModal .details-grid { flex-direction: column; }
            #reportDetailsModal .modal-image { max-width: 100%; }
        }

.notification-bell {
    font-size: 20px;
    color: #333;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: red;
    color: white;
    font-size: 12px;
    border-radius: 50%;
    padding: 2px 6px;
}

.notification-dropdown {
    display: none;
    position: absolute;
    top: 30px;
    right: 0;
    background: #fff;
    border: 1px solid #ddd;
    width: 300px;
    max-height: 400px;
    overflow-y: auto;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    z-index: 1000;
    border-radius: 8px;
}

.notification-dropdown.show {
    display: block;
}

.notification-item {
    padding: 10px;
    font-size: 14px;
    color: #333;
    border-bottom: 1px solid #eee;
    transition: background 0.2s;
}

.notification-item:hover {
    background: #f7f7f7;
    cursor: pointer;
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
                    <li class="nav-item active">
                        <a href="vendor-notices.php" class="nav-link <?php echo !$isVerified ? 'disabled-link' : ''; ?>" <?php echo !$isVerified ? 'onclick="return false;"' : ''; ?>>
                            <i class="fas fa-bullhorn"></i>
                            <span>NOTICES</span>
                        </a>
                    </li>
                    <li class="nav-item">
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
                        <h2>Market Notices</h2>
                        <p>Important notices, reports, and complaints.</p>
                        
                        <!-- Status Legend -->
                        <div class="vendor-status-legend">
                            <div class="legend-item">
                                <span class="legend-indicator suspended"></span>
                                <span class="legend-label">Suspended</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-indicator complaint"></span>
                                <span class="legend-label">Has Complaint</span>
                            </div>
                        </div>
                    </div>
                    <div class="section-content">
                        <!-- Notice Filters -->
                        <div class="notice-filters">
                                <div class="filter-group">
                                    <label for="noticeTypeFilter">Filter by Type:</label>
                                    <select id="noticeTypeFilter">
                                        <option value="all">All Types</option>
                                        <?php if (!empty($categories)): ?>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo ucfirst(htmlspecialchars($cat)); ?></option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="complaint">Complaints</option>
                                            <option value="report">Reports</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            <div class="filter-group">
                                <label for="noticeDateFilter">Filter by Date:</label>
                                <select id="noticeDateFilter">
                                    <option value="all">All Dates</option>
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                </select>
                            </div>
                            <div class="filter-group search">
                                <input type="text" id="noticeSearchInput" placeholder="Search notices...">
                                <button class="search-btn"><i class="fas fa-search"></i></button>
                            </div>
                        </div>

                        <!-- Notices List -->
                        <div class="notices-container">
                            <!-- Complaint -->
                             <?php if (!empty($reports)): ?>
                                <?php foreach ($reports as $report): ?>
                                    <div class="notice-item complaint"
                                         data-category="<?php echo htmlspecialchars($report['category']); ?>"
                                         data-date="<?php echo htmlspecialchars($report['report_date']); ?>"
                                         data-customer="<?php echo htmlspecialchars($report['customer_name']); ?>"
                                         data-stall="<?php echo htmlspecialchars($report['stall_name']); ?>"
                                         data-location="<?php echo htmlspecialchars($report['stall_location']); ?>"
                                         data-desc="<?php echo htmlspecialchars($report['description']); ?>"
                                         data-status="<?php echo htmlspecialchars($report['status']); ?>">
                                        <div class="notice-header">
                                            <span class="notice-badge complaint <?php echo htmlspecialchars($report['category']); ?>">Complaint</span>
                                            <h3 class="notice-title">Customer Complaint: <?php echo ucfirst(htmlspecialchars($report['category'])); ?></h3>
                                            <span class="notice-date"><?php echo ucfirst(htmlspecialchars($report['category'])); ?></span>
                                        </div>
                                        <div class="notice-content">
                                            <p><?php echo ucfirst(htmlspecialchars(string: $report['description'])); ?></p>
                                            <div class="notice-actions">
                                                <div class="notice-actions">
                                                    <?php
                                                        $rawPhoto = $report['evidence_photo'] ?? '';
                                                        $photoAttr = '';
                                                        if ($rawPhoto) {
                                                            // filesystem path
                                                            $fsPath = __DIR__ . '/' . ltrim($rawPhoto, '/');
                                                            if (file_exists($fsPath) && is_readable($fsPath)) {
                                                                // store only the basename in the data attribute and let JS build the proxy URL
                                                                $photoAttr = basename($rawPhoto);
                                                            } else {
                                                                // file missing on disk -> leave empty to hide image in modal
                                                                $photoAttr = '';
                                                            }
                                                        }
                                                    ?>
                                                    <a href="#" class="notice-action view-details-btn" 
                                                        data-customer="<?php echo htmlspecialchars($report['customer_name']); ?>"
                                                        data-stall="<?php echo htmlspecialchars($report['stall_name']); ?>"
                                                        data-location="<?php echo htmlspecialchars($report['stall_location']); ?>"
                                                        data-category="<?php echo htmlspecialchars($report['category']); ?>"
                                                        data-description="<?php echo htmlspecialchars($report['description']); ?>"
                                                        data-date="<?php echo htmlspecialchars($report['report_date']); ?>"
                                                        data-status="<?php echo htmlspecialchars($report['status']); ?>"
                                                        data-photo="<?php echo htmlspecialchars($photoAttr); ?>"
                                                    >View Details</a>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>       
                            <?php else: ?>
                                <p>No reports found.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Pagination -->
                        <div class="notices-pagination">
                            <?php if($totalPages > 1): ?>
                                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>" class="pagination-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            <?php endif; ?>
                        </div>

                    </div>
                
            </div>
        </main>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="reportDetailsModal" tabindex="-1" aria-labelledby="reportDetailsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportDetailsLabel">Report Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="details-grid">
                    <div class="left-col">
                        <div id="modalEvidencePhotoContainer">
                            <img id="modalEvidencePhoto" class="modal-image" src="" alt="Evidence" style="display:none;">
                        </div>
                    </div>
                    <div class="modal-info">
                        <div class="meta-row">
                            <div style="flex:1">
                                <div style="font-size:0.95rem;color:#6b7280">Category</div>
                                <div id="modalCategory" style="font-weight:700;font-size:1.05rem;color:#111827"></div>
                            </div>
                            <div>
                                <span id="modalStatus" class="status-badge status-complaint">Status</span>
                            </div>
                        </div>

                        <div style="margin-bottom:10px;color:#6b7280">Report Date</div>
                        <div id="modalReportDate" style="font-weight:600;margin-bottom:12px;color:#111827"></div>

                        <div style="margin-bottom:6px;color:#6b7280">Description</div>
                        <div id="modalDescription" class="modal-description"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
            </div>
        </div>
    </div>

    
    <!-- *** PAGE SPECIFIC SCRIPTS GO HERE (if any) *** -->
        <script src="../JS/notification.js"></script>
        <script>
            document.getElementById("logoutBtn").addEventListener("click", function(e) {
            e.preventDefault();
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "php/logout.php";
            }
        });
            document.addEventListener('DOMContentLoaded', function() {
                const modal = new bootstrap.Modal(document.getElementById('reportDetailsModal'));

                // Filtering logic (type, date, search)
                const typeSelect = document.getElementById('noticeTypeFilter');
                const dateSelect = document.getElementById('noticeDateFilter');
                const searchInput = document.getElementById('noticeSearchInput');
                const searchBtn = document.querySelector('.search-btn');

                function parseDate(d) {
                    // robust parse for YYYY-MM-DD or datetime
                    const parsed = new Date(d);
                    if (isNaN(parsed)) return null;
                    return parsed;
                }

                function matchesDateRange(itemDateStr, range) {
                    if (!itemDateStr) return false;
                    const itemDate = parseDate(itemDateStr);
                    if (!itemDate) return false;
                    const now = new Date();
                    if (range === 'today') {
                        return itemDate.getFullYear() === now.getFullYear() && itemDate.getMonth() === now.getMonth() && itemDate.getDate() === now.getDate();
                    } else if (range === 'week') {
                        const diff = now - itemDate; // ms
                        return diff >= 0 && diff <= 7 * 24 * 60 * 60 * 1000;
                    } else if (range === 'month') {
                        return itemDate.getFullYear() === now.getFullYear() && itemDate.getMonth() === now.getMonth();
                    }
                    return true; // 'all'
                }

                function filterNotices() {
                    const typeVal = (typeSelect && typeSelect.value) || 'all';
                    const dateVal = (dateSelect && dateSelect.value) || 'all';
                    const searchVal = (searchInput && searchInput.value || '').trim().toLowerCase();

                    document.querySelectorAll('.notice-item').forEach(item => {
                        const itemType = (item.dataset.category || '').toLowerCase();
                        const itemDate = item.dataset.date || '';
                        // build searchable content from structured attributes for more accurate results
                        const searchable = [
                            (item.dataset.customer || ''),
                            (item.dataset.stall || ''),
                            (item.dataset.location || ''),
                            (item.dataset.desc || ''),
                            (item.dataset.category || ''),
                            (item.dataset.status || ''),
                            textContentFallback()
                        ].join(' ').toLowerCase();

                        let visible = true;
                        if (typeVal !== 'all' && itemType !== typeVal.toLowerCase()) visible = false;
                        if (visible && dateVal !== 'all' && !matchesDateRange(itemDate, dateVal)) visible = false;
                        if (visible && searchVal) {
                            if (searchable.indexOf(searchVal) === -1) visible = false;
                        }

                        function textContentFallback() {
                            return (item.textContent || '').slice(0, 300); // small fallback
                        }

                        item.style.display = visible ? '' : 'none';
                    });
                }

                if (typeSelect) typeSelect.addEventListener('change', filterNotices);
                if (dateSelect) dateSelect.addEventListener('change', filterNotices);
                if (searchInput) searchInput.addEventListener('input', function(e){ filterNotices(); });
                if (searchBtn) searchBtn.addEventListener('click', function(e){ e.preventDefault(); filterNotices(); });

                // initial filter pass
                filterNotices();

                document.querySelectorAll('.view-details-btn').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();

                        // Only populate requested fields: category, description, report date, status, evidence photo
                        document.getElementById('modalCategory').textContent = this.dataset.category || '';
                        document.getElementById('modalDescription').textContent = this.dataset.description || '';
                        document.getElementById('modalReportDate').textContent = this.dataset.date || '';
                        document.getElementById('modalStatus').textContent = this.dataset.status || '';

                        const photo = this.dataset.photo;
                        const photoElem = document.getElementById('modalEvidencePhoto');
                        if (photo) {
                            // build secure proxy URL and add cache-busting timestamp to force reload when updated
                            const proxyBase = 'php/serve_upload.php?f=' + encodeURIComponent(photo);
                            const sep = proxyBase.includes('?') ? '&' : '?';
                            photoElem.src = proxyBase + sep + 't=' + Date.now();
                            photoElem.style.display = 'block';
                        } else {
                            photoElem.style.display = 'none';
                        }

                        // update status badge classes (approved/pending/rejected/expired/complaint)
                        const statusEl = document.getElementById('modalStatus');
                        const statusVal = (this.dataset.status || '').toLowerCase();
                        statusEl.className = 'status-badge';
                        if (statusVal === 'approved') statusEl.classList.add('status-approved');
                        else if (statusVal === 'pending') statusEl.classList.add('status-pending');
                        else if (statusVal === 'rejected') statusEl.classList.add('status-rejected');
                        else if (statusVal === 'expired') statusEl.classList.add('status-expired');
                        else statusEl.classList.add('status-complaint');

                        modal.show();
                    });
                });
            });
    </script>

</body>
</html>
