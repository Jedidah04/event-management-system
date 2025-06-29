<?php
session_start();
require_once("db.php");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit();
}

// Fetch current user data
$stmt = $conn->prepare("SELECT name, email, phone, notify_email, notify_sms, notify_push, password_hash FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Message variables for feedback in registration management
$regMessage = '';
$regMessageClass = 'success';

// Handle unregister POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unregister_event_id'])) {
    $eventToRemove = intval($_POST['unregister_event_id']);
    $delStmt = $conn->prepare("DELETE FROM registrations WHERE user_id = ? AND event_id = ?");
    $delStmt->bind_param("ii", $user_id, $eventToRemove);
    if ($delStmt->execute()) {
        $regMessage = 'Successfully unregistered from event.';
        $regMessageClass = 'success';
    } else {
        $regMessage = 'Failed to unregister from event.';
        $regMessageClass = 'error';
    }
}

// Handle register POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event_id'])) {
    $eventToAdd = intval($_POST['register_event_id']);
    // Check if already registered
    $checkStmt = $conn->prepare("SELECT 1 FROM registrations WHERE user_id = ? AND event_id = ?");
    $checkStmt->bind_param("ii", $user_id, $eventToAdd);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows === 0) {
        $insStmt = $conn->prepare("INSERT INTO registrations (user_id, event_id) VALUES (?, ?)");
        $insStmt->bind_param("ii", $user_id, $eventToAdd);
        if ($insStmt->execute()) {
            $regMessage = 'Successfully registered for event.';
            $regMessageClass = 'success';
        } else {
            $regMessage = 'Failed to register for event.';
            $regMessageClass = 'error';
        }
    } else {
        $regMessage = 'You are already registered for this event.';
        $regMessageClass = 'error';
    }
}

// Refresh user events after possible registration/unregistration
$registeredStmt = $conn->prepare("
    SELECT e.id, e.title, e.date, e.venue, IFNULL(r.status, 'Registered') AS status
    FROM events e
    JOIN registrations r ON e.id = r.event_id
    WHERE r.user_id = ?
    ORDER BY e.date ASC
");
$registeredStmt->bind_param("i", $user_id);
$registeredStmt->execute();
$registeredResult = $registeredStmt->get_result();

$availableStmt = $conn->prepare("
    SELECT e.id, e.title, e.date, v.name AS venue
    FROM events e
    LEFT JOIN venues v ON e.venue_id = v.id
    WHERE e.status = 'Published'
      AND e.id NOT IN (
        SELECT event_id FROM registrations WHERE user_id = ?
      )
    ORDER BY e.date ASC
");

$availableStmt->bind_param("i", $user_id);
$availableStmt->execute();
$availableResult = $availableStmt->get_result();


// Handle Profile Update
$message = '';
$message_class = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notify_email = isset($_POST['notify_email']) ? 1 : 0;
    $notify_sms = isset($_POST['notify_sms']) ? 1 : 0;
    $notify_push = isset($_POST['notify_push']) ? 1 : 0;

    $updateStmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, notify_email=?, notify_sms=?, notify_push=? WHERE id=?");
    $updateStmt->bind_param("sssiiii", $name, $email, $phone, $notify_email, $notify_sms, $notify_push, $user_id);

    if ($updateStmt->execute()) {
        $message = "Profile updated successfully.";
        $user['name'] = $name;
        $user['email'] = $email;
        $user['phone'] = $phone;
        $user['notify_email'] = $notify_email;
        $user['notify_sms'] = $notify_sms;
        $user['notify_push'] = $notify_push;
    } else {
        $message = "Error updating profile.";
        $message_class = 'error';
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if ($new_pass !== $confirm_pass) {
        $message = "New passwords do not match.";
        $message_class = 'error';
    } else {
        if (password_verify($current_pass, $user['password_hash'])) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $updatePassStmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $updatePassStmt->bind_param("si", $new_hash, $user_id);

            if ($updatePassStmt->execute()) {
                $message = "Password changed successfully.";
            } else {
                $message = "Failed to change password.";
                $message_class = 'error';
            }
        } else {
            $message = "Current password is incorrect.";
            $message_class = 'error';
        }
    }
}

$user_name = $user['name'];

// Fetch events for calendar (unchanged)
$stmt2 = $conn->prepare("
  SELECT e.title, e.description, DATE(e.date) as event_date
  FROM events e
  JOIN registrations r ON e.id = r.event_id
  WHERE r.user_id = ?
");
$stmt2->bind_param("i", $user_id);

$stmt2->execute();
$result2 = $stmt2->get_result();

$events = [];
while ($row = $result2->fetch_assoc()) {
    $events[$row['event_date']][] = $row;
}

?>

<!DOCTYPE html>
<html>
<head>
  <title>Attendee Dashboard</title>
  <style>
    /* Page & font */
   /* Full page background from index.php */
body, html {
  height: 100%;
  margin: 0;
  font-family: Arial, sans-serif;
  background: url('https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1470&q=80') no-repeat center center fixed;
  background-size: cover;
  position: relative;
}

/* Dark overlay */
body::before {
  content: "";
  position: fixed;
  top: 0; left: 0;
  width: 100%;
  height: 100%;
  background: rgba(30, 20, 10, 0.65);
  z-index: -1;
}


    /* Welcome container */
    .welcome-container {
      max-width: 1100px;
      margin: 0 auto 20px auto;
      padding: 20px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      font-size: 1.3rem;
      color: #5a4c3c;
    }

    /* Navbar with tabs */
    .navbar {
      background-color: #bfa58c;
      display: flex;
      flex-wrap: nowrap;
      padding: 10px 20px;
      align-items: center;
      max-width: 1100px;
      margin: 0 auto 30px auto;
      border-radius: 8px;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
      overflow-x: auto;
      scrollbar-width: none;
    }
    .navbar::-webkit-scrollbar {
      display: none;
    }
    .nav-tab {
      color: white;
      text-decoration: none;
      margin-left: 15px;
      padding: 8px 14px;
      border-radius: 5px;
      cursor: pointer;
      user-select: none;
      white-space: nowrap;
      flex-shrink: 0;
      font-weight: 600;
      font-size: 0.95rem;
    }
    .nav-tab:hover, .nav-tab.active {
      background-color: #a78664;
    }

    /* Container for tab content */
    .container {
      max-width: 1100px;
      margin: 0 auto 60px auto;
      padding: 20px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      min-height: 300px;
    }

    /* Tab content hidden by default */
    .tab-content {
      display: none;
    }

    /* Show active tab content */
    .tab-content.active {
      display: block;
    }

    h3 {
      color: #bfa58c;
    }

    /* Profile & Settings form styles */
    form label {
      font-weight: bold;
      display: block;
      margin-top: 12px;
    }
    form input[type="text"],
    form input[type="email"],
    form input[type="tel"],
    form input[type="password"],
    form select,
    form textarea {
      width: 100%;
      padding: 8px;
      margin-top: 6px;
      margin-bottom: 15px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 1rem;
      box-sizing: border-box;
    }
    form button {
      background-color: #bfa58c;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
      margin-top: 10px;
    }
    form button:hover {
      background-color: #a78664;
    }
    hr {
      margin: 40px 0;
      border: none;
      border-top: 1px solid #ccc;
    }
    .message {
      font-weight: bold;
      margin-bottom: 15px;
      color: green;
    }
    .message.error {
      color: red;
    }

    /* ==== Improved Calendar Styles ==== */
    .calendar-container {
      max-width: 1000px;
      margin: auto;
      background: #fff;
      padding: 20px 25px 30px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      user-select: none;
    }
    .calendar-nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .calendar-nav h2 {
      font-weight: 700;
      font-size: 1.8rem;
      color: #7b6e58;
      user-select: none;
    }
    .calendar {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 2px; /* grid lines */
      background: #d1c3a3; /* grid line color */
      border-radius: 8px;
      overflow: hidden;
      font-size: 0.9rem;
    }
    .calendar div {
      background: #faf7f2;
      min-height: 110px;
      padding: 12px 10px 10px 10px;
      box-sizing: border-box;
      position: relative;
      border-radius: 6px;
      box-shadow: inset 0 0 5px rgba(255,255,255,0.7);
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      cursor: default;
      display: flex;
      flex-direction: column;
    }
    .calendar div:hover:not(.day-name) {
      background-color: #f0e8d9;
      box-shadow: 0 4px 8px rgba(183, 149, 90, 0.3);
    }
    .calendar .day-name {
      background: #bfa58c;
      color: white;
      font-weight: 700;
      text-align: center;
      padding: 8px 0;
      user-select: none;
      font-size: 1rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      box-shadow: inset 0 -3px 10px rgba(0,0,0,0.15);
    }
    .calendar .today {
      border: 3px solid #a78664;
      background-color: #fff7e6;
      font-weight: 700;
      box-shadow: 0 0 12px #a78664aa;
    }
    .calendar strong {
      font-weight: 700;
      color: #7b6e58;
      margin-bottom: 6px;
    }
    .event {
      background: #a78664;
      color: white;
      padding: 5px 8px;
      border-radius: 5px;
      margin-top: 6px;
      font-size: 0.85rem;
      display: flex;
      align-items: center;
      gap: 6px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      cursor: pointer;
      user-select: none;
      transition: background-color 0.3s ease;
    }
    .event:hover {
      background-color: #927b59;
    }
    .event::before {
      content: "📅";
      font-size: 1rem;
      display: inline-block;
    }
    .nav-button {
      background-color: #bfa58c;
      color: white;
      border: none;
      padding: 10px 22px;
      border-radius: 6px;
      cursor: pointer;
      user-select: none;
      font-weight: 600;
      font-size: 1rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
      transition: background-color 0.3s ease;
    }
    .nav-button:hover {
      background-color: #a78664;
    }

    /* List styles for upcoming events */
    ul.no-style {
      list-style: none;
      padding-left: 0;
    }
    ul.no-style li {
      margin-bottom: 20px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 10px;
    }

    /* Style for unregister/register buttons inside lists */
    ul.no-style form {
      display: inline;
      margin-top: 6px;
    }
    ul.no-style button {
      background-color: #bfa58c;
      border: none;
      color: white;
      padding: 6px 12px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: 600;
      font-size: 0.9rem;
      transition: background-color 0.3s ease;
    }
    ul.no-style button:hover {
      background-color: #a78664;
    }

  </style>
</head>
<body>

<div class="welcome-container">
  Welcome, <?= htmlspecialchars($user_name) ?>!
</div>

<div class="navbar">
  <div class="nav-tab active" onclick="showTab('upcoming-events', this)">My Upcoming Events</div>
  <div class="nav-tab" onclick="showTab('tickets', this)">Download/Print Tickets</div>
  <div class="nav-tab" onclick="showTab('registration', this)">Registration Management</div>
  <div class="nav-tab" onclick="showTab('calendar', this)">Calendar View</div>
  <div class="nav-tab" onclick="showTab('payments', this)">Payments & Invoices</div>
  <div class="nav-tab" onclick="showTab('profile', this)">Profile & Settings</div>
  <div class="nav-tab" onclick="location.href='logout.php'">Logout</div>
</div>

<div class="container">

  <div id="upcoming-events" class="tab-content active">
    <h3>My Upcoming Events</h3>
    <?php
    $today = date('Y-m-d');
    $upcomingStmt = $conn->prepare("
        SELECT e.id, e.title, e.date, e.venue 
        FROM events e
        JOIN registrations r ON e.id = r.event_id
        WHERE r.user_id = ? AND e.date >= ?
        ORDER BY e.date ASC
    ");
    $upcomingStmt->bind_param("is", $user_id, $today);
    $upcomingStmt->execute();
    $upcomingResult = $upcomingStmt->get_result();

    if ($upcomingResult->num_rows > 0): ?>
      <ul class="no-style">
        <?php while ($event = $upcomingResult->fetch_assoc()): ?>
          <li>
            <strong style="font-size: 1.2rem;"><?= htmlspecialchars($event['title']) ?></strong><br>
            📅 <?= date('F j, Y', strtotime($event['date'])) ?><br>
            📍 <?= htmlspecialchars($event['venue']) ?><br>
            <a href="event-details.php?id=<?= $event['id'] ?>" style="color: #bfa58c; font-weight: bold;">View Details</a>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p>You have no upcoming events.</p>
    <?php endif; ?>
  </div>

 <div id="tickets" class="tab-content">
  <h3>Download/Print Tickets</h3>
  <?php if ($registeredResult->num_rows > 0): ?>
    <ul class="no-style">
      <?php
      // Reset pointer in case it was iterated above
      $registeredResult->data_seek(0);

      while ($event = $registeredResult->fetch_assoc()): ?>
        <li>
          <strong><?= htmlspecialchars($event['title']) ?></strong><br>
          📅 <?= date('F j, Y', strtotime($event['date'])) ?><br>
          📍 <?= htmlspecialchars($event['venue']) ?><br>
          <a href="download-ticket.php?event_id=<?= $event['id'] ?>" class="btn">Download Ticket</a>
        </li>
      <?php endwhile; ?>
    </ul>
  <?php else: ?>
    <p>You have no tickets to download.</p>
  <?php endif; ?>
</div>


  <div id="registration" class="tab-content">
    <h3>Registration Management</h3>

    <?php if ($regMessage): ?>
      <p class="message <?= $regMessageClass ?>"><?= htmlspecialchars($regMessage) ?></p>
    <?php endif; ?>

    <h4>Events You Are Registered For</h4>
    <?php if ($registeredResult->num_rows > 0): ?>
      <ul class="no-style">
        <?php while ($event = $registeredResult->fetch_assoc()): ?>
  <li>
    <strong><?= htmlspecialchars($event['title']) ?></strong> — <?= date('F j, Y', strtotime($event['date'])) ?> @ <?= htmlspecialchars($event['venue']) ?><br>
    Status: <em><?= htmlspecialchars($event['status']) ?></em><br>

    <!-- Simulate Payment Button -->
    <button onclick="location.href='simulate-payment.php?user_id=<?= $user_id ?>&event_id=<?= $event['id'] ?>&amount=100.00'" class="btn" style="margin: 8px 0;">
      Simulate Payment
    </button>

    <form method="POST" onsubmit="return confirm('Are you sure you want to unregister from <?= htmlspecialchars($event['title']) ?>?');">
      <input type="hidden" name="unregister_event_id" value="<?= $event['id'] ?>" />
      <button type="submit">Unregister</button>
    </form>
  </li>
<?php endwhile; ?>

      </ul>
    <?php else: ?>
      <p>You are not registered for any events.</p>
    <?php endif; ?>

    <hr />

    <h4>Available Events to Register</h4>
    <?php if ($availableResult->num_rows > 0): ?>
      <ul class="no-style">
        <?php while ($event = $availableResult->fetch_assoc()): ?>
          <li>
            <strong><?= htmlspecialchars($event['title']) ?></strong> — <?= date('F j, Y', strtotime($event['date'])) ?> @ <?= htmlspecialchars($event['venue']) ?><br>
            <form method="POST">
              <input type="hidden" name="register_event_id" value="<?= $event['id'] ?>" />
              <button type="submit">Register</button>
            </form>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p>No upcoming events available for registration.</p>
    <?php endif; ?>
  </div>


  <div id="calendar" class="tab-content">
    <div class="calendar-container">
      <div class="calendar-nav">
        <button class="nav-button" onclick="prevMonth()">Prev</button>
        <h2 id="monthYear"></h2>
        <button class="nav-button" onclick="nextMonth()">Next</button>
      </div>
      <div class="calendar" id="calendarGrid">
        <!-- Calendar grid injected by JS -->
      </div>
    </div>
  </div>

  <div id="payments" class="tab-content">
    <h3>Payments & Invoices</h3>
    <p>Display payment history and invoice download links here.</p>
  </div>

  <div id="profile" class="tab-content">
    <h3>Profile & Settings</h3>

    <?php if ($message): ?>
      <p class="message <?= $message_class ?>"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="update_profile" value="1" />
      <label for="name">Full Name</label>
      <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required />

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required />

      <label for="phone">Phone</label>
      <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" />

      <label>Notification Preferences</label>
      <label><input type="checkbox" name="notify_email" <?= $user['notify_email'] ? 'checked' : '' ?> /> Email Notifications</label><br/>
      <label><input type="checkbox" name="notify_sms" <?= $user['notify_sms'] ? 'checked' : '' ?> /> SMS Notifications</label><br/>
      <label><input type="checkbox" name="notify_push" <?= $user['notify_push'] ? 'checked' : '' ?> /> Push Notifications</label><br/>

      <button type="submit">Update Profile</button>
    </form>

    <hr />
  </div>
</div>

<script>
  // Tab switching
  function showTab(tabId, el) {
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(t => t.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');

    const tabButtons = document.querySelectorAll('.nav-tab');
    tabButtons.forEach(btn => btn.classList.remove('active'));
    el.classList.add('active');

    // Store selected tab in localStorage
    localStorage.setItem('activeTab', tabId);
  }

  // Restore active tab from localStorage on page load
  window.onload = function () {
    const savedTab = localStorage.getItem('activeTab');
    if (savedTab && document.getElementById(savedTab)) {
      const tabEl = document.querySelector(`.nav-tab[onclick*="${savedTab}"]`);
      if (tabEl) showTab(savedTab, tabEl);
    }
  };


  // Calendar JS
  let currentMonth = new Date().getMonth();
  let currentYear = new Date().getFullYear();

  const monthYearElem = document.getElementById('monthYear');
  const calendarGrid = document.getElementById('calendarGrid');

  // Events data from PHP
  const events = <?= json_encode($events); ?>;

  function renderCalendar(month, year) {
    calendarGrid.innerHTML = '';

    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'];

    monthYearElem.textContent = `${monthNames[month]} ${year}`;

    const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    // Day names header
    daysOfWeek.forEach(day => {
      const dayNameDiv = document.createElement('div');
      dayNameDiv.textContent = day;
      dayNameDiv.classList.add('day-name');
      calendarGrid.appendChild(dayNameDiv);
    });

    const firstDay = new Date(year, month, 1).getDay();
    const lastDate = new Date(year, month + 1, 0).getDate();

    // Fill blank slots before first day
    for (let i = 0; i < firstDay; i++) {
      const emptyDiv = document.createElement('div');
      calendarGrid.appendChild(emptyDiv);
    }

    // Fill days
    for (let date = 1; date <= lastDate; date++) {
      const dayDiv = document.createElement('div');
      const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;

      // Highlight today
      const today = new Date();
      if (date === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
        dayDiv.classList.add('today');
      }

      // Day number on top left
      const dayNumber = document.createElement('strong');
      dayNumber.textContent = date;
      dayDiv.appendChild(dayNumber);

      // Show events on that day
      if (events[dateString]) {
        events[dateString].forEach(ev => {
          const evDiv = document.createElement('div');
          evDiv.classList.add('event');
          evDiv.title = ev.description;
          evDiv.textContent = ev.title;
          dayDiv.appendChild(evDiv);
        });
      }

      calendarGrid.appendChild(dayDiv);
    }
  }

  function prevMonth() {
    currentMonth--;
    if (currentMonth < 0) {
      currentMonth = 11;
      currentYear--;
    }
    renderCalendar(currentMonth, currentYear);
  }

  function nextMonth() {
    currentMonth++;
    if (currentMonth > 11) {
      currentMonth = 0;
      currentYear++;
    }
    renderCalendar(currentMonth, currentYear);
  }

  renderCalendar(currentMonth, currentYear);
</script>

</body>
</html>
