<?php
session_start();
require '../includes/db.php'; // Ensure your database connection is included

if (isset($_POST['submit'])) {
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $temp_name = $_FILES['resume']['tmp_name'];
        $file_name = basename($_FILES['resume']['name']);
        $upload_dir = "uploads/";
        $file_path = $upload_dir . $file_name;

        // Ensure the uploads directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Try to move the file
        if (move_uploaded_file($temp_name, $file_path)) {
            echo "File uploaded successfully: <a href='$file_path' target='_blank'>$file_path</a>";
        } else {
            echo "Error: Failed to upload file.";
        }
    } else {
        echo "Error: No file uploaded or an issue occurred.";
        echo "<br>Error Code: " . $_FILES['resume']['error'];
    }
}
?>
