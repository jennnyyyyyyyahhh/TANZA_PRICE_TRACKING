<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.html");
    exit;
}

$userId = $_SESSION['user_id'];
$surveyId = intval($_POST['survey_id']);

$productName   = trim($_POST['product_name']);
$commodityType = trim($_POST['commodity_type']);
$variety       = trim($_POST['variety']);
$price         = floatval($_POST['price']);
$quantityType  = trim($_POST['quantity_type']);
$quality       = trim($_POST['quality']);
$note          = trim($_POST['note']);

// Optional image upload
$productImage = null;
if (!empty($_FILES['product_image']['name'])) {
    $targetDir = "../uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $fileName = time() . "_" . basename($_FILES['product_image']['name']);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFile)) {
        $productImage = $fileName;
    }
}

// ✅ Prepare query properly depending on image
if ($productImage) {
    $sql = "UPDATE surveys 
            SET product_name=?, commodity_type=?, variety=?, price=?, quantity_type=?, quality=?, note=?, product_image=? 
            WHERE id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssdssssii",
        $productName,
        $commodityType,
        $variety,
        $price,
        $quantityType,
        $quality,
        $note,
        $productImage,
        $surveyId,
        $userId
    );
} else {
    $sql = "UPDATE surveys 
            SET product_name=?, commodity_type=?, variety=?, price=?, quantity_type=?, quality=?, note=? 
            WHERE id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssii",
        $productName,
        $commodityType,
        $variety,
        $price,
        $quantityType,
        $quality,
        $note,
        $surveyId,
        $userId
    );
}

if (!$stmt) {
    die("❌ SQL Error: " . $conn->error);
}

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        header("Location: ../vendor-survey-forms-list.php?product=" . urlencode($productName) . "&updated=1");
        exit;
    } else {
        header("Location: ../vendor-survey-forms-list.php?product=" . urlencode($productName) . "&updated=0");
        exit;
    }
}
 else {
    die("❌ Error executing query: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>
