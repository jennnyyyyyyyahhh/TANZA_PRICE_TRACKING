<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    require_once 'php/admin_backend/admin-business-permit.php';

    // If not logged in, redirect to login page
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }

    // Now safe to read user id from session
    $userId = $_SESSION['user_id'];
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
    <title>Business Permit - Tanza Public Market</title>
    
    <!-- Bootstrap CSS - Load first -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
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
                    <li class="nav-item"><a href="index.php" class="nav-link">HOME</a></li>
                    <li class="nav-item">
                        <a href="pricefront.php"  style="color: white; text-decoration: none;">PRICES</a>
                    </li>
                    <li class="nav-item"><a href="index.php#weather" class="nav-link">WEATHER</a></li>
                    <li class="nav-item"><a href="index.php#about" class="nav-link">ABOUT</a></li>
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

        <main class="main-content">
            <header class="top-navbar">
                <!-- <div class="top-nav-left">
                    <button class="mobile-sidebar-trigger" id="mobileSidebarTrigger" aria-label="Open sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Business Permit Management</h1>
                </div> -->
                <div class="date-time">
            <span id="currentDate"><?= date('F d, Y') ?></span>
            </div>
            </header>

            <!-- Business Permit Management Section -->
            <div class="container-fluid py-4 bg-light min-vh-100">
                <section class="card shadow-lg border-0 rounded-4 p-4">
                    <!-- Header -->
                    <div class="card-header bg-white border-0 pb-3">
                    <h2 class="h4 fw-bold text-dark mb-1">Business Permit Management</h2>
                    <p class="text-muted mb-0">Review and manage vendor business permit applications and renewals.</p>
                    </div>

                    <div class="card-body">
                    <!-- Controls -->
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
                        <div class="form-floating w-100 w-md-25">
                            <select class="form-select" id="permitStatusFilter" onchange="location = this.value;">
                                <option value="?status=all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Status</option>
                                <option value="?status=pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                                <option value="?status=approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="?status=rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                <option value="?status=expired" <?= $filterStatus === 'expired' ? 'selected' : '' ?>>Expired</option>
                            </select>
                            <label for="permitStatusFilter">Filter by Status</label>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="row g-3 mb-4 text-center">
                        <div class="col-6 col-md-2">
                            <div class="card border-0 bg-body shadow-sm rounded-4">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">Total Permits</p>
                                    <h5 class="fw-bold mb-0"><?= $totalPermits ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card border-0 bg-warning-subtle shadow-sm rounded-4">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">Pending</p>
                                    <h5 class="fw-bold text-warning mb-0"><?= $pendingPermits ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card border-0 bg-success-subtle shadow-sm rounded-4">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">Approved</p>
                                    <h5 class="fw-bold text-success mb-0"><?= $approvedPermits ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card border-0 bg-danger-subtle shadow-sm rounded-4">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">Expired</p>
                                    <h5 class="fw-bold text-danger mb-0"><?= $expiredPermits ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card border-0 bg-secondary-subtle shadow-sm rounded-4">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">Rejected</p>
                                    <h5 class="fw-bold text-secondary mb-0"><?= $rejectedPermits ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Table -->
                    <div class="table-responsive">
                        <table class='table table-hover align-middle'>
                <thead class='table-light text-secondary small text-uppercase'>
                    <tr>
                    <th>ID</th>
                    <th>Permit Number</th>
                    <th>Vendor Name</th>
                    <th>Business Name</th>
                    <th>Stall</th>
                    <th>Submit Date</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                    </tr>
                </thead>
                <tbody class='border-top'>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class='fw-semibold'><?= htmlspecialchars($row['id']) ?></td>
                        <td class='fw-semibold'><?= htmlspecialchars($row['permit_number']) ?></td>
                        <td><?= htmlspecialchars($row['vendor_name']) ?></td>
                        <td><?= htmlspecialchars($row['business_name']) ?></td>
                        <td><?= htmlspecialchars($row['stall_number']) ?></td>
                        <td><?= htmlspecialchars(date('M d, Y', strtotime($row['submit_date']))) ?></td>
                        <td><?= $row['expiry_date'] ? htmlspecialchars(date('M d, Y', strtotime($row['expiry_date']))) : '-' ?></td>
                        <td>
                        <?php
                        $status = strtolower($row['status']);
                            if ($status === 'approved') {
                                echo "<span class='badge bg-success-subtle text-success d-inline-block text-center' style='min-width:100px;padding:6px 0;font-size:0.85rem;border-radius:8px;'>Approved</span>";
                            } elseif ($status === 'pending') {
                                echo "<span class='badge bg-warning-subtle text-warning d-inline-block text-center' style='min-width:100px;padding:6px 0;font-size:0.85rem;border-radius:8px;'>Pending</span>";
                            } else {
                                echo "<span class='badge bg-danger-subtle text-danger d-inline-block text-center' style='min-width:100px;padding:6px 0;font-size:0.85rem;border-radius:8px;'>Rejected</span>";
                            }
                            ?>
                        </td>

                        <td>
                            <!-- View (available for all statuses) -->
                            <button class='btn btn-sm btn-primary me-2 d-inline-flex align-items-center justify-content-center' 
                                    style='width:36px;height:36px;border-radius:8px;color:#fff;' 
                                    data-bs-toggle='modal' data-bs-target='#viewModal<?= $row['id'] ?>' title='View'>
                                <i class='fas fa-eye'></i>
                            </button>

                            <?php if ($status === 'approved'): ?>
                                <?php if (!empty($row['file_path'])): ?>
                                    <a href="php/download.php?id=<?= urlencode($row['id']) ?>"
                                        class="btn btn-sm btn-secondary d-inline-flex align-items-center justify-content-center"
                                        style="width:36px;height:36px;border-radius:8px;color:#fff;" title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Review -->
                                <form method='POST' style='display:inline;'>
                                    <input type='hidden' name='permit_id' value='<?= $row['id'] ?>'>
                                    <input type='hidden' name='action_type' value='review'>
                                    <button type='submit' 
                                            class='btn btn-sm btn-success me-2 d-inline-flex align-items-center justify-content-center' 
                                            style='width:36px;height:36px;border-radius:8px;color:#fff;' title='Review'>
                                        <i class='fas fa-check'></i>
                                    </button>
                                </form>

                                <!-- Reject -->
                                <button class='btn btn-sm btn-danger d-inline-flex align-items-center justify-content-center' 
                                        style='width:36px;height:36px;border-radius:8px;color:#fff;' 
                                        data-bs-toggle='modal' data-bs-target='#rejectModal<?= $row['id'] ?>' title='Reject'>
                                    <i class='fas fa-times'></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <!-- View Modal -->
                    <div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Business Permit Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Permit No:</strong> <?= htmlspecialchars($row['permit_number']) ?></p>
                                    <p><strong>Business Name:</strong> <?= htmlspecialchars($row['business_name']) ?></p>
                                    <p><strong>Stall:</strong> <?= htmlspecialchars($row['stall_number']) ?></p>
                                    <p><strong>Type:</strong> <?= htmlspecialchars($row['permit_type'] ?? '-') ?></p>
                                    <p><strong>Issue Date:</strong> <?= htmlspecialchars($row['submit_date']) ?></p>
                                    <p><strong>Expiry Date:</strong> <?= htmlspecialchars($row['expiry_date']) ?></p>
                                    <hr>
                                    <?php if (!empty($row['file_path'])): ?>
                                        <?php 
                                            $filePath = $row['file_path'];
                                            $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                                            $isImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
                                        ?>
                                        <?php if ($isImage): ?>
                                            <div class="text-center">
                                                <p class="fw-bold text-muted mb-2">Uploaded Permit Document:</p>
                                                <img src="<?= htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8') ?>" 
                                                     alt="Business Permit" 
                                                     class="img-fluid rounded shadow-sm" 
                                                     style="max-height: 500px; max-width: 100%; object-fit: contain; cursor: pointer;"
                                                     onclick="window.open(this.src, '_blank')">
                                                <p class="text-muted small mt-2"><i class="fas fa-info-circle me-1"></i>Click image to view full size</p>
                                            </div>
                                        <?php else: ?>
                                            <embed src="<?= htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8') ?>" type="application/pdf" width="100%" height="500px">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="text-muted">No permit file found.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reject Modal -->
                    <div class="modal fade" id="rejectModal<?= $row['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                    <h5 class="modal-title">Reject Business Permit</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                    <input type="hidden" name="permit_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="action_type" value="reject">
                                    <div class="mb-3">
                                        <label class="form-label">Rejection Reason</label>
                                        <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                                    </div>
                                    </div>
                                    <div class="modal-footer">
                                    <button type="submit" class="btn btn-danger">Reject</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>
                        

        <div class="vendor-pagination">
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
                <button 
                    class="pagination-btn <?= $i == $page ? 'active' : '' ?>" 
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
    </section>

</div>

        </main>
    </div>

    <script src="JS/admin-sidebar.js"></script>
    <script src="JS/admin-dashboard.js"></script>
    <script 
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" 
        crossorigin="anonymous">
    </script>

    <script>
        function goToPage(page) {
            window.location.href = '?page=' + page;   // reload with page
        }

        // // On page load, check localStorage
        // document.addEventListener('DOMContentLoaded', () => {
        //     const savedPage = localStorage.getItem('vendorPage');
        //     if(savedPage && parseInt(savedPage) !== <?= $page ?>){
        //         window.location.href = '?page=' + savedPage;
        //     }
        // });


        document.addEventListener('DOMContentLoaded', () => {
            const notif = document.getElementById('notif');
            const notifMsg = document.getElementById('notifMsg');
            const notifClose = document.getElementById('notifClose');

            // function to show notification
            function showNotification(message, type = 'success') {
                if (!notif || !notifMsg) return;
                notifMsg.textContent = message;

                // color scheme
                const colors = {
                    success: '#28a745',
                    error: '#dc3545',
                    info: '#0d6efd',
                    warning: '#ffc107'
                };
                notif.style.background = colors[type] || '#0d6efd';

                notif.style.display = 'block';
                setTimeout(() => { notif.style.opacity = 1; }, 10);

                // auto hide after 4 seconds
                setTimeout(() => {
                    notif.style.opacity = 0;
                    setTimeout(() => { notif.style.display = 'none'; }, 300);
                }, 4000);
            }

            // close button
            notifClose?.addEventListener('click', () => {
                notif.style.opacity = 0;
                setTimeout(() => { notif.style.display = 'none'; }, 300);
            });

            // optional trigger after form submit actions
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status_update') === 'approved') {
                showNotification('Permit approved successfully', 'success');
            } else if (urlParams.get('status_update') === 'rejected') {
                showNotification('Permit rejected successfully', 'error');
            }

            // modify your POST redirect in PHP like this:
            // header("Location: " . $_SERVER['PHP_SELF'] . "?status_update=approved");
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
