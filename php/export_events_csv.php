<?php
require_once __DIR__ . '/connection.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Not authenticated';
    exit;
}

$type = isset($_GET['type']) && $_GET['type'] !== '' && $_GET['type'] !== 'all' ? $_GET['type'] : null;
$status = isset($_GET['status']) && $_GET['status'] !== '' && $_GET['status'] !== 'all' ? $_GET['status'] : null;

$where = [];
$params = [];
if ($type) { $where[] = "type = ?"; $params[] = $type; }
if ($status) { $where[] = "status = ?"; $params[] = $status; }

$sql = "SELECT id, title, type, date, start_time, end_time, location, description, image_path, status, created_at FROM events";
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY date ASC';

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}

$filename = 'events_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
// header row
fputcsv($out, ['ID','Title','Type','Date','Start Time','End Time','Location','Description','Status','Created At']);

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        fputcsv($out, [
            $row['id'],
            $row['title'],
            $row['type'],
            $row['date'],
            $row['start_time'],
            $row['end_time'],
            $row['location'],
            $row['description'],
            $row['status'],
            $row['created_at']
        ]);
    }
}

fclose($out);
exit;
