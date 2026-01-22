<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once 'php/connection.php';

    // Redirect if not logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }

    $userId = $_SESSION['user_id'];

    // get user and profile info
    $stmt = $conn->prepare("
        SELECT u.first_name, u.last_name, u.email, u.created_at,
            p.phone_number,  p.city, p.province, p.postal_code
        FROM users u
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE u.id = ? LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    // Pagination setup
    $recordsPerPage = 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $recordsPerPage;

    // Count total records
    $countQuery = "SELECT COUNT(*) AS total FROM business_permits";
    $countResult = $conn->query($countQuery);
    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $recordsPerPage);

    // Main query with LIMIT
    $query = "
        SELECT 
            bp.id,
            bp.permit_number,
            u.first_name,
            CONCAT(u.first_name, ' ', u.last_name) AS vendor_name,
            bp.business_name,
            bp.stall_number,
            bp.issue_date AS submit_date,
            bp.expiry_date,
            bp.verification_status AS status,
            bp.file_path,
            bp.file_name
        FROM business_permits bp
        JOIN users u ON bp.user_id = u.id
        ORDER BY bp.id ASC
        LIMIT $recordsPerPage OFFSET $offset
    ";



    // Handle POST actions (review or reject)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $permitId = intval($_POST['permit_id']);
        $action = $_POST['action_type'];
        $adminId = $userId; // logged-in admin
        $now = date('Y-m-d H:i:s');

        if ($action === 'review') {
            // Approve the permit
            $stmt = $conn->prepare("
                UPDATE business_permits 
                SET verification_status = 'approved',
                    verified_by = ?,
                    verified_at = ? 
                WHERE id = ?
            ");
            $stmt->bind_param("isi", $adminId, $now, $permitId);
            $stmt->execute();

            // redirect with success flag
            header("Location: " . $_SERVER['PHP_SELF'] . "?status_update=approved");
            exit;

        } elseif ($action === 'reject' && !empty($_POST['rejection_reason'])) {
            $reason = trim($_POST['rejection_reason']);
            $stmt = $conn->prepare("
                UPDATE business_permits 
                SET verification_status = 'rejected',
                    verified_by = ?,
                    verified_at = ?,
                    rejection_reason = ?
                WHERE id = ?
            ");
            $stmt->bind_param("issi", $adminId, $now, $reason, $permitId);
            $stmt->execute();

            // redirect with reject flag
            header("Location: " . $_SERVER['PHP_SELF'] . "?status_update=rejected");
            exit;
        }
    }



    // Get filter from query string
    $filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

    // Build WHERE clause
    $where = "1"; // default: all records
    if ($filterStatus !== 'all') {
        $where .= " AND bp.verification_status = '" . $conn->real_escape_string($filterStatus) . "'";
    }

    // Update count for pagination with filter
    $countQuery = "SELECT COUNT(*) AS total FROM business_permits bp WHERE $where";
    $countResult = $conn->query($countQuery);
    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $recordsPerPage);

    // Update main query with WHERE clause
    $query = "
        SELECT 
            bp.id,
            bp.permit_number,
            CONCAT(u.first_name, ' ', u.last_name) AS vendor_name,
            bp.business_name,
            bp.stall_number,
            bp.issue_date AS submit_date,
            bp.expiry_date,
            bp.verification_status AS status,
            bp.file_path,
            bp.file_name
        FROM business_permits bp
        JOIN users u ON bp.user_id = u.id
        WHERE $where
        ORDER BY bp.id ASC
        LIMIT $recordsPerPage OFFSET $offset
    ";
    $result = $conn->query($query);

    // Dashboard counts
    $totalQuery = $conn->query("SELECT COUNT(*) AS total FROM business_permits");
    $totalPermits = $totalQuery->fetch_assoc()['total'];

    $pendingQuery = $conn->query("SELECT COUNT(*) AS total FROM business_permits WHERE verification_status = 'pending'");
    $pendingPermits = $pendingQuery->fetch_assoc()['total'];

    $approvedQuery = $conn->query("SELECT COUNT(*) AS total FROM business_permits WHERE verification_status = 'approved'");
    $approvedPermits = $approvedQuery->fetch_assoc()['total'];

    $expiredQuery = $conn->query("SELECT COUNT(*) AS total FROM business_permits WHERE expiry_date < CURDATE()");
    $expiredPermits = $expiredQuery->fetch_assoc()['total'];

    $rejectedQuery = $conn->query("SELECT COUNT(*) AS total FROM business_permits WHERE verification_status = 'rejected'");
    $rejectedPermits = $rejectedQuery->fetch_assoc()['total'];

?>