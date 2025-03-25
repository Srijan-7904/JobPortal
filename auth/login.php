<?php
session_start();
require '../includes/db.php'; // Ensure database connection is correct

// Function to clean user input
function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
$error = ""; // Initialize error message

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = clean_input($_POST['email']);
    $password = $_POST['password']; // Don't trim passwords (may remove valid spaces)

    // ✅ Check if email and password are provided
    if (empty($email) || empty($password)) {
        $error = "❌ Please enter both email and password.";
    } else {
        // Prepare SQL statement to fetch user data
        $sql = "SELECT id, name, password, role FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            // ✅ Verify password and store session variables
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = !empty($user['role']) ? $user['role'] : 'job_seeker'; // Default role

                // Redirect users based on role
                if ($_SESSION['user_role'] == 'employer') {
                    header("Location: ../jobs/post_job.php");  // Redirect employers to job posting
                } else {
                    header("Location: ../views/dashboard.php"); // Redirect job seekers to dashboard
                }
                exit();
            } else {
                $error = "❌ Invalid email or password.";
            }
        } else {
            $error = "❌ Database query error.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animated Login Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.0/TextPlugin.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            position: relative;
            overflow: hidden;
        }

        /* Background Video Styles */
        #background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover; /* Ensures video covers the entire screen */
            z-index: -1; /* Behind all content */
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 50px;
            width: 80%;
            max-width: 1200px;
            position: relative;
            z-index: 1;
        }

        .text-container {
            width: 400px;
            color: white;
            text-align: center;
            opacity: 0;
            transform: translateX(-50px);
        }

        .text-container h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .text-container p {
            font-size: 18px;
            font-weight: 400;
            line-height: 1.6;
            min-height: 60px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .login-container {
            width: 400px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1); /* Glassmorphism effect */
            backdrop-filter: blur(15px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            opacity: 0;
            transform: scale(0.8);
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-container:hover {
            transform: scale(1.02);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        h2 {
            margin-bottom: 30px;
            margin-top: 15px;
            color: #fff;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .error-message {
            color: #ff4d4d;
            font-size: 14px;
            margin-bottom: 20px;
            background: rgba(255, 75, 75, 0.1);
            padding: 10px;
            border-radius: 5px;
            border: 1px solid rgba(255, 75, 75, 0.3);
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .input-box {
            width: 100%;
            margin-bottom: 15px;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transition: 0.3s;
            opacity: 0;
        }

        .input-box::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .input-box:focus {
            border-color: #ffd700;
            background: rgba(255, 255, 255, 0.3);
            outline: none;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: #ffd700;
            color: #333;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            opacity: 0;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .login-btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .register-link {
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        .register-link a {
            color: #ffd700;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #e6c200;
            text-decoration: underline;
        }

        .social-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
            opacity: 0;
        }

        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: 0.3s;
            color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .social-btn img {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .google-btn {
            background: #ff5959;
        }

        .google-btn:hover {
            background: #e04e4e;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .microsoft-btn {
            background: #0078d4;
        }

        .microsoft-btn:hover {
            background: #0067b8;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                width: 90%;
            }

            .text-container, .login-container {
                width: 100%;
                margin: 0;
            }

            .text-container {
                margin-bottom: 30px;
            }

            .text-container h1 {
                font-size: 28px;
            }

            .text-container p {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Background Video -->
    <video autoplay muted loop id="background-video">
        <source src="videoplayback.webm" type="video/webm">
        Your browser does not support the video tag.
    </video>
    <div class="container">
        <div class="text-container">
            <h1>Believe in Your Growth!</h1>
            <p id="dynamic-text"></p>
        </div>
        <div class="login-container">
            <h2>Login</h2>
            <?php if (!empty($error)) { ?>
                <p class="error-message"><?php echo $error; ?></p>
            <?php } ?>
            <form method="POST">
                <input type="email" name="email" class="input-box" required placeholder="Email">
                <input type="password" name="password" class="input-box" required placeholder="Password">
                <button type="submit" class="login-btn">Login</button>
            </form>
            <p class="register-link">Don't have an account? <a href="register.php">Register here</a></p>
            <div class="social-buttons">
                <button class="social-btn google-btn" onclick="googleLogin()">
                    <img src="https://img.icons8.com/?size=100&id=17949&format=png&color=000000" alt="Google Logo" />
                    Sign in with Google
                </button>
                <button class="social-btn microsoft-btn" onclick="microsoftLogin()">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" alt="Microsoft Logo" />
                    Sign in with Microsoft
                </button>
            </div>
        </div>
    </div>
    <script>
        gsap.registerPlugin(TextPlugin);

        // Typewriter Effect for Quotes
        const quotes = [
            "💪 Believe in Yourself! Hard work and dedication will lead to success...🚀",
            "📚 Keep Learning, Keep Growing! Knowledge is the key to unlocking your potential...🔑",
            "🔥 Success Comes from Consistency! Small daily improvements lead to stunning results...🌟",
            "🌍 Your Future is in Your Hands! Every decision you make shapes your tomorrow... ✨",
            "🌠 Dream Big, Work Hard! Your efforts today will define your achievements tomorrow...🏆",
            "🚧 Failure is Just a Stepping Stone! Learn from it and keep moving forward...💡",
            "🎯 Stay Focused, Stay Determined! Success is closer than you think...🏅"
        ];

        let quoteIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        const dynamicText = document.querySelector("#dynamic-text");

        function typeEffect() {
            let currentQuote = quotes[quoteIndex];
            
            if (isDeleting) {
                dynamicText.textContent = currentQuote.substring(0, charIndex--);
            } else {
                dynamicText.textContent = currentQuote.substring(0, charIndex++);
            }

            let typingSpeed = isDeleting ? 50 : 100; // Erase faster, type slower

            if (!isDeleting && charIndex === currentQuote.length + 1) {
                isDeleting = true;
                typingSpeed = 1500; // Pause before erasing
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                quoteIndex = (quoteIndex + 1) % quotes.length;
                typingSpeed = 500; // Pause before typing new text
            }

            setTimeout(typeEffect, typingSpeed);
        }

        // GSAP Animations
        document.addEventListener("DOMContentLoaded", () => {
            // Text Container Animation
            gsap.to(".text-container", {
                opacity: 1,
                x: 0,
                duration: 1.5,
                ease: "power2.out"
            });

            gsap.to(".text-container h1", {
                opacity: 1,
                y: 0,
                duration: 1,
                delay: 0.5,
                ease: "back.out(1.7)"
            });

            // Start typewriter effect after the text container is visible
            setTimeout(typeEffect, 1000);

            // Login Container Animation
            gsap.to(".login-container", {
                opacity: 1,
                scale: 1,
                duration: 1,
                delay: 0.5,
                ease: "elastic.out(1, 0.5)"
            });

            // Form Elements Animation
            gsap.to(".input-box, .login-btn, .social-buttons", {
                opacity: 1,
                y: 0,
                duration: 0.8,
                stagger: 0.2,
                delay: 1,
                ease: "power2.out"
            });

            // Error Message Animation (if present)
            if (document.querySelector(".error-message")) {
                gsap.from(".error-message", {
                    opacity: 0,
                    y: 20,
                    duration: 0.5,
                    ease: "power2.out"
                });
            }
        });

        function googleLogin() {
            window.location.href = "google-login.php";
        }

        function microsoftLogin() {
            window.location.href = "microsoft-login.php";
        }
    </script>
</body>
</html>