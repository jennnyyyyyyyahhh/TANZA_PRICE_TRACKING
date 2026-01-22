<?php
// Return JSON only; suppress PHP notices to avoid corrupting JSON output
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require_once 'connection.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing id']);
    exit;
}

$id = intval($_GET['id']);

try {
    // fetch user basic info
    $stmt = $conn->prepare("SELECT u.id, u.first_name, u.last_name, u.email, u.account_status, a.role
        FROM users u
        LEFT JOIN admin a ON u.id = a.user_id
        WHERE u.id = ? LIMIT 1");
    if (!$stmt) throw new Exception('DB prepare failed');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // fetch profile
    $profile = [];
    $stmt2 = $conn->prepare("SELECT phone_number, barangay, city, province, postal_code FROM user_profiles WHERE user_id = ? LIMIT 1");
    if ($stmt2) {
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        $profile = $stmt2->get_result()->fetch_assoc() ?: [];
    }

    // fetch latest business permit (use correct column names)
    $permit = [];
    $stmt3 = $conn->prepare("SELECT id, user_id, permit_number, stall_number, permit_type, business_name, issue_date, file_path, file_name, file_size, mime_type, verification_status, verified_by, verified_at, expiry_date, rejection_reason, created_at, updated_at FROM business_permits WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    if ($stmt3) {
        $stmt3->bind_param('i', $id);
        $stmt3->execute();
        $permit = $stmt3->get_result()->fetch_assoc() ?: [];
    }

    echo json_encode(['success' => true, 'user' => $user, 'profile' => $profile, 'permit' => $permit]);
} catch (Exception $e) {
    // Do not reveal internal errors to the client
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>