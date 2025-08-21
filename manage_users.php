<?php
session_start();
require 'connect.php';

// Handle user deletion
if (isset($_GET['delete'])) {
    $userId = $_GET['delete'];

    // Delete appointments related to the user
    $stmt = $conn->prepare("DELETE FROM appointments WHERE patient_id = ? OR doctor_id = ?");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $stmt->close();

    // Delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_users.php");
    exit();
}

// Fetch users with optional role filter
$roleFilter = $_GET['role'] ?? '';
$query = "SELECT id, name, email, role, created_at, specialization FROM users";
if (!empty($roleFilter)) {
    $query .= " WHERE role = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $roleFilter);
} else {
    $stmt = $conn->prepare($query);
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users - Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #1e3a8a;
    --primary-light: #3b82f6;
    --danger: #dc3545;
    --success: #28a745;
    --bg: #f0f2f5;
    --text: #333;
}

body {
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
    display: flex;
    background: var(--bg);
    min-height: 100vh;
}

.main-content {
    flex: 1;
    padding: 30px;
    margin-left: 220px;
}

h2 { color: var(--primary); margin-bottom: 20px; }

/* Add User button */
.add-user-btn {
    background-color: var(--success);
    color: #fff;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    margin-bottom: 20px;
    display: inline-block;
    text-decoration: none;
    transition: 0.3s;
}
.add-user-btn:hover { background-color: #218838; }

/* Filter form */
.filter-form { margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.filter-form select, .filter-form button {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}
.filter-form button {
    background-color: var(--primary);
    color: #fff;
    border: none;
    cursor: pointer;
}
.filter-form button:hover { background-color: var(--primary-light); }

/* Table card */
.table-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
    overflow: hidden;
    padding: 20px;
}

.table-card table {
    width: 100%;
    border-collapse: collapse;
}

.table-card thead tr {
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    color: #fff;
}

.table-card th, .table-card td {
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
    text-align: left;
}

.table-card tbody tr:hover { background-color: #f1f5fb; }

/* Action buttons */
.action-btn {
    padding: 6px 12px;
    font-size: 13px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    margin-right: 5px;
    transition: 0.3s;
}

.edit-btn { background: var(--primary); color: #fff; }
.edit-btn:hover { background: var(--primary-light); }

.delete-btn { background: var(--danger); color: #fff; }
.delete-btn:hover { background: #b71c1c; }

/* Responsive */
@media (max-width: 768px) {
    .main-content { margin-left: 0; padding: 15px; }
    .filter-form { flex-direction: column; align-items: flex-start; gap: 8px; }
    .table-card th, .table-card td { font-size: 12px; padding: 10px; }
}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h2>Manage Users</h2>

    <a href="add_user.php" class="add-user-btn"><i class="fa fa-plus"></i> Add User</a>

    <form method="GET" class="filter-form">
        <label for="role">Filter by Role:</label>
        <select name="role" id="role">
            <option value="">All</option>
            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="doctor" <?= $roleFilter === 'doctor' ? 'selected' : '' ?>>Doctor</option>
            <option value="patient" <?= $roleFilter === 'patient' ? 'selected' : '' ?>>Patient</option>
        </select>
        <button type="submit"><i class="fa fa-filter"></i> Filter</button>
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Specialization</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($users): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id']; ?></td>
                        <td><?= htmlspecialchars($user['name']); ?></td>
                        <td><?= htmlspecialchars($user['email']); ?></td>
                        <td><?= htmlspecialchars($user['role']); ?></td>
                        <td><?= htmlspecialchars($user['specialization']); ?></td>
                        <td><?= $user['created_at']; ?></td>
                        <td>
                            <a href="edit_user.php?id=<?= $user['id']; ?>" class="action-btn edit-btn"><i class="fa fa-edit"></i> Edit</a>
                            <a href="?delete=<?= $user['id']; ?>" onclick="return confirm('Are you sure? This will delete all appointments too.');" class="action-btn delete-btn"><i class="fa fa-trash"></i> Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center;">No users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
