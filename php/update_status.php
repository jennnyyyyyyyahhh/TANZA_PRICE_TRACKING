<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    session_start();
    require 'connection.php'; // $conn
    if (!isset($_SESSION['user_id'])) {
    header("Location: /login.html");
    exit;
}
    header('Content-Type: application/json');

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = isset($input['id']) ? intval($input['id']) : 0;
    $newStatus = isset($input['status']) ? strtolower(trim($input['status'])) : '';

    // Validate input
    $validStatuses = ['pending', 'active', 'inactive', 'suspended'];
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    if (!in_array($newStatus, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Update database
    $stmt = $conn->prepare("UPDATE users SET account_status = ? WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("si", $newStatus, $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'new_status' => $newStatus]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
?>
