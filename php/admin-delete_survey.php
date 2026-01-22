<?php
session_start();
require 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

if (isset($_POST['survey_id'])) {
    $survey_id = intval($_POST['survey_id']);

    $stmt = $conn->prepare("DELETE FROM surveys WHERE id = ?");
    $stmt->bind_param("i", $survey_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
}
$conn->close();
?>
