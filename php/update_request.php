<?php
include_once 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION['user_id'];
    $request_id = intval($_POST['requestId']);
    $request_type = htmlspecialchars(trim($_POST['requestType']));
    $preferred_date = htmlspecialchars(trim($_POST['preferredDate']));
    $preferred_time = htmlspecialchars(trim($_POST['preferredTime']));
    $stall_number = htmlspecialchars(trim($_POST['stallNumber']));
    $market_section = htmlspecialchars(trim($_POST['marketSection']));
    $request_description = htmlspecialchars(trim($_POST['requestDescription'] ?? ''));

    $stmt = $conn->prepare("
        UPDATE cleaning_requests 
        SET request_type=?, preferred_date=?, preferred_time=?, stall_number=?, market_section=?, request_description=?, updated_at=NOW()
        WHERE id=? AND user_id=?
    ");
    $stmt->bind_param(
        "ssssssii",
        $request_type,
        $preferred_date,
        $preferred_time,
        $stall_number,
        $market_section,
        $request_description,
        $request_id,
        $user_id
    );

    if ($stmt->execute()) {
        header("Location: ../vendor-cleaning.php?status=updated");
    } else {
        header("Location: ../vendor-cleaning.php?error=" . urlencode("Error updating request."));
    }

    $stmt->close();
    $conn->close();
    exit;
} else {
    header("Location: ../vendor-cleaning.php?error=" . urlencode("Invalid request method."));
    exit;
}
?>
