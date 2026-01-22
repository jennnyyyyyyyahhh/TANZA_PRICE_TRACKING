<?php
require_once __DIR__ . '/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin-settings.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$filename = isset($_POST['filename']) ? trim($_POST['filename']) : '';

// If id > 0 and row exists, remove DB record and file
if ($id > 0) {
    $stmt = $conn->prepare('SELECT filename FROM media WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $filename = $row['filename'];
        }
        $stmt->close();
    }
    if ($filename) {
        $path = __DIR__ . '/../uploads/media/' . $filename;
        if (file_exists($path)) @unlink($path);
    }
    $stmt = $conn->prepare('DELETE FROM media WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: /admin-settings.php?saved=media#media');
    exit;
}

// If id == 0, try to delete by filename from filesystem
if ($filename) {
    // sanitize filename
    $fn = basename($filename);
    $path = __DIR__ . '/../uploads/media/' . $fn;
    if (file_exists($path)) {
        @unlink($path);
    } else {
        // try root uploads
        $path2 = __DIR__ . '/../uploads/' . $fn;
        if (file_exists($path2)) @unlink($path2);
    }
}

header('Location: /admin-settings.php?saved=media#media');
exit;
