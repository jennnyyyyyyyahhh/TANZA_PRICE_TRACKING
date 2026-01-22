<?php
require_once '../pehp/auth_check.php';
require_once '../pehp/config.php'; // Include database configuration

checkAuth('admin');

$loggedInUsername = $_SESSION['username'] ?? 'Guest';

// Fetch products from the database
$products = [];
try {
    $stmt = $pdo->query("SELECT product_id, product_name, price, stock, image_path FROM products ORDER BY product_name ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching products on dashboard load: " . $e->getMessage());
    // Optionally, display an error message to the admin if this initial load fails
    // This will appear on page load, not for AJAX failures which are handled by JS
    // $errorMessage = "Could not load products initially. Database error: " + $e->getMessage();
}

// Fetch Overall Total Sales and Total Orders from the total_summary table
$totalSalesOverall = 0;
$totalOrdersOverall = 0;
try {
    $stmt = $pdo->query("SELECT grand_total_sales, total_order_count FROM total_summary WHERE id = 1");
    $statsOverall = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalSalesOverall = $statsOverall['grand_total_sales'] ?? 0;
    $totalOrdersOverall = $statsOverall['total_order_count'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching overall dashboard stats from total_summary: " . $e->getMessage());
    $totalSalesOverall = 0;
    $totalOrdersOverall = 0;
}

// Fetch Distributor Sales and Orders (still based on completed payments)
$distributorSales = 0;
$distributorOrders = 0;
try {
    $stmt = $pdo->prepare("
        SELECT
            SUM(p.total_amount) AS total_sales,
            COUNT(p.payment_id) AS total_orders
        FROM
            payments p
        JOIN
            users u ON p.user_id = u.user_id
        WHERE
            p.payment_status = 'completed' AND u.user_role = 'distributor'
    ");
    $stmt->execute();
    $distributorStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $distributorSales = $distributorStats['total_sales'] ?? 0;
    $distributorOrders = $distributorStats['total_orders'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching distributor stats: " . $e->getMessage());
    $distributorSales = 0;
    $distributorOrders = 0;
}

// Fetch Reseller Sales and Orders (still based on completed payments)
$resellerSales = 0;
$resellerOrders = 0;
try {
    $stmt = $pdo->prepare("
        SELECT
            SUM(p.total_amount) AS total_sales,
            COUNT(p.payment_id) AS total_orders
        FROM
            payments p
        JOIN
            users u ON p.user_id = u.user_id
        WHERE
            p.payment_status = 'completed' AND u.user_role = 'reseller'
    ");
    $stmt->execute();
    $resellerStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $resellerSales = $resellerStats['total_sales'] ?? 0;
    $resellerOrders = $resellerStats['total_orders'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching reseller stats: " . $e->getMessage());
    $resellerSales = 0;
    $resellerOrders = 0;
}

// Fetch all orders for the admin order list (removed payment_status filter here)
$allOrders = [];
try {
    $stmt = $pdo->query("
        SELECT
            p.payment_id AS order_id,
            p.total_amount AS total,
            p.order_status AS status,
            p.payment_date AS date,
            COALESCE(u.username, 'Guest') AS customer_username,
            COALESCE(u.user_role, 'Guest') AS customer_role
        FROM
            payments p
        LEFT JOIN
            users u ON p.user_id = u.user_id
        ORDER BY
            p.payment_date DESC
    ");
    $allOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching all orders for admin dashboard: " . $e->getMessage());
    $allOrders = [];
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    body { display: flex; min-height: 100vh; background: #e6f2fb; }

    .sidebar {
      width: 230px;
      background:  #90D0F2;
      color: white;
      padding: 20px;
      position: fixed;
      height: 100%;
      top: 0;
      left: 0;
      overflow-y: auto;
    }

    .sidebar h2 { margin-bottom: 20px; }
    .sidebar a {
      color: #fdfeff;
      text-decoration: none;
      display: block;
      margin: 15px 0;
    }
    .sidebar a:hover { color: #ecf0f1; }

    .main {
      margin-left: 230px;
      padding: 20px;
      width: 100%;
      box-sizing: border-box;
    }

    .header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: #90D0F2;
      padding-bottom: 10px;
      border-bottom: 1px solid #ccc;
      margin-bottom: 20px;
    }

    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 20px;
      margin: 20px 0;
    }

    .card {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .card h3 { font-size: 16px; color: #333; }
    .card p { font-size: 24px; font-weight: bold; margin-top: 10px; }

    .section { margin: 30px 0; }
    .section h2 { margin-bottom: 15px; }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      table-layout: fixed;
    }

    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th { background: #f1f1f1; }

    .btn {
      padding: 5px 10px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .edit-btn { background-color: #27ae60; color: white; }
    .delete-btn { background-color: #e74c3c; color: white; }
    .add-btn { background-color: #3498db; color: white; margin-bottom: 10px; }

    input[type="text"], select {
      padding: 8px;
      width: 100%;
      max-width: 100px; /* This was an issue, resetting in next media query for table inputs */
      margin-bottom: 10px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }
    textarea { /* This style is now irrelevant for description in modals but kept for other uses if any */
      width: calc(100% - 20px);
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      box-sizing: border-box;
      resize: vertical;
      min-height: 80px;
    }

    /* Modal Styles */
    .modal {
      display: none; /* Hidden by default */
      position: fixed; /* Stay in place */
      z-index: 1002; /* Sit on top */
      left: 0;
      top: 0;
      width: 100%; /* Full width */
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.4);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: #fefefe;
      margin: auto;
      padding: 20px;
      border: 1px solid #888;
      width: 80%;
      max-width: 500px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      position: relative;
    }

    .modal-content h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #333;
    }

    .modal-content label {
      display: block;
      margin-bottom: 8px;
      font-weight: bold;
    }

    .modal-content input[type="text"],
    .modal-content input[type="number"],
    .modal-content input[type="file"] {
      width: calc(100% - 20px);
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      box-sizing: border-box;
    }

    .modal-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }

    .modal-buttons button {
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
    }

    .modal-cancel-btn {
      background-color: #f44336;
      color: white;
      border: none;
    }

    .modal-add-btn, .modal-update-btn { /* Added .modal-update-btn */
      background-color: #4CAF50;
      color: white;
      border: none;
    }
    .product-image-thumb {
      width: 50px; /* Small thumbnail size */
      height: 50px;
      object-fit: cover;
      border-radius: 5px;
      margin-right: 10px;
      vertical-align: middle;
    }
    .table-image-cell {
        display: flex;
        align-items: center;
    }

    /* Styles for Status dropdown */
    .status-select {
        padding: 5px;
        border-radius: 5px;
        border: 1px solid #ccc;
        min-width: 120px; /* Adjust as needed */
    }


    @media (max-width: 768px) {
      .main { margin-left: 0; }
      .sidebar { display: none; }

      .header-content {
        flex-direction: column;
        align-items: flex-start;
      }
      table, thead, tbody, th, td, tr {
          display: block;
      }
      thead tr {
          position: absolute;
          top: -9999px;
          left: -9999px;
      }
      tr { border: 1px solid #ccc; margin-bottom: 10px; }
      td {
          border: none;
          border-bottom: 1px solid #eee;
          position: relative;
          padding-left: 50%;
          text-align: right;
      }
      td:before {
          position: absolute;
          top: 6px;
          left: 6px;
          width: 45%;
          padding-right: 10px;
          white-space: nowrap;
          text-align: left;
          font-weight: bold;
      }
      /* Specific labels for mobile table columns */
      #order-table-admin td:nth-of-type(1):before { content: "Order ID:"; }
      #order-table-admin td:nth-of-type(2):before { content: "Customer:"; }
      #order-table-admin td:nth-of-type(3):before { content: "Role:"; }
      #order-table-admin td:nth-of-type(4):before { content: "Total (₱):"; }
      #order-table-admin td:nth-of-type(5):before { content: "Status:"; }
      #order-table-admin td:nth-of-type(6):before { content: "Date:"; }
    }
  </style>
</head>
<body>

  <div class="sidebar">
    <h2>JSKIN BEAUTY</h2>
    <a href="admindashboard.php">Dashboard</a>
    <a href="salesss monitoring (admin side).php">Sales</a> <!-- Corrected extension -->
    <a href="accmanagement admin.php">Account</a>
    <a href="adminside.php">Applicants</a>
    <a href="approvelist.html">Employee</a>
    <a href="../pehp/logout.php">Logout</a>
  </div>

  <div class="main">
    <div class="header-content">
      <h1>Admin Dashboard</h1>
      <p>Welcome back, <?php echo htmlspecialchars($loggedInUsername); ?>!</p>
    </div>

    <div class="cards">
      <div class="card">
        <h3>Total Sales</h3>
        <p>₱<?php echo number_format($totalSalesOverall, 2); ?></p>
      </div>
      <div class="card">
        <h3>Total Orders</h3>
        <p><?php echo htmlspecialchars($totalOrdersOverall); ?></p>
      </div>
    </div>

    <div class="section">
      <h2>Product List</h2>
      <button class="btn add-btn" onclick="openAddProductModal()">Add Product</button>
      <table>
        <colgroup>
          <col style="width: 200px; min-width:180px;"> <!-- Adjusted width -->
          <col style="width: 100px;">
          <col style="width: 80px;">
          <col style="width: 120px;">
          <col style="width: 150px;"> <!-- New column for Image -->
        </colgroup>
        <thead>
          <tr>
            <th>Name</th>
            <th>Price (₱)</th>
            <th>Stock</th>
            <th>Image</th> <!-- New column header -->
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="product-table">
          <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
              <tr data-id="<?php echo htmlspecialchars($product['product_id']); ?>">
                <td data-field="product_name"><?php echo htmlspecialchars($product['product_name']); ?></td>
                <td data-field="price"><?php echo htmlspecialchars($product['price']); ?></td>
                <td data-field="stock"><?php echo htmlspecialchars($product['stock']); ?></td>
                <td class="table-image-cell" data-field="image_path" data-full-path="<?php echo htmlspecialchars($product['image_path'] ? '../' . $product['image_path'] : ''); ?>">
                    <?php if ($product['image_path']): ?>
                        <img src="../<?php echo htmlspecialchars($product['image_path']); ?>" alt="Product Image" class="product-image-thumb">
                    <?php else: ?>
                        No Image
                    <?php endif; ?>
                </td>
                <td>
                  <button class="btn edit-btn" onclick="openEditProductModal(this)">Edit</button>
                  <button class="btn delete-btn" onclick="deleteProduct(this)">Delete</button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5" style="text-align: center;">No products found. Add a new product!</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <h2>Distributor</h2>
    <div class="cards">
      <div class="card">
        <h3>Total Sales</h3>
        <p>₱<?php echo number_format($distributorSales, 2); ?></p>
      </div>
      <div class="card">
        <h3>Total Orders</h3>
        <p><?php echo htmlspecialchars($distributorOrders); ?></p>
      </div>
    </div>

    <h2>Reseller</h2>
    <div class="cards">
      <div class="card">
        <h3>Total Sales</h3>
        <p>₱<?php echo number_format($resellerSales, 2); ?></p>
      </div>
      <div class="card">
        <h3>Total Orders</h3>
        <p><?php echo htmlspecialchars($resellerOrders); ?></p>
      </div>
    </div>

    <div class="section">
      <h2>Order List</h2>
      <input type="text" id="orderSearch" placeholder="Search orders..." onkeyup="filterOrders()" />
      <table>
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Role</th> <!-- New column for Customer Role -->
            <th>Total (₱)</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody id="order-table-admin"> <!-- Changed ID for clarity -->
          <?php if (!empty($allOrders)): ?>
            <?php foreach ($allOrders as $order): ?>
              <tr data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>">
                <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                <td><?php echo htmlspecialchars($order['customer_username']); ?></td>
                <td><?php echo htmlspecialchars($order['customer_role']); ?></td>
                <td>₱<?php echo number_format($order['total'], 2); ?></td>
                <td>
                  <select class="status-select" onchange="updateOrderStatus(this.value, <?php echo htmlspecialchars($order['order_id']); ?>)">
                    <option value="Pending" <?php echo ($order['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="Processing" <?php echo ($order['status'] == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                    <option value="Shipped" <?php echo ($order['status'] == 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
                    <option value="Delivered" <?php echo ($order['status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                    <option value="Cancelled" <?php echo ($order['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                  </select>
                </td>
                <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($order['date']))); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" style="text-align: center;">No orders found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add Product Modal -->
  <div id="addProductModal" class="modal">
    <div class="modal-content">
      <h2>Add New Product</h2>
      <form id="addProductForm" enctype="multipart/form-data" action="../pehp/add_product.php" method="POST">
        <label for="productName">Product Name:</label>
        <input type="text" id="productName" name="product_name" required>

        <label for="productPrice">Price (₱):</label>
        <input type="number" id="productPrice" name="product_price" step="0.01" required>

        <label for="productStock">Stock:</label>
        <input type="number" id="productStock" name="product_stock" required>

        <label for="productImage">Product Image:</label>
        <input type="file" id="productImage" name="product_image" accept="image/*" required>

        <div class="modal-buttons">
          <button type="button" class="modal-cancel-btn" onclick="closeAddProductModal()">Cancel</button>
          <button type="submit" class="modal-add-btn">Add Product</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Product Modal -->
  <div id="editProductModal" class="modal">
    <div class="modal-content">
      <h2>Edit Product</h2>
      <form id="editProductForm" enctype="multipart/form-data" action="../pehp/update_product.php" method="POST">
        <input type="hidden" id="editProductId" name="product_id">
        <input type="hidden" id="editExistingImagePath" name="existing_image_path">

        <label for="editProductName">Product Name:</label>
        <input type="text" id="editProductName" name="product_name" required>

        <label for="editProductPrice">Price (₱):</label>
        <input type="number" id="editProductPrice" name="price" step="0.01" required>

        <label for="editProductStock">Stock:</label>
        <input type="number" id="editProductStock" name="stock" required>

        <label>Current Image:</label>
        <div style="text-align: center; margin-bottom: 15px;">
            <img id="currentProductImage" src="" alt="Current Product Image" style="max-width: 100px; max-height: 100px; border-radius: 5px; object-fit: cover;">
            <p id="noImageText" style="display: none; color: #777;">No image uploaded.</p>
        </div>


        <label for="editProductImage">Change Image (optional):</label>
        <input type="file" id="editProductImage" name="product_image" accept="image/*">

        <div class="modal-buttons">
          <button type="button" class="modal-cancel-btn" onclick="closeEditProductModal()">Cancel</button>
          <button type="submit" class="modal-update-btn">Update Product</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Function to fetch and render products
    async function fetchAndRenderProducts() {
      try {
        const response = await fetch('../pehp/get_products.php'); // New PHP file to fetch products

        // Log the raw response status and text
        console.log('Response Status for get_products.php:', response.status);
        const responseText = await response.text();
        console.log('Raw Response Text for get_products.php:', responseText);

        if (!response.ok) {
            // If the HTTP status code is not 2xx, throw an error
            let errorMessage = `HTTP error! Status: ${response.status}`;
            try {
                // Attempt to parse as JSON first if the server intended to send a JSON error
                const errorJson = JSON.parse(responseText);
                if (errorJson.message) {
                    errorMessage = errorJson.message; // Use the specific error message from PHP
                } else {
                    errorMessage += ` - ${responseText.substring(0, 200)}...`; // Fallback to raw text if not structured JSON
                }
            } catch (e) {
                errorMessage += ` - ${responseText.substring(0, 200)}...`; // If responseText is not JSON, use it as is
            }
            throw new Error(errorMessage);
        }

        let products;
        try {
            products = JSON.parse(responseText); // Try parsing as JSON
        } catch (e) {
            console.error('Failed to parse JSON response from get_products.php:', e);
            throw new Error('Server response from get_products.php was not valid JSON. Response: ' + responseText.substring(0, 200) + '...');
        }

        const productTableBody = document.getElementById('product-table');
        productTableBody.innerHTML = ''; // Clear existing rows

        if (products.length === 0) {
          productTableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No products found. Add a new product!</td></tr>';
          return;
        }

        products.forEach(product => {
          const row = document.createElement('tr');
          row.setAttribute('data-id', product.product_id);
          row.innerHTML = `
            <td data-field="product_name">${product.product_name}</td>
            <td data-field="price">${product.price}</td>
            <td data-field="stock">${product.stock}</td>
            <td class="table-image-cell" data-field="image_path" data-full-path="${product.image_path ? '../' + product.image_path : ''}">
                ${product.image_path ? `<img src="../${product.image_path}" alt="Product Image" class="product-image-thumb">` : 'No Image'}
            </td>
            <td>
              <button class="btn edit-btn" onclick="openEditProductModal(this)">Edit</button>
              <button class="btn delete-btn" onclick="deleteProduct(this)">Delete</button>
            </td>
          `;
          productTableBody.appendChild(row);
        });
      } catch (error) {
        console.error('Error fetching products:', error);
        
      }
    }


    async function deleteProduct(btn) {
      const row = btn.closest("tr");
      const productId = row.getAttribute('data-id');

      if (!confirm(`Are you sure you want to delete product ID ${productId}?`)) {
        return;
      }

      try {
        const response = await fetch('../pehp/delete_product.php', { // New PHP file for deleting
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ product_id: productId })
        });

        const result = await response.json();

        if (result.status === 'success') {
          alert(result.message);
          fetchAndRenderProducts(); // Refresh the list
        } else {
          alert('Error: ' + result.message);
        }
      } catch (error) {
        console.error('Error deleting product:', error);
        alert('An error occurred while deleting the product. Please try again.');
      }
    };

    function filterOrders() {
      const input = document.getElementById("orderSearch").value.toLowerCase();
      const rows = document.querySelectorAll("#order-table-admin tr"); // Changed ID to target admin order table

      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(input) ? "" : "none";
      });
    }

    // Function to update order status via AJAX
    async function updateOrderStatus(newStatus, orderId) {
        if (!confirm(`Are you sure you want to change order ${orderId} status to ${newStatus}?`)) {
            // Revert dropdown if user cancels
            // You might need to re-fetch orders or store original status to revert
            location.reload(); // Simple reload for now to revert
            return;
        }

        try {
            const response = await fetch('../pehp/update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ order_id: orderId, status: newStatus })
            });

            const result = await response.json();

            if (result.status === 'success') {
                alert(result.message);
                // No need to reload whole page, status is updated visually by dropdown
                // If other parts of the dashboard need refreshing, call respective fetch functions
            } else {
                alert('Error updating order status: ' + result.message);
                location.reload(); // Reload on error to show correct status
            }
        } catch (error) {
            console.error('Error updating order status:', error);
            alert('An unexpected error occurred while updating order status. Please try again.');
            location.reload(); // Reload on network error
        }
    }


    // --- Add Product Modal Functions ---
    function openAddProductModal() {
      document.getElementById('addProductModal').style.display = 'flex';
    }

    function closeAddProductModal() {
      document.getElementById('addProductModal').style.display = 'none';
      document.getElementById('addProductForm').reset(); // Clear form fields
    }

    document.getElementById('addProductForm').addEventListener('submit', async function(e) {
      e.preventDefault();

      const form = e.target;
      const formData = new FormData(form);

      // Log FormData contents before sending for verification
      console.log('FormData for Add Product (before send):');
      for (let pair of formData.entries()) {
          console.log(pair[0] + ': ' + pair[1]);
      }


      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
          alert(result.message);
          closeAddProductModal();
          fetchAndRenderProducts();
        } else {
          alert('Error: ' + result.message);
        }
      } catch (error) {
        console.error('Error adding product:', error);
        alert('An error occurred while adding the product. Please try again.');
      }
    });

    // --- Edit Product Modal Functions ---
    function openEditProductModal(buttonElement) {
      const row = buttonElement.closest('tr');
      const productId = row.getAttribute('data-id');
      // Retrieve text content directly from the data-field cells
      const productName = row.querySelector('[data-field="product_name"]').textContent.trim();
      const productPrice = row.querySelector('[data-field="price"]').textContent.trim();
      const productStock = row.querySelector('[data-field="stock"]').textContent.trim();
      const productImagePath = row.querySelector('[data-field="image_path"]').getAttribute('data-full-path');


      console.log('Editing Product Data from table row:', {
        productId,
        productName,
        productPrice,
        productStock,
        productImagePath,
      });


      // Populate the edit modal fields
      document.getElementById('editProductId').value = productId;
      document.getElementById('editProductName').value = productName;
      document.getElementById('editProductPrice').value = productPrice;
      document.getElementById('editProductStock').value = productStock;
      document.getElementById('editExistingImagePath').value = productImagePath; // Store existing path for backend

      const currentImageElement = document.getElementById('currentProductImage');
      const noImageTextElement = document.getElementById('noImageText');

      if (productImagePath) {
          currentImageElement.src = productImagePath;
          currentImageElement.style.display = 'block';
          noImageTextElement.style.display = 'none';
      } else {
          currentImageElement.src = '';
          currentImageElement.style.display = 'none';
          noImageTextElement.style.display = 'block';
      }

      // Clear any previously selected file in the file input
      document.getElementById('editProductImage').value = '';

      document.getElementById('editProductModal').style.display = 'flex';
    }

    function closeEditProductModal() {
      document.getElementById('editProductModal').style.display = 'none';
      document.getElementById('editProductForm').reset(); // Clear form fields
      // Also clear the image preview
      document.getElementById('currentProductImage').src = '';
      document.getElementById('currentProductImage').style.display = 'none';
      document.getElementById('noImageText').style.display = 'block';
    }

    document.getElementById('editProductForm').addEventListener('submit', async function(e) {
      e.preventDefault();

      const form = e.target;
      const formData = new FormData(form);

      // Log FormData contents before sending for verification
      console.log('FormData for Update Product (before send):');
      for (let pair of formData.entries()) {
          console.log(pair[0] + ': ' + pair[1]);
      }


      try {
        const response = await fetch(form.action, { // This action points to update_product.php
          method: 'POST',
          body: formData
        });

        // Log the raw response from update_product.php
        console.log('Update Product Response Status:', response.status);
        const responseText = await response.text();
        console.log('Update Product Raw Response Text:', responseText);

        if (!response.ok) {
            // Handle HTTP errors for update_product.php
            let errorMessage = `HTTP error! Status: ${response.status}`;
            try {
                const errorJson = JSON.parse(responseText);
                if (errorJson.message) {
                    errorMessage = errorJson.message;
                } else {
                    errorMessage += ` - ${responseText.substring(0, 200)}...`;
                }
            } catch (jsonError) {
                errorMessage += ` - ${responseText.substring(0, 200)}...`;
            }
            throw new Error(errorMessage);
        }

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (jsonParseError) {
            console.error('Failed to parse JSON response for update:', jsonParseError);
            throw new Error('Server response for update was not valid JSON. Response: ' + responseText.substring(0, 200) + '...');
        }


        if (result.status === 'success' || result.status === 'info') { // 'info' status is also a successful outcome for no changes
          alert(result.message);
          closeEditProductModal();
          fetchAndRenderProducts(); // Refresh the list after updating
        } else {
          alert('Error: ' + result.message);
        }
      } catch (error) {
        console.error('Error updating product:', error);
        alert('An error occurred while updating the product. Details: ' + error.message);
      }
    });


    // Close modal if user clicks outside of it (for both modals)
    window.onclick = function(event) {
      const addModal = document.getElementById('addProductModal');
      const editModal = document.getElementById('editProductModal');
      if (event.target == addModal) {
        closeAddProductModal();
      }
      if (event.target == editModal) {
        closeEditProductModal();
      }
    }

    // Initial load of products
    document.addEventListener('DOMContentLoaded', fetchAndRenderProducts);
  </script>
</body>
</html>
