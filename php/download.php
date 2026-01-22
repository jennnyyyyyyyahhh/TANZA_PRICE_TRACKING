<?php
    session_start();
    require 'connection.php';

    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        exit("Access denied");
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        exit("Invalid request");
    }

    $permitId = (int)$_GET['id'];

    $stmt = $conn->prepare("SELECT file_path FROM business_permits WHERE id = ?");
    $stmt->bind_param("i", $permitId);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();

    if (!$file || empty($file['file_path'])) {
        http_response_code(404);
        exit("File not found");
    }

    $fileUrl = $file['file_path'];
    $fileName = basename($fileUrl);

    // Use file_get_contents to get the remote file
    $context = stream_context_create([
        'http' => ['header' => "User-Agent: PHP\r\n"]
    ]);
    $fileData = @file_get_contents($fileUrl, false, $context);

    if ($fileData === false) {
        http_response_code(404);
        exit("File not found on server");
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($fileData));

    echo $fileData;
    exit;
?>
