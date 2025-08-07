<?php
session_start();
require 'connect.php';

// Check if logged in as doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit;
}

$doctor_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current doctor info
$query = "SELECT name, email, specialization FROM users WHERE id = ? AND role = 'doctor'";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Doctor not found.");
}

$doctor = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');

    if (empty($name) || empty($email) || empty($specialization)) {
        $error = "Please fill all fields.";
    } else {
        $updateQuery = "UPDATE users SET name = ?, email = ?, specialization = ? WHERE id = ? AND role = 'doctor'";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('sssi', $name, $email, $specialization, $doctor_id);
        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
            $doctor['name'] = $name;
            $doctor['email'] = $email;
            $doctor['specialization'] = $specialization;
        } else {
            $error = "Failed to update profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Doctor Profile</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #f0f4f8;
    padding: 20px;
  }
  .container {
    max-width: 480px;
    margin: auto;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }
  h2 {
    text-align: center;
    color: #004080;
  }
  label {
    display: block;
    margin-top: 12px;
  }
  input[type="text"], input[type="email"] {
    width: 100%;
    padding: 8px;
    margin-top: 4px;
    border: 1px solid #ccc;
    border-radius: 4px;
  }
  button {
    margin-top: 20px;
    width: 100%;
    padding: 10px;
    background: #004080;
    color: white;
    border: none;
    border-radius: 5px;
    font-weight: bold;
    cursor: pointer;
  }
  button:hover {
    background: #003366;
  }
  .message {
    text-align: center;
    margin-top: 10px;
  }
  .error {
    color: red;
  }
  .success {
    color: green;
  }
</style>
</head>
<body>
  <div class="container">
    <h2>My Profile</h2>

    <?php if ($error): ?>
      <div class="message error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="message success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <label for="name">Full Name</label>
      <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($doctor['name']); ?>" required />

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" required />

      <label for="specialization">Specialization</label>
      <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required />

      <button type="submit">Update Profile</button>
    </form>
  </div>
</body>
</html>
