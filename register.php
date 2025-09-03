<?php
session_start();
require 'connect.php';

$name = $email = $role = $location = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $location = trim($_POST['location'] ?? '');

    if (empty($name) || empty($email) || empty($password) || !in_array($role, ['patient', 'doctor'])) {
        $error = 'Please fill in all required fields correctly.';
    } elseif(strlen($password) < 4){
        $error = 'Password must be at least 4 characters long.';
    } elseif(empty($location)){
        $error = 'Please enter your location.';
    } else {
        $name = $conn->real_escape_string($name);
        $email = $conn->real_escape_string($email);
        $role = $conn->real_escape_string($role);
        $location = $conn->real_escape_string($location);

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Email is already registered. Please use another or login.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, role, location, twofa_enabled) VALUES (?, ?, ?, ?, ?, 0)");
            $insertStmt->bind_param("sssss", $name, $email, $passwordHash, $role, $location);

            if ($insertStmt->execute()) {
                $user_id = $insertStmt->insert_id;
                if ($role === 'doctor') {
                    header("Location: doctor_verification.php?user_id=" . $user_id);
                    exit;
                } else {
                    $_SESSION['success_message'] = "Registration successful! Please log in.";
                    header("Location: login.php");
                    exit;
                }
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
<title>Register - MedConnect</title>
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
    background: #e8f6f4; /* subtle professional green background */
}

.register-container {
    background: var(--white);
    padding: 45px 40px;
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    width: 400px;
    text-align:center;
    transition: var(--transition);
}

.register-container:hover {
    transform: translateY(-4px);
}

.register-container h2 {
    font-size:28px;
    color: var(--neutral-dark);
    margin-bottom:25px;
    font-weight:700;
}

.register-container form input,
.register-container form select {
    width:100%;
    padding:14px 15px;
    margin-bottom:18px;
    border:1px solid #ccc;
    border-radius: var(--radius);
    outline:none;
    font-size:15px;
    transition: var(--transition);
}

.register-container form input:focus,
.register-container form select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 8px rgba(42,157,143,0.25);
}

.register-container button {
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

.register-container button:hover {
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

.login-link {
    margin-top:18px;
    font-size:14px;
    color: var(--neutral-medium);
}

.login-link a {
    color: var(--primary);
    font-weight:bold;
    text-decoration:none;
}

.login-link a:hover { text-decoration:underline; }

@media(max-width:480px){
    .register-container { width:90%; padding:30px; }
}
</style>
</head>
<body>

<div class="register-container">
<h2>Create Your Account</h2>

<?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
    <input type="text" name="name" placeholder="Full Name" required value="<?= htmlspecialchars($name) ?>" />
    <input type="email" name="email" placeholder="Email Address" required value="<?= htmlspecialchars($email) ?>" />
    <input type="password" name="password" placeholder="Password" required />
    <input type="text" name="location" placeholder="Location" required value="<?= htmlspecialchars($location) ?>" />
    
    <select name="role" required>
        <option value="" disabled <?= !$role ? 'selected' : '' ?>>Select Role</option>
        <option value="patient" <?= $role === 'patient' ? 'selected' : '' ?>>Patient</option>
        <option value="doctor" <?= $role === 'doctor' ? 'selected' : '' ?>>Doctor</option>
    </select>

    <button type="submit"><i class="fas fa-user-plus"></i> Sign Up</button>
</form>

<div class="login-link">
    Already have an account? <a href="login.php">Login here</a>
</div>
</div>

</body>
</html>
