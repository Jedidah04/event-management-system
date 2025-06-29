<?php
session_start();
require_once("db.php");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit();
}

// Fetch user name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) die("User not found.");
$user_name = $user['name'];

// Handle unregister POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unregister_event_id'])) {
    $eventToRemove = intval($_POST['unregister_event_id']);
    $delStmt = $conn->prepare("DELETE FROM registrations WHERE user_id = ? AND event_id = ?");
    $delStmt->bind_param("ii", $user_id, $eventToRemove);
    $delStmt->execute();
}

// Fetch registered events with venue info
$registeredStmt = $conn->prepare("
    SELECT e.id, e.title, e.date, v.name AS venue_name
    FROM events e
    JOIN registrations r ON e.id = r.event_id
    LEFT JOIN venues v ON e.venue_id = v.id
    WHERE r.user_id = ?
    ORDER BY e.date ASC
");
$registeredStmt->bind_param("i", $user_id);
$registeredStmt->execute();
$registeredResult = $registeredStmt->get_result();

// Fetch available events with venue and ticket info
$availableStmt = $conn->prepare("
    SELECT e.id, e.title, e.date, v.name AS venue_name, e.ticket_price
    FROM events e
    LEFT JOIN venues v ON e.venue_id = v.id
    WHERE e.status = 'Published' AND e.date >= CURDATE()
      AND e.id NOT IN (SELECT event_id FROM registrations WHERE user_id = ?)
    ORDER BY e.date ASC
");
$availableStmt->bind_param("i", $user_id);
$availableStmt->execute();
$availableResult = $availableStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Registration Management</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f0ebe1; padding: 20px; }
    h2, h3 { color: #5a4631; text-align: center; }
    ul { list-style: none; padding: 0; max-width: 700px; margin: 20px auto; }
    li { background: white; margin-bottom: 12px; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .btn {
      background: #bfa58c; color: white; border: none; padding: 8px 14px;
      border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none;
      display: inline-block; margin-left: 10px;
    }
    .btn:hover { background: #a78664; }
    form { display: inline; }
  </style>
</head>
<body>

<h2>Welcome, <?= htmlspecialchars($user_name) ?>!</h2>

<h3>Your Registered Events</h3>
<?php if ($registeredResult->num_rows > 0): ?>
  <ul>
    <?php while ($event = $registeredResult->fetch_assoc()): ?>
      <li>
        <strong><?= htmlspecialchars($event['title']) ?></strong> — <?= date('F j, Y', strtotime($event['date'])) ?> @ <?= htmlspecialchars($event['venue_name']) ?>
        <form method="POST" onsubmit="return confirm('Unregister from <?= htmlspecialchars(addslashes($event['title'])) ?>?');" style="float:right;">
          <input type="hidden" name="unregister_event_id" value="<?= $event['id'] ?>">
          <button type="submit" class="btn">Unregister</button>
        </form>
        <div style="clear:both;"></div>
      </li>
    <?php endwhile; ?>
  </ul>
<?php else: ?>
  <p style="text-align:center;">You are not registered for any events.</p>
<?php endif; ?>

<hr>

<h3>Available Events to Pay & Register</h3>
<?php if ($availableResult->num_rows > 0): ?>
  <ul>
    <?php while ($event = $availableResult->fetch_assoc()): ?>
      <li>
        <strong><?= htmlspecialchars($event['title']) ?></strong> — <?= date('F j, Y', strtotime($event['date'])) ?> @ <?= htmlspecialchars($event['venue_name']) ?>
        <span style="margin-left:10px;">Ticket Price: ₦<?= number_format($event['ticket_price'], 2) ?></span>
        <a href="flutterwave-payment.php?user_id=<?= $user_id ?>&event_id=<?= $event['id'] ?>&amount=<?= $event['ticket_price'] ?>" class="btn">Pay & Register</a>
      </li>
    <?php endwhile; ?>
  </ul>
<?php else: ?>
  <p style="text-align:center;">No available events at the moment.</p>
<?php endif; ?>

</body>
</html>
