<?php
// Simple helper to verify composer autoload and library classes from webserver context
header('Content-Type: application/json');
$result = [
    'autoload_exists' => false,
    'iofactory_exists' => false,
    'tcpdf_exists' => false,
    'php_ini' => null,
    'sapi' => php_sapi_name(),
];

$autoload = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($autoload)) {
    $result['autoload_exists'] = true;
    require_once $autoload;
    $result['iofactory_exists'] = class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory');
    $result['tcpdf_exists'] = class_exists('TCPDF');
}

$result['php_ini'] = php_ini_loaded_file();

echo json_encode($result, JSON_PRETTY_PRINT);

?>
