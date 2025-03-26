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
    <meta name="description" content="Job Portal - Connect with top employers and job seekers. Find your dream job or hire the best talent today!">
    <meta name="keywords" content="job portal, find jobs, hire talent, career opportunities">
    <meta name="author" content="Job Portal Team">
    <title>Job Portal - Find Your Dream Job</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" 
          crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Existing styles remain unchanged until the end */

        /* Chatbot Styling */
        .chatbot-container {
            position: fixed;
            bottom: 60px; /* Adjusted to accommodate toggle button */
            right: 20px;
            z-index: 1070; /* Higher than most elements */
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
            background: #00aaff;
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        #toggleChatbot:hover {
            background: #0088cc;
            transform: scale(1.05);
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

        /* Responsive Design for Chatbot */
        @media (max-width: 768px) {
            zapier-interfaces-chatbot-embed {
                width: 300px;
                height: 400px;
            }
        }

        /* Existing styles continue here */
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

        /* Hero Section with Parallax */
        .hero-section {
            position: relative;
            height: 100vh;
            background: url('https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1') no-repeat center center/cover;
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
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.4);
        }

        .hero-content p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
        }

        .btn-hero {
            background: #ffd700;
            color: #1a2a44;
            border: none;
            border-radius: 25px;
            padding: 1rem 2.5rem;
            font-size: 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block !important;
            opacity: 1 !important;
            z-index: 2;
        }

        .btn-hero:hover {
            background: #e6c200;
            transform: scale(1.05);
        }

        /* About Section */
        .about-section {
            padding: 6rem 0;
            background: #fff;
        }

        .about-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 1rem;
        }

        .about-section p {
            font-size: 1.1rem;
            color: #666;
            line-height: 1.8;
        }

        /* What to Do Section */
        .what-to-do-section {
            padding: 6rem 0;
            background: #f8f9fa;
        }

        .what-to-do-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 3rem;
        }

        .what-to-do-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .what-to-do-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .what-to-do-card img {
            width: 60px;
            height: 60px;
            margin-bottom: 1rem;
        }

        .what-to-do-card h5 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a2a44;
        }

        /* Features Section */
        .features-section {
            padding: 6rem 0;
            background: #fff;
        }

        .features-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 3rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .feature-card img {
            width: 50px;
            height: 50px;
            margin-bottom: 1rem;
        }

        .feature-card h5 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a2a44;
        }

        /* Explore Companies Section */
        .companies-section {
            padding: 6rem 0;
            background: #f8f9fa;
        }

        .companies-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 3rem;
        }

        .company-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .company-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .company-card img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
        }

        .company-card h5 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a2a44;
        }

        /* Job Categories Section */
        .categories-section {
            padding: 6rem 0;
            background: #fff;
        }

        .categories-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 3rem;
        }

        .category-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .category-card img {
            width: 50px;
            height: 50px;
            margin-bottom: 1rem;
        }

        .category-card h5 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a2a44;
        }

        /* Statistics Section */
        .stats-section {
            padding: 6rem 0;
            background: linear-gradient(135deg, #00aaff 0%, #1a2a44 100%);
            color: white;
            text-align: center;
        }

        .stats-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 3rem;
        }

        .stat-card {
            padding: 2rem;
        }

        .stat-card h3 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Testimonials Section */
        .testimonials-section {
            padding: 6rem 0;
            background: #f8f9fa;
        }

        .testimonials-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 3rem;
        }

        .testimonial-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .testimonial-card img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
        }

        .testimonial-card p {
            font-style: italic;
            color: #666;
        }

        .testimonial-card h6 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a2a44;
            margin-top: 1rem;
        }

        /* FAQ Section */
        .faq-section {
            padding: 6rem 0;
            background: #fff;
        }

        .faq-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 3rem;
        }

        .accordion-item {
            border: none;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 10px;
        }

        .accordion-button {
            background: transparent;
            color: #1a2a44;
            font-weight: 600;
        }

        .accordion-button:not(.collapsed) {
            background: transparent;
            color: #00aaff;
        }

        /* Newsletter Section */
        .newsletter-section {
            padding: 6rem 0;
            background: #f8f9fa;
            text-align: center;
        }

        .newsletter-section h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #1a2a44;
            margin-bottom: 1rem;
        }

        .newsletter-section p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
        }

        .newsletter-form {
            max-width: 500px;
            margin: 0 auto;
        }

        .newsletter-form .form-control {
            border-radius: 25px 0 0 25px;
            border: none;
            padding: 0.75rem 1.5rem;
        }

        .newsletter-form .btn {
            border-radius: 0 25px 25px 0;
            background: #00aaff;
            color: white;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .newsletter-form .btn:hover {
            background: #0088cc;
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
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .fallback-icon {
            width: 50px;
            height: 50px;
            background: #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-section {
                height: 80vh;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1.2rem;
            }

            .btn-hero {
                padding: 0.75rem 2rem;
                font-size: 1.2rem;
            }

            .about-section, .what-to-do-section, .features-section, .companies-section, 
            .categories-section, .stats-section, .testimonials-section, .faq-section, 
            .newsletter-section, .cta-section {
                padding: 3rem 0;
            }

            .what-to-do-card, .feature-card, .company-card, .category-card, 
            .testimonial-card, .stat-card {
                margin-bottom: 2rem;
            }

            .stat-card h3 {
                font-size: 2rem;
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
                        <a class="nav-link active" aria-current="page" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
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
            <h1>Discover Your Dream Career</h1>
            <p>Join Job Portal to connect with top employers and find opportunities that match your skills.</p>
            <a href="<?php echo isset($_SESSION['user_id']) ? 'views/dashboard.php' : 'auth/register.php'; ?>" 
               class="btn btn-hero">Get Started</a>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2>About Job Portal</h2>
                    <p>
                        Job Portal is a cutting-edge platform designed to simplify the job search and hiring process. 
                        Whether you're a job seeker looking for your next big opportunity or an employer searching for 
                        top talent, we provide the tools you need to succeed.
                    </p>
                    <p>
                        With features like advanced job search, resume building, direct messaging, and interview scheduling, 
                        we make it easy to connect and grow. Join our community today and take the next step in your career journey!
                    </p>
                </div>
                <div class="col-md-6">
                    <img src="https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" 
                         alt="Team working together" class="img-fluid rounded" 
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/600x400?text=Team+Image';">
                </div>
            </div>
        </div>
    </section>

    <!-- What to Do Section -->
    <section class="what-to-do-section">
        <div class="container">
            <h2 class="text-center">What You Can Do</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="what-to-do-card">
                        <img src="https://cdn-icons-png.flaticon.com/512/954/954549.png" alt="Search Icon" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-icon\'>S</div>';">
                        <h5>Search for Jobs</h5>
                        <p>Explore thousands of job listings tailored to your skills and preferences.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="what-to-do-card">
                        <img src="https://cdn-icons-png.flaticon.com/512/4213/4213950.png" alt="Upload Icon" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-icon\'>U</div>';">
                        <h5>Post a Job</h5>
                        <p>Employers can post job openings and reach top talent effortlessly.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="what-to-do-card">
                        <img src="https://cdn-icons-png.flaticon.com/512/893/893078.png" alt="Chat Icon" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-icon\'>C</div>';">
                        <h5>Chat with Users</h5>
                        <p>Communicate directly with employers or job seekers to discuss opportunities.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="text-center">Our Features</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="feature-card">
                        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135768.png" alt="Resume Icon" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-icon\'>R</div>';">
                        <h5>Resume Builder</h5>
                        <p>Create a professional resume with our easy-to-use builder.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <img src="https://cdn-icons-png.flaticon.com/512/2572/2572495.png" alt="Notification Icon" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-icon\'>N</div>';">
                        <h5>Job Alerts</h5>
                        <p>Get notified about new jobs that match your criteria.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <img src="https://cdn-icons-png.flaticon.com/512/2695/2695971.png" alt="Calendar Icon" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-icon\'>C</div>';">
                        <h5>Interview Scheduling</h5>
                        <p>Schedule interviews directly through the platform.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <img src="https://cdn-icons-png.flaticon.com/512/2195/2195915.png" alt="Feedback Icon" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-icon\'>F</div>';">
                        <h5>Employer Reviews</h5>
                        <p>Read and write reviews to make informed decisions.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Explore Companies Section -->
    <section class="companies-section">
        <div class="container">
            <h2 class="text-center">Explore Top Companies</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="company-card">
                        <img src="https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" 
                             alt="Tech Innovate" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-image\'>TI</div>';">
                        <h5>Tech Innovate</h5>
                        <p>Leading tech company hiring software engineers.</p>
                        <a href="company_profile.php?id=1" class="btn btn-primary">View Jobs</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="company-card">
                        <img src="https://images.pexels.com/photos/3184292/pexels-photo-3184292.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" 
                             alt="Creative Solutions" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-image\'>CS</div>';">
                        <h5>Creative Solutions</h5>
                        <p>Marketing firm looking for creative talent.</p>
                        <a href="company_profile.php?id=2" class="btn btn-primary">View Jobs</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="company-card">
                        <img src="https://images.pexels.com/photos/3184293/pexels-photo-3184293.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" 
                             alt="Data Dynamics" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-image\'>DD</div>';">
                        <h5>Data Dynamics</h5>
                        <p>Data analytics company seeking analysts.</p>
                        <a href="company_profile.php?id=3" class="btn btn-primary">View Jobs</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="company-card">
                        <img src="https://images.pexels.com/photos/3184294/pexels-photo-3184294.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" 
                             alt="HealthTech" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-image\'>HT</div>';">
                        <h5>HealthTech</h5>
                        <p>Healthcare startup hiring medical professionals.</p>
                        <a href="company_profile.php?id=4" class="btn btn-primary">View Jobs</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Job Categories Section -->
    <section class="categories-section">
        <div class="container">
            <h2 class="text-center">Popular Job Categories</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="category-card">
                        <img src="https://cdn-icons-png.flaticon.com/512/2920/2920348.png" alt="Tech Icon" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-icon\'>T</div>';">
                        <h5>Technology</h5>
                        <p>Software development, IT, and more.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="category-card">
                        <img src="https://cdn-icons-png.flaticon.com/512/3163/3163478.png" alt="Marketing Icon" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-icon\'>M</div>';">
                        <h5>Marketing</h5>
                        <p>Digital marketing, SEO, and branding.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="category-card">
                        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135768.png" alt="Finance Icon" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-icon\'>F</div>';">
                        <h5>Finance</h5>
                        <p>Accounting, banking, and financial analysis.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="category-card">
                        <img src="https://cdn-icons-png.flaticon.com/512/2965/2965279.png" alt="Healthcare Icon" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-icon\'>H</div>';">
                        <h5>Healthcare</h5>
                        <p>Medical professionals and support staff.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <h2 class="text-center">Our Impact</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card">
                        <h3 class="stat-counter" data-target="5000">0</h3>
                        <p>Jobs Posted</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <h3 class="stat-counter" data-target="10000">0</h3>
                        <p>Active Users</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <h3 class="stat-counter" data-target="3000">0</h3>
                        <p>Successful Hires</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="container">
            <h2 class="text-center">What Our Users Say</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="https://images.pexels.com/photos/415829/pexels-photo-415829.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" 
                             alt="Emily Johnson" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-image\'>EJ</div>';">
                        <p>"I found my dream job within a week of joining Job Portal. The platform is so easy to use!"</p>
                        <h6>Emily Johnson, Software Engineer</h6>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="https://images.pexels.com/photos/2379004/pexels-photo-2379004.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" 
                             alt="Michael Brown" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-image\'>MB</div>';">
                        <p>"As an employer, I was able to hire top talent quickly. The chat feature is a game-changer!"</p>
                        <h6>Michael Brown, HR Manager</h6>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <img src="https://images.pexels.com/photos/1239291/pexels-photo-1239291.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" 
                             alt="Sarah Davis" 
                             onerror="this.onerror=null; this.parentNode.innerHTML='<div class=\'fallback-image\'>SD</div>';">
                        <p>"The resume builder helped me create a professional CV that got me noticed by employers."</p>
                        <h6>Sarah Davis, Marketing Specialist</h6>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <h2 class="text-center">Frequently Asked Questions</h2>
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading1">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
                            How do I create an account?
                        </button>
                    </h2>
                    <div id="faqCollapse1" class="accordion-collapse collapse show" 
                         aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Click on the "Sign Up" button in the navbar, fill out the registration form, and submit. You'll receive a confirmation email to activate your account.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                            How can I post a job?
                        </button>
                    </h2>
                    <div id="faqCollapse2" class="accordion-collapse collapse" 
                         aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            After logging in as an employer, go to your dashboard and click on "Post a Job." Fill out the job details and submit to publish the listing.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                            Is Job Portal free to use?
                        </button>
                    </h2>
                    <div id="faqCollapse3" class="accordion-collapse collapse" 
                         aria-labelledby="faqHeading3" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes, Job Portal is free for job seekers. Employers can post jobs for free, but premium features like advanced analytics may require a subscription.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter-section">
        <div class="container">
            <h2>Stay Updated</h2>
            <p>Subscribe to our newsletter for the latest job opportunities and updates.</p>
            <form class="newsletter-form d-flex">
                <input type="email" class="form-control" placeholder="Enter your email" required>
                <button type="submit" class="btn">Subscribe</button>
            </form>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Join Job Portal Today!</h2>
            <p>Whether you're a job seeker or an employer, we have the tools to help you succeed.</p>
            <a href="<?php echo isset($_SESSION['user_id']) ? 'views/dashboard.php' : 'auth/register.php'; ?>" 
               class="btn btn-cta">Get Started Now</a>
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
    <button id="toggleChatbot" aria-label="Toggle Chatbot">💬</button>

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
            // Chatbot Toggle Functionality
            const toggleChatbotBtn = document.getElementById('toggleChatbot');
            const chatbotEmbed = document.querySelector('zapier-interfaces-chatbot-embed');
            
            toggleChatbotBtn.addEventListener('click', () => {
                const isVisible = chatbotEmbed.style.display === 'block';
                chatbotEmbed.style.display = isVisible ? 'none' : 'block';
                toggleChatbotBtn.textContent = isVisible ? '💬' : '✖'; // Change icon based on state
                toggleChatbotBtn.setAttribute('aria-label', isVisible ? 'Show Chatbot' : 'Hide Chatbot');
            });

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

            gsap.from('.hero-content p', {
                duration: 1.5,
                opacity: 0,
                y: 100,
                ease: 'back.out(1.7)',
                delay: 0.7
            });

            gsap.from('.btn-hero', {
                duration: 1.5,
                opacity: 0,
                scale: 0.8,
                ease: 'back.out(1.7)',
                delay: 0.9,
                onStart: () => {
                    document.querySelector('.btn-hero').style.display = 'inline-block';
                },
                onComplete: () => {
                    document.querySelector('.btn-hero').style.opacity = '1';
                    document.querySelector('.btn-hero').style.display = 'inline-block';
                }
            });

            // About Section Animation
            gsap.from('.about-section h2', {
                scrollTrigger: {
                    trigger: '.about-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.about-section p', {
                scrollTrigger: {
                    trigger: '.about-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out',
                delay: 0.2
            });

            gsap.from('.about-section img', {
                scrollTrigger: {
                    trigger: '.about-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                x: 50,
                ease: 'power2.out',
                delay: 0.4
            });

            // What to Do Section Animation
            gsap.from('.what-to-do-section h2', {
                scrollTrigger: {
                    trigger: '.what-to-do-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.what-to-do-card', {
                scrollTrigger: {
                    trigger: '.what-to-do-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                stagger: 0.2,
                ease: 'power2.out',
                delay: 0.2
            });

            // Features Section Animation
            gsap.from('.features-section h2', {
                scrollTrigger: {
                    trigger: '.features-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.feature-card', {
                scrollTrigger: {
                    trigger: '.features-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                stagger: 0.2,
                ease: 'power2.out',
                delay: 0.2
            });

            // Explore Companies Section Animation
            gsap.from('.companies-section h2', {
                scrollTrigger: {
                    trigger: '.companies-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.company-card', {
                scrollTrigger: {
                    trigger: '.companies-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                stagger: 0.2,
                ease: 'power2.out',
                delay: 0.2
            });

            // Job Categories Section Animation
            gsap.from('.categories-section h2', {
                scrollTrigger: {
                    trigger: '.categories-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.category-card', {
                scrollTrigger: {
                    trigger: '.categories-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                stagger: 0.2,
                ease: 'power2.out',
                delay: 0.2
            });

            // Statistics Section Animation
            gsap.from('.stats-section h2', {
                scrollTrigger: {
                    trigger: '.stats-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.stat-card', {
                scrollTrigger: {
                    trigger: '.stats-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                stagger: 0.2,
                ease: 'power2.out',
                delay: 0.2
            });

            // Animate Counters
            document.querySelectorAll('.stat-counter').forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target'));
                gsap.to(counter, {
                    scrollTrigger: {
                        trigger: '.stats-section',
                        start: 'top 80%'
                    },
                    innerHTML: target,
                    duration: 2,
                    ease: 'power1.out',
                    snap: { innerHTML: 1 },
                    onUpdate: function() {
                        counter.innerHTML = Math.ceil(counter.innerHTML).toLocaleString();
                    }
                });
            });

            // Testimonials Section Animation
            gsap.from('.testimonials-section h2', {
                scrollTrigger: {
                    trigger: '.testimonials-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.testimonial-card', {
                scrollTrigger: {
                    trigger: '.testimonials-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                stagger: 0.2,
                ease: 'power2.out',
                delay: 0.2
            });

            // FAQ Section Animation
            gsap.from('.faq-section h2', {
                scrollTrigger: {
                    trigger: '.faq-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.accordion-item', {
                scrollTrigger: {
                    trigger: '.faq-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                stagger: 0.2,
                ease: 'power2.out',
                delay: 0.2
            });

            // Newsletter Section Animation
            gsap.from('.newsletter-section h2', {
                scrollTrigger: {
                    trigger: '.newsletter-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out'
            });

            gsap.from('.newsletter-section p', {
                scrollTrigger: {
                    trigger: '.newsletter-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out',
                delay: 0.2
            });

            gsap.from('.newsletter-form', {
                scrollTrigger: {
                    trigger: '.newsletter-section',
                    start: 'top 80%'
                },
                duration: 1,
                opacity: 0,
                scale: 0.5,
                ease: 'back.out(1.7)',
                delay: 0.4
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

            // Chatbot Animation
            gsap.from('#toggleChatbot', {
                duration: 1,
                opacity: 1,
                scale: 1.2,
                ease: 'back.out(1.7)',
                delay: 1
            });

            // Hover Animations for Cards
            document.querySelectorAll('.what-to-do-card, .feature-card, .company-card, .category-card, .testimonial-card').forEach(card => {
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