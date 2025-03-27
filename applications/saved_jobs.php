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
$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Guest', ENT_QUOTES, 'UTF-8');
$user_role = $_SESSION['user_role'] ?? 'guest';

// Ensure the user is a job seeker
if ($user_role !== 'jobseeker') {
    header("Location: ../views/dashboard.php");
    exit();
}

// Fetch bookmarked jobs with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total number of bookmarked jobs
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM bookmarked_jobs WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_stmt->bind_result($total_jobs);
$count_stmt->fetch();
$count_stmt->close();
$total_pages = ceil($total_jobs / $limit);

// Fetch bookmarked jobs
$stmt = $conn->prepare("
    SELECT j.*, bj.created_at AS bookmarked_at 
    FROM jobs j
    INNER JOIN bookmarked_jobs bj ON j.id = bj.job_id
    WHERE bj.user_id = ?
    ORDER BY bj.created_at DESC
    LIMIT ?, ?
");
if (!$stmt) {
    error_log("Failed to prepare statement for bookmarked jobs: " . $conn->error);
    $jobs = null;
} else {
    $stmt->bind_param("iii", $user_id, $offset, $limit);
    $stmt->execute();
    $jobs = $stmt->get_result();
    $stmt->close();
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
    <meta name="description" content="View your saved jobs on the RookieRise">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; 
        script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com 'nonce-<?php echo $nonce; ?>'; 
        style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'nonce-<?php echo $nonce; ?>'; 
        img-src 'self' data: https://cdn-icons-png.flaticon.com;">
    <title>Saved Jobs | RookieRise</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" 
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" 
            crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" 
          crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style nonce="<?php echo $nonce; ?>">
        body {
            background: #f0f4f8;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
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
            font-weight: 700;
            font-size: 1.8rem;
            color: #1a2a44;
            transition: color 0.3s ease;
        }

        .navbar-brand:hover { color: #00aaff; }

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
            background: linear-gradient(135deg, #00aaff 0%, #1a2a44 100%);
            color: white;
            border-radius: 15px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .welcome-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        /* Job List */
        .job-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            padding: 2rem 0;
            opacity: 1; /* Ensure initial visibility */
        }

        .card {
            border: none;
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            opacity: 1; /* Ensure initial visibility */
            display: block; /* Ensure display is not overridden */
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
            border-left: 4px solid #00aaff;
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-title {
            color: #1a2a44;
            font-weight: 700;
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
        }

        .card-text {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .card-text p {
            margin-bottom: 0.5rem;
        }

        .btn {
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-success {
            background: #27ae60;
            border: none;
        }

        .btn-success:hover {
            background: #219653;
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

        /* Loading Spinner */
        .loading-spinner {
            text-align: center;
            padding: 2rem;
            display: none;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #00aaff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

        /* Focus Styles */
        :focus-visible {
            outline: 2px solid #00aaff;
            outline-offset: 2px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .job-list { grid-template-columns: 1fr; }
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
                        <a class="nav-link" href="../views/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../applications/job_listings.php">Job Listings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../applications/compare_jobs.php" 
                           aria-label="Compare Jobs">Compare Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../applications/saved_jobs.php" 
                           aria-label="View Saved Jobs" aria-current="page">Saved Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../views/resume_builder.php">Resume Builder</a>
                    </li>
                </ul>
                <div class="navbar-actions">
                    <span class="navbar-text me-3" aria-live="polite">
                        Welcome, <?php echo $user_name; ?>
                    </span>
                    <a href="../auth/logout.php" class="btn btn-nav-primary" 
                       aria-label="Logout">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mt-5" role="main">
        <section class="welcome-section" aria-labelledby="welcome-heading">
            <h2 class="display-5 mb-3" id="welcome-heading">Your Saved Jobs</h2>
            <p class="lead">Review and apply for your bookmarked jobs</p>
        </section>

        <section aria-labelledby="saved-jobs-heading">
            <h3 id="saved-jobs-heading" class="mb-4">Bookmarked Jobs</h3>
            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner"></div>
                <p>Loading saved jobs...</p>
            </div>
            <div class="job-list" id="jobList" role="region" aria-live="polite">
                <?php if ($jobs && $jobs->num_rows > 0): ?>
                    <?php while ($job = $jobs->fetch_assoc()): ?>
                        <article class="card" 
                                 data-job-id="<?php echo $job['id']; ?>" 
                                 tabindex="0" 
                                 aria-label="Job: <?php echo htmlspecialchars($job['title']); ?>">
                            <div class="card-body">
                                <h4 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h4>
                                <div class="card-text">
                                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                                    <p><strong>Salary:</strong> $<?php echo number_format($job['salary'], 2); ?></p>
                                    <p><small class="text-muted">Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?></small></p>
                                    <p><small class="text-muted">Saved: <?php echo date('M d, Y', strtotime($job['bookmarked_at'])); ?></small></p>
                                </div>
                                <form method="POST" action="../applications/apply_job.php" 
                                      enctype="multipart/form-data" class="apply-form mb-2">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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
                                <button class="btn btn-danger remove-bookmark" 
                                        data-job-id="<?php echo $job['id']; ?>" 
                                        aria-label="Remove bookmark for <?php echo htmlspecialchars($job['title']); ?>">
                                    Remove Bookmark
                                </button>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info rounded-pill text-center" 
                         role="alert">
                        You have no saved jobs at the moment. 
                        <a href="../applications/job_listings.php" class="alert-link">Browse jobs now!</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Saved jobs pagination" class="mt-4">
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
            <?php endif; ?>
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
                        <li><a href="../views/dashboard.php">Home</a></li>
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
                duration: 1.2, 
                opacity: 0, 
                y: -100, 
                ease: 'back.out(1.7)' 
            });

            // Animate job cards directly
            gsap.from('.card', {
                duration: 0.8,
                opacity: 0,
                y: 50,
                stagger: 0.2,
                ease: 'power2.out',
                delay: 0.4,
                onStart: () => {
                    console.log('Job cards animation started');
                },
                onComplete: () => {
                    console.log('Job cards animation completed');
                }
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

            // Remove bookmark functionality
            document.querySelectorAll('.remove-bookmark').forEach(button => {
                button.addEventListener('click', function() {
                    const jobId = this.getAttribute('data-job-id');
                    if (confirm('Are you sure you want to remove this job from your bookmarks?')) {
                        $.post('remove_bookmark.php', { job_id: jobId }, function(response) {
                            if (response.success) {
                                document.querySelector(`.card[data-job-id="${jobId}"]`).remove();
                                const remainingCards = document.querySelectorAll('.card').length;
                                if (remainingCards === 0) {
                                    document.querySelector('.job-list').innerHTML = `
                                        <div class="alert alert-info rounded-pill text-center" role="alert">
                                            You have no saved jobs at the moment. 
                                            <a href="../applications/job_listings.php" class="alert-link">Browse jobs now!</a>
                                        </div>
                                    `;
                                }
                            } else {
                                alert('Error removing bookmark. Please try again.');
                            }
                        }, 'json').fail(function() {
                            alert('Error removing bookmark. Please try again.');
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>