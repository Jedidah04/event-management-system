<?php
require_once("db.php");
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

function sendPasswordResetEmail($toEmail, $resetLink) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jedidahpam04@gmail.com';
        $mail->Password = 'ludmeyodajrznhbh';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));

    if ($email) {
        $stmt = $conn->prepare("SELECT id FROM organizers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($organizer = $result->fetch_assoc()) {
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $expires);
            $stmt->execute();

            $resetLink = "http://localhost/event-management/reset-password.php?token=$token";

            if (sendPasswordResetEmail($email, $resetLink)) {
                $message = "✅ A password reset link has been sent to your email address.";
            } else {
                $message = "❌ Failed to send email. Please try again later.";
            }
        } else {
            $message = "❌ No account found with that email.";
        }
    } else {
        $message = "❌ Please enter your email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Forgot Password</title>
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

    form {
      position: relative;
      z-index: 1;
      max-width: 480px;
      margin: 60px auto;
      background: rgba(255, 255, 255, 0.95);
      padding: 30px 40px;
      border-radius: 15px;
      box-shadow: 0 0 25px rgba(0, 0, 0, 0.4);
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

    input[type="email"] {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      box-sizing: border-box;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
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
      margin-top: 20px;
      color: green;
      text-align: center;
    }

    .error {
      margin-top: 20px;
      color: red;
      text-align: center;
    }

    a {
      color: #007bff;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <form method="POST">
    <h2>Forgot Password</h2>

    <label>Email:</label>
    <input type="email" name="email" required>

    <button type="submit">Send Reset Link</button>
  </form>

  <?php if ($message): ?>
    <script>
      alert(<?= json_encode($message) ?>);
    </script>
  <?php endif; ?>

</body>
</html>
