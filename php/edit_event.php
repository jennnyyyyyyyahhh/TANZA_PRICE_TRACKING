<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $type = $_POST['type'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $location = $_POST['location'];
    $description = $_POST['description'];

    $sql = "UPDATE events SET title=?, type=?, date=?, start_time=?, end_time=?, location=?, description=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $title, $type, $date, $start_time, $end_time, $location, $description, $id);

    if ($stmt->execute()) {
        header("Location: ../admin-events.php?status=success&action=edit");
        exit();
    } else {
        echo "Error updating event: " . $conn->error;
    }
}
?>
