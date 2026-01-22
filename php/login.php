<?php
session_start();

require_once __DIR__ . '/connection.php';

// Simple helper to log and redirect with a generic message
function fail_redirect($msg = 'An error occurred.') {
	error_log('[login] ' . $msg);
	header('Location: ../login.php?error=' . urlencode('Login failed.'));
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: ../login.php');
	exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$remember = isset($_POST['rememberMe']);

// CSRF validation
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
	header('Location: ../login.php?error=' . urlencode('Invalid session or CSRF token.'));
	exit;
}

if ($email === '' || $password === '') {
	header('Location: ../login.php?error=' . urlencode('Please provide email and password.'));
	exit;
}

// Prepare and fetch user by email using bind_result (avoid get_result())
// Include last_login_attempt for rate-limiting checks
$stmt = $conn->prepare('SELECT id, password_hash, account_status, first_name, last_name, login_attempts, last_login_attempt FROM users WHERE email = ? LIMIT 1');
if (!$stmt) {
	fail_redirect('DB prepare failed: ' . $conn->error);
}

$stmt->bind_param('s', $email);
if (!$stmt->execute()) {
	$stmt->close();
	fail_redirect('DB execute failed: ' . $stmt->error);
}

$stmt->bind_result($uid, $password_hash, $account_status, $first_name, $last_name, $login_attempts, $last_login_attempt);
$fetched = $stmt->fetch();
if (!$fetched) {
	$stmt->close();
	// No such user
	header('Location: ../login.php?error=' . urlencode('Email not found.'));
	exit;
}

// Close stmt after fetch
$stmt->close();

// Check account status
if (!isset($account_status) || strtolower($account_status) !== 'active') {
	header('Location: ../login.php?error=' . urlencode('Account is not active. Please contact support.'));
	exit;
}

// Verify password
// Rate-limit / lockout: if too many attempts within window, block
$max_attempts = 5;
$lock_window_seconds = 15 * 60; // 15 minutes
$last_attempt_ts = $last_login_attempt ? strtotime($last_login_attempt) : 0;
if ((int)$login_attempts >= $max_attempts && ($last_attempt_ts > time() - $lock_window_seconds)) {
	header('Location: ../login.php?error=' . urlencode('Too many login attempts. Try again later.'));
	exit;
}

if (!password_verify($password, $password_hash)) {
	// increment login attempts
	$ua = $conn->prepare('UPDATE users SET login_attempts = COALESCE(login_attempts,0) + 1, last_login_attempt = NOW() WHERE id = ?');
	if ($ua) {
		$ua->bind_param('i', $uid);
		$ua->execute();
		$ua->close();
	} else {
		error_log('[login] failed to update login attempts: ' . $conn->error);
	}

	header('Location: ../login.php?error=' . urlencode('Invalid credentials.'));
	exit;
}

// Successful login: reset attempts and set last_login
$uok = $conn->prepare('UPDATE users SET login_attempts = 0, last_login = NOW() WHERE id = ?');
if ($uok) {
	$uok->bind_param('i', $uid);
	$uok->execute();
	$uok->close();
}

// Regenerate session id
session_regenerate_id(true);

$_SESSION['user_id'] = (int)$uid;
$_SESSION['user_name'] = trim($first_name . ' ' . $last_name);

if ($remember) {
	setcookie(session_name(), session_id(), time() + (86400 * 30), "/");
}

// Determine role from admin table
$role = null;
$ast = $conn->prepare('SELECT role, is_active FROM admin WHERE user_id = ? LIMIT 1');
if ($ast) {
	$ast->bind_param('i', $uid);
	if ($ast->execute()) {
		$ast->bind_result($arole, $ais_active);
		if ($ast->fetch()) {
				// Do not block login based on admin.is_active. Only users.account_status is used to allow/block login.
				$role = strtolower($arole);
			}
	} else {
		error_log('[login] admin execute failed: ' . $ast->error);
	}
	$ast->close();
} else {
	error_log('[login] prepare admin failed: ' . $conn->error);
}

if ($role === null) {
	$role = 'vendor';
}

$_SESSION['role'] = $role;

// Redirect based on role
if ($role === 'vendor') {
	header('Location: ../vendor-dashboard.php');
	exit;
} elseif ($role === 'admin') {
	header('Location: ../admin-dashboard.php');
	exit;
} else {
	header('Location: ../vendor-dashboard.php');
	exit;
}

?>
