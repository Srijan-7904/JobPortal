<?php
session_start();

// Redirect if not logged in or not a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'jobseeker') {
    header("Location: ../auth/login.php");
    exit();
}

$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Guest', ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Search and apply for job listings on Job Portal">
    <title>Job Listings | Job Portal</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" 
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" 
            crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" 
          crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f0f4f8;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        main { 
            flex: 1 0 auto; 
            padding: 20px 0;
        }

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

        /* Filter Container */
        .filter-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin: 2rem auto;
            max-width: 1200px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .filter-container label {
            font-size: 0.9rem;
            color: #1a2a44;
            margin-bottom: 0.5rem;
            display: block;
        }

        input, select, button {
            border: none;
            border-radius: 10px;
            padding: 10px 15px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            font-size: 14px;
            width: 100%;
        }

        input:focus, select:focus {
            outline: none;
            box-shadow: 0 2px 8px rgba(0, 170, 255, 0.3);
            transform: scale(1.02);
        }

        button {
            background: #00aaff;
            color: white;
            font-weight: 500;
            cursor: pointer;
        }

        button:hover {
            background: #0088cc;
            transform: scale(1.05);
        }

        /* Job List */
        .job-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            opacity: 1; /* Ensure initial visibility */
        }

        .job-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            opacity: 1; /* Ensure initial visibility */
            display: block; /* Ensure display is not overridden */
        }

        .job-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
            border-left: 4px solid #00aaff;
        }

        .job-card.new-job::before {
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

        .job-title {
            color: #1a2a44;
            font-weight: 700;
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
        }

        .job-details {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .job-details p {
            margin-bottom: 0.5rem;
        }

        .apply-btn {
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .apply-btn:hover {
            background: #219653;
            transform: scale(1.05);
        }

        .apply-btn:disabled {
            background: #6c757d;
            transform: none;
            cursor: not-allowed;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            color: white;
            display: inline-block;
            margin-left: 10px;
        }

        .no-results {
            text-align: center;
            color: #e74c3c;
            padding: 2rem;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin: 2rem auto;
            max-width: 1200px;
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
            .filter-container {
                grid-template-columns: 1fr;
                padding: 1.5rem;
            }
            .job-list {
                grid-template-columns: 1fr;
            }
            .navbar { padding: 0.5rem 1rem; }
            .navbar-brand { font-size: 1.5rem; }
            .social-icons img { width: 24px; height: 24px; margin: 0 0.5rem; }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
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
                        <a class="nav-link" href="../applications/compare_jobs.php">Compare Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../applications/saved_jobs.php">Saved Jobs</a>
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

    <!-- Main Content -->
    <main>
        <div class="filter-container" role="form" aria-label="Job search filters">
            <div>
                <label for="jobRole">Job Role</label>
                <input type="text" id="jobRole" placeholder="Search by job role..." 
                       aria-label="Search by job role">
            </div>
            <div>
                <label for="locationFilter">Location</label>
                <select id="locationFilter" aria-label="Filter by location">
                    <option value="">All Locations</option>
                </select>
            </div>
            <div>
                <label for="typeFilter">Job Type</label>
                <select id="typeFilter" aria-label="Filter by job type">
                    <option value="">All Job Types</option>
                    <option value="Full-Time">Full-Time</option>
                    <option value="Part-Time">Part-Time</option>
                    <option value="Internship">Internship</option>
                    <option value="Contract">Contract</option>
                </select>
            </div>
            <div>
                <label for="experienceFilter">Experience Level</label>
                <select id="experienceFilter" aria-label="Filter by experience level">
                    <option value="">All Experience Levels</option>
                    <option value="0+ Years">Entry Level (0+ Years)</option>
                    <option value="1+ Years">1+ Years</option>
                    <option value="3+ Years">3+ Years</option>
                    <option value="5+ Years">5+ Years</option>
                </select>
            </div>
            <div>
                <label for="minSalary">Min Salary ($)</label>
                <input type="number" id="minSalary" placeholder="Min Salary ($)" min="0" 
                       aria-label="Minimum salary filter">
            </div>
            <div>
                <label for="maxSalary">Max Salary ($)</label>
                <input type="number" id="maxSalary" placeholder="Max Salary ($)" min="0" 
                       aria-label="Maximum salary filter">
            </div>
            <div>
                <button onclick="filterJobs()" aria-label="Search jobs">Search Jobs</button>
            </div>
        </div>

        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
            <p>Loading jobs...</p>
        </div>

        <div class="job-list" id="jobResults" role="region" aria-live="polite"></div>
        <div id="noResults" class="no-results" style="display: none;" 
             role="alert" aria-live="assertive">
            No jobs match your search criteria
        </div>
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
        let jobs = [];
        let appliedJobs = new Set();

        $(document).ready(function() {
            fetchJobs();
            $("#jobRole").on("input", debounce(filterJobs, 300));
            $("#locationFilter, #typeFilter, #experienceFilter, #minSalary, #maxSalary").on("change", filterJobs);
            animateElements();
        });

        function fetchJobs() {
            $("#loadingSpinner").show();
            $("#jobResults").hide();
            $("#noResults").hide();

            $.getJSON("job_list.php", function(data) {
                jobs = data.map(job => ({
                    ...job,
                    postedDate: new Date(job.postedDate || Date.now())
                }));
                console.log('Number of jobs fetched:', jobs.length);
                populateFilters();
                displayJobs(jobs);
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error('Error fetching jobs:', textStatus, errorThrown);
                $("#noResults").text("Error loading jobs. Please try again.").show();
            }).always(function() {
                $("#loadingSpinner").hide();
                $("#jobResults").show();
            });
        }

        function populateFilters() {
            const locations = [...new Set(jobs.map(job => job.location))].sort();
            locations.forEach(location => {
                $("#locationFilter").append(`<option value="${location}">${location}</option>`);
            });
        }

        function displayJobs(filteredJobs) {
            const $jobResults = $("#jobResults");
            $jobResults.empty();
            
            if (!filteredJobs.length) {
                $("#noResults").show();
                return;
            }

            $("#noResults").hide();
            filteredJobs.sort((a, b) => new Date(b.postedDate) - new Date(a.postedDate));

            filteredJobs.forEach(job => {
                const isApplied = appliedJobs.has(`${job.title}-${job.company}`);
                const isNew = (Date.now() - new Date(job.postedDate)) / (1000 * 60 * 60 * 24) <= 7;
                
                const jobCard = `
                    <article class="job-card ${isNew ? 'new-job' : ''}" 
                             tabindex="0" 
                             aria-label="Job: ${job.title} at ${job.company}">
                        <h3 class="job-title">${job.title}</h3>
                        <div class="job-details">
                            <p><strong>Company:</strong> ${job.company}</p>
                            <p><strong>Location:</strong> ${job.location}</p>
                            <p><strong>Salary:</strong> $${parseInt(job.salary).toLocaleString()}</p>
                            <p><strong>Type:</strong> ${job.type}</p>
                            <p><strong>Experience:</strong> ${job.experience}</p>
                            <p><strong>Posted:</strong> ${new Date(job.postedDate).toLocaleDateString()}</p>
                        </div>
                        <button class="apply-btn" 
                                ${isApplied ? 'disabled' : ''} 
                                onclick="applyJob('${job.title}', '${job.company}')"
                                aria-label="${isApplied ? 'Already applied to ' : 'Apply for '} ${job.title} at ${job.company}">
                            ${isApplied ? 'Applied' : 'Apply Now'}
                            <span class="status-badge" style="background-color: ${isApplied ? '#6c757d' : '#28a745'}">
                                ${isApplied ? 'Applied' : 'Open'}
                            </span>
                        </button>
                    </article>
                `;
                $jobResults.append(jobCard);
            });

            // Ensure job cards are initially visible
            const jobCards = document.querySelectorAll('.job-card');
            jobCards.forEach(card => {
                card.style.opacity = '1';
                card.style.display = 'block';
            });

            console.log('Number of job cards displayed:', jobCards.length);
            animateCards();
        }

        function filterJobs() {
            const filters = {
                role: $("#jobRole").val().trim().toLowerCase(),
                location: $("#locationFilter").val(),
                type: $("#typeFilter").val(),
                experience: $("#experienceFilter").val(),
                minSalary: parseInt($("#minSalary").val()) || 0,
                maxSalary: parseInt($("#maxSalary").val()) || Infinity
            };

            const filteredJobs = jobs.filter(job => {
                const jobSalary = parseInt(job.salary.replace(/[^0-9]/g, ''));
                return (
                    (!filters.role || job.title.toLowerCase().includes(filters.role)) &&
                    (!filters.location || job.location === filters.location) &&
                    (!filters.type || job.type === filters.type) &&
                    (!filters.experience || job.experience === filters.experience) &&
                    (jobSalary >= filters.minSalary && jobSalary <= filters.maxSalary)
                );
            });

            displayJobs(filteredJobs);
        }

        function applyJob(title, company) {
            const jobId = `${title}-${company}`;
            appliedJobs.add(jobId);
            alert(`Application submitted for ${title} at ${company}!`);
            filterJobs();
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function animateElements() {
            gsap.from('.navbar', { 
                duration: 1, 
                opacity: 0, 
                y: -50, 
                ease: 'power2.out' 
            });
            gsap.from('.filter-container', { 
                duration: 1.2, 
                opacity: 0, 
                y: -100, 
                ease: 'back.out(1.7)',
                delay: 0.3 
            });
            gsap.from('footer', { 
                duration: 1, 
                opacity: 0, 
                y: 50, 
                ease: 'power2.out', 
                delay: 0.6 
            });
        }

        function animateCards() {
            gsap.from('.job-card', {
                duration: 0.8,
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
        }

        document.addEventListener('DOMContentLoaded', () => {
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
        });
    </script>
</body>
</html>