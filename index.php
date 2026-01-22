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
    $stmtUser->close();

    // detect if current user is an admin (admin table stores roles)
    $isAdmin = false;
    $user = null;
    $stmtRole = $conn->prepare("SELECT role FROM admin WHERE user_id = ? LIMIT 1");
    if ($stmtRole) {
        $stmtRole->bind_param("i", $_SESSION['user_id']);
        $stmtRole->execute();
        $resRole = $stmtRole->get_result();
        if ($resRole && $resRole->num_rows > 0) {
            $r = $resRole->fetch_assoc();
            if (isset($r['role']) && $r['role'] === 'admin') {
                $isAdmin = true;
                // fetch full user info for admin display
                $stmtFull = $conn->prepare("SELECT first_name, last_name, email, created_at FROM users WHERE id = ? LIMIT 1");
                if ($stmtFull) {
                    $stmtFull->bind_param("i", $_SESSION['user_id']);
                    $stmtFull->execute();
                    $resFull = $stmtFull->get_result();
                    if ($resFull && $resFull->num_rows > 0) {
                        $user = $resFull->fetch_assoc();
                    }
                    $stmtFull->close();
                }
            }
        }
        $stmtRole->close();
    }
}

    $query = "SELECT name, value FROM settings";
    $result = $conn->query($query);

    $settings = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['name']] = $row['value'];
        }
    }

    
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
    <meta name="description" content="Real-time public market price monitoring for fresh produce, fruits, vegetables, and more in Tanza, Cavite">
    <title>Tanza Public Market - Public Market Price Monitoring</title>
    <link rel="stylesheet" href="CSS/landingtrial.css">
    <link rel="stylesheet" href="CSS/vendor-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="CSS/price-update-notification.css">
    <style>
        /* Price update animations */
        .price-card.updating {
            animation: priceUpdate 0.5s ease-in-out;
        }
        
        @keyframes priceUpdate {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); box-shadow: 0 8px 25px rgba(39, 174, 96, 0.3); }
            100% { transform: scale(1); }
        }
        
        .price-card {
            transition: all 0.3s ease;
        }
        
        .price-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
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
                        <span><?php echo $settings['system_name'];; ?></span>
                    </a>
                </div>

                
                <!-- Mobile Hamburger Menu -->
                <button class="hamburger-menu" id="hamburgerMenu" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                
                <!-- Navigation Center -->
                <ul class="nav-menu" id="navMenu">
                    <li class="nav-item">
                        <a href="#home" class="nav-link">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a href="pricefront.php"  style="color: black; text-decoration: none;">PRICES</a>
                    </li>
                    <li class="nav-item">
                        <a href="#weather" class="nav-link">WEATHER</a>
                    </li>
                    <li class="nav-item">
                        <a href="#about" class="nav-link">ABOUT</a>
                    </li>
                </ul>
                
                <!-- Right Side Actions -->
                <div class="nav-actions">
                    
                        <?php if (!empty($userName)): ?>
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
                            </div>
                        <?php else: ?>
                            <div class="user-account-container" id="headerAccountContainer">
                        <div class="dropdown">
                            <button class="btn btn-light d-flex align-items-center" id="headerAccountBtn" data-bs-toggle="dropdown" aria-expanded="false" style="padding:6px 10px;">
                                <i class="fas fa-user-circle"></i>
                                <span class="ms-1"><?php echo htmlspecialchars(($user['first_name'] ?? $userName ?? '')) ?></span>
                                <i class="fas fa-caret-down ms-1"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="headerAccountBtn">
                                <li><a class="dropdown-item" href="vendor-profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="php/logout.php">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="btn-login">LOGIN</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero-section" id="home">
            <!-- Full-page Image Carousel -->
            <div class="hero-carousel">
                <div class="carousel-container">
                    <?php
                    // Load media slides from DB (most recent first). If none, use defaults.
                    $mediaSlides = [];
                    $res = $conn->query("SELECT `id`,`filename`,`original_name`,`mime`,`size`,`created_at` FROM `media` ORDER BY created_at DESC");
                    if ($res) {
                        while ($row = $res->fetch_assoc()) {
                            $mediaSlides[] = $row;
                        }
                        $res->free();
                    }

                    if (empty($mediaSlides)) {
                        $mediaSlides = [
                            ['type' => 'image', 'src' => 'IMG/default.jpg', 'caption' => 'Fresh Produce Direct from Farms'],
                            ['type' => 'image', 'src' => 'IMG/market3.jpg', 'caption' => 'Quality Produce at Fair Prices'],
                            ['type' => 'image', 'src' => 'IMG/market4.jpg', 'caption' => 'Community-Driven Marketplace'],
                        ];
                    }

                    foreach ($mediaSlides as $i => $m) {
                        $active = $i === 0 ? ' active' : '';
                        echo '<div class="carousel-slide' . $active . '">';

                        // default slides use 'src' key and optionally 'type'
                        if (isset($m['src'])) {
                            $type = $m['type'] ?? 'image';
                            if ($type === 'video') {
                                echo '<video src="' . htmlspecialchars($m['src']) . '" class="carousel-image" muted loop playsinline preload="metadata"></video>';
                            } else {
                                echo '<img src="' . htmlspecialchars($m['src']) . '" alt="' . htmlspecialchars($m['caption'] ?? '') . '" class="carousel-image">';
                            }

                        } else {
                            // rows from DB
                            $mime = $m['mime'] ?? '';
                            $isVideo = stripos($mime, 'video') !== false;
                            $filename = $m['filename'] ?? '';
                            $src = 'uploads/media/' . rawurlencode($filename);

                            if ($isVideo) {
                                echo '<video src="' . htmlspecialchars($src) . '" class="carousel-image" muted loop playsinline preload="metadata"></video>';
                            } else {
                                echo '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($m['original_name'] ?? $filename) . '" class="carousel-image" onerror="this.onerror=null;this.src=\'IMG/default.jpg\'">';
                            }
                        }

                        
                        echo '</div>' . "\n";
                    }
                    ?>
                </div>
            </div>
            
            <div class="hero-content">
                <h1 class="hero-title"><?php echo $settings['hero_title']; ?></h1>
                <p class="hero-subtitle"><?php echo $settings['hero_subtitle']; ?></p>
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number">50+</div>
                        <div class="stat-text">Local Vendors</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-text">Market Updates</div>
                    </div>
                </div>
                <div class="hero-buttons">
                    <button id="browsePricesBtn" class="btn-primary" onclick="window.location.href='pricefront.php'">Browse Market Prices</button>
                </div>
            </div>
            <div class="hero-visual">
    <div class="lowest-prices-section">
        <div class="section-header">
            <h2 class="lowest-products-title">
                <i class="fas fa-fire"></i> Today's Lowest Prices
            </h2>
            <p class="section-subtitle">Real-time prices updated from local markets</p>
        </div>

        <?php
            // Fetch unique product names
            $productsQuery = $conn->query("
                SELECT product_name, product_commodity, image, calamity
                FROM products
                GROUP BY product_name
            ");
        ?>

        <div class="modern-price-grid">

        <?php
            while ($prod = $productsQuery->fetch_assoc()) {

                $productName = $prod['product_name'];
                $imagePath = "../IMG/" . $prod['image'];

                // -----------------------------
                // 1) FETCH MOST COMMON COMMODITY + PRICE
                // -----------------------------
                $surveyQuery = $conn->prepare("
                    SELECT commodity_type, price, quantity_type, COUNT(*) AS count_group
                    FROM surveys 
                    WHERE product_name = ?
                    GROUP BY commodity_type, price
                    ORDER BY count_group DESC
                    LIMIT 1
                ");
                $surveyQuery->bind_param("s", $productName);
                $surveyQuery->execute();
                $surveyData = $surveyQuery->get_result()->fetch_assoc();

                if (!$surveyData) {
                    $displayCommodity = "No Data";
                    $displayPrice = "0";
                    $displayQty = "/unit";
                } else {
                    $displayCommodity = $surveyData['commodity_type'];
                    $displayPrice = $surveyData['price'];
                    $displayQty = "/" . $surveyData['quantity_type'];
                }

                // -----------------------------
                // 2) FETCH PERMIT TYPE (STORE LOCATION)
                // -----------------------------
                $permitQuery = $conn->prepare("
                    SELECT permit_type 
                    FROM business_permits 
                    WHERE user_id IN (
                        SELECT user_id FROM surveys WHERE product_name = ?
                    )
                    LIMIT 1
                ");
                $permitQuery->bind_param("s", $productName);
                $permitQuery->execute();
                $permitData = $permitQuery->get_result()->fetch_assoc();

                $martList = ['divimart','alphamart','savemore','dali','puregold','waltermart'];

                if (!$permitData || !in_array(strtolower($permitData['permit_type']), $martList)) {
                    $storeLoc = "Public Market";
                } else {
                    $storeLoc = ucfirst($permitData['permit_type']);
                }

                // -----------------------------
                // OUTPUT PRODUCT CARD
                // -----------------------------
                echo '
                <div class="modern-price-card vegetables">
                    <div class="card-header">
                        <span class="category-badge vegetables-badge">
                            <i class="fas fa-leaf"></i> ' . strtoupper($productName) . '
                        </span>
                    </div>

                    <div class="card-body">
                        <img src="'.$imagePath.'" class="product-image">

                        <h3 class="product-name">'.$productName.'</h3>

                        <div class="price-display">
                            <span class="price-amount">₱'.$displayPrice.'</span>
                            <span class="price-unit">'.$displayQty.'</span>
                        </div>

                        <div class="store-location">
                            <i class="fas fa-map-marker-alt"></i> '.$storeLoc.'
                        </div>
                    </div>
                </div>
                ';
            }
        ?>

        </div> <!-- END modern-price-grid -->
    </div>
</div>

        </section>

        <!-- Weather Section -->
        <section class="weather-section" id="weather">
            <div class="weather-header">
                <h2 class="section-title">
                    Weather & Harvest Impact 
                    <span class="location-badge">Tanza, Cavite</span>
                </h2>
            </div>
            <div class="weather-cards">
                <div class="weather-card today">
                    <div class="weather-day" id="forecastDay1">THURSDAY</div>
                    <div class="weather-date" id="forecastDate1">October 9</div>
                    <div class="weather-icon" id="weatherIcon1">
                        <i class="fas fa-sun"></i>
                    </div>
                    <div class="weather-temp" id="weatherTemp1">32°C</div>
                    <div class="weather-condition" id="weatherCondition1">Sunny & Perfect</div>
                    <div class="price-impact good">
                        <i class="fas fa-leaf"></i>
                        <span>Great Harvest</span>
                    </div>
                </div>
                
                <div class="weather-card">
                    <div class="weather-day" id="forecastDay2">FRIDAY</div>
                    <div class="weather-date" id="forecastDate2">October 10</div>
                    <div class="weather-icon" id="weatherIcon2">
                        <i class="fas fa-cloud-rain"></i>
                    </div>
                    <div class="weather-temp" id="weatherTemp2">26°C</div>
                    <div class="weather-condition" id="weatherCondition2">Light Showers</div>
                    <div class="price-impact neutral">
                        <i class="fas fa-seedling"></i>
                        <span>Good for Crops</span>
                    </div>
                </div>
                
                <div class="weather-card">
                    <div class="weather-day" id="forecastDay3">SATURDAY</div>
                    <div class="weather-date" id="forecastDate3">October 11</div>
                    <div class="weather-icon" id="weatherIcon3">
                        <i class="fas fa-cloud-sun"></i>
                    </div>
                    <div class="weather-temp" id="weatherTemp3">29°C</div>
                    <div class="weather-condition" id="weatherCondition3">Partly Cloudy</div>
                    <div class="price-impact good">
                        <i class="fas fa-tractor"></i>
                        <span>Farming Weather</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section class="about-section" id="about">
            <h2 class="section-title"><?php echo $settings['about_title']; ?></h2>
            <div class="about-content">
                <p><?php echo $settings['about_description']; ?></p>
                
                <div class="about-stats">
                    <div class="about-stat">
                        <div class="stat-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="stat-info">
                            <h4>50+ Market Vendors</h4>
                            <p>Fresh produce from trusted local suppliers</p>
                        </div>
                    </div>
                    
                    <div class="about-stat">
                        <div class="stat-icon">
                            <i class="fas fa-tractor"></i>
                        </div>
                        <div class="stat-info">
                            <h4>25+ Partner Farms</h4>
                            <p>Direct from farms in Cavite and nearby provinces</p>
                        </div>
                    </div>
                    
                    <div class="about-stat">
                        <div class="stat-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <div class="stat-info">
                            <h4>100% Fresh & Local</h4>
                            <p>Supporting sustainable agriculture and local economy</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section class="contact-section" id="contact">
            <h2 class="section-title">Contact Us</h2>
            <div class="contact-content">
                <div class="contact-info">
                    <div class="contact-header">
                        <h3>Do you have any problems or want to report an issue?</h3>
                        <p>We're here to help! Contact us through any of the following channels:</p>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email:</strong>
                            <span><?php echo $settings['contact_email']; ?></span>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Phone:</strong>
                            <span><?php echo $settings['contact_phone']; ?></span>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Location:</strong>
                            <span><?php echo $settings['contact_location']; ?></span>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Market Hours:</strong>
                            <span><?php echo $settings['contact_hours']; ?></span>
                        </div>
                    </div>
                </div>
                <div class="about-info">
                    <h3>About Us</h3>
                    <p class="about-description">
                        <strong>Tanza Public Market</strong> is your trusted source for real-time agricultural pricing and market information. We connect local farmers, vendors, and consumers through transparent pricing and fresh produce availability.
                    </p>
                    <div class="about-mission">
                        <h4><i class="fas fa-bullseye"></i> Our Mission</h4>
                        <p>To empower the agricultural community by providing accessible market data, promoting fair pricing, and supporting sustainable local farming practices.</p>
                    </div>
                    <div class="about-values">
                        <h4><i class="fas fa-heart"></i> Our Values</h4>
                        <ul>
                            <li><strong>Transparency:</strong> Real-time, accurate market pricing</li>
                            <li><strong>Community:</strong> Supporting local farmers and vendors</li>
                            <li><strong>Sustainability:</strong> Promoting eco-friendly agriculture</li>
                            <li><strong>Quality:</strong> Fresh, locally-sourced products</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Login/Signup Modal -->
    <div id="authModal" class="auth-modal">
        <div class="auth-modal-content">
            <span class="auth-close" id="authModalClose">&times;</span>
            
            <!-- Modal Tabs -->
            <div class="auth-tabs">
                <button class="auth-tab-btn active" id="loginTab">LOGIN</button>
                <button class="auth-tab-btn" id="signupTab">SIGN UP</button>
            </div>
            
            <!-- Login Form -->
            <div class="auth-form-container" id="loginForm">
                <h2>Welcome Back!</h2>
                <p class="auth-subtitle">Sign in to access your market dashboard</p>
                
                <form class="auth-form" id="loginFormElement">
                    <div class="form-group">
                        <label for="loginEmail">Email Address</label>
                        <input type="email" id="loginEmail" name="email" class="auth-input" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="loginPassword">Password</label>
                        <input type="password" id="loginPassword" name="password" class="auth-input" placeholder="Enter your password" required>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" id="rememberMe" name="rememberMe">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="#" class="forgot-password">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="auth-btn primary">Sign In</button>
                    
                    
                </form>
            </div>
            
            <!-- Signup Form -->
            <div class="auth-form-container hidden" id="signupForm">
                <h2>Join Our Community!</h2>
                <p class="auth-subtitle">Create an account to track your favorite deals</p>
                
                <form class="auth-form" method="post" action="php/register.php" id="signupFormElement" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="firstName" class="auth-input" placeholder="First name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="lastName" class="auth-input" placeholder="Last name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="signupEmail">Email Address</label>
                        <input type="email" id="signupEmail" name="email" class="auth-input" placeholder="Enter your email (Gmail or Yahoo only)" required>
                        <small id="emailValidation" style="color: #dc3545; font-size: 0.85rem; margin-top: 0.25rem; display: none;">
                            Please use a Gmail or Yahoo email address
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="signupPassword">Password</label>
                        <div style="position: relative;">
                            <input type="password" id="signupPassword" name="password" class="auth-input" placeholder="Create a password" required>
                            <i class="fas fa-eye" id="togglePassword" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;"></i>
                        </div>
                        <div id="passwordStrength" style="margin-top: 10px; display: none;">
                            <div style="margin-bottom: 8px;">
                                <div style="height: 4px; background: #e0e0e0; border-radius: 2px; overflow: hidden;">
                                    <div id="strengthBar" style="height: 100%; width: 0%; transition: all 0.3s;"></div>
                                </div>
                                <span id="strengthText" style="font-size: 0.85rem; font-weight: 600; margin-top: 5px; display: block;"></span>
                            </div>
                            <div style="font-size: 0.85rem; color: #666;">
                                <div style="margin-bottom: 5px; font-weight: 600;">Password must contain:</div>
                                <div id="checkMinLength" style="display: flex; align-items: center; margin-bottom: 3px;">
                                    <i class="fas fa-circle" style="font-size: 6px; margin-right: 8px; color: #dc3545;"></i>
                                    <span>At least 8 characters</span>
                                </div>
                                <div id="checkUppercase" style="display: flex; align-items: center; margin-bottom: 3px;">
                                    <i class="fas fa-circle" style="font-size: 6px; margin-right: 8px; color: #dc3545;"></i>
                                    <span>One uppercase letter</span>
                                </div>
                                <div id="checkLowercase" style="display: flex; align-items: center; margin-bottom: 3px;">
                                    <i class="fas fa-circle" style="font-size: 6px; margin-right: 8px; color: #dc3545;"></i>
                                    <span>One lowercase letter</span>
                                </div>
                                <div id="checkNumber" style="display: flex; align-items: center; margin-bottom: 3px;">
                                    <i class="fas fa-circle" style="font-size: 6px; margin-right: 8px; color: #dc3545;"></i>
                                    <span>One number</span>
                                </div>
                                <div id="checkSpecial" style="display: flex; align-items: center;">
                                    <i class="fas fa-circle" style="font-size: 6px; margin-right: 8px; color: #dc3545;"></i>
                                    <span>One special character (!@#$%^&*)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" class="auth-input" placeholder="Confirm your password" required>
                        <small id="passwordMatch" style="font-size: 0.85rem; margin-top: 0.25rem; display: none;"></small>
                    </div>
                    
                    <div class="form-group" id="otpSection" style="display: none;">
                        <label for="otpCode">Verification Code</label>
                        <input type="text" id="otpCode" name="otpCode" class="auth-input" placeholder="Enter 6-digit code" maxlength="6">
                        <small style="color: #27ae60; font-size: 0.85rem; margin-top: 0.25rem; display: block;">
                            A verification code has been sent to your email
                        </small>
                        <button type="button" id="resendOTP" class="btn-link" style="margin-top: 10px; font-size: 0.85rem; color: #27ae60; border: none; background: none; cursor: pointer; text-decoration: underline;">
                            Resend Code
                        </button>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" id="agreeTerms" required>
                            <span class="checkmark"></span>
                            I agree to the <a href="#" class="terms-link">Terms of Service</a> and <a href="#" class="terms-link">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" id="newsletter" name="newsletter">
                            <span class="checkmark"></span>
                            Send me market updates and price alerts
                        </label>
                    </div>
                    
                    <button type="submit" class="auth-btn primary">Create Account</button>
                    
                    
                </form>
            </div>
        </div>
    </div>


<script src="JS/price-data.js"></script>
<script src="JS/landingtrial.js"></script>
<script src="JS/carousel.js"></script>
<script src="JS/price-update-notification.js" defer></script>
<script src="JS/notification.js"></script>
<script src="JS/signup-validation.js"></script>
<script src="JS/auth-integration.js"></script>
<script>
async function fetchWeather() {
    const lat = 14.352;
    const lon = 120.8384;

    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(today.getDate() - 1);
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 1);

    // Format YYYY-MM-DD
    const formatDate = (date) => date.toISOString().split('T')[0];

    // Open-Meteo API: historical & forecast combined
    // We'll fetch temperature_2m and weathercode
    const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&daily=temperature_2m_max,temperature_2m_min,weathercode&timezone=Asia/Manila&start_date=${formatDate(yesterday)}&end_date=${formatDate(tomorrow)}`;

    try {
        const response = await fetch(url);
        const data = await response.json();

        const days = [yesterday, today, tomorrow];
        const weatherCards = [
            { dayId: 'forecastDay1', dateId: 'forecastDate1', tempId: 'weatherTemp1', conditionId: 'weatherCondition1', iconId: 'weatherIcon1' },
            { dayId: 'forecastDay2', dateId: 'forecastDate2', tempId: 'weatherTemp2', conditionId: 'weatherCondition2', iconId: 'weatherIcon2' },
            { dayId: 'forecastDay3', dateId: 'forecastDate3', tempId: 'weatherTemp3', conditionId: 'weatherCondition3', iconId: 'weatherIcon3' }
        ];

        // Map weather codes to icons & description
        const weatherMap = {
            0: { icon: 'fas fa-sun', desc: 'Clear Sky' },
            1: { icon: 'fas fa-cloud-sun', desc: 'Mainly Clear' },
            2: { icon: 'fas fa-cloud', desc: 'Partly Cloudy' },
            3: { icon: 'fas fa-cloud', desc: 'Overcast' },
            45: { icon: 'fas fa-smog', desc: 'Fog' },
            48: { icon: 'fas fa-smog', desc: 'Depositing Rime Fog' },
            51: { icon: 'fas fa-cloud-rain', desc: 'Drizzle Light' },
            53: { icon: 'fas fa-cloud-rain', desc: 'Drizzle Moderate' },
            55: { icon: 'fas fa-cloud-rain', desc: 'Drizzle Dense' },
            61: { icon: 'fas fa-cloud-showers-heavy', desc: 'Rain Slight' },
            63: { icon: 'fas fa-cloud-showers-heavy', desc: 'Rain Moderate' },
            65: { icon: 'fas fa-cloud-showers-heavy', desc: 'Rain Heavy' },
            71: { icon: 'fas fa-snowflake', desc: 'Snow Slight' },
            73: { icon: 'fas fa-snowflake', desc: 'Snow Moderate' },
            75: { icon: 'fas fa-snowflake', desc: 'Snow Heavy' },
            80: { icon: 'fas fa-cloud-showers-heavy', desc: 'Rain Showers Slight' },
            81: { icon: 'fas fa-cloud-showers-heavy', desc: 'Rain Showers Moderate' },
            82: { icon: 'fas fa-cloud-showers-heavy', desc: 'Rain Showers Violent' },
            95: { icon: 'fas fa-bolt', desc: 'Thunderstorm' },
            96: { icon: 'fas fa-bolt', desc: 'Thunderstorm with Slight Hail' },
            99: { icon: 'fas fa-bolt', desc: 'Thunderstorm with Heavy Hail' },
        };

        days.forEach((day, index) => {
            const weatherCode = data.daily.weathercode[index];
            const maxTemp = data.daily.temperature_2m_max[index];
            const minTemp = data.daily.temperature_2m_min[index];
            const weather = weatherMap[weatherCode] || { icon: 'fas fa-question', desc: 'Unknown' };

            const card = weatherCards[index];
            document.getElementById(card.dayId).textContent = day.toLocaleDateString('en-US', { weekday: 'long' }).toUpperCase();
            document.getElementById(card.dateId).textContent = day.toLocaleDateString('en-US', { month: 'long', day: 'numeric' });
            document.getElementById(card.tempId).textContent = `${Math.round(maxTemp)}°C`;
            document.getElementById(card.conditionId).textContent = weather.desc;
            document.getElementById(card.iconId).innerHTML = `<i class="${weather.icon}"></i>`;
        });

    } catch (error) {
        console.error("Failed to fetch weather:", error);
    }
}

// Run on page load
fetchWeather();
</script>


</body>
</html>
