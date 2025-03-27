<?php
session_start();
require_once __DIR__ . '/includes/db.php';

// Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
$user_role = $_SESSION['user_role'];
$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8');

// Validate room ID from URL
$room_id = $_GET['room'] ?? null;
if (!$room_id) {
    die("Error: No room ID provided.");
}

// Verify the user is part of the interview session
$stmt = $conn->prepare("SELECT user1_id, user2_id FROM interview_sessions WHERE room_id = ?");
$stmt->bind_param("s", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$session = $result->fetch_assoc();
$stmt->close();

if (!$session || ($session['user1_id'] != $user_id && $session['user2_id'] != $user_id)) {
    die("Error: You are not authorized to access this interview.");
}

$other_user_id = ($session['user1_id'] == $user_id) ? $session['user2_id'] : $session['user1_id'];

$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $other_user_id);
$stmt->execute();
$result = $stmt->get_result();
$other_user = $result->fetch_assoc();
$stmt->close();
$other_user_name = htmlspecialchars($other_user['name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Join a live interview session with video conferencing and a basic code editor">
    <title>Interview Portal | FresherStart</title>
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
        .interview-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        #jitsi-meet-container {
            width: 100%;
            height: 500px;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        #editor {
            width: 100%;
            height: 300px;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        #output {
            width: 100%;
            height: 150px;
            background: #1a1a1a;
            color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 1rem;
            overflow-y: auto;
            font-family: 'Courier New', Courier, monospace;
            white-space: pre-wrap;
        }
        .editor-controls {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .status-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 1.5rem;
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
        .btn-run {
            background: #28a745;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        .btn-run:hover {
            background: #218838;
            transform: scale(1.05);
        }
        .btn-end-interview {
            background: #dc3545;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        .btn-end-interview:hover {
            background: #c82333;
            transform: scale(1.05);
        }
        .toast-container { z-index: 1060; }
        .toast {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .cheating-alert { background: #dc3545; color: white; font-weight: bold; }
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
        @media (max-width: 768px) {
            .navbar { padding: 0.5rem 1rem; }
            .navbar-brand { font-size: 1.5rem; }
            .welcome-section { padding: 1.5rem; }
            .welcome-section h2 { font-size: 2rem; }
            #jitsi-meet-container { height: 300px; }
            #editor { height: 200px; }
            #output { height: 100px; }
            .interview-section { padding: 1rem; }
            .status-card { padding: 1rem; }
        }
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="cheatingToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body cheating-alert"></div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php" aria-label="FresherStart Home">FresherStart</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#navbarNav" aria-controls="navbarNav" 
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="views/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Interview</a>
                    </li>
                </ul>
                <div class="navbar-actions">
                    <span class="navbar-text me-3" aria-live="polite">
                        Welcome, <?php echo $user_name; ?>
                    </span>
                    <a href="auth/logout.php" class="btn btn-nav-primary" 
                       aria-label="Logout">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mt-5" role="main">
        <section class="welcome-section" aria-labelledby="welcome-heading">
            <h2 class="display-5 mb-3" id="welcome-heading">Interview with <?php echo $other_user_name; ?></h2>
            <p class="lead">Join the live video session and use the code editor below</p>
        </section>

        <div class="row">
            <!-- Jitsi Video and Editor Section -->
            <div class="col-lg-8">
                <section class="interview-section" aria-labelledby="video-heading">
                    <h3 id="video-heading" class="mb-3">Video Conference</h3>
                    <div id="jitsi-meet-container"></div>

                    <h3 id="editor-heading" class="mb-3 mt-4">Code Editor</h3>
                    <div id="editor">function helloWorld() { console.log("Hello, World!"); }</div>
                    <div class="editor-controls">
                        <select id="languageSelect" class="form-select w-auto" aria-label="Select programming language">
                            <option value="javascript">JavaScript</option>
                            <option value="python">Python</option>
                            <option value="java">Java</option>
                            <option value="cpp">C++</option>
                            <option value="html">HTML</option>
                        </select>
                        <button type="button" id="runCode" class="btn btn-run" aria-label="Run code">Run</button>
                        <button type="button" id="clearEditor" class="btn btn-secondary" aria-label="Clear editor">Clear</button>
                    </div>
                    <div id="output">Output will appear here...</div>
                </section>
            </div>

            <!-- Status/Info Panel -->
            <div class="col-lg-4">
                <div class="status-card mb-4">
                    <h4>Session Details</h4>
                    <p><strong>Room ID:</strong> <?php echo htmlspecialchars($room_id); ?></p>
                    <p><strong>Your Role:</strong> <?php echo ucfirst($user_role); ?></p>
                    <p><strong>Participant:</strong> <?php echo $other_user_name; ?></p>
                    <p><strong>Start Time:</strong> <?php echo date('H:i:s'); ?></p>
                </div>
                <div class="status-card">
                    <h4>Actions</h4>
                    <button id="muteAudio" class="btn btn-secondary mb-2 w-100">Mute Audio</button>
                    <button id="muteVideo" class="btn btn-secondary w-100">Mute Video</button>
                </div>
            </div>
        </div>

        <div class="mt-3 text-center">
            <button id="end-interview" class="btn btn-end-interview">End Interview</button>
            <a href="views/dashboard.php" class="btn btn-secondary ms-2" 
               aria-label="Back to Dashboard">Back to Dashboard</a>
        </div>
    </main>

    <!-- Footer -->
    <footer role="contentinfo">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>FresherStart</h5>
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
                <p class="mb-0">© <?php echo date('Y'); ?> FresherStart. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://meet.jit.si/external_api.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@3.9.0/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" 
            crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" 
            integrity="sha512-16esztaSRplJROstbIIdwX3N97V1+pZvV33ABoG1H2OyTttBxEGkTsoIVsiP1iaTtM8b3+hu2kB6pQ4Clr5yug==" 
            crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.js" 
            integrity="sha512-GZ1RIgZaSc8rnco/8CXfRdCpDxRCphenWuh84MXrnumyUh7/37ENwFs3LPx1F3G0o0F4T4Lq5L+0ZuhV1s0w==" 
            crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const userRole = "<?php echo $user_role; ?>";
            const userId = "<?php echo $user_id; ?>";
            const userName = "<?php echo $user_name; ?>";
            const roomId = "<?php echo $room_id; ?>";
            const otherUserId = "<?php echo $other_user_id; ?>";

            // Initialize Jitsi Meet
            const domain = 'meet.jit.si';
            const options = {
                roomName: roomId,
                width: '100%',
                height: '100%',
                parentNode: document.querySelector('#jitsi-meet-container'),
                userInfo: {
                    displayName: userName,
                    email: userRole === 'employer' ? 'employer@example.com' : 'candidate@example.com'
                },
                configOverwrite: {
                    startWithAudioMuted: false,
                    startWithVideoMuted: false,
                    disableDeepLinking: true
                },
                interfaceConfigOverwrite: {
                    SHOW_JITSI_WATERMARK: false,
                    SHOW_WATERMARK_FOR_GUESTS: false
                }
            };
            const api = new JitsiMeetExternalAPI(domain, options);

            // Initialize Ace Editor
            const editor = ace.edit('editor');
            editor.setTheme('ace/theme/monokai');
            editor.session.setMode('ace/mode/javascript');
            editor.setOptions({
                fontSize: '14px',
                enableBasicAutocompletion: true,
                enableLiveAutocompletion: true,
                showPrintMargin: false
            });

            const output = document.getElementById('output');

            // Custom console.log to capture output
            const originalConsoleLog = console.log;
            console.log = (...args) => {
                output.textContent += args.join(' ') + '\n';
                output.scrollTop = output.scrollHeight;
                originalConsoleLog.apply(console, args);
            };

            // Language change
            document.getElementById('languageSelect').addEventListener('change', () => {
                const language = document.getElementById('languageSelect').value;
                editor.session.setMode(`ace/mode/${language}`);
                output.textContent = `Note: Only JavaScript can be executed in this editor.\n`;
            });

            // Run code
            document.getElementById('runCode').addEventListener('click', () => {
                const language = document.getElementById('languageSelect').value;
                output.textContent = ''; // Clear previous output

                if (language !== 'javascript') {
                    output.textContent = `Error: Only JavaScript can be executed in this editor.\nSelect "JavaScript" to run code.`;
                    return;
                }

                try {
                    const code = editor.getValue();
                    const func = new Function(code);
                    func();
                } catch (error) {
                    output.textContent = `Error: ${error.message}`;
                }
            });

            // Clear editor
            document.getElementById('clearEditor').addEventListener('click', () => {
                editor.setValue('');
                output.textContent = 'Output will appear here...';
            });

            // Mute controls
            document.getElementById('muteAudio').addEventListener('click', () => {
                api.executeCommand('toggleAudio');
            });
            document.getElementById('muteVideo').addEventListener('click', () => {
                api.executeCommand('toggleVideo');
            });

            // End interview
            document.getElementById('end-interview').addEventListener('click', () => {
                api.dispose();
                window.location.href = `views/chat.php?with=${otherUserId}`;
            });

            // GSAP Animations
            gsap.from('.navbar', { duration: 1, opacity: 0, y: -50, ease: 'power2.out' });
            gsap.from('.welcome-section', { duration: 1.2, opacity: 0, y: -100, ease: 'back.out(1.7)', delay: 0.2 });
            gsap.from('.interview-section', { duration: 1, opacity: 0, y: 50, ease: 'power2.out', delay: 0.4 });
            gsap.from('#editor', { duration: 1, opacity: 0, y: 50, ease: 'power2.out', delay: 0.6 });
            gsap.from('#output', { duration: 1, opacity: 0, y: 50, ease: 'power2.out', delay: 0.8 });
            gsap.from('.status-card', { duration: 1, opacity: 0, y: 50, stagger: 0.2, ease: 'power2.out', delay: 1 });
            gsap.from('footer', { duration: 1, opacity: 0, y: 50, ease: 'power2.out', delay: 1.2 });

            // Social Icons Fallback
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

            // Cheating Detection Logic
            let cheatingDetected = false;
            function showCheatingAlert(message) {
                if (userRole === 'employer') {
                    const toast = new bootstrap.Toast(document.getElementById('cheatingToast'));
                    document.querySelector('#cheatingToast .toast-body').textContent = message;
                    toast.show();
                }
            }
            function notifyEmployer(message) {
                if (!cheatingDetected) {
                    cheatingDetected = true;
                    showCheatingAlert(message);
                    setTimeout(() => cheatingDetected = false, 10000);
                }
            }
            if (userRole === 'candidate') {
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) notifyEmployer('Candidate may be cheating: Tab switch detected.');
                });
                window.addEventListener('blur', () => {
                    notifyEmployer('Candidate may be cheating: Window focus lost.');
                });
                document.documentElement.requestFullscreen();
                document.addEventListener('fullscreenchange', () => {
                    if (!document.fullscreenElement) notifyEmployer('Candidate may be cheating: Exited fullscreen.');
                });
                Promise.all([
                    faceApi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights'),
                    faceApi.nets.faceLandmark68Net.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights'),
                    faceApi.nets.faceRecognitionNet.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights')
                ]).then(startFaceDetection).catch(error => console.error("Face-api load error:", error));

                async function startFaceDetection() {
                    api.getParticipantsInfo().forEach(participant => {
                        if (participant.displayName === userName) {
                            api.getVideoStream(participant.id).then(stream => {
                                const video = document.createElement('video');
                                video.srcObject = stream;
                                video.play();
                                setInterval(async () => {
                                    const detections = await faceApi.detectAllFaces(video, new faceApi.TinyFaceDetectorOptions());
                                    if (detections.length > 1) notifyEmployer('Candidate may be cheating: Multiple faces detected.');
                                }, 5000);
                            }).catch(err => console.error('Video stream error:', err));
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>