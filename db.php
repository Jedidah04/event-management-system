<?php
$servername = "localhost";
$username = "root";
$password = ""; // or your password
$dbname = "event_management"; // replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
