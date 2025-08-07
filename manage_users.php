<?php
session_start();
require 'connect.php';

// Handle user deletion
if (isset($_GET['delete'])) {
    $userId = $_GET['delete'];
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
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin Dashboard</title>
    <style>
        /* Reset and Layout */
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 220px; /* Matches sidebar width */
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .add-user-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 20px;
            display: inline-block;
            text-decoration: none;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #007BFF;
            color: white;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .actions a {
            margin-right: 10px;
            color: #007BFF;
            text-decoration: none;
        }

        .actions a.delete {
            color: red;
        }

        .filter-form {
            margin-bottom: 20px;
        }

        .filter-form select,
        .filter-form button {
            padding: 8px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .filter-form button {
            background: #007BFF;
            color: white;
            border: none;
            margin-left: 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
    <h2>Manage Users</h2>

    <!-- Add User Button -->
    <a href="add_user.php" class="add-user-btn">+ Add User</a>

    <!-- Role Filter -->
    <form method="GET" class="filter-form">
        <label for="role">Filter by Role:</label>
        <select name="role" id="role">
            <option value="">All</option>
            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="doctor" <?= $roleFilter === 'doctor' ? 'selected' : '' ?>>Doctor</option>
            <option value="patient" <?= $roleFilter === 'patient' ? 'selected' : '' ?>>Patient</option>
        </select>
        <button type="submit">Filter</button>
    </form>

    <!-- Users Table -->
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
                    <td class="actions">
                        <a href="edit_user.php?id=<?= $user['id']; ?>">Edit</a>
                        <a href="?delete=<?= $user['id']; ?>" class="delete" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">No users found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
