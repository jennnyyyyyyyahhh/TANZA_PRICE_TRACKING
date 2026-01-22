<?php
require 'connection.php';

$id = intval($_GET['id']);
$product = $conn->query("SELECT id, product_name, product_commodity, image FROM products WHERE id = $id")->fetch_assoc();

$commodities = [];
if (!empty($product['product_commodity'])) {
  $commodities = explode(',', $product['product_commodity']);
}

$qRes = $conn->query("SELECT product_quantity FROM products_qty WHERE product_id = $id");
$quantities = [];
while ($row = $qRes->fetch_assoc()) {
  $quantities[] = $row['product_quantity'];
}

echo json_encode([
  'id' => $product['id'],
  'product_name' => $product['product_name'],
  'image' => $product['image'],
  'commodities' => $commodities,
  'quantities' => $quantities
]);
?>
