<?php
require_once('../config/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];
$password = $data['password'];

// Optional: Hash the password for better security
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
if ($stmt->execute([$password, $email])) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Password reset failed']);
}
