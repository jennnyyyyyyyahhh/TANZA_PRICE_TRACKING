<?php
// Diagnostic endpoint to verify composer/vendor and export libraries availability
error_reporting(E_ALL);
ini_set('display_errors', 1);

$out = [];
$out['time'] = date('c');
$out['php_version'] = PHP_VERSION;
$out['script_dir'] = __DIR__;
$out['cwd'] = getcwd();

$vendorPath = __DIR__ . '/vendor/autoload.php';
$out['vendor_autoload_exists'] = file_exists($vendorPath);
if ($out['vendor_autoload_exists']) {
    $out['vendor_autoload_path'] = realpath($vendorPath);
}

// Try to include vendor/autoload.php and detect classes
if ($out['vendor_autoload_exists']) {
    try {
        require_once $vendorPath;
        $out['autoload_included'] = true;
    } catch (Throwable $e) {
        $out['autoload_included'] = false;
        $out['autoload_error'] = $e->getMessage();
    }
} else {
    $out['autoload_included'] = false;
}

$out['class_tcpdf_exists'] = class_exists('TCPDF');
$out['class_phpspreadsheet_exists'] = class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet');

// show minimal listing of vendor dir if present
if (is_dir(__DIR__ . '/vendor')) {
    $files = scandir(__DIR__ . '/vendor');
    $out['vendor_list_sample'] = array_values(array_slice($files, 0, 20));
}

header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT);
exit;

?>
