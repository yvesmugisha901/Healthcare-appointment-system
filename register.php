<?php
session_start();
require 'connect.php';

$name = $email = $role = '';
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Basic validation
    if (empty($name) || empty($email) || empty($password) || !in_array($role, ['patient', 'doctor'])) {
        $error = 'Please fill in all required fields correctly.';
    } else {
        // Escape inputs
        $name = $conn->real_escape_string($name);
        $email = $conn->real_escape_string($email);
        $role = $conn->real_escape_string($role);

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Email is already registered. Please use another or login.';
        } else {
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $insertStmt->bind_param("ssss", $name, $email, $passwordHash, $role);

            if ($insertStmt->execute()) {
                $success = 'Registration successful! You can now <a href="login.php">login here</a>.';
                $name = $email = $role = '';
            } else {
                $error = 'Error: ' . $conn->error;
            }

            $insertStmt->close();
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
  <title>Register - Healthcare Appointment System</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f4f8;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .register-container {
      background: white;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      width: 360px;
    }
    h2 {
      text-align: center;
      margin-bottom: 1.5rem;
    }
    input, select {
      width: 100%;
      padding: 0.5rem;
      margin: 0.5rem 0 1rem 0;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
      font-size: 1rem;
    }
    button {
      width: 100%;
      padding: 0.7rem;
      background-color: #007BFF;
      border: none;
      color: white;
      font-weight: bold;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1rem;
      transition: background-color 0.3s ease;
    }
    button:hover {
      background-color: #0056b3;
    }
    .login-link {
      margin-top: 1rem;
      text-align: center;
      font-size: 0.9rem;
    }
    .login-link a {
      color: #007BFF;
      text-decoration: none;
      font-weight: 600;
    }
    .login-link a:hover {
      text-decoration: underline;
    }
    .error {
      color: #d9534f;
      text-align: center;
      margin-bottom: 1rem;
      font-weight: 600;
    }
    .success {
      color: #28a745;
      text-align: center;
      margin-bottom: 1rem;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="register-container">
    <h2>Create an Account</h2>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="text" name="name" placeholder="Full Name" required value="<?= htmlspecialchars($name) ?>" />
      <input type="email" name="email" placeholder="Email Address" required value="<?= htmlspecialchars($email) ?>" />
      <input type="password" name="password" placeholder="Password" required />
      <select name="role" required>
        <option value="" disabled <?= !$role ? 'selected' : '' ?>>Select Role</option>
        <option value="patient" <?= $role === 'patient' ? 'selected' : '' ?>>Patient</option>
        <option value="doctor" <?= $role === 'doctor' ? 'selected' : '' ?>>Doctor</option>
      </select>
      <button type="submit">Sign Up</button>
    </form>

    <div class="login-link">
      Already have an account? <a href="login.php">Login here</a>
    </div>
  </div>
</body>
</html>
