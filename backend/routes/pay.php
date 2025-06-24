<?php
require_once('../controllers/PaymentController.php');

// Get data from request
$data = json_decode(file_get_contents("php://input"), true);

// Call dummy payment processor
$payment = new DummyPayment();
$result = $payment->pay($data['user_id'], $data['event_id']);

// Return result as JSON
echo json_encode($result);
