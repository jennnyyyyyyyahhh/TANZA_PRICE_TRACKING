<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['survey_id'])) {
    $survey_id = intval($_POST['survey_id']);
    $product_name = trim($_POST['product'] ?? '');

    // Get survey and confirm ownership
    $stmt = $conn->prepare("SELECT product_image FROM surveys WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $survey_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $survey = $result->fetch_assoc();

    if (!$survey) {
        header("Location: ../vendor-survey-forms-list.php?product=" . urlencode($product_name) . "&error=unauthorized");
        exit;
    }

    // Delete image if exists
    if (!empty($survey['product_image'])) {
        $imagePath = __DIR__ . '/../' . $survey['product_image'];
        if (file_exists($imagePath)) unlink($imagePath);
    }

    // Delete record
    $deleteStmt = $conn->prepare("DELETE FROM surveys WHERE id = ? AND user_id = ?");
    $deleteStmt->bind_param("ii", $survey_id, $user_id);
    $deleteStmt->execute();

    header("Location: ../vendor-survey-forms-list.php?product=" . urlencode($product_name) . "&deleted=1");
    exit;
}
header("Location: ../vendor-survey-forms-list.php?error=invalid_request");
exit;
?>
