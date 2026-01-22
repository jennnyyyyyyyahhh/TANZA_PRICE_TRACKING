<?php
session_start();
include 'connection.php'; // your DB connection

$user_id = $_SESSION['user_id']; // logged-in user

$notifications = [];

// 1. Business Permits
$sql1 = "SELECT id, verification_status, business_name, updated_at 
         FROM business_permits 
         WHERE user_id = ? 
         ORDER BY updated_at DESC";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("i", $user_id);
$stmt1->execute();
$result1 = $stmt1->get_result();
while ($row = $result1->fetch_assoc()) {
    $msg = '';
    if ($row['verification_status'] === 'approved') $msg = "Business permit '{$row['business_name']}' approved.";
    elseif ($row['verification_status'] === 'rejected') $msg = "Business permit '{$row['business_name']}' rejected.";
    elseif ($row['verification_status'] === 'expired') $msg = "Business permit '{$row['business_name']}' is already expired.";
    if ($msg !== '') {
        $notifications[] = [
            'message' => $msg,
            'type' => 'permit',
            'time' => $row['updated_at']
        ];
    }
}

// 2. Cleaning Requests
$sql2 = "SELECT id, request_type, status, updated_at 
         FROM cleaning_requests 
         WHERE user_id = ? 
         ORDER BY updated_at DESC";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
while ($row = $result2->fetch_assoc()) {
    if ($row['status'] !== 'pending') {
        $msg = "Cleaning request '{$row['request_type']}' status: {$row['status']}.";
        $notifications[] = [
            'message' => $msg,
            'type' => 'cleaning',
            'time' => $row['updated_at']
        ];
    }
}

// 3. Admin Role changes
$sql3 = "SELECT role, updated_at 
         FROM admin 
         WHERE user_id = ? 
         ORDER BY updated_at DESC 
         LIMIT 1";
$stmt3 = $conn->prepare($sql3);
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$result3 = $stmt3->get_result();
if ($row = $result3->fetch_assoc()) {
    if (strtolower($row['role']) === 'admin') {
        $msg = "You have been granted admin access.";
        $notifications[] = [
            'message' => $msg,
            'type' => 'role',
            'time' => $row['updated_at']
        ];
    }
}

// 4. Events
$sql4 = "SELECT title, date, status, created_at 
         FROM events 
         ORDER BY created_at DESC";
$result4 = $conn->query($sql4);
$today = new DateTime();
while ($row = $result4->fetch_assoc()) {
    $event_date = new DateTime($row['date']);
    $diff_days = (int)$today->diff($event_date)->format('%r%a');
    $msg = null;
    if ($row['status'] === 'new') $msg = "New event: {$row['title']}.";
    elseif ($row['status'] === 'upcoming') $msg = "Check upcoming event titled '{$row['title']}'.";
    elseif ($row['status'] === 'on-going') $msg = "Event '{$row['title']}' is now ongoing.";
    elseif ($row['status'] === 'cancelled') $msg = "Event '{$row['title']}' has been cancelled.";
    elseif ($diff_days === 1) $msg = "Event '{$row['title']}' starts tomorrow.";
    if ($msg) {
        $notifications[] = [
            'message' => $msg,
            'type' => 'event',
            'time' => $row['created_at']
        ];
    }
}

// 5. Reports (added)
$sql5 = "SELECT id, report_date, customer_name, stall_name, stall_location, category, description, evidence_photo, created_at, status
         FROM reports
         WHERE user_id = ?
         ORDER BY created_at DESC";
$stmt5 = $conn->prepare($sql5);
$stmt5->bind_param("i", $user_id);
$stmt5->execute();
$result5 = $stmt5->get_result();
while ($row = $result5->fetch_assoc()) {
    $msg = "Report for '{$row['customer_name']}' at '{$row['stall_name']}' is currently '{$row['status']}'.";
    $notifications[] = [
        'message' => $msg,
        'type' => 'report',
        'time' => $row['created_at']
    ];
}

// Sort all notifications by latest time
usort($notifications, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));

echo json_encode($notifications);
?>
