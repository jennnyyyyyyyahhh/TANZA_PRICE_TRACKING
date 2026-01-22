<?php
// Centralized navbar component — reads `settings` from DB
require_once __DIR__ . '/../php/connection.php';
$settings = [];
$res = $conn->query("SHOW TABLES LIKE 'settings'");
if ($res && $res->num_rows) {
    $rs = $conn->query("SELECT `name`,`value` FROM `settings`");
    if ($rs) {
        while ($r = $rs->fetch_assoc()) {
            $settings[$r['name']] = $r['value'];
        }
    }
}
$system_logo = $settings['system_logo'] ?? '';
$system_name = $settings['system_name'] ?? 'Tanza Public Market';
?>
<header class="header">
    <nav class="navbar">
        <div class="nav-container">
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle mobile menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
            <div class="logo">
                <a href="pricefront.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.5rem;">
                    <?php if (!empty($system_logo) && file_exists(__DIR__ . '/../' . ltrim($system_logo, '/'))): ?>
                        <img src="<?php echo htmlspecialchars($system_logo); ?>" alt="logo" style="height:32px; object-fit:contain;">
                    <?php else: ?>
                        <i class="fas fa-seedling"></i>
                    <?php endif; ?>
                    <span class="logo-text"><?php echo htmlspecialchars($system_name); ?></span>
                </a>
            </div>
           <ul class="nav-menu" id="navMenu">
                <li class="nav-item"><a href="index.php" class="nav-link">HOME</a></li>
                <li class="nav-item"><a href="index.php#weather" class="nav-link">WEATHER</a></li>
                <li class="nav-item"><a href="index.php#about" class="nav-link">ABOUT</a></li>
            </ul>
            <div class="nav-actions">
                <div class="notification-container">
                    <i class="fas fa-bell notification-bell" id="notificationBell"></i>
                    <span class="notification-badge" id="notificationBadge">0</span>
                    <div class="notification-dropdown" id="notificationDropdown"></div>
                </div>
                <div class="user-account-container" id="headerAccountContainer">
                    <a href="#profile" class="btn-user-account" id="adminAccountBtn">
                        <i class="fas fa-user-shield"></i>
                        <span id="headerUserName">Admin</span>
                    </a>
                    <div class="account-dropdown" id="accountDropdown">
                        <div class="account-dropdown-header">
                            <div class="account-avatar"><i class="fas fa-user-shield"></i></div>
                            <div class="account-user-info">
                                <h3>Admin User</h3>
                                <p>admin@farmfreshmarket.com</p>
                                <span class="user-type">Administrator</span>
                            </div>
                        </div>
                        <div class="account-menu">
                            <a href="#" class="account-menu-item" data-action="profile"><i class="fas fa-user-shield"></i><span>ADMIN PROFILE</span></a>
                            <a href="#" class="account-menu-item" data-action="settings"><i class="fas fa-cog"></i><span>SYSTEM SETTINGS</span></a>
                            <a href="#" class="account-menu-item logout" data-action="logout"><i class="fas fa-sign-out-alt"></i><span>LOGOUT</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
