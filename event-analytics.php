<?php
session_start();
require_once("db.php");

if (!isset($_SESSION['organizer_id'])) {
    echo "Access denied. Please log in.";
    exit();
}

$organizer_id = $_SESSION['organizer_id'];

// Fetch events with attendee counts
$sql = "
  SELECT e.id, e.title, e.date, e.status, COUNT(r.id) AS total_registered
  FROM events e
  LEFT JOIN registrations r ON r.event_id = e.id AND r.status = 'Registered'
  WHERE e.user_id = ?
  GROUP BY e.id
  ORDER BY e.date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$events = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Analytics</title>
    <style>
      body { font-family: Arial, sans-serif; padding: 20px; background: #f4f0ec; color: #5a4631; }
      table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
      th, td { border: 1px solid #ddd; padding: 12px; vertical-align: top; }
      th { background: #bfa58c; color: white; }
      h1 { color: #5a4631; margin-bottom: 0; }
      .back-link { margin-bottom: 20px; display: inline-block; background: #bfa58c; color: white; padding: 8px 12px; border-radius: 5px; text-decoration: none; }
      .attendees-list { font-size: 0.9em; margin-top: 5px; padding-left: 15px; }
      .attendee-item { margin-bottom: 3px; }
      .no-attendees { font-style: italic; color: #888; }
    </style>
</head>
<body>

<h1>Event Attendance Analytics</h1>
<a href="manage-events.php" class="back-link">Back to Manage Events</a>

<table>
    <thead>
        <tr>
            <th>Event Title</th>
            <th>Date</th>
            <th>Status</th>
            <th>Registered Attendees</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($events->num_rows > 0): ?>
            <?php while ($event = $events->fetch_assoc()): ?>
                <?php
                // Fetch last 3 registered attendees for this event
                $stmt2 = $conn->prepare("
                    SELECT u.name, r.registered_at
                    FROM registrations r
                    JOIN users u ON r.user_id = u.id
                    WHERE r.event_id = ? AND r.status = 'Registered'
                    ORDER BY r.registered_at DESC
                    LIMIT 3
                ");
                $stmt2->bind_param("i", $event['id']);
                $stmt2->execute();
                $recent_attendees = $stmt2->get_result();
                ?>
                <tr>
                    <td><?= htmlspecialchars($event['title']) ?></td>
                    <td><?= htmlspecialchars($event['date']) ?></td>
                    <td><?= htmlspecialchars($event['status']) ?></td>
                    <td>
                        <strong><?= (int)$event['total_registered'] ?></strong>
                        <?php if ($recent_attendees->num_rows > 0): ?>
                            <div class="attendees-list">
                                <em>Recent Attendees:</em>
                                <ul>
                                    <?php while ($attendee = $recent_attendees->fetch_assoc()): ?>
                                        <li class="attendee-item">
                                            <?= htmlspecialchars($attendee['name']) ?> (<?= htmlspecialchars(date("Y-m-d", strtotime($attendee['registered_at']))) ?>)
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="no-attendees">No attendees registered yet.</div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">No events found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
