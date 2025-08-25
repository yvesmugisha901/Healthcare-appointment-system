<?php
session_start();
require 'connect.php';

$name = $email = $role = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Basic validation
    if (empty($name) || empty($email) || empty($password) || !in_array($role, ['patient', 'doctor'])) {
        $error = 'Please fill in all required fields correctly.';
    } 
    // Simple password validation (only minimum length)
    elseif(strlen($password) < 4){
        $error = 'Password must be at least 4 characters long.';
    } 
    else {
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

            // Insert user with optional 2FA column (default 0)
            $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, role, twofa_enabled) VALUES (?, ?, ?, ?, 0)");
            $insertStmt->bind_param("ssss", $name, $email, $passwordHash, $role);

            if ($insertStmt->execute()) {
                $_SESSION['success_message'] = "Registration successful! Please log in.";
                header("Location: login.php");
                exit;
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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register - Healthcare Appointment System</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }
body {
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
    background: linear-gradient(135deg, #4facfe, #00f2fe);
}
.register-container {
    background:#fff;
    padding:30px 40px;
    border-radius:12px;
    box-shadow:0px 8px 25px rgba(0,0,0,0.15);
    width:360px;
    text-align:center;
}
.register-container h2 { margin-bottom:20px; font-size:24px; color:#333; }
.register-container label {
    display:block;
    text-align:left;
    margin:12px 0 6px;
    font-size:14px;
    font-weight:bold;
    color:#555;
}
.register-container input,
.register-container select {
    width:100%;
    padding:10px 12px;
    margin-bottom:15px;
    border:1px solid #ddd;
    border-radius:8px;
    outline:none;
    transition:0.3s;
}
.register-container input:focus,
.register-container select:focus {
    border-color:#4facfe;
    box-shadow:0px 0px 6px rgba(79,172,254,0.6);
}
.register-container button {
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
.register-container button:hover { background:#008cff; }
.error { color:#d9534f; font-weight:bold; margin-bottom:10px; }
.success { color:#28a745; font-weight:bold; margin-bottom:10px; }
.login-link { margin-top:15px; font-size:14px; color:#555; }
.login-link a { color:#4facfe; font-weight:bold; text-decoration:none; }
.login-link a:hover { text-decoration:underline; }
</style>
</head>
<body>

<div class="register-container">
<h2>Create an Account</h2>

<?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
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
