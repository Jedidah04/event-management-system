<?php
require_once("db.php");
session_start();

// Redirect to login if not logged in (optional but recommended)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validate and get the event ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid event ID.");
}

$event_id = (int)$_GET['id'];

// Fetch event details with venue info
$stmt = $conn->prepare("
    SELECT e.*, v.name AS venue_name, v.location AS venue_address
    FROM events e
    LEFT JOIN venues v ON e.venue_id = v.id
    WHERE e.id = ?
");

$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Event not found.");
}

$event = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($event['title']) ?> - Event Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9f4f0;
            padding: 40px;
        }
        .container {
            max-width: 700px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #bfa58c;
            margin-bottom: 20px;
        }
        .meta {
            margin-bottom: 15px;
            font-size: 1rem;
            color: #555;
            line-height: 1.6;
        }
        .section {
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            background: #bfa58c;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s ease;
        }
        .back-link:hover {
            background: #a78664;
        }
    </style>
</head>
<body>

<div class="container">
    <h1><?= htmlspecialchars($event['title']) ?></h1>

    <div class="meta">
        <strong>Date:</strong> <?= htmlspecialchars(date('F j, Y', strtotime($event['date']))) ?><br>
        <strong>Time:</strong> <?= htmlspecialchars(date('g:i A', strtotime($event['date']))) ?><br>
        <strong>Category:</strong> <?= htmlspecialchars($event['category'] ?? 'N/A') ?><br>
        <strong>Venue:</strong> <?= htmlspecialchars($event['venue_name'] ?? 'TBA') ?><br>
        <strong>Address:</strong> <?= htmlspecialchars($event['venue_address'] ?? 'Not provided') ?><br>
        <strong>Status:</strong> <?= htmlspecialchars($event['status']) ?><br>
    </div>

    <div class="section">
        <label>Description:</label>
        <div><?= nl2br(htmlspecialchars($event['description'] ?? 'No description available.')) ?></div>
    </div>

    <div class="section">
        <label>Ticket Price:</label>
        ₦<?= number_format((float)$event['ticket_price'], 2) ?>
    </div>
     <a href="simulate-payment.php?user_id=<?= $user_id ?>&event_id=<?= $event_id ?>&amount=<?= $amount ?>" class="btn">Simulate Payment</a>

    <a href="attendee-dashboard.php" class="back-link">← Back to Dashboard</a>
</div>

</body>
</html>
