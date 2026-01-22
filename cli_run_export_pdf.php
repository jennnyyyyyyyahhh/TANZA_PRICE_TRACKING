<?php
// CLI wrapper to run export_price.php and output result to stdout
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['dateFrom'] = '2025-01-01';
$_POST['dateTo'] = '2025-11-27';
$_POST['format'] = 'pdf';
include __DIR__ . '/export_price.php';
?>