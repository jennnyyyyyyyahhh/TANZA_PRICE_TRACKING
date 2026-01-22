<?php
    include_once 'connection.php';
    session_start();

    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $request_id = intval($_GET['id'] ?? 0);

    if ($request_id <= 0) {
        header("Location: ../vendor-cleaning.php?error=" . urlencode("Invalid request ID."));
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM cleaning_requests WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $request_id, $user_id);

    if ($stmt->execute()) {
        header("Location: ../vendor-cleaning.php?status=deleted");
    } else {
        header("Location: ../vendor-cleaning.php?error=" . urlencode("Error deleting request."));
    }

    $stmt->close();
    $conn->close();
    exit;
?>
