<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

$room_id = $_GET['room_id'] ?? null;
if (!$room_id) {
    echo json_encode(['status' => 'error', 'error' => 'No room_id provided']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = $input['code'] ?? '';
    $language = $input['language'] ?? 'javascript';
    $stmt = $conn->prepare("
        INSERT INTO interview_code_snippets (room_id, code, language)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE code = ?, language = ?, updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->bind_param("sssss", $room_id, $code, $language, $code, $language);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'updated_at' => date('Y-m-d H:i:s')]);
    } else {
        echo json_encode(['status' => 'error', 'error' => $conn->error]);
    }
    $stmt->close();
    exit();
}

$stmt = $conn->prepare("SELECT code, language, updated_at FROM interview_code_snippets WHERE room_id = ?");
$stmt->bind_param("s", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$snippet = $result->fetch_assoc();
$stmt->close();

if ($snippet) {
    echo json_encode([
        'status' => 'success',
        'code' => $snippet['code'],
        'language' => $snippet['language'],
        'updated_at' => $snippet['updated_at']
    ]);
} else {
    echo json_encode([
        'status' => 'success',
        'code' => '',
        'language' => 'javascript',
        'updated_at' => null
    ]);
}
?>