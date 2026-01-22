<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'php/connection.php';

// Ensure settings are loaded even for non-logged-in users
$settings = [];
if (isset($conn)) {
    $query = "SELECT name, value FROM settings";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['name']] = $row['value'];
        }
    }
}

// get logged-in user's first name for header display
$userName = null;
if (isset($_SESSION['user_id'])) {
    $stmtU = $conn->prepare("SELECT first_name FROM users WHERE id = ? LIMIT 1");
    $stmtU->bind_param("i", $_SESSION['user_id']);
    $stmtU->execute();
    $resU = $stmtU->get_result();
    if ($resU && $resU->num_rows > 0) {
        $rowU = $resU->fetch_assoc();
        $userName = $rowU['first_name'];
    }

        $query = "SELECT name, value FROM settings";
    $result = $conn->query($query);

    $settings = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['name']] = $row['value'];
        }
    }
    // populate full user info and admin flag for header usage
    $user = null;
    $isAdmin = false;

    // fetch more user fields if available
    $stmtU->close();
    $stmtUser = $conn->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ? LIMIT 1");
    if ($stmtUser) {
        $stmtUser->bind_param('i', $_SESSION['user_id']);
        $stmtUser->execute();
        $resUser = $stmtUser->get_result();
        if ($resUser && $resUser->num_rows > 0) {
            $user = $resUser->fetch_assoc();
        }
        $stmtUser->close();
    }

    // determine admin role from `admin` table (if present)
    $stmtRole = $conn->prepare("SELECT role FROM admin WHERE user_id = ? LIMIT 1");
    if ($stmtRole) {
        $stmtRole->bind_param('i', $_SESSION['user_id']);
        $stmtRole->execute();
        $resRole = $stmtRole->get_result();
        if ($resRole && $resRole->num_rows > 0) {
            $rowRole = $resRole->fetch_assoc();
            if (!empty($rowRole['role']) && $rowRole['role'] === 'admin') {
                $isAdmin = true;
            }
        }
        $stmtRole->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Price Data - Tanza Public Market</title>
    
    <!-- Global Standards CSS -->
    <link rel="stylesheet" href="../CSS/global-standards.css">
    
    <!-- Page-Specific CSS -->
    <link rel="stylesheet" href="CSS/export-data.css">
    <!-- Vendor dashboard styles for header/notification -->
    <link rel="stylesheet" href="CSS/vendor-dashboard.css">
    <link rel="stylesheet" href="../CSS/price-update-notification.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container"> 
                <!-- Logo -->
                <a href="index.php" class="logo">
                    <i class="fas fa-seedling"></i>
                    <span><?php echo $settings['system_name'];?></span>
                </a>
                
                <!-- Hamburger Menu (Mobile) -->
                <button class="hamburger-menu" id="hamburgerMenu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                
                <!-- Navigation Menu -->
                <ul class="nav-menu" id="navMenu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a href="pricefront.php" class="nav-link active">PRICE MONITORING</a>
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
                                    <span><?php echo htmlspecialchars($user['first_name'] ?? $userName ?? '') ?></span>
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
                                <span class="ms-1"><?php echo htmlspecialchars($user['first_name'] ?? $userName ?? '') ?></span>
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
    <main class="export-main">
        <div class="export-container">
            <!-- Back Button -->
            <div class="back-navigation">
                <a href="pricefront.html" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Price Monitoring</span>
                </a>
            </div>

            <!-- Export Form Card -->
            <div class="export-card">
                <div class="export-header">
                    <i class="fas fa-file-download"></i>
                    <h1>Export Price Data</h1>
                    <p>Generate and download comprehensive price reports</p>
                </div>

                <form id="exportForm" class="export-form">
                    <!-- Date Range Section -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-calendar-alt"></i>
                            Date Range
                        </h2>
                        <div class="date-inputs">
                            <div class="input-group">
                                <label for="dateFrom">From</label>
                                <div class="input-wrapper">
                                    <input type="date" id="dateFrom" name="dateFrom" required>
                                    <i class="fas fa-calendar"></i>
                                </div>
                            </div>
                            <div class="input-group">
                                <label for="dateTo">To</label>
                                <div class="input-wrapper">
                                    <input type="date" id="dateTo" name="dateTo" required>
                                    <i class="fas fa-calendar"></i>
                                </div>
                            </div>
                        </div>
                        <div class="date-presets">
                            <button type="button" class="preset-btn" data-range="today">Today</button>
                            <button type="button" class="preset-btn" data-range="week">Last 7 Days</button>
                            <button type="button" class="preset-btn" data-range="month">Last 30 Days</button>
                            <button type="button" class="preset-btn" data-range="year">This Year</button>
                        </div>
                    </div>

                    <!-- Include in Report Section -->
                    <div class="form-section">
                      
                         
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="includeComparison" id="includeComparison">
                                <span class="checkmark::after"></span>
                                <div class="checkbox-content">
                                    <i class="fas fa-balance-scale"></i>
                                    <span>Price Comparison</span>
                                    <small>Compare prices across stores</small>
                                </div>
                            
                            </label>
                            <BR></BR>
                      <HR></HR>

                    <!-- Action Buttons -->
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="window.location.href='pricefront.html'">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-download"></i>
                            Generate & Download Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Info Cards -->
            <div class="info-cards">
                <div class="info-card">
                    <i class="fas fa-info-circle"></i>
                    <h3>Export Guidelines</h3>
                    <ul>
                        <li>Maximum date range: 365 days</li>
                        <li>Reports include all commodity categories</li>
                        <li>Large datasets may take longer to generate</li>
                    </ul>
                </div>
                <div class="info-card">
                    <i class="fas fa-lightbulb"></i>
                    <h3>Tips</h3>
                    <ul>
                        <li>Excel format allows custom calculations</li>
                        <li>You can import Excel files to other systems</li>
                        <li>Use filters and sorting in Excel for analysis</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <h3>Generating Report...</h3>
            <p>Please wait while we prepare your data</p>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <span class="progress-text" id="progressText">0%</span>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="success-modal" id="successModal">
        <div class="success-content">
            <i class="fas fa-check-circle"></i>
            <h2>Report Generated Successfully!</h2>
            <p>Your report is ready for download</p>
            <div class="modal-actions">
                <button class="btn-primary" id="downloadBtn">
                    <i class="fas fa-download"></i>
                    Download Now
                </button>
                <button class="btn-secondary" id="closeModalBtn">
                    Generate Another Report
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Mobile menu toggle
        const hamburger = document.getElementById('hamburgerMenu');
        const navMenu = document.getElementById('navMenu');
        
        if (hamburger && navMenu) {
            hamburger.addEventListener('click', function() {
                navMenu.classList.toggle('active');
                this.classList.toggle('active');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!hamburger.contains(event.target) && !navMenu.contains(event.target)) {
                    navMenu.classList.remove('active');
                    hamburger.classList.remove('active');
                }
            });
        }
        
        // Notification behavior handled by shared notification scripts

        // Set default dates
        const today = new Date();
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        
        // Set default: last 30 days to today
        const thirtyDaysAgo = new Date(today);
        thirtyDaysAgo.setDate(today.getDate() - 30);
        
        dateFrom.value = thirtyDaysAgo.toISOString().split('T')[0];
        dateTo.value = today.toISOString().split('T')[0];
        
        // Date preset buttons
        const presetButtons = document.querySelectorAll('.preset-btn');
        presetButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const range = this.dataset.range;
                const today = new Date();
                let fromDate = new Date();
                
                switch(range) {
                    case 'today':
                        fromDate = today;
                        break;
                    case 'week':
                        fromDate.setDate(today.getDate() - 7);
                        break;
                    case 'month':
                        fromDate.setDate(today.getDate() - 30);
                        break;
                    case 'year':
                        fromDate.setFullYear(today.getFullYear(), 0, 1);
                        break;
                }
                
                dateFrom.value = fromDate.toISOString().split('T')[0];
                dateTo.value = today.toISOString().split('T')[0];
                
                // Visual feedback
                presetButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Form submission
        const exportForm = document.getElementById('exportForm');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const successModal = document.getElementById('successModal');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        
        // keep last export params so modal download can re-request the same report
        let lastExportParams = null;

        exportForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Get form data
            const dateFromValue = document.getElementById('dateFrom').value;
            const dateToValue = document.getElementById('dateTo').value;

            // Validate dates
            if (new Date(dateFromValue) > new Date(dateToValue)) {
                alert('Start date must be before end date');
                return;
            }

            // Show loading overlay
            loadingOverlay.classList.add('active');
            progressFill.style.width = '5%';
            progressText.textContent = '5%';

            // Resolve the absolute URL for the export endpoint and do a quick POST-check
            const endpointUrl = new URL('export_price.php', window.location.href).href;

            // prepare check payload
            const checkBody = new URLSearchParams();
            checkBody.append('dateFrom', dateFromValue);
            checkBody.append('dateTo', dateToValue);
            checkBody.append('check', '1');
            // Always use excel format
            const selectedFormat = 'excel';

            fetch(endpointUrl, { method: 'POST', body: checkBody })
                .then(res => {
                    if (!res.ok) throw new Error('Server returned ' + res.status);
                    return res.json().catch(() => ({ ok: true }));
                })
                .then(json => {
                    // endpoint reachable — save params and show modal.
                    // Do NOT start the download here to avoid duplicate downloads.
                    lastExportParams = { dateFrom: dateFromValue, dateTo: dateToValue, format: selectedFormat };
                    loadingOverlay.classList.remove('active');
                    successModal.classList.add('active');
                    progressFill.style.width = '100%';
                    progressText.textContent = '100%';
                })
                .catch(err => {
                    loadingOverlay.classList.remove('active');
                    alert('Could not reach export endpoint: ' + err.message + '\nEndpoint: ' + endpointUrl);
                    console.error('export check failed', err);
                });
        });
        
        // Download button in success modal: trigger actual download using lastExportParams
        const downloadBtn = document.getElementById('downloadBtn');
        // small toast notifier
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = 'export-toast export-toast-' + type;
            toast.textContent = message;
            // minimal inline styles (keeps dependency-free)
            toast.style.position = 'fixed';
            toast.style.right = '20px';
            toast.style.top = '20px';
            toast.style.zIndex = 3000;
            toast.style.padding = '10px 14px';
            toast.style.borderRadius = '6px';
            toast.style.color = '#fff';
            toast.style.boxShadow = '0 2px 10px rgba(0,0,0,0.15)';
            toast.style.fontSize = '14px';
            if (type === 'success') toast.style.background = '#28a745';
            else if (type === 'error') toast.style.background = '#dc3545';
            else toast.style.background = '#007bff';
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.transition = 'opacity 300ms';
                toast.style.opacity = '0';
                setTimeout(() => { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 300);
            }, 3500);
        }

        downloadBtn.addEventListener('click', async function() {
            // prevent double clicks
            if (downloadBtn.disabled) return;
            downloadBtn.disabled = true;
            const origHTML = downloadBtn.innerHTML;
            downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Downloading...';

            // prefer lastExportParams saved at generation time; fall back to current form
            const params = lastExportParams || {
                dateFrom: dateFrom.value,
                dateTo: dateTo.value,
                format: 'excel'
            };

            const endpoint = new URL('export_price.php', window.location.href).href;

            try {
                const fd = new FormData();
                fd.append('dateFrom', params.dateFrom);
                fd.append('dateTo', params.dateTo);
                fd.append('format', params.format);

                const resp = await fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' });
                if (!resp.ok) {
                    const text = await resp.text().catch(() => '');
                    throw new Error('Server error ' + resp.status + (text ? ': ' + text : ''));
                }

                const blob = await resp.blob();

                // derive filename
                let ext = params.format || 'pdf';
                if (ext === 'excel') ext = 'xlsx';
                const filename = `price_report_${params.dateFrom}_to_${params.dateTo}.${ext}`;

                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);

                showToast('Download started', 'success');
                // mark lastExportParams consumed so re-clicks require regenerate
                lastExportParams = null;
            } catch (err) {
                console.error('Download failed', err);
                showToast('Download failed: ' + (err.message || 'unknown'), 'error');
            } finally {
                downloadBtn.disabled = false;
                downloadBtn.innerHTML = origHTML;
                successModal.classList.remove('active');
                // reset form to defaults
                exportForm.reset();
                dateFrom.value = thirtyDaysAgo.toISOString().split('T')[0];
                dateTo.value = today.toISOString().split('T')[0];
            }
        });
        
        // Close modal button
        const closeModalBtn = document.getElementById('closeModalBtn');
        closeModalBtn.addEventListener('click', function() {
            successModal.classList.remove('active');
        });
    </script>
    <script src="JS/price-update-notification.js" defer></script>
    <script src="JS/notification-sync.js"></script>
    <script src="JS/notification.js"></script>
</body>
</html>
