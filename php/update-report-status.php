<?php
require 'connection.php';

if (isset($_POST['id']) && isset($_POST['status'])) {
    $id = intval($_POST['id']);
    $status = trim($_POST['status']); // remove extra spaces

    $allowedStatuses = ['New', 'Investigating', 'Resolved', 'Dismissed'];
    if (!in_array($status, $allowedStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status: ' . $status]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No report found with this ID']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
}
