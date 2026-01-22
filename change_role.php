<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'connection.php';
session_start();

// Verify admin session
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

// Get current user's role
$stmt = $conn->prepare("SELECT role FROM admin WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
$current = $res->fetch_assoc();

if (!$current || $current['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied";
    exit;
}

// Change role request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_POST['user_id'];
    $newRole = $_POST['new_role'];

    // Validate role value
    $allowedRoles = ['admin', 'vendor'];
    if (!in_array($newRole, $allowedRoles, true)) {
        http_response_code(400);
        echo "Invalid role";
        exit;
    }

    // Update role in database
    $stmt = $conn->prepare("UPDATE admin SET role = ? WHERE user_id = ?");
    $stmt->bind_param("si", $newRole, $userId);

    if ($stmt->execute()) {
        echo "Role updated successfully";
    } else {
        http_response_code(500);
        echo "Database error";
    }
}
?>
