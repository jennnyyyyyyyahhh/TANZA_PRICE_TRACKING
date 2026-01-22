<?php
require_once 'connection.php';

if (!isset($_GET['id'])) {
    echo json_encode([]);
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("
    SELECT barangay, city, province 
    FROM user_profiles 
    WHERE user_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

echo json_encode($res ?: []);
?>
