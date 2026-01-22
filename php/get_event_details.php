<?php
require_once 'connection.php';

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing event ID']);
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if ($event) {
    echo json_encode(['status' => 'success', 'data' => $event]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Event not found']);
}
?>
