<?php
require_once __DIR__ . '/connection.php';

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$chains = ['waltermart','puregold','divimart','alphamart','savemore'];

// Gather all products (one card per product)
$out = [];
$prodStmt = $conn->prepare("SELECT id, product_name, product_commodity, image, calamity FROM products");
if ($prodStmt) {
    $prodStmt->execute();
    $prodStmt->bind_result($pid, $pname, $pcommodity, $pimage, $pcalamity);
    while ($prodStmt->fetch()) {
        $product_name = $pname;
        $product_commodity = $pcommodity ?: 'OTHER';
        $image = $pimage;

        // Count today's survey entries for this product
        $votes = 0;
        $countStmt = $conn->prepare("SELECT COUNT(*) FROM surveys WHERE product_name = ? AND DATE(created_at) = CURDATE()");
        if ($countStmt) {
            $countStmt->bind_param('s', $product_name);
            $countStmt->execute();
            $countStmt->bind_result($cnum);
            if ($countStmt->fetch()) $votes = (int)$cnum;
            $countStmt->close();
        }

        // Lowest price today for this product
        $pmin = null;
        $minStmt = $conn->prepare("SELECT MIN(price) FROM surveys WHERE product_name = ? AND DATE(created_at) = CURDATE()");
        if ($minStmt) {
            $minStmt->bind_param('s', $product_name);
            $minStmt->execute();
            $minStmt->bind_result($minp);
            if ($minStmt->fetch()) $pmin = $minp;
            $minStmt->close();
        }

        // Determine vendor type from top submitter for this product today (if any)
        $vendor_type = 'Public Market';
        $business_name = null;
        $top_user_id = null;
        $ust = $conn->prepare("SELECT user_id, COUNT(*) AS c FROM surveys WHERE product_name = ? AND DATE(created_at) = CURDATE() GROUP BY user_id ORDER BY c DESC LIMIT 1");
        if ($ust) {
            $ust->bind_param('s', $product_name);
            $ust->execute();
            $ust->bind_result($uid, $ucnt);
            if ($ust->fetch()) $top_user_id = $uid;
            $ust->close();
        }
        if (!empty($top_user_id)) {
            $bst = $conn->prepare("SELECT business_name, permit_type FROM business_permits WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
            if ($bst) {
                $bst->bind_param('i', $top_user_id);
                $bst->execute();
                $bst->bind_result($bizn, $permtype);
                if ($bst->fetch()) {
                    $business_name = $bizn;
                    $bn_low = strtolower((string)$bizn);
                    foreach ($chains as $cchain) {
                        if (strpos($bn_low, $cchain) !== false) { $vendor_type = ucfirst($cchain); break; }
                    }
                    if ($vendor_type === 'Public Market' && !empty($permtype)) {
                        $pt_low = strtolower($permtype);
                        foreach ($chains as $cchain) { if (strpos($pt_low, $cchain) !== false) { $vendor_type = ucfirst($cchain); break; } }
                    }
                }
                $bst->close();
            }
        }

        $out[] = [
            'product_id' => $pid,
            'product_name' => $product_name,
            'product_commodity' => $product_commodity,
            'votes' => $votes,
            'min_price' => $pmin !== null ? $pmin : null,
            'image' => $image,
            'vendor_type' => $vendor_type,
            'business_name' => $business_name,
            'calamity' => $pcalamity,
        ];
    }
    $prodStmt->close();
}

// Render HTML fragment
foreach ($out as $row) {
    $img = !empty($row['image']) ? esc($row['image']) : '/IMG/placeholder.png';
    $priceText = is_numeric($row['min_price']) ? number_format((float)$row['min_price'], 2) . '/kg' : 'N/A';
    echo '<div class="modern-price-card">';
    echo '<div class="modern-price-image"><img src="' . esc($img) . '" alt="' . esc($row['product_name']) . '"></div>';
    echo '<div class="modern-price-body">';
    echo '<h4>' . esc($row['product_name']) . '</h4>';
    echo '<p class="vendor">' . esc($row['vendor_type']) . ($row['business_name'] ? ' - ' . esc($row['business_name']) : '') . '</p>';
    echo '<p class="price">' . $priceText . '</p>';
    echo '</div></div>';
}

?>