<?php
session_start();
require_once("db.php");

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $age = intval($_POST['age'] ?? 0);
    $area_of_interest = trim($_POST['area_of_interest'] ?? '');

    if (!$name || !$email || !$password || !$confirm_password || !$age || !$area_of_interest) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif ($age < 1 || $age > 120) {
        $message = "Please enter a valid age.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Email is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, age, domain_of_interest) VALUES (?, ?, ?, 'user', ?, ?)");
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("sssis", $name, $email, $hashed_password, $age, $area_of_interest);

            if ($stmt->execute()) {
                header("Location: user-login.php?registered=1");
                exit();
            } else {
                $message = "Error occurred. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Attendee Signup</title>
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

  input, select {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
  }

  button {
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

  button:hover {
    background: #a78664;
  }

  .message {
    color: red;
    margin-bottom: 15px;
    font-weight: 600;
    text-align: center;
  }

  .login-link {
    margin-top: 15px;
    text-align: center;
    font-size: 0.9rem;
    color: #5a4c3c;
  }

  .login-link a {
    color: #bfa58c;
    text-decoration: none;
    font-weight: 600;
  }

  .login-link a:hover {
    text-decoration: underline;
  }

  .password-toggle {
    margin-top: 5px;
    font-size: 0.9rem;
    cursor: pointer;
    color: #bfa58c;
    user-select: none;
    display: inline-block;
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

<form method="POST" action="">
    <h2>Attendee Signup</h2>
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <label for="name">Full Name:</label>
    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" />

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required autocomplete="new-password" />
    <span class="password-toggle" onclick="togglePassword('password', 'togglePwd1')" id="togglePwd1">Show</span>

    <label for="confirm_password">Confirm Password:</label>
    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" />
    <span class="password-toggle" onclick="togglePassword('confirm_password', 'togglePwd2')" id="togglePwd2">Show</span>

    <label for="age">Age:</label>
    <input type="number" id="age" name="age" min="1" max="120" required value="<?= htmlspecialchars($_POST['age'] ?? '') ?>" />

    <label for="area_of_interest">Area of Interest:</label>
    <input type="text" id="area_of_interest" name="area_of_interest" required value="<?= htmlspecialchars($_POST['area_of_interest'] ?? '') ?>" />

    <button type="submit">Sign Up</button>

    <div class="login-link">
        Already have an account? <a href="user-login.php">Login here</a>
    </div>
</form>

</body>
</html>
