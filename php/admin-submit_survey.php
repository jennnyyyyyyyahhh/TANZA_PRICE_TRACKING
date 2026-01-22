<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id        = intval($_POST['user_id_display'] ?? 0);
    $name           = trim($_POST['name'] ?? '');
    $product_name   = trim($_POST['product_name'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $commodity_type = trim($_POST['commodity_type'] ?? '');
    $variety        = trim($_POST['variety'] ?? '');
    $price          = floatval($_POST['price'] ?? 0);
    $quantity_type  = trim($_POST['quantity_type'] ?? '');
    $quality        = trim($_POST['quality'] ?? '');
    $note           = trim($_POST['note'] ?? '');
    $image_path     = null;

    if (!empty($_FILES['product_image']['name']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/surveys/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $tmp = $_FILES['product_image']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            $newName = 'survey_' . uniqid('', true) . '.' . $ext;
            $dest = $uploadDir . $newName;
            if (move_uploaded_file($tmp, $dest)) $image_path = 'uploads/surveys/' . $newName;
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO surveys 
        (user_id, name, product_name, address, commodity_type, variety, price, quantity_type, quality, note, product_image, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) die('Prepare failed: ' . $conn->error);

    $stmt->bind_param(
        "isssssdssss",
        $user_id,
        $name,
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
        header("Location: ../admin-survey-forms-list.php?product=" . urlencode($product_name) . "&success=1");
        exit;
    } else {
        die("Error executing query: " . $stmt->error);
    }
}
?>
