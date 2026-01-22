<?php
$servername = "localhost";
$username = "u139077651_farmfresh";
$password = "Presyongtanza2025";
$database = "u139077651_farmfresh_db";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");
?>
