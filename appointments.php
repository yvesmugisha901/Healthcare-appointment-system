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
    return date('M d, Y - h:i A', strtotime($datetime));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Appointments - Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #1e3a8a;
    --primary-light: #3b82f6;
    --success: #28a745;
    --danger: #dc3545;
    --bg: #f0f2f5;
    --text: #333;
}

body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: var(--bg);
    display: flex;
    min-height: 100vh;
}

/* Sidebar */
.sidebar {
    width: 220px;
    background-color: var(--primary);
    color: white;
    padding: 25px 20px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.sidebar h2 {
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 24px;
}

.sidebar h2 i { font-size: 28px; }

.sidebar nav a {
    color: #cce5ff;
    text-decoration: none;
    margin: 10px 0;
    padding: 10px 12px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.sidebar nav a.active,
.sidebar nav a:hover {
    background-color: var(--primary-light);
    color: #fff;
}

/* Main content */
.main-content {
    flex: 1;
    padding: 30px;
    margin-left: 220px;
    box-sizing: border-box;
}

h1 {
    color: var(--primary);
    margin-bottom: 20px;
}

/* Scrollable Table Card */
.table-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    padding: 20px;
    overflow: hidden;
}

.table-card table {
    width: 100%;
    border-collapse: collapse;
}

.table-card thead tr {
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    color: #fff;
    text-align: left;
}

.table-card th, .table-card td {
    padding: 12px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}

.table-card tbody {
    display: block;
    max-height: 400px; /* Table height fixed, scrollable */
    overflow-y: auto;
}

.table-card thead, .table-card tbody tr {
    display: table;
    width: 100%;
    table-layout: fixed;
}

.table-card tbody tr:hover {
    background-color: #f1f5fb;
}

/* Scrollbar Styling */
.table-card tbody::-webkit-scrollbar {
    width: 8px;
}

.table-card tbody::-webkit-scrollbar-track {
    background: #f0f2f5;
    border-radius: 10px;
}

.table-card tbody::-webkit-scrollbar-thumb {
    background-color: var(--primary-light);
    border-radius: 10px;
}

</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <h2><i class="fa fa-stethoscope"></i> HealthSys</h2>
    <nav>
        <a href="admin_dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_users.php"><i class="fa fa-users"></i> Manage Users</a>
        <a href="appointments.php" class="active"><i class="fa fa-calendar-check"></i> Appointments</a>
        <a href="settings.php"><i class="fa fa-cog"></i> Settings</a>
        <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>

<!-- Main Content -->
<div class="main-content">
    <h1>Appointments</h1>

    <div class="table-card">
        <table>
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
                <?php if($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['patient_name']) ?></td>
                            <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                            <td><?= htmlspecialchars(formatDateTime($row['appointment_datetime'])) ?></td>
                            <td><?= ucfirst(htmlspecialchars($row['status'])) ?></td>
                            <td><?= htmlspecialchars($row['notes']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No appointments found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
