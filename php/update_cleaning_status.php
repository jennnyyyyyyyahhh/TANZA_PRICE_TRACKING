<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = intval($_POST['request_id']);
    $newStatus = $_POST['new_status'] === 'completed' ? 'completed' : 'pending';

    $stmt = $conn->prepare("UPDATE cleaning_requests SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $requestId);
    $stmt->execute();

    echo json_encode(['success' => true, 'status' => $newStatus]);
    exit;
}

echo json_encode(['success' => false]);
