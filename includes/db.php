<?php
$host = "localhost";
$user = "root";  // Default XAMPP MySQL username
$pass = "";      // Default XAMPP MySQL password (leave blank)
$db = "job_portal";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
