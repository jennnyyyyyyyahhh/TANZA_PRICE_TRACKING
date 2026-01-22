<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'connection.php'; // $conn

$id = $_POST['id'];
$permitNumber = $_POST['permitNumber'];
$businessName = $_POST['businessName'];
$issueDate = $_POST['issueDate'];
$expiryDate = $_POST['expiryDate'];
$vendorType = $_POST['vendorType'];

// check if file uploaded
if (isset($_FILES['businessPermit']) && $_FILES['businessPermit']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['businessPermit'];
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        header("Location: ../vendor-business-permit.php?error=Invalid+file+type");
        exit();
    }
    if ($file['size'] > $maxSize) {
        header("Location: ../vendor-business-permit.php?error=File+too+large");
        exit();
    }

    // directory setup
    $uploadDir = 'uploads/business_permits/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileName = time() . '_' . basename($file['name']);
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        header("Location: ../vendor-business-permit.php?error=File+upload+failed");
        exit();
    }

    // get old file and delete it
    $oldFileQuery = $conn->prepare("SELECT file_path FROM business_permits WHERE id=?");
    $oldFileQuery->bind_param("i", $id);
    $oldFileQuery->execute();
    $oldFileQuery->bind_result($oldFilePath);
    if ($oldFileQuery->fetch() && file_exists($oldFilePath)) unlink($oldFilePath);
    $oldFileQuery->close();

    // update including file
    $stmt = $conn->prepare("UPDATE business_permits 
        SET permit_number=?, business_name=?, issue_date=?, expiry_date=?, permit_type=?, 
            file_path=?, file_name=?, file_size=?, mime_type=?, updated_at=NOW() 
        WHERE id=?");
    $stmt->bind_param(
        "sssssssdsi",
        $permitNumber,
        $businessName,
        $issueDate,
        $expiryDate,
        $vendorType,
        $filePath,
        $fileName,
        $file['size'],
        $file['type'],
        $id
    );
} else {
    // update only details
    $stmt = $conn->prepare("UPDATE business_permits 
        SET permit_number=?, business_name=?, issue_date=?, expiry_date=?, permit_type=?, updated_at=NOW() 
        WHERE id=?");
    $stmt->bind_param("sssssi", $permitNumber, $businessName, $issueDate, $expiryDate, $vendorType, $id);
}

// execute update
if ($stmt->execute()) {
    header("Location: ../vendor-business-permit.php?updated=1");
    exit();
} else {
    header("Location: ../vendor-business-permit.php?error=Update+failed");
    exit();
}

$stmt->close();
$conn->close();
?>
