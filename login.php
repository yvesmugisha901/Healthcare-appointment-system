<?php
session_start();
require 'connect.php';

$error = '';
$success = '';

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            if (!empty($user['twofa_enabled']) && $user['twofa_enabled'] == 1) {
                $code = rand(100000, 999999);
                $_SESSION['temp_user_id'] = $user['id'];
                $_SESSION['temp_user_role'] = $user['role'];
                $_SESSION['temp_user_name'] = $user['name'];
                $_SESSION['twofa_code'] = $code;
                $_SESSION['twofa_expires'] = time() + 300;

                $to = $user['email'];
                $subject = "Your 2FA Verification Code";
                $message = "Your verification code is: $code. It will expire in 5 minutes.";
                $headers = "From: no-reply@healthcare.com\r\n";
                mail($to, $subject, $message, $headers);

                header("Location: twofa_verify.php");
                exit;
            } else {
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
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - MedConnect</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary:#2a9d8f;
    --primary-dark:#1d7870;
    --neutral-dark:#264653;
    --neutral-medium:#6c757d;
    --neutral-light:#f8f9fa;
    --white:#fff;
    --shadow-md: 0 8px 20px rgba(0,0,0,0.12);
    --radius:12px;
    --transition: all 0.3s ease;
}

* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }

body {
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:#e8f6f4;
}

.login-container {
    background: var(--white);
    padding: 45px 40px;
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    width: 400px;
    text-align:center;
    transition: var(--transition);
}

.login-container:hover { transform: translateY(-4px); }

.login-container h2 {
    font-size:28px;
    color: var(--neutral-dark);
    margin-bottom:25px;
    font-weight:700;
}

.login-container form input {
    width:100%;
    padding:14px 15px;
    margin-bottom:18px;
    border:1px solid #ccc;
    border-radius: var(--radius);
    outline:none;
    font-size:15px;
    transition: var(--transition);
}

.login-container form input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 8px rgba(42,157,143,0.25);
}

.login-container button {
    width:100%;
    padding:14px;
    border:none;
    border-radius: var(--radius);
    background: var(--primary);
    color: var(--white);
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    transition: var(--transition);
}

.login-container button:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow:0 6px 14px rgba(0,0,0,0.12);
}

.error {
    color:#d9534f;
    font-weight:bold;
    margin-bottom:15px;
    background:#fdecea;
    padding:10px;
    border-radius: var(--radius);
}

.success {
    color:#28a745;
    font-weight:bold;
    margin-bottom:15px;
    background:#e6f4ea;
    padding:10px;
    border-radius: var(--radius);
}

.register-text, .forgot-text {
    font-size:14px;
    margin-top:15px;
    color: var(--neutral-medium);
}

.register-text a, .forgot-text a {
    color: var(--primary);
    font-weight:bold;
    text-decoration:none;
}

.register-text a:hover, .forgot-text a:hover { text-decoration:underline; }

@media(max-width:480px){
    .login-container { width:90%; padding:30px; }
}
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
    <input type="email" name="email" placeholder="Email Address" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit"><i class="fas fa-sign-in-alt"></i> Login</button>
</form>

<p class="forgot-text"><a href="forgot_password.php">Forgot Password?</a></p>
<p class="register-text">Don't have an account? <a href="register.php">Register here</a></p>
</div>

</body>
</html>
