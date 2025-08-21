<?php
session_start();
require 'connect.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $error = "Please enter your registered email.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id);
            $stmt->fetch();

            // Generate secure token
            $token = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration

            // Store token & expiry in DB
            $stmt2 = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
            $stmt2->bind_param("ssi", $token, $expires, $user_id);
            $stmt2->execute();
            $stmt2->close();

            // Send reset email
            $resetLink = "http://yourdomain.com/reset_password.php?token=$token";
            $subject = "Password Reset Request";
            $body = "Hi,\n\nClick the link below to reset your password:\n$resetLink\n\nThis link expires in 1 hour.";
            $headers = "From: no-reply@yourdomain.com\r\n";

            if (mail($email, $subject, $body, $headers)) {
                $message = "Password reset link sent to your email.";
            } else {
                $error = "Failed to send email. Try again later.";
            }

        } else {
            $error = "Email not found in our records.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }

        body {
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }

        .forgot-container {
            background:#fff;
            padding:30px 40px;
            border-radius:12px;
            box-shadow:0px 8px 25px rgba(0,0,0,0.15);
            width:360px;
            text-align:center;
        }

        .forgot-container h2 {
            margin-bottom:20px;
            font-size:24px;
            color:#333;
        }

        .forgot-container input {
            width:100%;
            padding:10px 12px;
            margin-bottom:15px;
            border:1px solid #ddd;
            border-radius:8px;
            outline:none;
            transition:0.3s;
        }

        .forgot-container input:focus {
            border-color:#4facfe;
            box-shadow:0px 0px 6px rgba(79,172,254,0.6);
        }

        .forgot-container button {
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

        .forgot-container button:hover { background:#008cff; }

        .message { color:green; font-weight:bold; margin-bottom:10px; }
        .error { color:red; font-weight:bold; margin-bottom:10px; }

        a { color:#4facfe; text-decoration:none; font-weight:bold; }
        a:hover { text-decoration:underline; }

        p.back { margin-top:15px; font-size:14px; }
    </style>
</head>
<body>

<div class="forgot-container">
    <h2>Forgot Password</h2>

    <?php if ($message) echo "<p class='message'>$message</p>"; ?>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>

    <form method="POST" action="">
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Send Reset Link</button>
    </form>

    <p class="back"><a href="login.php">Back to login</a></p>
</div>

</body>
</html>
