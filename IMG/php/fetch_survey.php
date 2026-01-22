<?php
require_once 'connection.php';
session_start();

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
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, product_name, commodity_type, variety, price, quantity_type, quality, note, product_image 
    FROM surveys 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $surveyId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Survey not found"]);
}
$stmt->close();
$conn->close();
?>
