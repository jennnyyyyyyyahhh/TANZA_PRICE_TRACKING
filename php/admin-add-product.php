<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $commodities = $_POST['product_commodity'] ?? [];
    $quantities = $_POST['product_quantity'] ?? [];
    $calamity = isset($_POST['calamity']) ? trim($_POST['calamity']) : null;
    $default_image_path = '';

    // Handle default product image upload
    if (!empty($_FILES['product_image']['name'])) {
        $target_dir = "../uploads/products/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $file_name = time() . "_" . basename($_FILES['product_image']['name']);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            $default_image_path = "uploads/products/" . $file_name;
        }
    }

    // Ensure calamity column exists (MySQL 8+ supports IF NOT EXISTS)
    $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS calamity VARCHAR(255) DEFAULT NULL");

    // Insert base product (with calamity)
    $stmt = $conn->prepare("INSERT INTO products (product_name, product_commodity, image, calamity) VALUES (?, '', ?, ?)");
    $stmt->bind_param("sss", $product_name, $default_image_path, $calamity);
    $stmt->execute();
    $stmt->close();

    // Insert commodities as separate rows with individual images
    if (!empty($commodities)) {
        $stmt = $conn->prepare("INSERT INTO products (product_name, product_commodity, image, calamity) VALUES (?, ?, ?, ?)");
        
        $target_dir = "../uploads/products/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        foreach ($commodities as $index => $commodity) {
            $commodity = trim($commodity);
            if ($commodity !== '') {
                $commodity_image = $default_image_path; // Default to product image
                
                // Check if this commodity has its own image uploaded
                if (isset($_FILES['commodity_images']) && 
                    isset($_FILES['commodity_images']['name'][$index]) && 
                    !empty($_FILES['commodity_images']['name'][$index])) {
                    
                    $tmp_name = $_FILES['commodity_images']['tmp_name'][$index];
                    $orig_name = $_FILES['commodity_images']['name'][$index];
                    $file_name = time() . "_" . $index . "_" . basename($orig_name);
                    $target_file = $target_dir . $file_name;
                    
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $commodity_image = "uploads/products/" . $file_name;
                    }
                }
                
                $stmt->bind_param("ssss", $product_name, $commodity, $commodity_image, $calamity);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

    // Insert quantities in products_qty
    if (!empty($quantities)) {
        $stmt = $conn->prepare("INSERT INTO products_qty (product_name, product_quantity) VALUES (?, ?)");
        foreach ($quantities as $qty) {
            $qty = trim($qty);
            if ($qty !== '') {
                $stmt->bind_param("ss", $product_name, $qty);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

    $conn->close();
    echo "<script>
        sessionStorage.setItem('product_added', '1');
        window.location.href = '../admin-survey-form.php';
    </script>";
    exit;


}
?>
