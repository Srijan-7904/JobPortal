<?php
session_start();
require_once __DIR__ . '/includes/db.php';

// Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
$user_role = $_SESSION['user_role'] === 'employer' ? 'interviewer' : 'jobseeker';
$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8');

// Generate or retrieve CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Validate room ID
$room_id = $_GET['room'] ?? null;
if (!$room_id) {
    die("Error: No room ID provided.");
}

// Verify session
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

// Generate token for Flask authentication
$secret_key = "your-secret-key"; // Match with Flask app
$token = hash('sha256', "$user_id:$room_id:$secret_key");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Join a live interview session with video conferencing and a code editor">
    <title>Interview Portal | RookieRise</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.5.1/socket.io.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <style>
        :root {
            --primary: #00aaff;
            --primary-dark: #0088cc;
            --secondary: #6c757d;
            --secondary-dark: #5a6268;
            --background: #f4f7fa;
            --card-bg: #ffffff;
            --text-dark: #1a2a44;
            --text-light: #666;
            --success: #28a745;
            --error: #dc3545;
            --warning: #ffc107;
            --chat-bg: #e9ecef;
            --chat-bubble-me: #00aaff;
            --chat-bubble-other: #f1f3f5;
            --glow: rgba(0, 170, 255, 0.2);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to bottom, var(--background), #e8eef3);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar-brand { font-weight: 600; font-size: 1.6rem; color: var(--text-dark); text-decoration: none; transition: color 0.3s ease; }
        .navbar-brand:hover { color: var(--primary); }
        .navbar-nav { display: flex; align-items: center; gap: 1rem; }
        .nav-link { color: var(--text-dark); font-weight: 500; padding: 0.5rem 1rem; text-decoration: none; border-radius: 8px; transition: background 0.3s ease; }
        .nav-link:hover { color: var(--primary); background: rgba(0, 170, 255, 0.1); }
        .btn-nav { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; border: none; border-radius: 20px; padding: 0.5rem 1.5rem; font-weight: 500; cursor: pointer; }
        .navbar-text { color: var(--text-dark); font-weight: 500; }
        .container {
            flex: 1;
            display: flex;
            width: 90%;
            margin: 0 auto;
            padding: 2rem;
            gap: 2rem;
        }
        .left-content { flex: 1; display: grid; grid-template-columns: 1fr; gap: 2rem; }
        .right-content { flex: 1; display: flex; flex-direction: column; gap: 2rem; }
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary), transparent);
            opacity: 0.7;
        }
        h1 { font-size: 2.2rem; font-weight: 600; color: var(--text-dark); text-align: center; }
        h2 { font-size: 1.4rem; font-weight: 500; color: var(--text-dark); margin-bottom: 0.5rem; }
        .video-tabs { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .tab-btn { padding: 0.5rem 1rem; background: var(--secondary); color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; }
        .tab-btn.active { background: var(--primary); }
        .video-container {
            border-radius: 12px;
            width: 100%;
            height: 360px;
            border: 1px solid #eee;
            position: relative;
            display: none;
            overflow: hidden;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.05);
        }
        .video-container.active { height: 480px; }
        video, img.remote-feed { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; }
        .video-controls { position: absolute; bottom: 10px; left: 10px; display: flex; gap: 10px; }
        .control-btn { padding: 0.5rem; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; }
        .control-btn.active { background: var(--error); }
        .status { text-align: center; font-size: 0.9rem; font-weight: 500; margin-top: 0.5rem; }
        .status.connected { color: var(--success); }
        .status.processing { color: var(--primary); }
        .status.error { color: var(--error); }
        #timer { text-align: center; font-size: 1.1rem; font-weight: 500; color: var(--text-dark); background: rgba(255, 193, 7, 0.1); padding: 0.5rem 1rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2); }
        #messages { width: 100%; padding: 1rem; border-radius: 12px; background: rgba(255, 245, 238, 0.8); color: var(--text-light); font-family: 'Courier New', monospace; font-size: 0.9rem; max-height: 150px; overflow-y: auto; border: 1px solid #eee; display: none; box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.05); }
        #messages.visible { display: block; }
        #cheating-dashboard p { font-size: 0.9rem; margin: 0.3rem 0; }
        #cheating-dashboard span { font-weight: bold; color: var(--primary); }
        .chat-icon { position: fixed; bottom: 2rem; right: 2rem; background: var(--primary); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); z-index: 901; }
        .chat-sidebar { position: fixed; top: 0; right: -400px; width: 400px; height: 100%; background: var(--chat-bg); box-shadow: -4px 0 20px rgba(0, 0, 0, 0.1); padding: 1.5rem; z-index: 900; display: flex; flex-direction: column; }
        .chat-sidebar.open { right: 0; }
        .chat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 1rem; background: white; border-radius: 12px; border: 1px solid #ddd; box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.05); }
        .chat-message { display: flex; align-items: flex-start; margin-bottom: 1rem; opacity: 0; }
        .chat-message.me { flex-direction: row-reverse; }
        .chat-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--secondary); display: flex; align-items: center; justify-content: center; color: white; font-weight: 500; font-size: 1rem; margin: 0 0.5rem; }
        .chat-bubble { max-width: 70%; padding: 0.75rem; border-radius: 12px; background: var(--chat-bubble-other); color: var(--text-dark); font-size: 0.9rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); }
        .chat-message.me .chat-bubble { background: var(--chat-bubble-me); color: white; }
        .chat-timestamp { font-size: 0.7rem; color: var(--text-light); margin-top: 0.25rem; }
        #chat-input { width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #ddd; background: var(--card-bg); font-family: 'Poppins', sans-serif; font-size: 0.9rem; margin-top: 1rem; transition: all 0.3s ease; }
        #chat-input:focus { border-color: var(--primary); box-shadow: 0 0 8px var(--glow); outline: none; }
        #editor-container { width: 100%; height: 600px; border-radius: 12px; overflow: hidden; border: 1px solid #eee; transition: box-shadow 0.3s ease; }
        #editor-container:focus-within { box-shadow: 0 0 15px var(--glow); }
        #editor { width: 100%; height: 100%; }
        #controls { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin: 1rem 0; }
        #language-select { padding: 0.75rem; border-radius: 8px; border: 1px solid #ddd; background: var(--card-bg); font-family: 'Poppins', sans-serif; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; flex: 1; }
        #language-select:focus { border-color: var(--primary); box-shadow: 0 0 8px var(--glow); outline: none; }
        #notes { width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #ddd; background: var(--card-bg); font-family: 'Poppins', sans-serif; font-size: 0.9rem; height: 150px; resize: vertical; transition: all 0.3s ease; }
        #notes:focus { border-color: var(--primary); box-shadow: 0 0 8px var(--glow); outline: none; }
        #output { padding: 1rem; border-radius: 12px; background: rgba(248, 249, 250, 0.8); color: var(--text-light); font-family: 'Courier New', monospace; font-size: 0.9rem; border: 1px solid #eee; opacity: 0; }
        #output.error { color: var(--error); background: rgba(220, 53, 69, 0.1); }
        #ai-detection { margin-top: 1rem; font-size: 0.9rem; opacity: 0; }
        button { padding: 0.75rem 1.5rem; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 500; font-size: 0.9rem; cursor: pointer; }
        button.secondary { background: var(--secondary); }
        footer {
            background: linear-gradient(135deg, var(--text-dark) 0%, var(--primary) 100%);
            color: white;
            padding: 2rem 0;
            text-align: center;
            font-size: 0.9rem;
        }
        footer a { color: var(--warning); margin: 0 0.75rem; text-decoration: none; transition: color 0.3s ease; }
        footer a:hover { color: white; }
        footer p { margin: 0.5rem 0; }
        @media (max-width: 900px) {
            .container { flex-direction: column; padding: 1rem; }
            .left-content, .right-content { gap: 1rem; }
            .chat-sidebar { width: 100%; right: -100%; }
            .chat-sidebar.open { right: 0; }
            .video-container { height: 300px; }
            .video-container.active { height: 400px; }
            #editor-container { height: 500px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/" class="navbar-brand">RookieRise Interview</a>
        <div class="navbar-nav">
            <a href="/views/dashboard.php" class="nav-link">Dashboard</a>
            <a href="#" class="nav-link">Video Feed</a>
            <span class="navbar-text">Welcome, <?php echo $user_name; ?>!</span>
            <a href="auth/logout.php" class="btn-nav">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="left-content">
            <div class="card">
                <h1>Interview Portal</h1>
                <div id="timer">Time Remaining: 30:00</div>
            </div>
            <div class="card">
                <div class="video-tabs">
                    <button class="tab-btn active" id="self-tab">Self</button>
                    <button class="tab-btn" id="remote-tab">Remote</button>
                </div>
                <div class="video-container" id="self-video-container">
                    <video id="self-video" autoplay muted></video>
                    <div class="video-controls">
                        <button class="control-btn" id="mute-audio-btn">Mute</button>
                        <button class="control-btn" id="toggle-video-btn">Video Off</button>
                    </div>
                </div>
                <div class="video-container" id="remote-video-container">
                    <img id="remote-video" class="remote-feed" alt="Remote feed">
                </div>
                <div class="status" id="video-status">Initializing...</div>
            </div>
            <div class="card" id="messages-card">
                <h2>Messages</h2>
                <div id="messages">Messages will appear here...</div>
                <button class="secondary" id="clear-messages">Clear Messages</button>
            </div>
            <div class="card" id="cheating-dashboard" style="display: none;">
                <h2>Cheating Dashboard</h2>
                <div id="cheating-stats">
                    <p>Tab Switches: <span id="tab-switches">0</span></p>
                    <p>Inactivity Periods: <span id="inactivity-periods">0</span></p>
                    <p>Pastes: <span id="pastes">0</span></p>
                    <p>Screen Shares: <span id="screen-shares">0</span></p>
                    <p>Audio Alerts: <span id="audio-alerts">0</span></p>
                    <p>No Face Detections: <span id="no-face-detections">0</span></p>
                    <p>Gaze Off-Screen: <span id="gaze-off-screen">0</span></p>
                    <p>Multiple Faces: <span id="multiple-faces">0</span></p>
                </div>
            </div>
            <div class="card">
                <h2>Notes</h2>
                <textarea id="notes" placeholder="Take notes here..."></textarea>
            </div>
        </div>
        <div class="right-content">
            <div class="card">
                <h2>Code Editor</h2>
                <div id="editor-container">
                    <div id="editor"></div>
                </div>
                <div id="controls">
                    <select id="language-select">
                        <option value="python">Python</option>
                        <option value="javascript">JavaScript</option>
                        <option value="java">Java</option>
                    </select>
                    <button id="compile-btn">Run Code</button>
                </div>
                <div id="output"></div>
                <div id="ai-detection"></div>
            </div>
        </div>
    </div>

    <div class="chat-icon" id="chat-icon"></div>
    <div class="chat-sidebar" id="chat-sidebar">
        <div class="chat-header">
            <h2>Chat</h2>
        </div>
        <div class="chat-messages" id="chat-messages"></div>
        <input type="text" id="chat-input" placeholder="Type a message...">
    </div>

    <footer>
        <p>RookieRise Interview Portal</p>
        <p>Enhancing interviews with real-time collaboration.</p>
        <div>
            <a href="#">Home</a>
            <a href="#">About Us</a>
            <a href="#">Contact</a>
            <a href="#">Privacy Policy</a>
        </div>
        <p>© 2025 RookieRise. All rights reserved.</p>
    </footer>

    <script>
        const socket = io('http://localhost:5000', {
            query: { 
                token: "<?php echo $token; ?>",
                user_id: "<?php echo $user_id; ?>",
                room_id: "<?php echo $room_id; ?>",
                role: "<?php echo $user_role; ?>",
                username: "<?php echo $user_name; ?>",
                other_username: "<?php echo $other_user_name; ?>"
            }
        });
        const userRole = "<?php echo $user_role; ?>";
        const roomId = "<?php echo $room_id; ?>";
        const username = "<?php echo $user_name; ?>";
        const otherUsername = "<?php echo $other_user_name; ?>";

        const selfVideo = document.getElementById('self-video');
        const remoteVideo = document.getElementById('remote-video');
        const selfVideoContainer = document.getElementById('self-video-container');
        const remoteVideoContainer = document.getElementById('remote-video-container');
        const videoStatus = document.getElementById('video-status');
        const muteAudioBtn = document.getElementById('mute-audio-btn');
        const toggleVideoBtn = document.getElementById('toggle-video-btn');

        let stream;
        let isAudioMuted = false;
        let isVideoEnabled = true;

        const editor = ace.edit("editor");
        editor.setTheme("ace/theme/monokai");
        editor.session.setMode("ace/mode/python");
        editor.setOptions({
            fontSize: "14px",
            showPrintMargin: false,
            enableBasicAutocompletion: true,
            enableLiveAutocompletion: true
        });

        const languageSelect = document.getElementById('language-select');
        languageSelect.addEventListener('change', () => {
            const language = languageSelect.value;
            const mode = { 'python': 'ace/mode/python', 'javascript': 'ace/mode/javascript', 'java': 'ace/mode/java' }[language];
            editor.session.setMode(mode);
        });

        gsap.from(".card", { opacity: 0, y: 50, duration: 1, stagger: 0.2, ease: "power3.out" });
        gsap.from(".navbar", { y: -50, opacity: 0, duration: 0.8, ease: "power2.out" });
        gsap.from("footer", { y: 50, opacity: 0, duration: 0.8, ease: "power2.out", delay: 0.5 });

        document.querySelectorAll('button, .nav-link, .btn-nav').forEach(btn => {
            btn.addEventListener('mouseenter', () => gsap.to(btn, { scale: 1.05, duration: 0.3, ease: "power2.out" }));
            btn.addEventListener('mouseleave', () => gsap.to(btn, { scale: 1, duration: 0.3, ease: "power2.out" }));
        });

        const chatIcon = document.getElementById('chat-icon');
        chatIcon.addEventListener('mouseenter', () => gsap.to(chatIcon, { scale: 1.1, duration: 0.3, ease: "bounce.out" }));
        chatIcon.addEventListener('mouseleave', () => gsap.to(chatIcon, { scale: 1, duration: 0.3, ease: "bounce.out" }));

        async function startVideo() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                if (!stream || !stream.getVideoTracks().length) throw new Error("No video tracks available");
                selfVideo.srcObject = stream;
                videoStatus.textContent = "Connected";
                videoStatus.className = "status connected";
                selfVideo.onloadedmetadata = () => {
                    console.log("Video metadata loaded:", selfVideo.videoWidth, "x", selfVideo.videoHeight);
                    sendVideoFrames();
                    startAudioMonitoring();
                };
            } catch (err) {
                videoStatus.textContent = `Error: ${err.message}`;
                videoStatus.className = "status error";
                console.error('Error accessing media devices:', err);
            }
        }

        function sendVideoFrames() {
            const canvas = document.createElement('canvas');
            canvas.width = 640;
            canvas.height = 480;
            const ctx = canvas.getContext('2d');

            setInterval(() => {
                if (isVideoEnabled && stream && selfVideo.videoWidth > 0) {
                    ctx.drawImage(selfVideo, 0, 0, canvas.width, canvas.height);
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
                    console.log(`Sending frame from ${userRole}: ${dataUrl.substring(0, 50)}...`);
                    socket.emit('video_frame', { role: userRole, frame: dataUrl });
                } else {
                    console.log("Skipping frame send: Video not enabled or not ready");
                }
            }, 500);
        }

        socket.on('video_frame', (data) => {
            console.log(`Received frame from ${data.role}: ${data.frame.substring(0, 50)}...`);
            if (data.role !== userRole) {
                try {
                    remoteVideo.src = data.frame;
                    console.log("Remote video src set successfully");
                } catch (err) {
                    console.error("Error setting remote video src:", err);
                    videoStatus.textContent = "Error: Remote feed failed";
                    videoStatus.className = "status error";
                }
            } else {
                console.log("Ignoring frame from self");
            }
        });

        muteAudioBtn.addEventListener('click', () => {
            isAudioMuted = !isAudioMuted;
            stream.getAudioTracks().forEach(track => track.enabled = !isAudioMuted);
            muteAudioBtn.textContent = isAudioMuted ? 'Unmute' : 'Mute';
            muteAudioBtn.classList.toggle('active', isAudioMuted);
            socket.emit('mute_audio', { role: userRole, muted: isAudioMuted });
            gsap.to(muteAudioBtn, { scale: 1.1, duration: 0.2, ease: "power2.out", yoyo: true, repeat: 1 });
        });

        toggleVideoBtn.addEventListener('click', () => {
            isVideoEnabled = !isVideoEnabled;
            stream.getVideoTracks().forEach(track => track.enabled = isVideoEnabled);
            toggleVideoBtn.textContent = isVideoEnabled ? 'Video Off' : 'Video On';
            toggleVideoBtn.classList.toggle('active', !isVideoEnabled);
            socket.emit('toggle_video', { role: userRole, enabled: isVideoEnabled });
            if (!isVideoEnabled) selfVideo.srcObject = null;
            else selfVideo.srcObject = stream;
            gsap.to(toggleVideoBtn, { scale: 1.1, duration: 0.2, ease: "power2.out", yoyo: true, repeat: 1 });
            gsap.to(selfVideoContainer, { opacity: isVideoEnabled ? 1 : 0.5, duration: 0.5, ease: "power2.out" });
        });

        const selfTab = document.getElementById('self-tab');
        const remoteTab = document.getElementById('remote-tab');

        function setActiveTab(activeTab, inactiveTab, activeContainer, inactiveContainer) {
            activeTab.classList.add('active');
            inactiveTab.classList.remove('active');
            activeContainer.style.display = 'block';
            inactiveContainer.style.display = 'none';
            console.log(`Switched to tab: ${activeTab.id}`);
            gsap.fromTo(activeContainer, { opacity: 0, scale: 0.95 }, { opacity: 1, scale: 1, duration: 0.5, ease: "power2.out" });
        }

        selfTab.addEventListener('click', () => setActiveTab(selfTab, remoteTab, selfVideoContainer, remoteVideoContainer));
        remoteTab.addEventListener('click', () => setActiveTab(remoteTab, selfTab, remoteVideoContainer, selfVideoContainer));

        selfVideoContainer.style.display = 'block';
        remoteVideoContainer.style.display = 'none';

        socket.on('connect', () => {
            console.log('Connected to Socket.IO server');
            if (userRole === 'interviewer') {
                document.getElementById('messages-card').style.display = 'block';
                document.getElementById('messages').classList.add('visible');
                document.getElementById('cheating-dashboard').style.display = 'block';
            } else {
                document.getElementById('messages-card').style.display = 'none';
            }
            socket.emit('join_role', { role: userRole, room_id: roomId });
            startVideo().catch(err => console.error('Video start failed:', err));
            setupAntiCheating();
        });

        let timeLeft = 30 * 60;
        const timerDiv = document.getElementById('timer');
        setInterval(() => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerDiv.textContent = `Time Remaining: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            timeLeft--;
            if (timeLeft < 0) timerDiv.textContent = "Interview Ended";
        }, 1000);

        const messagesDiv = document.getElementById('messages');
        socket.on('interviewer_message', (message) => {
            if (userRole === 'interviewer') {
                const timestamp = new Date().toLocaleTimeString();
                messagesDiv.textContent += `\n[${timestamp}] ${message}`;
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
                gsap.fromTo(messagesDiv.lastChild, { opacity: 0, y: 20 }, { opacity: 1, y: 0, duration: 0.5, ease: "power2.out" });
            }
        });
        document.getElementById('clear-messages').addEventListener('click', () => {
            messagesDiv.textContent = "Messages cleared.";
            gsap.from(messagesDiv, { opacity: 0, duration: 0.5, ease: "power2.out" });
        });

        socket.on('cheating_stats_update', (stats) => {
            if (userRole === 'interviewer') {
                document.getElementById('tab-switches').textContent = stats.tab_switches || 0;
                document.getElementById('inactivity-periods').textContent = stats.inactivity_periods || 0;
                document.getElementById('pastes').textContent = stats.pastes || 0;
                document.getElementById('screen-shares').textContent = stats.screen_shares || 0;
                document.getElementById('audio-alerts').textContent = stats.audio_alerts || 0;
                document.getElementById('no-face-detections').textContent = stats.no_face_detections || 0;
                document.getElementById('gaze-off-screen').textContent = stats.gaze_off_screen || 0;
                document.getElementById('multiple-faces').textContent = stats.multiple_faces || 0;
            }
        });

        const chatSidebar = document.getElementById('chat-sidebar');
        const chatMessages = document.getElementById('chat-messages');
        const chatInput = document.getElementById('chat-input');

        chatIcon.addEventListener('click', () => {
            const isOpen = chatSidebar.classList.toggle('open');
            gsap.to(chatSidebar, { x: isOpen ? 0 : 400, duration: 0.5, ease: "power3.inOut" });
        });

        function addChatMessage(user, message, timestamp) {
            const div = document.createElement('div');
            div.className = `chat-message ${user === userRole ? 'me' : ''}`;
            div.innerHTML = `
                <div class="chat-avatar">${user[0].toUpperCase()}</div>
                <div>
                    <div class="chat-bubble">${message}</div>
                    <div class="chat-timestamp">${timestamp}</div>
                </div>
            `;
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            gsap.from(div, { opacity: 0, y: 20, duration: 0.5, ease: "power2.out" });
        }

        socket.on('chat_update', (messages) => {
            chatMessages.innerHTML = '';
            messages.forEach(msg => {
                const [timestamp, userMsg] = msg.split('] ');
                const [user, message] = userMsg.split(': ');
                addChatMessage(user.slice(1), message, timestamp.slice(1));
            });
        });

        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && chatInput.value.trim()) {
                socket.emit('chat_message', { message: chatInput.value.trim(), room_id: roomId });
                chatInput.value = '';
            }
        });

        let isCodeUpdating = false;
        editor.session.on('change', () => {
            if (isCodeUpdating) return;
            const code = editor.getValue();
            const timestamp = Date.now() / 1000;
            socket.emit('code_change', { content: code, timestamp: timestamp, room_id: roomId });
        });
        socket.on('code_update', (code) => {
            if (editor.getValue() !== code) {
                isCodeUpdating = true;
                const cursor = editor.session.selection.getCursor();
                editor.setValue(code, -1);
                editor.session.selection.moveCursorTo(cursor.row, cursor.column);
                isCodeUpdating = false;
            }
        });

        const notesTextarea = document.getElementById('notes');
        let isNotesUpdating = false;
        notesTextarea.addEventListener('input', () => {
            if (isNotesUpdating) return;
            const notes = notesTextarea.value;
            const timestamp = Date.now() / 1000;
            socket.emit('notes_change', { content: notes, timestamp: timestamp, room_id: roomId });
        });
        socket.on('notes_update', (notes) => {
            if (notesTextarea.value !== notes) {
                isNotesUpdating = true;
                notesTextarea.value = notes;
                isNotesUpdating = false;
            }
        });

        document.getElementById('compile-btn').addEventListener('click', async () => {
            const code = editor.getValue();
            const language = languageSelect.value;
            const outputDiv = document.getElementById('output');
            const aiDetectionDiv = document.getElementById('ai-detection');
            outputDiv.textContent = "Running...";
            outputDiv.classList.remove('error');
            aiDetectionDiv.textContent = "Analyzing code...";

            gsap.to([outputDiv, aiDetectionDiv], { opacity: 0, duration: 0.3, ease: "power2.out" });

            try {
                const response = await fetch('/compile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code: code, language: language })
                });
                const result = await response.json();
                console.log('Compile response:', result);

                if (result.output) outputDiv.textContent = result.output;
                else if (result.error) {
                    outputDiv.textContent = result.error;
                    outputDiv.classList.add('error');
                } else outputDiv.textContent = "No output";

                if (result.ai_result) {
                    const { is_ai_generated, confidence, details } = result.ai_result;
                    aiDetectionDiv.innerHTML = `
                        <strong>AI Detection:</strong> 
                        ${is_ai_generated ? 'Likely AI-generated' : 'Likely human-written'} 
                        (Confidence: ${confidence}%)<br>
                        <small>${details.join(', ')}</small>
                    `;
                    aiDetectionDiv.style.color = is_ai_generated ? 'var(--warning)' : 'var(--success)';
                } else aiDetectionDiv.textContent = "AI detection unavailable";

                gsap.fromTo([outputDiv, aiDetectionDiv], 
                    { opacity: 0, y: 20 }, 
                    { opacity: 1, y: 0, duration: 0.5, ease: "power2.out", stagger: 0.2 }
                );
            } catch (error) {
                outputDiv.textContent = `Network error: ${error.message}`;
                outputDiv.classList.add('error');
                aiDetectionDiv.textContent = "AI detection failed due to network error";
                gsap.fromTo([outputDiv, aiDetectionDiv], 
                    { opacity: 0, y: 20 }, 
                    { opacity: 1, y: 0, duration: 0.5, ease: "power2.out", stagger: 0.2 }
                );
                console.error('Fetch error:', error);
            }
        });

        function setupAntiCheating() {
            if (userRole === 'jobseeker') {
                let lastTabSwitch = 0;
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        const now = Date.now();
                        if (now - lastTabSwitch > 5000) {
                            socket.emit('interviewer_message', "Job Seeker switched tabs or minimized window!", { room_id: roomId });
                            lastTabSwitch = now;
                        }
                    }
                });

                let lastMouseMove = Date.now();
                let inactivityTimeout;
                document.addEventListener('mousemove', (e) => {
                    const now = Date.now();
                    clearTimeout(inactivityTimeout);
                    if (now - lastMouseMove > 10000) {
                        socket.emit('interviewer_message', "Job Seeker inactive for over 10 seconds - possible external assistance!", { room_id: roomId });
                    }
                    lastMouseMove = now;
                    inactivityTimeout = setTimeout(() => {
                        socket.emit('interviewer_message', "Job Seeker inactive for over 10 seconds - possible external assistance!", { room_id: roomId });
                    }, 10000);
                });

                editor.container.addEventListener('paste', (e) => {
                    const pastedText = e.clipboardData.getData('text');
                    if (pastedText.length > 50) {
                        socket.emit('interviewer_message', `Job Seeker pasted ${pastedText.length} characters into the editor!`, { room_id: roomId });
                    }
                });

                window.addEventListener('focus', () => {
                    if (document.hasFocus() && window.innerWidth !== screen.width) {
                        socket.emit('interviewer_message', "Job Seeker's window size suggests possible screen sharing!", { room_id: roomId });
                    }
                });
            }
        }

        function startAudioMonitoring() {
            if (userRole === 'jobseeker') {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const analyser = audioContext.createAnalyser();
                const microphone = audioContext.createMediaStreamSource(stream);
                microphone.connect(analyser);
                analyser.fftSize = 2048;
                const bufferLength = analyser.frequencyBinCount;
                const dataArray = new Uint8Array(bufferLength);

                let lastAudioAlert = 0;
                const checkAudio = () => {
                    analyser.getByteFrequencyData(dataArray);
                    const average = dataArray.reduce((a, b) => a + b) / bufferLength;
                    if (average > 50 && !isAudioMuted) {
                        const now = Date.now();
                        if (now - lastAudioAlert > 10000) {
                            socket.emit('interviewer_message', "Unexpected audio activity detected from Job Seeker!", { room_id: roomId });
                            lastAudioAlert = now;
                        }
                    }
                    requestAnimationFrame(checkAudio);
                };
                checkAudio();
            }
        }
    </script>
</body>
</html>