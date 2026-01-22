<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    require 'connection.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }

    $userId = $_SESSION['user_id'];

    // get user and profile info
    $stmt = $conn->prepare("
        SELECT u.first_name, u.last_name, u.email, u.created_at,
            p.phone_number, p.city, p.province, p.postal_code
        FROM users u
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE u.id = ? LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // check if business permit exists
    $hasPermit = false;
    $permitData = [];

    $stmt = $conn->prepare("
        SELECT * FROM business_permits 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $permitData = $result->fetch_assoc();
        $hasPermit = true;
    }
 

    // Determine verification status
    $permitStatus = $hasPermit ? strtolower($permitData['verification_status']) : '';
    $isVerified = ($hasPermit && $permitStatus === 'approved');
?>
