<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Ensure DB connection is available for role check
    require_once __DIR__ . '/php/connection.php';

    // If not logged in, redirect to login page
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }

    // enforce admin role: check admin table for this user
    $userId = intval($_SESSION['user_id']);
    $isAdmin = false;
    if (isset($conn) && $conn) {
        $stmtRole = $conn->prepare("SELECT role, is_active FROM admin WHERE user_id = ? LIMIT 1");
        if ($stmtRole) {
            $stmtRole->bind_param('i', $userId);
            $stmtRole->execute();
            $resRole = $stmtRole->get_result();
            if ($resRole && $rowRole = $resRole->fetch_assoc()) {
                $roleVal = strtolower($rowRole['role'] ?? '');
                $activeVal = intval($rowRole['is_active'] ?? 0);
                if ($roleVal === 'admin' && $activeVal === 1) {
                    $isAdmin = true;
                }
            }
            $stmtRole->close();
        }
    }

    if (!$isAdmin) {
        // not an admin — redirect to login (or show 403)
        header("Location: /login.php");
        exit;
    }

    // include backend logic (pagination, queries) now that auth is confirmed
    require_once __DIR__ . '/php/admin_backend/admin-cleaning-management.php';
    $user = null;
    if (!empty($userId) && isset($conn) && $conn) {
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
    <title>Cleaning Management - Tanza Public Market</title>
    
    <!-- Bootstrap CSS - Load first -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS - Load after Bootstrap to override -->
    <link rel="stylesheet" href="CSS/admin-dashboard.css">
    <link rel="stylesheet" href="CSS/notification-management.css">
    <link rel="stylesheet" href="CSS/ss.css">
    <link rel="stylesheet" href="CSS/admin-mobile-view.css">

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
            
            <?php include __DIR__ . '/admin-sidebar.php'; ?>
            <!-- <div class="sidebar-footer">
                <a href="logout.php" id="logoutBtn" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>LOG OUT</span>
                </a>
            </div> -->
        </aside>
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
        <main class="main-content">
            <header class="top-navbar">
                <!-- <div class="top-nav-left">
                    <button class="mobile-sidebar-trigger" id="mobileSidebarTrigger" aria-label="Open sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Cleaning Management</h1>
                </div> -->
                <div class="date-time">
            <span id="currentDate"><?= date('F d, Y') ?></span>
        </div>
            </header>

            <div class="content-wrapper">
                <section class="content-section active" id="cleaning-management-section">
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
                                <button class="btn-sm btn-info" id="cleaningScheduleBtn" style="color: #000;"><i class="fas fa-calendar-alt"></i> View Schedule</button>
                            </div>
                        </div>

                        <div class="cleaning-stats">
                            <div class="stat-item">
                                <span class="stat-label">Total Requests:</span>
                                <span class="stat-value" id="totalCleaningRequests"><?= $totalRequests ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Pending:</span>
                                <span class="stat-value pending" id="pendingCleaningRequests"><?= $pendingRequests ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Completed:</span>
                                <span class="stat-value completed" id="completedCleaningRequests"><?= $completedRequests ?></span>
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
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr data-request-id="<?= htmlspecialchars($row['id']) ?>">
                                            <td data-label="Request ID">CR<?= str_pad($row['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                            <td data-label="Vendor Name"><?= htmlspecialchars($row['vendor_name']) ?></td>
                                            <td data-label="Stall Number"><?= htmlspecialchars($row['stall_number']) ?></td>
                                            <td data-label="Request Date"><?= date("M j, Y", strtotime($row['preferred_date'])) ?></td>
                                            <td data-label="Scheduled Time"><?= htmlspecialchars($row['preferred_time']) ?></td>
                                            <td data-label="Status">
                                                <span class="status-badge <?= $row['status'] === 'completed' ? 'completed' : 'pending' ?>">
                                                    <?= ucfirst($row['status']) ?>
                                                </span>
                                            </td>
                                            <td data-label="" class="action-buttons" style="display: flex; justify-content: center;">
                                                <div class="btn-group" role="group" aria-label="Actions" style="width: auto;">
                                                    <!-- View button removed (moved to relevant admin pages) -->

                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                                        <input type="hidden" name="new_status" value="<?= $row['status'] === 'pending' ? 'completed' : 'pending' ?>">
                                                        <button class="btn btn-sm <?= $row['status'] === 'pending' ? 'btn-success' : 'btn-warning' ?>" type="submit" title="Toggle Status" style="color: #fff; width: 50px; height: 40px;">
                                                            <i class="fas <?= $row['status'] === 'pending' ? 'fa-check' : 'fa-undo' ?>"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>


                        <div class="cleaning-pagination">
                            <!-- Previous button -->
                            <a href="?page=<?= max(1, $page - 1) ?>" class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>

                            <!-- Page numbers -->
                            <?php
                            $range = 2; // number of pages to show before and after current page
                            for ($i = 1; $i <= $totalPages; $i++):
                                if ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range)):
                            ?>
                                <a href="?page=<?= $i ?>" class="pagination-btn <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
                            <?php elseif ($i == 2 && $page - $range > 2 || $i == $totalPages - 1 && $page + $range < $totalPages - 1): ?>
                                <span class="pagination-dots">...</span>
                            <?php endif; endfor; ?>

                            <!-- Next button -->
                            <a href="?page=<?= min($totalPages, $page + 1) ?>" class="pagination-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>

                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="JS/admin-sidebar.js"></script>
    <script src="JS/admin-dashboard.js"></script>
    <!-- Bootstrap JS (optional, for interactivity like modals or tooltips) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const notif = document.getElementById("notif");
            const notifMsg = document.getElementById("notifMsg");
            const notifClose = document.getElementById("notifClose");

            <?php if (!empty($_SESSION['notif_message'])): ?>
                notifMsg.textContent = "<?= addslashes($_SESSION['notif_message']) ?>";
                notif.style.display = "block";
                notif.style.backgroundColor = "#28a745"; // green for completed or pending
                setTimeout(() => notif.style.opacity = 1, 50);
                setTimeout(() => notif.style.opacity = 0, 4000);
                setTimeout(() => notif.style.display = "none", 4500);
            <?php unset($_SESSION['notif_message']); endif; ?>

            notifClose.addEventListener("click", () => {
                notif.style.opacity = 0;
                setTimeout(() => notif.style.display = "none", 300);
            });
        });

        // Export cleaning requests CSV
        document.getElementById('exportCleaningReportsBtn')?.addEventListener('click', () => {
            const status = document.getElementById('cleaningStatusFilter')?.value || '';
            const date = document.getElementById('requestDateFilter')?.value || '';
            const params = new URLSearchParams();
            if (status && status !== 'all') params.set('status', status);
            if (date) params.set('date', date);
            const url = 'php/export_cleaning_csv.php' + (params.toString() ? ('?' + params.toString()) : '');
            window.location.href = url;
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
    </script>

</body>
</html>
