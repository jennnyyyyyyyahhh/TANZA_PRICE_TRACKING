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
$stmt = $conn->prepare("SELECT role FROM admin WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if (!$userData || $userData['role'] !== 'admin') {
    header("Location: /login.php");
    exit;
}

// 1. Fetch all media
$media = [];
$q1 = "SELECT * FROM media ORDER BY id DESC";
$r1 = $conn->query($q1);

if ($r1) {
    while ($row = $r1->fetch_assoc()) {
        $media[] = $row;
    }
}

// 2. Initialize variable first (to avoid warning)
$used_media_id = 0;

// 3. Fetch current used media ID
$q2 = "SELECT value FROM settings WHERE name='landing_feature_media'";
$r2 = $conn->query($q2);

if ($r2 && $row2 = $r2->fetch_assoc()) {
    $used_media_id = intval($row2['value']);
}
?>

<?php
// Load settings key/value pairs
$settings = [];
$res = $conn->query("SHOW TABLES LIKE 'settings'");
if ($res && $res->num_rows) {
    $rs = $conn->query("SELECT `name`,`value` FROM settings");
    if ($rs) {
        while ($row = $rs->fetch_assoc()) {
            $settings[$row['name']] = $row['value'];
        }
        $rs->free();
    }
}

// Load barangays
$barangays = [];
$res = $conn->query("SHOW TABLES LIKE 'barangays'");
if ($res && $res->num_rows) {
    $rs = $conn->query("SELECT * FROM barangays ORDER BY created_at DESC");
    if ($rs) {
        while ($row = $rs->fetch_assoc()) {
            $barangays[] = $row;
        }
        $rs->free();
    }
}

// Load media
$media = [];
$res = $conn->query("SHOW TABLES LIKE 'media'");
if ($res && $res->num_rows) {
    $rs = $conn->query("SELECT * FROM media ORDER BY created_at DESC");
    if ($rs) {
        while ($row = $rs->fetch_assoc()) {
            $media[] = $row;
        }
        $rs->free();
    }
}

// If media table is empty, fallback to scanning uploads directories for media files
if (empty($media)) {
    $scanDirs = [__DIR__ . '/../uploads/media', __DIR__ . '/../uploads'];
    $allowedExt = ['jpg','jpeg','png','gif','webp','mp4','webm','mov','ogg'];
    foreach ($scanDirs as $d) {
        if (!is_dir($d)) continue;
        $files = scandir($d);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $full = $d . '/' . $f;
            if (!is_file($full)) continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) continue;
            $m = mime_content_type($full);
            $media[] = [
                'id' => 0,
                'filename' => basename($f),
                'original_name' => basename($f),
                'mime' => $m,
                'size' => filesize($full),
            ];
        }
        // if we found files in a directory, prefer that directory's files
        if (!empty($media)) break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presyong Tanza Settings - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/ss.css">
    <link rel="stylesheet" href="CSS/admin-mobile-view.css">
    <style>
        /* Fixed header and sidebar for admin settings layout */
        header.settings-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            z-index: 1000;
        }

        /* sidebar fixed beneath header */
        aside.settings-sidebar {
            position: fixed;
            top: 70px;
            left: 0;
            width: 250px;
            height: calc(100vh - 70px);
            overflow: auto;
            z-index: 900;
        }

        /* main content moves right and below header */
        main.settings-main {
            margin-left: 250px;
            margin-top: 70px;
            padding: 40px;
        }

        /* make sure mobile / small screens still work reasonably */
        @media (max-width: 800px) {
            aside.settings-sidebar { 
                position: relative; 
                width: 100%; 
                height: auto; 
                top: 0;
                padding: 15px !important;
            }
            main.settings-main { 
                margin-left: 0; 
                margin-top: 0; 
                padding: 15px;
            }
            header.settings-header { 
                position: relative; 
                height: auto;
                padding: 12px 15px !important;
            }
            
            /* Stack sidebar nav items */
            aside.settings-sidebar nav ul {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            
            aside.settings-sidebar nav ul li {
                margin-bottom: 0 !important;
                flex: 1 1 calc(50% - 4px);
                min-width: 140px;
            }
            
            aside.settings-sidebar nav ul li a {
                padding: 10px !important;
                font-size: 0.85rem;
                text-align: center;
            }
            
            /* Form sections */
            section {
                padding: 20px !important;
            }
            
            section h2 {
                font-size: 1.2rem !important;
            }
            
            section input,
            section textarea,
            section select {
                font-size: 16px !important; /* Prevents zoom on iOS */
            }
            
            section button[type="submit"] {
                width: 100%;
                padding: 14px !important;
            }
            
            /* Media grid */
            .media-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 10px !important;
            }
            
            /* Barangay table */
            .barangay-table {
                display: block;
                overflow-x: auto;
            }
            
            /* Modal */
            #barangayModal > div {
                width: 95% !important;
                margin: 10px;
            }
        }
        
        @media (max-width: 480px) {
            aside.settings-sidebar nav ul li {
                flex: 1 1 100%;
            }
            
            .media-grid {
                grid-template-columns: 1fr !important;
            }
        }
        
        /* Active sidebar link */
        aside nav a.active {
            background-color: rgba(0,0,0,0.08) !important;
            box-shadow: inset 0 0 0 2px rgba(39,174,96,0.12);
            font-weight: 700;
        }
        
        /* Settings Modal Overlay - protect from mobile CSS overrides */
        .settings-modal-overlay {
            display: none !important;
            position: fixed !important;
            inset: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0,0,0,0.5) !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 99999 !important;
            flex-direction: row !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .settings-modal-overlay[style*="display: flex"],
        .settings-modal-overlay[style*="display:flex"] {
            display: flex !important;
        }
        
        .settings-modal-content {
            display: block !important;
            width: 420px !important;
            max-width: 95% !important;
            margin: auto !important;
            flex-direction: column !important;
        }
        
        /* Floating sidebar toggle button */
        .settings-floating-btn {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(39,174,96,0.4);
            cursor: pointer;
            z-index: 99998;
            font-size: 24px;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .settings-floating-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(39,174,96,0.5);
        }
        
        .settings-floating-btn:active {
            transform: scale(0.95);
        }
        
        /* Sidebar overlay for mobile */
        .settings-sidebar-overlay {
            display: none !important;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.3);
            z-index: 899;
            pointer-events: none;
        }
        
        .settings-sidebar-overlay.active {
            display: block !important;
            pointer-events: auto;
        }
        
        @media (max-width: 800px) {
            .settings-floating-btn {
                display: flex;
            }
            
            /* Hide sidebar by default on mobile */
            aside.settings-sidebar {
                position: fixed !important;
                top: 0 !important;
                left: -100% !important;
                height: 100vh !important;
                width: 280px !important;
                max-width: 80vw !important;
                z-index: 10100 !important;
                overflow-y: auto !important;
                padding: 20px !important;
                transition: left 0.3s ease !important;
            }
            
            /* Show sidebar when active */
            aside.settings-sidebar.active {
                left: 0 !important;
            }
        }
    </style>
</head>
<body style="margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f9f4;">

<!-- Sidebar Overlay -->
<div class="settings-sidebar-overlay" id="settingsSidebarOverlay"></div>

<!-- Floating Sidebar Toggle Button -->
<button class="settings-floating-btn" id="settingsFloatingBtn" aria-label="Toggle settings menu">
    <i class="fas fa-bars"></i>
</button>

<!-- Header with Back Button -->
<header class="settings-header" style="background: linear-gradient(135deg, #2d5016 0%, #27ae60 100%); color: white; padding: 15px 30px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <div style="display: flex; align-items: center; gap: 15px;">
        <a href="admin-dashboard.php" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 8px 16px; background-color: rgba(255,255,255,0.2); border-radius: 6px; transition: background-color 0.3s;">
            <i class="fas fa-arrow-left"></i>
            <span style="font-weight: 600;">Back to Dashboard</span>
        </a>
    </div>
</header>

<?php
// Display a short notification if redirected after CRUD actions
if (isset($_GET['saved']) || isset($_GET['success']) || isset($_GET['msg'])) {
    $msg = 'Saved successfully.';
    if (!empty($_GET['msg'])) {
        $msg = htmlspecialchars($_GET['msg']);
        // make friendlier phrases
        if ($msg === 'uploaded' && isset($_GET['count'])) {
            $msg = intval($_GET['count']) . ' file(s) uploaded successfully.';
        }
    } elseif (isset($_GET['success'])) {
        $msg = 'Action completed.';
    }

    echo '<div id="adminNotification" style="position:fixed; top:85px; left:50%; transform:translateX(-50%); z-index:2200; width:auto; max-width:90%;">';
    echo '<div style="background:#27ae60; color:white; padding:12px 16px; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.12); font-weight:600; text-align:center; white-space:nowrap;">' . $msg . ' <button onclick="document.getElementById(\'adminNotification\').style.display=\'none\'" style="margin-left:12px; background:transparent; border:none; color:rgba(255,255,255,0.9); font-weight:700; cursor:pointer;">✕</button></div>';
    echo '</div>';
}
?>
<script>
// Auto-hide notification after 3 seconds and provide smooth behavior
document.addEventListener('DOMContentLoaded', function(){
    var n = document.getElementById('adminNotification');
    if (n) {
        setTimeout(function(){
            n.style.transition = 'opacity 0.4s ease';
            n.style.opacity = '0';
            setTimeout(function(){ if(n && n.parentNode) n.parentNode.removeChild(n); }, 500);
        }, 3000);
    }

    // Sidebar active link highlighting and offset scroll
    var header = document.querySelector('header');
    var headerHeight = header ? header.offsetHeight : 70;
    var links = document.querySelectorAll('aside nav a');

    function setActiveByHash(hash) {
        links.forEach(function(a){
            if (a.getAttribute('href') === hash) a.classList.add('active'); else a.classList.remove('active');
        });
        if (!hash) return;
        var target = document.querySelector(hash);
        if (target) {
            var top = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 12;
            window.scrollTo({ top: top, behavior: 'smooth' });
        }
    }

    // On page load, prefer hash, otherwise try section query param
    var hash = window.location.hash;
    if (hash) setActiveByHash(hash);
    else {
        var params = new URLSearchParams(window.location.search);
        var section = params.get('section');
        var map = { 'hero':'#hero-section', 'media':'#media-gallery', 'contact':'#contact-info', 'general':'#site-settings', 'about':'#about', 'barangay':'#barangay-management' };
        if (section && map[section]) setActiveByHash(map[section]);
    }

    // Attach click handlers to sidebar links
    links.forEach(function(a){
        a.addEventListener('click', function(e){
            var href = a.getAttribute('href');
            if (href && href.startsWith('#')) {
                e.preventDefault();
                history.replaceState(null, '', href);
                setActiveByHash(href);
            }
        });
    });
});
</script>

    <div style="display: flex; min-height: calc(100vh - 70px);">
        <!-- Sidebar -->
        <aside class="settings-sidebar" style="width: 250px; background: linear-gradient(180deg, #1e4620 0%, #2d5016 100%); color: white; padding: 20px; box-shadow: 2px 0 5px rgba(0,0,0,0.1);">
            <h2 style="margin-bottom: 30px; font-size: 18px; color: #e8f5e9; border-bottom: 2px solid rgba(255,255,255,0.2); padding-bottom: 10px;">Admin Settings</h2>
            <nav>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 15px;">
                        <a href="#contact-info" style="color: white; text-decoration: none; display: block; padding: 12px; border-radius: 6px; background-color: #27ae60; transition: all 0.3s;">📧 Contact Settings</a>
                    </li>
                    <li style="margin-bottom: 15px;">
                        <a href="#hero-section" style="color: white; text-decoration: none; display: block; padding: 12px; border-radius: 6px; background-color: rgba(255,255,255,0.1); transition: all 0.3s;">🎯 Homepage Banner</a>
                    </li>
                    <li style="margin-bottom: 15px;">
                        <a href="#media-gallery" style="color: white; text-decoration: none; display: block; padding: 12px; border-radius: 6px; background-color: rgba(255,255,255,0.1); transition: all 0.3s;">🎬 Landing Page Media</a>
                    </li>
                    <li style="margin-bottom: 15px;">
                        <a href="#about" style="color: white; text-decoration: none; display: block; padding: 12px; border-radius: 6px; background-color: rgba(255,255,255,0.1); transition: all 0.3s;">ℹ️ About System</a>
                    </li>
                    <li style="margin-bottom: 15px;">
                        <a href="#site-settings" style="color: white; text-decoration: none; display: block; padding: 12px; border-radius: 6px; background-color: rgba(255,255,255,0.1); transition: all 0.3s;">⚙️ General Settings</a>
                    </li>
                    <li style="margin-bottom: 15px;">
                        <a href="#barangay-management" style="color: white; text-decoration: none; display: block; padding: 12px; border-radius: 6px; background-color: rgba(255,255,255,0.1); transition: all 0.3s;">🏘️ Barangay Management</a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="settings-main" style="flex: 1; padding: 40px; background-color: #f0f9f4;">
            <!-- Contact Information Section -->
            <section id="contact-info" style="background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(39,174,96,0.1); border-left: 4px solid #27ae60;">
                <h2 style="color: #2d5016; margin-bottom: 10px;">Contact Settings</h2>
                <p style="color: #5a7f5f; margin-bottom: 25px;">Configure Presyong Tanza contact information</p>
                <form action="php/save_settings.php" method="POST">
                    <input type="hidden" name="section" value="contact">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #2d5016;">Email Address:</label>
                        <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 2px solid #c8e6c9; border-radius: 6px; font-size: 14px; transition: border-color 0.3s;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #2d5016;">Phone Number:</label>
                        <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 2px solid #c8e6c9; border-radius: 6px; font-size: 14px; transition: border-color 0.3s;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #2d5016;">Location:</label>
                        <input type="text" name="contact_location" value="<?php echo htmlspecialchars($settings['contact_location'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 2px solid #c8e6c9; border-radius: 6px; font-size: 14px; transition: border-color 0.3s;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #2d5016;">Market Hours:</label>
                        <input type="text" name="contact_hours" value="<?php echo htmlspecialchars($settings['contact_hours'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 2px solid #c8e6c9; border-radius: 6px; font-size: 14px; transition: border-color 0.3s;">
                    </div>
                    <button type="submit" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 12px 30px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; box-shadow: 0 2px 4px rgba(39,174,96,0.3);">Save Changes</button>
                </form>
            </section>

            <!-- Hero Section -->
            <section id="hero-section" style="background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(39,174,96,0.1); border-left: 4px solid #27ae60;">
                <h2 style="color: #2d5016; margin-bottom: 10px;">Homepage Banner Settings</h2>
                <p style="color: #5a7f5f; margin-bottom: 25px;">Customize the main banner on Presyong Tanza homepage</p>
                <form action="php/save_settings.php" method="POST">
                    <input type="hidden" name="section" value="hero">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #2d5016;">Main Title:</label>
                        <input type="text" name="hero_title" value="<?php echo htmlspecialchars($settings['hero_title'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 2px solid #c8e6c9; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #2d5016;">Subtitle:</label>
                        <textarea name="hero_subtitle" rows="3" style="width: 100%; padding: 12px; border: 2px solid #c8e6c9; border-radius: 6px; font-size: 14px; resize: vertical;"><?php echo htmlspecialchars($settings['hero_subtitle'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 12px 30px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; box-shadow: 0 2px 4px rgba(39,174,96,0.3);">Update</button>
                </form>
            </section>

            <!-- Landing Page Media Gallery Section -->
            <section id="media-gallery" style="background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(39,174,96,0.1); border-left: 4px solid #27ae60;">
                <h2 style="color: #2d5016; margin-bottom: 10px;">Landing Page Media Display</h2>
                <p style="color: #5a7f5f; margin-bottom: 25px;">Upload and manage pictures or videos displayed on the landing page</p>
                
                <!-- Upload Section -->
                <div style="border: 2px dashed #81c784; padding: 40px; border-radius: 8px; text-align: center; margin-bottom: 30px; background-color: #f1f8f4;">
                    <div style="margin-bottom: 20px;">
                        <svg style="width: 64px; height: 64px; color: #7f8c8d; margin-bottom: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <h3 style="color: #2c3e50; margin-bottom: 10px;">Upload Media Files</h3>
                        <p style="color: #7f8c8d; margin-bottom: 20px;">Drag and drop or click to upload images or videos</p>
                    </div>
                    <form id="mediaForm" action="php/save_settings.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="section" value="media">
                        <input type="file" id="mediaUpload" name="media_files[]" accept="image/*,video/*" multiple style="display: none;">
                        <label for="mediaUpload" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 12px 30px; border-radius: 6px; cursor: pointer; display: inline-block; font-weight: 600; box-shadow: 0 2px 4px rgba(39,174,96,0.3);">Choose Files</label>
                        <p style="color: #5a7f5f; font-size: 12px; margin-top: 15px;">Supported formats: JPG, PNG, GIF, MP4, WEBM (Max 50MB)</p>
                    </form>
                </div>

                <!-- Media Gallery Grid -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="color: #2d5016; margin: 0;">Current Media Gallery</h3>
                        <button id="mediaUploadTrigger" type="button" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; box-shadow: 0 2px 4px rgba(39,174,96,0.3);">Upload</button>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                        <?php if (!empty($media)): ?>
                            <?php foreach ($media as $m): ?>
                                <div style="border: 2px solid #c8e6c9; border-radius: 8px; overflow: hidden; background: white; transition: transform 0.3s;">
                                    <div style="position: relative; width: 100%; height: 180px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                        <?php $path = 'uploads/media/' . htmlspecialchars($m['filename']); ?>
                                        <?php if (strpos($m['mime'], 'image') !== false): ?>
                                            <img src="<?php echo $path; ?>" alt="Landing page image" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <video src="<?php echo $path; ?>" style="width: 100%; height: 100%; object-fit: cover;" controls></video>
                                        <?php endif; ?>
                                    </div>
                                    <div style="padding: 15px;">
                                        <p style="color: #2d5016; font-weight: 600; margin-bottom: 8px; font-size: 14px;"><?php echo htmlspecialchars($m['original_name'] ?: $m['filename']); ?></p>
                                        <p style="color: #5a7f5f; font-size: 12px; margin-bottom: 12px;"><?php echo round(($m['size'] ?? 0) / 1024 / 1024, 2); ?> MB</p>
                                        <div style="display: flex; gap: 8px;">
                                            <form method="POST" action="php/delete_media.php" style="flex:1; margin:0;">
                                                <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                                                <?php if (empty($m['id'])): ?>
                                                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($m['filename']); ?>">
                                                <?php endif; ?>
                                                <button type="submit" style="width:100%; background-color: #e74c3c; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">Delete</button>
                                            </form>

                                            <?php $is_used = ($m['id'] == $used_media_id); ?>

                                            <form method="POST" action="php/save_used_media.php" style="flex:1; margin:0;">
                                                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">

                                                <?php if ($is_used): ?>
                                                    <button type="button" disabled
                                                        style="width:100%; background-color: #95a5a6; color:white; padding:8px 12px;
                                                        border:none; border-radius:4px; font-size:13px;">
                                                        Already Used
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit"
                                                        style="width:100%; background-color:#2980b9; color:white; padding:8px 12px;
                                                        border:none; border-radius:4px; cursor:pointer; font-size:13px;">
                                                        Use
                                                    </button>
                                                <?php endif; ?>
                                            </form>

                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="border: 2px dashed #81c784; border-radius: 8px; height: 300px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; background-color: #f1f8f4;">
                                <svg style="width: 48px; height: 48px; color: #27ae60; margin-bottom: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <p style="color: #27ae60; font-weight: 600;">No media uploaded yet — add some files</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- About Section -->
            <section id="about" style="background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(39,174,96,0.1); border-left: 4px solid #27ae60;">
                <h2 style="color: #2d5016; margin-bottom: 10px;">About System Settings</h2>
                <p style="color: #5a7f5f; margin-bottom: 25px;">Edit information about Presyong Tanza system</p>
                <form action="php/save_settings.php" method="POST">
                    <input type="hidden" name="section" value="about">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #2d5016;">About Title:</label>
                        <input type="text" name="about_title" value="<?php echo htmlspecialchars($settings['about_title'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 2px solid #c8e6c9; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #2d5016;">About Description:</label>
                        <textarea name="about_description" rows="5" style="width: 100%; padding: 12px; border: 2px solid #c8e6c9; border-radius: 6px; font-size: 14px; resize: vertical;"><?php echo htmlspecialchars($settings['about_description'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 12px 30px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; box-shadow: 0 2px 4px rgba(39,174,96,0.3);">Update About Section</button>
                </form>
            </section>

            <!-- General Settings Section -->
            <section id="site-settings" style="background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(39,174,96,0.1); border-left: 4px solid #27ae60;">
                <h2 style="color: #2d5016; margin-bottom: 10px;">General Settings</h2>
                <p style="color: #5a7f5f; margin-bottom: 25px;">Configure general Presyong Tanza system settings</p>
                <form action="php/save_settings.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="section" value="general">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #2d5016;">System Name:</label>
                        <input type="text" name="system_name" value="<?php echo htmlspecialchars($settings['system_name'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 2px solid #c8e6c9; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #2d5016;">System Logo:</label>
                        <input type="file" name="system_logo" accept="image/*" style="width: 100%; padding: 12px; border: 2px solid #c8e6c9; border-radius: 6px; font-size: 14px;">
                    </div>
                    <button type="submit" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 12px 30px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; box-shadow: 0 2px 4px rgba(39,174,96,0.3);">Save Settings</button>
                </form>
            </section>

            <!-- Barangay Management Section -->
            <section id="barangay-management" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(39,174,96,0.1); border-left: 4px solid #27ae60;">
                <h2 style="color: #2d5016; margin-bottom: 10px;">Barangay Management</h2>
                <p style="color: #5a7f5f; margin-bottom: 25px;">Manage barangays in the Presyong Tanza system</p>
                
                <!-- Add Barangay Form -->
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #2d5016; margin-bottom: 15px;">Add New Barangay</h3>
                    <form action="php/save_barangay.php" method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <input type="hidden" name="action" value="add">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #2d5016;">Barangay Name:</label>
                            <input type="text" name="barangay_name" placeholder="Enter barangay name" style="width: 100%; padding: 12px; border: 2px solid #c8e6c9; border-radius: 6px; font-size: 14px;">
                        </div>
                        <!-- code is generated automatically on save -->
                        <div style="grid-column: 1 / -1;">
                            <button type="submit" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 12px 30px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; box-shadow: 0 2px 4px rgba(39,174,96,0.3);">Add Barangay</button>
                        </div>
                    </form>
                </div>

                <!-- Barangay List -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="color: #2d5016; margin: 0;">Barangay List</h3>
                        <input type="text" placeholder="Search barangay..." style="padding: 10px 15px; border: 2px solid #c8e6c9; border-radius: 6px; width: 250px;">
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #2d5016 0%, #27ae60 100%); color: white;">
                                    <th style="padding: 15px; text-align: left; border: 1px solid #c8e6c9;">Code</th>
                                    <th style="padding: 15px; text-align: left; border: 1px solid #c8e6c9;">Barangay Name</th>
                                    <th style="padding: 15px; text-align: center; border: 1px solid #c8e6c9; width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($barangays)): ?>
                                    <?php foreach ($barangays as $b): ?>
                                        <tr style="background-color: <?php echo (($b['id'] % 2) ? '#f1f8f4' : 'white'); ?>;">
                                            <td style="padding: 15px; border: 1px solid #c8e6c9;"><?php echo htmlspecialchars($b['code'] ?? ''); ?></td>
                                            <td style="padding: 15px; border: 1px solid #c8e6c9; font-weight: 600; color: #2d5016;"><?php echo htmlspecialchars($b['name']); ?></td>
                                            <td style="padding: 15px; border: 1px solid #c8e6c9; text-align: center;">
                                                <button type="button" class="openEditBarangay" data-id="<?php echo (int)$b['id']; ?>" data-name="<?php echo htmlspecialchars($b['name'], ENT_QUOTES); ?>" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px;">Edit</button>
                                                <form style="display:inline-block; margin:0;" method="POST" action="php/delete_barangay.php" onsubmit="return confirm('Delete this barangay?');">
                                                    <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                                    <button type="submit" style="background-color: #e74c3c; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer;">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="padding: 20px; text-align: center; color: #5a7f5f;">No barangays found. Add one using the form above.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

<!-- Barangay Edit Modal -->
<div id="barangayModal" class="settings-modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:99999;">
    <div class="settings-modal-content" style="background:#fff; padding:20px; border-radius:8px; width:420px; max-width:95%; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0; color:#2d5016;">Edit Barangay</h3>
        <form id="barangayEditForm" method="POST" action="php/update_barangay.php">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="modal_barangay_id" value="">
            <div style="margin-bottom:12px;">
                <label style="display:block; font-weight:600; color:#2d5016; margin-bottom:6px;">Barangay Name</label>
                <input type="text" name="barangay_name" id="modal_barangay_name" style="width:100%; padding:10px; border:1px solid #c8e6c9; border-radius:6px; box-sizing:border-box;">
            </div>
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" id="modalCancel" style="background:#eee; padding:8px 14px; border-radius:6px; border:none; cursor:pointer;">Cancel</button>
                <button type="submit" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color:white; padding:8px 14px; border:none; border-radius:6px; cursor:pointer;">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-submit media form when files are selected
document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('mediaUpload');
    if (!input) return;
    input.addEventListener('change', function() {
        if (this.files && this.files.length) {
            // submit the enclosing form
            var f = document.getElementById('mediaForm');
            if (f) f.submit();
        }
    });
});

// Open modal and populate fields
document.addEventListener('DOMContentLoaded', function() {
    var buttons = document.querySelectorAll('.openEditBarangay');
    var modal = document.getElementById('barangayModal');
    var inputId = document.getElementById('modal_barangay_id');
    var inputName = document.getElementById('modal_barangay_name');
    var cancel = document.getElementById('modalCancel');

    buttons.forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = this.getAttribute('data-id');
            var name = this.getAttribute('data-name');
            inputId.value = id;
            inputName.value = name;
            modal.style.display = 'flex';
            inputName.focus();
        });
    });

    cancel.addEventListener('click', function(){
        modal.style.display = 'none';
    });

    // Close modal when clicking outside dialog
    modal.addEventListener('click', function(e){
        if (e.target === modal) modal.style.display = 'none';
    });
    
    // Floating sidebar toggle functionality
    var floatingBtn = document.getElementById('settingsFloatingBtn');
    var sidebar = document.querySelector('.settings-sidebar');
    var overlay = document.getElementById('settingsSidebarOverlay');
    
    function toggleSettingsSidebar() {
        if (sidebar && overlay) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            
            // Change icon
            var icon = floatingBtn.querySelector('i');
            if (sidebar.classList.contains('active')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        }
    }
    
    if (floatingBtn) {
        floatingBtn.addEventListener('click', toggleSettingsSidebar);
    }
    
    if (overlay) {
        overlay.addEventListener('click', toggleSettingsSidebar);
    }
});
</script>
</body>
</html>
