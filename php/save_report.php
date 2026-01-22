<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    require 'connection.php';

    // Collect form data
    $reportDate     = $_POST['reportDate'] ?? '';
    $customerEmail  = $_POST['customerEmail'] ?? null;
    $customerName   = $_POST['customerName'] ?? null;
    $stallName      = $_POST['stallName'] ?? '';
    $stallLocation  = $_POST['stallLocation'] ?? '';
    $category       = $_POST['category'] ?? '';
    $description    = $_POST['description'] ?? '';
    $vendorUserId   = $_POST['vendorUserId'] ?? null; // new field
    $evidencePhoto  = null;
    $status         = 'New'; // default status
    $verified       = $_POST['verified'] ?? null;

    // File upload handling
    if (isset($_FILES['evidencePhoto']) && $_FILES['evidencePhoto']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES['evidencePhoto']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['evidencePhoto']['tmp_name'], $targetFile)) {
            $evidencePhoto = $targetFile;
        }
    }

    // Determine if request is AJAX
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // Require verification flag (sent after client-side OTP verification)
    if ($verified !== '1') {
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Email not verified. Please verify the code sent to your email.']);
            exit;
        }
        header("Location: ../pricefront.php?error=" . urlencode('Email not verified.'));
        exit;
    }

    // Duplicate check: same email, same vendor (user_id), same report_date
    if ($customerEmail && $vendorUserId && $reportDate) {
        $dupStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM reports WHERE email = ? AND user_id = ? AND report_date = ?");
        $dupStmt->bind_param('sis', $customerEmail, $vendorUserId, $reportDate);
        $dupStmt->execute();
        $dupRes = $dupStmt->get_result();
        $dupCount = 0;
        if ($dupRes) {
            $r = $dupRes->fetch_assoc();
            $dupCount = intval($r['cnt'] ?? 0);
        }
        $dupStmt->close();

        if ($dupCount > 0) {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'You have already reported this vendor today.']);
                exit;
            }
            header("Location: ../pricefront.php?error=" . urlencode('You have already reported this vendor today.'));
            exit;
        }
    }

    // Insert data into database including email and user_id
    $stmt = $conn->prepare(
        "INSERT INTO reports (report_date, email, customer_name, stall_name, stall_location, category, description, evidence_photo, status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "sssssssssi",
        $reportDate, $customerEmail, $customerName, $stallName, $stallLocation, $category, $description, $evidencePhoto, $status, $vendorUserId
    );

    if ($stmt->execute()) {
        // Attempt to notify admin via email (mirrors vendor cleaning request behavior)
        $adminEmail = 'capstonedmn@gmail.com';

        // fetch vendor info (if provided) for context in email
        $vendorName = '';
        $vendorEmail = '';
        if (!empty($vendorUserId)) {
            $uStmt = $conn->prepare("SELECT first_name, email FROM users WHERE id = ? LIMIT 1");
            if ($uStmt) {
                $uStmt->bind_param('i', $vendorUserId);
                $uStmt->execute();
                $uRes = $uStmt->get_result();
                if ($uRes && $row = $uRes->fetch_assoc()) {
                    $vendorName = $row['first_name'];
                    $vendorEmail = $row['email'];
                }
                $uStmt->close();
            }
        }

        $subject = "New Vendor Report" . (!empty($vendorName) ? " from " . $vendorName : '');
        $message = "A new vendor report has been submitted." . "\n\n";
        $message .= "Report Date: " . $reportDate . "\n";
        $message .= "Customer Name: " . ($customerName ?: 'N/A') . "\n";
        $message .= "Customer Email: " . ($customerEmail ?: 'N/A') . "\n";
        $message .= "Stall/Vendor: " . ($stallName ?: 'N/A') . "\n";
        $message .= "Stall Location: " . ($stallLocation ?: 'N/A') . "\n";
        $message .= "Category: " . ($category ?: 'N/A') . "\n";
        $message .= "Description: " . ($description ?: 'N/A') . "\n";
        if (!empty($evidencePhoto)) {
            $message .= "Evidence: " . (isset($_SERVER['HTTP_HOST']) ? ('https://' . $_SERVER['HTTP_HOST'] . '/' . $evidencePhoto) : $evidencePhoto) . "\n";
        }
        if (!empty($vendorName)) {
            $message .= "Vendor Name: " . $vendorName . "\n";
        }
        $message .= "\nView reports: " . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] . '/admin-report-management.php' : '/admin-report-management.php') . "\n";

        // Prepare multipart email with attachment if evidence exists
        $boundary = md5(time());
        $fromEmail = 'no-reply@farmfreshmarket.com';

        $headers = "From: " . $fromEmail . "\r\n";
        if (!empty($customerEmail)) {
            $headers .= "Reply-To: " . $customerEmail . "\r\n";
        } elseif (!empty($vendorEmail)) {
            $headers .= "Reply-To: " . $vendorEmail . "\r\n";
        }
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        // Build body
        $body = "--" . $boundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $message . "\r\n\r\n";

        // Attach evidence file if available
        if (!empty($evidencePhoto) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $evidencePhoto)) {
            // evidencePhoto is stored relative to project root (e.g. uploads/...), construct path
            $filePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $evidencePhoto;
            $fileName = basename($filePath);
            $fileSize = filesize($filePath);
            $fileData = file_get_contents($filePath);
            $base64File = chunk_split(base64_encode($fileData));

            // Try to detect MIME type
            $mimeType = 'application/octet-stream';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $detected = finfo_file($finfo, $filePath);
                    if ($detected) $mimeType = $detected;
                    finfo_close($finfo);
                }
            } elseif (function_exists('mime_content_type')) {
                $detected = mime_content_type($filePath);
                if ($detected) $mimeType = $detected;
            }

            $body .= "--" . $boundary . "\r\n";
            $body .= "Content-Type: " . $mimeType . "; name=\"" . $fileName . "\"\r\n";
            $body .= "Content-Disposition: attachment; filename=\"" . $fileName . "\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= $base64File . "\r\n\r\n";
        }

        $body .= "--" . $boundary . "--\r\n";

        // Send with envelope sender (-f) to help deliverability
        $mailSent = false;
        try {
            $mailSent = mail($adminEmail, $subject, $body, $headers, "-f" . $fromEmail);
        } catch (Exception $e) {
            $mailSent = false;
        }

        if (!$mailSent) {
            $logDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $logLine = date('c') . " | REPORT EMAIL FAILED | user:" . ($vendorUserId ?: 'N/A') . " | stall:" . $stallLocation . " | subject:" . str_replace("\n", ' ', $subject) . "\n";
            @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'report_email.log', $logLine, FILE_APPEND);
        }

        if ($isAjax) {
            echo json_encode(['success' => true, 'message' => 'Report saved successfully.']);
            exit;
        }
        header("Location: ../pricefront.php?status=success");
        exit;
    } else {
        $errorMsg = "Failed to save report: " . $stmt->error;
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => $errorMsg]);
            exit;
        }
        $error = urlencode($errorMsg);
        header("Location: ../pricefront.php?error=$error");
        exit;
    }

  
?>
