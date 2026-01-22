<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    session_start();
    require_once 'php/connection.php';
    require 'php/vendor_init.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }

    $userId = $_SESSION['user_id'];

    // get user profile
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
    $stmt->close();

    // get registered events
    $registeredEvents = [];
    $stmtReg = $conn->prepare("SELECT event_id FROM event_registrations WHERE user_id = ?");
    $stmtReg->bind_param("i", $userId);
    $stmtReg->execute();
    $resultReg = $stmtReg->get_result();
    while ($r = $resultReg->fetch_assoc()) {
        $registeredEvents[] = $r['event_id'];
    }
    $stmtReg->close();

    // Pagination settings
    $eventsPerPage = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $eventsPerPage;

    // Get total number of events
    $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM events");
    $stmtCount->execute();
    $resultCount = $stmtCount->get_result();
    $totalEvents = $resultCount->fetch_assoc()['total'];
    $stmtCount->close();

    $totalPages = ceil($totalEvents / $eventsPerPage);

    // Fetch events for current page
    $events = [];
    $stmtEvents = $conn->prepare("
           SELECT id, title, type, date, start_time, end_time, location, description, image_path, status, created_at
        FROM events
        ORDER BY date ASC
        LIMIT ? OFFSET ?
    ");
    $stmtEvents->bind_param("ii", $eventsPerPage, $offset);
    $stmtEvents->execute();
    $resultEvents = $stmtEvents->get_result();
    while ($row = $resultEvents->fetch_assoc()) {
        $events[] = $row;
    }
    $stmtEvents->close();

    $stmt = $conn->prepare("SELECT verification_status FROM business_permits WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $permit = $result->fetch_assoc();
    $stmt->close();

    $isVerified = ($permit && isset($permit['verification_status']) && strtolower($permit['verification_status']) === 'approved');

    if (!$isVerified) {
        // redirect or show error if not approved
        header("Location: vendor-dashboard.php?error=permit_not_verified");
        exit;
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
    <title>Market Events - Tanza Public Market</title>
    <link rel="stylesheet" href="CSS/minimalist-responsive.css">
    <link rel="stylesheet" href="CSS/vendor-dashboard.css?v=2">
    <link rel="stylesheet" href="CSS/survey-form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

        <style>
            .event-image img {
                width: 100%;
                height: 200px;
                object-fit: cover;
                border-radius: 8px;
                margin-bottom: 10px;
            }
        </style>

    <script src="JS/disable-console-logs.js"></script>
    <script src="JS/disable-all-notifications.js"></script>
    <script src="JS/disable-login-requirements.js"></script>
    <!-- Shared JS for all pages -->
    <script src="JS/vendor-dashboard-shared.js" defer></script>
</head>
<style>
    .disabled-link {
    pointer-events: none;
    opacity: 0.5;
    cursor: not-allowed;
}
    .notification-container {
    position: relative;
    cursor: pointer;
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
    <style>
        /* Product-like events grid */
        .events-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
            padding: 12px 0;
        }

        .event-card {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            display: flex;
            flex-direction: column;
            min-height: 360px;
        }

        .event-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 36px rgba(0,0,0,0.12);
        }

        .event-image img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
        }

        .event-header {
            padding: 12px 14px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }

        .event-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 600;
            line-height: 1.2;
            max-height: 2.6rem;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .event-details {
            padding: 8px 14px 14px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1 1 auto;
        }

        .event-info {
            font-size: 0.95rem;
            color: #444;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .event-badge {
            display: inline-block;
            padding: 6px 8px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .event-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-top: 8px;
        }

        .event-action-btn {
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .event-action-link {
            margin-left: auto;
            color: #6c757d;
            text-decoration: none;
            font-size: 0.95rem;
        }

        @media (max-width: 767px) {
            .events-container { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
            .event-image img { height: 150px; }
        }
    </style>
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
                    <li class="nav-item active">
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
                        <a href="vendor-events.php" 
                        class="nav-link <?php echo !$isVerified ? 'disabled-link' : ''; ?>" 
                        <?php echo !$isVerified ? 'onclick="return false;"' : ''; ?>>
                            <i class="fas fa-calendar-alt"></i>
                            <span>EVENTS</span>
                        </a>
                    </li>
                    <li class="nav-item ">
                            <a href="vendor-notices.php" class="nav-link <?php echo !$isVerified ? 'disabled-link' : ''; ?>" <?php echo !$isVerified ? 'onclick="return false;"' : ''; ?>>
                                <i class="fas fa-bullhorn"></i>
                                <span>NOTICES</span>
                            </a>
                        </li>

                    <li class="nav-item">
                        <a href="vendor-survey-form.php" 
                        class="nav-link <?php echo !$isVerified ? 'disabled-link' : ''; ?>" 
                        <?php echo !$isVerified ? 'onclick="return false;"' : ''; ?>>
                            <i class="fas fa-clipboard-list"></i>
                            <span>SUBMIT SURVEY RESPONSE</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="vendor-cleaning.php" 
                        class="nav-link <?php echo !$isVerified ? 'disabled-link' : ''; ?>" 
                        <?php echo !$isVerified ? 'onclick="return false;"' : ''; ?>>
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
                        <h2>Market Events</h2>
                        <p>Upcoming events and activities at Tanza Public Market.</p>
                    </div>
                    <div class="section-content">
                        <!-- Events Categories Tabs -->
                        <div class="events-tabs">
                            <button class="event-tab active" data-tab="all">All Events</button>
                            <button class="event-tab" data-tab="face-to-face">Face-to-Face</button>
                            <button class="event-tab" data-tab="online">Online/Webinar</button>
                        </div>

                        <div class="events-container">
                            <?php foreach ($events as $event): ?>

                                <?php $img = !empty($event['image_path']) ? $event['image_path'] : 'IMG/event-placeholder.png'; ?>

                                <div class="event-card <?php echo ($event['type'] == 'online' ? 'online' : 'face-to-face'); ?>" data-event-id="<?php echo $event['id']; ?>">
                                    <div class="event-image">
                                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                    </div>
                                    <div class="event-header">
                                        <span class="event-badge <?php echo ($event['type'] == 'online' ? 'online' : 'face-to-face'); ?>">
                                            <?php echo ucfirst($event['type']); ?>
                                        </span>
                                        <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                    </div>
                                    <div class="event-details">
                                        <div class="event-info">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span><?php echo date("F j, Y", strtotime($event['date'])) . " | " . date("g:i A", strtotime($event['start_time'])) . " - " . date("g:i A", strtotime($event['end_time'])); ?></span>
                                        </div>
                                        <div class="event-info">
                                            <i class="fas <?php echo ($event['type'] == 'online' ? 'fa-link' : 'fa-map-marker-alt'); ?>"></i>
                                            <span>
                                                <?php echo ($event['type'] == 'online') ? "<a href='" . htmlspecialchars($event['location']) . "' target='_blank'>" . htmlspecialchars($event['location']) . "</a>" : htmlspecialchars($event['location']); ?>
                                            </span>
                                        </div>
                                        <div class="event-info">
                                            <i class="fas fa-info-circle"></i>
                                            <span><?php echo htmlspecialchars($event['description']); ?></span>
                                        </div>
                                        <div class="event-info">
                                            <i class="fas fa-info"></i>
                                            <span>Status: <?php echo htmlspecialchars(ucfirst($event['status'])); ?></span>
                                        </div>
                                        <div class="event-actions">
                                            <?php if (strtolower($event['status']) === 'cancelled'): ?>
                                                <button class="event-action-btn btn btn-danger" disabled>Event Cancelled</button>
                                            <?php elseif (in_array($event['id'], $registeredEvents)): ?>
                                                <button class="event-action-btn btn btn-secondary" disabled>Registered</button>
                                            <?php else: ?>
                                                <a href="#register" class="event-action-btn btn btn-success">Register</a>
                                            <?php endif; ?>

                                            <a href="#details" class="event-action-link">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                           <div class="events-pagination">
                                <?php if($totalPages > 1): ?>
                                    <?php if($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>" class="pagination-btn prev">&laquo; Previous</a>
                                    <?php endif; ?>

                                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?page=<?php echo $i; ?>" class="pagination-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if($page < $totalPages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>" class="pagination-btn next">Next &raquo;</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                     
                    </div>
                
            </div>
        </main>
    </div>
    <!-- Registration Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="eventRegistrationForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerModalLabel">Event Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="event_id" name="event_id">

                    <div class="mb-3"><label>First Name</label><input type="text" class="form-control" name="first_name" required></div>
                    <div class="mb-3"><label>Middle Name</label><input type="text" class="form-control" name="middle_name"></div>
                    <div class="mb-3"><label>Last Name</label><input type="text" class="form-control" name="last_name" required></div>
                    <div class="mb-3"><label>Email</label><input type="email" class="form-control" name="email" required></div>
                    <div class="mb-3"><label>Phone Number</label><input type="text" class="form-control" name="phone"></div>
                    <div class="mb-3"><label>Business Name</label><input type="text" class="form-control" name="business"></div>
                    <div class="mb-3"><label>Stall Number</label><input type="text" class="form-control" name="stall"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Register</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventDetailsModalLabel">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Title:</strong> <span id="detailTitle"></span></p>
                    <p><strong>Type:</strong> <span id="detailType"></span></p>
                    <p><strong>Date:</strong> <span id="detailDate"></span></p>
                    <p><strong>Time:</strong> <span id="detailTime"></span></p>
                    <p><strong>Location:</strong> <span id="detailLocation"></span></p>
                    <p><strong>Description:</strong> <span id="detailDescription"></span></p>
                    <p><strong>Status:</strong> <span id="detailStatus"></span></p>
                </div>
            </div>
        </div>
    </div>

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
    <!-- *** PAGE SPECIFIC SCRIPTS GO HERE (if any) *** -->
 
    <script src="../JS/events-functions.js"></script>
    <script src="../JS/notification.js"></script>
    <script>
        document.getElementById("logoutBtn").addEventListener("click", function(e) {
            e.preventDefault();
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "php/logout.php";
            }
        });
    </script>
    
</body>
</html>
