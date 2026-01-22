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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name   = trim($_POST['product_name'] ?? '');
    $commodity_type = trim($_POST['commodity_type'] ?? '');
    $variety        = trim($_POST['variety'] ?? null);
    $price          = floatval($_POST['price'] ?? 0);
    $quantity_type  = trim($_POST['quantity_type'] ?? '');
    $quality        = trim($_POST['quality'] ?? '');
    $note           = trim($_POST['note'] ?? null);
    $address        = trim($_POST['address'] ?? '');
    $full_name      = trim($_POST['full_name'] ?? '');

    // Handle image upload...
    $image_path = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/surveys/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileTmpPath = $_FILES['product_image']['tmp_name'];
        $fileName = basename($_FILES['product_image']['name']);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg','jpeg','png','gif','webp'];

        if (in_array($fileExtension, $allowedTypes)) {
            $newFileName = 'survey_' . uniqid('', true) . '.' . $fileExtension;
            $destination = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destination)) {
                $image_path = 'uploads/surveys/' . $newFileName;
            }
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO surveys 
            (user_id, name, product_name, address, commodity_type, variety, price, quantity_type, quality, note, product_image, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) die("Prepare failed: " . $conn->error);

    $stmt->bind_param(
        "issssssssss",
        $user_id,
        $full_name,
        $product_name,
        $address,
        $commodity_type,
        $variety,
        $price,
        $quantity_type,
        $quality,
        $note,
        $image_path
    );

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: /vendor-survey-forms-list.php?product=" . urlencode($product_name) . "&success=1");
        exit;
    } else {
        die("Error executing query: " . $stmt->error);
    }
}
?>
