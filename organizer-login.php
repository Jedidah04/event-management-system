<?php
session_start();
require_once("db.php");
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT id, password, fullname FROM organizers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($organizer = $result->fetch_assoc()) {
            if (password_verify($password, $organizer['password'])) {
                $_SESSION['organizer_id'] = $organizer['id'];
                $_SESSION['organizer_name'] = $organizer['fullname'];
                header("Location: manage-events.php");
                exit();
            } else {
                $message = "Incorrect password.";
            }
        } else {
            $message = "Organizer not found.";
        }
    } else {
        $message = "Please enter email and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Organizer Login</title>
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

  .password-toggle {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    color: #bfa58c;
    user-select: none;
    font-weight: 600;
    padding: 2px 6px;
  }

  .password-toggle:hover {
    color: #a78664;
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
</style>
<script>
  function togglePassword() {
    const pwdInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePwd');

    if (pwdInput.type === 'password') {
      pwdInput.type = 'text';
      toggleBtn.textContent = 'Hide';
    } else {
      pwdInput.type = 'password';
      toggleBtn.textContent = 'Show';
    }
  }
</script>
</head>
<body>

<form method="POST">
  <h2>Organizer Login</h2>

  <?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <label for="email">Email:</label>
  <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />

  <label for="password">Password:</label>
  <div class="password-wrapper">
    <input type="password" id="password" name="password" required />
    <button type="button" class="password-toggle" id="togglePwd" aria-label="Show password" onclick="togglePassword()">Show</button>
  </div>

  <button type="submit">Login</button>
  <div class="signup-link" style="margin-top:10px;">
  <a href="forgot-password.php">Forgot Password?</a>
   </div>
  <div class="signup-link">
    Don't have an account? <a href="organizer-signup.php">Register here</a>
  </div>
</form>

</body>
</html>
