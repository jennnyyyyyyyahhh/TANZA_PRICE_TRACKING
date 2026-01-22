<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'connection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Survey ID not provided']);
    exit;
}

$surveyId = intval($_GET['id']);
if ($surveyId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid survey ID']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $surveyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Survey not found']);
    exit;
}

$survey = $result->fetch_assoc();

// Return JSON
echo json_encode([
    'id' => $survey['id'],
    'product_name' => $survey['product_name'],
    'commodity_type' => $survey['commodity_type'],
    'variety' => $survey['variety'],
    'price' => $survey['price'],
    'quantity_type' => $survey['quantity_type'],
    'quality' => $survey['quality'],
    'note' => $survey['note'],
    'product_image' => $survey['product_image'] ? $survey['product_image'] : null
]);

$stmt->close();
$conn->close();
exit;
?>