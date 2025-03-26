<?php
session_start();
require __DIR__ . '/../includes/db.php';

// Set the timezone to ensure consistent date handling
date_default_timezone_set('UTC'); // Adjust to your desired timezone, e.g., 'America/Los_Angeles'

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

// Get user details
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? null;

// Fetch user role if not set
if (!$user_role) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    if (!$stmt) {
        $user_role = 'guest';
    } else {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($db_role);
        $stmt->fetch();
        $stmt->close();
        $user_role = $db_role ?? 'guest';
        $_SESSION['user_role'] = $user_role;
    }
}

// Pagination setup for jobs
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 25;
$offset = ($page - 1) * $limit;

// Fetch total number of jobs for pagination
if ($user_role === 'employer') {
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = ?");
    $count_stmt->bind_param("i", $user_id);
} else {
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM jobs");
}
$count_stmt->execute();
$count_stmt->bind_result($total_jobs);
$count_stmt->fetch();
$count_stmt->close();
$total_pages = ceil($total_jobs / $limit);

// Fetch job listings based on role with pagination
if ($user_role === 'employer') {
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE employer_id = ? ORDER BY created_at DESC LIMIT ?, ?");
    $stmt->bind_param("iii", $user_id, $offset, $limit);
} else {
    $stmt = $conn->prepare("SELECT * FROM jobs ORDER BY created_at DESC LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $limit);
}
$stmt->execute();
$jobs = $stmt->get_result();
$stmt->close();

// Fetch applicants count and details for each job (for employers)
$applicants_counts = [];
$applicants_data = [];
$jobs->data_seek(0);
while ($job = $jobs->fetch_assoc()) {
    $applicants_query = $conn->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
    $applicants_query->bind_param("i", $job['id']);
    $applicants_query->execute();
    $applicants_query->bind_result($count);
    $applicants_query->fetch();
    $applicants_query->close();
    $applicants_counts[$job['id']] = $count;

    $applicants = [];
    if ($user_role === 'employer') {
        $applicants_query = $conn->prepare("
            SELECT u.id, u.name 
            FROM applications a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.job_id = ? AND u.role = 'jobseeker'
        ");
        $applicants_query->bind_param("i", $job['id']);
        $applicants_query->execute();
        $result = $applicants_query->get_result();
        while ($applicant = $result->fetch_assoc()) {
            $applicants[] = $applicant;
        }
        $applicants_query->close();
    }
    $applicants_data[$job['id']] = $applicants;
}
$jobs->data_seek(0);

// Fetch job seekers who have messaged the employer (based on applications)
$job_seeker_messages = [];
if ($user_role === 'employer') {
    $stmt = $conn->prepare("
        SELECT DISTINCT u.id, u.name, j.title AS job_title
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN jobs j ON a.job_id = j.id
        WHERE j.employer_id = ? AND u.role = 'jobseeker'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $job_seeker_messages[] = $row;
    }
    $stmt->close();
}

// Fetch bookmarked jobs for the current user (if job seeker)
$bookmarked_jobs = [];
if ($user_role === 'jobseeker') {
    $stmt = $conn->prepare("SELECT job_id FROM bookmarked_jobs WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bookmarked_jobs[$row['job_id']] = true;
    }
    $stmt->close();
    error_log("Bookmarked jobs for user $user_id: " . print_r($bookmarked_jobs, true));
}

// Fetch hackathon summary for dashboard
$upcoming_hackathons = [];
$registered_hackathons = [];
$past_hackathons = [];

if ($user_role === 'employer') {
    $stmt = $conn->prepare("SELECT * FROM hackathons WHERE employer_id = ? AND is_active = 1 ORDER BY date ASC LIMIT 3");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $upcoming_hackathons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM hackathons WHERE employer_id = ? AND is_active = 0 ORDER BY date DESC LIMIT 3");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $past_hackathons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT h.*, u.name AS organizer_name FROM hackathons h JOIN users u ON h.employer_id = u.id WHERE h.is_active = 1 ORDER BY h.date ASC LIMIT 3");
    $stmt->execute();
    $upcoming_hackathons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT h.*, u.name AS organizer_name 
        FROM hackathons h 
        JOIN hackathon_registrations hr ON h.id = hr.hackathon_id 
        JOIN users u ON h.employer_id = u.id 
        WHERE hr.user_id = ? AND h.is_active = 1 
        ORDER BY h.date ASC LIMIT 3
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $registered_hackathons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("SELECT h.*, u.name AS organizer_name FROM hackathons h JOIN users u ON h.employer_id = u.id WHERE h.is_active = 0 ORDER BY h.date DESC LIMIT 3");
    $stmt->execute();
    $past_hackathons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Job Portal Dashboard - Manage your job listings, applications, and hackathons">
    <meta name="keywords" content="job portal, dashboard, jobs, applications, hackathons">
    <meta name="author" content="Job Portal Team">
    <title>Dashboard | Job Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" 
          crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
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
            font-weight: 700;
            font-size: 1.8rem;
            color: #1a2a44;
            transition: color 0.3s ease;
        }

        .navbar-brand:hover { color: #00aaff; }

        .navbar-nav {
            display: flex;
            align-items: center;
        }

        .nav-link {
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
            color: #1a2a44;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        /* Welcome Section */
        .welcome-section {
            padding: 3rem 0;
            text-align: center;
            background: linear-gradient(135deg, #00aaff 0%, #1a2a44 100%);
            color: white;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .welcome-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }

        .welcome-section .btn {
            margin: 0 0.5rem;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .welcome-section .btn-primary {
            background: #ffd700;
            color: #1a2a44;
            border: none;
        }

        .welcome-section .btn-primary:hover {
            background: #e6c200;
            transform: scale(1.05);
        }

        .welcome-section .btn-info {
            background: #17a2b8;
            border: none;
        }

        .welcome-section .btn-info:hover {
            background: #138496;
            transform: scale(1.05);
        }

        /* Search Bar */
        .search-bar {
            max-width: 500px;
            margin: 0 auto 2rem;
        }

        .search-bar .form-control {
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .search-bar .form-control:focus {
            border-color: #00aaff;
            box-shadow: 0 0 10px rgba(0, 170, 255, 0.3);
        }

        /* Job Listings */
        .job-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            padding: 2rem 0;
            opacity: 1;
        }

        .card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            opacity: 1;
            display: block;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-left: 4px solid #00aaff;
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a2a44;
            margin-bottom: 0.75rem;
        }

        .card-text {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .job-meta, .hackathon-meta {
            font-size: 0.9rem;
            color: #888;
            margin-bottom: 1rem;
        }

        .job-meta strong, .hackathon-meta strong {
            color: #1a2a44;
        }

        .job-card-actions, .hackathon-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .bookmark-btn {
            background: none;
            border: none;
            position: absolute;
            top: 1rem;
            right: 1rem;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .bookmark-btn:hover {
            transform: scale(1.1);
        }

        .bookmark-icon {
            width: 24px;
            height: 24px;
            display: block;
        }

        .btn {
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #00aaff;
            border: none;
        }

        .btn-primary:hover {
            background: #0088cc;
            transform: scale(1.05);
        }

        .btn-success {
            background: #28a745;
            border: none;
        }

        .btn-success:hover {
            background: #218838;
            transform: scale(1.05);
        }

        .btn-info {
            background: #17a2b8;
            border: none;
        }

        .btn-info:hover {
            background: #138496;
            transform: scale(1.05);
        }

        .btn-warning {
            background: #f1c40f;
            border: none;
            color: #fff;
        }

        .btn-warning:hover {
            background: #d4ac0d;
            transform: scale(1.05);
        }

        .btn-danger {
            background: #dc3545;
            border: none;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: scale(1.05);
        }

        /* Chat Section */
        .chat-section, .hackathon-section {
            margin: 2rem 0;
            padding: 2rem;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .chat-section h3, .hackathon-section h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 1.5rem;
        }

        .chat-section .list-group-item, .hackathon-section .list-group-item {
            border: none;
            border-bottom: 1px solid #e0e0e0;
            padding: 1rem;
            transition: background-color 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-section .list-group-item:hover, .hackathon-section .list-group-item:hover {
            background-color: #f8f9fa;
        }

        .chat-section .list-group-item strong, .hackathon-section .list-group-item strong {
            color: #1a2a44;
        }

        .chat-section .list-group-item .text-muted, .hackathon-section .list-group-item .text-muted {
            font-size: 0.9rem;
        }

        /* Modal Styling */
        .modal {
            z-index: 1055;
        }

        .modal-backdrop {
            z-index: 1050;
        }

        .modal-content {
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            border: none;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            background: #f8f9fa;
            border-radius: 15px 15px 0 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a2a44;
        }

        .modal-body {
            padding: 2rem;
        }

        .job-details p {
            margin-bottom: 1rem;
            line-height: 1.6;
            color: #666;
        }

        .job-details strong {
            color: #1a2a44;
        }

        .modal-footer {
            border-top: 1px solid #e0e0e0;
            border-radius: 0 0 15px 15px;
            background: #f8f9fa;
        }

        body.modal-open {
            overflow: hidden;
        }

        body {
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* Toast Notification */
        .toast-container {
            z-index: 1060;
        }

        .toast {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* Pagination */
        .pagination .page-link {
            border-radius: 5px;
            margin: 0 5px;
            color: #1a2a44;
            transition: all 0.3s ease;
        }

        .pagination .page-item.active .page-link {
            background: #00aaff;
            border-color: #00aaff;
        }

        .pagination .page-link:hover {
            background: #00aaff;
            color: white;
            border-color: #00aaff;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, #1a2a44 0%, #00aaff 100%);
            color: white;
            padding: 3rem 0 1rem;
            flex-shrink: 0;
        }

        footer h5 {
            color: #ffd700;
            font-weight: 600;
            margin-bottom: 1.5rem;
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

        /* Chatbot Styling */
        .chatbot-container {
            position: fixed;
            bottom: 60px; /* Adjusted to accommodate toggle button */
            right: 20px;
            z-index: 1070; /* Higher than toast (1060) and modal (1055) */
        }

        #toggleChatbot {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1075; /* Above chatbot */
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        zapier-interfaces-chatbot-embed {
            display: none; /* Hidden by default */
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        /* Attempt to hide "Made with Zapier" branding */
        zapier-interfaces-chatbot-embed::part(footer),
        zapier-interfaces-chatbot-embed [slot="footer"] {
            display: none !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .job-list {
                grid-template-columns: 1fr;
            }

            .welcome-section {
                padding: 2rem 1rem;
            }

            .welcome-section h2 {
                font-size: 2rem;
            }

            .navbar {
                padding: 0.5rem 1rem;
            }

            .navbar-brand {
                font-size: 1.5rem;
            }

            .chat-section, .hackathon-section {
                padding: 1rem;
            }

            zapier-interfaces-chatbot-embed {
                width: 300px;
                height: 400px;
            }
        }
    </style>
</head>
<body>
    <!-- Toast Notification Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="bookmarkToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body"></div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php" aria-label="Job Portal Home">Job Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                data-bs-target="#navbarNav" aria-controls="navbarNav" 
                aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../hackathons/hackathons.php" 
                           aria-label="Hackathons">Hackathons</a>
                    </li>
                    <?php if ($user_role === 'employer'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../applications/view_applications.php" 
                               aria-label="View Applications">
                                View Applications
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($user_role === 'jobseeker'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../applications/compare_jobs.php" 
                               aria-label="Compare Jobs">Compare Jobs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../applications/saved_jobs.php" 
                               aria-label="View Saved Jobs">Saved Jobs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="resume_builder.php" 
                               aria-label="Build Resume">Resume Builder</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-actions">
                    <span class="navbar-text me-3" aria-live="polite">
                        Welcome, <?php echo htmlspecialchars($user_name); ?>!
                    </span>
                    <a href="../auth/logout.php" class="btn btn-nav-primary" 
                       aria-label="Logout">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mt-4" role="main">
        <!-- Welcome Section -->
        <section class="welcome-section" aria-labelledby="welcome-heading">
            <h2 class="display-5" id="welcome-heading">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
            <p>Your role: <strong><?php echo ucfirst($user_role); ?></strong></p>
            <?php if ($user_role === 'employer'): ?>
                <a href="../jobs/post_job.php" class="btn btn-primary" 
                   aria-label="Post a new job listing">Post a Job</a>
                <a href="../applications/view_applications.php" class="btn btn-info" 
                   aria-label="View Applications">View Applications</a>
                <a href="../hackathons/hackathons.php" class="btn btn-info" 
                   aria-label="Manage Hackathons">Manage Hackathons</a>
            <?php else: ?>
                <a href="../hackathons/hackathons.php" class="btn btn-info" 
                   aria-label="Explore Hackathons">Explore Hackathons</a>
            <?php endif; ?>
        </section>

        <!-- Hackathon Summary Section -->
        <section class="hackathon-section" aria-labelledby="hackathon-heading">
            <h3 id="hackathon-heading">
                <?php echo $user_role === 'employer' ? 'Your Hackathons' : 'Hackathon Opportunities'; ?>
            </h3>
            <?php if ($user_role === 'employer'): ?>
                <!-- Employer's Hackathons -->
                <h4>Upcoming Hackathons You Created</h4>
                <?php if (empty($upcoming_hackathons)): ?>
                    <div class="alert alert-info rounded-pill text-center" role="alert">
                        You haven't created any upcoming hackathons yet.
                    </div>
                <?php else: ?>
                    <ul class="list-group mb-3">
                        <?php foreach ($upcoming_hackathons as $hackathon): ?>
                            <li class="list-group-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($hackathon['title']); ?></strong>
                                    <p class="mb-0 text-muted">
                                        Date: <?php echo date('M d, Y, H:i A', strtotime($hackathon['date'])); ?> | 
                                        Location: <?php echo htmlspecialchars($hackathon['location']); ?>
                                    </p>
                                </div>
                                <a href="../hackathons/hackathons.php?hackathon_id=<?php echo $hackathon['id']; ?>" 
                                   class="btn btn-info btn-sm" 
                                   aria-label="View details for hackathon <?php echo htmlspecialchars($hackathon['title']); ?>">
                                    View Details
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h4>Past Hackathons You Created</h4>
                <?php if (empty($past_hackathons)): ?>
                    <div class="alert alert-info rounded-pill text-center" role="alert">
                        You have no past hackathons.
                    </div>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($past_hackathons as $hackathon): ?>
                            <li class="list-group-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($hackathon['title']); ?></strong>
                                    <p class="mb-0 text-muted">
                                        Date: <?php echo date('M d, Y, H:i A', strtotime($hackathon['date'])); ?> | 
                                        Location: <?php echo htmlspecialchars($hackathon['location']); ?>
                                    </p>
                                </div>
                                <a href="../hackathons/hackathons.php?hackathon_id=<?php echo $hackathon['id']; ?>" 
                                   class="btn btn-info btn-sm" 
                                   aria-label="View details for past hackathon <?php echo htmlspecialchars($hackathon['title']); ?>">
                                    View Details
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            <?php else: ?>
                <!-- Job Seeker's Hackathons -->
                <h4>Upcoming Hackathons</h4>
                <?php if (empty($upcoming_hackathons)): ?>
                    <div class="alert alert-info rounded-pill text-center" role="alert">
                        No upcoming hackathons available at the moment.
                    </div>
                <?php else: ?>
                    <ul class="list-group mb-3">
                        <?php foreach ($upcoming_hackathons as $hackathon): ?>
                            <li class="list-group-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($hackathon['title']); ?></strong>
                                    <p class="mb-0 text-muted">
                                        Date: <?php echo date('M d, Y, H:i A', strtotime($hackathon['date'])); ?> | 
                                        Organizer: <?php echo htmlspecialchars($hackathon['organizer_name']); ?>
                                    </p>
                                </div>
                                <a href="../hackathons/hackathons.php?hackathon_id=<?php echo $hackathon['id']; ?>" 
                                   class="btn btn-info btn-sm" 
                                   aria-label="View details for hackathon <?php echo htmlspecialchars($hackathon['title']); ?>">
                                    View Details
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h4>Your Registered Hackathons</h4>
                <?php if (empty($registered_hackathons)): ?>
                    <div class="alert alert-info rounded-pill text-center" role="alert">
                        You haven't registered for any hackathons yet.
                    </div>
                <?php else: ?>
                    <ul class="list-group mb-3">
                        <?php foreach ($registered_hackathons as $hackathon): ?>
                            <li class="list-group-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($hackathon['title']); ?></strong>
                                    <p class="mb-0 text-muted">
                                        Date: <?php echo date('M d, Y, H:i A', strtotime($hackathon['date'])); ?> | 
                                        Organizer: <?php echo htmlspecialchars($hackathon['organizer_name']); ?>
                                    </p>
                                </div>
                                <a href="../hackathons/hackathons.php?hackathon_id=<?php echo $hackathon['id']; ?>" 
                                   class="btn btn-info btn-sm" 
                                   aria-label="View details for registered hackathon <?php echo htmlspecialchars($hackathon['title']); ?>">
                                    View Details
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h4>Past Hackathons</h4>
                <?php if (empty($past_hackathons)): ?>
                    <div class="alert alert-info rounded-pill text-center" role="alert">
                        No past hackathons available.
                    </div>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($past_hackathons as $hackathon): ?>
                            <li class="list-group-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($hackathon['title']); ?></strong>
                                    <p class="mb-0 text-muted">
                                        Date: <?php echo date('M d, Y, H:i A', strtotime($hackathon['date'])); ?> | 
                                        Organizer: <?php echo htmlspecialchars($hackathon['organizer_name']); ?>
                                    </p>
                                </div>
                                <a href="../hackathons/hackathons.php?hackathon_id=<?php echo $hackathon['id']; ?>" 
                                   class="btn btn-info btn-sm" 
                                   aria-label="View details for past hackathon <?php echo htmlspecialchars($hackathon['title']); ?>">
                                    View Details
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
            <div class="text-center mt-3">
                <a href="../hackathons/hackathons.php" class="btn btn-primary" 
                   aria-label="View all hackathons">
                    View All Hackathons
                </a>
            </div>
        </section>

        <!-- Chat Section for Employers -->
        <?php if ($user_role === 'employer'): ?>
            <section class="chat-section" aria-labelledby="chat-heading">
                <h3 id="chat-heading">Messages from Job Seekers</h3>
                <?php if (empty($job_seeker_messages)): ?>
                    <div class="alert alert-info rounded-pill text-center" role="alert">
                        No job seekers have messaged you yet.
                    </div>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($job_seeker_messages as $message): ?>
                            <li class="list-group-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($message['name']); ?></strong>
                                    <p class="mb-0 text-muted">
                                        Applied for: <?php echo htmlspecialchars($message['job_title']); ?>
                                    </p>
                                </div>
                                <a href="chat.php?with=<?php echo $message['id']; ?>" 
                                   class="btn btn-primary btn-sm" 
                                   aria-label="Chat with <?php echo htmlspecialchars($message['name']); ?>">
                                    Chat
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- Job Listings Section -->
        <section aria-labelledby="jobs-heading">
            <h3 id="jobs-heading" class="text-center mb-4">Job Listings</h3>
            <div class="search-bar">
                <input type="search" class="form-control" 
                       placeholder="Search jobs..." 
                       aria-label="Search job listings">
            </div>
            
            <div class="job-list" role="region" aria-live="polite">
                <?php if ($jobs->num_rows > 0): ?>
                    <?php while ($job = $jobs->fetch_assoc()): ?>
                        <article class="card" 
                                 data-job-id="<?php echo $job['id']; ?>" 
                                 tabindex="0" aria-label="Job: <?php echo htmlspecialchars($job['title']); ?>">
                            <div class="card-body position-relative">
                                <!-- Bookmark Icon for Job Seekers -->
                                <?php if ($user_role === 'jobseeker'): ?>
                                    <button class="bookmark-btn btn btn-link" 
                                            data-job-id="<?php echo $job['id']; ?>" 
                                            aria-label="<?php echo isset($bookmarked_jobs[$job['id']]) ? 'Remove job from bookmarks' : 'Bookmark job'; ?>">
                                        <img src="<?php echo isset($bookmarked_jobs[$job['id']]) ? '../assets/icons/bookmark-filled.png' : '../assets/icons/bookmark-outline.png'; ?>" 
                                             alt="<?php echo isset($bookmarked_jobs[$job['id']]) ? 'Bookmarked' : 'Not bookmarked'; ?>" 
                                             class="bookmark-icon"
                                             onerror="this.onerror=null; this.src='../assets/icons/bookmark-outline.png';">
                                    </button>
                                <?php endif; ?>
                                
                                <h4 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h4>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                                <p class="job-meta"><strong>Salary:</strong> $<?php echo number_format($job['salary'], 2); ?></p>
                                <p class="job-meta"><strong>Posted:</strong> <?php echo date('M d, Y', strtotime($job['created_at'])); ?></p>
                                <p class="job-meta"><strong>Applicants:</strong> <?php echo $applicants_counts[$job['id']] ?? 0; ?></p>
                                
                                <!-- Job Seeker: Apply Form and Chat with Employer -->
                                <?php if ($user_role === 'jobseeker'): ?>
                                    <form method="POST" action="../applications/apply_job.php" 
                                          enctype="multipart/form-data" class="apply-form d-inline-block">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <div class="mb-3">
                                            <label for="resume-<?php echo $job['id']; ?>" class="form-label">
                                                Upload Resume (PDF, DOCX, max 5MB) <span class="text-danger">*</span>
                                            </label>
                                            <input type="file" name="resume" class="form-control" 
                                                   id="resume-<?php echo $job['id']; ?>" 
                                                   accept=".pdf,.docx" 
                                                   aria-describedby="resumeHelp-<?php echo $job['id']; ?>" 
                                                   required>
                                            <div id="resumeHelp-<?php echo $job['id']; ?>" class="form-text">
                                                Only PDF or DOCX files accepted
                                            </div>
                                        </div>
                                        <div class="job-card-actions">
                                            <button type="submit" class="btn btn-success me-2" 
                                                    aria-label="Apply for <?php echo htmlspecialchars($job['title']); ?>">
                                                Apply Now
                                            </button>
                                            <button type="button" class="btn btn-info view-job-btn me-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#jobDetailsModal-<?php echo $job['id']; ?>" 
                                                    data-job-id="<?php echo $job['id']; ?>" 
                                                    aria-label="View details for <?php echo htmlspecialchars($job['title']); ?>">
                                                View
                                            </button>
                                            <a href="chat.php?with=<?php echo $job['employer_id']; ?>" class="btn btn-primary me-2" 
                                               aria-label="Chat with employer for <?php echo htmlspecialchars($job['title']); ?>">
                                                Chat with Employer
                                            </a>
                                        </div>
                                    </form>
                                <?php endif; ?>

                                <!-- Employer: Edit, Remove -->
                                <?php if ($user_role === 'employer'): ?>
                                    <div class="job-card-actions">
                                        <a href="../jobs/edit_job.php?id=<?php echo $job['id']; ?>" 
                                           class="btn btn-warning me-2" 
                                           aria-label="Edit job <?php echo htmlspecialchars($job['title']); ?>">
                                            Edit
                                        </a>
                                        <form method="POST" action="../jobs/remove_job.php" 
                                              class="d-inline-block delete-form">
                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                            <button type="submit" class="btn btn-danger me-2" 
                                                    onclick="return confirm('Are you sure you want to delete this job?');"
                                                    aria-label="Delete job <?php echo htmlspecialchars($job['title']); ?>">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>

                        <!-- Job Details Modal -->
                        <div class="modal fade" id="jobDetailsModal-<?php echo $job['id']; ?>" tabindex="-1" 
                             aria-labelledby="jobDetailsModalLabel-<?php echo $job['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="jobDetailsModalLabel-<?php echo $job['id']; ?>">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" 
                                                aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="job-details">
                                            <p><strong>Description:</strong></p>
                                            <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                                            <p><strong>Salary:</strong> $<?php echo number_format($job['salary'], 2); ?></p>
                                            <p><strong>Posted On:</strong> <?php echo date('M d, Y', strtotime($job['created_at'])); ?></p>
                                            <?php
                                            $employer_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
                                            $employer_stmt->bind_param("i", $job['employer_id']);
                                            $employer_stmt->execute();
                                            $employer_result = $employer_stmt->get_result();
                                            $employer = $employer_result->fetch_assoc();
                                            $employer_stmt->close();
                                            ?>
                                            <p><strong>Posted By:</strong> <?php echo htmlspecialchars($employer['name'] ?? 'Unknown Employer'); ?></p>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info rounded-pill text-center" 
                         role="alert">No job listings available at the moment.</div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <nav aria-label="Job listings pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" 
                           aria-label="Previous page">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>" 
                           aria-label="Next page">Next</a>
                    </li>
                </ul>
            </nav>
        </section>
    </main>

    <!-- Footer -->
    <footer role="contentinfo">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>Job Portal</h5>
                    <p>Connecting talent with opportunities since 2025.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Home</a></li>
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
                                 alt="Facebook Icon" 
                                 onerror="this.onerror=null; this.parentNode.innerHTML='<span>F</span>';">
                        </a>
                        <a href="https://twitter.com" target="_blank" 
                           aria-label="Follow us on Twitter" title="Twitter">
                            <img src="https://cdn-icons-png.flaticon.com/512/733/733579.png" 
                                 alt="Twitter Icon" 
                                 onerror="this.onerror=null; this.parentNode.innerHTML='<span>T</span>';">
                        </a>
                        <a href="https://linkedin.com" target="_blank" 
                           aria-label="Connect with us on LinkedIn" title="LinkedIn">
                            <img src="https://cdn-icons-png.flaticon.com/512/733/733561.png" 
                                 alt="LinkedIn Icon" 
                                 onerror="this.onerror=null; this.parentNode.innerHTML='<span>L</span>';">
                        </a>
                        <a href="https://instagram.com" target="_blank" 
                           aria-label="Follow us on Instagram" title="Instagram">
                            <img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" 
                                 alt="Instagram Icon" 
                                 onerror="this.onerror=null; this.parentNode.innerHTML='<span>I</span>';">
                        </a>
                    </div>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.2);;">
            <div class="text-center">
                <p class="mb-0">© <?php echo date('Y'); ?> Job Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Chatbot Container with Toggle Button -->
    <div class="chatbot-container" id="chatbotContainer">
        <script async type="module" src="https://interfaces.zapier.com/assets/web-components/zapier-interfaces/zapier-interfaces.esm.js"></script>
        <zapier-interfaces-chatbot-embed 
            is-popup="false" 
            chatbot-id="cm8kj49cb000l12yyt5s0gm8k" 
            height="600px" 
            width="400px">
        </zapier-interfaces-chatbot-embed>
    </div>
    <button id="toggleChatbot" class="btn btn-primary" aria-label="Toggle Chatbot">💬</button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" 
            crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" 
            integrity="sha512-16esztaSRplJROstbIIdwX3N97V1+pZvV33ABoG1H2OyTttBxEGkTsoIVsiP1iaTtM8b3+hu2kB6pQ4Clr5yug==" 
            crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js" 
            integrity="sha512-Ic9xkERjyZ1xgJ5svx3y0u3xrvfT/uPkV99LBwe68xjy/mGtO+4eURHZBW2xW4SZbFrF1Tf090XqB+EVgXnVjw==" 
            crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Existing JavaScript remains unchanged until here...

            // Chatbot Toggle Functionality
            const toggleChatbotBtn = document.getElementById('toggleChatbot');
            const chatbotEmbed = document.querySelector('zapier-interfaces-chatbot-embed');
            
            toggleChatbotBtn.addEventListener('click', () => {
                const isVisible = chatbotEmbed.style.display === 'block';
                chatbotEmbed.style.display = isVisible ? 'none' : 'block';
                toggleChatbotBtn.textContent = isVisible ? '💬' : '✖'; // Change icon based on state
                toggleChatbotBtn.setAttribute('aria-label', isVisible ? 'Show Chatbot' : 'Hide Chatbot');
            });

            // Log the number of job cards for debugging
            const jobCards = document.querySelectorAll('.card');
            console.log('Number of job cards:', jobCards.length);

            // Ensure job cards are initially visible
            jobCards.forEach(card => {
                card.style.opacity = '1';
                card.style.display = 'block';
            });

            // GSAP Animations
            gsap.from('.navbar', {
                duration: 1,
                opacity: 0,
                y: -50,
                ease: 'power2.out'
            });

            gsap.from('.welcome-section', {
                duration: 1.5,
                opacity: 0,
                y: 100,
                ease: 'back.out(1.7)'
            });

            gsap.from('.search-bar', {
                duration: 1,
                opacity: 0,
                scale: 0.5,
                ease: 'back.out(1.7)'
            });

            gsap.from('.card', {
                duration: 1,
                opacity: 0,
                y: 50,
                stagger: 0.2,
                ease: 'power2.out',
                onStart: () => {
                    console.log('Job cards animation started');
                },
                onComplete: () => {
                    console.log('Job cards animation completed');
                }
            });

            gsap.from('.hackathon-section', {
                scrollTrigger: {
                    trigger: '.hackathon-section',
                    start: 'top 80%',
                    toggleActions: 'play none none none'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.chat-section', {
                scrollTrigger: {
                    trigger: '.chat-section',
                    start: 'top 80%',
                    toggleActions: 'play none none none'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('footer', {
                scrollTrigger: {
                    trigger: 'footer',
                    start: 'top 80%',
                    toggleActions: 'play none none none'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            // Search Functionality
            const searchInput = document.querySelector('.search-bar input');
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                jobCards.forEach(card => {
                    const title = card.querySelector('.card-title').textContent.toLowerCase();
                    const description = card.querySelector('.card-text').textContent.toLowerCase();
                    const isVisible = title.includes(searchTerm) || description.includes(searchTerm);
                    card.style.display = isVisible ? 'block' : 'none';
                    card.style.opacity = isVisible ? '1' : '0';
                });
            });

            // Delete Confirmation
            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!confirm('Are you sure you want to delete this job?')) {
                        e.preventDefault();
                    }
                });
            });

            // Bookmark Functionality
            document.querySelectorAll('.bookmark-btn').forEach(button => {
                button.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const jobId = button.getAttribute('data-job-id');
                    const icon = button.querySelector('.bookmark-icon');

                    try {
                        const response = await fetch('../applications/bookmark_job.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `job_id=${jobId}`
                        });

                        const data = await response.json();
                        if (data.success) {
                            if (data.bookmarked) {
                                icon.src = '/assets/icons/bookmark-filled.png';
                                button.setAttribute('aria-label', 'Remove job from bookmarks');
                            } else {
                                icon.src = '/assets/icons/bookmark-outline.png';
                                button.setAttribute('aria-label', 'Bookmark job');
                            }
                            const toast = new bootstrap.Toast(document.getElementById('bookmarkToast'));
                            document.querySelector('#bookmarkToast .toast-body').textContent = data.message;
                            toast.show();
                        } else {
                            console.error('Bookmark action failed:', data.message);
                        }
                    } catch (error) {
                        console.error('Error toggling bookmark:', error);
                    }
                });
            });

            // Modal Animation for Job Details
            document.querySelectorAll('.view-job-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const modal = document.querySelector(button.getAttribute('data-bs-target'));
                    if (modal) {
                        modal.style.display = 'block';
                        gsap.from(modal.querySelector('.modal-content'), {
                            duration: 0.5,
                            scale: 0.8,
                            opacity: 0,
                            ease: 'back.out(1.7)'
                        });
                    }
                });
            });

            // Fix scrolling when job modal is closed
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('hidden.bs.modal', () => {
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = 'auto';
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                });

                modal.addEventListener('shown.bs.modal', () => {
                    document.body.classList.add('modal-open');
                    document.body.style.overflow = 'hidden';
                });
            });
        });
    </script>
</body>
</html>