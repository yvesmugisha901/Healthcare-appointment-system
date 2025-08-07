<?php
session_start();
require 'connect.php';

// Check if user ID is provided
if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$userId = $_GET['id'];

// Fetch user data
$stmt = $conn->prepare("SELECT name, email, role, specialization FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "User not found.";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $specialization = $_POST['specialization'];

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, specialization = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $email, $role, $specialization, $userId);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Edit User</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .form-container {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      width: 400px;
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
    }
    input, select {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button {
      width: 100%;
      padding: 10px;
      background-color: #007BFF;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
    }
    button:hover {
      background-color: #0056b3;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Edit User</h2>
    <form method="POST">
      <label for="name">Full Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($user['name']); ?>" required />

      <label for="email">Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required />

      <label for="role">Role</label>
      <select name="role" required>
        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
        <option value="doctor" <?= $user['role'] === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
        <option value="patient" <?= $user['role'] === 'patient' ? 'selected' : ''; ?>>Patient</option>
      </select>

      <label for="specialization">Specialization (if doctor)</label>
      <input type="text" name="specialization" value="<?= htmlspecialchars($user['specialization']); ?>" />

      <button type="submit">Update User</button>
    </form>
  </div>
</body>
</html>
