<?php
require_once("db.php");

$token = $_GET['token'] ?? '';
$message = '';
$error = '';

// Optional: Check token validity before rendering form
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $token) {
    $stmt = $conn->prepare("SELECT 1 FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $error = "Invalid or expired reset link.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $email = $row['email'];
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE organizers SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed, $email);
            $stmt->execute();

            // Invalidate all tokens for that email
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

            $message = "✅ Password has been reset. <a href='organizer-login.php'>Login</a>";
        } else {
            $error = "Invalid or expired token.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reset Password</title>
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

    .input-wrapper {
      position: relative;
      margin-top: 5px;
    }

    input[type="password"] {
      width: 100%;
      padding: 10px 40px 10px 10px;
      box-sizing: border-box;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
    }

    .password-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 0.9rem;
      cursor: pointer;
      color: #bfa58c;
      user-select: none;
      min-width: 40px;
      text-align: right;
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
  <script>
    function togglePassword(id, toggleId) {
      const pwdInput = document.getElementById(id);
      const toggleText = document.getElementById(toggleId);
      if (pwdInput.type === "password") {
        pwdInput.type = "text";
        toggleText.textContent = "Hide";
      } else {
        pwdInput.type = "password";
        toggleText.textContent = "Show";
      }
    }
  </script>
</head>
<body>

  <form method="POST">
    <h2>Reset Password</h2>

    <?php if ($message): ?>
      <p class="message"><?= $message ?></p>
    <?php elseif ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($token && !$message && !$error): ?>
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

      <label>New Password:</label>
      <div class="input-wrapper">
        <input type="password" id="password" name="password" required>
        <span class="password-toggle" onclick="togglePassword('password', 'togglePwd1')" id="togglePwd1">Show</span>
      </div>

      <label>Confirm Password:</label>
      <div class="input-wrapper">
        <input type="password" id="confirm_password" name="confirm_password" required>
        <span class="password-toggle" onclick="togglePassword('confirm_password', 'togglePwd2')" id="togglePwd2">Show</span>
      </div>

      <button type="submit">Reset Password</button>
    <?php endif; ?>
  </form>

</body>
</html>
