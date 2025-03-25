<?php
session_start();

// Adjust path based on your structure (fixed from previous error)
require './includes/db.php'; // Assumes db.php is in JobPortal/includes/

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Generate or fetch a unique room ID
$room_id = isset($_GET['room']) ? $_GET['room'] : bin2hex(random_bytes(8));

// Log session (simplified)
$sql = "INSERT IGNORE INTO interview_sessions (room_id, user1_id, user2_id) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error); // Debug if query fails
}
$interviewer_id = $_SESSION['user_role'] === 'employer' ? $_SESSION['user_id'] : null;
$fresher_id = $_SESSION['user_role'] === 'job_seeker' ? $_SESSION['user_id'] : null;
$stmt->bind_param("sii", $room_id, $interviewer_id, $fresher_id);
$stmt->execute();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview & Coding Environment</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.js"></script>
    <!-- Agora SDK for video calls -->
    <script src="https://cdn.agora.io/sdk/release/AgoraRTC_N.js"></script>
    <!-- Agora RTM SDK for real-time messaging (code syncing) -->
    <script src="https://download.agora.io/sdk/release/AgoraRTM_SDK_WEB-1.7.1.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            position: relative;
            overflow: hidden;
        }

        #background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .container {
            display: flex;
            width: 90%;
            max-width: 1200px;
            gap: 20px;
            padding: 20px;
            z-index: 1;
        }

        .video-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .video-container {
            width: 100%;
            height: 300px;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
        }

        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .code-section {
            flex: 1;
            height: 500px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        #editor {
            width: 100%;
            height: 100%;
            border-radius: 10px;
        }

        .controls {
            margin-top: 10px;
            text-align: center;
        }

        .btn {
            padding: 10px 20px;
            background: #ffd700;
            color: #333;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .video-container, .code-section {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="video-section">
            <div class="video-container" id="local-video"></div>
            <div class="video-container" id="remote-video"></div>
            <div class="controls">
                <button class="btn" id="leave-btn">Leave Call</button>
            </div>
        </div>
        <div class="code-section">
            <div id="editor"></div>
        </div>
    </div>

    <script>
        const roomId = "<?php echo $room_id; ?>";
        const userId = "<?php echo $_SESSION['user_id']; ?>";
        const appId = "bf964ee7b86d45698c9dff6cb9e91726"; // Use the App ID from your web project

        console.log('Joining room:', roomId);
        console.log('AgoraRTM:', typeof AgoraRTM); // Debug RTM SDK loading

        // Function to fetch token dynamically
        async function getToken(channel, uid) {
            try {
                const response = await fetch(`token.php?channel=${channel}&uid=${uid}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                const data = await response.text(); // token.php returns a string
                return data;
            } catch (err) {
                console.error('Failed to fetch token:', err);
                return null; // Fallback to null for testing
            }
        }

        // Initialize Agora RTC client for video calls
        const rtcClient = AgoraRTC.createClient({
            mode: "rtc",
            codec: "vp8",
            iceTransportPolicy: "all",
            iceServer: [
                { urls: "stun:stun.l.google.com:19302" }
            ]
        });

        // Initialize Agora RTM client for code syncing
        const rtmClient = AgoraRTM.createInstance(appId);
        let rtmChannel;

        // Initialize Ace Editor
        const editor = ace.edit("editor");
        editor.setTheme("ace/theme/monokai");
        editor.session.setMode("ace/mode/javascript");
        editor.setValue("// Start coding here\n");

        let localStream;

        // Join the video call
        async function joinVideoCall() {
            try {
                // Use the temporary token from the Agora Console for testing
                const token = "YOUR_TEMP_TOKEN"; // Replace with the token from the Agora Console
                // Alternatively, use getToken once token.php is set up:
                // const token = await getToken(roomId, userId) || null;
                console.log('Attempting to join RTC channel with token:', token);

                // Join the RTC channel
                await rtcClient.join(appId, roomId, token, userId);
                console.log('Joined RTC channel:', roomId);

                // Create and publish local stream
                console.log('Requesting media access...');
                localStream = await AgoraRTC.createStream({
                    video: true,
                    audio: true
                });
                console.log('Media stream created, initializing...');
                await localStream.init();
                console.log('Local stream initialized');

                // Play local stream
                const localVideo = document.createElement('div');
                localVideo.id = `player-${userId}`;
                document.getElementById('local-video').appendChild(localVideo);
                localStream.play(localVideo.id);
                console.log('Local stream playing');

                // Publish local stream to the channel
                await rtcClient.publish(localStream);
                console.log('Local stream published');

                // Subscribe to remote streams
                rtcClient.on('stream-added', (evt) => {
                    const remoteStream = evt.stream;
                    console.log('Remote stream added:', remoteStream.getId());
                    rtcClient.subscribe(remoteStream);
                });

                rtcClient.on('stream-subscribed', (evt) => {
                    const remoteStream = evt.stream;
                    console.log('Remote stream subscribed:', remoteStream.getId());
                    const remoteVideo = document.createElement('div');
                    remoteVideo.id = `player-${remoteStream.getId()}`;
                    document.getElementById('remote-video').appendChild(remoteVideo);
                    remoteStream.play(remoteVideo.id);
                });

                rtcClient.on('peer-leave', (evt) => {
                    const userId = evt.uid;
                    console.log('User disconnected:', userId);
                    const player = document.getElementById(`player-${userId}`);
                    if (player) player.remove();
                });

                // Add WebRTC connection debugging
                rtcClient.on('connection-state-change', (state) => {
                    console.log('Connection state changed to:', state);
                });
            } catch (err) {
                console.error('Video call error:', err.message, err);
                if (err.code) {
                    console.error('Agora error code:', err.code);
                }
            }
        }

        // Join the RTM channel for code syncing
        async function joinRtmChannel() {
            try {
                // Use the temporary token from the Agora Console for testing
                const token = "YOUR_TEMP_TOKEN"; // Replace with the token from the Agora Console
                // Alternatively, use getToken once token.php is set up:
                // const token = await getToken(roomId, userId) || null;
                console.log('Attempting RTM login with token:', token);

                // Login to RTM
                await rtmClient.login({ uid: userId, token: token });
                console.log('RTM login successful');

                // Join the RTM channel
                rtmChannel = rtmClient.createChannel(roomId);
                await rtmChannel.join();
                console.log('Joined RTM channel:', roomId);

                // Handle messages (code updates)
                rtmChannel.on('ChannelMessage', (message, senderId) => {
                    const code = message.text;
                    console.log('Received code update from', senderId, ':', code);
                    if (code !== editor.getValue()) {
                        editor.setValue(code, -1);
                    }
                });

                rtmChannel.on('MemberLeft', (memberId) => {
                    console.log('RTM member left:', memberId);
                });

                // Add RTM connection debugging
                rtmClient.on('ConnectionStateChanged', (state, reason) => {
                    console.log('RTM connection state changed to:', state, 'Reason:', reason);
                });
            } catch (err) {
                console.error('RTM error:', err.message, err);
            }
        }

        // Code syncing
        let lastCode = editor.getValue();
        editor.session.on('change', () => {
            const newCode = editor.getValue();
            if (newCode !== lastCode) {
                lastCode = newCode;
                if (rtmChannel) {
                    rtmChannel.sendMessage({ text: newCode })
                        .then(() => console.log('Code update sent:', newCode))
                        .catch(err => console.error('Failed to send code update:', err));
                }
            }
        });

        // Leave call
        document.getElementById('leave-btn').addEventListener('click', async () => {
            if (localStream) {
                localStream.close();
            }
            await rtcClient.leave();
            await rtmChannel.leave();
            await rtmClient.logout();
            document.getElementById('local-video').innerHTML = '';
            document.getElementById('remote-video').innerHTML = '';
        });

        // Start the video call and RTM
        joinVideoCall();
        joinRtmChannel();

        // GSAP animation
        gsap.from(".container", {
            opacity: 0,
            y: 50,
            duration: 1,
            ease: "power2.out"
        });
    </script>
</body>
</html>