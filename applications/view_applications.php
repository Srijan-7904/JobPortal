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
// Validate and normalize the employer's email
$user_email_raw = $_SESSION['user_email'] ?? null;
$user_email = null;
if ($user_email_raw && filter_var($user_email_raw, FILTER_VALIDATE_EMAIL)) {
    $user_email = htmlspecialchars($user_email_raw, ENT_QUOTES, 'UTF-8');
}

// Fetch applications with error handling
try {
    $stmt = $conn->prepare("
        SELECT a.id, a.resume, a.notes, u.id AS applicant_id, u.name AS applicant_name, u.email AS applicant_email, j.id AS job_id, j.title AS job_title
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
    <title>View Applications | RookieRise</title>
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
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 2rem;
            opacity: 1;
        }
        .table {
            opacity: 1;
        }
        .table tbody tr {
            opacity: 1;
            display: table-row;
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
        .btn-interview {
            background: #e67e22;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            color: white;
        }
        .btn-interview:hover {
            background: #d35400;
            transform: scale(1.05);
        }
        .btn-interview:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .btn-join-interview {
            background: #28a745;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            color: white;
        }
        .btn-join-interview:hover {
            background: #218838;
            transform: scale(1.05);
        }
        .chat-icon {
            width: 20px;
            height: 20px;
        }
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
        :focus-visible {
            outline: 2px solid #00aaff;
            outline-offset: 2px;
        }
        .toast-container {
            z-index: 1060;
        }
        .toast {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        /* Chat Sidebar Styles */
        .chat-sidebar {
            position: fixed;
            top: 0;
            right: -400px; /* Hidden by default */
            width: 400px;
            height: 100%;
            background: white;
            box-shadow: -5px 0 20px rgba(0,0,0,0.2);
            z-index: 1050;
            transition: right 0.3s ease-in-out;
        }
        .chat-sidebar.open {
            right: 0;
        }
        .chat-sidebar-header {
            background: #1a2a44;
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-sidebar-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.2rem;
        }
        .chat-sidebar-header .btn-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0.8;
        }
        .chat-sidebar-header .btn-close:hover {
            opacity: 1;
        }
        .chat-sidebar-body {
            padding: 0;
            height: calc(100% - 60px);
        }
        .chat-sidebar-body #chat-container {
            width: 100%;
            height: 100%;
            border: none;
        }
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
            .chat-sidebar {
                width: 300px;
                right: -300px;
            }
            .chat-sidebar.open {
                right: 0;
            }
        }
        /* Added Notes Styles */
        .btn-notes {
            background: #8e44ad;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            color: white;
        }
        .btn-notes:hover {
            background: #732d91;
            transform: scale(1.05);
        }
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .modal-header {
            background: #1a2a44;
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }
        .notes-textarea {
            resize: vertical;
            min-height: 100px;
        }
    </style>
</head>
<body>
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="interviewToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body"></div>
        </div>
    </div>

    <!-- Added Notes Modal -->
    <div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notesModalLabel">Application Notes</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="notesForm">
                        <input type="hidden" id="applicationId">
                        <div class="mb-3">
                            <label for="notesInput" class="form-label">Notes</label>
                            <textarea class="form-control notes-textarea" id="notesInput" placeholder="Enter your notes here..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveNotes">Save Notes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Sidebar -->
    <div class="chat-sidebar" id="chatSidebar">
        <div class="chat-sidebar-header">
            <h5 id="chatSidebarTitle">Chat with Applicant</h5>
            <button type="button" class="btn-close" id="closeChatSidebar" aria-label="Close">✕</button>
        </div>
        <div class="chat-sidebar-body">
            <div id="chat-container">
                <div class="text-center p-4">
                    <p>Loading chat...</p>
                </div>
            </div>
        </div>
    </div>

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
            <p class="lead">Review applications and send interview invites</p>
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
                                <?php
                                $isScheduled = false;
                                $interviewStatus = null;
                                $roomId = null;

                                try {
                                    $stmt = $conn->prepare("SELECT room_id, status FROM interview_sessions WHERE user1_id = ? AND user2_id = ? AND job_id = ?");
                                    if (!$stmt) {
                                        throw new Exception("Prepare failed: " . $conn->error);
                                    }
                                    $stmt->bind_param("iii", $application['applicant_id'], $user_id, $application['job_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    if ($result->num_rows > 0) {
                                        $isScheduled = true;
                                        $interview = $result->fetch_assoc();
                                        $interviewStatus = $interview['status'] ?? null;
                                        $roomId = $interview['room_id'] ?? null;
                                    }
                                    $stmt->close();
                                } catch (Exception $e) {
                                    error_log("Error checking interview session: " . $e->getMessage());
                                    $isScheduled = false;
                                    $interviewStatus = null;
                                    $roomId = null;
                                }

                                // Validate and normalize the applicant's email
                                $applicant_email_raw = $application['applicant_email'] ?? null;
                                $applicant_email = null;
                                if ($applicant_email_raw && filter_var($applicant_email_raw, FILTER_VALIDATE_EMAIL)) {
                                    $applicant_email = htmlspecialchars($applicant_email_raw, ENT_QUOTES, 'UTF-8');
                                }
                                ?>
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
                                        <!-- Added Notes Button -->
                                        <button class="btn btn-notes ms-2 open-notes-modal" 
                                                data-application-id="<?php echo $application['id']; ?>"
                                                data-notes="<?php echo htmlspecialchars($application['notes'] ?? ''); ?>"
                                                aria-label="Add notes for <?php echo htmlspecialchars($application['applicant_name']); ?>">
                                            Notes
                                        </button>
                                        <button class="btn btn-chat ms-2 open-chat-sidebar" 
                                                data-applicant-id="<?php echo $application['applicant_id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($application['applicant_name']); ?>" 
                                                data-email="<?php echo htmlspecialchars($applicant_email ?? ''); ?>" 
                                                aria-label="Chat with <?php echo htmlspecialchars($application['applicant_name']); ?>">
                                            <img src="https://img.icons8.com/ios-filled/50/ffffff/chat.png" 
                                                 alt="Chat Icon" 
                                                 class="chat-icon">
                                            Chat
                                        </button>
                                        <?php if ($isScheduled && $interviewStatus === 'scheduled'): ?>
                                            <a href="../interview_portal.php?room=<?php echo htmlspecialchars($roomId); ?>" 
                                               class="btn btn-join-interview ms-2" 
                                               aria-label="Join interview with <?php echo htmlspecialchars($application['applicant_name']); ?>">
                                                Join Interview
                                            </a>
                                        <?php elseif ($isScheduled && $interviewStatus === 'completed'): ?>
                                            <button class="btn btn-secondary ms-2" disabled 
                                                    aria-label="Interview completed with <?php echo htmlspecialchars($application['applicant_name']); ?>">
                                                Interview Completed
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-interview ms-2 send-interview-invite" 
                                                    data-applicant-id="<?php echo $application['applicant_id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($application['applicant_name']); ?>" 
                                                    data-email="<?php echo htmlspecialchars($applicant_email ?? ''); ?>" 
                                                    data-job-id="<?php echo $application['job_id']; ?>" 
                                                    aria-label="Send interview invite to <?php echo htmlspecialchars($application['applicant_name']); ?>">
                                                Send Interview Invite
                                            </button>
                                        <?php endif; ?>
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
                    <h5>RookieRise</h5>
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
                <p class="mb-0">© <?php echo date('Y'); ?> RookieRise. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Minified snippet to load TalkJS asynchronously -->
    <script>
        (function(t,a,l,k,j,s){
        s=a.createElement('script');s.async=1;s.src='https://cdn.talkjs.com/talk.js';a.head.appendChild(s)
        ;k=t.Promise;t.Talk={v:3,ready:{then:function(f){if(k)return new k(function(r,e){l.push([f,r,e])});l
        .push([f])},catch:function(){return k&&new k()},c:l}};})(window,document,[]);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" 
            crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" 
            integrity="sha512-16esztaSRplJROstbIIdwX3N97V1+pZvV33ABoG1H2OyTttBxEGkTsoIVsiP1iaTtM8b3+hu2kB6pQ4Clr5yug==" 
            crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const applicationRows = document.querySelectorAll('tbody tr');
            console.log('Number of application rows:', applicationRows.length);

            applicationRows.forEach(row => {
                row.style.opacity = '1';
                row.style.display = 'table-row';
            });

            gsap.from('.navbar', { duration: 1, opacity: 0, y: -50, ease: 'power2.out' });
            gsap.from('.welcome-section', { duration: 1.2, opacity: 0, y: -100, ease: 'back.out(1.7)', delay: 0.2 });
            gsap.from('.table-container', { duration: 1, opacity: 0, y: 50, ease: 'power2.out', delay: 0.4 });
            gsap.from('tbody tr', { duration: 0.8, opacity: 0, x: -50, stagger: 0.1, ease: 'power2.out', delay: 0.6 });

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

            gsap.from('footer', { duration: 1, opacity: 0, y: 50, ease: 'power2.out', delay: 0.8 });

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

            // Chat Sidebar Functionality
            const chatSidebar = document.getElementById('chatSidebar');
            const chatSidebarTitle = document.getElementById('chatSidebarTitle');
            const chatContainer = document.getElementById('chat-container');
            const closeChatSidebar = document.getElementById('closeChatSidebar');
            let currentChatbox = null;
            let currentConversation = null;

            function openChatSidebar(applicantId, name, email, callback = null) {
                // Validate and normalize the applicant's email
                let validatedEmail = null;
                if (email && email.trim() !== '' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    validatedEmail = email;
                }

                // Update sidebar title
                chatSidebarTitle.textContent = `Chat with ${name}`;

                // Reset the chat container
                chatContainer.innerHTML = '<div class="text-center p-4"><p>Loading chat...</p></div>';

                // Initialize TalkJS chat
                Talk.ready.then(() => {
                    console.log("TalkJS is ready, initializing users...");
                    const me = new Talk.User({
                        id: <?php echo $user_id; ?>,
                        name: '<?php echo $user_name; ?>',
                        email: <?php echo $user_email ? json_encode($user_email) : 'null'; ?>,
                        role: 'employer',
                        welcomeMessage: "Hi!"
                    });
                    const other = new Talk.User({
                        id: applicantId,
                        name: name,
                        email: validatedEmail,
                        role: 'jobseeker',
                        welcomeMessage: "Hey, how can I help?"
                    });
                    console.log("Users initialized:", { me: me, other: other });

                    const session = new Talk.Session({
                        appId: 'tkr3cqVc', // Your TalkJS App ID
                        me: me
                    });
                    console.log("Session created");

                    const conversationId = Talk.oneOnOneId(me, other);
                    console.log("Conversation ID:", conversationId);

                    const conversation = session.getOrCreateConversation(conversationId);
                    conversation.setParticipant(me);
                    conversation.setParticipant(other);
                    console.log("Conversation created:", conversation.id);

                    // Store the conversation for later use
                    currentConversation = conversation;

                    // Create and mount the chatbox
                    if (currentChatbox) {
                        currentChatbox.destroy();
                    }
                    currentChatbox = session.createChatbox();
                    currentChatbox.select(conversation);

                    currentChatbox.mount(chatContainer).catch(error => {
                        console.error("Failed to mount TalkJS chatbox:", error);
                        chatContainer.innerHTML = `
                            <div class="text-center p-4">
                                <p class="text-danger">Error: Unable to load chat. Please try again later.</p>
                            </div>
                        `;
                    });

                    // Open the sidebar
                    chatSidebar.classList.add('open');

                    // Execute callback if provided (e.g., sending the interview invite)
                    if (callback) {
                        callback(conversation);
                    }
                }).catch(error => {
                    console.error('TalkJS initialization failed:', error);
                    chatContainer.innerHTML = `
                        <div class="text-center p-4">
                            <p class="text-danger">Error: Unable to initialize chat. Please try again later.</p>
                        </div>
                    `;
                    chatSidebar.classList.add('open');
                });
            }

            function closeChatSidebarHandler() {
                chatSidebar.classList.remove('open');
                if (currentChatbox) {
                    currentChatbox.destroy();
                    currentChatbox = null;
                }
                currentConversation = null;
                chatContainer.innerHTML = '<div class="text-center p-4"><p>Loading chat...</p></div>';
            }

            closeChatSidebar.addEventListener('click', closeChatSidebarHandler);

            // Open Chat Sidebar for "Chat" Button
            document.querySelectorAll('.open-chat-sidebar').forEach(button => {
                button.addEventListener('click', () => {
                    const applicantId = button.getAttribute('data-applicant-id');
                    const name = button.getAttribute('data-name');
                    const email = button.getAttribute('data-email');
                    openChatSidebar(applicantId, name, email);
                });
            });

            // Send Interview Invite and Open Chat Sidebar
            document.querySelectorAll('.send-interview-invite').forEach(button => {
                button.addEventListener('click', async () => {
                    const applicantId = button.getAttribute('data-applicant-id');
                    const name = button.getAttribute('data-name');
                    const emailRaw = button.getAttribute('data-email');
                    const jobId = button.getAttribute('data-job-id');

                    // Validate and normalize the applicant's email
                    let email = null;
                    if (emailRaw && emailRaw.trim() !== '' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailRaw)) {
                        email = emailRaw;
                    }

                    try {
                        console.log('Sending interview invite to:', { applicantId, name, jobId, email });

                        // Step 1: Create interview session via API
                        const sessionResponse = await fetch('../api/message.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'create_session',
                                user_id: applicantId,
                                employer_id: <?php echo $user_id; ?>,
                                job_id: jobId
                            })
                        });

                        if (!sessionResponse.ok) {
                            const errorText = await sessionResponse.text();
                            console.error('Raw response on error:', errorText);
                            throw new Error(`HTTP error! Status: ${sessionResponse.status} - ${errorText}`);
                        }

                        const rawResponse = await sessionResponse.text();
                        console.log('Raw response:', rawResponse);

                        let sessionData;
                        try {
                            sessionData = JSON.parse(rawResponse);
                        } catch (jsonError) {
                            console.error('JSON parsing error:', jsonError);
                            throw new Error('Invalid JSON response: ' + rawResponse);
                        }

                        console.log('Parsed session data:', sessionData);

                        if (sessionData.success) {
                            const roomId = sessionData.room_id;
                            const message = `Dear ${name}, you have been selected for an interview for Job ID ${jobId}. Please join the interview here: http://localhost/JobPortal/interview_portal.php?room=${roomId}`;

                            // Step 2: Open the chat sidebar and send the message
                            openChatSidebar(applicantId, name, email, (conversation) => {
                                conversation.sendMessage(message);
                                console.log("Message sent:", message);

                                // Show success toast
                                const toast = new bootstrap.Toast(document.getElementById('interviewToast'));
                                document.querySelector('#interviewToast .toast-body').textContent = `Interview invite sent to ${name}! Room ID: ${roomId}`;
                                toast.show();

                                // Update button state
                                button.disabled = true;
                                button.textContent = 'Invite Sent';
                            });
                        } else {
                            alert('Failed to create session: ' + (sessionData.error || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error during interview invite:', error);
                        alert('An error occurred: ' + error.message);
                    }
                });
            });

            // Added Notes Functionality
            document.querySelectorAll('.open-notes-modal').forEach(button => {
                button.addEventListener('click', () => {
                    const applicationId = button.getAttribute('data-application-id');
                    const existingNotes = button.getAttribute('data-notes');
                    
                    document.getElementById('applicationId').value = applicationId;
                    document.getElementById('notesInput').value = existingNotes || '';
                    
                    const modal = new bootstrap.Modal(document.getElementById('notesModal'));
                    modal.show();
                });
            });

            document.getElementById('saveNotes').addEventListener('click', async () => {
                const applicationId = document.getElementById('applicationId').value;
                const notes = document.getElementById('notesInput').value;

                try {
                    const response = await fetch('../api/save_notes.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            application_id: applicationId,
                            notes: notes
                        })
                    });

                    if (!response.ok) {
                        throw new Error('Failed to save notes');
                    }

                    const data = await response.json();
                    if (data.success) {
                        // Update the button's data-notes attribute
                        const button = document.querySelector(`.open-notes-modal[data-application-id="${applicationId}"]`);
                        button.setAttribute('data-notes', notes);
                        
                        // Show success toast
                        const toast = new bootstrap.Toast(document.getElementById('interviewToast'));
                        document.querySelector('#interviewToast .toast-body').textContent = 'Notes saved successfully!';
                        toast.show();

                        // Close modal
                        bootstrap.Modal.getInstance(document.getElementById('notesModal')).hide();
                    } else {
                        throw new Error(data.error || 'Failed to save notes');
                    }
                } catch (error) {
                    console.error('Error saving notes:', error);
                    alert('Failed to save notes: ' + error.message);
                }
            });
        });
    </script>
</body>
</html>