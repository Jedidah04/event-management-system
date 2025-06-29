<?php
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to homepage or login page
header("Location: index.php");
exit();
?>
