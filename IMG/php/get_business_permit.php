<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = intval($_SESSION['user_id']);
$stmt = $conn->prepare("SELECT * FROM business_permits WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'permit' => $row]);
} else {
    echo json_encode(['success' => false, 'error' => 'No permit found']);
}

$stmt->close();
$conn->close();
?>
