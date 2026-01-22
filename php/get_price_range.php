<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'connection.php';

$commodity = isset($_GET['commodity']) ? $_GET['commodity'] : '';
if (!$commodity) {
    echo json_encode(['error' => 'No commodity specified']);
    exit;
}

$sql = "SELECT quantity_type, MIN(price) AS min_price, MAX(price) AS max_price 
        FROM surveys 
        WHERE commodity_type = ?
        GROUP BY quantity_type";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('s', $commodity);
if (!$stmt->execute()) {
    echo json_encode(['error' => 'Execute failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$priceRanges = [];
while ($row = $result->fetch_assoc()) {
    $priceRanges[] = $row;
}

echo json_encode($priceRanges);
$conn->close();
?>
