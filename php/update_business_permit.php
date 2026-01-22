<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    require 'connection.php'; // $conn

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $id          = $_POST['id'] ?? null;
    $permitNumber = $_POST['permitNumber'] ?? null;
    $businessName = $_POST['businessName'] ?? null;
    $issueDate    = $_POST['issueDate'] ?? null;
    $expiryDate   = $_POST['expiryDate'] ?? null;
    $vendorType   = $_POST['vendorType'] ?? null;

    if (!$id || !$permitNumber || !$businessName || !$issueDate || !$expiryDate || !$vendorType) {
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
        echo json_encode(['success' => false, 'message' => 'Your permit is expired ']);
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

    if (isset($_FILES['businessPermit']) && $_FILES['businessPermit']['error'] === UPLOAD_ERR_OK) {
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

        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/business_permits/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save file. Please try again.']);
            exit;
        }

        // Delete old file
        $oldFileQuery = $conn->prepare("SELECT file_path FROM business_permits WHERE id=?");
        $oldFileQuery->bind_param("i", $id);
        $oldFileQuery->execute();
        $oldFileQuery->bind_result($oldFilePath);
        if ($oldFileQuery->fetch()) {
            $oldFileServerPath = str_replace('https://' . $_SERVER['HTTP_HOST'] . '/', $_SERVER['DOCUMENT_ROOT'] . '/', $oldFilePath);
            if (file_exists($oldFileServerPath)) unlink($oldFileServerPath);
        }
        $oldFileQuery->close();

        // ✅ Create public URL
        $baseURL = 'https://' . $_SERVER['HTTP_HOST'] . '/';
        $fileURL = $baseURL . 'uploads/business_permits/' . $fileName;

        $stmt = $conn->prepare("
            UPDATE business_permits 
            SET permit_number=?, business_name=?, issue_date=?, expiry_date=?, permit_type=?, 
                file_path=?, file_name=?, file_size=?, mime_type=?, verification_status = 'renewed', updated_at=NOW()
            WHERE id=?
        ");
        $stmt->bind_param(
            "sssssssdsi",
            $permitNumber,
            $businessName,
            $issueDate,
            $expiryDate,
            $vendorType,
            $fileURL, // ✅ use public URL
            $fileName,
            $file['size'],
            $file['type'],
            $id
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE business_permits 
            SET permit_number=?, business_name=?, issue_date=?, expiry_date=?, permit_type=?, verification_status = 'renewed', updated_at=NOW()
            WHERE id=?
        ");
        $stmt->bind_param("sssssi", $permitNumber, $businessName, $issueDate, $expiryDate, $vendorType, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Business permit updated successfully!']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
        exit;
    }

?>
