<?php
$host = "localhost";
$user = "root";  // Default XAMPP MySQL username
$pass = "";      // Default XAMPP MySQL password (leave blank)
$db = "job_portal";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    header('Content-Type: application/json'); // Ensure JSON response
    echo json_encode(['success' => false, 'error' => 'Database Connection Failed: ' . $conn->connect_error]);
    exit();
}
?>