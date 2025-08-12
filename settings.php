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
$stmt = $conn->prepare("SELECT name, email, password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $hashedPassword);
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
<style>
  body, html {
    margin: 0; padding: 0;
    font-family: Arial, sans-serif;
    background-color: #f5f7fa;
    color: #333;
    transition: background-color 0.3s, color 0.3s;
  }
  body.dark {
    background-color: #121212;
    color: #eee;
  }
  a {
    color: #007bff;
    text-decoration: none;
  }
  a:hover {
    text-decoration: underline;
  }

  .container {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
  }
  h1 {
    text-align: center;
    margin-bottom: 40px;
  }
  .message {
    max-width: 600px;
    margin: 10px auto 30px;
    padding: 15px 20px;
    border-radius: 6px;
    text-align: center;
  }
  .success {
    background-color: #d4edda;
    color: #155724;
    border-left: 6px solid #28a745;
  }
  .error {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 6px solid #dc3545;
  }

  .settings-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 40px;
    justify-content: space-between;
  }
  .card {
    background: white;
    padding: 25px 30px;
    border-radius: 8px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 400px;
    box-sizing: border-box;
    transition: background-color 0.3s, color 0.3s;
  }
  body.dark .card {
    background: #222;
    color: #ddd;
  }
  .card h2 {
    margin-top: 0;
    margin-bottom: 20px;
    font-weight: 700;
  }
  label {
    display: block;
    margin: 12px 0 6px;
    font-weight: 600;
  }
  input[type="text"],
  input[type="email"],
  input[type="password"] {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 1rem;
  }
  body.dark input[type="text"],
  body.dark input[type="email"],
  body.dark input[type="password"] {
    background-color: #333;
    border-color: #555;
    color: #eee;
  }
  button {
    margin-top: 20px;
    background-color: #007bff;
    color: white;
    border: none;
    padding: 12px 0;
    width: 100%;
    font-size: 1rem;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 700;
  }
  button:hover {
    background-color: #0056b3;
  }

  .profile-link {
    display: inline-block;
    margin-bottom: 30px;
    font-weight: 700;
    font-size: 1.1rem;
  }

  .toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
  }
  .toggle-switch input {
    opacity: 0;
    width: 0; height: 0;
  }
  .slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc;
    border-radius: 26px;
    transition: .4s;
  }
  .slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: .4s;
  }
  input:checked + .slider {
    background-color: #007bff;
  }
  input:checked + .slider:before {
    transform: translateX(24px);
  }

  /* Responsive */
  @media (max-width: 960px) {
    .settings-grid {
      flex-direction: column;
      align-items: center;
    }
    .card {
      max-width: 100%;
    }
  }
</style>
</head>
<body>

<div class="container">

  <h1>Settings</h1>

  <?php if ($success): ?>
    <div class="message success"><?php echo htmlspecialchars($success); ?></div>
  <?php elseif ($error): ?>
    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <!-- Profile link -->
  <a href="patient_profile.php" class="profile-link">➡️ Edit Profile Information</a>

  <div class="settings-grid">

    <!-- Change Password -->
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

    <!-- Privacy & Legal -->
    <div class="card">
      <h2>Privacy & Legal</h2>
      <p><a href="privacy_policy.php" target="_blank">Privacy Policy</a></p>
      <p><a href="terms_conditions.php" target="_blank">Terms & Conditions</a></p>
      <p><a href="help.php" target="_blank">Help & Support</a></p>
    </div>

    <!-- Notifications -->
    <div class="card">
      <h2>Notifications</h2>
      <label class="toggle-switch">
        <input type="checkbox" id="dark_mode_toggle" />
        <span class="slider"></span>
      </label>
      <span style="margin-left: 10px;">Enable Dark Mode</span>
    </div>

    <!-- Logout -->
    <div class="card" style="text-align: center;">
      <h2>Account</h2>
      <a href="logout.php" style="color: #dc3545; font-weight: 700;">Logout</a>
    </div>

  </div>
</div>

<script>
  // Dark mode toggle: save preference in localStorage and apply on load
  const toggle = document.getElementById('dark_mode_toggle');
  const body = document.body;

  // Load saved preference
  if(localStorage.getItem('darkMode') === 'enabled') {
    body.classList.add('dark');
    toggle.checked = true;
  }

  toggle.addEventListener('change', () => {
    if(toggle.checked) {
      body.classList.add('dark');
      localStorage.setItem('darkMode', 'enabled');
    } else {
      body.classList.remove('dark');
      localStorage.setItem('darkMode', 'disabled');
    }
  });
</script>

</body>
</html>
