<?php
// Helper script to create a test user. Run once on the server (e.g., visit in browser or run via PHP CLI).
// It will create a user with a Gmail address and password 'Test@1234' (change as desired).

require_once __DIR__ . '/connection.php';

$email = 'testuser+' . time() . '@gmail.com';
$password_plain = 'Test@1234';
$firstName = 'Test';
$lastName = 'User';

// check if email exists
$chk = $conn->prepare('SELECT id FROM users WHERE email = ?');
$chk->bind_param('s', $email);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    echo "Email already exists: $email\n";
    exit;
}
$chk->close();

$hashed = password_hash($password_plain, PASSWORD_DEFAULT);
$verification_token = bin2hex(random_bytes(16));
$account_status = 'active';
$created_at = date('Y-m-d H:i:s');
$updated_at = $created_at;

// insert user
$stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password_hash, email_verified, verification_token, reset_token, reset_token_expiry, newsletter_subscribed, account_status, login_attempts, last_login_attempt, last_login, created_at, updated_at) VALUES (?, ?, ?, ?, 0, ?, NULL, NULL, 0, ?, 0, NULL, NULL, ?, ?)");
if (!$stmt) {
    echo 'Prepare failed: ' . $conn->error;
    exit;
}
$stmt->bind_param('sssssss', $firstName, $lastName, $email, $hashed, $verification_token, $account_status, $created_at, $updated_at);
if (!$stmt->execute()) {
    echo 'Execute failed: ' . $stmt->error;
    exit;
}
$user_id = $stmt->insert_id;
$stmt->close();

// create profile row (if table exists)
$stmtp = $conn->prepare("INSERT INTO user_profiles (user_id, phone_number, city, province, postal_code, profile_image_path, date_of_birth, gender, updated_at) VALUES (?, '', '', '', '', NULL, '', '', ?)");
if ($stmtp) {
    $stmtp->bind_param('is', $user_id, $updated_at);
    $stmtp->execute();
    $stmtp->close();
}

// create admin mapping active
$stmta = $conn->prepare("INSERT INTO admin (user_id, role, assigned_by, is_active, created_at, updated_at) VALUES (?, 'vendor', NULL, 1, ?, ?)");
if ($stmta) {
    $stmta->bind_param('iss', $user_id, $created_at, $updated_at);
    $stmta->execute();
    $stmta->close();
}

echo "Test user created:\n";
echo "Email: $email\n";
echo "Password: $password_plain\n";
echo "User ID: $user_id\n";

echo "Now try logging in via the form with that email and password.\n";

?>