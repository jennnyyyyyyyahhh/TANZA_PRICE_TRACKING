<?php
require_once __DIR__ . '/connection.php';
// include TCPDF
$tcpdfPath = __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'TCPDF library not found. Please run composer install or add TCPDF to vendor/tecnickcom/tcpdf.';
    exit;
}
require_once $tcpdfPath;

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Not authenticated';
    exit;
}

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
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}

// build HTML table
$html = '<h2>Reports Export</h2>';
$html .= '<table border="1" cellpadding="4">';
$html .= '<thead><tr style="background-color:#f2f2f2;"><th>ID</th><th>Report Date</th><th>Customer</th><th>Vendor/Stall</th><th>Location</th><th>Category</th><th>Description</th><th>Status</th><th>Created At</th></tr></thead>';
$html .= '<tbody>';

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        // escape
        $desc = htmlspecialchars($row['description']);
        $html .= '<tr>';
        $html .= '<td>' . $row['id'] . '</td>';
        $html .= '<td>' . $row['report_date'] . '</td>';
        $html .= '<td>' . htmlspecialchars($row['customer_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['stall_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['stall_location']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['category']) . '</td>';
        $html .= '<td>' . $desc . '</td>';
        $html .= '<td>' . htmlspecialchars($row['status']) . '</td>';
        $html .= '<td>' . $row['created_at'] . '</td>';
        $html .= '</tr>';
    }
}
$html .= '</tbody></table>';

// create TCPDF
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Tanza Public Market');
$pdf->SetTitle('Reports Export');
$pdf->SetSubject('Reports');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

// write HTML
$pdf->writeHTML($html, true, false, true, false, '');

$filename = 'reports_export_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'I');
exit;
