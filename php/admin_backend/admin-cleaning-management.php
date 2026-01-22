<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'php/connection.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Pagination settings
$recordsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

// Handle cleaning status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['new_status'])) {
    $requestId = intval($_POST['request_id']);
    $newStatus = $_POST['new_status'] === 'completed' ? 'completed' : 'pending';

    $stmt = $conn->prepare("
        UPDATE cleaning_requests
        SET status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("si", $newStatus, $requestId);
    $stmt->execute();

    // Set session message for notification
    $_SESSION['notif_message'] = "Request #{$requestId} marked as " . ucfirst($newStatus) . ".";

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Filter logic
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$where = "1";
if ($filterStatus !== 'all') {
    $where .= " AND cr.status = '" . $conn->real_escape_string($filterStatus) . "'";
}

// Total records for pagination
$countQuery = "SELECT COUNT(*) AS total FROM cleaning_requests cr WHERE $where";
$totalRecords = $conn->query($countQuery)->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Main query: fetch paginated cleaning requests with vendor names
$query = "
    SELECT 
        cr.id,
        cr.user_id,
        CONCAT(u.first_name, ' ', u.last_name) AS vendor_name,
        u.email,
        cr.stall_number,
        cr.preferred_date,
        cr.preferred_time,
        cr.request_description,
        cr.status
    FROM cleaning_requests cr
    JOIN users u ON cr.user_id = u.id
    WHERE $where
    ORDER BY cr.preferred_date DESC
    LIMIT $recordsPerPage OFFSET $offset
";
$result = $conn->query($query);

// Dashboard stats
$totalRequests = $conn->query("SELECT COUNT(*) AS total FROM cleaning_requests")->fetch_assoc()['total'];
$pendingRequests = $conn->query("SELECT COUNT(*) AS total FROM cleaning_requests WHERE status='pending'")->fetch_assoc()['total'];
$completedRequests = $conn->query("SELECT COUNT(*) AS total FROM cleaning_requests WHERE status='completed'")->fetch_assoc()['total'];
?>
