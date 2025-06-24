<?php
header("Content-Type: application/json");
include '../db.php';

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$role = trim($data['role'] ?? '');

if (!$name || !$email || !$password || !$role) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Check if email already exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Email already registered']);
    exit;
}

// Hash the password securely before storing
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert user with role column
$stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$name, $email, $hashedPassword, $role])) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
