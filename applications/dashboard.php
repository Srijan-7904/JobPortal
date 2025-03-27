<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Generate nonce for CSP
$nonce = base64_encode(random_bytes(16));

// Helper Functions
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function redirect($location) {
    header("Location: $location");
    exit();
}

function getUserRole($conn, $user_id) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['role'] ?? 'guest';
}

function getJobListings($conn, $user_role, $user_id) {
    try {
        $query = ($user_role === 'employer') 
            ? "SELECT id, title, description, salary, created_at FROM jobs WHERE employer_id = ? ORDER BY created_at DESC"
            : "SELECT id, title, description, salary, created_at FROM jobs ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($query);
        if ($user_role === 'employer') {
            $stmt->bind_param("i", $user_id);
        }
        $stmt->execute();
        return $stmt->get_result();
    } catch (Exception $e) {
        error_log("Error fetching job listings: " . $e->getMessage());
        return false;
    }
}

function getNewApplicationsCount($conn, $user_id) {
    try {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) as count FROM applications 
            WHERE job_id IN (SELECT id FROM jobs WHERE employer_id = ?) 
            AND status IN ('pending', NULL)"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Error fetching applications count: " . $e->getMessage());
        return 0;
    }
}

function formatCurrency($amount) {
    return '$' . number_format(floatval($amount), 2);
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function isNewJob($created_at) {
    $now = new DateTime();
    $job_date = new DateTime($created_at);
    $interval = $now->diff($job_date);
    return $interval->days <= 7;
}

// Authentication Check
if (!isAuthenticated()) {
    redirect('login.php');
}

// Input Sanitization
$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if ($user_id === false) {
    session_destroy();
    redirect('login.php');
}

$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Guest', ENT_QUOTES, 'UTF-8');
$user_role = $_SESSION['user_role'] ?? null;

if (!$user_role) {
    $user_role = getUserRole($conn, $user_id);
    $_SESSION['user_role'] = $user_role;
}

$jobs = getJobListings($conn, $user_role, $user_id);
$new_applications = ($user_role === 'employer') ? getNewApplicationsCount($conn, $user_id) : 0;

// Check for messages from apply_job.php
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="RookieRise Dashboard - Manage your job listings and applications">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; 
        script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'nonce-<?php echo $nonce; ?>'; 
        style-src 'self' https://cdn.jsdelivr.net 'nonce-<?php echo $nonce; ?>'; 
        img-src 'self' data: https://img.icons8.com;">
    <title>Dashboard | RookieRise</title>
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
        }

        main { flex: 1 0 auto; }

        .navbar {
            background: #f0f2f5;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            padding: 0.5rem 2rem;
            border-radius: 0 0 8px 8px;
            border-bottom: 2px solid #e0e0e0;
        }

        .navbar-brand {
            font-weight: bold;
            font-size: 2rem;
            color: #000;
            transition: color 0.3s ease;
        }

        .navbar-brand:hover { color: #ffd700; }

        .nav-link {
            color: #333;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: color 0.3s ease, background-color 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: #000;
            background-color: rgba(255, 215, 0, 0.1);
        }

        .btn-nav-primary {
            background: #000;
            color: white;
            border: none;
            margin-left: 1rem;
            border-radius: 20px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-nav-primary:hover {
            background: #333;
            transform: scale(1.05);
        }

        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }

        .search-bar {
            max-width: 400px;
            margin: 2rem 0;
        }

        .job-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            padding: 2rem 0;
        }

        .card {
            border: none;
            border-radius: 15px;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
            border-left: 4px solid #ffd700;
        }

        .card.new-job::before {
            content: 'New';
            position: absolute;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-title {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
        }

        .card-text {
            color: #555;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .job-card-actions {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card:hover .job-card-actions { opacity: 1; }

        .btn {
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: #3498db;
            border: none;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: scale(1.05);
        }

        .btn-success {
            background: #27ae60;
            border: none;
        }

        .btn-success:hover {
            background: #219653;
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
            background: #e74c3c;
            border: none;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: scale(1.05);
        }

        footer {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 2rem;
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

        :focus-visible {
            outline: 2px solid #ffd700;
            outline-offset: 2px;
        }

        @media (max-width: 768px) {
            .job-list { grid-template-columns: 1fr; }
            .navbar { padding: 0.5rem 1rem; }
            .navbar-brand { font-size: 1.5rem; }
            .social-icons img { width: 24px; height: 24px; margin: 0 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand" href="#" aria-label="RookieRise Home">RookieRise</a>
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
                    <?php if ($user_role === 'employer'): ?>
                        <li class="nav-item position-relative">
                            <a class="nav-link" href="../applications/view_applications.php" 
                               aria-label="View Applications with <?php echo $new_applications; ?> new">
                                View Applications
                                <?php if ($new_applications > 0): ?>
                                    <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                                        <?php echo $new_applications; ?>
                                        <span class="visually-hidden">new applications</span>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($user_role === 'job_seeker'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="http://localhost/JobPortal/applications/compare_jobs.php" 
                               aria-label="Compare Jobs">Compare Jobs</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-actions">
                    <span class="navbar-text me-3" aria-live="polite">
                        Welcome, <?php echo $user_name; ?>
                    </span>
                    <a href="../auth/logout.php" class="btn btn-nav btn-nav-primary" 
                       aria-label="Logout">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mt-5" role="main">
        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message['type']); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message['text']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <section class="welcome-section" aria-labelledby="welcome-heading">
            <h2 class="display-5 mb-3" id="welcome-heading">Welcome, <?php echo $user_name; ?>!</h2>
            <p class="lead">Your role: <strong><?php echo ucfirst($user_role); ?></strong></p>
            <?php if ($user_role === 'employer'): ?>
                <a href="../jobs/post_job.php" class="btn btn-primary" 
                   aria-label="Post a new job listing">Post New Job</a>
            <?php endif; ?>
        </section>

        <section aria-labelledby="jobs-heading">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 id="jobs-heading">Job Listings</h3>
                <div class="search-bar">
                    <input type="search" class="form-control" 
                           placeholder="Search jobs..." 
                           aria-label="Search job listings">
                </div>
            </div>
            
            <div class="job-list" role="region" aria-live="polite">
                <?php if ($jobs && $jobs->num_rows > 0): ?>
                    <?php while ($job = $jobs->fetch_assoc()): ?>
                        <article class="card <?php echo isNewJob($job['created_at']) ? 'new-job' : ''; ?>" 
                                 data-job-id="<?php echo $job['id']; ?>" 
                                 tabindex="0" aria-label="Job: <?php echo htmlspecialchars($job['title']); ?>">
                            <div class="card-body">
                                <h4 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h4>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                                <p><strong>Salary:</strong> <?php echo formatCurrency($job['salary']); ?></p>
                                <p><small class="text-muted">Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?></small></p>
                                
                                <?php if ($user_role === 'job_seeker'): ?>
                                    <form method="POST" action="../applications/apply_job.php" 
                                          enctype="multipart/form-data" class="apply-form">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
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
                                        <button type="submit" class="btn btn-success" 
                                                aria-label="Apply for <?php echo htmlspecialchars($job['title']); ?>">
                                            Apply Now
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($user_role === 'employer'): ?>
                                    <div class="card-actions job-card-actions d-flex gap-2">
                                        <a href="../jobs/edit_job.php?id=<?php echo $job['id']; ?>" 
                                           class="btn btn-warning" 
                                           aria-label="Edit job <?php echo htmlspecialchars($job['title']); ?>">
                                            Edit
                                        </a>
                                        <form method="POST" action="../jobs/remove_job.php" 
                                              class="d-inline-block delete-form">
                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <button type="submit" class="btn btn-danger" 
                                                    aria-label="Delete job <?php echo htmlspecialchars($job['title']); ?>">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info rounded-pill text-center" 
                         role="alert">No job listings available at the moment.</div>
                <?php endif; ?>
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
                            <img src="https://img.icons8.com/color/48/000000/facebook-new.png" 
                                 alt="Facebook Icon">
                        </a>
                        <a href="https://twitter.com" target="_blank" 
                           aria-label="Follow us on Twitter" title="Twitter">
                            <img src="https://img.icons8.com/color/48/000000/twitter--v1.png" 
                                 alt="Twitter Icon">
                        </a>
                        <a href="https://linkedin.com" target="_blank" 
                           aria-label="Connect with us on LinkedIn" title="LinkedIn">
                            <img src="https://img.icons8.com/color/48/000000/linkedin.png" 
                                 alt="LinkedIn Icon">
                        </a>
                        <a href="https://google.com" target="_blank" 
                           aria-label="Follow us on Google" title="Google">
                            <img src="https://img.icons8.com/color/48/000000/google-logo.png" 
                                 alt="Google Icon">
                        </a>
                    </div>
                </div>
            </div>
            <hr>
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
            gsap.from('.welcome-section', { duration: 1.2, opacity: 0, y: -100, ease: 'back.out(1.7)' });

            const searchInput = document.querySelector('.search-bar input');
            const jobCards = document.querySelectorAll('.card');
            
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                jobCards.forEach(card => {
                    const title = card.querySelector('.card-title').textContent.toLowerCase();
                    const description = card.querySelector('.card-text').textContent.toLowerCase();
                    card.style.display = (title.includes(searchTerm) || description.includes(searchTerm)) ? 'block' : 'none';
                });
            });

            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!confirm('Are you sure you want to delete this job?')) {
                        e.preventDefault();
                    }
                });
            });

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        gsap.from(entry.target, { opacity: 0, y: 50, duration: 0.8, ease: 'power2.out' });
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            jobCards.forEach(card => observer.observe(card));

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

            document.querySelectorAll('.apply-form').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    const spinner = document.querySelector('.loading-spinner');
                    spinner.style.display = 'block';
                    try {
                        await new Promise(resolve => setTimeout(resolve, 1000));
                    } finally {
                        spinner.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>