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
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
    $organizer = filter_input(INPUT_POST, 'organizer', FILTER_SANITIZE_STRING);
    $registration_deadline = filter_input(INPUT_POST, 'registration_deadline', FILTER_SANITIZE_STRING);
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

    $stmt = $conn->prepare("UPDATE hackathons SET title = ?, description = ?, date = ?, location = ?, organizer = ?, registration_deadline = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $title, $description, $date, $location, $organizer, $registration_deadline, $hackathon_id);

    if ($stmt->execute()) {
        header("Location: hackathons.php?success=updated");
    } else {
        header("Location: hackathons.php?error=update_failed");
    }
    $stmt->close();
}
exit();
?>