<?php
require_once __DIR__ . '/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin-settings.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$filename = isset($_POST['filename']) ? trim($_POST['filename']) : '';

if (empty($_FILES['replace_file']) || $_FILES['replace_file']['error'] !== UPLOAD_ERR_OK) {
    header('Location: /admin-settings.php?error=upload_failed#media');
    exit;
}

$tmp = $_FILES['replace_file']['tmp_name'];
$orig = basename($_FILES['replace_file']['name']);
$ext = pathinfo($orig, PATHINFO_EXTENSION);
$safe = uniqid('media_', true) . ($ext ? '.' . $ext : '');
$destDir = __DIR__ . '/../uploads/media';
if (!is_dir($destDir)) mkdir($destDir, 0755, true);
$dest = $destDir . '/' . $safe;

if (!move_uploaded_file($tmp, $dest)) {
    header('Location: /admin-settings.php?error=move_failed#media');
    exit;
}

$mime = mime_content_type($dest);
$size = filesize($dest);

if ($id > 0) {
    // replace DB-managed media: remove old file and update row
    $old = null;
    $stmt = $conn->prepare('SELECT filename FROM media WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) $old = $row['filename'];
        $stmt->close();
    }
    if ($old) {
        $oldPath = $destDir . '/' . $old;
        if (file_exists($oldPath)) @unlink($oldPath);
    }
    $stmt = $conn->prepare('UPDATE media SET filename = ?, original_name = ?, mime = ?, size = ? WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('sssii', $safe, $orig, $mime, $size, $id);
        $stmt->execute();
        $stmt->close();
    }
} else {
    // id == 0: try replace filesystem file passed by filename
    if ($filename) {
        $fn = basename($filename);
        $target = $destDir . '/' . $fn;
        // remove the temporary saved new file and instead overwrite target
        if (file_exists($target)) {
            @unlink($target);
        }
        // move the uploaded file to target name
        rename($dest, $target);
    }
}

header('Location: /admin-settings.php?saved=media#media');
exit;
