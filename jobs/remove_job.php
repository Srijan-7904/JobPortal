<?php
session_start();
require '../includes/db.php';

// Redirect if not logged in or not an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employer') {
    header("Location: login.php");
    exit();
}

if (isset($_POST['job_id'])) {
    $job_id = (int)$_POST['job_id']; // Cast to integer for safety
    $employer_id = $_SESSION['user_id'];

    // Begin a transaction to ensure atomicity
    $conn->begin_transaction();

    try {
        // Step 1: Delete related interview_sessions records
        $stmt = $conn->prepare("DELETE FROM interview_sessions WHERE job_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed for interview_sessions deletion: " . $conn->error);
        }
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $stmt->close();

        // Step 2: Delete related applications (assuming an applications table exists)
        $stmt = $conn->prepare("DELETE FROM applications WHERE job_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed for applications deletion: " . $conn->error);
        }
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $stmt->close();

        // Step 3: Delete the job
        $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ? AND employer_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed for jobs deletion: " . $conn->error);
        }
        $stmt->bind_param("ii", $job_id, $employer_id);
        $affected_rows = $stmt->execute() ? $stmt->affected_rows : 0;
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        if ($affected_rows > 0) {
            // Set a success message in session
            $_SESSION['success_message'] = "Job successfully removed.";
        } else {
            $_SESSION['error_message'] = "No job found with the provided ID or you lack permission.";
        }
    } catch (Exception $e) {
        // Roll back the transaction on error
        $conn->rollback();
        error_log("Error removing job ID $job_id: " . $e->getMessage());
        $_SESSION['error_message'] = "An error occurred while removing the job. Please try again later.";
    }

    // Redirect to the dashboard
    header("Location: ../views/dashboard.php");
    exit();
} else {
    $_SESSION['error_message'] = "No job ID provided.";
    header("Location: ../views/dashboard.php");
    exit();
}

// Close the database connection
$conn->close();
?>