<?php
require_once('../config/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];
$password = $data['password'];

// Get user by email
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $password === $user['password']) {
    // Normally you'd hash passwords with password_verify()
    echo json_encode(['status' => 'success', 'user_id' => $user['id']]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
}
