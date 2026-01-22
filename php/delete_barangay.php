<?php
require_once __DIR__ . '/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin-settings.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    header('Location: /admin-settings.php?error=invalid_id#barangay-management');
    exit;
}

$stmt = $conn->prepare('DELETE FROM barangays WHERE id = ?');
if ($stmt) {
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

header('Location: /admin-settings.php?saved=barangay#barangay-management');
exit;
