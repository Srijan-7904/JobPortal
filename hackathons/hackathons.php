<?php
session_start();
require __DIR__ . '/../includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

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

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 25;
$offset = ($page - 1) * $limit;

// Fetch total number of active hackathons for pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM hackathons WHERE is_active = 1");
$count_stmt->execute();
$count_stmt->bind_result($total_hackathons);
$count_stmt->fetch();
$count_stmt->close();
$total_pages = ceil($total_hackathons / $limit);

// Fetch active hackathons with pagination
$stmt = $conn->prepare("SELECT h.*, u.name AS organizer_name FROM hackathons h JOIN users u ON h.employer_id = u.id WHERE h.is_active = 1 ORDER BY h.date ASC LIMIT ?, ?");
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$hackathons = $stmt->get_result();
$stmt->close();

// Fetch past hackathons (limited to 5 for display)
$past_stmt = $conn->prepare("SELECT h.*, u.name AS organizer_name FROM hackathons h JOIN users u ON h.employer_id = u.id WHERE h.is_active = 0 ORDER BY h.date DESC LIMIT 5");
$past_stmt->execute();
$past_hackathons = $past_stmt->get_result();
$past_stmt->close();

// Fetch registered hackathons for the current user (if job seeker)
$registered_hackathons = [];
if ($user_role === 'jobseeker') {
    $reg_stmt = $conn->prepare("SELECT hackathon_id FROM hackathon_registrations WHERE user_id = ?");
    $reg_stmt->bind_param("i", $user_id);
    $reg_stmt->execute();
    $result = $reg_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $registered_hackathons[$row['hackathon_id']] = true;
    }
    $reg_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Hackathons for Freshers and Students - Join exciting coding challenges">
    <meta name="keywords" content="hackathons, freshers, students, coding, challenges">
    <meta name="author" content="Job Portal Team">
    <title>Hackathons | Job Portal</title>
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

        /* Hackathon Listings */
        .hackathon-list {
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

        .hackathon-meta {
            font-size: 0.9rem;
            color: #888;
            margin-bottom: 1rem;
        }

        .hackathon-meta strong {
            color: #1a2a44;
        }

        .hackathon-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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

        /* Past Hackathons Section */
        .past-hackathons {
            margin: 2rem 0;
            padding: 2rem;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .past-hackathons h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 1.5rem;
        }

        .past-hackathons .list-group-item {
            border: none;
            border-bottom: 1px solid #e0e0e0;
            padding: 1rem;
            transition: background-color 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .past-hackathons .list-group-item:hover {
            background-color: #f8f9fa;
        }

        .past-hackathons .list-group-item strong {
            color: #1a2a44;
        }

        .past-hackathons .list-group-item .text-muted {
            font-size: 0.9rem;
        }

        /* Modal Styling */
        .modal-content {
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            border: none;
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

        .hackathon-details p {
            margin-bottom: 1rem;
            line-height: 1.6;
            color: #666;
        }

        .hackathon-details strong {
            color: #1a2a44;
        }

        .modal-footer {
            border-top: 1px solid #e0e0e0;
            border-radius: 0 0 15px 15px;
            background: #f8f9fa;
        }

        /* Toast Notification */
        .toast-container {
            z-index: 1055;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .hackathon-list {
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

            .past-hackathons {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Toast Notification Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="registerToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
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
                        <a class="nav-link" href="../views/dashboard.php" aria-label="Dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#" aria-current="page" aria-label="Hackathons">Hackathons</a>
                    </li>
                    <?php if ($user_role === 'employer'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../applications/view_applications.php" 
                               aria-label="View Applications">View Applications</a>
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
                            <a class="nav-link" href="../resume_builder.php" 
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
            <h2 class="display-5" id="welcome-heading">Hackathons for Freshers & Students</h2>
            <p>Join exciting coding challenges to showcase your skills!</p>
            <?php if ($user_role === 'employer'): ?>
                <a href="#postHackathonModal" class="btn btn-primary" 
                   data-bs-toggle="modal" aria-label="Post a new hackathon">Post a Hackathon</a>
            <?php endif; ?>
        </section>

        <!-- Post Hackathon Modal (for Employers) -->
        <?php if ($user_role === 'employer'): ?>
            <div class="modal fade" id="postHackathonModal" tabindex="-1" 
                 aria-labelledby="postHackathonModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="postHackathonModalLabel">Post a New Hackathon</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" 
                                    aria-label="Close"></button>
                        </div>
                        <form method="POST" action="post_hackathon.php">
                            <div class="modal-body">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="date" class="form-label">Date and Time</label>
                                    <input type="datetime-local" class="form-control" id="date" name="date" required>
                                </div>
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" required>
                                </div>
                                <div class="mb-3">
                                    <label for="organizer" class="form-label">Organizer</label>
                                    <input type="text" class="form-control" id="organizer" name="organizer" required>
                                </div>
                                <div class="mb-3">
                                    <label for="registration_deadline" class="form-label">Registration Deadline</label>
                                    <input type="datetime-local" class="form-control" id="registration_deadline" name="registration_deadline" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Post Hackathon</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Hackathon Listings Section -->
        <section aria-labelledby="hackathons-heading">
            <h3 id="hackathons-heading" class="text-center mb-4">Upcoming Hackathons</h3>
            <div class="search-bar">
                <input type="search" class="form-control" 
                       placeholder="Search hackathons..." 
                       aria-label="Search hackathons">
            </div>
            
            <div class="hackathon-list" role="region" aria-live="polite">
                <?php if ($hackathons->num_rows > 0): ?>
                    <?php while ($hackathon = $hackathons->fetch_assoc()): ?>
                        <article class="card" 
                                 data-hackathon-id="<?php echo $hackathon['id']; ?>" 
                                 tabindex="0" 
                                 aria-label="Hackathon: <?php echo htmlspecialchars($hackathon['title']); ?>">
                            <div class="card-body">
                                <h4 class="card-title"><?php echo htmlspecialchars($hackathon['title']); ?></h4>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($hackathon['description'])); ?></p>
                                <p class="hackathon-meta"><strong>Date:</strong> <?php echo date('M d, Y, H:i', strtotime($hackathon['date'])); ?></p>
                                <p class="hackathon-meta"><strong>Location:</strong> <?php echo htmlspecialchars($hackathon['location']); ?></p>
                                <p class="hackathon-meta"><strong>Organizer:</strong> <?php echo htmlspecialchars($hackathon['organizer_name']); ?></p>
                                <p class="hackathon-meta"><strong>Registration Deadline:</strong> <?php echo date('M d, Y, H:i', strtotime($hackathon['registration_deadline'])); ?></p>

                                <div class="hackathon-actions">
                                    <?php if ($user_role === 'jobseeker'): ?>
                                        <?php if (isset($registered_hackathons[$hackathon['id']])): ?>
                                            <button class="btn btn-success" disabled 
                                                    aria-label="Already registered for <?php echo htmlspecialchars($hackathon['title']); ?>">
                                                Registered
                                            </button>
                                        <?php else: ?>
                                            <form method="POST" action="register_hackathon.php" class="register-form d-inline-block">
                                                <input type="hidden" name="hackathon_id" value="<?php echo $hackathon['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                <button type="submit" class="btn btn-success" 
                                                        aria-label="Register for <?php echo htmlspecialchars($hackathon['title']); ?>">
                                                    Register
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-info view-hackathon-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#hackathonDetailsModal-<?php echo $hackathon['id']; ?>" 
                                                data-hackathon-id="<?php echo $hackathon['id']; ?>" 
                                                aria-label="View details for <?php echo htmlspecialchars($hackathon['title']); ?>">
                                            View Details
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($user_role === 'employer' && $hackathon['employer_id'] == $user_id): ?>
                                        <a href="#editHackathonModal-<?php echo $hackathon['id']; ?>" 
                                           class="btn btn-warning" 
                                           data-bs-toggle="modal" 
                                           aria-label="Edit hackathon <?php echo htmlspecialchars($hackathon['title']); ?>">
                                            Edit
                                        </a>
                                        <form method="POST" action="mark_hackathon_past.php" class="d-inline-block">
                                            <input type="hidden" name="hackathon_id" value="<?php echo $hackathon['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <button type="submit" class="btn btn-danger" 
                                                    onclick="return confirm('Are you sure you want to mark this hackathon as past?');"
                                                    aria-label="Mark hackathon <?php echo htmlspecialchars($hackathon['title']); ?> as past">
                                                Mark as Past
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>

                        <!-- Hackathon Details Modal -->
                        <div class="modal fade" id="hackathonDetailsModal-<?php echo $hackathon['id']; ?>" tabindex="-1" 
                             aria-labelledby="hackathonDetailsModalLabel-<?php echo $hackathon['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="hackathonDetailsModalLabel-<?php echo $hackathon['id']; ?>">
                                            <?php echo htmlspecialchars($hackathon['title']); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" 
                                                aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="hackathon-details">
                                            <p><strong>Description:</strong></p>
                                            <p><?php echo nl2br(htmlspecialchars($hackathon['description'])); ?></p>
                                            <p><strong>Date:</strong> <?php echo date('M d, Y, H:i', strtotime($hackathon['date'])); ?></p>
                                            <p><strong>Location:</strong> <?php echo htmlspecialchars($hackathon['location']); ?></p>
                                            <p><strong>Organizer:</strong> <?php echo htmlspecialchars($hackathon['organizer_name']); ?></p>
                                            <p><strong>Registration Deadline:</strong> <?php echo date('M d, Y, H:i', strtotime($hackathon['registration_deadline'])); ?></p>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Hackathon Modal (for Employers) -->
                        <?php if ($user_role === 'employer' && $hackathon['employer_id'] == $user_id): ?>
                            <div class="modal fade" id="editHackathonModal-<?php echo $hackathon['id']; ?>" tabindex="-1" 
                                 aria-labelledby="editHackathonModalLabel-<?php echo $hackathon['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editHackathonModalLabel-<?php echo $hackathon['id']; ?>">
                                                Edit Hackathon: <?php echo htmlspecialchars($hackathon['title']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" 
                                                    aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="update_hackathon.php">
                                            <div class="modal-body">
                                                <input type="hidden" name="hackathon_id" value="<?php echo $hackathon['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                <div class="mb-3">
                                                    <label for="title-<?php echo $hackathon['id']; ?>" class="form-label">Title</label>
                                                    <input type="text" class="form-control" id="title-<?php echo $hackathon['id']; ?>" 
                                                           name="title" value="<?php echo htmlspecialchars($hackathon['title']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="description-<?php echo $hackathon['id']; ?>" class="form-label">Description</label>
                                                    <textarea class="form-control" id="description-<?php echo $hackathon['id']; ?>" 
                                                              name="description" rows="3" required><?php echo htmlspecialchars($hackathon['description']); ?></textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="date-<?php echo $hackathon['id']; ?>" class="form-label">Date and Time</label>
                                                    <input type="datetime-local" class="form-control" id="date-<?php echo $hackathon['id']; ?>" 
                                                           name="date" value="<?php echo date('Y-m-d\TH:i', strtotime($hackathon['date'])); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="location-<?php echo $hackathon['id']; ?>" class="form-label">Location</label>
                                                    <input type="text" class="form-control" id="location-<?php echo $hackathon['id']; ?>" 
                                                           name="location" value="<?php echo htmlspecialchars($hackathon['location']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="organizer-<?php echo $hackathon['id']; ?>" class="form-label">Organizer</label>
                                                    <input type="text" class="form-control" id="organizer-<?php echo $hackathon['id']; ?>" 
                                                           name="organizer" value="<?php echo htmlspecialchars($hackathon['organizer']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="registration_deadline-<?php echo $hackathon['id']; ?>" class="form-label">Registration Deadline</label>
                                                    <input type="datetime-local" class="form-control" id="registration_deadline-<?php echo $hackathon['id']; ?>" 
                                                           name="registration_deadline" value="<?php echo date('Y-m-d\TH:i', strtotime($hackathon['registration_deadline'])); ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Update Hackathon</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info rounded-pill text-center" 
                         role="alert">No upcoming hackathons available at the moment.</div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <nav aria-label="Hackathon listings pagination">
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

        <!-- Past Hackathons Section -->
        <section class="past-hackathons" aria-labelledby="past-hackathons-heading">
            <h3 id="past-hackathons-heading">Past Hackathons</h3>
            <?php if ($past_hackathons->num_rows > 0): ?>
                <ul class="list-group">
                    <?php while ($past_hackathon = $past_hackathons->fetch_assoc()): ?>
                        <li class="list-group-item">
                            <div>
                                <strong><?php echo htmlspecialchars($past_hackathon['title']); ?></strong>
                                <p class="mb-0 text-muted">
                                    Held on: <?php echo date('M d, Y', strtotime($past_hackathon['date'])); ?> | 
                                    Organizer: <?php echo htmlspecialchars($past_hackathon['organizer_name']); ?>
                                </p>
                            </div>
                            <button type="button" class="btn btn-info btn-sm view-hackathon-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#pastHackathonDetailsModal-<?php echo $past_hackathon['id']; ?>" 
                                    aria-label="View details for past hackathon <?php echo htmlspecialchars($past_hackathon['title']); ?>">
                                View Details
                            </button>
                        </li>

                        <!-- Past Hackathon Details Modal -->
                        <div class="modal fade" id="pastHackathonDetailsModal-<?php echo $past_hackathon['id']; ?>" tabindex="-1" 
                             aria-labelledby="pastHackathonDetailsModalLabel-<?php echo $past_hackathon['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="pastHackathonDetailsModalLabel-<?php echo $past_hackathon['id']; ?>">
                                            <?php echo htmlspecialchars($past_hackathon['title']); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" 
                                                aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="hackathon-details">
                                            <p><strong>Description:</strong></p>
                                            <p><?php echo nl2br(htmlspecialchars($past_hackathon['description'])); ?></p>
                                            <p><strong>Date:</strong> <?php echo date('M d, Y, H:i', strtotime($past_hackathon['date'])); ?></p>
                                            <p><strong>Location:</strong> <?php echo htmlspecialchars($past_hackathon['location']); ?></p>
                                            <p><strong>Organizer:</strong> <?php echo htmlspecialchars($past_hackathon['organizer_name']); ?></p>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="alert alert-info rounded-pill text-center" role="alert">
                    No past hackathons available.
                </div>
            <?php endif; ?>
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
                        <li><a href="../dashboard.php">Home</a></li>
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
            <hr style="border-color: rgba(255,255,255,0.2);">
            <div class="text-center">
                <p class="mb-0">© <?php echo date('Y'); ?> Job Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>

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
                ease: 'power2.out'
            });

            gsap.from('.past-hackathons', {
                scrollTrigger: {
                    trigger: '.past-hackathons',
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
            const hackathonCards = document.querySelectorAll('.card');
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                hackathonCards.forEach(card => {
                    const title = card.querySelector('.card-title').textContent.toLowerCase();
                    const description = card.querySelector('.card-text').textContent.toLowerCase();
                    const isVisible = title.includes(searchTerm) || description.includes(searchTerm);
                    card.style.display = isVisible ? 'block' : 'none';
                    card.style.opacity = isVisible ? '1' : '0';
                });
            });

            // Register Functionality
            document.querySelectorAll('.register-form').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(form);
                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        const toast = new bootstrap.Toast(document.getElementById('registerToast'));
                        document.querySelector('#registerToast .toast-body').textContent = data.message;
                        toast.show();
                        if (data.success) {
                            setTimeout(() => location.reload(), 2000);
                        }
                    } catch (error) {
                        console.error('Error registering for hackathon:', error);
                    }
                });
            });

            // Modal Animation
            document.querySelectorAll('.view-hackathon-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const modal = document.querySelector(button.getAttribute('data-bs-target'));
                    gsap.from(modal.querySelector('.modal-content'), {
                        duration: 0.5,
                        scale: 0.8,
                        opacity: 0,
                        ease: 'back.out(1.7)'
                    });
                });
            });

            // Show toast based on URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                const toast = new bootstrap.Toast(document.getElementById('registerToast'));
                let message = '';
                if (urlParams.get('success') === 'registered') {
                    message = 'Successfully registered for the hackathon!';
                } else if (urlParams.get('success') === 'updated') {
                    message = 'Hackathon updated successfully!';
                } else if (urlParams.get('success') === 'marked_past') {
                    message = 'Hackathon marked as past successfully!';
                } else if (urlParams.get('success') === '1') {
                    message = 'Hackathon posted successfully!';
                }
                document.querySelector('#registerToast .toast-body').textContent = message;
                toast.show();
            } else if (urlParams.has('error')) {
                const toast = new bootstrap.Toast(document.getElementById('registerToast'));
                let message = 'An error occurred. Please try again.';
                if (urlParams.get('error') === 'already_registered') {
                    message = 'You are already registered for this hackathon.';
                } else if (urlParams.get('error') === 'unauthorized') {
                    message = 'You are not authorized to perform this action.';
                }
                document.querySelector('#registerToast .toast-body').textContent = message;
                toast.show();
            }
        });
    </script>
</body>
</html>