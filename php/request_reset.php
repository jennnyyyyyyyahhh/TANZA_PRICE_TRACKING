<?php
header('Content-Type: application/json');
require_once __DIR__ . '/connection.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || empty($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

$email = trim($data['email']);

$stmt = $conn->prepare('SELECT id, first_name, last_name FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    // Do not reveal whether email exists in production; here we inform the user
    echo json_encode(['success' => false, 'message' => 'Email not found']);
    exit;
}
$row = $res->fetch_assoc();
$userId = $row['id'];
$first = $row['first_name'] ?? '';
$last = $row['last_name'] ?? '';
$fullName = trim($first . ' ' . $last);

// Generate numeric verification code
$code = random_int(100000, 999999);
$codeHash = password_hash((string)$code, PASSWORD_DEFAULT);
// Store expiry as DATETIME string (many schemas use DATETIME for timestamps)
$expiryTs = time() + 15 * 60; // 15 minutes
$expiry = date('Y-m-d H:i:s', $expiryTs);

$ustmt = $conn->prepare('UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?');
if (!$ustmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$ustmt->bind_param('ssi', $codeHash, $expiry, $userId);
$ok = $ustmt->execute();
if (!$ok) {
    $err = $ustmt->error ?: $conn->error;
    echo json_encode(['success' => false, 'message' => 'Failed to set reset token: ' . $err]);
    exit;
}

// Return success and the code (and name) so the client can pass it to EmailJS to send
echo json_encode(['success' => true, 'code' => (string)$code, 'name' => $fullName]);
exit;

?>
