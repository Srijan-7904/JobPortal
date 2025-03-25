<?php
session_start();
require '../includes/db.php';

// Redirect if not logged in or not an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employer') {
    header("Location: login.php");
    exit();
}

if (isset($_POST['job_id'])) {
    $job_id = $_POST['job_id'];

    // Remove the job from the database
    $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ? AND employer_id = ?");
    $stmt->bind_param("ii", $job_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();

    // Redirect to the dashboard after deletion
    header("Location: ../views/dashboard.php");
    exit();
} else {
    echo "No job ID provided.";
    exit();
}
