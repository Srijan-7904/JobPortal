<?php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employer') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $hackathon_id = filter_input(INPUT_POST, 'hackathon_id', FILTER_SANITIZE_NUMBER_INT);
    $employer_id = $_SESSION['user_id'];

    // Verify the hackathon belongs to the employer
    $check_stmt = $conn->prepare("SELECT employer_id FROM hackathons WHERE id = ?");
    $check_stmt->bind_param("i", $hackathon_id);
    $check_stmt->execute();
    $check_stmt->bind_result($db_employer_id);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($db_employer_id !== $employer_id) {
        header("Location: hackathons.php?error=unauthorized");
        exit();
    }

    $stmt = $conn->prepare("UPDATE hackathons SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $hackathon_id);

    if ($stmt->execute()) {
        header("Location: hackathons.php?success=marked_past");
    } else {
        header("Location: hackathons.php?error=mark_failed");
    }
    $stmt->close();
}
exit();
?>