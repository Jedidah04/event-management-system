<?php
session_start();
require_once("db.php");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit();
}

// Fetch current user data
$stmt = $conn->prepare("SELECT name, email, phone, notify_email, notify_sms, notify_push, password_hash FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

$message = '';
$message_class = 'success';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notify_email = isset($_POST['notify_email']) ? 1 : 0;
    $notify_sms = isset($_POST['notify_sms']) ? 1 : 0;
    $notify_push = isset($_POST['notify_push']) ? 1 : 0;

    $updateStmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, notify_email=?, notify_sms=?, notify_push=? WHERE id=?");
    $updateStmt->bind_param("sssiiii", $name, $email, $phone, $notify_email, $notify_sms, $notify_push, $user_id);

    if ($updateStmt->execute()) {
        $message = "Profile updated successfully.";
        $user['name'] = $name;
        $user['email'] = $email;
        $user['phone'] = $phone;
        $user['notify_email'] = $notify_email;
        $user['notify_sms'] = $notify_sms;
        $user['notify_push'] = $notify_push;
    } else {
        $message = "Error updating profile.";
        $message_class = 'error';
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if ($new_pass !== $confirm_pass) {
        $message = "New passwords do not match.";
        $message_class = 'error';
    } else {
        if (password_verify($current_pass, $user['password_hash'])) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $updatePassStmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $updatePassStmt->bind_param("si", $new_hash, $user_id);

            if ($updatePassStmt->execute()) {
                $message = "Password changed successfully.";
            } else {
                $message = "Failed to change password.";
                $message_class = 'error';
            }
        } else {
            $message = "Current password is incorrect.";
            $message_class = 'error';
        }
    }
}

$user_name = $user['name'];
?>

<!DOCTYPE html>
<html>
<head>
  <title>Profile & Settings</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f7f4f0;
      margin: 0;
      padding: 40px 20px;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: flex-start;
    }

    .container {
      max-width: 600px;
      width: 100%;
      background: white;
      padding: 30px 40px;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      box-sizing: border-box;
    }

    h2 {
      color: #a78664;
      margin-bottom: 20px;
      font-weight: 700;
      font-size: 2rem;
    }

    form h3 {
      color: #a78664;
      font-weight: 600;
      margin-bottom: 15px;
      margin-top: 30px;
      font-size: 1.4rem;
    }

    label {
      display: block;
      font-weight: 600;
      margin-top: 15px;
      font-size: 1rem;
    }

    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="password"] {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
      box-sizing: border-box;
      transition: border-color 0.3s ease;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="tel"]:focus,
    input[type="password"]:focus {
      border-color: #bfa58c;
      outline: none;
    }

    .checkbox-group {
      margin-top: 15px;
    }

    .checkbox-group label {
      display: inline-block;
      margin-right: 20px;
      font-weight: 500;
      cursor: pointer;
      font-size: 0.95rem;
      color: #333;
    }

    .checkbox-group input[type="checkbox"] {
      margin-right: 6px;
      vertical-align: middle;
      cursor: pointer;
    }

    button {
      margin-top: 25px;
      padding: 12px 28px;
      font-weight: 600;
      font-size: 1rem;
      color: white;
      background-color: #bfa58c;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      box-shadow: 0 4px 6px rgba(191,165,140,0.4);
      user-select: none;
    }

    button:hover {
      background-color: #a78664;
    }

    .message {
      margin-bottom: 20px;
      font-weight: 700;
      font-size: 1rem;
      color: green;
    }

    .message.error {
      color: #c94a4a;
    }

    .nav-bar {
      margin-bottom: 30px;
      font-size: 1rem;
    }

    .nav-bar a {
      color: #a78664;
      text-decoration: none;
      font-weight: 600;
      border: 2px solid #a78664;
      padding: 8px 16px;
      border-radius: 12px;
      display: inline-block;
      transition: background-color 0.3s ease, color 0.3s ease;
      user-select: none;
    }

    .nav-bar a:hover {
      background-color: #a78664;
      color: white;
      text-decoration: none;
    }

    @media (max-width: 640px) {
      .container {
        padding: 20px;
      }
      h2 {
        font-size: 1.6rem;
      }
      form h3 {
        font-size: 1.2rem;
      }
      button {
        width: 100%;
        padding: 14px;
      }
    }
  </style>
</head>
<body>

<div class="container">
  <div class="nav-bar">
    <a href="attendee-dashboard.php">← Back to Dashboard</a>
  </div>

  <h2>Profile & Settings</h2>

  <?php if ($message): ?>
    <p class="message <?= $message_class ?>"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <form method="POST" action="">
    <h3>Update Profile</h3>

    <label for="name">Name</label>
    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($user['name']) ?>" />

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>" />

    <label for="phone">Phone</label>
    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" />

    <div class="checkbox-group">
      <label><input type="checkbox" name="notify_email" <?= $user['notify_email'] ? 'checked' : '' ?> /> Email Notifications</label>
      <label><input type="checkbox" name="notify_sms" <?= $user['notify_sms'] ? 'checked' : '' ?> /> SMS Notifications</label>
      <label><input type="checkbox" name="notify_push" <?= $user['notify_push'] ? 'checked' : '' ?> /> Push Notifications</label>
    </div>

    <button type="submit" name="update_profile">Update Profile</button>
  </form>

  <form method="POST" action="">
    <h3>Change Password</h3>

    <label for="current_password">Current Password</label>
    <input type="password" id="current_password" name="current_password" required autocomplete="off" />

    <label for="new_password">New Password</label>
    <input type="password" id="new_password" name="new_password" required autocomplete="off" />

    <label for="confirm_password">Confirm New Password</label>
    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="off" />

    <button type="submit" name="change_password">Change Password</button>
  </form>
</div>

</body>
</html>
