<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = $error = '';

// Fetch current user info
$stmt = $conn->prepare("SELECT name, email, password, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $hashedPassword, $role);
$stmt->fetch();
$stmt->close();

// Handle password change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $hashedPassword)) {
        $error = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters.";
    } else {
        $newHashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $newHashed, $user_id);
        if ($stmt->execute()) {
            $success = "Password changed successfully!";
        } else {
            $error = "Failed to change password.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Settings - Healthcare System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #2a9d8f;        /* main green-teal */
    --primary-dark: #1d7870;   /* hover darker green */
    --primary-light: #7fcdc3;  /* light green hover */
    --secondary: #e76f51;      /* coral accent */
    --neutral-dark: #264653;   /* main text */
    --neutral-medium: #6c757d; /* secondary text */
    --neutral-light: #f8f9fa;  /* background */
    --white: #ffffff;
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
    --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
    --radius: 10px;
    --transition: all 0.3s ease;
}

body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: var(--neutral-light);
    display: flex;
    min-height: 100vh;
    color: var(--neutral-dark);
}

/* Sidebar */
.sidebar {
    width: 220px;
    background-color: var(--primary);
    color: var(--white);
    padding: 25px 20px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}
.sidebar h2 {
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 24px;
}
.sidebar h2 i { font-size: 28px; }
.sidebar nav a {
    color: #ffffff;
    text-decoration: none;
    margin: 10px 0;
    padding: 10px 12px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    gap: 10px;
    transition: var(--transition);
}
.sidebar nav a.active,
.sidebar nav a:hover {
    background-color: var(--primary-light);
    color: var(--neutral-dark);
}

/* Main Content */
.main-content {
    flex: 1;
    padding: 30px;
    display: flex;
    flex-direction: column;
    align-items: center;
    background-color: var(--neutral-light);
}

/* Settings Styles */
h1 { color: var(--primary); margin-bottom: 30px; }

.message {
    max-width: 600px;
    margin: 10px auto 30px;
    padding: 15px 20px;
    border-radius: var(--radius);
    text-align: center;
}
.message.success { background-color: #d4f1ed; color: var(--primary-dark); border-left: 6px solid var(--primary); }
.message.error { background-color: #f8d7da; color: var(--secondary); border-left: 6px solid var(--secondary); }

.settings-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
    justify-content: space-between;
}
.card {
    background: var(--white);
    padding: 25px 30px;
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    width: 100%;
    max-width: 400px;
    box-sizing: border-box;
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
.card h2 { margin-top:0; margin-bottom:20px; font-weight:700; color: var(--primary); }

label { display: block; margin:12px 0 6px; font-weight:600; }
input[type="text"], input[type="email"], input[type="password"] {
    width:100%; padding:10px 12px; border:1px solid #ccc; border-radius:5px; font-size:1rem;
}
button {
    margin-top:20px; background-color: var(--primary); color: var(--white);
    border:none; padding:12px 0; width:100%; font-size:1rem; border-radius:5px;
    cursor:pointer; font-weight:700; transition: background 0.3s;
}
button:hover { background-color: var(--primary-dark); }

.profile-link {
    display:inline-block; margin-bottom:30px; font-weight:700; font-size:1.1rem; color:var(--primary);
}

.toggle-switch {
    position: relative; display: inline-block; width:50px; height:26px;
}
.toggle-switch input { opacity:0; width:0; height:0; }
.slider {
    position: absolute; cursor:pointer; top:0; left:0; right:0; bottom:0;
    background-color: #ccc; border-radius:26px; transition:.4s;
}
.slider:before {
    position: absolute; content:""; height:20px; width:20px; left:3px; bottom:3px;
    background-color:white; border-radius:50%; transition:.4s;
}
input:checked + .slider { background-color: var(--primary); }
input:checked + .slider:before { transform: translateX(24px); }

/* Responsive */
@media(max-width:960px) {
    .settings-grid { flex-direction: column; align-items: center; }
    .card { max-width: 100%; }
    .sidebar { width: 100%; flex-direction: row; overflow-x: auto; }
}
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <h2><i class="fa fa-stethoscope"></i> HealthSys</h2>
    <nav>
        <a href="admin_dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
        <?php if($role === 'admin'): ?>
        <a href="manage_users.php"><i class="fa fa-users"></i> Manage Users</a>
        <?php endif; ?>
        <a href="appointments.php"><i class="fa fa-calendar-check"></i> Appointments</a>
        <a href="settings.php" class="active"><i class="fa fa-cog"></i> Settings</a>
        <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>

<!-- Main Content -->
<div class="main-content">
    <h1>Settings</h1>

    <?php if ($success): ?>
        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
    <?php elseif ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <a href="patientprofile.php" class="profile-link">➡️ Edit Profile Information</a>

    <div class="settings-grid">

        <div class="card">
            <h2>Change Password</h2>
            <form method="POST" action="">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>

                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>

                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <button type="submit" name="change_password">Update Password</button>
            </form>
        </div>

        <div class="card">
            <h2>Privacy & Legal</h2>
            <p><a href="privacy_policy.php" target="_blank">Privacy Policy</a></p>
            <p><a href="terms_conditions.php" target="_blank">Terms & Conditions</a></p>
            <p><a href="help.php" target="_blank">Help & Support</a></p>
        </div>

        <div class="card">
            <h2>Notifications</h2>
            <label class="toggle-switch">
                <input type="checkbox" id="dark_mode_toggle" />
                <span class="slider"></span>
            </label>
            <span style="margin-left: 10px;">Enable Dark Mode</span>
        </div>

        <div class="card" style="text-align:center;">
            <h2>Account</h2>
            <a href="logout.php" style="color: var(--secondary); font-weight:700;">Logout</a>
        </div>

    </div>
</div>

<script>
const toggle = document.getElementById('dark_mode_toggle');
const body = document.body;

if(localStorage.getItem('darkMode') === 'enabled') {
    body.classList.add('dark');
    toggle.checked = true;
}

toggle.addEventListener('change', () => {
    if(toggle.checked) {
        body.classList.add('dark');
        localStorage.setItem('darkMode','enabled');
    } else {
        body.classList.remove('dark');
        localStorage.setItem('darkMode','disabled');
    }
});
</script>
</body>
</html>
