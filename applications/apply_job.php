<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Enable error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Generate nonce for CSP
$nonce = base64_encode(random_bytes(16));

// Initialize message variables
$message = '';
$message_type = '';

// Check if form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        $message = "Error: You must be logged in to upload a resume.";
        $message_type = "danger";
    } else {
        $user_id = $_SESSION['user_id'];
        $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;

        if ($job_id <= 0) {
            $message = "Error: Invalid Job ID.";
            $message_type = "danger";
        } else {
            // Check if user has already applied for this job
            $stmt = $conn->prepare("SELECT id FROM applications WHERE user_id = ? AND job_id = ?");
            $stmt->bind_param("ii", $user_id, $job_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $message = "You have already applied for this job.";
                $message_type = "warning";
            } else {
                // Check if file is uploaded
                if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
                    $message = "Error: Resume file not uploaded or has an error.";
                    $message_type = "danger";
                } else {
                    $temp_name = $_FILES['resume']['tmp_name'];
                    $original_name = basename($_FILES['resume']['name']);
                    $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                    // Allowed file types
                    $allowed_extensions = ['pdf', 'doc', 'docx'];
                    if (!in_array($file_ext, $allowed_extensions)) {
                        $message = "Error: Only PDF, DOC, and DOCX files are allowed.";
                        $message_type = "danger";
                    } else {
                        $upload_dir = "../uploads/resumes/";

                        // Ensure the directory exists
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        // Secure file naming
                        $safe_name = "resume_" . time() . "_" . rand(1000, 9999) . "." . $file_ext;
                        $file_path = $upload_dir . $safe_name;

                        // Move uploaded file to the server
                        if (!move_uploaded_file($temp_name, $file_path)) {
                            $message = "Error: Failed to upload file.";
                            $message_type = "danger";
                        } else {
                            // Store in the database
                            $stmt = $conn->prepare("
                                INSERT INTO applications (user_id, job_id, resume) 
                                VALUES (?, ?, ?)
                            ");
                            $stmt->bind_param("iis", $user_id, $job_id, $safe_name);

                            if ($stmt->execute()) {
                                $message = "Application submitted successfully! Resume uploaded: <a href='$file_path' target='_blank'>$safe_name</a>";
                                $message_type = "success";
                            } else {
                                $message = "Error saving application: " . $stmt->error;
                                $message_type = "danger";
                                // Clean up uploaded file if DB fails
                                if (file_exists($file_path)) {
                                    unlink($file_path);
                                }
                            }
                            $stmt->close(); // Close the statement after execution
                        }
                    }
                }
            }
            // Remove redundant stmt->close() here
        }
    }
} else {
    $message = "No form submission detected.";
    $message_type = "warning";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Job Application Submission">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; 
        script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'nonce-<?php echo $nonce; ?>'; 
        style-src 'self' https://cdn.jsdelivr.net 'nonce-<?php echo $nonce; ?>';">
    <title>Apply Job | Job Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" 
          crossorigin="anonymous">
    <style nonce="<?php echo $nonce; ?>">
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .container {
            max-width: 600px;
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: #3498db;
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: scale(1.05);
        }

        .alert {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container text-center">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <a href="../views/dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" 
            crossorigin="anonymous"></script>
    <script nonce="<?php echo $nonce; ?>">
        document.addEventListener('DOMContentLoaded', () => {
            // Optional: Add any client-side enhancements here if needed
        });
    </script>
</body>
</html>
