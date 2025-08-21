<?php
session_start();
require 'connect.php';

$error = '';

// Check if temp session exists
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['twofa_code'])) {
    header("Location: login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    if (empty($code)) {
        $error = "Please enter the verification code.";
    } else {
        // Check expiration
        if (time() > $_SESSION['twofa_expires']) {
            $error = "Code expired. Please login again.";
            // Clear temp session
            session_unset();
            session_destroy();
        } elseif ($code == $_SESSION['twofa_code']) {
            // 2FA success - log user in
            $_SESSION['user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['role'] = $_SESSION['temp_user_role'];
            $_SESSION['name'] = $_SESSION['temp_user_name'];

            // Clear temp session variables
            unset($_SESSION['temp_user_id'], $_SESSION['temp_user_role'], $_SESSION['temp_user_name'], $_SESSION['twofa_code'], $_SESSION['twofa_expires']);

            // Redirect according to role
            switch ($_SESSION['role']) {
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
        } else {
            $error = "Invalid verification code.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>2FA Verification</title>
    <style>
        body {
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            font-family:"Segoe UI", sans-serif;
        }
        .twofa-container {
            background:#fff;
            padding:30px 40px;
            border-radius:12px;
            box-shadow:0px 8px 25px rgba(0,0,0,0.15);
            width:360px;
            text-align:center;
        }
        .twofa-container h2 { margin-bottom:20px; font-size:24px; color:#333; }
        .twofa-container input {
            width:100%;
            padding:10px 12px;
            margin-bottom:15px;
            border:1px solid #ddd;
            border-radius:8px;
            outline:none;
            transition:0.3s;
        }
        .twofa-container input:focus {
            border-color:#4facfe;
            box-shadow:0px 0px 6px rgba(79,172,254,0.6);
        }
        .twofa-container button {
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
        .twofa-container button:hover { background:#008cff; }
        .error { color:red; font-weight:bold; margin-bottom:10px; }
    </style>
</head>
<body>
<div class="twofa-container">
    <h2>Two-Factor Verification</h2>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="code" placeholder="Enter 6-digit code" maxlength="6" required>
        <button type="submit">Verify</button>
    </form>
</div>
</body>
</html>
