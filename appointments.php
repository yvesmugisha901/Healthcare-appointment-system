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
    --primary: #2a9d8f;
    --primary-dark: #1d7870;
    --primary-light: #7fcdc3;
    --secondary: #e76f51;
    --neutral-dark: #264653;
    --neutral-light: #f8f9fa;
    --success: #28a745;
    --pending: #ff9900;
    --failed: #dc3545;
}

/* Reset */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }

body { display:flex; min-height:100vh; background: var(--neutral-light); color: var(--neutral-dark); }

/* Sidebar */
.sidebar {
    width: 240px; background: var(--primary); color: #fff; height: 100vh; position: fixed; top:0; left:0;
    padding:25px 20px; display:flex; flex-direction:column; transition:0.3s; z-index:1000;
}
.sidebar h2 { display:flex; align-items:center; gap:10px; margin-bottom:40px; font-size:24px; }
.sidebar h2 i { font-size:28px; }
.sidebar nav { display:flex; flex-direction:column; gap:10px; flex-grow:1; }
.sidebar nav a { color:#cce5ff; text-decoration:none; padding:10px 15px; border-radius:8px; display:flex; align-items:center; gap:10px; transition:0.3s; }
.sidebar nav a.active, .sidebar nav a:hover { background: var(--primary-dark); color:#fff; }

/* Main content */
.main-content { flex:1; margin-left:240px; padding:30px; display:flex; flex-direction:column; align-items:center; }
h1 { color: var(--primary); margin-bottom:20px; text-align:center; }

/* Table Card */
.table-card { background:#fff; border-radius:12px; box-shadow:0 8px 25px rgba(0,0,0,0.1); padding:20px; overflow:hidden; width:100%; max-width:900px; margin:auto; }
.table-card table { width:100%; border-collapse: collapse; }
.table-card thead tr { background: linear-gradient(90deg,var(--primary),var(--primary-light)); color:#fff; text-align:left; }
.table-card th, .table-card td { padding:12px; border-bottom:1px solid #eee; font-size:14px; }
.table-card tbody { display:block; max-height:400px; overflow-y:auto; }
.table-card thead, .table-card tbody tr { display:table; width:100%; table-layout:fixed; }
.table-card tbody tr:hover { background:#f1f5fb; }

/* Status Colors */
.status-pending { color: var(--pending); font-weight:600; }
.status-completed { color: var(--success); font-weight:600; }
.status-failed { color: var(--failed); font-weight:600; }
.status-cancelled { color: var(--secondary); font-weight:600; }

/* Scrollbar */
.table-card tbody::-webkit-scrollbar { width:8px; }
.table-card tbody::-webkit-scrollbar-track { background:#f8f9fa; border-radius:10px; }
.table-card tbody::-webkit-scrollbar-thumb { background: var(--primary-light); border-radius:10px; }

/* Responsive */
@media(max-width:768px){
    .sidebar { width:100%; height:auto; flex-direction:row; justify-content:space-around; padding:15px; }
    .main-content{ margin-left:0; padding:20px; }
}
</style>
</head>
<body>

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
                            <td class="status-<?= strtolower($row['status']) ?>"><?= ucfirst(htmlspecialchars($row['status'])) ?></td>
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
