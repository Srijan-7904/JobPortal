<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['room_id']) || !isset($_POST['message'])) {
    http_response_code(403);
    exit();
}

$room_id = filter_var($_POST['room_id'], FILTER_SANITIZE_STRING);
$message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);

$stmt = $conn->prepare("INSERT INTO cheating_logs (room_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("sis", $room_id, $user_id, $message);
$stmt->execute();
$stmt->close();

echo json_encode(['status' => 'success']);
?>