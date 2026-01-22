<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require 'connection.php'; // $conn
    session_start();

    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.html");
        exit;   
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = $data['id'] ?? 0;

    if ($user_id) {
        // Delete from business_permits
        $stmt = $conn->prepare("DELETE FROM business_permits WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Delete from users
        $stmt2 = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    }
?>
