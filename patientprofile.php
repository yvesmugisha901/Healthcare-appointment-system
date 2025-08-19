<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if (empty($name) || empty($email)) {
        $message = "Name and Email cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $user_id);
        if ($stmt->execute()) {
            $message = "Profile updated successfully.";
        } else {
            $message = "Error updating profile.";
        }
        $stmt->close();
    }
}

// Fetch current user info
$stmt = $conn->prepare("SELECT name, email, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Profile</title>
<style>
  body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f6f9;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
  }

  .profile-container {
    background: #fff;
    width: 100%;
    max-width: 500px;
    margin: 50px 20px;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
  }

  .profile-container h2 {
    text-align: center;
    color: #007bff;
    margin-bottom: 25px;
  }

  form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
  }

  form input[type="text"],
  form input[type="email"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    box-sizing: border-box;
    transition: border-color 0.3s;
  }

  form input[type="text"]:focus,
  form input[type="email"]:focus {
    border-color: #007bff;
    outline: none;
  }

  .info-field {
    background: #e9ecef;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 20px;
    color: #555;
  }

  button {
    width: 100%;
    padding: 12px;
    background-color: #007bff;
    border: none;
    border-radius: 6px;
    color: white;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background-color 0.3s;
  }

  button:hover {
    background-color: #0056b3;
  }

  .message {
    margin-bottom: 20px;
    text-align: center;
    font-weight: bold;
    padding: 10px;
    border-radius: 6px;
  }

  .message.success {
    background-color: #d4edda;
    color: #155724;
  }

  .message.error {
    background-color: #f8d7da;
    color: #721c24;
  }

  .logout-link {
    display: block;
    text-align: center;
    margin-top: 25px;
    font-size: 0.95rem;
    color: #007bff;
    text-decoration: none;
    transition: color 0.3s;
  }

  .logout-link:hover {
    color: #0056b3;
  }

  @media (max-width: 600px) {
    .profile-container {
      margin: 20px 10px;
      padding: 20px;
    }
  }
</style>
</head>
<body>

<div class="profile-container">
  <h2>My Profile</h2>

  <?php if ($message): ?>
    <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <label for="name">Name</label>
    <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

    <label>Role</label>
    <div class="info-field"><?= htmlspecialchars($user['role']) ?></div>

    <label>Member Since</label>
    <div class="info-field"><?= htmlspecialchars(date('F j, Y', strtotime($user['created_at']))) ?></div>

    <button type="submit">Update Profile</button>
  </form>

  <a href="logout.php" class="logout-link">Logout</a>
</div>

</body>
</html>
