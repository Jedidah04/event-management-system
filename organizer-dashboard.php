<?php
session_start();
if (!isset($_SESSION['organizer_id'])) {
    header("Location: organizer-login.php");
    exit();
}

$user_id = $_SESSION['organizer_id'];
$username = $_SESSION['organizer_name'] ?? 'Organizer';

require_once("db.php");

// --- Handle Events POST (Create/Update) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'event_form') {
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

    // Venue logic (create or get existing)
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

    // Banner upload
    $banner = '';
    if (!empty($_FILES['banner']['name'])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
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
        // Create event
        $stmt = $conn->prepare("INSERT INTO events (title, description, category, date, timezone, venue_id, status, banner, user_id, published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssii", $title, $desc, $category, $date, $timezone, $venue_id, $status, $banner, $user_id, $published);
        $stmt->execute();
    }
    header("Location: ".$_SERVER['PHP_SELF']."#tab-events");
    exit();
}

// --- Handle Venues POST (Create/Update) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'venue_form') {
    $venue_id = $_POST['venue_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $location = $_POST['location'] ?? '';

    if (!$name) {
        die("Error: Venue name required.");
    }

    if ($venue_id) {
        $stmt = $conn->prepare("UPDATE venues SET name=?, location=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $location, $venue_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO venues (name, location) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $location);
        $stmt->execute();
    }
    header("Location: ".$_SERVER['PHP_SELF']."#tab-venues");
    exit();
}

// --- Handle Deletions ---
if (isset($_GET['delete_event'])) {
    $id = $_GET['delete_event'];
    $stmt = $conn->prepare("DELETE FROM events WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    header("Location: ".$_SERVER['PHP_SELF']."#tab-events");
    exit();
}
if (isset($_GET['publish_event'])) {
    $id = $_GET['publish_event'];
    $stmt = $conn->prepare("UPDATE events SET status = 'Published', published = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    header("Location: ".$_SERVER['PHP_SELF']."#tab-events");
    exit();
}
if (isset($_GET['delete_venue'])) {
    $id = $_GET['delete_venue'];
    $stmt = $conn->prepare("DELETE FROM venues WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: ".$_SERVER['PHP_SELF']."#tab-venues");
    exit();
}

// --- Fetch Data for Display ---
// Events + venues list for datalist
$venues_result = $conn->query("SELECT id, name FROM venues ORDER BY name");
$venues = [];
while ($row = $venues_result->fetch_assoc()) {
    $venues[] = $row;
}
// Events for table
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

// Venues for table
$venues_table = $conn->query("SELECT * FROM venues ORDER BY id DESC");

// Analytics: events + attendee counts
$sql = "
  SELECT e.id, e.title, e.date, e.status, COUNT(r.id) AS total_registered
  FROM events e
  LEFT JOIN registrations r ON r.event_id = e.id AND r.status = 'Registered'
  WHERE e.user_id = ?
  GROUP BY e.id
  ORDER BY e.date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$analytics_events = $stmt->get_result();

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Organizer Dashboard</title>
<style>
  /* Reset & base */
  * {
    box-sizing: border-box;
  }
  body, html {
    margin: 0; padding: 0; height: 100%;
    font-family: Arial, sans-serif;
    background: url('https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1470&q=80') no-repeat center center fixed;
    background-size: cover;
    color: #bfa58c;
  }
  body::before {
    content: "";
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(30, 20, 10, 0.65);
    z-index: -1;
  }

  nav {
    background: #bfa58c;
    padding: 12px 20px;
    color: white;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  nav .nav-left a {
    color: white;
    text-decoration: none;
    margin-right: 20px;
    font-weight: 600;
  }
  nav .nav-left a:hover {
    text-decoration: underline;
  }
  nav .welcome {
    font-weight: 600;
  }
  nav a.logout {
    color: white;
    background: #8c7a5a;
    padding: 8px 14px;
    border-radius: 5px;
    font-weight: 600;
    text-decoration: none;
    transition: background-color 0.3s ease;
  }
  nav a.logout:hover {
    background: #6e5d43;
  }

  /* Tabs container */
  .tabs {
    max-width: 1100px;
    margin: 30px auto 60px auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0,0,0,0.25);
    color: #4a3f35;
  }

  /* Tab buttons */
  .tab-buttons {
    display: flex;
    background: #bfa58c;
    border-radius: 12px 12px 0 0;
    overflow-x: auto;
  }
  .tab-buttons button {
    flex: 1;
    border: none;
    background: transparent;
    color: white;
    padding: 14px 20px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
    border-bottom: 3px solid transparent;
  }
  .tab-buttons button:hover {
    background-color: rgba(255,255,255,0.25);
  }
  .tab-buttons button.active {
    border-bottom: 3px solid #4a3f35;
    background-color: #d9c9b3;
    color: #4a3f35;
  }

  /* Tab content */
  .tab-content {
    padding: 25px 35px 40px 35px;
  }
  .tab-pane {
    display: none;
  }
  .tab-pane.active {
    display: block;
  }

  /* Form styles */
  form {
    max-width: 700px;
    margin-bottom: 30px;
  }
  form label {
    font-weight: 600;
    display: block;
    margin-top: 15px;
    margin-bottom: 6px;
  }
  form input[type=text],
  form input[type=datetime-local],
  form input[type=file],
  form select,
  form textarea {
    width: 100%;
    padding: 10px 12px;
    font-size: 15px;
    border: 1px solid #cbbfa6;
    border-radius: 6px;
    font-family: inherit;
    color: #4a3f35;
  }
  form textarea {
    min-height: 90px;
    resize: vertical;
  }
  form input[type=checkbox] {
    width: auto;
    margin-right: 8px;
  }
  form button {
    margin-top: 22px;
    background-color: #bfa58c;
    border: none;
    padding: 12px 20px;
    border-radius: 7px;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    color: white;
    transition: background-color 0.3s ease;
  }
  form button:hover {
    background-color: #a5916c;
  }

  /* Table styles */
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
  }
  th, td {
    border: 1px solid #ddd;
    padding: 12px 15px;
    text-align: left;
  }
  th {
    background: #bfa58c;
    color: white;
  }
  td img {
    max-width: 100px;
    border-radius: 6px;
  }

  /* Buttons in table */
  .btn {
    background: #bfa58c;
    color: white;
    padding: 6px 12px;
    border-radius: 5px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    margin-right: 6px;
    transition: background-color 0.3s ease;
    text-decoration: none;
    display: inline-block;
  }
  .btn:hover {
    background: #a5916c;
  }
  .btn-danger {
    background: #d9534f;
  }
  .btn-danger:hover {
    background: #b2362a;
  }

  /* Smaller utility button */
  .nude-btn {
    background-color: #e6d8c3;
    color: #5a4631;
    border: 1px solid #cbbfa6;
    padding: 7px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    margin-right: 10px;
    font-size: 14px;
  }
  .nude-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  /* Analytics styles */
  .attendees-list {
    font-size: 0.9em;
    margin-top: 5px;
    padding-left: 15px;
  }
  .attendee-item {
    margin-bottom: 3px;
  }
  .no-attendees {
    font-style: italic;
    color: #888;
  }

  /* Responsive */
  @media (max-width: 720px) {
    nav {
      flex-direction: column;
      align-items: flex-start;
    }
    nav .nav-left {
      margin-bottom: 10px;
    }
    .tab-buttons button {
      font-size: 14px;
      padding: 12px 10px;
    }
  }
</style>
</head>
<body>

<nav>
  <div class="nav-left">
    <a href="#" data-tab="tab-events" class="tab-link">Manage Events</a>
    <a href="#" data-tab="tab-venues" class="tab-link">Manage Venues</a>
    <a href="#" data-tab="tab-analytics" class="tab-link">Event Analytics</a>
  </div>
  <div class="welcome">
    Welcome, <?= htmlspecialchars($username) ?> |
    <a href="logout.php" class="logout">Logout</a>
  </div>
</nav>

<div class="tabs" role="tablist" aria-label="Organizer dashboard tabs">

  <div class="tab-buttons" role="tablist">
    <button role="tab" aria-selected="true" aria-controls="tab-events" id="tab-events-btn" class="active" data-tab="tab-events">Manage Events</button>
    <button role="tab" aria-selected="false" aria-controls="tab-venues" id="tab-venues-btn" data-tab="tab-venues">Manage Venues</button>
    <button role="tab" aria-selected="false" aria-controls="tab-analytics" id="tab-analytics-btn" data-tab="tab-analytics">Event Analytics</button>
  </div>

  <!-- Manage Events Tab -->
  <section id="tab-events" class="tab-pane active" role="tabpanel" aria-labelledby="tab-events-btn">

    <h2>Create / Edit Event</h2>
    <form method="POST" enctype="multipart/form-data" id="eventForm">
      <input type="hidden" name="form_type" value="event_form">
      <input type="hidden" name="event_id" id="event_id">

      <label for="title">Title *</label>
      <input type="text" id="title" name="title" required>

      <label for="description">Description</label>
      <textarea id="description" name="description"></textarea>

      <label for="category">Category</label>
      <input type="text" id="category" name="category">

      <label for="date">Date & Time *</label>
      <input type="datetime-local" id="date" name="date" required>

      <label for="timezone">Timezone</label>
      <select id="timezone" name="timezone">
        <option value="">Select Timezone</option>
        <?php
        // List of common timezones
        $timezones = timezone_identifiers_list();
        foreach ($timezones as $tz) {
            echo "<option value=\"" . htmlspecialchars($tz) . "\">" . htmlspecialchars($tz) . "</option>";
        }
        ?>
      </select>

      <label for="venue_id">Select Venue *</label>
      <select id="venue_id" name="venue_id">
        <option value="">-- Select Venue --</option>
        <?php foreach ($venues as $v): ?>
          <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="venue_name">Or Type New Venue Name</label>
      <input type="text" id="venue_name" name="venue_name" placeholder="New venue name if not selecting above">

      <label for="banner">Banner Image</label>
      <input type="file" id="banner" name="banner" accept="image/*">

      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="Draft">Draft</option>
        <option value="Published">Published</option>
        <option value="Cancelled">Cancelled</option>
      </select>

      <label><input type="checkbox" name="publish" id="publish"> Publish Now</label>

      <button type="submit">Save Event</button>
    </form>

    <h2>Your Events</h2>
    <table aria-label="Your events table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Date</th>
          <th>Venue</th>
          <th>Status</th>
          <th>Banner</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($event = $events->fetch_assoc()): ?>
          <tr>
            <td><?= $event['id'] ?></td>
            <td><?= htmlspecialchars($event['title']) ?></td>
            <td><?= htmlspecialchars($event['date']) ?></td>
            <td><?= htmlspecialchars($event['venue_name']) ?></td>
            <td><?= htmlspecialchars($event['status']) ?></td>
            <td>
              <?php if ($event['banner']): ?>
                <img src="<?= htmlspecialchars($event['banner']) ?>" alt="Banner" />
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td>
              <button class="btn" onclick='editEvent(<?= json_encode($event) ?>)'>Edit</button>
              <a href="?delete_event=<?= $event['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this event?')">Delete</a>
              <?php if ($event['status'] !== 'Published'): ?>
                <a href="?publish_event=<?= $event['id'] ?>" class="btn" onclick="return confirm('Publish this event?')">Publish</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

  </section>

  <!-- Manage Venues Tab -->
  <section id="tab-venues" class="tab-pane" role="tabpanel" aria-labelledby="tab-venues-btn">

    <h2>Add / Edit Venue</h2>
    <form method="POST" id="venueForm">
      <input type="hidden" name="form_type" value="venue_form">
      <input type="hidden" name="venue_id" id="venue_id">

      <label for="name">Name *</label>
      <input type="text" name="name" id="venue_name_input" required>

      <label for="location">Location</label>
      <input type="text" name="location" id="venue_location">

      <button type="submit">Save Venue</button>
    </form>

    <h2>All Venues</h2>
    <table aria-label="All venues table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Location</th>
          <th>Created At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($venue = $venues_table->fetch_assoc()): ?>
          <tr>
            <td><?= $venue['id'] ?></td>
            <td><?= htmlspecialchars($venue['name']) ?></td>
            <td><?= htmlspecialchars($venue['location']) ?></td>
            <td><?= $venue['created_at'] ?></td>
            <td>
              <button class="btn" onclick='editVenue(<?= json_encode($venue) ?>)'>Edit</button>
              <a href="?delete_venue=<?= $venue['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this venue?')">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

  </section>

  <!-- Analytics Tab -->
  <section id="tab-analytics" class="tab-pane" role="tabpanel" aria-labelledby="tab-analytics-btn">

    <h2>Event Attendance Analytics</h2>

    <table aria-label="Event attendance analytics table">
      <thead>
        <tr>
          <th>Event Title</th>
          <th>Date</th>
          <th>Status</th>
          <th>Registered Attendees</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($analytics_events->num_rows > 0): ?>
          <?php while ($event = $analytics_events->fetch_assoc()): ?>
            <?php
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

  </section>

</div>

<script>
  // Tab switching logic
  const tabs = document.querySelectorAll('.tab-buttons button');
  const panes = document.querySelectorAll('.tab-pane');

  function activateTab(tabId) {
    tabs.forEach(btn => {
      btn.classList.toggle('active', btn.dataset.tab === tabId);
      btn.setAttribute('aria-selected', btn.dataset.tab === tabId ? 'true' : 'false');
    });
    panes.forEach(pane => {
      pane.classList.toggle('active', pane.id === tabId);
    });
    // Reset forms on tab switch
    if (tabId === 'tab-events') {
      resetEventForm();
    } else if (tabId === 'tab-venues') {
      resetVenueForm();
    }
  }

  tabs.forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      activateTab(btn.dataset.tab);
      history.replaceState(null, '', '#' + btn.dataset.tab); // Update URL hash
    });
  });

  // Load tab from URL hash or default to events
  window.addEventListener('load', () => {
    const hash = location.hash.replace('#', '');
    if (hash && document.getElementById(hash)) {
      activateTab(hash);
    } else {
      activateTab('tab-events');
    }
  });

  // Edit event - fill form
  function editEvent(event) {
    activateTab('tab-events');
    document.getElementById('event_id').value = event.id;
    document.getElementById('title').value = event.title;
    document.getElementById('description').value = event.description;
    document.getElementById('category').value = event.category;
    document.getElementById('date').value = event.date.replace(' ', 'T').substring(0,16);
    document.getElementById('timezone').value = event.timezone || '';
    document.getElementById('venue_id').value = event.venue_id || '';
    document.getElementById('venue_name').value = '';
    document.getElementById('status').value = event.status || 'Draft';
    document.getElementById('publish').checked = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  // Reset event form
  function resetEventForm() {
    document.getElementById('eventForm').reset();
    document.getElementById('event_id').value = '';
  }

  // Edit venue - fill form
  function editVenue(venue) {
    activateTab('tab-venues');
    document.getElementById('venue_id').value = venue.id;
    document.getElementById('venue_name_input').value = venue.name;
    document.getElementById('venue_location').value = venue.location;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  // Reset venue form
  function resetVenueForm() {
    document.getElementById('venueForm').reset();
    document.getElementById('venue_id').value = '';
  }
</script>

</body>
</html>
