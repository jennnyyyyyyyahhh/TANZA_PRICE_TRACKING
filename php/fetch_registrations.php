    <?php
    date_default_timezone_set('Asia/Manila');

        require_once 'connection.php';



        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = 5; // rows per page
        $offset = ($page - 1) * $limit;

        if ($event_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid event ID']);
            exit;
        }

        // Count total
        $countQuery = $conn->prepare("SELECT COUNT(*) as total FROM event_registrations WHERE event_id = ?");
        $countQuery->bind_param("i", $event_id);
        $countQuery->execute();
        $total = $countQuery->get_result()->fetch_assoc()['total'];
        $countQuery->close();

        $total_pages = ceil($total / $limit);

        // Fetch paginated data
        $stmt = $conn->prepare("
            SELECT id, first_name, middle_name, last_name, email, phone_number, business_name, stall_number, registered_at 
            FROM event_registrations 
            WHERE event_id = ? 
            ORDER BY registered_at DESC 
            LIMIT ?, ?
        ");
        $stmt->bind_param("iii", $event_id, $offset, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $registrations = [];
        while ($row = $result->fetch_assoc()) {
            $registrations[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'data' => $registrations,
            'total_pages' => $total_pages,
            'current_page' => $page
        ]);

        $stmt->close();
        $conn->close();
    ?>
