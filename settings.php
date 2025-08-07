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

// Handle profile update
if (isset($_POST['update_profile'])) {
    $newName = trim($_POST['name']);
    $newEmail = trim($_POST['email']);

    if (!empty($newName) && !empty($newEmail)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $newName, $newEmail, $user_id);
        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
            $name = $newName;
            $email = $newEmail;
        } else {
            $error = "Failed to update profile.";
        }
        $stmt->close();
    } else {
        $error = "All fields are required.";
    }
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Settings - Healthcare System</title>
<style>
  /* Reset some default */
  * {
    box-sizing: border-box;
  }

  body, html {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: #f4f6f9;
    height: 100%;
  }

  /* Sidebar styles (should match your sidebar.php) */
  .sidebar {
    width: 220px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background-color: #0056b3;
    padding: 30px 20px;
    color: #fff;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  /* Main content area, with left margin for sidebar */
  .main-content {
    margin-left: 220px; /* same width as sidebar */
    padding: 40px 30px;
    min-height: 100vh;
  }

  h2 {
    color: #333;
    text-align: center;
    margin-bottom: 20px;
  }

  .forms-container {
    display: flex;
    gap: 40px;
    justify-content: center;
    flex-wrap: wrap;
  }

  form {
    background: white;
    padding: 25px 30px;
    border-radius: 8px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    width: 450px;
  }

  form h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #007bff;
    font-size: 20px;
    text-align: center;
  }

  label {
    display: block;
    margin-top: 15px;
    font-weight: 600;
    color: #333;
    font-size: 15px;
  }

  input[type="text"],
  input[type="email"],
  input[type="password"] {
    width: 100%;
    padding: 10px 12px;
    margin-top: 6px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
  }

  button {
    width: 100%;
    padding: 12px 0;
    margin-top: 25px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  button:hover {
    background-color: #0056b3;
  }

  .success, .error {
    max-width: 900px;
    margin: 15px auto 30px auto;
    padding: 12px 20px;
    font-size: 15px;
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

  /* Responsive */
  @media (max-width: 768px) {
    .sidebar {
      position: relative;
      width: 100%;
      height: auto;
      padding: 20px 10px;
      text-align: center;
    }
    .main-content {
      margin-left: 0;
      padding: 20px 10px;
    }
    .forms-container {
      flex-direction: column;
      gap: 30px;
    }
    form {
      width: 100%;
      padding: 20px;
    }
  }
</style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
  <h2>Settings</h2>

  <!-- Success/Error messages here, example: -->
  <!-- <div class="success">Profile updated successfully!</div> -->
  <!-- <div class="error">Error updating password.</div> -->

  <div class="forms-container">
    <!-- Update Profile Form -->
    <form method="POST">
      <h3>Update Profile</h3>
      <label for="name">Name:</label>
      <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($name ?? ''); ?>">

      <label for="email">Email:</label>
      <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">

      <button type="submit" name="update_profile">Update Profile</button>
    </form>

    <!-- Change Password Form -->
    <form method="POST">
      <h3>Change Password</h3>
      <label for="current_password">Current Password:</label>
      <input type="password" id="current_password" name="current_password" required>

      <label for="new_password">New Password:</label>
      <input type="password" id="new_password" name="new_password" required>

      <label for="confirm_password">Confirm New Password:</label>
      <input type="password" id="confirm_password" name="confirm_password" required>

      <button type="submit" name="change_password">Change Password</button>
    </form>
  </div>
</div>

</body>
</html>
