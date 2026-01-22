<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require 'connection.php'; // $conn

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId        = $_POST['userId'] ?? null;
    $permitNumber  = $_POST['permitNumber'] ?? null;
    $businessName  = $_POST['businessName'] ?? null;
    $vendorType    = $_POST['vendorType'] ?? null;
    $issueDate     = $_POST['issueDate'] ?? null;
    $expiryDate    = $_POST['expiryDate'] ?? null;
    $stallNumber   = $_POST['stallNumber'] ?? null;

    if (!$userId || !$permitNumber || !$businessName || !$vendorType || !$issueDate || !$expiryDate) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Validate dates
    $issueDateObj = new DateTime($issueDate);
    $expiryDateObj = new DateTime($expiryDate);
    $currentDate = new DateTime();
    $twoYearsAgo = (new DateTime())->modify('-2 years');
    $twoYearsFromNow = (new DateTime())->modify('+2 years');

    // Check if expiry date is before issue date
    if ($expiryDateObj <= $issueDateObj) {
        echo json_encode(['success' => false, 'message' => 'Expiry date must be after the issue date']);
        exit;
    }

    // Check if issue date is older than 2 years
    if ($issueDateObj < $twoYearsAgo) {
        echo json_encode(['success' => false, 'message' => 'Issue date cannot be older than 2 years']);
        exit;
    }

    // Check if issue date is more than 2 years in the future
    if ($issueDateObj > $twoYearsFromNow) {
        echo json_encode(['success' => false, 'message' => 'Issue date cannot be more than 2 years from current date']);
        exit;
    }

    // Check if expiry date is more than 2 years from current date
    if ($expiryDateObj > $twoYearsFromNow) {
        echo json_encode(['success' => false, 'message' => 'Expiry date cannot be more than 2 years from current date']);
        exit;
    }

    // check existing
    $check = $conn->prepare("SELECT id FROM business_permits WHERE user_id = ?");
    $check->bind_param("i", $userId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        echo json_encode(['success' => false, 'message' => 'Business permit already uploaded']);
        exit;
    }
    $check->close();

    // file validation
    if (!isset($_FILES['businessPermit']) || $_FILES['businessPermit']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload failed. Please try again.']);
        exit;
    }

    $file = $_FILES['businessPermit'];
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 5 * 1024 * 1024;

    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, JPG, and PNG are allowed.']);
        exit;
    }

    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
        exit;
    }

    // ✅ Use real server directory for saving
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/business_permits/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileName = time() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file. Please try again.']);
        exit;
    }

    // ✅ Generate public URL for browser access
    $baseURL = 'https://' . $_SERVER['HTTP_HOST'] . '/';
    $fileURL = $baseURL . 'uploads/business_permits/' . $fileName;

    // Insert to database
    $stmt = $conn->prepare("
        INSERT INTO business_permits 
        (user_id, permit_number, stall_number, permit_type, business_name, issue_date, file_path, file_name, file_size, mime_type, verification_status, expiry_date, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?, 'pending', ?, NOW(), NOW())
    ");

    $stmt->bind_param(
        "issssssssis",
        $userId,
        $permitNumber,
        $stallNumber,
        $vendorType,
        $businessName,
        $issueDate,
        $fileURL, // ✅ use public URL
        $fileName,
        $file['size'],
        $file['type'],
        $expiryDate
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Business permit uploaded successfully!']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
        exit;
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
