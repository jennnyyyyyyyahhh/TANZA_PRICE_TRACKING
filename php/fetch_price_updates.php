<?php
header('Content-Type: application/json');
// Simple endpoint: for each product_name return the mode (most frequent) price,
// its count and the latest created_at for that price.
require_once __DIR__ . '/connection.php';

function fail($msg) {
    http_response_code(500);
    echo json_encode(['error' => $msg]);
    exit;
}

try {
    $products = [];
    $res = $conn->query("SELECT DISTINCT product_name FROM surveys");
    if ($res) {
        while ($r = $res->fetch_assoc()) $products[] = $r['product_name'];
        $res->free();
    }

    $out = [];
    $stmt = $conn->prepare("SELECT price, COUNT(*) AS cnt, MAX(created_at) AS latest_created_at FROM surveys WHERE product_name = ? GROUP BY price ORDER BY cnt DESC, latest_created_at DESC LIMIT 1");
    if (!$stmt) fail($conn->error);

    foreach ($products as $p) {
        $stmt->bind_param('s', $p);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($row = $r->fetch_assoc()) {
            // fetch a representative commodity_type for this product+price (most recent)
            $commodity = null;
            $stmt2 = $conn->prepare("SELECT commodity_type FROM surveys WHERE product_name = ? AND price = ? ORDER BY created_at DESC LIMIT 1");
            if ($stmt2) {
                $stmt2->bind_param('sd', $p, $row['price']);
                $stmt2->execute();
                $r2 = $stmt2->get_result();
                if ($r2 && ($rrow = $r2->fetch_assoc())) {
                    $commodity = $rrow['commodity_type'];
                }
                if ($r2) $r2->free();
                $stmt2->close();
            }

            $out[] = [
                'product_name' => $p,
                'price' => floatval($row['price']),
                'count' => intval($row['cnt']),
                'latest_created_at' => $row['latest_created_at'],
                'commodity_type' => $commodity
            ];
        }
        $r->free();
    }

    $stmt->close();
    echo json_encode(['data' => $out]);
} catch (Exception $e) {
    fail($e->getMessage());
}

?>
