<?php
require_once __DIR__ . '/connection.php'; // provides $conn (mysqli)

// Create settings table if not exists
$createSettingsSql = "CREATE TABLE IF NOT EXISTS `settings` (
    `name` VARCHAR(191) NOT NULL,
    `value` TEXT,
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($createSettingsSql);

// Helper to upsert a setting
function upsert_setting($conn, $name, $value) {
    $stmt = $conn->prepare("INSERT INTO settings (`name`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    if (!$stmt) return false;
    $stmt->bind_param('ss', $name, $value);
    $res = $stmt->execute();
    $stmt->close();
    return $res;
}

$section = isset($_POST['section']) ? $_POST['section'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($section === 'media') {
        // Handle media uploads
        // Ensure uploads folder
        $uploadDir = __DIR__ . '/../uploads/media';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // Create media table
        $createMediaSql = "CREATE TABLE IF NOT EXISTS `media` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `filename` VARCHAR(255) NOT NULL,
            `original_name` VARCHAR(255),
            `mime` VARCHAR(100),
            `size` INT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $conn->query($createMediaSql);

        if (!empty($_FILES['media_files'])) {
            $files = $_FILES['media_files'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $tmp = $files['tmp_name'][$i];
                $orig = basename($files['name'][$i]);
                $ext = pathinfo($orig, PATHINFO_EXTENSION);
                $safe = uniqid('media_', true) . ($ext ? '.' . $ext : '');
                $dest = $uploadDir . '/' . $safe;
                if (move_uploaded_file($tmp, $dest)) {
                    $mime = mime_content_type($dest);
                    $size = filesize($dest);
                    $stmt = $conn->prepare("INSERT INTO media (filename, original_name, mime, size) VALUES (?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param('sssi', $safe, $orig, $mime, $size);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }

        // redirect back to admin settings, anchor to media gallery and include a message
        $anchorMap = [
            'media' => 'media-gallery',
            'hero' => 'hero-section',
            'contact' => 'contact-info',
            'general' => 'site-settings',
            'about' => 'about',
            'barangay' => 'barangay-management'
        ];

        $uploadedCount = isset($files) ? 0 : 0;
        if (!empty($files)) {
            // count files that were successfully moved to uploads (based on DB rows we inserted)
            // simple heuristic: count files with no upload error
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) $uploadedCount++;
            }
        }

        $anchorId = $anchorMap['media'];
        $msg = 'uploaded';
        $qs = http_build_query(['saved' => 1, 'section' => 'media', 'msg' => $msg, 'count' => $uploadedCount]);
        header('Location: /admin-settings.php?' . $qs . '#'. $anchorId);
        exit;
    }

    // For other sections: upsert each POST field (except "section") into settings table
    foreach ($_POST as $key => $value) {
        if ($key === 'section') continue;
        // store keys prefixed by section for clarity (e.g. contact_email)
        $name = trim($key);
        upsert_setting($conn, $name, $value);
    }

    // If there's a file for system_logo
    if (!empty($_FILES['system_logo']) && $_FILES['system_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/settings';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $orig = basename($_FILES['system_logo']['name']);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $safe = 'logo_' . uniqid() . ($ext ? '.' . $ext : '');
        $dest = $uploadDir . '/' . $safe;
        if (move_uploaded_file($_FILES['system_logo']['tmp_name'], $dest)) {
            upsert_setting($conn, 'system_logo', 'uploads/settings/' . $safe);
        }
    }

    // Redirect back to section anchor using mapping so anchors match admin page IDs
    $anchorMap = [
        'media' => 'media-gallery',
        'hero' => 'hero-section',
        'contact' => 'contact-info',
        'general' => 'site-settings',
        'about' => 'about',
        'barangay' => 'barangay-management'
    ];

    $anchorId = isset($anchorMap[$section]) ? $anchorMap[$section] : $section;
    $msg = 'saved';
    $qs = http_build_query(['saved' => 1, 'section' => $section, 'msg' => $msg]);
    header('Location: /admin-settings.php?' . $qs . '#'. $anchorId);
    exit;
}

// If not POST, redirect back
header('Location: /admin-settings.php');
exit;
