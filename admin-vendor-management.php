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
    $stmt = $conn->prepare("
        SELECT u.first_name, u.last_name, u.email, u.created_at,
            p.phone_number,  p.city, p.province, p.postal_code
        FROM users u
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE u.id = ? LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
   
    $vendorsPerPage = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $vendorsPerPage;

    $vendorQuery = "
        SELECT 
            u.id AS user_id,
            COALESCE(u.user_code, '-') AS user_code,
            CONCAT(u.first_name, ' ', u.last_name) AS vendor_name,
            COALESCE(bp.permit_type, '-') AS category,
            COALESCE(bp.stall_number, '-') AS stall,
            COALESCE(up.phone_number, '-') AS contact,
            COALESCE(u.account_status, '-') AS status,
            a.role
        FROM users u
        INNER JOIN admin a ON u.id = a.user_id
        LEFT JOIN business_permits bp ON u.id = bp.user_id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        ORDER BY u.id ASC
        LIMIT $vendorsPerPage OFFSET $offset
        ";

    $vendorResult = $conn->query($vendorQuery);

    // Total vendors count (for page numbers)
    $countResult = $conn->query("SELECT COUNT(*) AS total FROM users u INNER JOIN admin a ON u.id = a.user_id AND a.role = 'vendor'");
    $totalVendors = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalVendors / $vendorsPerPage);


        $query = "SELECT name, value FROM settings";
        $settingsResult = $conn->query($query);

    $settings = [];

    if ($settingsResult) {
        while ($row = $settingsResult->fetch_assoc()) {
            $settings[$row['name']] = $row['value'];
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Management - Tanza Public Market</title>
    
    <!-- Bootstrap CSS - Load first -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS - Load after Bootstrap to override -->
    <link rel="stylesheet" href="CSS/admin-dashboard.css">
    <link rel="stylesheet" href="CSS/notification-management.css">
    <link rel="stylesheet" href="CSS/ss.css">
    <link rel="stylesheet" href="CSS/admin-mobile-view.css">

    <!-- Bootstrap JS & Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="JS/disable-console-logs.js"></script>
    <script src="JS/disable-all-notifications.js"></script>
    <script src="JS/disable-login-requirements.js"></script>
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
                    <li class="nav-item active"><a href="admin-vendor-management.php" class="nav-link"><i class="fas fa-users"></i><span>Vendor Management</span></a></li>
                    <li class="nav-item"><a href="admin-survey-form.php" class="nav-link"><i class="fas fa-clipboard-list"></i><span>Submit Survey Form</span></a></li>
                    <li class="nav-item"><a href="admin-report-management.php" class="nav-link"><i class="fas fa-exclamation-triangle"></i><span>Report Management</span></a></li>
                    <li class="nav-item"><a href="admin-cleaning-management.php" class="nav-link"><i class="fas fa-broom"></i><span>Cleaning Management</span></a></li>
                   <li class="nav-item"><a href="admin-vendor-requests.php" class="nav-link"><i class="fas fa-file-alt"></i><span>VENDOR REQUESTS</span></a></li> -->
                    <!--<li class="nav-item"><a href="admin-business-permit.php" class="nav-link"><i class="fas fa-certificate"></i><span>Business Permit</span></a></li>
                    <li class="nav-item"><a href="admin-events.php" class="nav-link"><i class="fas fa-calendar-alt"></i><span>Eventss</span></a></li>
                    <li class="nav-item"><a href="admin-notices.php" class="nav-link"><i class="fas fa-bullhorn"></i><span>NOTICES</span></a></li>
                    <li class="nav-item"><a href="admin-notification-management.php" class="nav-link"><i class="fas fa-bell"></i><span>NOTIFICATION MANAGEMENT</span></a></li> -->
                <!--</ul>
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
                    <h1 class="page-title">Vendor Management</h1>
                </div> -->
                <div class="date-time">
                    <span id="currentDate"><?= date('F d, Y') ?></span>
                </div>
            </header>

            <!-- Vendor Management Content -->
            <div class="content-wrapper">
                <section class="content-section active" id="vendor-management-section">
                    <div class="section-header">
                        <h2>Vendor Management</h2>
                        <p>View and manage all market vendors.</p>
                    </div>
                    <div class="section-content">
                        <div class="vendor-management-controls">
                            <div class="filter-options">
                                <select id="vendorStatusFilter">
                                    <option value="all">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                                <select id="vendorCategoryFilter">
                                    <option value="all">All Categories</option>
                                    <option value="produce">Produce</option>
                                    <option value="meat">Meat & Poultry</option>
                                    <option value="seafood">Seafood</option>
                                    <option value="dry-goods">Dry Goods</option>
                                    <option value="food-stalls">Food Stalls</option>
                                </select>
                            </div>
                            <div class="vendor-actions-global" style="display:flex;gap:0.5rem;align-items:center;">
                                <input id="vendorSearch" type="search" class="form-control" placeholder="Search by ID, name, category..." style="min-width:220px;" />
                                <button id="exportListBtn" class="btn-sm btn-secondary"><i class="fas fa-file-export"></i> Export List</button>
                            </div>
                        </div>

                        <div class="vendor-list-container">
                            <table class="vendor-table">
                                <thead>
                                    <tr>
                                        
                                        <th>ID</th> 
                                        <th>Vendor Name</th>
                                        <th>Category</th>
                                        <th>Stall</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($vendorResult && $vendorResult->num_rows > 0): ?>
                                        <?php while ($row = $vendorResult->fetch_assoc()): ?>
                                            <tr>
                                                <td data-label="ID"><?= htmlspecialchars($row['user_code'] ?? $row['user_id']) ?></td>
                                                <td data-label="Vendor Name"><?= htmlspecialchars($row['vendor_name']) ?></td>
                                                <td data-label="Category"><?= htmlspecialchars($row['category']) ?></td>
                                                <td data-label="Stall"><?= htmlspecialchars($row['stall']) ?></td>
                                                <td data-label="Contact"><?= htmlspecialchars($row['contact']) ?></td>
                                                <td data-label="Status">
                                                    <span class="status-badge <?= strtolower($row['status']) ?>">
                                                        <?= htmlspecialchars($row['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="action-buttons">
                                                    <button class="btn-icon view-btn" title="View" data-user-id="<?= $row['user_id'] ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>

                                                    <button class="btn-icon toggle-status-btn" 
                                                            data-user-id="<?= $row['user_id'] ?>" 
                                                            data-status="<?= $row['status'] ?>" 
                                                            title="Activate">
                                                        <i class="<?= $row['status'] === 'active' ? 'fas fa-pause' : 'fas fa-play' ?>"></i>
                                                    </button>

                                                    <button class="btn-icon suspend-btn" 
                                                            data-user-id="<?= $row['user_id'] ?>" 
                                                            data-status="suspended" 
                                                            title="Suspend"
                                                            <?= strtolower($row['status']) === 'suspended' ? 'disabled' : '' ?>>
                                                        <i class="fas fa-user-slash"></i>
                                                    </button>

                                                    <button class="btn-icon delete-btn" 
                                                            data-user-id="<?= $row['user_id'] ?>" 
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>

                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="8">No vendors found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="vendor-pagination">
                            <?php if ($page > 1): ?>
                                <button class="pagination-btn" onclick="goToPage(<?= $page-1 ?>)"><i class="fas fa-chevron-left"></i></button>
                            <?php else: ?>
                                <button class="pagination-btn" disabled><i class="fas fa-chevron-left"></i></button>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <button class="pagination-btn <?= $i==$page?'active':'' ?>" onclick="goToPage(<?= $i ?>)"><?= $i ?></button>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <button class="pagination-btn" onclick="goToPage(<?= $page+1 ?>)"><i class="fas fa-chevron-right"></i></button>
                            <?php else: ?>
                                <button class="pagination-btn" disabled><i class="fas fa-chevron-right"></i></button>
                            <?php endif; ?>
                        </div>


                        
                    </div>
                </section>
            </div>
        </main>
    </div>
           
    <!-- Edit Vendor Modal -->
    <div class="modal fade" id="editVendorModal" tabindex="-1" aria-labelledby="editVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editVendorModalLabel">Edit Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editVendorForm">
                    <input type="hidden" id="editVendorId" name="user_id">
                    
                    <div class="row mb-3">
                        <div class="col">
                        <label for="editFirstName" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="editFirstName" name="first_name" required>
                        </div>
                        <div class="col">
                        <label for="editLastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="editLastName" name="last_name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editContact" class="form-label">Contact</label>
                        <input type="text" class="form-control" id="editContact" name="contact">
                    </div>

                    <div class="mb-3">
                        <label for="editStallNumber" class="form-label">Stall Number</label>
                        <input type="text" class="form-control" id="editStallNumber" name="stall_number" required>
                    </div>

                    <div class="mb-3">
                        <label for="editPermitType" class="form-label">Permit Type</label>
                        <input type="text" class="form-control" id="editPermitType" name="permit_type" required>
                    </div>

                    
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Confirm Action Modal -->
    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="confirmActionModalLabel">
                    <i class="fas fa-question-circle me-2"></i>Confirm Action
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p>Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmActionBtn" class="btn btn-primary">Confirm</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Scripts -->
    <script src="JS/admin-sidebar.js"></script>
<script>
// Pagination function - navigate to page
function goToPage(pageNum) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', pageNum);
    window.location.href = url.toString();
}

document.addEventListener("DOMContentLoaded", () => {
    let actionType = null;
    let targetUserId = null;
    let targetNewValue = null;

    // Use this modal for all confirmations
    const confirmActionModal = new bootstrap.Modal(document.getElementById("confirmActionModal"));
    const confirmBtn = document.getElementById("confirmActionBtn");
    const modalTitle = document.getElementById("confirmActionModalLabel");
    const modalBody = document.querySelector("#confirmActionModal .modal-body p");

    // Open confirmation modal
    function openConfirmModal(type, userId, message, newValue = null) {
        actionType = type;
        targetUserId = userId;
        targetNewValue = newValue;
        modalTitle.innerHTML = `<i class="fas fa-question-circle"></i> Confirm Action`;
        modalBody.textContent = message;
        confirmActionModal.show();
    }

    // When Confirm button is clicked
    confirmBtn.addEventListener("click", () => {
        if (!actionType || !targetUserId) return;

        switch (actionType) {
            case "status":
                handleStatusChange(targetUserId, targetNewValue);
                break;
            case "suspend":
                handleSuspend(targetUserId);
                break;
            case "delete":
                handleDelete(targetUserId);
                break;
        }

        confirmActionModal.hide();
    });

    // --- BUTTON ACTIONS ---

    // Toggle Active/Inactive
    document.querySelectorAll(".toggle-status-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            const userId = parseInt(this.dataset.userId);
            const currentStatus = this.dataset.status.toLowerCase().trim();
            const newStatus = currentStatus === "active" ? "inactive" : "active";
            const actionText = newStatus === "active" ? "activate" : "inactivate";
            openConfirmModal("status", userId, `Are you sure you want to ${actionText} this account?`, newStatus);
        });
    });

    // Suspend
    document.querySelectorAll(".suspend-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            const userId = parseInt(this.dataset.userId);
            openConfirmModal("suspend", userId, "Are you sure you want to suspend this account?");
        });
    });

    // Delete Vendor
    document.querySelectorAll(".delete-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            const userId = parseInt(this.dataset.userId);
            openConfirmModal("delete", userId, "Are you sure you want to delete this vendor? This action cannot be undone.");
        });
    });

    // --- ACTION HANDLERS ---

    function handleStatusChange(userId, newStatus) {
        const actionText = newStatus === "active" ? "activate" : "inactivate";
        fetch("php/update_status.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: userId, status: newStatus })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const btn = document.querySelector(`.toggle-status-btn[data-user-id='${userId}']`);
                btn.dataset.status = newStatus;
                btn.querySelector("i").className = newStatus === "active" ? "fas fa-pause" : "fas fa-play";

                const badge = btn.closest("tr").querySelector(".status-badge");
                badge.textContent = newStatus;
                badge.className = "status-badge " + newStatus;

                showNotif(`Account ${actionText}d successfully`, "#4caf50");
            } else {
                showNotif(`Error: ${data.message}`, "#f44336");
            }
        })
        .catch(err => showNotif(`AJAX failed: ${err}`, "#f44336"));
    }

    function handleSuspend(userId) {
        fetch("php/update_status.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: userId, status: "suspended" })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`.suspend-btn[data-user-id='${userId}']`).closest("tr");
                const badge = row.querySelector(".status-badge");
                badge.textContent = "suspended";
                badge.className = "status-badge suspended";
                row.querySelector(".suspend-btn").disabled = true;
                showNotif("User suspended successfully", "#ff9800");
            } else {
                showNotif(`Error: ${data.message}`, "#f44336");
            }
        })
        .catch(err => showNotif(`AJAX failed: ${err}`, "#f44336"));
    }

    function handleDelete(userId) {
        fetch("php/delete_vendor.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: userId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`.delete-btn[data-user-id='${userId}']`).closest("tr");
                row.remove();
                showNotif("Vendor deleted successfully", "#4caf50");
            } else {
                showNotif(`Error: ${data.message}`, "#f44336");
            }
        })
        .catch(err => showNotif(`AJAX failed: ${err}`, "#f44336"));
    }

    // View vendor details in modal (read-only)
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            if (!userId) return;

            // fetch details with robust parsing (server may return warnings)
            // use absolute path to avoid relative-path issues
            fetch(`/php/get_user_details.php?id=${encodeURIComponent(userId)}`)
                .then(res => res.text())
                .then(text => {
                    let data = null;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON from get_user_details.php:', text);
                        showNotif('Failed to load user (server error) — see debug', '#f44336');
                        // show raw server response in debug modal for easier inspection
                        let pre = document.getElementById('debugResponsePre');
                        if (!pre) {
                            // create modal if not exists
                            const dbgHtml = `\n<div class="modal fade" id="debugResponseModal" tabindex="-1" aria-hidden="true">\n  <div class="modal-dialog modal-lg modal-dialog-centered">\n    <div class="modal-content">\n      <div class="modal-header">\n        <h5 class="modal-title">Server Response (debug)</h5>\n        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>\n      </div>\n      <div class="modal-body"><pre id=\"debugResponsePre\" style=\"white-space:pre-wrap;word-break:break-word;max-height:400px;overflow:auto;\"></pre></div>\n      <div class="modal-footer"><button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Close</button></div>\n    </div>\n  </div>\n</div>`;
                            const wrapper = document.createElement('div');
                            wrapper.innerHTML = dbgHtml;
                            document.body.appendChild(wrapper);
                            pre = document.getElementById('debugResponsePre');
                        }
                        pre.textContent = text;
                        const dbgModalEl = document.getElementById('debugResponseModal');
                        if (dbgModalEl) new bootstrap.Modal(dbgModalEl).show();
                        return;
                    }

                    if (!data || !data.success) {
                        showNotif(data?.message || 'User not found', '#f44336');
                        return;
                    }

                    const u = data.user || {};
                    const p = data.profile || {};
                    const perm = data.permit || {};

                    // populate modal fields
                    document.getElementById('editVendorModalLabel').textContent = 'View Vendor';
                    document.getElementById('editVendorId').value = u.id || '';
                    document.getElementById('editFirstName').value = u.first_name || '';
                    document.getElementById('editLastName').value = u.last_name || '';
                    document.getElementById('editContact').value = p.phone_number || '';
                    document.getElementById('editStallNumber').value = perm.stall_number || '';
                    document.getElementById('editPermitType').value = perm.permit_type || '';

                    // make inputs readonly/disabled for view mode
                    ['editFirstName','editLastName','editContact','editStallNumber','editPermitType'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) { el.readOnly = true; el.disabled = true; }
                    });

                    // hide Save button (the submit button inside the edit form)
                    const saveBtn = document.querySelector('#editVendorForm button[type="submit"]');
                    if (saveBtn) saveBtn.style.display = 'none';

                    // show modal
                    const modalEl = document.getElementById('editVendorModal');
                    const bsModal = new bootstrap.Modal(modalEl);
                    bsModal.show();
                })
                .catch(err => { showNotif('Failed to load user (network)', '#f44336'); console.error(err); });
        });
    });

    // --- NOTIFICATION SYSTEM ---
    function showNotif(message, color = "#4caf50") {
        const notif = document.getElementById("notif");
        const notifMsg = document.getElementById("notifMsg");
        notif.style.background = color;
        notifMsg.textContent = message;
        notif.style.display = "block";
        setTimeout(() => notif.style.opacity = 1, 50);
        setTimeout(() => {
            notif.style.opacity = 0;
            setTimeout(() => notif.style.display = "none", 300);
        }, 3000);
    }

    document.getElementById("notifClose").addEventListener("click", () => {
        const notif = document.getElementById("notif");
        notif.style.opacity = 0;
        setTimeout(() => notif.style.display = "none", 300);
    });

    // When the edit/view modal is closed, restore inputs to editable and show Save button again
    const editVendorModalEl = document.getElementById('editVendorModal');
    if (editVendorModalEl) {
        editVendorModalEl.addEventListener('hidden.bs.modal', () => {
            ['editFirstName','editLastName','editContact','editStallNumber','editPermitType'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.readOnly = false; el.disabled = false; }
            });
            const saveBtn = document.querySelector('#editVendorForm button[type="submit"]');
            if (saveBtn) saveBtn.style.display = '';
            // reset modal title
            const title = document.getElementById('editVendorModalLabel');
            if (title) title.textContent = 'Edit Vendor';
        });
    }

    // --- FILTERS, SEARCH and EXPORT ---
    const vendorStatusFilterEl = document.getElementById('vendorStatusFilter');
    const vendorCategoryFilterEl = document.getElementById('vendorCategoryFilter');
    const vendorSearchEl = document.getElementById('vendorSearch');
    const exportBtn = document.getElementById('exportListBtn');

    function applyFilters() {
        const status = (vendorStatusFilterEl?.value || 'all').toLowerCase();
        const category = (vendorCategoryFilterEl?.value || 'all').toLowerCase();
        const query = (vendorSearchEl?.value || '').trim().toLowerCase();

        const tbody = document.querySelector('.vendor-table tbody');
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        let visibleCount = 0;

        rows.forEach(row => {
            // skip placeholder rows
            const cells = row.querySelectorAll('td');
            if (!cells || cells.length === 0) return;

            const idText = (cells[0].textContent || '').trim().toLowerCase();
            const nameText = (cells[1].textContent || '').trim().toLowerCase();
            const categoryText = (cells[2].textContent || '').trim().toLowerCase();
            const statusBadge = row.querySelector('.status-badge');
            const statusText = (statusBadge ? statusBadge.textContent : (cells[5]?.textContent || '')).trim().toLowerCase();

            let statusMatch = (status === 'all') || (statusText === status);

            // allow category match by inclusion (maps values like "dry-goods" to check against displayed text)
            let categoryValue = category.replace('-', ' ');
            let categoryMatch = (category === 'all') || categoryText.includes(categoryValue);

            let searchMatch = true;
            if (query.length > 0) {
                searchMatch = idText.includes(query) || nameText.includes(query) || categoryText.includes(query) || statusText.includes(query);
            }

            const show = statusMatch && categoryMatch && searchMatch;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        // Optionally update a small status indicator (not present by default)
        // you could add an element to show counts if desired
        // e.g., document.getElementById('vendorCount').textContent = visibleCount;
    }

    function exportVisibleToCSV() {
        const table = document.querySelector('.vendor-table');
        if (!table) return;
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        const rows = Array.from(table.querySelectorAll('tbody tr')).filter(r => r.style.display !== 'none');
        const csvRows = [];
        csvRows.push(headers.map(h => '"' + h.replace(/"/g, '""') + '"').join(','));

        rows.forEach(row => {
            const cells = Array.from(row.querySelectorAll('td'));
            const values = cells.map(td => '"' + (td.textContent || '').trim().replace(/"/g, '""') + '"');
            csvRows.push(values.join(','));
        });

        const csv = csvRows.join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        const page = new URL(window.location.href).searchParams.get('page') || '1';
        link.download = `vendors_page_${page}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    // keep URL params in sync for pagination
    function updateUrlParams() {
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('status', vendorStatusFilterEl.value || 'all');
            url.searchParams.set('category', vendorCategoryFilterEl.value || 'all');
            url.searchParams.set('search', vendorSearchEl.value || '');
            // do not navigate; just replace state so params persist when paginating
            window.history.replaceState({}, '', url.toString());
        } catch (e) {
            // ignore
        }
    }

    // Initialize from URL params (if present)
    (function initFiltersFromUrl() {
        try {
            const params = new URL(window.location.href).searchParams;
            const s = params.get('status');
            const c = params.get('category');
            const q = params.get('search');
            if (s && vendorStatusFilterEl) vendorStatusFilterEl.value = s;
            if (c && vendorCategoryFilterEl) vendorCategoryFilterEl.value = c;
            if (q !== null && vendorSearchEl) vendorSearchEl.value = q;
        } catch (e) {
            // ignore
        }
        applyFilters();
    })();

    vendorStatusFilterEl?.addEventListener('change', () => { applyFilters(); updateUrlParams(); });
    vendorCategoryFilterEl?.addEventListener('change', () => { applyFilters(); updateUrlParams(); });
    vendorSearchEl?.addEventListener('input', () => { applyFilters(); updateUrlParams(); });
    exportBtn?.addEventListener('click', exportVisibleToCSV);
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
