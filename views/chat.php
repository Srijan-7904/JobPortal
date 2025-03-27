<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
$user_role = $_SESSION['user_role'];
$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'User', ENT_QUOTES, 'UTF-8');

$other_user_id = filter_var($_GET['with'] ?? 0, FILTER_VALIDATE_INT);
if (!$other_user_id) {
    header("Location: dashboard.php");
    exit();
}

$stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id = ?");
$stmt->bind_param("i", $other_user_id);
$stmt->execute();
$result = $stmt->get_result();
$other_user = $result->fetch_assoc();
$stmt->close();

if (!$other_user) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat | Job Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
        }
        #talkjs-container {
            width: 100%;
            height: 500px;
            border: 1px solid #ddd;
            border-radius: 10px;
        }
        .error-message {
            text-align: center;
            padding: 20px;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Chat with <?php echo htmlspecialchars($other_user['name']); ?></h2>
        <div id="talkjs-container">
            <div class="text-center p-4">
                <p>Loading chat...</p>
            </div>
        </div>
        <div class="mt-3 text-center">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <script>
        (function(t,a,l,k,j,s){
        s=a.createElement('script');s.async=1;s.src='https://cdn.talkjs.com/talk.js';a.head.appendChild(s)
        ;k=t.Promise;t.Talk={v:3,ready:{then:function(f){if(k)return new k(function(r,e){l.push([f,r,e])});l
        .push([f])},catch:function(){return k&&new k()},c:l}};})(window,document,[]);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        Talk.ready.then(function() {
            console.log("TalkJS is ready, initializing users...");
            const me = new Talk.User({
                id: "<?php echo $user_id; ?>",
                name: "<?php echo htmlspecialchars($user_name); ?>",
                role: "<?php echo $user_role; ?>",
                welcomeMessage: "Hi!"
            });

            const other = new Talk.User({
                id: "<?php echo $other_user['id']; ?>",
                name: "<?php echo htmlspecialchars($other_user['name']); ?>",
                role: "<?php echo $other_user['role']; ?>",
                welcomeMessage: "Hey, how can I help?"
            });

            console.log("Users initialized:", { me: me, other: other });

            const session = new Talk.Session({
                appId: "tkr3cqVc",
                me: me
            });

            console.log("Session created, creating conversation...");

            const conversation = session.getOrCreateConversation(
                Talk.oneOnOneId(me, other)
            );
            console.log("Conversation ID in chat.php:", Talk.oneOnOneId(me, other));
            conversation.setParticipant(me);
            conversation.setParticipant(other);

            console.log("Conversation created:", conversation.id);

            const chatbox = session.createChatbox();
            chatbox.select(conversation);

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