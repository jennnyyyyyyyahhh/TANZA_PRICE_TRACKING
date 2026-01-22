<?php
// php/get-product-data.php
require_once 'connection.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
  echo json_encode(['error' => 'invalid id']);
  exit;
}

// 1) Get the product row (to obtain product_name and image)
$stmt = $conn->prepare("SELECT id, product_name, image, calamity FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
  echo json_encode(['error' => 'product not found']);
  exit;
}
$product = $res->fetch_assoc();
$productName = $product['product_name'];

// 2) Fetch ALL commodities with their individual images for this product_name
$cstmt = $conn->prepare("SELECT DISTINCT product_commodity, image FROM products WHERE product_name = ? AND product_commodity <> ''");
$cstmt->bind_param("s", $productName);
$cstmt->execute();
$cres = $cstmt->get_result();
$commodities = [];
$commodities_with_images = [];
while ($r = $cres->fetch_assoc()) {
  // split if commodity field stores comma-separated values (defensive)
  $parts = array_map('trim', explode(',', $r['product_commodity']));
  foreach ($parts as $p) {
    if ($p !== '') {
      $commodities[] = $p;
      $commodities_with_images[] = [
        'commodity' => $p,
        'image' => $r['image']
      ];
    }
  }
}
$commodities = array_values(array_unique($commodities));

// 3) Fetch ALL quantities for this product_id OR product_name (covers both schemas)
$qstmt = $conn->prepare("SELECT product_quantity FROM products_qty WHERE product_id = ? OR product_name = ?");
$qstmt->bind_param("is", $id, $productName);
$qstmt->execute();
$qres = $qstmt->get_result();
$quantities = [];
while ($r = $qres->fetch_assoc()) {
  if ($r['product_quantity'] !== '') $quantities[] = $r['product_quantity'];
}
$quantities = array_values(array_unique($quantities));

// Output
echo json_encode([
  'id' => (int)$product['id'],
  'product_name' => $productName,
  'image' => $product['image'],
  'calamity' => isset($product['calamity']) ? $product['calamity'] : null,
  'commodities' => $commodities,
  'commodities_with_images' => $commodities_with_images,
  'quantities' => $quantities
]);
