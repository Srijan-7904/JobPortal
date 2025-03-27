<?php
header('Content-Type: application/json');
require '../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

error_log("Received request: " . json_encode($data));

if ($action === 'create_session') {
    $user_id = $data['user_id'] ?? null;
    $employer_id = $data['employer_id'] ?? null;
    $job_id = $data['job_id'] ?? null;

    if (!$user_id || !$employer_id || !$job_id) {
        error_log("Missing parameters: " . json_encode($data));
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
        exit;
    }

    $check_stmt = $conn->prepare("SELECT room_id FROM interview_sessions WHERE user1_id = ? AND user2_id = ? AND job_id = ?");
    $check_stmt->bind_param("iii", $user_id, $employer_id, $job_id);
    $check_stmt->execute();
    $check_stmt->bind_result($existing_room_id);
    if ($check_stmt->fetch()) {
        $check_stmt->close();
        echo json_encode(['success' => true, 'room_id' => $existing_room_id, 'message' => 'Session already exists']);
        exit;
    }
    $check_stmt->close();

    $room_id = bin2hex(random_bytes(8));
    $stmt = $conn->prepare("INSERT INTO interview_sessions (room_id, user1_id, user2_id, job_id) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("siii", $room_id, $user_id, $employer_id, $job_id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'room_id' => $room_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}