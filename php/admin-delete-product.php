<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    require 'connection.php';

    if (!isset($_POST['id'])) exit('No ID provided.');

    $id = intval($_POST['id']);

    // Fetch product_name first
    $stmt = $conn->prepare("SELECT product_name FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $productName = $row['product_name'];
    } else {
        exit('Product not found.');
    }
    $stmt->close();

    // Begin transaction
    $conn->begin_transaction();
    try {
        // Delete from dependent table
        $stmt = $conn->prepare("DELETE FROM products_qty WHERE product_name = ?");
        $stmt->bind_param("s", $productName);
        $stmt->execute();
        $stmt->close();

        // Delete from products table
        $stmt = $conn->prepare("DELETE FROM products WHERE product_name = ?");
        $stmt->bind_param("s", $productName);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo 'success';
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo 'error: ' . $e->getMessage();
    }
    $conn->close();
?>
