<?php
require_once __DIR__ . '/connection.php';

// If form submitted to update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = isset($_POST['barangay_name']) ? trim($_POST['barangay_name']) : '';
    if ($id > 0 && $name !== '') {
        // Only update the name; code is auto-generated and should not be edited manually
        $stmt = $conn->prepare('UPDATE barangays SET `name` = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('si', $name, $id);
            $stmt->execute();
            $stmt->close();
        }
    }
    header('Location: /admin-settings.php?saved=barangay#barangay-management');
    exit;
}

// Otherwise show edit form for the given id (id is posted as a single-field form from admin page)
$id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if ($id <= 0) {
    header('Location: /admin-settings.php?error=invalid_id#barangay-management');
    exit;
}

$row = null;
$stmt = $conn->prepare('SELECT id, code, name FROM barangays WHERE id = ? LIMIT 1');
if ($stmt) {
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
}

if (!$row) {
    header('Location: /admin-settings.php?error=not_found#barangay-management');
    exit;
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edit Barangay</title>
</head>
<body>
    <h2>Edit Barangay</h2>
    <form method="POST" action="update_barangay.php">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
        <div>
            <label>Barangay Name</label>
            <input type="text" name="barangay_name" value="<?php echo htmlspecialchars($row['name']); ?>">
        </div>
        <!-- Barangay code is auto-generated and cannot be edited here -->
        <div>
            <button type="submit">Save</button>
            <a href="/admin-settings.php#barangay-management">Cancel</a>
        </div>
    </form>
</body>
</html>
