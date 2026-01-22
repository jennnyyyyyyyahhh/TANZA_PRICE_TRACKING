<?php
    require_once 'connection.php';
    header('Content-Type: application/json');

    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['id']) || !isset($data['role'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    $userId = intval($data['id']);
    $newRole = $data['role'] === 'admin' ? 'admin' : 'vendor';

    $stmt = $conn->prepare("UPDATE admin SET role = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->bind_param("si", $newRole, $userId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
    $stmt->close();
    $conn->close();
?>
