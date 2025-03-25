<?php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'jobseeker';

// Fetch the other user to chat with
$other_user_id = isset($_GET['with']) ? (int)$_GET['with'] : null;
$other_user = null;

if ($other_user_id) {
    $stmt = $conn->prepare("SELECT id, name, role, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $other_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $other_user = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$other_user || $other_user['role'] === $user_role) {
    $_SESSION['error_message'] = "Invalid user to chat with. Please select a valid employer or job seeker.";
    header("Location: dashboard.php");
    exit();
}

// Fetch recent conversations (simulated using applications)
$recent_conversations = [];
if ($user_role === 'jobseeker') {
    // For job seekers: fetch employers they applied to
    $stmt = $conn->prepare("
        SELECT DISTINCT u.id, u.name, u.role, j.title AS job_title
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON j.employer_id = u.id
        WHERE a.user_id = ?
        ORDER BY a.id DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $user_id);
} else {
    // For employers: fetch job seekers who applied to their jobs
    $stmt = $conn->prepare("
        SELECT DISTINCT u.id, u.name, u.role, j.title AS job_title
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.user_id = u.id
        WHERE j.employer_id = ?
        ORDER BY a.id DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_conversations[] = $row;
}
$stmt->close();

// Debug: Log recent conversations
error_log("Recent conversations: " . json_encode($recent_conversations));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Chat with employers or job seekers on Job Portal">
    <title>Chat | Job Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" 
          crossorigin="anonymous">
    <style>
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

        .chat-section {
            margin: 2rem 0;
        }

        .profile-card {
            background: #fff;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .profile-card h5 {
            color: #2c3e50;
            font-weight: 700;
        }

        .profile-card p {
            margin: 0.5rem 0;
            color: #555;
        }

        .chat-container {
            background: #fff;
            padding: 1rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: 500px;
            width: 100%;
        }

        .sidebar {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: 500px;
            overflow-y: auto;
        }

        .sidebar h5 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .sidebar .list-group-item {
            border: none;
            border-bottom: 1px solid #e0e0e0;
            padding: 1rem;
            transition: background-color 0.3s ease;
        }

        .sidebar .list-group-item:hover {
            background-color: #e9ecef;
        }

        .btn-primary {
            background: #3498db;
            border: none;
            border-radius: 10px;
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
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: scale(1.05);
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
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

        @media (max-width: 768px) {
            .chat-container, .sidebar {
                height: 400px;
            }

            .profile-card {
                padding: 1rem;
            }

            .sidebar {
                margin-top: 1rem;
            }

            .navbar {
                padding: 0.5rem 1rem;
            }

            .navbar-brand {
                font-size: 1.5rem;
            }

            .social-icons img {
                width: 24px;
                height: 24px;
                margin: 0 0.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php" aria-label="Job Portal Home">Job Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                data-bs-target="#navbarNav" aria-controls="navbarNav" 
                aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <?php if ($user_role === 'jobseeker'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="resume_builder.php">Resume Builder</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-actions">
                    <span class="navbar-text me-3" aria-live="polite">
                        Welcome, <?php echo htmlspecialchars($user_name); ?>
                    </span>
                    <a href="../auth/logout.php" class="btn btn-nav-primary" 
                       aria-label="Logout">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mt-5" role="main">
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger rounded-pill text-center" role="alert">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <section class="chat-section" aria-labelledby="chat-heading">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="display-5" id="chat-heading">Chat with <?php echo htmlspecialchars($other_user['name']); ?></h2>
                <a href="dashboard.php" class="btn btn-secondary" aria-label="Back to Dashboard">Back to Dashboard</a>
            </div>
            <p class="lead mb-4">Communicate directly with <?php echo $other_user['role'] === 'employer' ? 'an employer' : 'a job seeker'; ?>.</p>

            <!-- Profile Card -->
            <div class="profile-card">
                <h5><?php echo htmlspecialchars($other_user['name']); ?></h5>
                <p><strong>Role:</strong> <?php echo ucfirst($other_user['role']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($other_user['email'] ?? 'Not provided'); ?></p>
            </div>

            <!-- Chat Area with Sidebar -->
            <div class="row">
                <!-- Sidebar for Recent Conversations -->
                <div class="col-md-3">
                    <div class="sidebar">
                        <h5>Recent Conversations</h5>
                        <?php if (empty($recent_conversations)): ?>
                            <p class="text-muted">No recent conversations.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($recent_conversations as $conversation): ?>
                                    <li class="list-group-item">
                                        <a href="chat.php?with=<?php echo $conversation['id']; ?>" 
                                           class="text-decoration-none">
                                            <strong><?php echo htmlspecialchars($conversation['name']); ?></strong>
                                            <p class="mb-0 text-muted">
                                                <?php echo htmlspecialchars($conversation['job_title']); ?>
                                            </p>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat Container -->
                <div class="col-md-9">
                    <div class="chat-container" id="talkjs-container">
                        <i>Loading chat...</i>
                    </div>
                </div>
            </div>
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
                        <a href="https://instagram.com" target="_blank" 
                           aria-label="Follow us on Instagram" title="Instagram">
                            <img src="https://img.icons8.com/color/48/000000/instagram-new.png" 
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
            // GSAP Animations
            gsap.from('.navbar', {
                duration: 1,
                opacity: 0,
                y: -50,
                ease: 'power2.out'
            });

            gsap.from('.chat-section h2', {
                duration: 1.2,
                opacity: 0,
                y: -100,
                ease: 'back.out(1.7)',
                delay: 0.2
            });

            gsap.from('.profile-card', {
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out',
                delay: 0.4
            });

            gsap.from('.sidebar', {
                duration: 1,
                opacity: 0,
                x: -50,
                ease: 'power2.out',
                delay: 0.6
            });

            gsap.from('.chat-container', {
                duration: 1,
                opacity: 0,
                x: 50,
                ease: 'power2.out',
                delay: 0.8
            });

            gsap.from('footer', {
                duration: 1,
                opacity: 0,
                y: 50,
                ease: 'power2.out',
                delay: 1
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
        });

        Talk.ready.then(function() {
            console.log("TalkJS is ready, initializing users...");
            const me = new Talk.User({
                id: "<?php echo $user_id; ?>",
                name: "<?php echo htmlspecialchars($user_name); ?>",
                email: <?php echo isset($_SESSION['user_email']) && !empty($_SESSION['user_email']) ? json_encode(htmlspecialchars($_SESSION['user_email'])) : 'null'; ?>,
                role: "<?php echo $user_role; ?>",
                welcomeMessage: "Hi!"
            });

            const other = new Talk.User({
                id: "<?php echo $other_user['id']; ?>",
                name: "<?php echo htmlspecialchars($other_user['name']); ?>",
                email: <?php echo isset($other_user['email']) && !empty($other_user['email']) ? json_encode(htmlspecialchars($other_user['email'])) : 'null'; ?>,
                role: "<?php echo $other_user['role']; ?>",
                welcomeMessage: "Hey, how can I help?"
            });

            console.log("Users initialized:", { me: me, other: other });

            const session = new Talk.Session({
                appId: "tkr3cqVc", // Replace with your actual TalkJS App ID
                me: me
            });

            console.log("Session created, creating conversation...");

            const conversation = session.getOrCreateConversation(
                Talk.oneOnOneId(me, other)
            );
            conversation.setParticipant(me);
            conversation.setParticipant(other);

            console.log("Conversation created:", conversation.id);

            const chatbox = session.createChatbox();
            chatbox.select(conversation);

            // Enable file sharing
            chatbox.on("sendMessage", function(event) {
                if (event.attachment) {
                    console.log("File sent:", event.attachment);
                }
            });

            console.log("Chatbox created, mounting...");

            const mountChat = () => {
                chatbox.mount(document.getElementById("talkjs-container")).catch(function(error) {
                    console.error("Failed to mount TalkJS chatbox:", error);
                    document.getElementById("talkjs-container").innerHTML = `
                        <div class="error-message">
                            <p>Error: Unable to load chat. Please try again later. (Error: ${error.message})</p>
                            <button class="btn btn-primary mt-2" onclick="mountChat()">Retry</button>
                        </div>
                    `;
                });
            };

            mountChat();
        }).catch(function(error) {
            console.error("TalkJS initialization failed:", error);
            document.getElementById("talkjs-container").innerHTML = `
                <div class="error-message">
                    <p>Error: Unable to load chat. Please try again later. (Error: ${error.message})</p>
                    <button class="btn btn-primary mt-2" onclick="location.reload()">Retry</button>
                </div>
            `;
        });
    </script>
</body>
</html>