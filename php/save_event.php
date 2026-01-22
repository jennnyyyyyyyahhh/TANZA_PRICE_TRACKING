<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    require 'connection.php';

    // Get form data safely
    $title = trim($_POST['title'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cancelled = $_POST['cancelled'] ?? 0; // Optional

    // Redirect URL (relative to site root)
    $redirectUrl = '/admin-events.php';

    // Validate required fields
    if (!$title || !$type || !$date || !$start_time || !$end_time || !$location || !$description) {
        header("Location: $redirectUrl?error=" . urlencode("Please fill all fields"));
        exit;
    }

    // Enforce date not in the past
    $currentDate = date('Y-m-d');
    if ($date < $currentDate) {
        header("Location: $redirectUrl?error=" . urlencode("Date cannot be in the past"));
        exit;
    }

    // Handle optional image upload
    $imagePath = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            header("Location: $redirectUrl?error=" . urlencode("Image upload failed"));
            exit;
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            header("Location: $redirectUrl?error=" . urlencode("Image too large"));
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];

        if (!isset($allowed[$mime])) {
            header("Location: $redirectUrl?error=" . urlencode("Invalid image type"));
            exit;
        }

        $ext = $allowed[$mime];
        $uploadsDir = __DIR__ . '/../uploads/events';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

        $filename = uniqid('evt_', true) . '.' . $ext;
        $dest = $uploadsDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            header("Location: $redirectUrl?error=" . urlencode("Failed to save image"));
            exit;
        }

        $imagePath = 'uploads/events/' . $filename; // path usable in HTML
    }

    // Determine status based on date & cancelled
    if ($cancelled) {
        $status = 'cancelled';
    } elseif ($date > $currentDate) {
        $status = 'upcoming';
    } elseif ($date == $currentDate) {
        $status = 'ongoing';
    } else {
        $status = 'completed';
    }

    $created_at = date('Y-m-d H:i:s');

    // Insert event into database (include image_path and created_at)
    $stmt = $conn->prepare("INSERT INTO events (title, type, date, start_time, end_time, location, description, image_path, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        header("Location: $redirectUrl?error=" . urlencode("DB prepare failed"));
        exit;
    }
    $stmt->bind_param('ssssssssss', $title, $type, $date, $start_time, $end_time, $location, $description, $imagePath, $status, $created_at);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        header("Location: $redirectUrl?status=success&action=add");
        exit;
    } else {
        header("Location: $redirectUrl?error=" . urlencode("Failed to add event"));
        exit;
    }

    // Note: script exits after redirect; resources will be freed by PHP automatically
?>
