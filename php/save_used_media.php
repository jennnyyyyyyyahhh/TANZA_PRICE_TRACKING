<?php
require_once __DIR__ . '/connection.php';

// Use prepared upsert to set landing_feature_media safely
if (!isset($_POST['id'])) {
	header("Location: ../admin-settings.php?error=noid");
	exit;
}

$id = intval($_POST['id']);

// Insert or update setting using INSERT ... ON DUPLICATE KEY
$stmt = $conn->prepare("INSERT INTO settings (`name`, `value`) VALUES ('landing_feature_media', ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ");
if ($stmt) {
	$stmt->bind_param('s', $id);
	$stmt->execute();
	$stmt->close();
}

header("Location: ../admin-settings.php?success=used");
exit;
?>
