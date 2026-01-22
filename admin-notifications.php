<?php
include 'php/connection.php';
$admin_id = 1;

// JSON file for storing last read timestamp
$readFile = __DIR__ . "/read_state_$admin_id.json";

// Load or initialize read state (allows per-commodity survey reads)
$readState = file_exists($readFile) ? json_decode(file_get_contents($readFile), true) : ['last_read' => null, 'read_surveys' => []];

// Handle mark-all-as-read via POST
if (isset($_POST['mark_read'])) {
    $readState = ['last_read' => date('Y-m-d H:i:s'), 'read_surveys' => []];
    file_put_contents($readFile, json_encode($readState));
    echo 'ok';
    exit;
}

// Handle marking a specific survey commodity as read (AJAX)
if (isset($_POST['mark_survey'])) {
    $commodity = trim($_POST['mark_survey']);
    if ($commodity !== '') {
        if (!in_array($commodity, $readState['read_surveys'])) {
            $readState['read_surveys'][] = $commodity;
        }
        file_put_contents($readFile, json_encode($readState));
    }

    // Recompute notifications and counts to return updated totals
    $notificationsTemp = [];

    // Cleaning Requests
    $q1 = $conn->query("SELECT market_section, created_at FROM cleaning_requests WHERE status='pending'");
    while ($r = $q1->fetch_assoc()) {
        $notificationsTemp[] = ['type' => 'Cleaning Request', 'message' => "Pending cleaning request for section {$r['market_section']}", 'created_at' => $r['created_at']];
    }

    // Business Permits
    $q2 = $conn->query("SELECT business_name, created_at FROM business_permits WHERE verification_status='pending'");
    while ($r = $q2->fetch_assoc()) {
        $notificationsTemp[] = ['type' => 'Business Permit', 'message' => "Business permit for {$r['business_name']} awaiting verification", 'created_at' => $r['created_at']];
    }

    // Business Permits Expiring Soon (within 1 month)
    $q2b = $conn->query("
        SELECT bp.business_name, bp.expiry_date, u.first_name, u.last_name 
        FROM business_permits bp 
        LEFT JOIN users u ON bp.user_id = u.id 
        WHERE bp.verification_status = 'approved' 
        AND bp.expiry_date IS NOT NULL 
        AND bp.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
        ORDER BY bp.expiry_date ASC
    ");
    while ($r = $q2b->fetch_assoc()) {
        $vendorName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        if (empty($vendorName)) $vendorName = 'Unknown Vendor';
        $expiryDate = date('M d, Y', strtotime($r['expiry_date']));
        $daysLeft = (int) ((strtotime($r['expiry_date']) - strtotime('today')) / 86400);
        $urgency = $daysLeft <= 7 ? '⚠️ ' : '';
        $notificationsTemp[] = ['type' => 'Permit Expiring', 'message' => "{$urgency}{$vendorName}'s permit for {$r['business_name']} expires on {$expiryDate} ({$daysLeft} days left)", 'created_at' => date('Y-m-d H:i:s')];
    }

    // Surveys (grouped)
    $q3 = $conn->query("SELECT commodity_type, COUNT(*) AS total, MIN(created_at) AS created_at FROM surveys GROUP BY commodity_type");
    while ($r = $q3->fetch_assoc()) {
        if (in_array($r['commodity_type'], $readState['read_surveys'])) continue; // skip read commodities
        $notificationsTemp[] = ['type' => 'Survey', 'message' => "{$r['commodity_type']} has {$r['total']} surveys", 'created_at' => $r['created_at']];
    }

    // Reports
    $q4 = $conn->query("SELECT stall_name, created_at FROM reports WHERE status='new'");
    while ($r = $q4->fetch_assoc()) {
        $notificationsTemp[] = ['type' => 'Report', 'message' => "New report for stall {$r['stall_name']}", 'created_at' => $r['created_at']];
    }

    // Event Registrations
    $q5 = $conn->query("SELECT e.title, COUNT(r.id) AS total, MIN(r.registered_at) AS created_at FROM event_registrations r JOIN events e ON e.id = r.event_id GROUP BY r.event_id");
    while ($r = $q5->fetch_assoc()) {
        $notificationsTemp[] = ['type' => 'Event Registration', 'message' => "Event '{$r['title']}' has {$r['total']} vendor registrations", 'created_at' => $r['created_at']];
    }

    // Filter by last_read if present
    if (!empty($readState['last_read'])) {
        $notificationsTemp = array_filter($notificationsTemp, fn($n) => $n['created_at'] > $readState['last_read']);
    }

    $newTotalUnread = count($notificationsTemp);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'totalUnread' => $newTotalUnread]);
    exit;
}

// Read last read timestamp (legacy behavior)
$lastRead = $readState['last_read'] ?? null;

$notifications = [];

// Cleaning Requests
$q1 = $conn->query("SELECT market_section, created_at FROM cleaning_requests WHERE status='pending'");
while ($r = $q1->fetch_assoc()) {
    $notifications[] = [
        'type' => 'Cleaning Request',
        'message' => "Pending cleaning request for section {$r['market_section']}",
        'created_at' => $r['created_at']
    ];
}

// Business Permits
$q2 = $conn->query("SELECT business_name, created_at FROM business_permits WHERE verification_status='pending'");
while ($r = $q2->fetch_assoc()) {
    $notifications[] = [
        'type' => 'Business Permit',
        'message' => "Business permit for {$r['business_name']} awaiting verification",
        'created_at' => $r['created_at']
    ];
}

// Business Permits Expiring Soon (within 1 month)
$q2b = $conn->query("
    SELECT bp.business_name, bp.expiry_date, u.first_name, u.last_name 
    FROM business_permits bp 
    LEFT JOIN users u ON bp.user_id = u.id 
    WHERE bp.verification_status = 'approved' 
    AND bp.expiry_date IS NOT NULL 
    AND bp.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)
    ORDER BY bp.expiry_date ASC
");
while ($r = $q2b->fetch_assoc()) {
    $vendorName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
    if (empty($vendorName)) $vendorName = 'Unknown Vendor';
    $expiryDate = date('M d, Y', strtotime($r['expiry_date']));
    $daysLeft = (int) ((strtotime($r['expiry_date']) - strtotime('today')) / 86400);
    $urgency = $daysLeft <= 7 ? '⚠️ ' : '';
    $notifications[] = [
        'type' => 'Permit Expiring',
        'message' => "{$urgency}{$vendorName}'s permit for {$r['business_name']} expires on {$expiryDate} ({$daysLeft} days left)",
        'created_at' => date('Y-m-d H:i:s') // Use current time so it always shows as new
    ];
}

// Surveys (grouped). Skip commodities that were marked read per admin read state.
$q3 = $conn->query("SELECT commodity_type, COUNT(*) AS total, MIN(created_at) AS created_at FROM surveys GROUP BY commodity_type");
while ($r = $q3->fetch_assoc()) {
    if (!empty($readState['read_surveys']) && in_array($r['commodity_type'], $readState['read_surveys'])) continue;
    $notifications[] = [
        'type' => 'Survey',
        'message' => "{$r['commodity_type']} has {$r['total']} surveys",
        'created_at' => $r['created_at']
    ];
}

// Reports
$q4 = $conn->query("SELECT stall_name, created_at FROM reports WHERE status='new'");
while ($r = $q4->fetch_assoc()) {
    $notifications[] = [
        'type' => 'Report',
        'message' => "New report for stall {$r['stall_name']}",
        'created_at' => $r['created_at']
    ];
}

// Event Registrations (use earliest registration timestamp per event)
$q5 = $conn->query("
    SELECT e.title, COUNT(r.id) AS total, MIN(r.registered_at) AS created_at
    FROM event_registrations r
    JOIN events e ON e.id = r.event_id
    GROUP BY r.event_id
");
while ($r = $q5->fetch_assoc()) {
    $notifications[] = [
        'type' => 'Event Registration',
        'message' => "Event '{$r['title']}' has {$r['total']} vendor registrations",
        'created_at' => $r['created_at']
    ];
}

// Filter unread notifications
if ($lastRead) {
    $notifications = array_filter($notifications, fn($n) => $n['created_at'] > $lastRead);
}

$totalUnread = count($notifications);
?>

<!-- Bootstrap Dropdown -->
<div class="dropdown">
    <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bell fa-lg"></i>
        <?php if ($totalUnread > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge"><?= $totalUnread ?></span>
        <?php endif; ?>
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow-lg p-2" style="width: 360px; max-height: 400px; overflow-y:auto;">
        <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <span class="fw-bold">Notifications</span>
            <button class="btn btn-sm btn-link p-0 text-primary" id="markRead">Mark all as read</button>
        </li>

        <?php if ($totalUnread > 0): ?>
            <?php foreach ($notifications as $n):
                switch($n['type']){
                    case 'Cleaning Request': $icon='fas fa-broom'; $color='text-warning'; break;
                    case 'Business Permit': $icon='fas fa-file-alt'; $color='text-info'; break;
                    case 'Permit Expiring': $icon='fas fa-clock'; $color='text-danger'; break;
                    case 'Survey': $icon='fas fa-chart-bar'; $color='text-success'; break;
                    case 'Report': $icon='fas fa-exclamation-circle'; $color='text-danger'; break;
                    case 'Event Registration': $icon='fas fa-calendar-check'; $color='text-primary'; break;
                    default: $icon='fas fa-info-circle'; $color='text-secondary';
                }
            ?>
                <li class="dropdown-item d-flex align-items-start gap-2 p-2 rounded hover-shadow mb-1">
                    <i class="<?= $icon ?> <?= $color ?> fa-lg mt-1"></i>
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($n['type']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($n['message']) ?></small>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="dropdown-item text-center text-muted small">No new notifications</li>
        <?php endif; ?>
    </ul>
</div>

<style>
.hover-shadow:hover {
    background-color: #f8f9fa;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    cursor: pointer;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const markRead = document.getElementById('markRead');
    const badge = document.getElementById('notificationBadge');
    const notificationList = document.querySelector('.dropdown-menu');

    markRead.addEventListener('click', () => {
        fetch('admin-notifications.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'mark_read=1'
        }).then(() => {
            if(badge) badge.textContent = 0;
            const items = notificationList.querySelectorAll('li:not(:first-child)');
            items.forEach(i => i.remove());
            const noNotif = document.createElement('li');
            noNotif.className = 'dropdown-item text-center text-muted small';
            noNotif.textContent = 'No new notifications';
            notificationList.appendChild(noNotif);
        });
    });
});
</script>
