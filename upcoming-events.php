<?php
session_start();
require_once("db.php");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit();
}

// Fetch current user name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_name = $user['name'] ?? 'Attendee';

// Fetch all upcoming published events
$today = date('Y-m-d');
$eventStmt = $conn->prepare("
    SELECT e.id, e.title, e.date, v.name AS venue_name
    FROM events e
    LEFT JOIN venues v ON e.venue_id = v.id
    WHERE e.status = 'Published' AND e.published = 1 AND e.date >= ?
    ORDER BY e.date ASC
");
$eventStmt->bind_param("s", $today);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upcoming Events</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f4f0;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h2 {
            color: #a78664;
        }
        ul.no-style {
            list-style: none;
            padding-left: 0;
        }
        ul.no-style li {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ccc;
        }
        a {
            color: #bfa58c;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Upcoming Events</h2>
    <p>Welcome, <?= htmlspecialchars($user_name) ?>.</p>
    <?php if ($eventResult->num_rows > 0): ?>
        <ul class="no-style">
            <?php while ($event = $eventResult->fetch_assoc()): ?>
                <li>
                    <strong style="font-size: 1.2rem;"><?= htmlspecialchars($event['title']) ?></strong><br>
                    📅 <?= date('F j, Y', strtotime($event['date'])) ?><br>
                    📍 <?= htmlspecialchars($event['venue_name']) ?><br>
                    <a href="event-details.php?id=<?= $event['id'] ?>">View Details</a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>There are no upcoming events at the moment.</p>
    <?php endif; ?>
    <br>
    <a href="attendee-dashboard.php">← Back to Dashboard</a>
</div>
</body>
</html>
