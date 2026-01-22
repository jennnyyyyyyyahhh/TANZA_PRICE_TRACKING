<?php
require_once __DIR__ . '/connection.php'; // provides $conn (mysqli)

// Create barangays table if not exists
$createSql = "CREATE TABLE IF NOT EXISTS `barangays` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($createSql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'add') {
        $name = isset($_POST['barangay_name']) ? trim($_POST['barangay_name']) : '';

        if ($name === '') {
            header('Location: /admin-settings.php?error=missing_name#barangay-management');
            exit;
        }

        // Insert without code, then generate code based on the inserted id (BRG-###)
        $stmt = $conn->prepare("INSERT INTO barangays (`name`) VALUES (?)");
        if ($stmt) {
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $newId = $conn->insert_id;
            $stmt->close();

            if ($newId > 0) {
                $generatedCode = sprintf('BRG-%03d', $newId);
                $u = $conn->prepare("UPDATE barangays SET `code` = ? WHERE id = ?");
                if ($u) {
                    $u->bind_param('si', $generatedCode, $newId);
                    $u->execute();
                    $u->close();
                }
            }
        }

        // Redirect back with a friendly message so admin-settings can show a toast
        $msg = rawurlencode('Barangay successfully added');
        header('Location: /admin-settings.php?saved=1&section=barangay&msg=' . $msg . '#barangay-management');
        exit;
    }
}

header('Location: /admin-settings.php');
exit;
