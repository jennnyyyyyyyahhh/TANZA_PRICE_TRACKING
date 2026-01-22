<?php
include_once 'connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = htmlspecialchars(trim($_POST['userId'] ?? ''));
    $request_type = htmlspecialchars(trim($_POST['requestType']));
    $preferred_date = htmlspecialchars(trim($_POST['preferredDate']));
    $preferred_time = htmlspecialchars(trim($_POST['preferredTime']));
    $stall_number = htmlspecialchars(trim($_POST['stallNumber']));
    $market_section = htmlspecialchars(trim($_POST['marketSection']));
    $request_description = htmlspecialchars(trim($_POST['requestDescription'] ?? ''));
    $status = "pending";

    $sql = "INSERT INTO cleaning_requests 
        (user_id, request_type, preferred_date, preferred_time, stall_number, market_section, request_description, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        header("Location: ../vendor-cleaning.php?error=" . urlencode("Database error."));
        exit;
    }

    $stmt->bind_param(
        "isssssss",
        $user_id,
        $request_type,
        $preferred_date,
        $preferred_time,
        $stall_number,
        $market_section,
        $request_description,
        $status
    );

    if ($stmt->execute()) {
        // attempt to notify admin via email
        $adminEmail = 'capstonedmn@gmail.com';
        // fetch vendor info for email
        $vendorName = '';
        $vendorEmail = '';
        $uStmt = $conn->prepare("SELECT first_name, email FROM users WHERE id = ? LIMIT 1");
        if ($uStmt) {
            $uStmt->bind_param('i', $user_id);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            if ($uRes && $row = $uRes->fetch_assoc()) {
                $vendorName = $row['first_name'];
                $vendorEmail = $row['email'];
            }
            $uStmt->close();
        }

        $subject = "New Cleaning Request from " . ($vendorName ?: 'Vendor');
        $message = "A new cleaning request has been submitted by " . ($vendorName ?: 'Vendor') . "\n\n";
        $message .= "Request Type: " . $request_type . "\n";
        $message .= "Preferred Date: " . $preferred_date . "\n";
        $message .= "Preferred Time: " . $preferred_time . "\n";
        $message .= "Stall Number: " . $stall_number . "\n";
        $message .= "Market Section: " . $market_section . "\n";
        if (!empty($request_description)) {
            $message .= "Details: " . $request_description . "\n";
        }
        $message .= "\nView requests: " . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] . '/admin-cleaning-management.php' : '/admin-cleaning-management.php') . "\n";

        $headers = "From: no-reply@farmfreshmarket.com\r\n";
        if (!empty($vendorEmail)) {
            $headers .= "Reply-To: " . $vendorEmail . "\r\n";
        }

        // use PHP mail; if unavailable, write to log
        $mailSent = false;
        try {
            $mailSent = mail($adminEmail, $subject, $message, $headers);
        } catch (Exception $e) {
            $mailSent = false;
        }

        if (!$mailSent) {
            // log to file for admins to review (ensure logs dir exists)
            $logDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $logLine = date('c') . " | CLEANING REQUEST EMAIL FAILED | user:" . $user_id . " | stall:" . $stall_number . " | subject:" . str_replace("\n", ' ', $subject) . "\n";
            @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'cleaning_email.log', $logLine, FILE_APPEND);
        }

        header("Location: ../vendor-cleaning.php?status=success");
    } else {
        header("Location: ../vendor-cleaning.php?error=" . urlencode("Error submitting request."));
    }

    $stmt->close();
    $conn->close();
    exit;
} else {
    header("Location: ../vendor-cleaning.php?error=" . urlencode("Invalid request method."));
    exit;
}
?>
