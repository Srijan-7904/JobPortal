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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/TextPlugin.min.js"></script>
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
        background: linear-gradient(
            135deg,
            rgba(102, 126, 234, 0.8),
            rgba(118, 75, 162, 0.8)
          ),
          url("https://img.freepik.com/free-vector/geometric-gradient-futuristic-background_23-2149116406.jpg?t=st=1739959299~exp=1739962899~hmac=6e7276f197a3a57403675da4c699c2c832019d6e3584f92bf0b6479c3c776115&w=1060");
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
      }

      .container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 50px;
        width: 80%;
      }

      .text-container {
        width: 400px;
        color: white;
        text-align: center;
        opacity: 0;
      }

      .text-container h1 {
        font-size: 30px;
        font-weight: 900;
        margin-bottom: 10px;
      }

      .text-container p {
        font-size: 16px;
        font-weight: 500;
        line-height: 1.5;
        min-height: 50px;
      }

      .login-container {
        width: 400px;
        padding: 40px;
        background-color: white;
        box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
        border-radius: 10px;
        text-align: center;
        margin-left: 80px;
        opacity: 0;
        transform: scale(0.8);
        position: relative;
      }

      h2 {
        margin-bottom: 30px;
        margin-top: 15px;
        color: #333;
      }

      form {
        display: flex;
        flex-direction: column;
        align-items: center;
      }

      .input-box {
        width: 100%;
        margin-bottom: 12px;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 14px;
        transition: 0.3s;
        opacity: 0;
      }

      .input-box:focus {
        border-color: #667eea;
        outline: none;
      }

      .login-btn {
        width: 100%;
        padding: 12px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        cursor: pointer;
        transition: 0.3s;
        opacity: 0;
        margin-top: 25px;
      }

      .login-btn:hover {
        background: #5a67d8;
      }

      .social-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 15px;
        opacity: 0;
      }

      .social-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 10px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: 0.3s;
        color: white;
      }

      .social-btn img {
        width: 20px;
        height: 20px;
        margin-right: 10px;
      }

      .google-btn {
        background: #ff5959;
      }

      .microsoft-btn {
        background: #0078d4;
      }

      .social-btn:hover {
        opacity: 0.8;
      }
    </style>
</head>
<body>
    <div class="container">
    <div class="text-container">
        <h1>Believe in Your Growth!</h1>
        <p id="dynamic-text"></p>
      </div>
        <div class="login-container">
            <h2>Login</h2>
            <?php if (!empty($error)) { ?>
                <p style="color: red;"> <?php echo $error; ?> </p>
            <?php } ?>
            <form method="POST">
                <input type="email" name="email" class="input-box" required placeholder="Email">
                <input type="password" name="password" class="input-box" required placeholder="Password">
                <button type="submit" class="login-btn">Login</button>
            </form>
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <div class="social-buttons">
          <button class="social-btn google-btn" onclick="googleLogin()">
            <img
              src="https://img.icons8.com/?size=100&id=17949&format=png&color=000000"
              alt="Google Logo"
            />
            Sign in with Google
          </button>
          <button class="social-btn microsoft-btn" onclick="microsoftLogin()">
            <img
              src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg"
              alt="Microsoft Logo"
            />
            Sign in with Microsoft
          </button>
        </div>
        </div>
    </div>
    <script>
      gsap.registerPlugin(TextPlugin);

      // Typewriter Effect
      const quotes = [
  "💪 Believe in Yourself! Hard work and dedication will lead to success...🚀",
  "📚 Keep Learning, Keep Growing! Knowledge is the key to unlocking your potential...🔑",
  "🔥 Success Comes from Consistency! Small daily improvements lead to stunning results...🌟",
  "🌍 Your Future is in Your Hands! Every decision you make shapes your tomorrow... ✨",
  "🌠 Dream Big, Work Hard! Your efforts today will define your achievements tomorrow...🏆",
  "🚧 Failure is Just a Stepping Stone! Learn from it and keep moving forward...💡",
  "🎯 Stay Focused, Stay Determined! Success is closer than you think...🏅"
];


let textElement = document.querySelector(".text-container h1");
let quoteIndex = 0;
let charIndex = 0;
let isDeleting = false;

function typeEffect() {
  let currentQuote = quotes[quoteIndex];
  
  if (isDeleting) {
    textElement.textContent = currentQuote.substring(0, charIndex--);
  } else {
    textElement.textContent = currentQuote.substring(0, charIndex++);
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

// Start the typewriter effect after the page loads
document.addEventListener("DOMContentLoaded", () => {
  setTimeout(typeEffect, 1000);
});


      // GSAP Animations for UI
      gsap.to(".text-container", { opacity: 1, x: 0, duration: 2 });

      gsap.to(".login-container", { opacity: 1, scale: 1, duration: 1 });

      gsap.to(".input-box, .login-btn, .social-buttons", {
        opacity: 1,
        duration: 1,
        stagger: 0.3,
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
