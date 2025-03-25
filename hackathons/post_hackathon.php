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

    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
    $organizer = filter_input(INPUT_POST, 'organizer', FILTER_SANITIZE_STRING);
    $registration_deadline = filter_input(INPUT_POST, 'registration_deadline', FILTER_SANITIZE_STRING);
    $employer_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO hackathons (title, description, date, location, organizer, registration_deadline, employer_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $title, $description, $date, $location, $organizer, $registration_deadline, $employer_id);

    if ($stmt->execute()) {
        header("Location: hackathons.php?success=1");
    } else {
        header("Location: hackathons.php?error=1");
    }
    $stmt->close();
}
exit();
?>