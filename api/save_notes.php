<?php
// Disable HTML error output and enable logging
ini_set('display_errors', 0); // Prevent HTML errors in response
ini_set('log_errors', 1);     // Enable error logging
ini_set('error_log', __DIR__ . '/php_errors.log'); // Log to a file in the api directory
error_reporting(E_ALL);       // Log all errors

// Set JSON response header
header('Content-Type: application/json');
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Start session for authentication
session_start();

// Include database connection
require_once __DIR__ . '/../../includes/db.php'; // Adjust path based on your structure

// Check if database connection is established
if (!isset($conn) || $conn->connect_error) {
    error_log("Database connection failed: " . ($conn->connect_error ?? 'Connection not initialized'));
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Authentication and authorization check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Read JSON input from the request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!isset($data['application_id']) || !isset($data['notes'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$application_id = filter_var($data['application_id'], FILTER_VALIDATE_INT);
$notes = trim($data['notes']);

if ($application_id === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid application ID']);
    exit();
}

// Verify that the application belongs to the employer's job
try {
    $stmt = $conn->prepare("
        SELECT a.id
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.id = ? AND j.employer_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ii", $application_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Application not found or unauthorized']);
        exit();
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error verifying application: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error during verification']);
    exit();
}

// Update notes in the database
try {
    $stmt = $conn->prepare("UPDATE applications SET notes = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("si", $notes, $application_id);
    $stmt->execute();
    
    if ($stmt->affected_rows >= 0) { // Allow 0 for unchanged notes
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        $stmt->close();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update notes']);
    }
} catch (Exception $e) {
    error_log("Error updating notes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error during update']);
    exit();
}
?>
