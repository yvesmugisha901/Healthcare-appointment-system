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

$totalAppointments = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM appointments");
if ($row = $result->fetch_assoc()) {
    $totalAppointments = $row['total'];
}

$pendingApprovals = 0;
$result = $conn->query("SELECT COUNT(*) AS pending FROM appointments WHERE status = 'pending'");
if ($row = $result->fetch_assoc()) {
    $pendingApprovals = $row['pending'];
}

$activeUsers = 0;
$result = $conn->query("SELECT COUNT(*) AS active FROM users WHERE role IN ('patient', 'doctor', 'admin')");
if ($row = $result->fetch_assoc()) {
    $activeUsers = $row['active'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard - Healthcare System</title>
  <link rel="stylesheet" href="css/admin_dash.css" />
  <script defer src="js/admindash.js"></script>
</head>
<body>
  <aside class="sidebar">
    <h2>Admin Panel</h2>
    <nav>
      <a href="admin_dashboard.php" class="active">Home</a>
      <a href="manage_users.php">Manage Users</a>
      <a href="appointments.php">Appointments</a>
      <a href="payments.php">Payments Management</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="logout.php">Logout</a>
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
  </main>
</body>
</html>
