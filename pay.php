<?php
require_once("config.php"); // Load environment variables
require_once("db.php");

$user_id = $_GET['user_id'] ?? 0;
$event_id = $_GET['event_id'] ?? 0;
$amount = $_GET['amount'] ?? 0;

if (!$user_id || !$event_id || !$amount) {
    die("Error: Missing user_id, event_id, or amount.");
}

// Safely get the Flutterwave public key from environment variables
$public_key = $_ENV['FLW_PUBLIC_KEY'] ?? null;
if (!$public_key) {
    die('Flutterwave public key not set. Please check your .env file and config.php.');
}

// Generate a unique transaction reference
$tx_ref = 'EVT_' . uniqid();

// Fetch user email
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$email = $user['email'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Flutterwave Payment</title>
    <script src="https://checkout.flutterwave.com/v3.js"></script>
</head>
<body>

<h3>Redirecting to payment...</h3>

<script>
FlutterwaveCheckout({
    public_key: "<?= htmlspecialchars($public_key) ?>",
    tx_ref: "<?= htmlspecialchars($tx_ref) ?>",
    amount: <?= htmlspecialchars($amount) ?>,
    currency: "NGN",
    payment_options: "card,ussd,banktransfer",
    customer: {
        email: "<?= htmlspecialchars($email) ?>",
    },
    customizations: {
        title: "Event Payment",
        description: "Payment for event ID <?= htmlspecialchars($event_id) ?>",
        logo: "https://yourwebsite.com/logo.png",
    },
    callback: function (data) {
        window.location.href = "verify.php?tx_ref=" + data.tx_ref + "&event_id=<?= $event_id ?>&user_id=<?= $user_id ?>&amount=<?= $amount ?>";
    },
    onclose: function() {
        alert("Payment window closed.");
    }
});
</script>

</body>
</html>
