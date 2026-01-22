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


    $status = "Pending";


    $sql = "INSERT INTO cleaning_requests 
        (user_id, request_type, preferred_date, preferred_time, stall_number, market_section, request_description, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
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
       
        echo "<script>alert('Cleaning request submitted successfully!'); window.location.href='../vendor-cleaning.php';</script>";
    } else {
        echo "<script>alert('Error submitting request. Please try again.'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<script>alert('Invalid request method.'); window.history.back();</script>";
}
?>
