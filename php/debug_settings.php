<?php
// Simple debug viewer for settings and media (use only temporarily)
require_once __DIR__ . '/connection.php';
header('Content-Type: text/plain; charset=utf-8');
echo "--- settings ---\n";
if ($res = $conn->query("SHOW TABLES LIKE 'settings'")) {
    if ($res->num_rows) {
        $rs = $conn->query("SELECT name, value FROM settings ORDER BY name");
        while ($r = $rs->fetch_assoc()) {
            echo $r['name'] . " => " . $r['value'] . "\n\n";
        }
    } else {
        echo "(no settings table)\n";
    }
}

echo "\n--- media (first 50) ---\n";
if ($res = $conn->query("SHOW TABLES LIKE 'media'")) {
    if ($res->num_rows) {
        $rs = $conn->query("SELECT id, filename, original_name, mime FROM media ORDER BY created_at DESC LIMIT 50");
        while ($r = $rs->fetch_assoc()) {
            echo "id=" . $r['id'] . " filename=" . $r['filename'] . " original=" . $r['original_name'] . " mime=" . $r['mime'] . "\n";
        }
    } else {
        echo "(no media table)\n";
    }
}

echo "\n--- end ---\n";

?>
