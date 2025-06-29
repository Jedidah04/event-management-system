<?php
require_once("db.php");
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $organization = trim($_POST['organization'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $role_position = trim($_POST['role_position'] ?? '');
    $agreement = $_POST['agreement'] ?? '';

    if (!$fullname || !$email || !$password || !$confirm_password) {
        $message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
        $message = "Invalid website URL format.";
    } elseif (!preg_match('/^\+?\d{1,15}$/', $phone)) {
        $message = "Invalid phone number. Must be up to 15 digits, digits only, and optional '+' at the start.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (!$agreement) {
        $message = "You must accept the terms and conditions.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM organizers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "Email is already registered.";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO organizers (fullname, email, password, phone, organization, website, address, bio, role_position) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $fullname, $email, $passwordHash, $phone, $organization, $website, $address, $bio, $role_position);
            if ($stmt->execute()) {
                header("Location: organizer-login.php");
                exit();
            } else {
                $message = "Error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Organizer Registration</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1470&q=80') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            color: #333;
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

        form {
            position: relative;
            z-index: 1;
            max-width: 650px;
            margin: 60px auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 35px 45px;
            border-radius: 15px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.4);
        }

        h2 {
            text-align: center;
            color: #5a4c3c;
            margin-bottom: 25px;
            font-size: 2rem;
        }

        label {
            display: block;
            margin-top: 12px;
            color: #5a4c3c;
            font-weight: 600;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        input[type="url"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            margin-top: 15px;
        }

        .checkbox-container input {
            width: auto;
            margin-right: 10px;
        }

        button[type="submit"] {
            margin-top: 25px;
            padding: 14px;
            background: #bfa58c;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 10px;
            font-size: 1.1rem;
            width: 100%;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        button[type="submit"]:hover {
            background: #a78664;
        }

        .message {
            color: red;
            margin-bottom: 15px;
            font-weight: 600;
            text-align: center;
        }

        .signup-link {
            margin-top: 15px;
            text-align: center;
            font-size: 0.9rem;
            color: #5a4c3c;
        }

        .signup-link a {
            color: #bfa58c;
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .required {
            color: red;
        }

        .toggle-btn {
            position: absolute;
            right: 10px;
            top: 10px;
            background: none;
            border: none;
            font-size: 1rem;
            cursor: pointer;
        }

        .password-wrapper {
            position: relative;
        }

        #password-requirements {
            font-size: 0.85rem;
            padding-left: 20px;
            color: #555;
        }
    </style>
</head>
<body>

<form method="POST" novalidate>
    <h2>Organizer Registration</h2>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <label for="fullname">Full Name <span class="required">*</span></label>
    <input type="text" id="fullname" name="fullname" required value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">

    <label for="email">Email Address <span class="required">*</span></label>
    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

    <label for="password">Password <span class="required">*</span></label>
    <div class="password-wrapper">
        <input type="password" id="password" name="password" required oninput="validatePassword()" style="padding-right: 40px;">
        <button type="button" class="toggle-btn" onclick="togglePassword('password')">👁️</button>
    </div>
    <ul id="password-requirements">
        <li id="length" style="color: red;">At least 8 characters</li>
        <li id="uppercase" style="color: red;">At least one uppercase letter</li>
        <li id="lowercase" style="color: red;">At least one lowercase letter</li>
        <li id="number" style="color: red;">At least one number</li>
        <li id="special" style="color: red;">At least one special character</li>
    </ul>

    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
    <div class="password-wrapper">
        <input type="password" id="confirm_password" name="confirm_password" required style="padding-right: 40px;">
        <button type="button" class="toggle-btn" onclick="togglePassword('confirm_password')">👁️</button>
    </div>

    <label for="phone">Phone Number</label>
    <input type="tel" id="phone" name="phone" pattern="^\+?\d{1,15}$" title="Phone number must be up to 15 digits with an optional '+' at the start" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

    <label for="organization">Organization Name</label>
    <input type="text" id="organization" name="organization" value="<?= htmlspecialchars($_POST['organization'] ?? '') ?>">

    <label for="website">Website URL</label>
    <input type="url" id="website" name="website" value="<?= htmlspecialchars($_POST['website'] ?? '') ?>">

    <label for="address">Address</label>
    <textarea id="address" name="address"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>

    <label for="bio">Bio / Description</label>
    <textarea id="bio" name="bio"><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>

    <label for="role_position">Role / Position</label>
    <input type="text" id="role_position" name="role_position" value="<?= htmlspecialchars($_POST['role_position'] ?? '') ?>">

    <div class="checkbox-container">
        <input type="checkbox" id="agreement" name="agreement" value="1" <?= isset($_POST['agreement']) ? 'checked' : '' ?> required>
        <label for="agreement">I agree to the <a href="terms.php" target="_blank">terms and conditions</a> <span class="required">*</span></label>
    </div>

    <button type="submit">Register</button>

    <div class="signup-link">
        Already have an account? <a href="organizer-login.php">Login here</a>
    </div>
</form>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    field.type = field.type === "password" ? "text" : "password";
}

function validatePassword() {
    const password = document.getElementById('password').value;
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /\d/.test(password),
        special: /[!@#\$%\^&\*\(\)_\+\-=\[\]\{\};:'"\\|,.<>\/?]/.test(password)
    };

    for (const key in requirements) {
        document.getElementById(key).style.color = requirements[key] ? 'green' : 'red';
    }
}

// Ensure '+' shows on focus and only digits are typed, limit to 15 total
const phoneInput = document.getElementById('phone');
phoneInput.addEventListener('focus', () => {
    if (!phoneInput.value.startsWith('+')) {
        phoneInput.value = '+';
    }
});

phoneInput.addEventListener('input', () => {
    let cleaned = phoneInput.value.replace(/[^\d+]/g, '');
    if (cleaned.startsWith('+')) {
        cleaned = '+' + cleaned.slice(1).replace(/\D/g, '').slice(0, 15);
    } else {
        cleaned = cleaned.replace(/\D/g, '').slice(0, 15);
    }
    phoneInput.value = cleaned;
});
</script>

</body>
</html>
