<?php
session_start();
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['csrf_token']) || $input['csrf_token'] !== $csrf_token) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit();
    }

    $room_id = filter_var($input['room_id'] ?? '', FILTER_SANITIZE_STRING);
    $content = $input['content'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (!$room_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Room ID is required']);
        exit();
    }

    $stmt = $conn->prepare("
        INSERT INTO editor_sync (room_id, content, last_updated_by, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE content = ?, last_updated_by = ?, updated_at = NOW()
    ");
    $stmt->bind_param("ssisi", $room_id, $content, $user_id, $content, $user_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $room_id = filter_var($_GET['room_id'] ?? '', FILTER_SANITIZE_STRING);
    if (!$room_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Room ID is required']);
        exit();
    }

    $stmt = $conn->prepare("SELECT content, last_updated_by FROM editor_sync WHERE room_id = ?");
    $stmt->bind_param("s", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    echo json_encode($data ?: ['content' => '', 'last_updated_by' => null]);
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>