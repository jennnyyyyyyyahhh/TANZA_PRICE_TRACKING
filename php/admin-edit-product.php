<?php
error_reporting(E_ALL);
    ini_set('display_errors', 1);
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $product_name = trim($_POST['product_name']);
    $commodities = $_POST['product_commodity'] ?? [];
    $quantities = $_POST['product_quantity'] ?? [];
    $existing_commodity_images = $_POST['existing_commodity_images'] ?? [];
    $calamity = isset($_POST['calamity']) ? trim($_POST['calamity']) : null;
    $default_image_path = '';

    // Get the original product name for updating related records
    $stmt = $conn->prepare("SELECT product_name, image FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $original = $res->fetch_assoc();
    $old_product_name = $original['product_name'];
    $old_image = $original['image'];
    $stmt->close();

    // Handle default product image upload
    if (!empty($_FILES['product_image']['name'])) {
        $target_dir = "../uploads/products/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $file_name = time() . "_" . basename($_FILES['product_image']['name']);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            $default_image_path = "uploads/products/" . $file_name;
        }
    } else {
        $default_image_path = $old_image; // Keep old image if no new one uploaded
    }

    // Ensure calamity column exists
    $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS calamity VARCHAR(255) DEFAULT NULL");

    // Delete old products with this product_name
    $stmt = $conn->prepare("DELETE FROM products WHERE product_name = ?");
    $stmt->bind_param("s", $old_product_name);
    $stmt->execute();
    $stmt->close();

    // Delete old quantities
    $stmt = $conn->prepare("DELETE FROM products_qty WHERE product_name = ?");
    $stmt->bind_param("s", $old_product_name);
    $stmt->execute();
    $stmt->close();

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
                // Start with existing image if available, otherwise use default
                $commodity_image = isset($existing_commodity_images[$index]) && !empty($existing_commodity_images[$index]) 
                    ? $existing_commodity_images[$index] 
                    : $default_image_path;
                
                // Check if this commodity has a NEW image uploaded - only then replace
                if (isset($_FILES['commodity_images']) && 
                    isset($_FILES['commodity_images']['name'][$index]) && 
                    !empty($_FILES['commodity_images']['name'][$index]) &&
                    $_FILES['commodity_images']['error'][$index] === UPLOAD_ERR_OK) {
                    
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
    $product_name_encoded = urlencode($product_name); // encode for URL safety
    echo "<script>
        sessionStorage.setItem('product_updated', '1');
        window.location.href = '../admin-survey-form.php';
    </script>";
    exit;
}
?>
