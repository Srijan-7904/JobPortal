<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/utils.php'; // Include the shared utility file

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Generate nonce for CSP
$nonce = base64_encode(random_bytes(16));

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

// Handle form submission
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid CSRF token.";
    } else {
        $title = filter_var($_POST['title'] ?? '', FILTER_SANITIZE_STRING);
        $description = filter_var($_POST['description'] ?? '', FILTER_SANITIZE_STRING);
        $salary = filter_var($_POST['salary'] ?? 0, FILTER_VALIDATE_FLOAT);

        if ($title && $description && $salary !== false && $salary > 0) {
            try {
                $stmt = $conn->prepare("INSERT INTO jobs (title, description, salary, employer_id, created_at) 
                                      VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssdi", $title, $description, $salary, $user_id);
                $stmt->execute();
                $stmt->close();
                
                unset($_SESSION['csrf_token']); // Regenerate token after successful submission
                header("Location: ../views/dashboard.php");
                exit();
            } catch (Exception $e) {
                error_log("Error posting job: " . $e->getMessage());
                $error_message = "An error occurred while posting the job. Please try again.";
            }
        } else {
            $error_message = "Please fill all fields with valid data. Salary must be a positive number.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Post a new job listing">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; 
        script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'nonce-<?php echo $nonce; ?>'; 
        style-src 'self' https://cdn.jsdelivr.net 'nonce-<?php echo $nonce; ?>'; 
        img-src 'self' data:;">
    <title>Post Job | RookieRise</title>
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

        .welcome-section {
            background: rgba(255,255,255,0.9);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 2rem;
        }

        .btn-primary {
            background: #3498db;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2980b9;
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
            font-size: 1.8rem;
            margin: 0 0.75rem;
            transition: all 0.3s ease;
        }

        .social-icons a:hover {
            transform: translateY(-5px);
            color: #fff;
        }

        :focus-visible {
            outline: 2px solid #ffd700;
            outline-offset: 2px;
        }

        @media (max-width: 768px) {
            .navbar { padding: 0.5rem 1rem; }
            .navbar-brand { font-size: 1.5rem; }
            .form-container { padding: 1.5rem; }
            .social-icons a { font-size: 1.5rem; margin: 0 0.5rem; }
        }
    </style>
</head>
<body>
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
                        <a class="nav-link" href="../applications/view_applications.php">View Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Post Job</a>
                    </li>
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
        <section class="welcome-section" aria-labelledby="welcome-heading">
            <h2 class="display-5 mb-3" id="welcome-heading">Post a New Job</h2>
            <p class="lead">Create a new job listing for job seekers</p>
        </section>

        <section class="form-container" aria-labelledby="post-job-heading">
            <h3 id="post-job-heading" class="mb-4">Job Details</h3>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger rounded-pill" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label for="title" class="form-label">Job Title</label>
                    <input type="text" name="title" id="title" class="form-control" 
                           required aria-describedby="titleHelp" 
                           placeholder="Enter job title">
                    <div id="titleHelp" class="form-text">Enter a concise job title</div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Job Description</label>
                    <textarea name="description" id="description" class="form-control" 
                              rows="4" required aria-describedby="descHelp" 
                              placeholder="Describe the job responsibilities and requirements"></textarea>
                    <div id="descHelp" class="form-text">Provide a detailed job description</div>
                </div>

                <div class="mb-3">
                    <label for="salary" class="form-label">Salary ($)</label>
                    <input type="number" name="salary" id="salary" class="form-control" 
                           required min="1" step="0.01" aria-describedby="salaryHelp" 
                           placeholder="Enter annual salary">
                    <div id="salaryHelp" class="form-text">Enter the annual salary in dollars</div>
                </div>

                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-primary" 
                            aria-label="Post job listing">Post Job</button>
                    <a href="../views/dashboard.php" class="btn btn-secondary" 
                       aria-label="Return to dashboard">Cancel</a>
                </div>
            </form>
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
                            <i class="fab fa-facebook-square"></i>
                        </a>
                        <a href="https://twitter.com" target="_blank" 
                           aria-label="Follow us on Twitter" title="Twitter">
                            <i class="fab fa-twitter-square"></i>
                        </a>
                        <a href="https://linkedin.com" target="_blank" 
                           aria-label="Connect with us on LinkedIn" title="LinkedIn">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="https://instagram.com" target="_blank" 
                           aria-label="Follow us on Instagram" title="Instagram">
                            <i class="fab fa-instagram-square"></i>
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
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script nonce="<?php echo $nonce; ?>">
        document.addEventListener('DOMContentLoaded', () => {
            // Animations
            gsap.from('.welcome-section', {
                duration: 1.2,
                opacity: 0,
                y: -100,
                ease: 'back.out(1.7)'
            });

            gsap.from('.form-container', {
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out',
                delay: 0.3
            });

            // Form validation feedback
            const form = document.querySelector('form');
            form.addEventListener('submit', (e) => {
                const inputs = form.querySelectorAll('input[required], textarea[required]');
                let isValid = true;

                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        input.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                    }
                });

                const salary = form.querySelector('#salary');
                if (salary.value <= 0) {
                    salary.classList.add('is-invalid');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });

            // Real-time input validation
            form.querySelectorAll('input, textarea').forEach(input => {
                input.addEventListener('input', () => {
                    if (input.value.trim()) {
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                    } else {
                        input.classList.remove('is-valid');
                        input.classList.add('is-invalid');
                    }
                });
            });
        });
    </script>
</body>
</html>