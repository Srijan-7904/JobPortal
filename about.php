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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Learn more about RookieRise - Our mission, vision, and team dedicated to connecting talent with opportunities.">
    <meta name="keywords" content="RookieRise, about us, mission, vision, team">
    <meta name="author" content="RookieRise Team">
    <title>About RookieRise - Connecting Talent with Opportunities</title>
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
            background: url('https://images.pexels.com/photos/3184292/pexels-photo-3184292.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1') no-repeat center center/cover;
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

        /* Mission Section */
        .mission-section {
            padding: 6rem 0;
            background: #fff;
        }

        .mission-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 1rem;
        }

        .mission-section p {
            font-size: 1.1rem;
            color: #666;
            line-height: 1.8;
        }

        /* Vision Section */
        .vision-section {
            padding: 6rem 0;
            background: #f8f9fa;
        }

        .vision-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 1rem;
        }

        .vision-section p {
            font-size: 1.1rem;
            color: #666;
            line-height: 1.8;
        }

        /* Team Section */
        .team-section {
            padding: 6rem 0;
            background: #fff;
        }

        .team-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 3rem;
        }

        .team-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .team-card img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 1rem;
        }

        .team-card h5 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a2a44;
        }

        .team-card p {
            font-size: 1rem;
            color: #666;
        }

        /* CTA Section */
        .cta-section {
            padding: 6rem 0;
            background: linear-gradient(135deg, #00aaff 0%, #1a2a44 100%);
            color: white;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        .btn-cta {
            background: #ffd700;
            color: #1a2a44;
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .btn-cta:hover {
            background: #e6c200;
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

        /* Fallback Image Styling */
        .fallback-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin: 0 auto 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-section {
                height: 50vh;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .mission-section, .vision-section, .team-section, .cta-section {
                padding: 3rem 0;
            }

            .team-card {
                margin-bottom: 2rem;
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
            <a class="navbar-brand" href="index.php" aria-label="RookieRise Home">RookieRise</a>
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
                        <a class="nav-link active" aria-current="page" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
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
            <h1>About RookieRise</h1>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="mission-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2>Our Mission</h2>
                    <p>
                        At RookieRise, our mission is to bridge the gap between talent and opportunity. We strive to create a platform where job seekers can find their dream careers and employers can discover the best talent to grow their organizations.
                    </p>
                    <p>
                        We are committed to providing a seamless, user-friendly experience with tools that empower both job seekers and employers to achieve their goals.
                    </p>
                </div>
                <div class="col-md-6">
                    <img src="https://images.pexels.com/photos/3184293/pexels-photo-3184293.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" 
                         alt="Our Mission" class="img-fluid rounded" 
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/600x400?text=Mission+Image';">
                </div>
            </div>
        </div>
    </section>

    <!-- Vision Section -->
    <section class="vision-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 order-md-2">
                    <h2>Our Vision</h2>
                    <p>
                        We envision a world where every individual has access to meaningful work that aligns with their skills and passions. RookieRise aims to be the leading global platform for career growth and talent acquisition.
                    </p>
                    <p>
                        By leveraging technology and innovation, we aspire to transform the job market, making it more inclusive, efficient, and rewarding for everyone involved.
                    </p>
                </div>
                <div class="col-md-6 order-md-1">
                    <img src="https://images.pexels.com/photos/3184294/pexels-photo-3184294.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" 
                         alt="Our Vision" class="img-fluid rounded" 
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/600x400?text=Vision+Image';">
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <h2 class="text-center">Meet Our Team</h2>
            <div class="row">
                <div class="col-md-4">

                    <div class="team-card">
                        <a href="https://ibb.co/n81krYRH"><img src="https://i.ibb.co/HpPg7bXW/473396657-9067858626612551-8103250684495701786-n.jpg" alt="473396657-9067858626612551-8103250684495701786-n" border="0"></a>
                        <h5>Kriti Rai</h5>
                        <p>Software Developer</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-card">
                    <a href="https://ibb.co/nND7YQWN"><img src="https://i.ibb.co/rRZxz5WR/473395305-401278983030328-2843355241182261745-n.jpg" alt="473395305-401278983030328-2843355241182261745-n" border="0"></a>
                        <h5>Abhay Tomar</h5>
                        <p>Hardware and Robotics Expert</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-card">
                    <a href="https://ibb.co/HTYYXLbM"><img src="https://i.ibb.co/QvnnX7Tq/dc952d1b-a9ff-40ba-a992-1d65ed2043b9.jpg" alt="dc952d1b-a9ff-40ba-a992-1d65ed2043b9" border="0"></a>
                        <h5>Srijan Jaiswal</h5>
                        <p>Web Developer</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Join RookieRise Today!</h2>
            <p>Ready to take the next step in your career or find the perfect candidate? Join our community now!</p>
            <a href="<?php echo isset($_SESSION['user_id']) ? 'views/dashboard.php' : 'auth/register.php'; ?>" 
               class="btn btn-cta">Get Started</a>
        </div>
    </section>

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

            // Mission Section Animation
            gsap.from('.mission-section h2', {
                scrollTrigger: {
                    trigger: '.mission-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.mission-section p', {
                scrollTrigger: {
                    trigger: '.mission-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out',
                delay: 0.2
            });

            gsap.from('.mission-section img', {
                scrollTrigger: {
                    trigger: '.mission-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                x: 50,
                ease: 'power2.out',
                delay: 0.4
            });

            // Vision Section Animation
            gsap.from('.vision-section h2', {
                scrollTrigger: {
                    trigger: '.vision-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.vision-section p', {
                scrollTrigger: {
                    trigger: '.vision-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out',
                delay: 0.2
            });

            gsap.from('.vision-section img', {
                scrollTrigger: {
                    trigger: '.vision-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                x: -50,
                ease: 'power2.out',
                delay: 0.4
            });

            // Team Section Animation
            gsap.from('.team-section h2', {
                scrollTrigger: {
                    trigger: '.team-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.team-card', {
                scrollTrigger: {
                    trigger: '.team-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                stagger: 0.2,
                ease: 'power2.out',
                delay: 0.2
            });

            // CTA Section Animation
            gsap.from('.cta-section h2', {
                scrollTrigger: {
                    trigger: '.cta-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.cta-section p', {
                scrollTrigger: {
                    trigger: '.cta-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out',
                delay: 0.2
            });

            gsap.from('.btn-cta', {
                scrollTrigger: {
                    trigger: '.cta-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                scale: 0.5,
                ease: 'back.out(1.7)',
                delay: 0.4
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

            // Hover Animations for Team Cards
            document.querySelectorAll('.team-card').forEach(card => {
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