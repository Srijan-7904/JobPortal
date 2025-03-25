<?php
session_start();

// Fetch user name if logged in
$user_name = '';
if (isset($_SESSION['user_id'])) {
    require __DIR__ . '/includes/db.php'; // Adjust path as needed
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $full_name = $result->fetch_assoc()['name'];
        // Split the name and take only the first name
        $name_parts = explode(' ', $full_name);
        $user_name = $name_parts[0]; // First name only
    }
    $stmt->close();
}

// Handle form submission
$success_message = '';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Here you can add code to save the message to the database or send an email
        // For now, we'll just display a success message
        $success_message = 'Thank you for your message! We will get back to you soon.';
        // Reset form fields
        $name = $email = $subject = $message = '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Contact Job Portal - Get in touch with us for any inquiries or support.">
    <meta name="keywords" content="job portal, contact us, support, inquiries">
    <meta name="author" content="Job Portal Team">
    <title>Contact Job Portal - Get in Touch</title>
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

        .user-name {
            color: #1a2a44;
            font-weight: 500;
            padding: 0.5rem 1rem;
            margin: 0;
            display: inline-flex;
            align-items: center;
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            height: 60vh;
            background: url('https://images.pexels.com/photos/3184295/pexels-photo-3184295.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1') no-repeat center center/cover;
            background-attachment: fixed;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 0 2rem;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 170, 255, 0.7), rgba(26, 42, 68, 0.7));
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.4);
        }

        /* Contact Section */
        .contact-section {
            padding: 6rem 0;
            background: #fff;
        }

        .contact-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 1rem;
        }

        .contact-form {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .contact-form .form-control {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 0.75rem;
            margin-bottom: 1rem;
        }

        .contact-form .form-control:focus {
            border-color: #00aaff;
            box-shadow: 0 0 5px rgba(0, 170, 255, 0.3);
        }

        .contact-form textarea {
            resize: none;
        }

        .btn-submit {
            background: #00aaff;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: #0088cc;
            transform: scale(1.05);
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        /* Contact Info Section */
        .contact-info-section {
            padding: 6rem 0;
            background: #f8f9fa;
        }

        .contact-info-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 3rem;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .info-card i {
            font-size: 2rem;
            color: #00aaff;
            margin-bottom: 1rem;
        }

        .info-card h5 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a2a44;
        }

        .info-card p {
            font-size: 1rem;
            color: #666;
        }

        /* Map Section */
        .map-section {
            padding: 6rem 0;
            background: #fff;
        }

        .map-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 2rem;
        }

        .map-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .map-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
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
            .hero-section {
                height: 50vh;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .contact-section, .contact-info-section, .map-section {
                padding: 3rem 0;
            }

            .info-card {
                margin-bottom: 2rem;
            }

            .map-container {
                padding-bottom: 75%; /* Adjust aspect ratio for mobile */
            }

            .user-name {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php" aria-label="Job Portal Home">Job Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                data-bs-target="#navbarNav" aria-controls="navbarNav" 
                aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="contact.php">Contact</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="views/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <span class="user-name">Welcome, <?php echo htmlspecialchars($user_name); ?>!</span>
                        </li>
                        <li class="nav-item">
                            <a href="auth/logout.php" class="btn btn-nav-primary" aria-label="Logout">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a href="auth/login.php" class="btn btn-nav-primary me-2" aria-label="Login">Login</a>
                        </li>
                        <li class="nav-item">
                            <a href="auth/register.php" class="btn btn-nav-primary" aria-label="Sign Up">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>Contact Us</h1>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-section">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mx-auto">
                    <h2 class="text-center">Get in Touch</h2>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    <form class="contact-form" method="POST" action="contact.php">
                        <div class="mb-3">
                            <input type="text" class="form-control" name="name" placeholder="Your Name" 
                                   value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="Your Email" 
                                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" name="subject" placeholder="Subject" 
                                   value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" name="message" rows="5" placeholder="Your Message" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-submit">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Info Section -->
    <section class="contact-info-section">
        <div class="container">
            <h2 class="text-center">Contact Information</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="info-card">
                        <i class="bi bi-geo-alt-fill"></i>
                        <h5>Address</h5>
                        <p>123 Job Street, Kansas City, MO 64105, USA</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <i class="bi bi-telephone-fill"></i>
                        <h5>Phone</h5>
                        <p>+1 (816) 123-4567</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <i class="bi bi-envelope-fill"></i>
                        <h5>Email</h5>
                        <p>support@jobportal.com</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <h2 class="text-center">Our Location</h2>
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3097.021766057614!2d-94.58467768463547!3d39.09972617953995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x87c0f75e8b7e5b9d%3A0x3e6c5e5d5e5d5e5d!2sKansas%20City%2C%20MO%2064105%2C%20USA!5e0!3m2!1sen!2sus!4v1697041234567!5m2!1sen!2sus" 
                        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </section>

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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
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

            // Navbar Animation
            gsap.from('.navbar', {
                duration: 1,
                opacity: 0,
                y: -50,
                ease: 'power2.out'
            });

            // Hero Section Animation
            gsap.from('.hero-content h1', {
                duration: 1.5,
                opacity: 0,
                y: 100,
                ease: 'back.out(1.7)',
                delay: 0.5
            });

            // Contact Form Section Animation
            gsap.from('.contact-section h2', {
                scrollTrigger: {
                    trigger: '.contact-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.contact-form', {
                scrollTrigger: {
                    trigger: '.contact-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                scale: 0.5,
                ease: 'back.out(1.7)',
                delay: 0.2
            });

            // Contact Info Section Animation
            gsap.from('.contact-info-section h2', {
                scrollTrigger: {
                    trigger: '.contact-info-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.info-card', {
                scrollTrigger: {
                    trigger: '.contact-info-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                stagger: 0.2,
                ease: 'power2.out',
                delay: 0.2
            });

            // Map Section Animation
            gsap.from('.map-section h2', {
                scrollTrigger: {
                    trigger: '.map-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.map-container', {
                scrollTrigger: {
                    trigger: '.map-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out',
                delay: 0.2
            });

            // Footer Animation
            gsap.from('footer', {
                scrollTrigger: {
                    trigger: 'footer',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            // Hover Animations for Info Cards
            document.querySelectorAll('.info-card').forEach(card => {
                card.addEventListener('mouseenter', () => {
                    gsap.to(card, {
                        scale: 1.05,
                        duration: 0.3,
                        ease: 'power2.out'
                    });
                });
                card.addEventListener('mouseleave', () => {
                    gsap.to(card, {
                        scale: 1,
                        duration: 0.3,
                        ease: 'power2.out'
                    });
                });
            });
        });
    </script>
</body>
</html> 