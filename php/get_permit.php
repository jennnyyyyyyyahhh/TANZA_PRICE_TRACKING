<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'connection.php'; 

$userId = $_GET['userId'] ?? null;
if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'User ID missing']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM business_permits WHERE user_id = ?");
$stmt->execute([$userId]);
$permit = $stmt->fetch(PDO::FETCH_ASSOC);

if ($permit) {
    echo json_encode(['status' => 'success', 'data' => $permit]);
} else {
    echo json_encode(['status' => 'empty']);
}
?>
