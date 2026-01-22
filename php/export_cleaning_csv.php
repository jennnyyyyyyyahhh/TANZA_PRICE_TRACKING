<?php
require_once __DIR__ . '/connection.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$status = isset($_GET['status']) && $_GET['status'] !== '' && $_GET['status'] !== 'all' ? $_GET['status'] : null;
$date = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : null;

$where = [];
$params = [];
if ($status) { $where[] = "cr.status = ?"; $params[] = $status; }
if ($date) { $where[] = "cr.preferred_date = ?"; $params[] = $date; }

$sql = "SELECT cr.id, CONCAT(u.first_name, ' ', u.last_name) AS vendor_name, u.email, cr.stall_number, cr.preferred_date, cr.preferred_time, cr.request_description, cr.status, cr.created_at
        FROM cleaning_requests cr
        JOIN users u ON cr.user_id = u.id";
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY cr.preferred_date DESC';

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

$filename = 'cleaning_requests_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
// header
fputcsv($out, ['Request ID','Vendor Name','Email','Stall Number','Preferred Date','Preferred Time','Description','Status','Created At']);

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        fputcsv($out, [
            $row['id'],
            $row['vendor_name'],
            $row['email'],
            $row['stall_number'],
            $row['preferred_date'],
            $row['preferred_time'],
            $row['request_description'],
            $row['status'],
            $row['created_at'] ?? ''
        ]);
    }
}

fclose($out);
exit;
