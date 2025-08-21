<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$adminId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$stmt->bind_result($adminName);
$stmt->fetch();
$stmt->close();

// Dashboard data
$totalAppointments = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM appointments");
if ($row = $result->fetch_assoc()) $totalAppointments = $row['total'];

$pendingApprovals = 0;
$result = $conn->query("SELECT COUNT(*) AS pending FROM appointments WHERE status = 'pending'");
if ($row = $result->fetch_assoc()) $pendingApprovals = $row['pending'];

$activeUsers = 0;
$result = $conn->query("SELECT COUNT(*) AS active FROM users WHERE role IN ('patient', 'doctor', 'admin')");
if ($row = $result->fetch_assoc()) $activeUsers = $row['active'];

// Handle approve/reject pending doctor updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateId = intval($_POST['update_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($updateId && in_array($action, ['approve','reject'])) {
        $stmt = $conn->prepare("SELECT doctor_id, field_name, new_value FROM pending_updates WHERE id=? AND status='pending'");
        $stmt->bind_param("i", $updateId);
        $stmt->execute();
        $stmt->bind_result($doctor_id, $field_name, $new_value);
        if ($stmt->fetch()) {
            $stmt->close();
            if ($action === 'approve') {
                // Update user's field
                $updateUser = $conn->prepare("UPDATE users SET $field_name=? WHERE id=?");
                $updateUser->bind_param("si", $new_value, $doctor_id);
                $updateUser->execute();
                $updateUser->close();
            }
            // Update pending_updates table
            $updateReq = $conn->prepare("UPDATE pending_updates SET status=?, approved_at=NOW(), admin_id=? WHERE id=?");
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $updateReq->bind_param("sii", $status, $adminId, $updateId);
            $updateReq->execute();
            $updateReq->close();
            header("Location: admin_dashboard.php");
            exit;
        } else { $stmt->close(); }
    }
}

// Fetch pending doctor updates
$pendingUpdates = [];
$res = $conn->query("
    SELECT p.id, p.doctor_id, u.name AS doctor_name, p.field_name, p.old_value, p.new_value, p.submitted_at
    FROM pending_updates p
    JOIN users u ON p.doctor_id = u.id
    WHERE p.status='pending'
    ORDER BY p.submitted_at ASC
");
if ($res) {
    while ($row = $res->fetch_assoc()) $pendingUpdates[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - HealthSys</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {--primary: #1e3a8a;--primary-light:#3b82f6;--success:#28a745;--info:#17a2b8;--danger:#dc3545;--text:#333;--bg:#f0f2f5;}
body {margin:0;font-family:'Segoe UI',sans-serif;background:var(--bg);display:flex;min-height:100vh;}
.sidebar {width:200px;background-color:var(--primary);color:#fff;padding:30px 20px;position:fixed;height:100vh;display:flex;flex-direction:column;transition:all 0.3s ease;}
.sidebar h2 {text-align:center;margin-bottom:30px;font-size:24px;font-weight:700;}
.sidebar nav a {display:flex;align-items:center;padding:10px 15px;margin-bottom:15px;color:#cce5ff;text-decoration:none;border-radius:8px;gap:10px;transition:all 0.3s ease;}
.sidebar nav a:hover {background-color:#003d80;color:#fff;transform:translateX(5px);}
.sidebar nav a.active {background-color:#003366;font-weight:700;}
.main-content {flex:1;margin-left:220px;padding:30px;box-sizing:border-box;}
h1 {color:var(--text);margin-bottom:30px;}
.dashboard-cards {display:flex;gap:20px;flex-wrap:wrap;}
.card {flex:1;min-width:180px;background:#fff;padding:25px;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);text-align:center;transition:transform 0.3s ease,box-shadow 0.3s ease;}
.card:hover {transform:translateY(-5px);box-shadow:0 8px 20px rgba(0,0,0,0.15);}
.card h3 {color:var(--primary);margin-bottom:15px;font-weight:600;}
.card p {font-size:28px;font-weight:bold;margin:0;}
.table-card {max-width:900px;margin:25px auto;background:#fff;border-radius:12px;box-shadow:0 8px 25px rgba(0,0,0,0.1);overflow:hidden;padding:20px;transition: transform 0.3s ease, box-shadow 0.3s ease;}
.table-card:hover {transform: translateY(-5px);box-shadow:0 12px 30px rgba(0,0,0,0.15);}
.table-card h2 {margin-bottom:15px;color:var(--primary);font-size:22px;text-align:center;}
table {width:100%;border-collapse:collapse;}
th, td {padding:10px 12px;border-bottom:1px solid #eee;text-align:center;}
thead tr {background:linear-gradient(90deg,var(--primary),var(--primary-light));color:#fff;}
button.approve-btn {background:var(--success);color:#fff;padding:5px 10px;border:none;border-radius:5px;cursor:pointer;}
button.reject-btn {background:var(--danger);color:#fff;padding:5px 10px;border:none;border-radius:5px;cursor:pointer;}
@media(max-width:768px){.sidebar{width:100%;height:auto;padding:15px 10px;flex-direction:row;justify-content:space-around;}.main-content{margin-left:0;padding:20px 10px;}.dashboard-cards{flex-direction:column;gap:15px;}}
</style>
</head>
<body>
<aside class="sidebar">
    <h2><i class="fa fa-cogs"></i> Admin Panel</h2>
    <nav>
      <a href="admin_dashboard.php" class="active"><i class="fa fa-home"></i> Home</a>
      <a href="manage_users.php"><i class="fa fa-users"></i> Manage Users</a>
      <a href="appointments.php"><i class="fa fa-calendar-check"></i> Appointments</a>
      <a href="payments.php"><i class="fa fa-credit-card"></i> Payments</a>
      <a href="reports.php"><i class="fa fa-file-alt"></i> Reports</a>
      <a href="settings.php"><i class="fa fa-cog"></i> Settings</a>
      <a href="logout.php" class="logout"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
<h1>Welcome, <?= htmlspecialchars($adminName) ?> 👋</h1>
<div class="dashboard-cards">
    <div class="card">
        <h3>Total Appointments</h3>
        <p><?= $totalAppointments ?></p>
    </div>
    <div class="card">
        <h3>Pending Approvals</h3>
        <p><?= $pendingApprovals ?></p>
    </div>
    <div class="card">
        <h3>Active Users</h3>
        <p><?= $activeUsers ?></p>
    </div>
</div>

<!-- Pending Doctor Updates -->
<div class="table-card">
    <h2>Pending Doctor Updates</h2>
    <?php if(count($pendingUpdates) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Doctor</th>
                <th>Field</th>
                <th>Old Value</th>
                <th>New Value</th>
                <th>Submitted At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($pendingUpdates as $upd): ?>
            <tr>
                <td><?= htmlspecialchars($upd['doctor_name']) ?></td>
                <td><?= htmlspecialchars($upd['field_name']) ?></td>
                <td><?= htmlspecialchars($upd['old_value']) ?></td>
                <td><?= htmlspecialchars($upd['new_value']) ?></td>
                <td><?= $upd['submitted_at'] ?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="update_id" value="<?= $upd['id'] ?>">
                        <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                        <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="text-align:center;">No pending updates</p>
    <?php endif; ?>
</div>
</main>
</body>
</html>
