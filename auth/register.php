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
    $name = isset($_POST['name']) ? clean_input($_POST['name']) : "";
    $role = isset($_POST['role']) ? clean_input($_POST['role']) : "";

    // ✅ Check if all required fields are provided
    if (empty($email) || empty($password) || empty($name)) {
        $error = "❌ Please enter name, email, and password.";
    } else {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "❌ Invalid email format.";
        } else {
            // Check if email already exists
            $check_email = "SELECT id FROM users WHERE email = ?";
            $stmt_check = $conn->prepare($check_email);
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $error = "❌ Email already registered. Try logging in.";
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                // Insert new user
                $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $name, $email, $password_hash, $role);

                if ($stmt->execute()) {
                    // Ensure session is set before redirecting
                    $_SESSION['user_id'] = $stmt->insert_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = $role;

                    // Redirect to login page after successful registration
                    header("Location: ../auth/login.php");
                    exit();
                } else {
                    $error = "❌ Error: " . $conn->error;
                }
            }
            $stmt_check->close();
            if (isset($stmt)) $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
            z-index: -2; /* Behind all content */
        }

        /* Overlay with theme color #0a7ab8 and reduced opacity */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 122, 184, 0.3); /* #0a7ab8 with 0.3 opacity */
            z-index: -1; /* Above video, below content */
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

        .register-container {
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

        .register-container:hover {
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

        select.input-box {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background: rgba(255, 255, 255, 0.2) url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.7)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") no-repeat right 10px center;
            background-size: 16px;
            padding-right: 30px;
        }

        select.input-box:focus {
            border-color: #ffd700;
            background: rgba(255, 255, 255, 0.3) url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffd700' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") no-repeat right 10px center;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }

        .register-btn {
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

        .register-btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .login-link {
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        .login-link a {
            color: #ffd700;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #e6c200;
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                width: 90%;
            }

            .text-container, .register-container {
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
        <div class="register-container">
            <h2>Register</h2>
            <?php if (!empty($error)) { ?>
                <p class="error-message"><?php echo $error; ?></p>
            <?php } ?>
            <form method="POST">
                <input type="text" name="name" class="input-box" required placeholder="Name">
                <input type="email" name="email" class="input-box" required placeholder="Email">
                <input type="password" name="password" class="input-box" required placeholder="Password">
                <select name="role" class="input-box" required>
                    <option value="" disabled selected>Select Role</option>
                    <option value="employer">Employer</option>
                    <option value="jobseeker">Job Seeker</option>
                </select>
                <button type="submit" class="register-btn">Register</button>
            </form>
            <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
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

            // Register Container Animation
            gsap.to(".register-container", {
                opacity: 1,
                scale: 1,
                duration: 1,
                delay: 0.5,
                ease: "elastic.out(1, 0.5)"
            });

            // Form Elements Animation
            gsap.to(".input-box, .register-btn", {
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
    </script>
</body>
</html>