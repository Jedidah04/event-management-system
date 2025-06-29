<?php
session_start();

if (!isset($_SESSION['organizer_id'])) {
    echo "Access denied. Please log in.";
    exit();
}

$user_id = $_SESSION['organizer_id'];
$username = $_SESSION['organizer_name'] ?? 'Organizer';

require_once("db.php");

// Fetch venues for datalist
$venues_result = $conn->query("SELECT id, name FROM venues ORDER BY name");
$venues = [];
while ($row = $venues_result->fetch_assoc()) {
    $venues[] = $row;
}

// Common timezone list
$timezones = [
    "Africa/Lagos", "America/New_York", "America/Chicago", "America/Denver", "America/Los_Angeles",
    "Europe/London", "Europe/Berlin", "Europe/Paris", "Asia/Tokyo", "Asia/Kolkata", "Australia/Sydney",
    "UTC"
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $desc = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $date = isset($_POST['date']) ? str_replace('T', ' ', $_POST['date']) : '';
    $timezone = $_POST['timezone'] ?? '';
    $venue_id = $_POST['venue_id'] ?? null;
    $venue_name = trim($_POST['venue_name'] ?? '');
    $status = $_POST['status'] ?? 'Draft';
    $eventId = $_POST['event_id'] ?? null;
    $published = isset($_POST['publish']) ? 1 : 0;

    if (!$title || !$date) {
        die("Error: Title and Date are required.");
    }

    // Handle venue: if venue_id empty but venue_name provided, create new venue or get existing id
    if (empty($venue_id) && !empty($venue_name)) {
        $stmt = $conn->prepare("SELECT id FROM venues WHERE LOWER(name) = LOWER(?) LIMIT 1");
        $stmt->bind_param("s", $venue_name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $venue_id = $row['id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO venues (name) VALUES (?)");
            $stmt->bind_param("s", $venue_name);
            $stmt->execute();
            $venue_id = $stmt->insert_id;
        }
    }

    if (!$venue_id) {
        die("Error: Venue must be selected or typed.");
    }

    // Handle banner upload
    $banner = '';
    if (!empty($_FILES['banner']['name'])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
        $banner = $uploadDir . uniqid('banner_', true) . "." . $ext;
        move_uploaded_file($_FILES['banner']['tmp_name'], $banner);
    }

    if ($eventId) {
        // Update event
        $query = "UPDATE events SET title=?, description=?, category=?, date=?, timezone=?, venue_id=?, status=?";
        if ($banner) $query .= ", banner=?";
        $query .= " WHERE id=? AND user_id=?";

        $stmt = $conn->prepare($query);

        if ($banner) {
            $stmt->bind_param("ssssssssii", $title, $desc, $category, $date, $timezone, $venue_id, $status, $banner, $eventId, $user_id);
        } else {
            $stmt->bind_param("ssssssiii", $title, $desc, $category, $date, $timezone, $venue_id, $status, $eventId, $user_id);
        }
        $stmt->execute();
    } else {
        // Create new event
        $stmt = $conn->prepare("INSERT INTO events (title, description, category, date, timezone, venue_id, status, banner, user_id, published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssii", $title, $desc, $category, $date, $timezone, $venue_id, $status, $banner, $user_id, $published);
        $stmt->execute();
    }

    header("Location: manage-events.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM events WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    header("Location: manage-events.php");
    exit();
}
if (isset($_GET['publish'])) {
    $id = $_GET['publish'];
    $stmt = $conn->prepare("UPDATE events SET status = 'Published', published = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    header("Location: manage-events.php");
    exit();
}

// Fetch events
$stmt = $conn->prepare("
    SELECT e.*, v.name AS venue_name
    FROM events e
    LEFT JOIN venues v ON e.venue_id = v.id
    WHERE e.user_id = ?
    ORDER BY e.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$events = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Events</title>
    <style>
    body, html {
      height: 100%;
      margin: 0;
      font-family: Arial, sans-serif;
      background: url('https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1470&q=80') no-repeat center center fixed;
      background-size: cover;
      position: relative;
      color: #bfa58c;
    }
    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      background: rgba(30, 20, 10, 0.65);
      z-index: -1;
    }

    nav { background:#bfa58c; padding:10px; color:#fff; font-weight:bold; margin-bottom: 20px;}
    nav a { color:#fff; text-decoration:none; margin-right:15px; }
    form, table { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 10px; color: #4a3f35; }
    input, select, textarea { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #cbbfa6; border-radius: 6px; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left;}
    img { max-width: 100px; border-radius: 6px; }
    button, .btn { padding: 6px 10px; border: none; border-radius: 5px; background: #bfa58c; color: white; cursor: pointer; font-weight: 600; }
    .btn-danger { background: #d9534f; }
    .nude-btn {
        background-color: #e6d8c3;
        color: #5a4631;
        border: 1px solid #cbbfa6;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        margin-right: 10px;
    }
    .nude-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    </style>
</head>
<body>

<nav>
    Welcome, <?= htmlspecialchars($username) ?> |
    <a href="manage-events.php">Manage Events</a>
    <a href="manage-venues.php">Manage Venues</a>
    <a href="event-analytics.php" style="color:#fff; margin-left:15px; font-weight:bold;">Analytics</a>
    <a href="logout.php">Logout</a>
</nav>

<h2>Create / Edit Event</h2>
<form method="POST" enctype="multipart/form-data" id="eventForm">
    <input type="hidden" name="event_id" id="event_id">

    <label>Title</label>
    <input type="text" name="title" id="title" required>

    <label>Description</label>
    <textarea name="description" id="description" required></textarea>

    <label>Category</label>
    <select name="category" id="category" required>
        <option value="">-- Select --</option>
        <option>Webinar</option>
        <option>Concert</option>
        <option>Workshop</option>
        <option>Seminar</option>
        <option>Conference</option>
        <option>Meetup</option>
        <option>Festival</option>
        <option>Networking</option>
        <option>Fundraiser</option>
        <option>Training</option>
    </select>

    <label>Date & Time</label>
    <input type="datetime-local" name="date" id="date" required>

    <label>Timezone</label>
    <select name="timezone" id="timezone" required>
        <?php foreach ($timezones as $tz): ?>
            <option value="<?= htmlspecialchars($tz) ?>" <?= ($tz == "Africa/Lagos" ? "selected" : "") ?>><?= htmlspecialchars($tz) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Venue (select or type new)</label>
    <input list="venues-list" name="venue_name" id="venue_name" placeholder="Select or type a venue" required>
    <datalist id="venues-list">
        <?php foreach ($venues as $venue): ?>
            <option value="<?= htmlspecialchars($venue['name']) ?>">
        <?php endforeach; ?>
    </datalist>
    <input type="hidden" name="venue_id" id="venue_id" value="">

    <div style="margin-top:10px;">
        <button type="button" id="createVenueBtn" class="nude-btn">Create Venue</button>
        <button type="button" id="editVenueBtn" class="nude-btn" disabled>Edit Venue</button>
    </div>

    <label>Status</label>
    <select name="status" id="status">
        <option>Draft</option>
        <option>Published</option>
        <option>Canceled</option>
    </select>

    <label><input type="checkbox" name="publish" value="1"> Publish Now</label>

    <label>Banner (Optional)</label>
    <input type="file" name="banner">

    <button type="submit" style="margin-top:15px;">Save Event</button>
</form>

<h2>All Events</h2>
<table>
    <tr>
        <th>Title</th>
        <th>Category</th>
        <th>Date & Time</th>
        <th>Timezone</th>
        <th>Venue</th>
        <th>Status</th>
        <th>Banner</th>
        <th>Actions</th>
    </tr>
    <?php while ($event = $events->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($event['title']) ?></td>
        <td><?= htmlspecialchars($event['category']) ?></td>
        <td><?= htmlspecialchars($event['date']) ?></td>
        <td><?= htmlspecialchars($event['timezone']) ?></td>
        <td><?= htmlspecialchars($event['venue_name']) ?></td>
        <td><?= htmlspecialchars($event['status']) ?></td>
        <td><?php if ($event['banner']): ?><img src="<?= htmlspecialchars($event['banner']) ?>" alt="Banner"><?php endif; ?></td>
        <td>
            <button class="btn" onclick='editEvent(<?= json_encode($event) ?>)'>Edit</button>
            <a class="btn btn-danger" href="?delete=<?= $event['id'] ?>" onclick="return confirm('Delete this event?')">Delete</a>
            <?php if ($event['status'] === 'Draft'): ?>
                <a class="btn" href="?publish=<?= $event['id'] ?>" onclick="return confirm('Publish this event now?')">Publish Now</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<script>
// Venue auto-match logic
const venuesMap = {};
<?php foreach ($venues as $venue): ?>
venuesMap["<?= addslashes(strtolower($venue['name'])) ?>"] = <?= (int)$venue['id'] ?>;
<?php endforeach; ?>

document.getElementById('venue_name').addEventListener('input', function() {
    const inputVal = this.value.toLowerCase();
    const venueIdInput = document.getElementById('venue_id');
    const editBtn = document.getElementById('editVenueBtn');

    if (venuesMap.hasOwnProperty(inputVal)) {
        venueIdInput.value = venuesMap[inputVal];
        editBtn.disabled = false;
    } else {
        venueIdInput.value = '';
        editBtn.disabled = true;
    }
});

function editEvent(event) {
    document.getElementById('event_id').value = event.id;
    document.getElementById('title').value = event.title;
    document.getElementById('description').value = event.description;
    document.getElementById('category').value = event.category;
    if (event.date) {
        let dt = event.date.replace(' ', 'T').slice(0,16);
        document.getElementById('date').value = dt;
    }
    document.getElementById('timezone').value = event.timezone;
    document.getElementById('venue_name').value = event.venue_name;
    const venueKey = event.venue_name.toLowerCase();
    document.getElementById('venue_id').value = venuesMap[venueKey] || '';
    document.getElementById('status').value = event.status;
    document.getElementById('editVenueBtn').disabled = !venuesMap[venueKey];
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('createVenueBtn').addEventListener('click', () => {
    window.location.href = 'manage-venues.php?action=create';
});
document.getElementById('editVenueBtn').addEventListener('click', () => {
    const venueId = document.getElementById('venue_id').value;
    if (venueId) {
        window.location.href = 'manage-venues.php?action=edit&id=' + venueId;
    }
});
</script>

</body>
</html>
