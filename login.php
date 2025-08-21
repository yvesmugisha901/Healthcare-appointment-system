<?php
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
            // Check if 2FA is enabled
            if (!empty($user['twofa_enabled']) && $user['twofa_enabled'] == 1) {
                // Generate 2FA code
                $code = rand(100000, 999999); // 6-digit code
                $_SESSION['temp_user_id'] = $user['id'];
                $_SESSION['temp_user_role'] = $user['role'];
                $_SESSION['temp_user_name'] = $user['name'];
                $_SESSION['twofa_code'] = $code;
                $_SESSION['twofa_expires'] = time() + 300; // expires in 5 min

                // Send code via email
                $to = $user['email'];
                $subject = "Your 2FA Verification Code";
                $message = "Your verification code is: $code. It will expire in 5 minutes.";
                $headers = "From: no-reply@healthcare.com\r\n";
                mail($to, $subject, $message, $headers);

                header("Location: twofa_verify.php");
                exit;
            } else {
                // Login successful without 2FA
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                switch ($user['role']) {
                    case 'patient':
                        header("Location: patient_dashboard.php");
                        break;
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
            }
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
    <style>
        /* Reset */
        * { margin:0; padding:0; box-sizing:border-box; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .login-container { background: #fff; padding: 30px 40px; border-radius: 12px; box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.15); width: 350px; text-align: center; }
        .login-container h2 { margin-bottom: 20px; font-size: 24px; color: #333; }
        .login-container label { display: block; text-align: left; margin: 12px 0 6px; font-size: 14px; font-weight: bold; color: #555; }
        .login-container input { width: 100%; padding: 10px 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; outline: none; transition:0.3s; }
        .login-container input:focus { border-color: #4facfe; box-shadow:0px 0px 6px rgba(79,172,254,0.6); }
        .login-container button { width: 100%; padding: 12px; background: #4facfe; border: none; border-radius: 8px; color: white; font-size: 16px; font-weight: bold; cursor: pointer; transition:0.3s; }
        .login-container button:hover { background: #008cff; }
        .success { color: green; font-weight: bold; margin-bottom: 10px; }
        .error { color: red; font-weight: bold; margin-bottom: 10px; }
        .register-text { margin-top: 15px; font-size: 14px; color: #555; }
        .register-text a { color: #4facfe; font-weight: bold; text-decoration: none; }
        .register-text a:hover { text-decoration: underline; }
        .forgot-text { margin-top: 10px; font-size: 13px; }
        .forgot-text a { color: #4facfe; text-decoration: none; }
        .forgot-text a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="login-container">
    <h2>Login</h2>

    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <p class="forgot-text"><a href="forgot_password.php">Forgot Password?</a></p>

    <p class="register-text">Don't have an account? 
        <a href="register.php">Register here</a>
    </p>
</div>
</body>
</html>
