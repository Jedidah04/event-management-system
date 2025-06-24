<?php
$host = 'localhost';
$db = 'event_db'; // This is your database name
$user = 'root';   // Default username for XAMPP
$pass = '';       // Leave blank (default for XAMPP)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected successfully!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>

