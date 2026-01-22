<?php
$ch = curl_init('http://127.0.0.1:8000/export_price.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'dateFrom' => '2025-01-01',
    'dateTo' => '2025-11-27',
    'format' => 'pdf'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$res = curl_exec($ch);
$err = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);
if ($res === false) {
    echo "CURL ERROR: $err\n";
    exit(1);
}
file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'test_report.pdf', $res);
echo "Wrote test_report.pdf (" . ($info['size_download'] ?? '0') . " bytes)\n";
print_r($info);
?>