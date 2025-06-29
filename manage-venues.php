<?php
session_start();

if (!isset($_SESSION['organizer_id'])) {
    header("Location: organizer-login.php");  // Adjust if your login page has a different name
    exit();
}

$user_id = $_SESSION['organizer_id'];
$username = $_SESSION['organizer_name'] ?? 'Organizer';

require_once("db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    header("Location: manage-venues.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM venues WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage-venues.php");
    exit();
}

$venues = $conn->query("SELECT * FROM venues ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Venues</title>
    <style>
        /* Full page background */
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
          background: rgba(14, 14, 13, 0.65);
          z-index: -1;
        }

        nav { background:#bfa58c; padding:10px; color:#fff; font-weight:bold; margin-bottom: 20px;}
        nav a { color:#fff; text-decoration:none; margin-right:15px; }
        form, table { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 10px; }
        input { width: 100%; padding: 8px; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left;}
        button, .btn { padding: 6px 10px; border: none; border-radius: 5px; background: #bfa58c; color: white; cursor: pointer; }
        .btn-danger { background: #d9534f; }
    </style>
</head>
<body>

<nav>
    Welcome, <?= htmlspecialchars($username) ?> |
    <a href="manage-events.php">Manage Events</a>
    <a href="manage-venues.php">Manage Venues</a>
    <a href="logout.php">Logout</a>
</nav>

<h2>Add / Edit Venue</h2>
<form method="POST" id="venueForm">
    <input type="hidden" name="venue_id" id="venue_id">
    <label>Name</label>
    <input type="text" name="name" id="name" required>

    <label>Location</label>
    <input type="text" name="location" id="location">

    <button type="submit">Save Venue</button>
</form>

<h2>All Venues</h2>
<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Location</th>
        <th>Created At</th>
        <th>Actions</th>
    </tr>
    <?php while ($venue = $venues->fetch_assoc()): ?>
    <tr>
        <td><?= $venue['id'] ?></td>
        <td><?= htmlspecialchars($venue['name']) ?></td>
        <td><?= htmlspecialchars($venue['location']) ?></td>
        <td><?= $venue['created_at'] ?></td>
        <td>
            <button class="btn" onclick='editVenue(<?= json_encode($venue) ?>)'>Edit</button>
            <a class="btn btn-danger" href="?delete=<?= $venue['id'] ?>" onclick="return confirm('Delete this venue?')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<script>
function editVenue(venue) {
    document.getElementById('venue_id').value = venue.id;
    document.getElementById('name').value = venue.name;
    document.getElementById('location').value = venue.location;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

</body>
</html>
