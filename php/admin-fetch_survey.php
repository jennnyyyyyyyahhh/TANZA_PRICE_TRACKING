<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing survey ID"]);
    exit;
}

$surveyId = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT s.*, CONCAT(u.first_name,' ',u.last_name) AS submitted_by
    FROM surveys s
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $surveyId);
$stmt->execute();
$result = $stmt->get_result();

if ($survey = $result->fetch_assoc()) {
    echo json_encode($survey);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Survey not found"]);
}

$stmt->close();
$conn->close();
?>
