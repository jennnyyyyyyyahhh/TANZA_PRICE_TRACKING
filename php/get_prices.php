<?php
require_once 'connection.php';

$commodity = isset($_GET['commodity']) ? $_GET['commodity'] : '';

$sql = "SELECT name, address, variety, price, quantity_type, quality, note, product_image, created_at 
        FROM surveys 
        WHERE commodity_type = ?
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $commodity);
$stmt->execute();
$result = $stmt->get_result();

$grouped = [];
$allPrices = []; // Store all prices for history

// Group by quantity_type
while ($row = $result->fetch_assoc()) {
    $qtyType = $row['quantity_type'];
    $grouped[$qtyType][] = $row;
    $allPrices[] = $row; // Keep all entries for price history
}

$bestDeals = [];
$uniqueByPrice = [];

// Compute best deals and deduplicate prices
foreach ($grouped as $qtyType => $entries) {
    $priceCount = [];
    foreach ($entries as $e) {
        $price = floatval($e['price']);
        $priceCount[$price] = ($priceCount[$price] ?? 0) + 1;
    }

    // Determine best deal
    $maxCount = max($priceCount);
    $topPrices = array_keys(array_filter($priceCount, fn($c) => $c === $maxCount));
    $bestDeals[$qtyType] = min($topPrices); // lowest if tie

    // Keep only one representative entry per unique price and move best deal first
    $seen = [];
    foreach ($entries as $e) {
        $p = floatval($e['price']);
        if (!isset($seen[$p])) {
            $seen[$p] = $e;
        }
    }
    $unique = array_values($seen);

    // Identify best deal price
    $bestPrice = $bestDeals[$qtyType];

    // Sort to move best deal first
    usort($unique, function($a, $b) use ($bestPrice) {
        if ($a['price'] == $bestPrice) return -1;
        if ($b['price'] == $bestPrice) return 1;
        return $a['price'] <=> $b['price'];
    });

    $uniqueByPrice[$qtyType] = $unique;

    }

echo json_encode([
    'grouped_prices' => $uniqueByPrice,
    'best_deals' => $bestDeals,
    'price_history' => $allPrices
]);

$conn->close();
?>
