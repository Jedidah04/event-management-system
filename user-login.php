<?php
session_start();
require_once("db.php");
require 'vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$loginMessage = '';
$resetMessage = '';

// --- Handle login ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT id, password, name FROM users WHERE email = ? AND role = 'user'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = 'user';
                header("Location: attendee-dashboard.php");
                exit();
            } else {
                $loginMessage = "Incorrect password.";
            }
        } else {
            $loginMessage = "No user found with that email.";
        }
    } else {
        $loginMessage = "Please enter email and password.";
    }
}

// --- Handle forgot password ---
function sendPasswordResetEmail($toEmail, $resetLink) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jedidahpam04@gmail.com';     // your Gmail
        $mail->Password = 'ludmeyodajrznhbh';           // app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('jedidahpam04@gmail.com', 'Event Management');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body = "Hi,<br><br>You requested a password reset. Click the link below to reset your password:<br><a href=\"$resetLink\">$resetLink</a><br><br>If you did not request this, please ignore this email.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_submit'])) {
    $resetEmail = trim(strtolower($_POST['reset_email'] ?? ''));

    if ($resetEmail) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'user'");
        $stmt->bind_param("s", $resetEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // Remove old tokens for this email
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $resetEmail);
            $stmt->execute();

            // Insert new token
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $resetEmail, $token, $expires);
            $stmt->execute();

            $resetLink = "http://localhost/event-management/reset-password.php?token=$token";

            if (sendPasswordResetEmail($resetEmail, $resetLink)) {
                $resetMessage = "✅ A password reset link has been sent to your email address.";
            } else {
                $resetMessage = "❌ Failed to send email. Please try again later.";
            }
        } else {
            $resetMessage = "❌ No user found with that email.";
        }
    } else {
        $resetMessage = "❌ Please enter your email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Attendee Login</title>
<style>
  body, html {
    height: 100%;
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: url('https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1470&q=80') no-repeat center center fixed;
    background-size: cover;
    position: relative;
    color: #333;
  }

  body::before {
    content: "";
    position: fixed;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    background: rgba(30, 20, 10, 0.65);
    z-index: -1;
  }

  .container {
    max-width: 480px;
    margin: 60px auto;
    background: rgba(255, 255, 255, 0.95);
    padding: 30px 40px;
    border-radius: 15px;
    box-shadow: 0 0 25px rgba(0, 0, 0, 0.4);
    position: relative;
    z-index: 1;
  }

  h2 {
    text-align: center;
    color: #5a4c3c;
    margin-bottom: 25px;
    font-size: 2rem;
  }

  label {
    display: block;
    margin-top: 12px;
    color: #5a4c3c;
    font-weight: 600;
  }

  input[type="email"],
  input[type="password"] {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
  }

  .password-wrapper {
    position: relative;
  }

  .password-wrapper input {
    width: 100%;
    box-sizing: border-box;
    padding-right: 40px;
    font-family: inherit;
    font-size: 1rem;
    letter-spacing: normal;
    line-height: 1.2;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
  }

  .password-toggle {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    color: #5a4c3c;
    user-select: none;
  }

  button[type="submit"] {
    margin-top: 20px;
    padding: 12px;
    background: #bfa58c;
    border: none;
    color: white;
    cursor: pointer;
    border-radius: 10px;
    font-size: 1.1rem;
    width: 100%;
    font-weight: 600;
    transition: background 0.3s ease;
  }

  button[type="submit"]:hover {
    background: #a78664;
  }

  .message {
    color: red;
    margin-bottom: 15px;
    font-weight: 600;
    text-align: center;
  }

  .signup-link {
    margin-top: 15px;
    text-align: center;
    font-size: 0.9rem;
    color: #5a4c3c;
  }

  .signup-link a {
    color: #bfa58c;
    text-decoration: none;
    font-weight: 600;
  }

  .signup-link a:hover {
    text-decoration: underline;
  }

  /* Forgot password link */
  .forgot-password-link {
    margin-top: 10px;
    text-align: center;
    color:#5a4c3c
  }

  .forgot-password-link button {
    background: none;
    border: none;
    color: #5a4c3c;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
    font-size: 0.9rem;
    text-decoration: underline;
  }

  /* Forgot password form */
  #forgot-password-form {
    display: none;
    margin-top: 30px;
  }

  #forgot-password-form .message {
    color: green;
    font-weight: 600;
  }
</style>
<script>
  function togglePassword() {
    const pwdInput = document.getElementById('password');
    pwdInput.type = pwdInput.type === 'password' ? 'text' : 'password';
  }

  function showForgotPassword() {
    document.getElementById('forgot-password-form').style.display = 'block';
    document.getElementById('login-form').style.display = 'none';
  }
</script>
</head>
<body>

<div class="container">

  <!-- LOGIN FORM -->
  <form id="login-form" method="POST" action="">
    <h2>Attendee Login</h2>

    <?php if ($loginMessage): ?>
      <div class="message"><?= htmlspecialchars($loginMessage) ?></div>
    <?php endif; ?>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />

    <label for="password">Password:</label>
    <div class="password-wrapper">
      <input type="password" id="password" name="password" required />
      <button type="button" class="password-toggle" aria-label="Toggle Password Visibility" onclick="togglePassword()">👁️</button>
    </div>

    <button type="submit" name="login_submit">Login</button>

    <div class="forgot-password-link">
      <button type="button" onclick="showForgotPassword()">Forgot password?</button>
    </div>

    <div class="signup-link">
      Don't have an account? <a href="user-signup.php">Sign up here</a>
    </div>
  </form>

  <!-- FORGOT PASSWORD FORM -->
  <form id="forgot-password-form" method="POST" action="">
    <h2>Forgot Password</h2>

    <?php if ($resetMessage): ?>
      <div class="message"><?= htmlspecialchars($resetMessage) ?></div>
      <script>
        alert(<?= json_encode($resetMessage) ?>);
      </script>
    <?php endif; ?>

    <label for="reset_email">Email:</label>
    <input type="email" id="reset_email" name="reset_email" required />

    <button type="submit" name="reset_submit">Send Reset Link</button>
  </form>

</div>

</body>
</html>
