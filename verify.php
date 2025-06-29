<?php
require_once("config.php");
require_once("db.php");

$secret_key = $_ENV['FLW_SECRET_KEY'];

$tx_ref = $_GET['tx_ref'] ?? '';
$event_id = $_GET['event_id'] ?? 0;
$user_id = $_GET['user_id'] ?? 0;
$amount = $_GET['amount'] ?? 0;

if (!$tx_ref || !$event_id || !$user_id || !$amount) {
    die("Invalid payment verification request.");
}

$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=" . urlencode($tx_ref),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer $secret_key"
  ),
));

$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response, true);

if ($result['status'] === 'success' && $result['data']['status'] === 'successful') {
    $flw_ref = $result['data']['flw_ref'];
    $status = 'Paid';

    $stmt = $conn->prepare("INSERT INTO payments (user_id, event_id, amount, tx_ref, flw_ref, payment_status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iidsss", $user_id, $event_id, $amount, $tx_ref, $flw_ref, $status);
    $stmt->execute();

    echo "<h3>Payment Successful! You can now download your ticket.</h3>";
} else {
    echo "<h3>Payment Failed or Cancelled.</h3>";
}
if ($stmt->execute()) {
    echo "<h3>Payment Successful! You can now download your ticket.</h3>";
} else {
    echo "<h3>Database error: " . htmlspecialchars($stmt->error) . "</h3>";
}

?>
