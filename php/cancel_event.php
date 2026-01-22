<?php
    require 'connection.php';

    // Accept id from POST or GET
    $id = $_POST['id'] ?? $_GET['id'] ?? null;

    if (!$id) {
        header("Location: https://silver-koala-640008.hostingersite.com/admin-events.php?error=" . urlencode("Invalid event ID"));
        exit;
    }

    // Get current status
    $stmt = $conn->prepare("SELECT status FROM events WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($currentStatus);
    $stmt->fetch();
    $stmt->close();

    if ($currentStatus === 'cancelled') {
        // Get event date
        $stmt = $conn->prepare("SELECT `date` FROM events WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($eventDate);
        $stmt->fetch();
        $stmt->close();

        $today = date('Y-m-d');

        // Determine correct status based on date
        if ($eventDate > $today) $newStatus = 'upcoming';
        elseif ($eventDate == $today) $newStatus = 'ongoing';
        else $newStatus = 'completed';

        $action = 'restore';
    } else {
        $newStatus = 'cancelled';
        $action = 'cancel';
    }


    // Update status
    $stmt = $conn->prepare("UPDATE events SET status=? WHERE id=?");
    $stmt->bind_param("si", $newStatus, $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    // Redirect back with action
    header("Location: https://silver-koala-640008.hostingersite.com/admin-events.php?status=success&action={$action}");
    exit;
?>
