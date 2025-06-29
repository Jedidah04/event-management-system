<?php
session_start();
require_once("db.php"); // your database connection

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic input sanitization
    $name = trim($_POST['name'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $is_paid = isset($_POST['is_paid']) && $_POST['is_paid'] === '1' ? 1 : 0;
    $price = $is_paid ? floatval($_POST['price'] ?? 0) : 0;

    // Validate inputs
    if (empty($name)) {
        $message = "Event name is required.";
    } elseif (empty($date)) {
        $message = "Event date is required.";
    } elseif ($is_paid && $price <= 0) {
        $message = "Price must be greater than zero for paid events.";
    } else {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO events (name, date, is_paid, price) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            $message = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        } else {
            $stmt->bind_param("ssid", $name, $date, $is_paid, $price);
            if ($stmt->execute()) {
                $message = "Event created successfully!";
            } else {
                $message = "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Create Event</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 2rem auto; padding: 1rem; }
        label { display: block; margin-top: 1rem; }
        input[type="text"], input[type="date"], input[type="number"] {
            padding: 0.5rem; width: 100%; box-sizing: border-box;
        }
        #price-container { margin-top: 0.5rem; }
        .message { margin-top: 1rem; font-weight: bold; color: green; }
        .error { color: red; }
    </style>
</head>
<body>

<h2>Create New Event</h2>

<?php if ($message): ?>
    <p class="message <?= strpos($message, 'successfully') !== false ? '' : 'error' ?>"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="POST" action="">
    <label>
        Event Name:
        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required />
    </label>

    <label>
        Event Date:
        <input type="date" name="date" value="<?= htmlspecialchars($_POST['date'] ?? '') ?>" required />
    </label>

    <label>
        <input type="radio" name="is_paid" value="0" <?= (!isset($_POST['is_paid']) || $_POST['is_paid'] === '0') ? 'checked' : '' ?> />
        Free Event
    </label>
    <label>
        <input type="radio" name="is_paid" value="1" <?= (isset($_POST['is_paid']) && $_POST['is_paid'] === '1') ? 'checked' : '' ?> />
        Paid Event
    </label>

    <div id="price-container" style="display:none;">
        <label>
            Price:
            <input type="number" step="0.01" min="0" name="price" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" placeholder="Enter price" />
        </label>
    </div>

    <button type="submit" style="margin-top: 1rem;">Create Event</button>
</form>

<script>
    const paidRadio = document.querySelector('input[name="is_paid"][value="1"]');
    const freeRadio = document.querySelector('input[name="is_paid"][value="0"]');
    const priceContainer = document.getElementById('price-container');

    function togglePriceInput() {
        if (paidRadio.checked) {
            priceContainer.style.display = 'block';
        } else {
            priceContainer.style.display = 'none';
        }
    }

    paidRadio.addEventListener('change', togglePriceInput);
    freeRadio.addEventListener('change', togglePriceInput);

    // Initialize on page load
    togglePriceInput();
</script>

</body>
</html>
