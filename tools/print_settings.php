<?php
require_once __DIR__ . '/../php/connection.php';
$res = $conn->query('SELECT name,value FROM settings');
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo $r['name'] . " => " . str_replace("\n", "\\n", $r['value']) . "\n";
    }
} else {
    echo "no settings table or empty\n";
}
?>