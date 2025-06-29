`<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'])) {
    $role = $_POST['role'];

    if ($role === 'user') {
        header("Location: user-login.php");
        exit();
    } elseif ($role === 'organizer') {
        header("Location: organizer-login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Welcome to Event Management</title>
<style>
    /* Background image + overlay */
    body, html {
        height: 100%;
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    body {
        background: url('https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1470&q=80') no-repeat center center fixed;
        background-size: cover;
        position: relative;
        color: #f5f0e6;
    }
    /* Overlay */
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
        max-width: 500px;
        margin: 0 auto;
        padding: 40px 30px;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 15px;
        text-align: center;
        margin-top: 10vh;
        box-shadow: 0 0 25px rgba(0,0,0,0.7);
    }
    h1 {
        font-weight: 700;
        margin-bottom: 15px;
        font-size: 2.8rem;
    }
    p {
        font-size: 1.2rem;
        margin-bottom: 30px;
        line-height: 1.5;
    }
    button {
        background: #bfa58c;
        color: white;
        border: none;
        padding: 15px 30px;
        font-size: 1.2rem;
        border-radius: 10px;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    button:hover {
        background: #a78664;
    }
    .role-selection {
        margin-top: 30px;
        display: none;
    }
    .role-selection button {
        margin: 10px;
        width: 140px;
    }
</style>
<script>
    function showRoleSelection() {
        document.getElementById('lets-get-started').style.display = 'none';
        document.getElementById('role-selection').style.display = 'block';
    }
</script>
</head>
<body>

<div class="container">
    <h1>Welcome to Event Management System</h1>
    <p>Your all-in-one platform to create, manage, and attend amazing events with ease.</p>

    <button id="lets-get-started" onclick="showRoleSelection()">Let's Get Started</button>

    <form method="POST" id="role-selection" class="role-selection">
        <p>Please select your role to continue:</p>
        <button type="submit" name="role" value="user"> Attendee</button>
        <button type="submit" name="role" value="organizer">Organizer</button>
    </form>
</div>

</body>
</html>
