<?php
include 'connection.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

if ($email && $token) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND verification_token = ? AND email_verified = 0");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmtUpdate = $conn->prepare("UPDATE users SET email_verified = 1, account_status = 'active', verification_token = NULL WHERE email = ?");
        $stmtUpdate->bind_param("s", $email);
        $stmtUpdate->execute();
        $stmtUpdate->close();
        echo "<h2>Email verified! You can now <a href='/login.html'>login</a>.</h2>";
    } else {
        echo "<h2>Invalid or expired verification link.</h2>";
    }

    $stmt->close();
} else {
    echo "<h2>Invalid request.</h2>";
}

$conn->close();
