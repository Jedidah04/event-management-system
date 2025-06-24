<?php
require_once('../config/db.php');

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Email not found']);
}
