<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "event_system"; // Your DB name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
?>
