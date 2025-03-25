<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if ($user_id === false) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Employer', ENT_QUOTES, 'UTF-8');

// Fetch applications with error handling (updated query to include applicant_id)
try {
    $stmt = $conn->prepare("
        SELECT a.id, a.resume, u.id AS applicant_id, u.name AS applicant_name, j.title AS job_title
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN jobs j ON a.job_id = j.id
        WHERE j.employer_id = ?
        ORDER BY a.id DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $applications = $stmt->get_result();
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching applications: " . $e->getMessage());
    $applications = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="View and manage job applications received for your postings">
    <title>View Applications | Job Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" 
          crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
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

        .welcome-section p {
            font-size: 1.2rem;
            margin-bottom: 0;
        }

        /* Search Bar */
        .search-bar {
            max-width: 400px;
            margin: 2rem 0;
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

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 2rem;
            opacity: 1; /* Ensure initial visibility */
        }

        .table {
            opacity: 1; /* Ensure initial visibility */
        }

        .table tbody tr {
            opacity: 1; /* Ensure initial visibility */
            display: table-row; /* Ensure display is not overridden */
        }

        .table thead th {
            color: #1a2a44;
            font-weight: 600;
            background: #f8f9fa;
        }

        .table tbody td {
            color: #666;
            vertical-align: middle;
        }

        /* Buttons */
        .btn-primary {
            background: #00aaff;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #0088cc;
            transform: scale(1.05);
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: scale(1.05);
        }

        .btn-chat {
            background: #27ae60;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-chat:hover {
            background: #219653;
            transform: scale(1.05);
        }

        .chat-icon {
            width: 20px;
            height: 20px;
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
            .navbar { padding: 0.5rem 1rem; }
            .navbar-brand { font-size: 1.5rem; }
            .table { font-size: 0.9rem; }
            .social-icons img { width: 24px; height: 24px; margin: 0 0.5rem; }
            .welcome-section { padding: 1.5rem; }
            .table-container { padding: 1rem; }
            .welcome-section h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
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
                        <a class="nav-link" href="../views/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">View Applications</a>
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
            <h2 class="display-5 mb-3" id="welcome-heading">Job Applications</h2>
            <p class="lead">Review applications for your job postings</p>
        </section>

        <section aria-labelledby="applications-heading">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 id="applications-heading">Application List</h3>
                <div class="search-bar">
                    <input type="search" class="form-control" 
                           placeholder="Search by name or job..." 
                           aria-label="Search applications">
                </div>
            </div>

            <div class="table-container">
                <?php if ($applications && $applications->num_rows > 0): ?>
                    <table class="table table-striped" role="grid">
                        <thead>
                            <tr>
                                <th scope="col">Applicant Name</th>
                                <th scope="col">Job Title</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($application = $applications->fetch_assoc()): ?>
                                <tr data-application-id="<?php echo $application['id']; ?>">
                                    <td><?php echo htmlspecialchars($application['applicant_name']); ?></td>
                                    <td><?php echo htmlspecialchars($application['job_title']); ?></td>
                                    <td>
                                        <a href="../uploads/resumes/<?php echo htmlspecialchars($application['resume']); ?>" 
                                           target="_blank" 
                                           class="btn btn-primary" 
                                           aria-label="View resume for <?php echo htmlspecialchars($application['applicant_name']); ?>">
                                            View Resume
                                        </a>
                                        <a href="../views/chat.php?with=<?php echo $application['applicant_id']; ?>" 
                                           class="btn btn-chat ms-2" 
                                           aria-label="Chat with <?php echo htmlspecialchars($application['applicant_name']); ?>">
                                            <img src="https://img.icons8.com/ios-filled/50/ffffff/chat.png" 
                                                 alt="Chat Icon" 
                                                 class="chat-icon">
                                            Chat
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info rounded-pill text-center" 
                         role="alert">
                        <?php echo $applications === false ? 'Error loading applications.' : 'No applications received yet.'; ?>
                        <?php if ($applications !== false): ?>
                            <a href="../jobs/post_job.php" class="alert-link">Post a job now!</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <a href="../views/dashboard.php" class="btn btn-secondary mt-3" 
               aria-label="Return to dashboard">Back to Dashboard</a>
        </section>
    </main>

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
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="../about.php">About Us</a></li>
                        <li><a href="../contact.php">Contact</a></li>
                        <li><a href="../privacy.php">Privacy Policy</a></li>
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
                <p class="mb-0">© <?php echo date('Y'); ?> Job Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" 
            crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" 
            integrity="sha512-16esztaSRplJROstbIIdwX3N97V1+pZvV33ABoG1H2OyTttBxEGkTsoIVsiP1iaTtM8b3+hu2kB6pQ4Clr5yug==" 
            crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Log the number of application rows for debugging
            const applicationRows = document.querySelectorAll('tbody tr');
            console.log('Number of application rows:', applicationRows.length);

            // Ensure application rows are initially visible
            applicationRows.forEach(row => {
                row.style.opacity = '1';
                row.style.display = 'table-row';
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
                ease: 'back.out(1.7)',
                delay: 0.2
            });

            gsap.from('.table-container', {
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out',
                delay: 0.4
            });

            // Animation for table rows (without IntersectionObserver)
            gsap.from('tbody tr', {
                duration: 0.8,
                opacity: 0,
                x: -50,
                stagger: 0.1,
                ease: 'power2.out',
                delay: 0.6,
                onStart: () => {
                    console.log('Table rows animation started');
                },
                onComplete: () => {
                    console.log('Table rows animation completed');
                }
            });

            // Search functionality
            const searchInput = document.querySelector('.search-bar input');
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                applicationRows.forEach(row => {
                    const applicantName = row.cells[0].textContent.toLowerCase();
                    const jobTitle = row.cells[1].textContent.toLowerCase();
                    const isVisible = applicantName.includes(searchTerm) || jobTitle.includes(searchTerm);
                    row.style.display = isVisible ? 'table-row' : 'none';
                    row.style.opacity = isVisible ? '1' : '0';
                });
            });

            // Footer animation
            gsap.from('footer', {
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out',
                delay: 0.8
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

            // Chat icon error handling
            document.querySelectorAll('.chat-icon').forEach(img => {
                img.onerror = () => {
                    console.warn(`Failed to load chat icon: ${img.src}`);
                    img.style.display = 'none';
                    const span = document.createElement('span');
                    span.textContent = '💬';
                    span.style.fontSize = '20px';
                    img.parentNode.insertBefore(span, img);
                };
            });
        });
    </script>
</body>
</html>