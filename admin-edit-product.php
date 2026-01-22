<?php
require_once '../config.php';

$id = $_POST['product_id'];
$name = trim($_POST['product_name']);
$imagePath = '';

if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
    $targetDir = "../uploads/products/";
    $fileName = time() . '_' . basename($_FILES["product_image"]["name"]);
    $targetFile = $targetDir . $fileName;
    move_uploaded_file($_FILES["product_image"]["tmp_name"], $targetFile);
    $imagePath = "uploads/products/" . $fileName;

    $stmt = $pdo->prepare("UPDATE products SET product_name=?, image=? WHERE id=?");
    $stmt->execute([$name, $imagePath, $id]);
} else {
    $stmt = $pdo->prepare("UPDATE products SET product_name=? WHERE id=?");
    $stmt->execute([$name, $id]);
}

header('Location: ../admin-price-survey.php');
exit;
?>
