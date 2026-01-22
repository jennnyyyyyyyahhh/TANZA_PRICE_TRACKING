<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'connection.php';
session_start();

header('Content-Type: application/json'); // Always return JSON

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Collect POST data
$user_id = $_POST['user_id'] ?? '';
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$stall_number = trim($_POST['stall_number'] ?? '');
$permit_type = trim($_POST['permit_type'] ?? '');
$contact = trim($_POST['contact'] ?? '');

if (!$user_id || !$first_name || !$last_name) {
    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=? WHERE id=?");
    $stmt->bind_param("ssi", $first_name, $last_name, $user_id);
    $stmt->execute();

    $stmt2 = $conn->prepare("UPDATE business_permits SET stall_number=?, permit_type=? WHERE user_id=?");
    $stmt2->bind_param("ssi", $stall_number, $permit_type, $user_id);
    $stmt2->execute();

    $stmt3 = $conn->prepare("
        INSERT INTO user_profiles (user_id, phone_number) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE phone_number=VALUES(phone_number)
    ");
    $stmt3->bind_param("is", $user_id, $contact);
    $stmt3->execute();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Vendor updated successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;
?>