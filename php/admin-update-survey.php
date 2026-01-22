<?php
session_start();
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin-survey-forms-list.php");
    exit;
}

// Collect and sanitize POST data
$survey_id      = intval($_POST['survey_id']);
$product_name   = trim($_POST['product_name']);
$commodity_type = trim($_POST['commodity_type']);
$variety        = trim($_POST['variety']);
$price          = floatval($_POST['price']);
$quantity_type  = trim($_POST['quantity_type']);
$quality        = trim($_POST['quality']);
$note           = trim($_POST['note']);

// Optional: user_id and address
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
$address = isset($_POST['address']) ? trim($_POST['address']) : null;

// Handle image upload if a new image is provided
$product_image = null;
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/';
    $tmpName = $_FILES['product_image']['tmp_name'];
    $originalName = basename($_FILES['product_image']['name']);
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $newFileName = 'survey_' . $survey_id . '_' . time() . '.' . $ext;

    if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
        $product_image = $newFileName;
    }
}

// Build the UPDATE query
$sql = "UPDATE surveys SET 
            product_name = ?, 
            commodity_type = ?, 
            variety = ?, 
            price = ?, 
            quantity_type = ?, 
            quality = ?, 
            note = ?";

$params = [$product_name, $commodity_type, $variety, $price, $quantity_type, $quality, $note];
$types = "sssdsds"; // s = string, d = double (for price)

if ($user_id !== null) {
    $sql .= ", user_id = ?";
    $types .= "i";
    $params[] = $user_id;
}

if ($address !== null) {
    $sql .= ", address = ?";
    $types .= "s";
    $params[] = $address;
}

if ($product_image !== null) {
    $sql .= ", product_image = ?";
    $types .= "s";
    $params[] = $product_image;
}

$sql .= " WHERE id = ?";
$types .= "i";
$params[] = $survey_id;

// Prepare and execute
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    // ✅ Set session notification message
    $_SESSION['notif_message'] = "Survey for '$product_name' successfully updated!";

    // Redirect back to the list page with product filter
    $productParam = isset($_POST['product_name']) ? '?product=' . urlencode($_POST['product_name']) : '';
    header("Location: ../admin-survey-forms-list.php{$productParam}");
    exit;
} else {
    $_SESSION['notif_message'] = "Error updating survey: " . $stmt->error;
    header("Location: ../admin-survey-forms-list.php");
    exit;
}


?>