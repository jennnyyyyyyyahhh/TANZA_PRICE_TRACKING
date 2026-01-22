<?php

    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    require 'php/connection.php';

    // Ensure session is started before accessing `$_SESSION`
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // If not logged in, redirect to login page
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }

    // Now safe to read user id from session
    $userId = $_SESSION['user_id'];
    // Load basic user info for header display
    $user = null;
    if (!empty($userId) && $conn) {
        $stmtUser = $conn->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ? LIMIT 1");
        if ($stmtUser) {
            $stmtUser->bind_param('i', $userId);
            $stmtUser->execute();
            $resUser = $stmtUser->get_result();
            if ($resUser && $resUser->num_rows > 0) {
                $user = $resUser->fetch_assoc();
            }
            $stmtUser->close();
        }
    }
    
    // Fetch report counts grouped by status for analytics
    $counts = [
        'New' => 0,
        'Investigating' => 0,
        'Resolved' => 0,
        'Dismissed' => 0,
    ];

    $countSql = "SELECT status, COUNT(*) AS cnt FROM reports GROUP BY status";
    $countRes = $conn->query($countSql);
    if ($countRes) {
        while ($r = $countRes->fetch_assoc()) {
            $st = $r['status'];
            if (isset($counts[$st])) $counts[$st] = (int)$r['cnt'];
        }
        $countRes->free();
    }

    $totalReports = array_sum($counts);

    // Fetch all reports
    $sql = "SELECT id, report_date, customer_name, stall_name, stall_location, category, description, evidence_photo, created_at, status 
            FROM reports 
            ORDER BY created_at DESC";
    $result = $conn->query($sql);

    // Status to Bootstrap badge colors
    $statusColors = [
        'New' => 'primary',
        'Investigating' => 'warning',
        'Resolved' => 'success',
        'Dismissed' => 'danger'
    ];

    // Category to Bootstrap badge colors
    $categoryColors = [
        'Product Quality' => 'info',
        'Pricing Issues' => 'secondary',
        'Hygiene Concerns' => 'danger',
        'Customer Service' => 'primary',
        'Safety Issues' => 'warning',
        'Fraudulent Practices' => 'dark',
        'Unauthorized Selling' => 'secondary',
        'Expired Goods' => 'danger',
        'Weight Manipulation' => 'warning',
        'Others' => 'light'
    ];

        $query = "SELECT name, value FROM settings";
    $results = $conn->query($query);

    $settings = [];

    if ($results) {
        while ($row = $results->fetch_assoc()) {
            $settings[$row['name']] = $row['value'];
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Management - Tanza Public Market</title>
    
    <!-- Bootstrap CSS - Load first -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS - Load after Bootstrap to override -->
    <link rel="stylesheet" href="../CSS/admin-dashboard.css">
    <link rel="stylesheet" href="../CSS/notification-management.css">
    <script src="../JS/disable-console-logs.js"></script>
    <script src="../JS/disable-all-notifications.js"></script>
    <script src="../JS/disable-login-requirements.js"></script>
</head>
<body>
    <!-- Header Section -->
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
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="">
        <!-- Sidebar -->
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
            
            <!-- <nav class="sidebar-nav">
                                <ul class="nav-list">
                    <li class="nav-item"><a href="admin-dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                    <li class="nav-item"><a href="admin-vendor-management.php" class="nav-link"><i class="fas fa-users"></i><span>Vendor Management</span></a></li>
                    <li class="nav-item"><a href="admin-survey-form.php" class="nav-link"><i class="fas fa-clipboard-list"></i><span>Submit Survey Form</span></a></li>
                    <li class="nav-item active"><a href="admin-report-management.php" class="nav-link"><i class="fas fa-exclamation-triangle"></i><span>Report Management</span></a></li>
                    <li class="nav-item"><a href="admin-cleaning-management.php" class="nav-link"><i class="fas fa-broom"></i><span>Cleaning Management</span></a></li>
                    <li class="nav-item"><a href="admin-business-permit.php" class="nav-link"><i class="fas fa-certificate"></i><span>Business Permit</span></a></li>
                    <li class="nav-item"><a href="admin-events.php" class="nav-link"><i class="fas fa-calendar-alt"></i><span>Events</span></a></li>
                    <li class="nav-item"><a href="admin-notices.php" class="nav-link"><i class="fas fa-bullhorn"></i><span>NOTICES</span></a></li>
                    <li class="nav-item"><a href="admin-notification-management.php" class="nav-link"><i class="fas fa-bell"></i><span>NOTIFICATION MANAGEMENT</span></a></li> 
                </ul>
            </nav>-->
            <?php include __DIR__ . '/admin-sidebar.php'; ?>
            <!-- <div class="sidebar-footer">
                <a href="logout.php" id="logoutBtn" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>LOG OUT</span>
                </a>
            </div> -->
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="top-navbar">
                <!-- <div class="top-nav-left">
                    <button class="mobile-sidebar-trigger" id="mobileSidebarTrigger" aria-label="Open sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Report Management</h1>
                </div> -->
                <div class="date-time">
            <span id="currentDate"><?= date('F d, Y') ?></span>
        </div>
            </header>

            <!-- Report Management Content -->
            <div class="content-wrapper">
                <section class="content-section active" id="report-management-section">
                    <div class="section-header">
                        <h2>Report Management</h2>
                        <p>Handle incident and customer reports about vendors.</p>
                    </div>
                    <div class="section-content">
                        <div class="cleaning-stats">
                            <div class="stat-item">
                                <span class="stat-label">Total Reports:</span>
                                <span class="stat-value" id="totalReports"><?= $totalReports ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">New:</span>
                                <span class="stat-value" id="newReports"><?= $counts['New'] ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Investigating:</span>
                                <span class="stat-value" id="investigatingReports"><?= $counts['Investigating'] ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Resolved:</span>
                                <span class="stat-value" id="resolvedReports"><?= $counts['Resolved'] ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Dismissed:</span>
                                <span class="stat-value" id="dismissedReports"><?= $counts['Dismissed'] ?></span>
                            </div>
                        </div>
                    
                        <div class="report-management-controls">
                            <div class="filter-options">
                                <select id="reportStatusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="New">New</option>
                                    <option value="Investigating">Investigating</option>
                                    <option value="Resolved">Resolved</option>
                                    <option value="Dismissed">Dismissed</option>
                                </select>
                                <select id="reportCategoryFilter">
                                    <option value="">All Categories</option>
                                    <option value="Product Quality">Product Quality</option>
                                    <option value="Pricing Issues">Pricing Issues</option>
                                    <option value="Hygiene Concerns">Hygiene Concerns</option>
                                    <option value="Customer Service">Customer Service</option>
                                    <option value="Safety Issues">Safety Issues</option>
                                    <option value="Fraudulent Practices">Fraudulent Practices</option>
                                    <option value="Unauthorized Selling">Unauthorized Selling</option>
                                    <option value="Expired Goods">Expired Goods</option>
                                    <option value="Weight Manipulation">Weight Manipulation</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            <div class="report-actions-global">
                                <button id="exportReportsBtn" class="btn-sm btn-primary"><i class="fas fa-file-export"></i> Export CSV</button>
                                <button id="printReportsBtn" class="btn-sm btn-secondary"><i class="fas fa-print"></i> Print (PDF)</button>
                            </div>
                        </div>

                        <div class="report-list-container">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Report Date</th>
                                        <th>Customer Name</th>
                                        <th>Vendor/Stall Name</th>
                                        <th>Stall Location</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $row['id'] ?></td>
                                        <td><?= $row['report_date'] ?></td>
                                        <td><?= $row['customer_name'] ?></td>
                                        <td><?= $row['stall_name'] ?></td>
                                        <td><?= htmlspecialchars($row['stall_location']) ?></td>
                                        <td>
                                            <?php
                                                $cat = $row['category'];
                                                $catClass = $categoryColors[$cat] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $catClass ?>">
                                                <?= htmlspecialchars($cat) ?>
                                            </span>
                                        </td>
                                        <td><?= $row['description'] ?></td>
                                        <td>
                                            <?php
                                                $status = $row['status'];
                                                $statusClass = $statusColors[$status] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>">
                                                <?= htmlspecialchars($status) ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <?php 
                                                $evidencePhoto = $row['evidence_photo'] ?? '';
                                                // The photos are stored relative to php/ folder, so we need php/ prefix
                                                // Check if the path already has the correct prefix
                                                if (!empty($evidencePhoto)) {
                                                    // If path starts with 'uploads/' add 'php/' prefix
                                                    if (strpos($evidencePhoto, 'uploads/') === 0) {
                                                        $evidencePhoto = 'php/' . $evidencePhoto;
                                                    } elseif (strpos($evidencePhoto, 'php/') !== 0 && strpos($evidencePhoto, '/') === false) {
                                                        // Just a filename, add full path
                                                        $evidencePhoto = 'php/uploads/' . $evidencePhoto;
                                                    }
                                                }
                                            ?>
                                            <button class="btn-icon view-details" 
                                                data-id="<?= $row['id'] ?>" 
                                                data-date="<?= htmlspecialchars($row['report_date']) ?>"
                                                data-customer="<?= htmlspecialchars($row['customer_name']) ?>"
                                                data-stall="<?= htmlspecialchars($row['stall_name']) ?>"
                                                data-location="<?= htmlspecialchars($row['stall_location']) ?>"
                                                data-category="<?= htmlspecialchars($row['category']) ?>"
                                                data-description="<?= htmlspecialchars($row['description']) ?>"
                                                data-status="<?= htmlspecialchars($row['status']) ?>"
                                                data-photo="<?= htmlspecialchars($evidencePhoto) ?>"
                                                data-created="<?= htmlspecialchars($row['created_at']) ?>"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon investigate-report update-status" data-id="<?= $row['id'] ?>" data-status="Investigating" title="Investigate">
                                                <i class="fas fa-search"></i>
                                            </button>
                                            <button class="btn-icon mark-resolved update-status" data-id="<?= $row['id'] ?>" data-status="Resolved" title="Mark Resolved">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn-icon dismiss-report update-status" data-id="<?= $row['id'] ?>" data-status="Dismissed" title="Dismiss">
                                                <i class="fas fa-times"></i>
                                            </button>

                                        </td>

                                    </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>    
                                             <td colspan="9" class="text-center">No reports found.</td>
                                         </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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

                        
                    </div>
                </section>
            </div>
        </main>
    </div>
    <div id="notif" 
        style="position: fixed; top: 80px; left: 50%; transform: translateX(-50%);
            min-width: 250px; max-width: 90vw;
            padding: 14px 18px; border-radius: 10px;
            color: #fff; font-weight: 500;
            display: none; z-index: 99999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            opacity: 0; transition: opacity 0.3s ease;
            text-align: center;">
        <span id="notifMsg"></span>
        <button id="notifClose" 
            style="background: transparent; border: none; color: #fff;
                float: right; font-size: 18px; cursor: pointer; 
                margin-left: 10px; line-height: 1;">×</button>
    </div>

    <!-- View Report Details Modal -->
    <div class="modal fade" id="viewReportModal" tabindex="-1" aria-labelledby="viewReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-white" style="background-color: #16a34a;">
                    <h5 class="modal-title" id="viewReportModalLabel">
                        <i class="fas fa-file-alt me-2"></i>Report Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Report ID</label>
                                <p class="mb-0" id="modalReportId">-</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Report Date</label>
                                <p class="mb-0" id="modalReportDate">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Customer Name</label>
                                <p class="mb-0" id="modalCustomerName">-</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Status</label>
                                <p class="mb-0" id="modalStatus">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Vendor/Stall Name</label>
                                <p class="mb-0" id="modalStallName">-</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Stall Location</label>
                                <p class="mb-0" id="modalStallLocation">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Category</label>
                                <p class="mb-0" id="modalCategory">-</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Created At</label>
                                <p class="mb-0" id="modalCreatedAt">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">Description</label>
                        <div class="p-3 bg-light rounded" id="modalDescription">-</div>
                    </div>
                    <div class="mb-3" id="evidencePhotoContainer">
                        <label class="form-label fw-bold text-muted">Evidence Photo</label>
                        <div class="text-center">
                            <img id="modalEvidencePhoto" src="" alt="Evidence Photo" class="img-fluid rounded" style="max-height: 300px; display: none;">
                            <p id="noEvidenceText" class="text-muted">No evidence photo provided</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/admin-sidebar.js"></script>
    <script src="../JS/admin-dashboard.js"></script>

    <script>
        // Export / Print handlers
        document.addEventListener('DOMContentLoaded', () => {
            const exportBtn = document.getElementById('exportReportsBtn');
            const printBtn = document.getElementById('printReportsBtn');
            function buildQuery() {
                const status = document.getElementById('reportStatusFilter').value || '';
                const category = document.getElementById('reportCategoryFilter').value || '';
                const params = new URLSearchParams();
                if (status) params.set('status', status);
                if (category) params.set('category', category);
                return params.toString();
            }

            if (exportBtn) {
                exportBtn.addEventListener('click', () => {
                    const q = buildQuery();
                    const url = 'php/export_reports_csv.php' + (q ? ('?' + q) : '');
                    // navigate to CSV to trigger download
                    window.location.href = url;
                });
            }

            if (printBtn) {
                printBtn.addEventListener('click', () => {
                    const q = buildQuery();
                    const url = 'php/export_reports_pdf.php' + (q ? ('?' + q) : '');
                    // open PDF in new tab
                    window.open(url, '_blank');
                });
            }
        });
        function showNotif(message, type = 'success') {
            const notif = document.getElementById('notif');
            const notifMsg = document.getElementById('notifMsg');
            const closeBtn = document.getElementById('notifClose');

            notifMsg.textContent = message;
            notif.style.backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';

            notif.style.display = 'block';
            setTimeout(() => notif.style.opacity = '1', 10); // fade in

            // Automatically hide after 3 seconds
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
            }, 1000); // fade out duration
        }

       document.querySelectorAll('.update-status').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                const status = button.getAttribute('data-status');

                const formData = new FormData();
                formData.append('id', id);
                formData.append('status', status);

                fetch('php/update-report-status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const row = button.closest('tr');
                        const badge = row.querySelector('td:nth-child(8) span');

                        const prevStatus = badge.textContent.trim();
                        const newStatus = status;

                        // Update badge text and color
                        badge.textContent = newStatus;
                        let colorClass = 'secondary';
                        switch(newStatus) {
                            case 'New': colorClass = 'primary'; break;
                            case 'Investigating': colorClass = 'warning'; break;
                            case 'Resolved': colorClass = 'success'; break;
                            case 'Dismissed': colorClass = 'danger'; break;
                        }
                        badge.className = 'badge bg-' + colorClass;

                        // Update analytics counts if present
                        try {
                            const map = {
                                'New': 'newReports',
                                'Investigating': 'investigatingReports',
                                'Resolved': 'resolvedReports',
                                'Dismissed': 'dismissedReports'
                            };

                            if (prevStatus && prevStatus !== newStatus && map[prevStatus]) {
                                const prevEl = document.getElementById(map[prevStatus]);
                                if (prevEl) prevEl.textContent = Math.max(0, parseInt(prevEl.textContent || '0') - 1);
                            }
                            if (map[newStatus]) {
                                const newEl = document.getElementById(map[newStatus]);
                                if (newEl) newEl.textContent = (parseInt(newEl.textContent || '0') + 1);
                            }

                        } catch (e) { console.warn('Failed to update report counts', e); }

                        showNotif('Status updated to "' + newStatus + '"', 'success');

                        // Refresh the page shortly after success so the list and counts
                        // are reloaded from the server (keeps UI consistent).
                        setTimeout(() => {
                            try { window.location.reload(); } catch (reloadErr) { console.warn('Reload failed', reloadErr); }
                        }, 900);

                    } else {
                        showNotif('Failed to update status: ' + data.message, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showNotif('An unexpected error occurred', 'error');
                });
            });
        });


        document.addEventListener('DOMContentLoaded', () => {
            const statusFilter = document.getElementById('reportStatusFilter');
            const categoryFilter = document.getElementById('reportCategoryFilter');
            const table = document.querySelector('.report-table tbody');
            const paginationContainer = document.querySelector('.report-pagination');
            const rows = Array.from(table.querySelectorAll('tr'));
            const rowsPerPage = 10;
            let currentPage = 1;

            function getFilteredRows() {
                const statusValue = statusFilter.value.toLowerCase().trim();
                const categoryValue = categoryFilter.value.toLowerCase().trim();

                return rows.filter(row => {
                    // Skip "no reports found" row
                    if (row.querySelector('td')?.colSpan === 9) return false;

                    const statusText = row.cells[7].innerText.toLowerCase().trim();
                    const categoryText = row.cells[5].innerText.toLowerCase().trim();

                    const statusMatch = !statusValue || statusText === statusValue;
                    const categoryMatch = !categoryValue || categoryText === categoryValue;

                    return statusMatch && categoryMatch;
                });
            }

            function showPage(page = 1) {
                const filteredRows = getFilteredRows();
                const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
                if (page < 1) page = 1;
                if (page > totalPages) page = totalPages || 1;
                currentPage = page;

                // Hide all rows
                rows.forEach(r => r.style.display = 'none');

                // Show only rows for current page
                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                filteredRows.slice(start, end).forEach(r => r.style.display = '');

                // Update pagination buttons
                renderPagination(totalPages);
            }

            function renderPagination(totalPages) {
                paginationContainer.innerHTML = '';

                if (totalPages <= 1) return; // No pagination needed

                // Prev button
                const prevBtn = document.createElement('button');
                prevBtn.className = 'pagination-btn';
                prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
                prevBtn.disabled = currentPage === 1;
                prevBtn.addEventListener('click', () => showPage(currentPage - 1));
                paginationContainer.appendChild(prevBtn);

                // Page number buttons
                for (let i = 1; i <= totalPages; i++) {
                    const btn = document.createElement('button');
                    btn.className = 'pagination-btn' + (i === currentPage ? ' active' : '');
                    btn.textContent = i;
                    btn.addEventListener('click', () => showPage(i));
                    paginationContainer.appendChild(btn);
                }

                // Next button
                const nextBtn = document.createElement('button');
                nextBtn.className = 'pagination-btn';
                nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
                nextBtn.disabled = currentPage === totalPages;
                nextBtn.addEventListener('click', () => showPage(currentPage + 1));
                paginationContainer.appendChild(nextBtn);
            }

            function updateTable() {
                showPage(1); // Reset to first page whenever filters change
            }

            // Event listeners
            statusFilter.addEventListener('change', updateTable);
            categoryFilter.addEventListener('change', updateTable);

            // Initial display
            showPage(1);
        });

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

        // View Report Details functionality
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                const date = button.getAttribute('data-date');
                const customer = button.getAttribute('data-customer');
                const stall = button.getAttribute('data-stall');
                const location = button.getAttribute('data-location');
                const category = button.getAttribute('data-category');
                const description = button.getAttribute('data-description');
                const status = button.getAttribute('data-status');
                const photo = button.getAttribute('data-photo');
                const created = button.getAttribute('data-created');

                // Populate modal fields
                document.getElementById('modalReportId').textContent = id || '-';
                document.getElementById('modalReportDate').textContent = date || '-';
                document.getElementById('modalCustomerName').textContent = customer || '-';
                document.getElementById('modalStallName').textContent = stall || '-';
                document.getElementById('modalStallLocation').textContent = location || '-';
                document.getElementById('modalDescription').textContent = description || '-';
                document.getElementById('modalCreatedAt').textContent = created || '-';

                // Set category with badge
                const categoryColors = {
                    'Product Quality': 'info',
                    'Pricing Issues': 'secondary',
                    'Hygiene Concerns': 'danger',
                    'Customer Service': 'primary',
                    'Safety Issues': 'warning',
                    'Fraudulent Practices': 'dark',
                    'Unauthorized Selling': 'secondary',
                    'Expired Goods': 'danger',
                    'Weight Manipulation': 'warning',
                    'Others': 'light'
                };
                const catColor = categoryColors[category] || 'secondary';
                document.getElementById('modalCategory').innerHTML = `<span class="badge bg-${catColor}">${category || '-'}</span>`;

                // Set status with badge
                const statusColors = {
                    'New': 'primary',
                    'Investigating': 'warning',
                    'Resolved': 'success',
                    'Dismissed': 'danger'
                };
                const statColor = statusColors[status] || 'secondary';
                document.getElementById('modalStatus').innerHTML = `<span class="badge bg-${statColor}">${status || '-'}</span>`;

                // Handle evidence photo
                const photoImg = document.getElementById('modalEvidencePhoto');
                const noPhotoText = document.getElementById('noEvidenceText');
                if (photo && photo.trim() !== '') {
                    // Reset and show loading state
                    photoImg.style.display = 'none';
                    noPhotoText.textContent = 'Loading image...';
                    noPhotoText.style.display = 'block';
                    
                    // Set up error handling for the image
                    photoImg.onerror = function() {
                        photoImg.style.display = 'none';
                        noPhotoText.textContent = 'Unable to load evidence photo';
                        noPhotoText.style.display = 'block';
                    };
                    
                    // Set up success handling
                    photoImg.onload = function() {
                        photoImg.style.display = 'block';
                        noPhotoText.style.display = 'none';
                    };
                    
                    // Set the image source
                    photoImg.src = photo;
                } else {
                    photoImg.style.display = 'none';
                    noPhotoText.textContent = 'No evidence photo provided';
                    noPhotoText.style.display = 'block';
                }

                // Show the modal
                const viewModal = new bootstrap.Modal(document.getElementById('viewReportModal'));
                viewModal.show();
            });
        });
    </script>
</body>
</html>
