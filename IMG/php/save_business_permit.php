<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require 'connection.php'; // $conn

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userId        = $_POST['userId'] ?? null;
        $permitNumber  = $_POST['permitNumber'] ?? null;
        $businessName  = $_POST['businessName'] ?? null;
        $vendorType    = $_POST['vendorType'] ?? null;
        $issueDate     = $_POST['issueDate'] ?? null;
        $expiryDate    = $_POST['expiryDate'] ?? null;
        $stallNumber   = $_POST['stallNumber'] ?? null;

        // Validate required fields
        if (!$userId || !$permitNumber || !$businessName || !$vendorType || !$issueDate || !$expiryDate) {
            header("Location: ../vendor-business-permit.php?error=Missing+required+fields");
            exit;
        }

        // Check if user already uploaded a business permit
        $check = $conn->prepare("SELECT id FROM business_permits WHERE user_id = ?");
        $check->bind_param("i", $userId);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // Already uploaded
            $check->close();
            header("Location: ../vendor-business-permit.php?error=Business+permit+already+uploaded");
            exit;
        }
        $check->close();

        // Handle file upload
        if (!isset($_FILES['businessPermit']) || $_FILES['businessPermit']['error'] !== UPLOAD_ERR_OK) {
            header("Location: ../vendor-business-permit.php?error=File+upload+failed");
            exit;
        }

        $file = $_FILES['businessPermit'];
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            header("Location: ../vendor-business-permit.php?error=Invalid+file+type");
            exit;
        }

        if ($file['size'] > $maxSize) {
            header("Location: ../vendor-business-permit.php?error=File+too+large");
            exit;
        }

        // File storage
        $uploadDir = 'uploads/business_permits/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = time() . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            header("Location: ../vendor-business-permit.php?error=File+save+failed");
            exit;
        }

        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO business_permits 
            (user_id, permit_number, permit_type, business_name, issue_date, file_path, file_name, file_size, mime_type, verification_status, expiry_date, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())
        ");

        $stmt->bind_param(
            "isssssssis",
            $userId,
            $permitNumber,
            $vendorType,
            $businessName,
            $issueDate,
            $filePath,
            $fileName,
            $file['size'],
            $file['type'],
            $expiryDate
        );

        if ($stmt->execute()) {
            header("Location: ../vendor-business-permit.php?status=success");
            exit;
        } else {
            header("Location: ../vendor-business-permit.php?error=Database+insert+failed");
            exit;
        }

        $stmt->close();
        $conn->close();
}
?>
