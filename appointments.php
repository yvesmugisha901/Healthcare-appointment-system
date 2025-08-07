<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch appointments with patient and doctor names
$sql = "SELECT 
            appointments.id, 
            appointments.appointment_datetime, 
            appointments.status, 
            appointments.notes,
            patients.name AS patient_name, 
            doctors.name AS doctor_name
        FROM appointments
        JOIN users AS patients ON appointments.patient_id = patients.id
        JOIN users AS doctors ON appointments.doctor_id = doctors.id
        ORDER BY appointments.appointment_datetime DESC";

$result = $conn->query($sql);

function formatDateTime($datetime) {
    return date('M d, Y - H:i', strtotime($datetime));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Appointments - Admin Dashboard</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f4f6f9;
      display: flex;
      height: 100vh;
    }

    .sidebar {
      width: 220px;
      background: linear-gradient(180deg, #007bff, #0056b3);
      color: white;
      padding: 25px 20px;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      height: 100vh;
    }

    .sidebar h2 {
      margin-bottom: 30px;
    }

    .sidebar nav a {
      color: white;
      text-decoration: none;
      margin: 10px 0;
      padding: 10px;
      border-radius: 6px;
      display: block;
      transition: background 0.3s;
    }

    .sidebar nav a.active,
    .sidebar nav a:hover {
      background: rgba(255, 255, 255, 0.2);
    }

    .main-content {
      flex: 1;
      padding: 40px;
      overflow-y: auto;
    }

    h1 {
      margin-top: 0;
      font-size: 2rem;
      color: #333;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    th, td {
      padding: 16px 20px;
      text-align: left;
    }

    thead {
      background-color: #007bff;
      color: white;
    }

    tr:nth-child(even) {
      background-color: #f2f2f2;
    }

    tr:hover {
      background-color: #e0f0ff;
    }
  </style>
</head>
<body>
  <aside class="sidebar">
    <h2>Admin Panel</h2>
    <nav>
      <a href="admin_dashboard.php">Home</a>
      <a href="manage_users.php">Manage Users</a>
      <a href="appointments.php" class="active">Appointments</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="logout.php">Logout</a>
    </nav>
  </aside>

  <main class="main-content">
    <h1>Appointments</h1>
    <table id="appointmentsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Patient</th>
          <th>Doctor</th>
          <th>Date & Time</th>
          <th>Status</th>
          <th>Notes</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['id']) ?></td>
          <td><?= htmlspecialchars($row['patient_name']) ?></td>
          <td><?= htmlspecialchars($row['doctor_name']) ?></td>
          <td><?= htmlspecialchars(formatDateTime($row['appointment_datetime'])) ?></td>
          <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
          <td><?= htmlspecialchars($row['notes']) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </main>

  <script src="js/appointments.js" defer></script>
</body>
</html>
