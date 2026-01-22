<?php
require_once __DIR__ . '/connection.php';

// basic auth / session check
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Accept optional filters via GET
$status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
$category = isset($_GET['category']) && $_GET['category'] !== '' ? $_GET['category'] : null;

$params = [];
$where = [];
if ($status) { $where[] = "status = ?"; $params[] = $status; }
if ($category) { $where[] = "category = ?"; $params[] = $category; }

$sql = "SELECT id, report_date, customer_name, stall_name, stall_location, category, description, status, created_at FROM reports";
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        // build types string
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    // fallback
    $res = $conn->query($sql);
}

// send CSV headers
$filename = 'reports_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
// header row
fputcsv($out, ['ID','Report Date','Customer Name','Vendor/Stall Name','Stall Location','Category','Description','Status','Created At']);

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        fputcsv($out, [
            $row['id'],
            $row['report_date'],
            $row['customer_name'],
            $row['stall_name'],
            $row['stall_location'],
            $row['category'],
            $row['description'],
            $row['status'],
            $row['created_at']
        ]);
    }
}

fclose($out);
exit;
