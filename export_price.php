<?php
// -------------------- CONFIG --------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '120'); // 2 minutes

require_once 'php/connection.php';

$dateFrom = $_POST['dateFrom'] ?? null;
$dateTo = $_POST['dateTo'] ?? null;
$useDateFilter = false;
// If both dates provided, validate and use them; otherwise export all surveys (no date filter)
if ($dateFrom && $dateTo) {
    // Validate date format (expecting YYYY-MM-DD)
    $d1 = DateTime::createFromFormat('Y-m-d', $dateFrom);
    $d2 = DateTime::createFromFormat('Y-m-d', $dateTo);
    if (!$d1 || !$d2 || $d1->format('Y-m-d') !== $dateFrom || $d2->format('Y-m-d') !== $dateTo) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(["error" => "Invalid date format. Use YYYY-MM-DD."]);
        exit;
    }

    // Ensure start <= end; if not, swap
    if ($d1 > $d2) {
        $tmp = $d1;
        $d1 = $d2;
        $d2 = $tmp;
    }

    $dateFrom = $d1->format('Y-m-d');
    $dateTo = $d2->format('Y-m-d');
    $useDateFilter = true;
} else {
    // Not using date filter; ensure variables are null
    $dateFrom = null;
    $dateTo = null;
    $useDateFilter = false;
}
$format = $_POST['format'] ?? 'pdf';

// -------- CHECK MODE (for pre-validation in frontend) -------- //
if (isset($_POST['check'])) {
    echo json_encode(["ok" => true]);
    exit;
}

// -------------------- FETCH DATA --------------------
// Build grouped export: products -> commodities under each product
$prodSql = "SELECT MIN(id) AS product_id, product_name FROM products GROUP BY product_name ORDER BY product_name";
$prodStmt = $conn->prepare($prodSql);
$prodStmt->execute();
$prodResult = $prodStmt->get_result();

$rows = [];

// Statements
$commoditiesSql = "SELECT DISTINCT product_commodity, source, calamity FROM products WHERE product_name = ? ORDER BY product_commodity";
$qtySql = "SELECT product_quantity FROM products_qty WHERE product_id = ? LIMIT 1";

if ($useDateFilter) {
    $modeSql = "SELECT price FROM surveys WHERE product_name = ? AND commodity_type = ? AND DATE(created_at) BETWEEN ? AND ? GROUP BY price ORDER BY COUNT(*) DESC LIMIT 1";
    $minmaxSql = "SELECT MIN(price) AS low, MAX(price) AS high FROM surveys WHERE product_name = ? AND commodity_type = ? AND DATE(created_at) BETWEEN ? AND ?";
} else {
    $modeSql = "SELECT price FROM surveys WHERE product_name = ? AND commodity_type = ? GROUP BY price ORDER BY COUNT(*) DESC LIMIT 1";
    $minmaxSql = "SELECT MIN(price) AS low, MAX(price) AS high FROM surveys WHERE product_name = ? AND commodity_type = ?";
}

$commStmt = $conn->prepare($commoditiesSql);
$qtyStmt = $conn->prepare($qtySql);
$modeStmt = $conn->prepare($modeSql);
$minmaxStmt = $conn->prepare($minmaxSql);

function fmt_price($p) {
    if ($p === null || $p === '') return '';
    return 'P ' . number_format((float)$p, 2);
}

while ($prod = $prodResult->fetch_assoc()) {
    $product_id = $prod['product_id'];
    $product_name = $prod['product_name'];

    // Product header row
    $rows[] = [
        'Commodity' => $product_name,
        'Specification' => '',
        'Prevailing' => '',
        'Low' => '',
        'High' => '',
        'Source of Supply' => '',
        'Weather Impact' => ''
    ];

    // Get specification (product_quantity) from products_qty
    $spec = '';
    $qtyStmt->bind_param('i', $product_id);
    $qtyStmt->execute();
    $qres = $qtyStmt->get_result();
    if ($qr = $qres->fetch_assoc()) {
        $spec = $qr['product_quantity'];
    }

    // Get commodities for this product
    $commStmt->bind_param('s', $product_name);
    $commStmt->execute();
    $commRes = $commStmt->get_result();
    while ($c = $commRes->fetch_assoc()) {
        $commodity = $c['product_commodity'];
        $source = $c['source'] ?? '';
        $calamity = $c['calamity'] ?? '';

        // Prevailing (mode)
        $prevailing = null;
        $modeStmt->bind_param('ssss', $product_name, $commodity, $dateFrom, $dateTo);
        $modeStmt->execute();
        $modeRes = $modeStmt->get_result();
        if ($mr = $modeRes->fetch_assoc()) {
            $prevailing = $mr['price'];
        }

        // Low and High
        $low = null; $high = null;
        $minmaxStmt->bind_param('ssss', $product_name, $commodity, $dateFrom, $dateTo);
        $minmaxStmt->execute();
        $mmRes = $minmaxStmt->get_result();
        if ($mm = $mmRes->fetch_assoc()) {
            $low = $mm['low'];
            $high = $mm['high'];
        }

        $rows[] = [
            'Commodity' => $commodity,
            'Specification' => $spec,
            'Prevailing' => fmt_price($prevailing),
            'Low' => fmt_price($low),
            'High' => fmt_price($high),
            'Source of Supply' => $source,
            'Weather Impact' => $calamity
        ];
    }
}

// -------------------- CSV EXPORT --------------------
if ($format === "csv") {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=price_data.csv");

    $output = fopen("php://output", "w");
    if (!empty($rows)) {
        $headers = array_keys($rows[0]);
        fputcsv($output, $headers);
        foreach ($rows as $r) {
            $line = [];
            foreach ($headers as $h) {
                $line[] = isset($r[$h]) ? $r[$h] : '';
            }
            fputcsv($output, $line);
        }
    } else {
        fputcsv($output, ['No data available']);
    }
    fclose($output);
    exit;
}

// -------------------- EXCEL EXPORT --------------------
if ($format === "excel") {
    if (!file_exists('vendor/autoload.php')) {
        http_response_code(500);
        header('Content-Type: application/json');
        $msg = "Missing PhpSpreadsheet library. Run Composer and upload the vendor/ folder.";
        error_log('[export_price] ' . $msg);
        echo json_encode(["error" => $msg]);
        exit;
    }

    require 'vendor/autoload.php';

    // Use user's Excel template if available, otherwise create spreadsheet and build header
    $templatePath = __DIR__ . '/Export Template/template.xlsx';
    $usingTemplate = false;
    if (file_exists($templatePath)) {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();
            $usingTemplate = true;
        } catch (Exception $e) {
            // fallback
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $usingTemplate = false;
        }
    } else {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $usingTemplate = false;
    }

    if (!$usingTemplate) {
        // Build document header rows (will occupy rows 1..7) and then table from row 9
        $logoPath = __DIR__ . '/IMG/IMG/logo.png';
        $titleLines = [
            ['Republic of the Philippines'],
            ['Province of Cavite'],
            ['MUNICIPALITY OF TANZA'],
            ['MUNICIPAL AGRICULTURE OFFICE'],
            ["Email: agriculture.lgutanza@gmail.com • (046) 230-7680"],
            ['PRICE MONITORING REPORT']
        ];

        // Merge across 7 columns (A..G) for header lines
        $rowIdx = 1;
        foreach ($titleLines as $i => $tl) {
            $cell = 'A' . $rowIdx;
            $sheet->setCellValue($cell, $tl[0]);
            $sheet->mergeCells('A' . $rowIdx . ':G' . $rowIdx);
            // Styling per line
            $style = $sheet->getStyle($cell);
            $align = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER;
            $style->getAlignment()->setHorizontal($align);
            $style->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            if ($i === 0 || $i === 1) {
                $style->getFont()->setName('Calibri')->setSize(18)->setBold(false);
            } elseif ($i === 2) {
                $style->getFont()->setName('Calibri')->setSize(18)->setBold(true);
                $sheet->getCell($cell)->setValue(strtoupper($tl[0]));
            } elseif ($i === 3) {
                $style->getFont()->setName('Cambria')->setSize(22)->setBold(true);
                $sheet->getCell($cell)->setValue(strtoupper($tl[0]));
            } elseif ($i === 4) {
                $style->getFont()->setName('Cambria')->setSize(16)->setBold(true);
                // make email a hyperlink in Excel
                $sheet->getCell('A' . $rowIdx)->getHyperlink()->setUrl('mailto:agriculture.lgutanza@gmail.com');
                $sheet->getStyle('A' . $rowIdx)->getFont()->getColor()->setRGB('0000FF');
                $sheet->getStyle('A' . $rowIdx)->getFont()->setUnderline(\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE);
            } elseif ($i === 5) {
                $style->getFont()->setName('Cambria')->setSize(20)->setBold(true);
            }
            $rowIdx++;
        }

        // If logo exists, insert image at A1
        if (file_exists(__DIR__ . '/IMG/IMG/logo.png')) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setPath(__DIR__ . '/IMG/IMG/logo.png');
            $drawing->setCoordinates('A1');
            $drawing->setHeight(185); // ~4.9cm
            $drawing->setOffsetX(5);
            $drawing->setWorksheet($sheet);
        }
    }

    // Table headers start at row 9
    if (!empty($rows)) {
        $headers = array_keys($rows[0]);
        // determine start row and whether to write header row
        $startRow = 9;
        $writeHeader = true;
        if ($usingTemplate) {
            // look for placeholder __TABLE_START__ first
            $found = false;
            foreach ($sheet->getRowIterator() as $rowIter) {
                $cellIter = $rowIter->getCellIterator();
                $cellIter->setIterateOnlyExistingCells(false);
                foreach ($cellIter as $cell) {
                    if ($cell && trim((string)$cell->getValue()) === '__TABLE_START__') {
                        $startRow = $cell->getRow();
                        $cell->setValue('');
                        $found = true;
                        break 2;
                    }
                }
            }
            if (!$found) {
                // find last non-empty row within first 200 rows (columns A..G)
                $lastRow = 0;
                for ($rchk = 1; $rchk <= 200; $rchk++) {
                    $has = false;
                    foreach (range('A', 'G') as $cc) {
                        $val = $sheet->getCell($cc . $rchk)->getValue();
                        if ($val !== null && trim((string)$val) !== '') { $has = true; break; }
                    }
                    if ($has) { $lastRow = $rchk; }
                }
                $startRow = max(9, $lastRow + 1);
                // template likely has header already, so don't write header row
                $writeHeader = false;
            }
        }
        // write header row if needed
        $col = 'A';
        if ($writeHeader) {
            foreach ($headers as $h) {
                $sheet->setCellValue($col . $startRow, $h);
                // header style: blue bg, white text, Cambria size 20
                $sheet->getStyle($col . $startRow)->getFont()->setName('Cambria')->setSize(20)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($col . $startRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('0D6EFD');
                $sheet->getStyle($col . $startRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $col++;
            }
        }

        // Set column widths to match template proportions
        $sheet->getColumnDimension('A')->setWidth(45); // Commodity
        $sheet->getColumnDimension('B')->setWidth(30); // Specification
        $sheet->getColumnDimension('C')->setWidth(30); // Prevailing
        $sheet->getColumnDimension('D')->setWidth(30); // Low
        $sheet->getColumnDimension('E')->setWidth(30); // High
        $sheet->getColumnDimension('F')->setWidth(40); // Source of Supply
        $sheet->getColumnDimension('G')->setWidth(40); // Weather Impact

        // If using template, map specific commodities to fixed cells per your mapping
        if ($usingTemplate) {
            // mapping: product_name => [ commodity_name => rowNumber ]
            $cellMap = [
                'Rice' => [
                    'NFA (regular milled)' => 9,
                    'NFA (well milled)' => 10
                ],
                'Low Commercial Rice' => [
                    'Special (Blue tag)' => 12,
                    'Premium (Yellow tag)' => 13,
                    'Well milled ( White tag)' => 14,
                    'Regular milled ( White tag)' => 15
                ],
                'CORN' => [
                    'Yellow Corn Grain' => 17,
                    'Yellow Corn Grits' => 18,
                    'Yellow Corn (Fresh)' => 19,
                    'White Corn Grain' => 20,
                    'White Corn Grits' => 21,
                    'White Corn (Fresh)' => 22
                ],
                'FISH' => [
                    'Bangus' => 24,
                    'Tilapia' => 25,
                    'Galunggong (local)' => 26,
                    'Galunggong (imported)' => 27,
                    'Alumahan' => 28,
                    'Sapsap' => 29,
                    'Tamban' => 30
                ],
                'DRIED FISH' => [
                    'Daing na galunggong' => 32,
                    'Labahita' => 33,
                    'Dilis' => 34,
                    'Tuyo (salinas)' => 35,
                    'Tuyo (tamban)' => 36
                ],
                'LIVESTOCK & POULTRY PRODUCTS' => [
                    'Beef rump' => 38,
                    'Beef brisket' => 39,
                    'Pork ham/kasim' => 40,
                    'Pork liempo' => 41,
                    'Whole chicken' => 42,
                    'Chicken egg' => 43
                ],
                'LOWLAND VEGETABLES' => [
                    'Ampalaya' => 45,
                    'Sitao' => 46,
                    'Pechay (native)' => 47,
                    'Squash' => 48,
                    'Eggplant' => 49,
                    'Tomato' => 50,
                    'Upo' => 51,
                    'Okra' => 52,
                    'Radish' => 53
                ],
                'HIGHLAND VEGETABLES' => [
                    'Cabbage (scorpio)' => 55,
                    'Carrots' => 56,
                    'Habitchuelas (Baguio beans)' => 57,
                    'Pechay (Baguio)' => 58,
                    'Chayote' => 59
                ],
                'SPICES' => [
                    'Onion Red' => 61,
                    'Onion Red (Imported)' => 62,
                    'Onion Yellow Grannex' => 63,
                    'Onion White (Imported)' => 64,
                    'Garlic (Imported)' => 65,
                    'Garlic (Native)' => 66,
                    'Ginger (Native)' => 67,
                    'Chili (Panigang)' => 68,
                    'Chilli (Labuyo)' => 69
                ],
                'ROOTCROPS' => [
                    'Sweet Potato' => 71,
                    'Potato' => 72,
                    'Cassava' => 73,
                    'Taro (Gabi)' => 74
                ],
                'FRUITS' => [
                    'Calamansi' => 76,
                    'Banana (Lakatan)' => 77,
                    'Banana (Latundan)' => 78,
                    'Papaya' => 79,
                    'Watermelon' => 80,
                    'Melon' => 81,
                    'Mango (Carabao)' => 82
                ],
                'OTHER BASIC COMMODITIES' => [
                    'Sugar, Refined' => 84,
                    'Sugar, Washed' => 85,
                    'Sugar, Brown (raw)' => 86,
                    'Cooking oil (palm)' => 87,
                    'Cooking oil (palm) 2' => 88
                ]
            ];

            // Prepare stmt to get source/calamity for a given product & commodity
            $infoStmt = $conn->prepare("SELECT source, calamity FROM products WHERE product_name = ? AND product_commodity = ? LIMIT 1");

            foreach ($cellMap as $productName => $commodityMap) {
                foreach ($commodityMap as $commName => $rowNum) {
                    // Prevailing
                    if ($useDateFilter) {
                        $modeStmt->bind_param('ssss', $productName, $commName, $dateFrom, $dateTo);
                    } else {
                        $modeStmt->bind_param('ss', $productName, $commName);
                    }
                    $modeStmt->execute();
                    $mres = $modeStmt->get_result();
                    $prevailing = null;
                    if ($mr = $mres->fetch_assoc()) { $prevailing = $mr['price']; }

                    // Low/High
                    if ($useDateFilter) {
                        $minmaxStmt->bind_param('ssss', $productName, $commName, $dateFrom, $dateTo);
                    } else {
                        $minmaxStmt->bind_param('ss', $productName, $commName);
                    }
                    $minmaxStmt->execute();
                    $mmr = $minmaxStmt->get_result();
                    $low = $high = null;
                    if ($mm = $mmr->fetch_assoc()) { $low = $mm['low']; $high = $mm['high']; }

                    // Source & Weather from products table
                    $infoStmt->bind_param('ss', $productName, $commName);
                    $infoStmt->execute();
                    $infoR = $infoStmt->get_result();
                    $source = $calamity = '';
                    if ($ir = $infoR->fetch_assoc()) { $source = $ir['source'] ?? ''; $calamity = $ir['calamity'] ?? ''; }

                    // Write to specific cells: Prevailing C, Low D, High E, Source F, Weather G
                    $sheet->setCellValue('C' . $rowNum, ($prevailing !== null ? fmt_price($prevailing) : ''));
                    $sheet->setCellValue('D' . $rowNum, ($low !== null ? fmt_price($low) : ''));
                    $sheet->setCellValue('E' . $rowNum, ($high !== null ? fmt_price($high) : ''));
                    $sheet->setCellValue('F' . $rowNum, $source);
                    $sheet->setCellValue('G' . $rowNum, $calamity);
                }
            }

            // done with template mapping — now output file
            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename=price_data.xlsx");
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save("php://output");
            exit;
        }

        // write data rows starting after header (if header was written) or at startRow
        $r = $startRow + ($writeHeader ? 1 : 0);
        foreach ($rows as $row) {
            $isProductRow = empty($row['Prevailing']) && empty($row['Low']) && empty($row['High']) && empty($row['Source of Supply']) && empty($row['Weather Impact']) && $row['Specification'] === '';
            if ($isProductRow) {
                // merge across A..G and write product name centered
                $sheet->mergeCells('A' . $r . ':G' . $r);
                $sheet->setCellValue('A' . $r, $row['Commodity']);
                $sheet->getStyle('A' . $r)->getFont()->setName('Cambria')->setSize(20)->setBold(true)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A' . $r)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('0D6EFD');
                $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $r++;
                continue;
            }

            $c = 'A';
            foreach ($headers as $h) {
                $val = isset($row[$h]) ? $row[$h] : '';
                if (strpos($val, '(') !== false && class_exists('\PhpOffice\PhpSpreadsheet\RichText\RichText')) {
                    $rich = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
                    $parts = preg_split('/(\([^)]*\))/', $val, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                    foreach ($parts as $p) {
                        if (preg_match('/^\(.*\)$/', $p)) {
                            $run = $rich->createTextRun($p);
                            $run->getFont()->setItalic(true);
                        } else {
                            $rich->createText($p);
                        }
                    }
                    $sheet->setCellValue($c . $r, $rich);
                } else {
                    $sheet->setCellValue($c . $r, $val);
                }
                $c++;
            }
            $r++;
        }
    } else {
        $sheet->fromArray(['No data available'], NULL, 'A9');
    }

    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=price_data.xlsx");

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;
}

// -------------------- PDF EXPORT --------------------
if ($format === "pdf") {
    if (!file_exists('vendor/autoload.php')) {
        http_response_code(500);
        header('Content-Type: application/json');
        $msg = "Missing vendor autoload. Run Composer and upload the vendor/ folder.";
        error_log('[export_price] ' . $msg);
        echo json_encode(["error" => $msg]);
        exit;
    }

    require_once 'vendor/autoload.php';

    // Build HTML header and table (logo placed separately via TCPDF->Image for reliability)
    $logoPath = __DIR__ . '/IMG/IMG/logo.png';

    // Friendly date range text
    $dateRangeText = ($useDateFilter && $dateFrom && $dateTo) ? htmlspecialchars($dateFrom) . ' to ' . htmlspecialchars($dateTo) : 'All dates';

    // Build HTML for titles (kept simple so TCPDF renders reliably)
    $html = "<div style='text-align:center;'>";
    $html .= "<div style='font-size:14pt;font-weight:600;'>Republic of the Philippines</div>";
    $html .= "<div style='font-size:14pt;font-weight:600;'>Province of Cavite</div>";
    $html .= "<div style='font-size:16pt;font-weight:700;text-transform:uppercase;margin-top:4px;'>MUNICIPALITY OF TANZA</div>";
    $html .= "<div style='font-size:18pt;font-weight:700;text-transform:uppercase;margin-top:2px;'>MUNICIPAL AGRICULTURE OFFICE</div>";
    $html .= "<div style='font-size:10pt;margin-top:4px;'><a href='mailto:agriculture.lgutanza@gmail.com' style='color:blue;text-decoration:underline;'>agriculture.lgutanza@gmail.com</a> &#8226; (046) 230-7680</div>";
    $html .= "<div style='font-size:12pt;font-weight:700;margin-top:6px;text-transform:uppercase;'>PRICE MONITORING REPORT</div>";
    $html .= "<div style='font-size:10pt;margin-top:6px;'>Date Range: " . $dateRangeText . "</div>";
    $html .= "</div><div style='height:8px;'></div>";

    // Build HTML table with clearer styling; we'll add alternating row colors while building
    $html .= "<table border='1' cellpadding='4' cellspacing='0' width='100%' style='border-collapse:collapse;font-size:10pt;'>";
    $html .= "<colgroup>";
    $html .= "<col style='width:18%'/>"; // Commodity
    $html .= "<col style='width:22%'/>"; // Specification
    $html .= "<col style='width:12%'/>"; // Prevailing
    $html .= "<col style='width:12%'/>"; // Low
    $html .= "<col style='width:12%'/>"; // High
    $html .= "<col style='width:14%'/>"; // Source
    $html .= "<col style='width:10%'/>"; // Weather
    $html .= "</colgroup>";

    if (!empty($rows)) {
        // header row
        $html .= "<thead><tr style='background-color:#0D6EFD;color:#ffffff;font-weight:700;text-align:center;'>";
        foreach (array_keys($rows[0]) as $col) {
            $html .= "<th style='padding:6px 8px;'>" . htmlspecialchars($col) . "</th>";
        }
        $html .= "</tr></thead><tbody>";

        $rowIdx = 0;
        foreach ($rows as $r) {
            $rowIdx++;
            // detect product header row (product name only)
            $isProductRow = empty($r['Prevailing']) && empty($r['Low']) && empty($r['High']) && empty($r['Source of Supply']) && empty($r['Weather Impact']) && $r['Specification'] === '';
            if ($isProductRow) {
                $html .= "<tr style='background-color:#0D6EFD;color:#ffffff;font-weight:700;text-align:center;'><td colspan='7' style='padding:8px; font-size:12pt;'>" . htmlspecialchars($r['Commodity']) . "</td></tr>";
                continue;
            }

            $bg = ($rowIdx % 2 === 0) ? '#f8f9fa' : '#ffffff';
            $html .= "<tr style='background-color:" . $bg . ";'>";
            foreach ($r as $v) {
                $cell = htmlspecialchars($v);
                $cell = preg_replace('/\(([^)]*)\)/', '<i>($1)</i>', $cell);
                $html .= "<td style='padding:6px 8px;vertical-align:middle;'>" . $cell . "</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</tbody>";
    } else {
        $html .= "<tr><td colspan='7' style='padding:8px;text-align:center;'>No data available</td></tr>";
    }
    $html .= "</table>";

    // Use TCPDF (already required in composer.json) to generate PDF
    try {
        // Prevent PHP warnings/notices from being output and corrupting PDF
        ini_set('display_errors', 0);
        error_reporting(E_ERROR);

        // Clean any existing output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $pdf = new \TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Tanza Public Market');
        $pdf->SetTitle('Price Data Report');
        $pdf->SetSubject('Price Report');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        // Place logo using TCPDF Image so it reliably appears in the PDF
        if (isset($logoPath) && file_exists($logoPath)) {
            try {
                // x=12mm, y=12mm, width=34mm (height auto)
                $pdf->Image($logoPath, 12, 12, 34, 0, '', '', '', false, 300);
            } catch (Exception $ie) {
                error_log('[export_price] TCPDF image insertion failed: ' . $ie->getMessage());
            }
        }
        // Try to register and use Cambria/Calibri TTFs if supplied in fonts/ (optional)
        $preferredFont = 'dejavusans';
        $fontsDir = __DIR__ . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR;
        try {
            if (file_exists($fontsDir . 'Cambria.ttf')) {
                $cam = \TCPDF_FONTS::addTTFfont($fontsDir . 'Cambria.ttf', 'TrueTypeUnicode', '', 32);
                if ($cam) { $preferredFont = $cam; }
            } elseif (file_exists($fontsDir . 'Calibri.ttf')) {
                $cal = \TCPDF_FONTS::addTTFfont($fontsDir . 'Calibri.ttf', 'TrueTypeUnicode', '', 32);
                if ($cal) { $preferredFont = $cal; }
            }
        } catch (Exception $fe) {
            error_log('[export_price] font registration failed: ' . $fe->getMessage());
        }

        // Use preferred font for PDF body
        $pdf->SetFont($preferredFont, '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');
        // Output to browser as download
        // Ensure proper headers for binary PDF output
        header_remove();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=price_data.pdf');

        // Try preferred Unicode font, fall back to core font if unavailable
        try {
            $pdf->SetFont('dejavusans', '', 10);
        } catch (Exception $fe) {
            error_log('[export_price] TCPDF font load failed, falling back: ' . $fe->getMessage());
            $pdf->SetFont('helvetica', '', 10);
        }

        $pdf->Output('price_data.pdf', 'D');
        exit;
    } catch (Exception $e) {
        // Write detailed error to project log for diagnosis
        $logMsg = "[" . date('Y-m-d H:i:s') . "] PDF generation failed: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n";
        @file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'export_price_error.log', $logMsg, FILE_APPEND);

        http_response_code(500);
        header('Content-Type: application/json');
        $msg = 'PDF generation failed. Check export_price_error.log for details.';
        error_log('[export_price] ' . $e->getMessage());
        echo json_encode(["error" => $msg]);
        exit;
    }
}

// -------------------- INVALID FORMAT --------------------
http_response_code(400);
exit("Invalid export format requested.");
?>
