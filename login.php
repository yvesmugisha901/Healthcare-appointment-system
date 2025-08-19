<?php
// Start session
session_start();
require 'connect.php';

$error = '';
$success = '';

// Check for flash message from registration
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear after showing
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            // Redirect according to role
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin_dashboard.php");
                    break;
                case 'doctor':
                    header("Location: doctor_dash.php");
                    break;
                default:
                    header("Location: patient_dashboard.php");
                    break;
            }
            exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <h2>Login</h2>

    <?php if ($success): ?>
        <p style="color:green; font-weight:600;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:red; font-weight:600;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Login</button>
    </form>

    <p>Don't have an account? <a href="register.php">Register here</a></p>
</body>
</html>
