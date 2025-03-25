<?php
header("Access-Control-Allow-Origin: *"); // Allow requests from any origin
header("Content-Type: application/json"); // Set response content type

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "job_portal";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// SQL query
$sql = "SELECT id, name, email, COALESCE(role, 'N/A') AS role, COALESCE(user_name, 'N/A') AS user_name FROM users";
$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data); // Convert PHP array to JSON
} else {
    echo json_encode(["error" => "Failed to fetch data"]);
}

$conn->close();
?>
