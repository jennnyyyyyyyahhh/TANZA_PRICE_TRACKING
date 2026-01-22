<?php
require_once '../config.php';

if (!isset($_POST['id'])) exit('No ID provided.');
$id = $_POST['id'];

$stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$id]);

echo 'success';
?>
