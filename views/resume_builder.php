<?php
session_start();
require __DIR__ . '/../includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'jobseeker';

// Check if the resumes table exists and has the correct structure
$check_table_query = "SHOW TABLES LIKE 'resumes'";
$check_table_result = $conn->query($check_table_query);

if ($check_table_result->num_rows === 0) {
    error_log("Error: The 'resumes' table does not exist in the database.");
    die("Error: The 'resumes' table is missing. Please contact the administrator to set up the database.");
}

$check_column_query = "SHOW COLUMNS FROM resumes LIKE 'resume_data'";
$check_column_result = $conn->query($check_column_query);

if ($check_column_result->num_rows === 0) {
    error_log("Error: The 'resume_data' column is missing in the 'resumes' table.");
    die("Error: The 'resumes' table is not set up correctly (missing 'resume_data' column). Please contact the administrator to update the database schema.");
}

// Fetch existing resume data if available
$resume_data = [];
$stmt = $conn->prepare("SELECT resume_data FROM resumes WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Error: Failed to prepare the query to fetch resume data. Please try again later.");
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    die("Error: Failed to fetch resume data. Please try again later.");
}

$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $resume_data = json_decode($row['resume_data'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        $resume_data = [];
    }
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_resume'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    // Collect form data
    $resume_data = [
        'contact_info' => [
            'name' => trim($_POST['contact_name'] ?? ''),
            'email' => trim($_POST['contact_email'] ?? ''),
            'phone' => trim($_POST['contact_phone'] ?? ''),
            'location' => trim($_POST['contact_location'] ?? '')
        ],
        'summary' => trim($_POST['summary'] ?? ''),
        'experience' => [],
        'education' => [],
        'skills' => [],
        'custom_sections' => []
    ];

    // Process work experience
    if (isset($_POST['experience_job_title'])) {
        foreach ($_POST['experience_job_title'] as $index => $job_title) {
            $resume_data['experience'][] = [
                'job_title' => trim($job_title),
                'company' => trim($_POST['experience_company'][$index] ?? ''),
                'dates' => trim($_POST['experience_dates'][$index] ?? ''),
                'responsibilities' => trim($_POST['experience_responsibilities'][$index] ?? '')
            ];
        }
    }

    // Process education
    if (isset($_POST['education_degree'])) {
        foreach ($_POST['education_degree'] as $index => $degree) {
            $resume_data['education'][] = [
                'degree' => trim($degree),
                'institution' => trim($_POST['education_institution'][$index] ?? ''),
                'graduation_year' => trim($_POST['education_graduation_year'][$index] ?? '')
            ];
        }
    }

    // Process skills
    if (isset($_POST['skills'])) {
        $resume_data['skills'] = array_filter(array_map('trim', $_POST['skills']));
    }

    // Process custom sections
    if (isset($_POST['custom_section_title'])) {
        foreach ($_POST['custom_section_title'] as $index => $title) {
            if (!empty($title)) {
                $resume_data['custom_sections'][] = [
                    'title' => trim($title),
                    'content' => trim($_POST['custom_section_content'][$index] ?? '')
                ];
            }
        }
    }

    // Validate required fields
    if (empty($resume_data['contact_info']['name']) || empty($resume_data['contact_info']['email']) || 
        empty($resume_data['experience']) || empty($resume_data['education'])) {
        $error = "Name, Email, at least one Work Experience, and at least one Education entry are required.";
    } else {
        // Save or update resume in database
        $stmt = $conn->prepare("SELECT id FROM resumes WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $resume_json = json_encode($resume_data);
        if ($result->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE resumes SET resume_data = ? WHERE user_id = ?");
            $stmt->bind_param("si", $resume_json, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO resumes (user_id, resume_data) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $resume_json);
        }

        if ($stmt->execute()) {
            $success = "Resume saved successfully!";

            // Fetch updated resume data
            $stmt = $conn->prepare("SELECT resume_data FROM resumes WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $resume_data = json_decode($row['resume_data'], true);
        } else {
            $error = "Failed to save resume. Please try again.";
        }
        $stmt->close();
    }
}

// Handle PDF download via PDFShift
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_pdf'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    // Generate HTML for PDFShift with enhanced styling
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                font-size: 11pt;
                line-height: 1.5;
                margin: 0.75in;
                color: #000;
            }
            .container {
                max-width: 7.5in;
                margin: 0 auto;
            }
            .header {
                padding-bottom: 0.25in;
                margin-bottom: 0.25in;
                text-align: center;
            }
            .header h1 {
                font-size: 18pt;
                font-weight: bold;
                margin: 0;
                color: #2c3e50;
            }
            .header p {
                font-size: 9pt;
                margin: 0.1in 0 0;
                color: #000;
            }
            .section {
                margin-bottom: 0.3in;
            }
            .section h2 {
                font-size: 12pt;
                font-weight: bold;
                color: #2c3e50;
                text-transform: uppercase;
                border-bottom: 1px solid lightgray;
                padding-bottom: 0.05in;
                margin-bottom: 0.15in;
            }
            .entry {
                margin-bottom: 0.2in;
                position: relative;
            }
            .entry-title {
                font-weight: bold;
                font-size: 11pt;
                margin-bottom: 0.05in;
            }
            .entry-subtitle {
                font-size: 10pt;
                color: #000;
                margin-bottom: 0.05in;
            }
            .entry-date {
                position: absolute;
                right: 0;
                top: 0;
                font-size: 10pt;
                color: #000;
            }
            .entry-content {
                font-size: 10pt;
                margin: 0;
            }
            .entry-content ul {
                margin-left: 1.5rem;
                list-style-type: disc;
            }
            .education-entry {
                display: flex;
                justify-content: space-between;
                align-items: baseline;
            }
            .education-details {
                font-weight: bold;
                font-size: 11pt;
            }
            .education-year {
                font-size: 10pt;
                color: #000;
            }
            @page {
                size: A4;
                margin: 0.75in;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Header -->
            <div class="header">
                <h1><?php echo htmlspecialchars($resume_data['contact_info']['name'] ?? 'Your Name'); ?></h1>
                <p>
                    <?php echo htmlspecialchars(implode(' | ', array_filter([
                        $resume_data['contact_info']['email'] ?? '',
                        $resume_data['contact_info']['phone'] ?? '',
                        $resume_data['contact_info']['location'] ?? ''
                    ]))); ?>
                </p>
            </div>

            <!-- Professional Summary -->
            <?php if (!empty($resume_data['summary'])): ?>
                <div class="section">
                    <h2>Professional Summary</h2>
                    <p class="entry-content"><?php echo nl2br(htmlspecialchars($resume_data['summary'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Skills -->
            <?php if (!empty($resume_data['skills'])): ?>
                <div class="section">
                    <h2>Skills</h2>
                    <div>
                        <p class="entry-content"><?php echo htmlspecialchars(implode(', ', $resume_data['skills'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Projects (Work Experience) -->
            <?php if (!empty($resume_data['experience'])): ?>
                <div class="section">
                    <h2>Projects</h2>
                    <?php foreach ($resume_data['experience'] as $exp): ?>
                        <div class="entry">
                            <span class="entry-date"><?php echo htmlspecialchars($exp['dates']); ?></span>
                            <div class="entry-title"><?php echo htmlspecialchars($exp['job_title']); ?></div>
                            <div class="entry-subtitle"><?php echo htmlspecialchars($exp['company']); ?></div>
                            <div class="entry-content">
                                <ul>
                                    <?php
                                    $responsibilities = explode("\n", $exp['responsibilities']);
                                    foreach ($responsibilities as $resp) {
                                        if (trim($resp)) {
                                            echo '<li>' . htmlspecialchars(trim($resp)) . '</li>';
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Education -->
            <?php if (!empty($resume_data['education'])): ?>
                <div class="section">
                    <h2>Education</h2>
                    <?php foreach ($resume_data['education'] as $edu): ?>
                        <div class="entry education-entry">
                            <p class="education-details"><?php echo htmlspecialchars($edu['degree'] . ', ' . $edu['institution']); ?></p>
                            <span class="education-year"><?php echo htmlspecialchars($edu['graduation_year']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Custom Sections -->
            <?php if (!empty($resume_data['custom_sections'])): ?>
                <?php foreach ($resume_data['custom_sections'] as $section): ?>
                    <div class="section">
                        <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                        <div class="entry">
                            <div class="entry-content">
                                <ul>
                                    <?php
                                    $contents = explode("\n", $section['content']);
                                    foreach ($contents as $content) {
                                        if (trim($content)) {
                                            echo '<li>' . htmlspecialchars(trim($content)) . '</li>';
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    // PDFShift API call
    $api_key = 'sk_3c306d2100d8488c9d76efa26b786c8c0c1aeb0e'; // Replace with your actual key
    $url = 'https://api.pdfshift.io/v3/convert/pdf';
    $params = [
        'source' => $html,
        'sandbox' => false,
        'margin' => [
            'top' => '15mm',
            'bottom' => '15mm',
            'left' => '15mm',
            'right' => '15mm'
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode("api:$api_key")
    ]);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Log details for debugging
    error_log("PDFShift Request: " . json_encode($params));
    error_log("PDFShift Response: " . $response);
    error_log("HTTP Code: " . $http_code);
    if ($curl_error) {
        error_log("cURL Error: " . $curl_error);
    }

    if ($http_code === 200) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="resume.pdf"');
        echo $response;
        exit();
    } else {
        $error = "PDF generation failed: " . htmlspecialchars($response);
    }
}
// Generate nonce for CSP
$nonce = base64_encode(random_bytes(16));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Build an ATS-friendly resume with a user-friendly interface">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; 
        script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'nonce-<?php echo $nonce; ?>'; 
        style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'nonce-<?php echo $nonce; ?>'; 
        img-src 'self' data: https://cdn-icons-png.flaticon.com;">
    <title>Resume Builder | RookieRise</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" 
          crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style nonce="<?php echo $nonce; ?>">
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            flex-direction: column;
        }

        main { flex: 1 0 auto; }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.8rem;
            color: #1a2a44;
            transition: color 0.3s ease;
        }

        .navbar-brand:hover { color: #00aaff; }

        .nav-link {
            font-family: 'Poppins', sans-serif;
            color: #1a2a44;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: #00aaff;
            background: rgba(0, 170, 255, 0.1);
            border-radius: 5px;
        }

        .btn-nav-primary {
            background: #00aaff;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-nav-primary:hover {
            background: #0088cc;
            transform: scale(1.05);
        }

        .navbar-text {
            font-family: 'Poppins', sans-serif;
            color: #1a2a44;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #00aaff 0%, #1a2a44 100%);
            color: white;
            border-radius: 15px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .welcome-section h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Resume Form and Preview */
        .wizard-step {
            display: none;
        }

        .wizard-step.active {
            display: block;
        }

        .resume-form, .resume-preview {
            background: #fff;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .resume-preview {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.5;
        }

        .resume-preview h1 {
            font-size: 18pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.2rem;
            text-align: center;
        }

        .resume-preview #preview-contact {
            font-size: 9pt;
            color: #000;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .resume-preview h2 {
            font-size: 12pt;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            border-bottom: 1px solid lightgray;
            padding-bottom: 0.1rem;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }

        .resume-preview p {
            margin-bottom: 0.3rem;
            font-size: 10pt;
        }

        .resume-preview ul {
            margin-left: 1.5rem;
            font-size: 10pt;
            list-style-type: disc;
        }

        .resume-preview .entry {
            margin-bottom: 0.5rem;
            position: relative;
        }

        .resume-preview .entry-title {
            font-weight: bold;
            font-size: 11pt;
        }

        .resume-preview .entry-subtitle {
            font-size: 10pt;
            color: #000;
        }

        .resume-preview .entry-date {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 10pt;
            color: #000;
        }

        .resume-preview .education-entry {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .resume-preview .education-details {
            font-weight: bold;
            font-size: 11pt;
        }

        .resume-preview .education-year {
            font-size: 10pt;
            color: #000;
        }

        .btn-primary {
            background: #3498db;
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: scale(1.05);
        }

        .btn-success {
            background: #27ae60;
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
        }

        .btn-success:hover {
            background: #219653;
            transform: scale(1.05);
        }

        .btn-danger {
            background: #e74c3c;
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1rem;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .dynamic-entry {
            border: 1px solid #e0e0e0;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .dynamic-entry .form-group {
            margin-bottom: 1rem;
        }

        .ats-tip {
            font-size: 0.9rem;
            color: #555;
            margin-top: 0.5rem;
        }

        .progress {
            height: 10px;
            margin-bottom: 2rem;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, #1a2a44 0%, #00aaff 100%);
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 2rem;
            flex-shrink: 0;
        }

        footer h5 {
            font-family: 'Poppins', sans-serif;
            color: #ffd700;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        footer p, footer a {
            font-family: 'Poppins', sans-serif;
        }

        footer a { 
            color: #ffd700; 
            text-decoration: none; 
            transition: color 0.3s ease;
        }

        footer a:hover { color: #fff; }

        .social-icons a {
            display: inline-block;
            margin: 0 0.75rem;
            transition: all 0.3s ease;
        }

        .social-icons img {
            width: 28px;
            height: 28px;
            transition: transform 0.3s ease;
        }

        .social-icons a:hover img {
            transform: translateY(-5px);
        }

        /* Focus Styles */
        :focus-visible {
            outline: 2px solid #00aaff;
            outline-offset: 2px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .resume-form, .resume-preview {
                padding: 1rem;
            }
            .navbar { padding: 0.5rem 1rem; }
            .navbar-brand { font-size: 1.5rem; }
            .social-icons img { width: 24px; height: 24px; margin: 0 0.5rem; }
            .welcome-section { padding: 1.5rem; }
            .welcome-section h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php" aria-label="RookieRise Home">RookieRise</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                data-bs-target="#navbarNav" aria-controls="navbarNav" 
                aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../applications/job_listings.php">Job Listings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../applications/compare_jobs.php" 
                           aria-label="Compare Jobs">Compare Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../applications/saved_jobs.php" 
                           aria-label="View Saved Jobs">Saved Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="resume_builder.php">Resume Builder</a>
                    </li>
                </ul>
                <div class="navbar-actions">
                    <span class="navbar-text me-3" aria-live="polite">
                        Welcome, <?php echo htmlspecialchars($user_name); ?>
                    </span>
                    <a href="../auth/logout.php" class="btn btn-nav-primary" 
                       aria-label="Logout">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mt-5" role="main">
        <section class="welcome-section" aria-labelledby="resume-builder-heading">
            <h2 class="mb-3" id="resume-builder-heading">Resume Builder</h2>
            <p class="lead">Create an ATS-friendly resume with our step-by-step guide.</p>
        </section>

        <section aria-labelledby="resume-form-heading">
            <?php if (isset($success)): ?>
                <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 20%;" 
                     aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">Step 1 of 5</div>
            </div>

            <div class="row">
                <!-- Resume Form -->
                <div class="col-md-6">
                    <div class="resume-form">
                        <form method="POST" action="resume_builder.php" id="resume-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <!-- Step 1: Contact Information -->
                            <div class="wizard-step active" data-step="1">
                                <h4>Step 1: Contact Information</h4>
                                <div class="mb-3">
                                    <label for="contact_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="contact_name" id="contact_name" class="form-control" required
                                           value="<?php echo htmlspecialchars($resume_data['contact_info']['name'] ?? ''); ?>">
                                    <div class="ats-tip">Use your full legal name as it appears on job applications.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="contact_email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="contact_email" id="contact_email" class="form-control" required
                                           value="<?php echo htmlspecialchars($resume_data['contact_info']['email'] ?? ''); ?>">
                                    <div class="ats-tip">Use a professional email address (e.g., firstname.lastname@email.com).</div>
                                </div>
                                <div class="mb-3">
                                    <label for="contact_phone" class="form-label">Phone Number</label>
                                    <input type="tel" name="contact_phone" id="contact_phone" class="form-control"
                                           value="<?php echo htmlspecialchars($resume_data['contact_info']['phone'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="contact_location" class="form-label">Location</label>
                                    <input type="text" name="contact_location" id="contact_location" class="form-control"
                                           value="<?php echo htmlspecialchars($resume_data['contact_info']['location'] ?? ''); ?>">
                                    <div class="ats-tip">Include your city and state (e.g., New York, NY).</div>
                                </div>
                                <button type="button" class="btn btn-primary next-step">Next</button>
                            </div>

                            <!-- Step 2: Professional Summary -->
                            <div class="wizard-step" data-step="2">
                                <h4>Step 2: Professional Summary</h4>
                                <div class="mb-3">
                                    <label for="summary" class="form-label">Professional Summary</label>
                                    <textarea name="summary" id="summary" class="form-control"
                                              placeholder="A brief summary of your skills and experience"><?php echo htmlspecialchars($resume_data['summary'] ?? ''); ?></textarea>
                                    <div class="ats-tip">Highlight your key skills and experience. Use keywords from job descriptions.</div>
                                </div>
                                <button type="button" class="btn btn-primary prev-step me-2">Previous</button>
                                <button type="button" class="btn btn-primary next-step">Next</button>
                            </div>

                            <!-- Step 3: Work Experience (Projects) -->
                            <div class="wizard-step" data-step="3">
                                <h4>Step 3: Projects</h4>
                                <div id="experience-entries">
                                    <?php if (!empty($resume_data['experience'])): ?>
                                        <?php foreach ($resume_data['experience'] as $index => $exp): ?>
                                            <div class="dynamic-entry">
                                                <div class="form-group">
                                                    <label>Project Title <span class="text-danger">*</span></label>
                                                    <input type="text" name="experience_job_title[]" class="form-control" required
                                                           value="<?php echo htmlspecialchars($exp['job_title']); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Organization/Company (Optional)</label>
                                                    <input type="text" name="experience_company[]" class="form-control"
                                                           value="<?php echo htmlspecialchars($exp['company']); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Dates (e.g., Sep 2024)</label>
                                                    <input type="text" name="experience_dates[]" class="form-control"
                                                           value="<?php echo htmlspecialchars($exp['dates']); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Description</label>
                                                    <textarea name="experience_responsibilities[]" class="form-control"><?php echo htmlspecialchars($exp['responsibilities']); ?></textarea>
                                                </div>
                                                <button type="button" class="btn btn-danger remove-entry">Remove</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="dynamic-entry">
                                            <div class="form-group">
                                                <label>Project Title <span class="text-danger">*</span></label>
                                                <input type="text" name="experience_job_title[]" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Organization/Company (Optional)</label>
                                                <input type="text" name="experience_company[]" class="form-control">
                                            </div>
                                            <div class="form-group">
                                                <label>Dates (e.g., Sep 2024)</label>
                                                <input type="text" name="experience_dates[]" class="form-control">
                                            </div>
                                            <div class="form-group">
                                                <label>Description</label>
                                                <textarea name="experience_responsibilities[]" class="form-control"></textarea>
                                            </div>
                                            <button type="button" class="btn btn-danger remove-entry">Remove</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-primary mb-3" id="add-experience">Add Project</button>
                                <div class="ats-tip">Describe your projects with clear outcomes and technologies used.</div>
                                <button type="button" class="btn btn-primary prev-step me-2">Previous</button>
                                <button type="button" class="btn btn-primary next-step">Next</button>
                            </div>

                            <!-- Step 4: Education and Skills -->
                            <div class="wizard-step" data-step="4">
                                <h4>Step 4: Education and Skills</h4>
                                <div id="education-entries">
                                    <?php if (!empty($resume_data['education'])): ?>
                                        <?php foreach ($resume_data['education'] as $index => $edu): ?>
                                            <div class="dynamic-entry">
                                                <div class="form-group">
                                                    <label>Degree <span class="text-danger">*</span></label>
                                                    <input type="text" name="education_degree[]" class="form-control" required
                                                           value="<?php echo htmlspecialchars($edu['degree']); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Institution</label>
                                                    <input type="text" name="education_institution[]" class="form-control"
                                                           value="<?php echo htmlspecialchars($edu['institution']); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Graduation Year</label>
                                                    <input type="text" name="education_graduation_year[]" class="form-control"
                                                           value="<?php echo htmlspecialchars($edu['graduation_year']); ?>">
                                                </div>
                                                <button type="button" class="btn btn-danger remove-entry">Remove</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="dynamic-entry">
                                            <div class="form-group">
                                                <label>Degree <span class="text-danger">*</span></label>
                                                <input type="text" name="education_degree[]" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Institution</label>
                                                <input type="text" name="education_institution[]" class="form-control">
                                            </div>
                                            <div class="form-group">
                                                <label>Graduation Year</label>
                                                <input type="text" name="education_graduation_year[]" class="form-control">
                                            </div>
                                            <button type="button" class="btn btn-danger remove-entry">Remove</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-primary mb-3" id="add-education">Add Education</button>

                                <h5 class="mt-4">Skills</h5>
                                <div id="skills-entries">
                                    <?php if (!empty($resume_data['skills'])): ?>
                                        <?php foreach ($resume_data['skills'] as $index => $skill): ?>
                                            <div class="dynamic-entry">
                                                <div class="form-group">
                                                    <input type="text" name="skills[]" class="form-control"
                                                           value="<?php echo htmlspecialchars($skill); ?>">
                                                </div>
                                                <button type="button" class="btn btn-danger remove-entry">Remove</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="dynamic-entry">
                                            <div class="form-group">
                                                <input type="text" name="skills[]" class="form-control" placeholder="e.g., JavaScript">
                                            </div>
                                            <button type="button" class="btn btn-danger remove-entry">Remove</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-primary mb-3" id="add-skill">Add Skill</button>
                                <div class="ats-tip">Include skills relevant to the jobs you're applying for (e.g., programming languages, tools).</div>

                                <button type="button" class="btn btn-primary prev-step me-2">Previous</button>
                                <button type="button" class="btn btn-primary next-step">Next</button>
                            </div>

                            <!-- Step 5: Custom Sections -->
                            <div class="wizard-step" data-step="5">
                                <h4>Step 5: Custom Sections</h4>
                                <p>Add any additional sections to your resume, such as Certifications, Achievements, or Hobbies.</p>
                                <div id="custom-section-entries">
                                    <?php if (!empty($resume_data['custom_sections'])): ?>
                                        <?php foreach ($resume_data['custom_sections'] as $index => $section): ?>
                                            <div class="dynamic-entry">
                                                <div class="form-group">
                                                    <label>Section Title</label>
                                                    <input type="text" name="custom_section_title[]" class="form-control"
                                                           value="<?php echo htmlspecialchars($section['title']); ?>"
                                                           placeholder="e.g., Certifications">
                                                </div>
                                                <div class="form-group">
                                                    <label>Content</label>
                                                    <textarea name="custom_section_content[]" class="form-control"
                                                              placeholder="e.g., Certified ScrumMaster, 2022"><?php echo htmlspecialchars($section['content']); ?></textarea>
                                                </div>
                                                <button type="button" class="btn btn-danger remove-entry">Remove</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="dynamic-entry">
                                            <div class="form-group">
                                                <label>Section Title</label>
                                                <input type="text" name="custom_section_title[]" class="form-control"
                                                       placeholder="e.g., Certifications">
                                            </div>
                                            <div class="form-group">
                                                <label>Content</label>
                                                <textarea name="custom_section_content[]" class="form-control"
                                                          placeholder="e.g., Certified ScrumMaster, 2022"></textarea>
                                            </div>
                                            <button type="button" class="btn btn-danger remove-entry">Remove</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-primary mb-3" id="add-custom-section">Add Custom Section</button>
                                <div class="ats-tip">Use clear section titles and concise content. Avoid special characters or formatting.</div>

                                <button type="button" class="btn btn-primary prev-step me-2">Previous</button>
                                <button type="submit" name="save_resume" class="btn btn-success">Save Resume</button>
                                <button type="submit" name="download_pdf" class="btn btn-primary">Download as PDF</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Resume Preview -->
                <div class="col-md-6">
                    <div class="resume-preview" id="resume-preview">
                        <h1 id="preview-name"><?php echo htmlspecialchars($resume_data['contact_info']['name'] ?? 'Your Name'); ?></h1>
                        <p id="preview-contact">
                            <?php
                            $contact_parts = array_filter([
                                $resume_data['contact_info']['email'] ?? '',
                                $resume_data['contact_info']['phone'] ?? '',
                                $resume_data['contact_info']['location'] ?? ''
                            ]);
                            echo htmlspecialchars(implode(' | ', $contact_parts)) ?: 'Email | Phone | City, State';
                            ?>
                        </p>

                        <?php if (!empty($resume_data['summary'])): ?>
                            <h2>Professional Summary</h2>
                            <p id="preview-summary"><?php echo nl2br(htmlspecialchars($resume_data['summary'])); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($resume_data['skills'])): ?>
                            <h2>Skills</h2>
                            <div id="preview-skills">
                                <p class="entry-content"><?php echo htmlspecialchars(implode(', ', $resume_data['skills'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <h2>Projects</h2>
                        <div id="preview-experience">
                            <?php if (!empty($resume_data['experience'])): ?>
                                <?php foreach ($resume_data['experience'] as $exp): ?>
                                    <div class="entry">
                                        <span class="entry-date"><?php echo htmlspecialchars($exp['dates']); ?></span>
                                        <p class="entry-title"><?php echo htmlspecialchars($exp['job_title']); ?></p>
                                        <p class="entry-subtitle"><?php echo htmlspecialchars($exp['company']); ?></p>
                                        <p class="entry-content">
                                            <ul>
                                                <?php
                                                $responsibilities = explode("\n", $exp['responsibilities']);
                                                foreach ($responsibilities as $resp) {
                                                    if (trim($resp)) {
                                                        echo '<li>' . htmlspecialchars(trim($resp)) . '</li>';
                                                    }
                                                }
                                                ?>
                                            </ul>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Add your projects here.</p>
                            <?php endif; ?>
                        </div>

                        <h2>Education</h2>
                        <div id="preview-education">
                            <?php if (!empty($resume_data['education'])): ?>
                                <?php foreach ($resume_data['education'] as $edu): ?>
                                    <div class="entry education-entry">
                                        <p class="education-details"><?php echo htmlspecialchars($edu['degree'] . ', ' . $edu['institution']); ?></p>
                                        <span class="education-year"><?php echo htmlspecialchars($edu['graduation_year']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Add your education here.</p>
                            <?php endif; ?>
                        </div>

                        <div id="preview-custom-sections">
                            <?php if (!empty($resume_data['custom_sections'])): ?>
                                <?php foreach ($resume_data['custom_sections'] as $section): ?>
                                    <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                                    <div class="entry">
                                        <p class="entry-content">
                                            <ul>
                                                <?php
                                                $contents = explode("\n", $section['content']);
                                                foreach ($contents as $content) {
                                                    if (trim($content)) {
                                                        echo '<li>' . htmlspecialchars(trim($content)) . '</li>';
                                                    }
                                                }
                                                ?>
                                            </ul>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer role="contentinfo">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>RookieRise</h5>
                    <p>Connecting talent with opportunities since 2025.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="dashboard.php">Home</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Connect With Us</h5>
                    <div class="social-icons">
                        <a href="https://facebook.com" target="_blank" 
                           aria-label="Follow us on Facebook" title="Facebook">
                            <img src="https://cdn-icons-png.flaticon.com/512/733/733547.png" 
                                 alt="Facebook Icon">
                        </a>
                        <a href="https://twitter.com" target="_blank" 
                           aria-label="Follow us on Twitter" title="Twitter">
                            <img src="https://cdn-icons-png.flaticon.com/512/733/733579.png" 
                                 alt="Twitter Icon">
                        </a>
                        <a href="https://linkedin.com" target="_blank" 
                           aria-label="Connect with us on LinkedIn" title="LinkedIn">
                            <img src="https://cdn-icons-png.flaticon.com/512/733/733561.png" 
                                 alt="LinkedIn Icon">
                        </a>
                        <a href="https://instagram.com" target="_blank" 
                           aria-label="Follow us on Instagram" title="Instagram">
                            <img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" 
                                 alt="Instagram Icon">
                        </a>
                    </div>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.2);">
            <div class="text-center">
                <p class="mb-0">© <?php echo date('Y'); ?> RookieRise. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" 
            crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" 
            integrity="sha512-16esztaSRplJROstbIIdwX3N97V1+pZvV33ABoG1H2OyTttBxEGkTsoIVsiP1iaTtM8b3+hu2kB6pQ4Clr5yug==" 
            crossorigin="anonymous"></script>
    <script nonce="<?php echo $nonce; ?>">
        document.addEventListener('DOMContentLoaded', () => {
            // GSAP Animations
            gsap.from('.navbar', {
                duration: 1,
                opacity: 0,
                y: -50,
                ease: 'power2.out'
            });

            gsap.from('.welcome-section', { 
                duration: 1.2, 
                opacity: 0, 
                y: -100, 
                ease: 'back.out(1.7)' 
            });

            gsap.from('footer', {
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out',
                delay: 0.6
            });

            // Social icons error handling
            document.querySelectorAll('.social-icons img').forEach(img => {
                img.onerror = () => {
                    console.warn(`Failed to load icon: ${img.src}`);
                    img.alt = img.alt.replace('Icon', '');
                    img.style.display = 'none';
                    const span = document.createElement('span');
                    span.textContent = img.alt.charAt(0);
                    span.style.color = '#ffd700';
                    span.style.fontSize = '28px';
                    img.parentNode.appendChild(span);
                };
            });

            // Wizard Navigation
            const steps = document.querySelectorAll('.wizard-step');
            const progressBar = document.querySelector('.progress-bar');
            let currentStep = 1;

            function updateProgress() {
                const progress = (currentStep / steps.length) * 100;
                progressBar.style.width = `${progress}%`;
                progressBar.textContent = `Step ${currentStep} of ${steps.length}`;
                progressBar.setAttribute('aria-valuenow', progress);
            }

            document.querySelectorAll('.next-step').forEach(button => {
                button.addEventListener('click', () => {
                    if (currentStep < steps.length) {
                        steps[currentStep - 1].classList.remove('active');
                        currentStep++;
                        steps[currentStep - 1].classList.add('active');
                        updateProgress();
                    }
                });
            });

            document.querySelectorAll('.prev-step').forEach(button => {
                button.addEventListener('click', () => {
                    if (currentStep > 1) {
                        steps[currentStep - 1].classList.remove('active');
                        currentStep--;
                        steps[currentStep - 1].classList.add('active');
                        updateProgress();
                    }
                });
            });

            // Dynamic Entries for Projects
            document.getElementById('add-experience').addEventListener('click', () => {
                const entry = document.createElement('div');
                entry.className = 'dynamic-entry';
                entry.innerHTML = `
                    <div class="form-group">
                        <label>Project Title <span class="text-danger">*</span></label>
                        <input type="text" name="experience_job_title[]" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Organization/Company (Optional)</label>
                        <input type="text" name="experience_company[]" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Dates (e.g., Sep 2024)</label>
                        <input type="text" name="experience_dates[]" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="experience_responsibilities[]" class="form-control"></textarea>
                    </div>
                    <button type="button" class="btn btn-danger remove-entry">Remove</button>
                `;
                document.getElementById('experience-entries').appendChild(entry);
                attachRemoveEvent(entry.querySelector('.remove-entry'));
            });

            // Dynamic Entries for Education
            document.getElementById('add-education').addEventListener('click', () => {
                const entry = document.createElement('div');
                entry.className = 'dynamic-entry';
                entry.innerHTML = `
                    <div class="form-group">
                        <label>Degree <span class="text-danger">*</span></label>
                        <input type="text" name="education_degree[]" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Institution</label>
                        <input type="text" name="education_institution[]" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Graduation Year</label>
                        <input type="text" name="education_graduation_year[]" class="form-control">
                    </div>
                    <button type="button" class="btn btn-danger remove-entry">Remove</button>
                `;
                document.getElementById('education-entries').appendChild(entry);
                attachRemoveEvent(entry.querySelector('.remove-entry'));
            });

            // Dynamic Entries for Skills
            document.getElementById('add-skill').addEventListener('click', () => {
                const entry = document.createElement('div');
                entry.className = 'dynamic-entry';
                entry.innerHTML = `
                    <div class="form-group">
                        <input type="text" name="skills[]" class="form-control" placeholder="e.g., JavaScript">
                    </div>
                    <button type="button" class="btn btn-danger remove-entry">Remove</button>
                `;
                document.getElementById('skills-entries').appendChild(entry);
                attachRemoveEvent(entry.querySelector('.remove-entry'));
            });

            // Dynamic Entries for Custom Sections
            document.getElementById('add-custom-section').addEventListener('click', () => {
                const entry = document.createElement('div');
                entry.className = 'dynamic-entry';
                entry.innerHTML = `
                    <div class="form-group">
                        <label>Section Title</label>
                        <input type="text" name="custom_section_title[]" class="form-control"
                               placeholder="e.g., Certifications">
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="custom_section_content[]" class="form-control"
                                  placeholder="e.g., Certified ScrumMaster, 2022"></textarea>
                    </div>
                    <button type="button" class="btn btn-danger remove-entry">Remove</button>
                `;
                document.getElementById('custom-section-entries').appendChild(entry);
                attachRemoveEvent(entry.querySelector('.remove-entry'));
            });

            // Attach remove event to existing entries
            function attachRemoveEvent(button) {
                button.addEventListener('click', () => {
                    button.parentElement.remove();
                    updatePreview();
                });
            }

            document.querySelectorAll('.remove-entry').forEach(attachRemoveEvent);

            // Live Preview Update
            function updatePreview() {
                const form = document.getElementById('resume-form');
                const formData = new FormData(form);

                // Contact Info
                const name = formData.get('contact_name') || 'Your Name';
                const email = formData.get('contact_email');
                const phone = formData.get('contact_phone');
                const location = formData.get('contact_location');
                const contactParts = [email, phone, location].filter(part => part);
                document.getElementById('preview-name').textContent = name;
                document.getElementById('preview-contact').textContent = contactParts.length ? contactParts.join(' | ') : 'Email | Phone | City, State';

                // Summary
                const summary = formData.get('summary');
                const summarySection = document.getElementById('preview-summary');
                if (summary) {
                    if (!summarySection) {
                        const h2 = document.createElement('h2');
                        h2.textContent = 'Professional Summary';
                        const p = document.createElement('p');
                        p.id = 'preview-summary';
                        document.getElementById('resume-preview').insertBefore(h2, document.getElementById('preview-skills')?.parentElement || document.getElementById('preview-experience').previousElementSibling);
                        document.getElementById('resume-preview').insertBefore(p, document.getElementById('preview-skills')?.parentElement || document.getElementById('preview-experience').previousElementSibling);
                    }
                    document.getElementById('preview-summary').innerHTML = summary.replace(/\n/g, '<br>');
                } else if (summarySection) {
                    summarySection.previousElementSibling.remove();
                    summarySection.remove();
                }

                // Skills
                const skills = formData.getAll('skills').filter(skill => skill);
                const skillsSection = document.getElementById('preview-skills');
                if (skills.length) {
                    if (!skillsSection) {
                        const h2 = document.createElement('h2');
                        h2.textContent = 'Skills';
                        const div = document.createElement('div');
                        div.id = 'preview-skills';
                        document.getElementById('resume-preview').insertBefore(h2, document.getElementById('preview-experience').previousElementSibling);
                        document.getElementById('resume-preview').insertBefore(div, document.getElementById('preview-experience').previousElementSibling);
                    }
                    document.getElementById('preview-skills').innerHTML = `
                        <p class="entry-content">${skills.join(', ')}</p>
                    `;
                } else if (skillsSection) {
                    skillsSection.previousElementSibling.remove();
                    skillsSection.remove();
                }

                // Projects (Work Experience)
                const experienceEntries = [];
                const jobTitles = formData.getAll('experience_job_title');
                const companies = formData.getAll('experience_company');
                const dates = formData.getAll('experience_dates');
                const responsibilities = formData.getAll('experience_responsibilities');

                jobTitles.forEach((title, index) => {
                    if (title) {
                        const respList = responsibilities[index] ? responsibilities[index].split('\n').filter(r => r.trim()).map(r => `<li>${r.trim()}</li>`).join('') : '';
                        experienceEntries.push(`
                            <div class="entry">
                                <span class="entry-date">${dates[index] || ''}</span>
                                <p class="entry-title">${title}</p>
                                <p class="entry-subtitle">${companies[index] || ''}</p>
                                <p class="entry-content"><ul>${respList}</ul></p>
                            </div>
                        `);
                    }
                });
                document.getElementById('preview-experience').innerHTML = experienceEntries.length ? experienceEntries.join('') : '<p>Add your projects here.</p>';

                // Education
                const educationEntries = [];
                const degrees = formData.getAll('education_degree');
                const institutions = formData.getAll('education_institution');
                const graduationYears = formData.getAll('education_graduation_year');

                degrees.forEach((degree, index) => {
                    if (degree) {
                        educationEntries.push(`
                            <div class="entry education-entry">
                                <p class="education-details">${degree}, ${institutions[index]}</p>
                                <span class="education-year">${graduationYears[index] || ''}</span>
                            </div>
                        `);
                    }
                });
                document.getElementById('preview-education').innerHTML = educationEntries.length ? educationEntries.join('') : '<p>Add your education here.</p>';

                // Custom Sections
                const customSectionEntries = [];
                const customTitles = formData.getAll('custom_section_title');
                const customContents = formData.getAll('custom_section_content');

                customTitles.forEach((title, index) => {
                    if (title) {
                        const contentList = customContents[index] ? customContents[index].split('\n').filter(c => c.trim()).map(c => `<li>${c.trim()}</li>`).join('') : '';
                        customSectionEntries.push(`
                            <h2>${title}</h2>
                            <div class="entry">
                                <p class="entry-content"><ul>${contentList}</ul></p>
                            </div>
                        `);
                    }
                });
                document.getElementById('preview-custom-sections').innerHTML = customSectionEntries.length ? customSectionEntries.join('') : '';
            }

            // Update preview on input
            document.getElementById('resume-form').addEventListener('input', updatePreview);
            document.getElementById('resume-form').addEventListener('change', updatePreview);
        });
    </script>
</body>
</html>