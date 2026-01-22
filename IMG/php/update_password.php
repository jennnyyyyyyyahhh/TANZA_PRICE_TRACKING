<?php
session_start();
require 'connection.php'; // $conn

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password     = $_POST['old_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        header("Location: ../profile.php?error=empty_fields");
        exit;
    }

    if ($new_password !== $confirm_password) {
        header("Location: ../profile.php?error=password_mismatch");
        exit;
    }

    // Fetch current password hash
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($password_hash);
    $stmt->fetch();
    $stmt->close();

    if (!$password_hash || !password_verify($old_password, $password_hash)) {
        header("Location: ../profile.php?error=wrong_old_password");
        exit;
    }

    // Hash and update new password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $updated_at = date('Y-m-d H:i:s');

    $update = $conn->prepare("
        UPDATE users 
        SET password_hash = ?, updated_at = ?
        WHERE id = ?
    ");
    $update->bind_param("ssi", $new_hash, $updated_at, $user_id);
    $update->execute();
    $update->close();

    header("Location: ../profile.php?password_updated=1");
    exit;
}

$conn->close();
?>
