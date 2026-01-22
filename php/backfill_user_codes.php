<?php
// One-time migration: populate users.user_code for existing users lacking it.
// Usage (CLI): php backfill_user_codes.php
// Or open in browser (careful: run only on trusted host)

require_once __DIR__ . '/connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dryRun = false; // set to true to only preview

echo "Starting backfill of user_code...\n";

// Function to create next sequence for a given prefix
function next_seq_for_prefix($conn, $prefix) {
    $like = $prefix . '-%';
    $stmt = $conn->prepare("SELECT user_code FROM users WHERE user_code LIKE ? ORDER BY user_code DESC LIMIT 1");
    if (!$stmt) return 1;
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $stmt->bind_result($lastCode);
    $seq = 1;
    if ($stmt->fetch() && !empty($lastCode)) {
        $parts = explode('-', $lastCode);
        $lastNum = (int)end($parts);
        $seq = $lastNum + 1;
    }
    $stmt->close();
    return $seq;
}

// Select users without user_code
$res = $conn->query("SELECT id, created_at FROM users WHERE COALESCE(user_code,'') = '' OR user_code IS NULL ORDER BY created_at ASC");
if (!$res) {
    echo "Query failed: " . $conn->error . "\n";
    exit(1);
}

$updates = 0;
$conn->begin_transaction();
try {
    while ($row = $res->fetch_assoc()) {
        $id = (int)$row['id'];
        $created = $row['created_at'] ?: date('Y-m-d H:i:s');
        $prefix = date('Ym', strtotime($created));
        $seq = next_seq_for_prefix($conn, $prefix);
        $code = sprintf('%s-%04d', $prefix, $seq);

        // ensure uniqueness loop (very unlikely to collide but safe)
        $tries = 0;
        while ($tries < 10) {
            $chk = $conn->prepare("SELECT COUNT(*) FROM users WHERE user_code = ?");
            $chk->bind_param('s', $code);
            $chk->execute();
            $chk->bind_result($cnt);
            $chk->fetch();
            $chk->close();
            if ($cnt == 0) break;
            $seq++;
            $code = sprintf('%s-%04d', $prefix, $seq);
            $tries++;
        }

        if ($dryRun) {
            echo "Would set user id={$id} -> user_code={$code}\n";
        } else {
            $u = $conn->prepare("UPDATE users SET user_code = ? WHERE id = ?");
            $u->bind_param('si', $code, $id);
            if (!$u->execute()) throw new Exception("Failed to update user {$id}: " . $u->error);
            $u->close();
            echo "Set user id={$id} -> user_code={$code}\n";
        }
        $updates++;
    }
    if ($dryRun) {
        $conn->rollback();
        echo "Dry run completed. {$updates} users would be updated.\n";
    } else {
        $conn->commit();
        echo "Backfill completed. {$updates} users updated.\n";
    }
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";

?>