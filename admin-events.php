    <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    // Ensure DB connection is available
    require 'php/connection.php'; // Make sure this file sets up $conn

    // Ensure session is started before accessing `$_SESSION`
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // If not logged in, redirect to login page
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }

    // Now safe to read user id from session and load basic user info for header
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

    $sql = "SELECT `id`, `title`, `type`, `date`, `start_time`, `end_time`, `location`, `description`, `image_path`, `status`, `created_at` 
            FROM `events` 
            ORDER BY `date` ASC";

    $result = $conn->query($sql);
    $events = [];

    $today = date('Y-m-d'); // current date

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row['status'] === 'cancelled') {
                $row['dynamic_status'] = 'cancelled';
            } elseif ($row['date'] > $today) {
                $row['dynamic_status'] = 'upcoming';
            } elseif ($row['date'] == $today) {
                $row['dynamic_status'] = 'ongoing';
            } else {
                $row['dynamic_status'] = 'completed';
            }

            $events[] = $row;
        }
    }
    // load settings
    $settings = [];
    $settingsQuery = "SELECT name, value FROM settings";
    if ($settingsResult = $conn->query($settingsQuery)) {
        while ($s = $settingsResult->fetch_assoc()) {
            $settings[$s['name']] = $s['value'];
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Management - Tanza Public Market</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/admin-dashboard.css">
    <link rel="stylesheet" href="CSS/notification-management.css">
    <link rel="stylesheet" href="CSS/ss.css">
    <link rel="stylesheet" href="CSS/admin-mobile-view.css">
    <script src="JS/disable-console-logs.js"></script>
    <script src="JS/disable-all-notifications.js"></script>
    <script src="JS/disable-login-requirements.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
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
                 <!-- <nav class="sidebar-nav">
                                    <ul class="nav-list">
                        <li class="nav-item"><a href="admin-dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                        <li class="nav-item"><a href="admin-vendor-management.php" class="nav-link"><i class="fas fa-users"></i><span>Vendor Management</span></a></li>
                        <li class="nav-item"><a href="admin-survey-form.php" class="nav-link"><i class="fas fa-clipboard-list"></i><span>Submit Survey Form</span></a></li>
                        <li class="nav-item"><a href="admin-report-management.php" class="nav-link"><i class="fas fa-exclamation-triangle"></i><span>Report Management</span></a></li>
                        <li class="nav-item"><a href="admin-cleaning-management.php" class="nav-link"><i class="fas fa-broom"></i><span>Cleaning Management</span></a></li>
                       <li class="nav-item"><a href="admin-vendor-requests.php" class="nav-link"><i class="fas fa-file-alt"></i><span>VENDOR REQUESTS</span></a></li> -->
                        <!-- <li class="nav-item"><a href="admin-stall-management.php" class="nav-link"><i class="fas fa-store"></i><span>STALL MANAGEMENT</span></a></li> -->
                        <!--<li class="nav-item"><a href="admin-business-permit.php" class="nav-link"><i class="fas fa-certificate"></i><span>Business Permit</span></a></li>
                        <li class="nav-item active"><a href="admin-events.php" class="nav-link"><i class="fas fa-calendar-alt"></i><span>Events</span></a></li>
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

        <main class="main-content">
                <header class="top-navbar">
                    <!-- <div class="top-nav-left">
                    <button class="btn btn-outline-secondary btn-sm" id="mobileSidebarTrigger" aria-label="Open sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title d-inline ms-2">Events Management</h1>
                    </div> -->
                    <div class="date-time">
                         <span id="currentDate"> <?= date('F d, Y') ?></span>
                     </div>
                </header>

                <div class="content-wrapper">
                    <section class="content-section active" id="events-section">
                    <div class="section-header mb-3">
                        <h2>Events Management</h2>
                        <p>Create and manage market events, workshops, and activities.</p>
                    </div>

                    <div class="event-management-controls d-flex justify-content-between align-items-center mb-3">
                        <div class="filter-options">
                        <select id="eventTypeFilter" class="form-select d-inline-block w-auto">
                            <option value="all">All Types</option>
                            <option value="face-to-face">Face-to-Face</option>
                            <option value="online">Online/Webinar</option>
                        </select>
                        <select id="eventStatusFilter" class="form-select d-inline-block w-auto">
                            <option value="all">All Status</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        </div>

                        <div class="event-actions-global">
                        <!-- Trigger modal -->
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEventModal">
                            <i class="fas fa-plus"></i> Add New Event
                        </button>
                        <button id="exportEventsBtn" class="btn btn-secondary btn-sm">
                            <i class="fas fa-file-export"></i> Export CSV
                        </button>
                        </div>
                    </div>

                <div class="row g-4">
                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $row): ?>
                            <?php
                                // determine type badge bootstrap class
                                $typeBadge = ($row['type'] === 'face-to-face') ? 'bg-success text-white' : 'bg-primary text-white';
                                // determine status badge bootstrap class
                                switch ($row['dynamic_status']) {
                                    case 'upcoming': $statusBadge = 'bg-info text-white'; break;
                                    case 'ongoing': $statusBadge = 'bg-success text-white'; break;
                                    case 'completed': $statusBadge = 'bg-secondary text-white'; break;
                                    case 'cancelled': $statusBadge = 'bg-danger text-white'; break;
                                    default: $statusBadge = 'bg-info text-white';
                                }
                                $eventDate = date("F j, Y", strtotime($row['date']));
                                $startTime = date("g:i A", strtotime($row['start_time']));
                                $endTime = date("g:i A", strtotime($row['end_time']));
                                $img = !empty($row['image_path']) ? $row['image_path'] : 'IMG/event-placeholder.png';
                            ?>

                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="card h-100 event-card" data-event-id="<?= htmlspecialchars($row['id']) ?>" data-type="<?= htmlspecialchars($row['type']) ?>" data-status="<?= htmlspecialchars($row['dynamic_status']) ?>">
                                    <img src="<?= htmlspecialchars($img) ?>" class="card-img-top" alt="<?= htmlspecialchars($row['title']) ?>">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-start">
                                                <span class="badge <?= $typeBadge ?>"><?= ucfirst($row['type']) ?></span>
                                                <h5 class="card-title mb-0 ms-2" style="max-width:65%; overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;"><?= htmlspecialchars($row['title']) ?></h5>
                                            </div>
                                            <span class="badge <?= $statusBadge ?>"><?= ucfirst($row['dynamic_status']) ?></span>
                                        </div>

                                        <p class="card-text text-muted small mb-2">
                                            <i class="fas fa-calendar-alt"></i> <?= $eventDate ?>
                                            <br>
                                            <i class="fas fa-clock"></i> <?= $startTime ?> - <?= $endTime ?>
                                            <br>
                                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($row['location']) ?>
                                        </p>

                                        <p class="card-text text-muted mb-3" style="overflow:hidden; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical;"><?= htmlspecialchars($row['description']) ?></p>

                                        <div class="mt-auto d-flex gap-2">
                                            <button 
                                                class="btn btn-primary btn-sm"
                                                onclick="openEditModal(
                                                    <?= $row['id'] ?>,
                                                    '<?= htmlspecialchars(addslashes($row['title'])) ?>',
                                                    '<?= $row['type'] ?>',
                                                    '<?= $row['date'] ?>',
                                                    '<?= $row['start_time'] ?>',
                                                    '<?= $row['end_time'] ?>',
                                                    '<?= htmlspecialchars(addslashes($row['location'])) ?>',
                                                    '<?= htmlspecialchars(addslashes($row['description'])) ?>'
                                                )">
                                                Edit Event
                                            </button>

                                            <button class="btn btn-secondary btn-sm">View Registrations</button>

                                            <button class="btn btn-<?= ($row['dynamic_status'] === 'cancelled') ? 'success' : 'danger' ?> btn-sm"
                                                onclick="toggleEventStatus(<?= $row['id'] ?>, '<?= $row['dynamic_status'] ?>')">
                                                <?= ($row['dynamic_status'] === 'cancelled') ? 'Re-activate' : 'Cancel Event' ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <p class="text-muted">No events found.</p>
                        </div>
                    <?php endif; ?>
                </div>

                    </section>
                </div>
                </main>

                <!-- Add Event Modal -->
                <!-- Add Event Modal -->
                <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form method="POST" action="php/save_event.php" enctype="multipart/form-data">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addEventModalLabel">Add New Event</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                    <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Event Title</label>
                                        <input type="text" class="form-control" name="title" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Event Type</label>
                                        <select class="form-select" name="type" required>
                                        <option value="face-to-face">Face-to-Face</option>
                                        <option value="online">Online/Webinar</option>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Date</label>
                                                <input type="date" id="addEventDate" class="form-control" name="date" required min="<?= date('Y-m-d') ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Start Time</label>
                                            <input type="time" class="form-control" name="start_time" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">End Time</label>
                                            <input type="time" class="form-control" name="end_time" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Location / Link</label>
                                        <input type="text" class="form-control" name="location" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Event Image (optional)</label>
                                        <input type="file" name="image" class="form-control" accept="image/*">
                                    </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Save Event</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Edit Event Modal -->
                <div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form method="POST" action="php/edit_event.php" enctype="multipart/form-data">
                                <input type="hidden" name="id" id="editEventId">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                    <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Event Title</label>
                                        <input type="text" class="form-control" name="title" id="editEventTitle" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Event Type</label>
                                        <select class="form-select" name="type" id="editEventType" required>
                                            <option value="face-to-face">Face-to-Face</option>
                                            <option value="online">Online/Webinar</option>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Date</label>
                                            <input type="date" class="form-control" name="date" id="editEventDate" required min="<?= date('Y-m-d') ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Start Time</label>
                                            <input type="time" class="form-control" name="start_time" id="editEventStartTime" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">End Time</label>
                                            <input type="time" class="form-control" name="end_time" id="editEventEndTime" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Location / Link</label>
                                        <input type="text" class="form-control" name="location" id="editEventLocation" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" id="editEventDescription" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Event Image (optional - upload to replace)</label>
                                        <input type="file" name="image" class="form-control" accept="image/*">
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Update Event</button>
                                </div>
                            </form>
                        </div>
                    </div>
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

            <!-- Confirm Toggle Modal -->
            <div class="modal fade" id="toggleEventModal" tabindex="-1" aria-labelledby="toggleEventModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="toggleEventModalLabel">Confirm Action</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="toggleEventModalBody">
                            <!-- Dynamic message will go here -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmToggleBtn">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- View Registrations Modal -->
            <div class="modal fade" id="viewRegistrationsModal" tabindex="-1" aria-labelledby="viewRegistrationsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewRegistrationsModalLabel">Event Registrations</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="registrationsTableContainer" class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Full Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Business</th>
                                            <th>Stall</th>
                                            <th>Registered At</th>
                                        </tr>
                                    </thead>
                                    <tbody id="registrationsTableBody">
                                    <tr><td colspan="7" class="text-center">Select an event to view registrations.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <nav>
                            <ul class="pagination justify-content-center" id="pagination"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

        <script>
        // ---------- Notification Functions ----------
        function showNotif(message, type = 'success') {
            const notif = document.getElementById('notif');
            const notifMsg = document.getElementById('notifMsg');
            const closeBtn = document.getElementById('notifClose');

            notifMsg.textContent = message;
            notif.style.backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';

            notif.style.display = 'block';
            setTimeout(() => notif.style.opacity = '1', 10);

            // Automatically hide after 3 seconds
            const timer = setTimeout(() => hideNotif(true), 3000);

            // Manual close
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
            }, 300);
        }

        // ---------- Event Cancel / Re-activate ----------
        let currentEventId = null;

        function toggleEventStatus(eventId, currentStatus) {
            currentEventId = eventId;
            const modalBody = document.getElementById('toggleEventModalBody');
            modalBody.textContent = currentStatus === 'cancelled' ? 
                                    "Are you sure you want to re-activate this event?" : 
                                    "Are you sure you want to cancel this event?";

            const toggleModal = new bootstrap.Modal(document.getElementById('toggleEventModal'));
            toggleModal.show();
        }

        document.getElementById('confirmToggleBtn').addEventListener('click', function() {
            if (!currentEventId) return;

            // Redirect to PHP to handle toggle (Cancel / Re-activate)
            window.location.href = `php/cancel_event.php?id=${currentEventId}`;

            // Close modal
            const toggleModalEl = document.getElementById('toggleEventModal');
            const toggleModal = bootstrap.Modal.getInstance(toggleModalEl);
            toggleModal.hide();
        });

        // ---------- Display Notifications Based on URL ----------
        const params = new URLSearchParams(window.location.search);
        if (params.get('status') === 'success') {
            let msg = 'Action completed successfully';
            const action = params.get('action');
            if (action === 'cancel') msg = 'Event cancelled successfully';
            if (action === 'restore') msg = 'Event re-activated successfully';
            if (action === 'add') msg = 'Event added successfully';
            if (action === 'edit') msg = 'Event updated successfully';
            showNotif(msg, 'success');
        }

        if (params.get('error')) showNotif(params.get('error'), 'error');


        function openEditModal(id, title, type, date, start_time, end_time, location, description) {
            document.getElementById('editEventId').value = id;
            document.getElementById('editEventTitle').value = title;
            document.getElementById('editEventType').value = type;
            document.getElementById('editEventDate').value = date;
            document.getElementById('editEventStartTime').value = start_time;
            document.getElementById('editEventEndTime').value = end_time;
            document.getElementById('editEventLocation').value = location;
            document.getElementById('editEventDescription').value = description;

            const editModal = new bootstrap.Modal(document.getElementById('editEventModal'));
            editModal.show();
        }


        async function loadRegistrations(eventId, page = 1) {
            const res = await fetch(`php/fetch_registrations.php?event_id=${eventId}&page=${page}`);
            const data = await res.json();

            const tbody = document.getElementById('registrationsTableBody');
            const pagination = document.getElementById('pagination');

            tbody.innerHTML = '';
            pagination.innerHTML = '';

            if (data.status !== 'success' || data.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center">No registrations found.</td></tr>`;
                return;
            }

            data.data.forEach((reg, index) => {
                const fullName = [reg.first_name, reg.middle_name, reg.last_name].filter(Boolean).join(' ');
                tbody.innerHTML += `
                    <tr>
                        <td>${index + 1 + (data.current_page - 1) * 5}</td>
                        <td>${fullName}</td>
                        <td>${reg.email}</td>
                        <td>${reg.phone_number ?? ''}</td>
                        <td>${reg.business_name ?? ''}</td>
                        <td>${reg.stall_number ?? ''}</td>
                        <td>${new Date(reg.registered_at).toLocaleString()}</td>
                    </tr>
                `;
            });

            // pagination
            for (let i = 1; i <= data.total_pages; i++) {
                pagination.innerHTML += `
                    <li class="page-item ${i === data.current_page ? 'active' : ''}">
                        <a href="#" class="page-link" onclick="loadRegistrations(${eventId}, ${i})">${i}</a>
                    </li>`;
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".btn-secondary").forEach(btn => {
                if (btn.textContent.trim() === "View Registrations") {
                    btn.addEventListener("click", (e) => {
                        const eventCard = btn.closest(".event-card");
                        const eventId = eventCard ? eventCard.dataset.eventId : null;
                        if (!eventId) return;
                        loadRegistrations(eventId);
                        const modal = new bootstrap.Modal(document.getElementById("viewRegistrationsModal"));
                        modal.show();
                    });
                }
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

            // ---------- Event Filters (type + status) ----------
            const typeFilter = document.getElementById('eventTypeFilter');
            const statusFilter = document.getElementById('eventStatusFilter');

            function filterEvents() {
                const typeVal = typeFilter ? typeFilter.value : 'all';
                const statusVal = statusFilter ? statusFilter.value : 'all';
                const cards = document.querySelectorAll('.event-card');

                cards.forEach(card => {
                    const cardType = (card.getAttribute('data-type') || '').toLowerCase();
                    const cardStatus = (card.getAttribute('data-status') || '').toLowerCase();

                    const typeMatch = (typeVal === 'all') || (cardType === typeVal.toLowerCase());
                    const statusMatch = (statusVal === 'all') || (cardStatus === statusVal.toLowerCase());

                    if (typeMatch && statusMatch) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            if (typeFilter) typeFilter.addEventListener('change', filterEvents);
            if (statusFilter) statusFilter.addEventListener('change', filterEvents);

            // Run once on load to apply initial filter state
            filterEvents();

            // Export events CSV
            const exportEventsBtn = document.getElementById('exportEventsBtn');
            if (exportEventsBtn) {
                exportEventsBtn.addEventListener('click', () => {
                    const type = document.getElementById('eventTypeFilter')?.value || '';
                    const status = document.getElementById('eventStatusFilter')?.value || '';
                    const params = new URLSearchParams();
                    if (type && type !== 'all') params.set('type', type);
                    if (status && status !== 'all') params.set('status', status);
                    const url = 'php/export_events_csv.php' + (params.toString() ? ('?' + params.toString()) : '');
                    window.location.href = url;
                });
            }

            // Ensure add/edit date inputs have min set (in case browser requires runtime enforcement)
            try {
                const todayStr = new Date().toISOString().slice(0,10);
                const addDate = document.getElementById('addEventDate');
                const editDate = document.getElementById('editEventDate');
                if (addDate) addDate.setAttribute('min', todayStr);
                if (editDate) editDate.setAttribute('min', todayStr);
            } catch (e) { /* ignore */ }
        });
    </script>


    </body>
    </html>
