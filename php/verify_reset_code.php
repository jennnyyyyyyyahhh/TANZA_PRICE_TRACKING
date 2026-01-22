<?php
header('Content-Type: application/json');
require_once __DIR__ . '/connection.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || empty($data['email']) || empty($data['code'])) {
    echo json_encode(['success' => false, 'message' => 'Email and code are required']);
    exit;
}

$email = trim($data['email']);
$code = trim($data['code']);

$stmt = $conn->prepare('SELECT id, reset_token, reset_token_expiry FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Email not found']);
    exit;
}
$row = $res->fetch_assoc();
if (empty($row['reset_token']) || empty($row['reset_token_expiry'])) {
    echo json_encode(['success' => false, 'message' => 'No reset request found']);
    exit;
}

// Support expiry stored as unix int or as DATETIME string in DB
$expiryRaw = $row['reset_token_expiry'];
$expiryTs = null;
if (is_numeric($expiryRaw)) {
    $expiryTs = (int)$expiryRaw;
} else {
    $expiryTs = $expiryRaw ? strtotime($expiryRaw) : 0;
}
if (!$expiryTs || time() > $expiryTs) {
    echo json_encode(['success' => false, 'message' => 'Verification code expired']);
    exit;
}

if (!password_verify((string)$code, $row['reset_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
    exit;
}

echo json_encode(['success' => true]);
exit;
?>
