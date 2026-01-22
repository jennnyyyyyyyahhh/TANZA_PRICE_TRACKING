<?php
// Securely serve files from the uploads/ directory by filename (basename only)
// Usage: php/serve_upload.php?f=1764435761_...png
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configure uploads dir relative to this script
$uploadsDir = __DIR__ . '/../uploads/';

if (empty($_GET['f'])) {
    http_response_code(400);
    echo 'Missing file parameter.';
    exit;
}

$file = basename($_GET['f']); // prevent directory traversal
$fullPath = realpath($uploadsDir . $file);

// Ensure the file is inside the uploads directory
if ($fullPath === false || strpos($fullPath, realpath($uploadsDir)) !== 0) {
    http_response_code(400);
    echo 'Invalid file.';
    exit;
}

if (!is_file($fullPath) || !is_readable($fullPath)) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

// Determine MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $fullPath);
finfo_close($finfo);

// Only allow common image types for safety
$allowed = ['image/jpeg','image/png','image/gif','image/webp'];
if (!in_array($mime, $allowed)) {
    http_response_code(403);
    echo 'Forbidden file type.';
    exit;
}

// Serve file with correct headers and cache
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=86400');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

// Output file
readfile($fullPath);
exit;

?>