<?php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'jobseeker') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $hackathon_id = filter_input(INPUT_POST, 'hackathon_id', FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];

    // Check if already registered
    $check_stmt = $conn->prepare("SELECT id FROM hackathon_registrations WHERE hackathon_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $hackathon_id, $user_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        header("Location: hackathons.php?error=already_registered");
        exit();
    }
    $check_stmt->close();

    $stmt = $conn->prepare("INSERT INTO hackathon_registrations (hackathon_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $hackathon_id, $user_id);

    if ($stmt->execute()) {
        header("Location: hackathons.php?success=registered");
    } else {
        header("Location: hackathons.php?error=registration_failed");
    }
    $stmt->close();
}
exit();
?>