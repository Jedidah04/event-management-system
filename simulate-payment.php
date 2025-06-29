<?php
require_once("db.php");

// Get and validate inputs from URL
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0.0;

// Validate all required fields
if ($user_id <= 0 || $event_id <= 0 || $amount <= 0) {
    die("<div style='color:red; font-weight:bold;'>Error: Missing or invalid user_id, event_id, or amount.</div>");
}

// Simulate a successful payment
$payment_status = 'Paid';

// Insert payment record
$stmt = $conn->prepare("INSERT INTO payments (user_id, event_id, amount, payment_status) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iids", $user_id, $event_id, $amount, $payment_status);
$insert_success = $stmt->execute();
$error_msg = !$insert_success ? $stmt->error : '';
$stmt->close();

// Fetch payment history
$stmt = $conn->prepare("
    SELECT p.*, e.title
    FROM payments p
    JOIN events e ON p.event_id = e.id
    WHERE p.user_id = ?
    ORDER BY p.paid_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Simulation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f0ec;
            padding: 20px;
        }
        h2, h3 {
            color: #5a4631;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            box-shadow: 0 0 8px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #bfa58c;
            color: #fff;
        }
        .success {
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .error {
            padding: 10px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<h2>Payment Simulation</h2>

<?php if ($insert_success): ?>
    <div class="success">
        Payment recorded successfully!<br>
        <strong>User ID:</strong> <?= htmlspecialchars($user_id) ?><br>
        <strong>Event ID:</strong> <?= htmlspecialchars($event_id) ?><br>
        <strong>Amount:</strong> $<?= number_format($amount, 2) ?><br>
        <strong>Status:</strong> <?= htmlspecialchars($payment_status) ?>
    </div>
<?php else: ?>
    <div class="error">
        Error processing payment: <?= htmlspecialchars($error_msg) ?>
    </div>
<?php endif; ?>

<h3>Payment History for User ID <?= htmlspecialchars($user_id) ?></h3>

<?php if ($payments->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>Event</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Paid At</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $payments->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td>$<?= number_format($row['amount'], 2) ?></td>
                <td><?= htmlspecialchars($row['payment_status']) ?></td>
                <td><?= htmlspecialchars($row['paid_at']) ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <p>No payment history found for this user.</p>
<?php endif; ?>

</body>
</html>
