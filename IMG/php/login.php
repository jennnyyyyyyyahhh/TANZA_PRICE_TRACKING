<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Collect user agent and IP for login_history
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

    // Validate form input
    if (empty($email) || empty($password)) {
        echo "<script>alert('Please fill in both email and password.'); window.location.href='/login.html';</script>";
        exit;
    }

    // Check if the user exists
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password_hash, account_status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Default login status
    $login_status = 'failed';
    $failure_reason = null;

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Check if account is active
        if ($user['account_status'] !== 'active') {
            $failure_reason = 'Account inactive';
            $login_status = 'failed';
            echo "<script>alert('Your account is not active. Please contact support.'); window.location.href='/login.html';</script>";
        }
        // Verify password
        elseif (password_verify($password, $user['password_hash'])) {
            // ✅ Successful login
            $login_status = 'success';
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];

            // Generate session token
            $session_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 day'));
            $created_at = date('Y-m-d H:i:s');

            // Insert into user_sessions
            $session_stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            $session_stmt->bind_param("isssss", $user['id'], $session_token, $ip_address, $user_agent, $expires_at, $created_at);
            $session_stmt->execute();
            $session_stmt->close();

            // Update last login
            $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update->bind_param("i", $user['id']);
            $update->execute();
            $update->close();

            // Log success in login_history
            $history_stmt = $conn->prepare("INSERT INTO login_history (user_id, email, ip_address, user_agent, login_status, failure_reason, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $history_stmt->bind_param("isssss", $user['id'], $email, $ip_address, $user_agent, $login_status, $failure_reason);
            $history_stmt->execute();
            $history_stmt->close();

            // Redirect to vendor dashboard
            echo "<script>
                    alert('Welcome back, {$user['first_name']}!');
                    window.location.href = '/vendor-dashboard.php';
                  </script>";
        } else {
            // Wrong password
            $failure_reason = 'Incorrect password';
            echo "<script>alert('Incorrect password. Please try again.'); window.location.href='/login.html';</script>";
        }
    } else {
        // No account found
        $failure_reason = 'Email not found';
        echo "<script>alert('No account found with that email. Please sign up first.'); window.location.href='/login.html';</script>";
    }

    // Record login attempt (success or failure)
    $log_stmt = $conn->prepare("INSERT INTO login_history (user_id, email, ip_address, user_agent, login_status, failure_reason, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $user_id = isset($user['id']) ? $user['id'] : null;
    $log_stmt->bind_param("isssss", $user_id, $email, $ip_address, $user_agent, $login_status, $failure_reason);
    $log_stmt->execute();
    $log_stmt->close();

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
