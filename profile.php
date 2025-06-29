<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: user-login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $area_of_interest = trim($_POST['area_of_interest'] ?? '');

    if (!$name || !$email) {
        $message = "Name and Email are required.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, age = ?, domain_of_interest = ? WHERE id = ?");
        $stmt->bind_param("ssisi", $name, $email, $age, $area_of_interest, $user_id);
        if ($stmt->execute()) {
            $message = "Profile updated successfully.";
            $_SESSION['name'] = $name; // Update session name
        } else {
            $message = "Update failed.";
        }
        $stmt->close();
    }
}

// Fetch current profile info
$stmt = $conn->prepare("SELECT name, email, age, domain_of_interest FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $age, $area_of_interest);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>My Profile</title>
</head>
<body>
<h1>My Profile</h1>
<?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="POST">
    <label>Name:<br><input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required></label><br><br>
    <label>Email:<br><input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required></label><br><br>
    <label>Age:<br><input type="number" name="age" value="<?= htmlspecialchars($age) ?>"></label><br><br>
    <label>Area of Interest:<br><input type="text" name="area_of_interest" value="<?= htmlspecialchars($area_of_interest) ?>"></label><br><br>
    <button type="submit">Update Profile</button>
</form>

<p><a href="attendee-dashboard.php">Back to Dashboard</a></p>
</body>
</html>
