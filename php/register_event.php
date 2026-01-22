<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_clean();
header('Content-Type: application/json');
require_once 'connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
        exit;
    }

    $user_id = intval($_SESSION['user_id']);
    $event_id = intval($_POST['event_id']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $business = trim($_POST['business']);
    $stall = trim($_POST['stall']);

    // Check if event is cancelled
    $eventCheck = $conn->prepare("SELECT status FROM events WHERE id = ? LIMIT 1");
    $eventCheck->bind_param("i", $event_id);
    $eventCheck->execute();
    $eventResult = $eventCheck->get_result();
    $eventData = $eventResult->fetch_assoc();
    $eventCheck->close();

    if (!$eventData) {
        echo json_encode(['status' => 'error', 'message' => 'Event not found.']);
        $conn->close();
        exit;
    }

    if (strtolower($eventData['status']) === 'cancelled') {
        echo json_encode(['status' => 'error', 'message' => 'This event has been cancelled. Registration is not allowed.']);
        $conn->close();
        exit;
    }

    // Check if user already registered for this event
    $check = $conn->prepare("SELECT id FROM event_registrations WHERE user_id = ? AND event_id = ?");
    $check->bind_param("ii", $user_id, $event_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'You are already registered for this event.']);
        $check->close();
        $conn->close();
        exit;
    }
    $check->close();

    // Insert registration with user_id
    $stmt = $conn->prepare("
        INSERT INTO event_registrations 
        (user_id, event_id, first_name, middle_name, last_name, email, phone_number, business_name, stall_number)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisssssss", $user_id, $event_id, $first_name, $middle_name, $last_name, $email, $phone, $business, $stall);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Registration successful.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>
