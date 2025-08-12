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
        $updateQuery = "UPDATE users SET name = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssi", $name, $email, $user_id);
        if ($stmt->execute()) {
            $message = "Profile updated successfully.";
        } else {
            $message = "Error updating profile.";
        }
        $stmt->close();
    }
}

// Fetch updated or current user info
$query = "SELECT name, email, role, specialization, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
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
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>My Profile</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #f0f4f8;
    margin: 0;
    padding: 40px 20px;
    display: flex;
    justify-content: center;
    min-height: 100vh;
  }
  .profile-container {
    background: white;
    max-width: 480px;
    width: 100%;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
  }
  h2 {
    text-align: center;
    margin-bottom: 25px;
  }
  form label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
  }
  form input[type="text"],
  form input[type="email"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    box-sizing: border-box;
  }
  form input[disabled] {
    background-color: #e9ecef;
    color: #555;
  }
  button {
    width: 100%;
    padding: 12px;
    background-color: #007BFF;
    border: none;
    border-radius: 4px;
    color: white;
    font-size: 1.1rem;
    cursor: pointer;
  }
  button:hover {
    background-color: #0056b3;
  }
  .message {
    margin-bottom: 20px;
    text-align: center;
    font-weight: bold;
  }
  .message.success {
    color: green;
  }
  .message.error {
    color: red;
  }
  .logout-link {
    display: block;
    text-align: center;
    margin-top: 20px;
    font-size: 0.9rem;
    color: #007BFF;
    text-decoration: none;
  }
  .logout-link:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>
  <div class="profile-container">
    <h2>My Profile</h2>

    <?php if ($message): ?>
      <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required />

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required />

      <label>Role</label>
      <input type="text" value="<?php echo htmlspecialchars($user['role']); ?>" disabled />

      <label>Specialization</label>
      <input type="text" value="<?php echo htmlspecialchars($user['specialization'] ?? 'N/A'); ?>" disabled />

      <label>Member Since</label>
      <input type="text" value="<?php echo htmlspecialchars(date('F j, Y', strtotime($user['created_at']))); ?>" disabled />

      <button type="submit">Update Profile</button>
    </form>

    <a href="logout.php" class="logout-link">Logout</a>
  </div>
</body>
</html>
