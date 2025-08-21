<?php
session_start();
require 'connect.php';

$message = '';
$error = '';
$showForm = false;

// Check token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = "Invalid or missing token.";
} else {
    // Check if token exists and is not expired
    $stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $expires);
        $stmt->fetch();

        if (strtotime($expires) < time()) {
            $error = "This reset link has expired. Please request a new one.";
        } else {
            $showForm = true;
        }
    } else {
        $error = "Invalid reset token.";
    }
    $stmt->close();
}

// Handle new password submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirmPassword)) {
        $error = "Please fill in both fields.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Update password and clear token
        $stmt2 = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
        $stmt2->bind_param("si", $passwordHash, $user_id);
        if ($stmt2->execute()) {
            $message = "Your password has been reset successfully. <a href='login.php'>Login here</a>.";
            $showForm = false;
        } else {
            $error = "Failed to reset password. Try again.";
        }
        $stmt2->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }

body {
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
    background: linear-gradient(135deg, #4facfe, #00f2fe);
}

.reset-container {
    background:#fff;
    padding:30px 40px;
    border-radius:12px;
    box-shadow:0px 8px 25px rgba(0,0,0,0.15);
    width:360px;
    text-align:center;
}

.reset-container h2 {
    margin-bottom:20px;
    font-size:24px;
    color:#333;
}

.reset-container input {
    width:100%;
    padding:10px 12px;
    margin-bottom:15px;
    border:1px solid #ddd;
    border-radius:8px;
    outline:none;
    transition:0.3s;
}

.reset-container input:focus {
    border-color:#4facfe;
    box-shadow:0px 0px 6px rgba(79,172,254,0.6);
}

.reset-container button {
    width:100%;
    padding:12px;
    background:#4facfe;
    border:none;
    border-radius:8px;
    color:white;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

.reset-container button:hover { background:#008cff; }

.message { color:green; font-weight:bold; margin-bottom:10px; }
.error { color:red; font-weight:bold; margin-bottom:10px; }

a { color:#4facfe; text-decoration:none; font-weight:bold; }
a:hover { text-decoration:underline; }

p.back { margin-top:15px; font-size:14px; }
</style>
</head>
<body>

<div class="reset-container">
<h2>Reset Password</h2>

<?php if ($message) echo "<p class='message'>$message</p>"; ?>
<?php if ($error) echo "<p class='error'>$error</p>"; ?>

<?php if ($showForm): ?>
<form method="POST" action="">
    <input type="password" name="password" placeholder="New Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="submit">Reset Password</button>
</form>
<?php endif; ?>

<p class="back"><a href="login.php">Back to login</a></p>
</div>

</body>
</html>
