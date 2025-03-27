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

// Get hackathon ID from query parameter
$hackathon_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($hackathon_id <= 0) {
    header("Location: hackathons.php");
    exit();
}

// Fetch hackathon details
if ($user_role === 'employer') {
    $stmt = $conn->prepare("SELECT * FROM hackathons WHERE id = ? AND employer_id = ?");
    $stmt->bind_param("ii", $hackathon_id, $user_id);
} else {
    $stmt = $conn->prepare("
        SELECT h.*, u.name AS organizer_name 
        FROM hackathons h 
        JOIN users u ON h.employer_id = u.id 
        WHERE h.id = ?
    ");
    $stmt->bind_param("i", $hackathon_id);
}
$stmt->execute();
$hackathon = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$hackathon) {
    header("Location: hackathons.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Hackathon Details - View details of the hackathon">
    <meta name="keywords" content="RookieRise, hackathon, details">
    <meta name="author" content="RookieRise Team">
    <title>Hackathon Details | RookieRise</title>
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

        /* Hackathon Details Section */
        .hackathon-details-section {
            margin: 2rem 0;
            padding: 2rem;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .hackathon-details-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 1.5rem;
        }

        .hackathon-details p {
            margin-bottom: 1rem;
            line-height: 1.6;
            color: #666;
        }

        .hackathon-details strong {
            color: #1a2a44;
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
            .hackathon-details-section {
                padding: 1rem;
            }

            .hackathon-details-section h2 {
                font-size: 2rem;
            }

            .navbar {
                padding: 0.5rem 1rem;
            }

            .navbar-brand {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand" href="../views/dashboard.php" aria-label="RookieRise Home">RookieRise</a>
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
                        <a class="nav-link active" aria-current="page" href="hackathons.php" 
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
                            <a class="nav-link" href="../views/resume_builder.php" 
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
        <!-- Hackathon Details Section -->
        <section class="hackathon-details-section" aria-labelledby="hackathon-details-heading">
            <h2 id="hackathon-details-heading"><?php echo htmlspecialchars($hackathon['title']); ?></h2>
            <div class="hackathon-details">
                <p><strong>Description:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($hackathon['description'])); ?></p>
                <p><strong>Date:</strong> <?php echo date('M d, Y, H:i A', strtotime($hackathon['date'])); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($hackathon['location']); ?></p>
                <p><strong>Organizer:</strong> <?php echo htmlspecialchars($user_role === 'employer' ? $hackathon['organizer'] : $hackathon['organizer_name']); ?></p>
                <?php if ($hackathon['is_active']): ?>
                    <p><strong>Registration Deadline:</strong> <?php echo date('M d, Y, H:i A', strtotime($hackathon['registration_deadline'])); ?></p>
                <?php endif; ?>
            </div>
            <div class="mt-3">
                <a href="hackathons.php" class="btn btn-primary" aria-label="Back to Hackathons">Back to Hackathons</a>
            </div>
        </section>
    </main>

    <!-- Footer -->
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
                <p class="mb-0">© <?php echo date('Y'); ?> RookieRise. All rights reserved.</p>
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
        // GSAP Animations
        gsap.from('.navbar', {
            duration: 1,
            opacity: 0,
            y: -50,
            ease: 'power2.out'
        });

        gsap.from('.hackathon-details-section', {
            duration: 1.5,
            opacity: 0,
            y: 100,
            ease: 'back.out(1.7)'
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
    </script>
</body>
</html>