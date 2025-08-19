<?php
session_start();
require 'connect.php';

$errors = [];
$success = '';

$userId = $_SESSION['user_id'] ?? 0; // Logged-in admin ID
if (!$userId) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $specialization = trim($_POST['specialization'] ?? '');

    // Basic validation
    if (!$name) $errors[] = "Name is required.";
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (!$password) $errors[] = "Password is required.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";
    if (!in_array($role, ['admin', 'doctor', 'patient'])) $errors[] = "Invalid role selected.";
    if ($role === 'doctor' && !$specialization) $errors[] = "Specialization is required for doctors.";

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $errors[] = "Email already registered.";
    $stmt->close();

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, specialization, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $specialization);
        if ($stmt->execute()) {
            $newUserId = $stmt->insert_id; // New user's ID
            $success = "User added successfully.";

            // Insert notification for admin(s)
            $notifType = 'user_created';
            $sentAt = date('Y-m-d H:i:s');
            $status = 'unread';
            $relatedTable = 'users';
            $relatedId = $newUserId;

            // Fetch all admins
            $admins = [];
            $res = $conn->query("SELECT id FROM users WHERE role='admin'");
            while ($row = $res->fetch_assoc()) {
                $admins[] = $row['id'];
            }

            foreach ($admins as $adminId) {
                $stmtNotif = $conn->prepare("
                    INSERT INTO notifications 
                        (appointment_id, type, sent_at, status, recipient_id, recipient_role, related_table, related_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $appointmentId = NULL; // No appointment for user creation
                $recipientRole = 'admin';
                $stmtNotif->bind_param("issssssi", $appointmentId, $notifType, $sentAt, $status, $adminId, $recipientRole, $relatedTable, $relatedId);
                $stmtNotif->execute();
                $stmtNotif->close();
            }

            header("Location: manage_users.php");
            exit();
        } else {
            $errors[] = "Database error: Could not add user.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 30px; display: flex; justify-content: center; }
        .container { background: white; padding: 25px 30px; border-radius: 8px; box-shadow: 0 3px 8px rgba(0,0,0,0.1); width: 400px; }
        h2 { margin-top: 0; color: #007BFF; text-align: center; margin-bottom: 20px; }
        label { display: block; margin-top: 15px; font-weight: 600; }
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%; padding: 8px 10px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box;
        }
        button { width: 100%; margin-top: 25px; background: #007BFF; color: white; padding: 12px; border: none; border-radius: 5px; font-weight: 700; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .errors { background: #f8d7da; color: #842029; padding: 10px; margin-bottom: 15px; border-radius: 5px; border-left: 6px solid #dc3545; }
        .success { background: #d1e7dd; color: #0f5132; padding: 10px; margin-bottom: 15px; border-radius: 5px; border-left: 6px solid #198754; }
    </style>
    <script>
        function toggleSpecialization() {
            const roleSelect = document.getElementById('role');
            const specField = document.getElementById('specializationField');
            specField.style.display = (roleSelect.value === 'doctor') ? 'block' : 'none';
        }
        window.onload = toggleSpecialization;
    </script>
</head>
<body>
<div class="container">
    <h2>Add New User</h2>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

        <label for="email">Email</label>
        <input type="email" name="email" id="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <label for="confirm_password">Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm_password" required>

        <label for="role">Role</label>
        <select name="role" id="role" onchange="toggleSpecialization()" required>
            <option value="">-- Select Role --</option>
            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
            <option value="doctor" <?= (($_POST['role'] ?? '') === 'doctor') ? 'selected' : '' ?>>Doctor</option>
            <option value="patient" <?= (($_POST['role'] ?? '') === 'patient') ? 'selected' : '' ?>>Patient</option>
        </select>

        <div id="specializationField" style="display:none;">
            <label for="specialization">Specialization (only for doctors)</label>
            <input type="text" name="specialization" id="specialization" value="<?= htmlspecialchars($_POST['specialization'] ?? '') ?>">
        </div>

        <button type="submit">Add User</button>
    </form>
</div>
</body>
</html>
