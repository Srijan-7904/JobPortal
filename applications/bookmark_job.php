<?php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'guest';

if ($user_role !== 'jobseeker') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

$job_id = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
if (!$job_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid job ID']);
    exit();
}

// Check if the job is already bookmarked
$stmt = $conn->prepare("SELECT id FROM bookmarked_jobs WHERE user_id = ? AND job_id = ?");
$stmt->bind_param("ii", $user_id, $job_id);
$stmt->execute();
$result = $stmt->get_result();
$is_bookmarked = $result->num_rows > 0;
$stmt->close();

if ($is_bookmarked) {
    // Remove bookmark
    $stmt = $conn->prepare("DELETE FROM bookmarked_jobs WHERE user_id = ? AND job_id = ?");
    $stmt->bind_param("ii", $user_id, $job_id);
    $success = $stmt->execute();
    $stmt->close();
    $message = 'Job removed from bookmarks';
    $new_state = false;
} else {
    // Add bookmark
    $stmt = $conn->prepare("INSERT INTO bookmarked_jobs (user_id, job_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $job_id);
    $success = $stmt->execute();
    $stmt->close();
    $message = 'Job bookmarked successfully';
    $new_state = true;
}

if ($success) {
    echo json_encode(['success' => true, 'message' => $message, 'bookmarked' => $new_state]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update bookmark']);
}
exit();