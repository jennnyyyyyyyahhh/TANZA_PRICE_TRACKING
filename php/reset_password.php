<?php
header('Content-Type: application/json');
require_once __DIR__ . '/connection.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || empty($data['email']) || empty($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Email and new password are required']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

// Basic password strength check
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

$stmt = $conn->prepare('SELECT id, reset_token_expiry, first_name, last_name FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Email not found']);
    exit;
}
$row = $res->fetch_assoc();
// Support expiry stored as unix int or as DATETIME string in DB
$expiryRaw = $row['reset_token_expiry'];
$expiryTs = null;
if (empty($expiryRaw)) {
    echo json_encode(['success' => false, 'message' => 'Reset token expired or not found']);
    exit;
}
if (is_numeric($expiryRaw)) {
    $expiryTs = (int)$expiryRaw;
} else {
    $expiryTs = $expiryRaw ? strtotime($expiryRaw) : 0;
}
if (!$expiryTs || time() > $expiryTs) {
    echo json_encode(['success' => false, 'message' => 'Reset token expired or not found']);
    exit;
}

$newHash = password_hash($password, PASSWORD_DEFAULT);
$ustmt = $conn->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL, updated_at = ? WHERE id = ?');
if (!$ustmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$now = date('Y-m-d H:i:s');
$userId = (int)$row['id'];
$ustmt->bind_param('ssi', $newHash, $now, $userId);
$ok = $ustmt->execute();
if (!$ok) {
    $err = $ustmt->error ?: $conn->error;
    echo json_encode(['success' => false, 'message' => 'Failed to update password: ' . $err]);
    exit;
}
// Respond with friendly message and name
$first = trim($row['first_name'] ?? '');
$last = trim($row['last_name'] ?? '');
$fullName = trim($first . ' ' . $last);
echo json_encode(['success' => true, 'message' => 'Password updated successfully.', 'name' => $fullName]);
exit;
?>
