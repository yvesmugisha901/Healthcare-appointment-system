<?php
session_start();
require 'connect.php';

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    header("Location: login.php");
    exit();
}

// Fetch user role
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($userRole);
$stmt->fetch();
$stmt->close();

// Determine dashboard link
$dashboardLink = match($userRole) {
    'doctor' => 'doctor_dash.php',
    'patient' => 'patient_dashboard.php',
    'admin' => 'admin_dashboard.php',
    default => 'login.php',
};

// Fetch appointments
$sql = "SELECT a.id, a.appointment_datetime, a.status, a.notes, 
               p.name AS patient_name, d.name AS doctor_name
        FROM appointments a
        LEFT JOIN users p ON a.patient_id = p.id
        LEFT JOIN users d ON a.doctor_id = d.id
        WHERE a.patient_id = ? OR a.doctor_id = ?
        ORDER BY a.appointment_datetime ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $title = "Dr. " . htmlspecialchars($row['doctor_name']) . " & " . htmlspecialchars($row['patient_name']);
    $events[] = [
        'id' => $row['id'],
        'title' => $title,
        'start' => $row['appointment_datetime'],
        'color' => $row['status'] === 'cancelled' ? 'red' : ($row['status'] === 'completed' ? 'green' : '#007bff'),
        'extendedProps' => [
            'notes' => htmlspecialchars($row['notes']),
            'status' => $row['status']
        ]
    ];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointment Calendar</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<style>
body { font-family: Arial, sans-serif; margin: 0; display: flex; background: #f4f6f9; }
.sidebar {
    width: 220px;
    background-color: #1e3a8a;
    color: white;
    padding: 25px 20px;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}
.sidebar h2 { margin-bottom: 30px; font-size: 24px; display: flex; align-items: center; gap: 10px; }
.sidebar h2 i { font-size: 28px; }
.sidebar a {
    color: #cce5ff;
    text-decoration: none;
    margin: 10px 0;
    padding: 10px 12px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.sidebar a.active, .sidebar a:hover { background-color: #3b82f6; color: #fff; }
.main-content { flex: 1; padding: 20px; }
#calendar { max-width: 900px; margin: 0 auto; background: white; padding: 10px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
h2 { margin-bottom: 20px; text-align: center; color: #1e3a8a; }
</style>
</head>
<body>

<aside class="sidebar">
    <h2><i class="fa fa-stethoscope"></i> HealthSys</h2>
    <a href="<?= $dashboardLink ?>"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
    <a href="appointments.php"><i class="fa fa-calendar-check"></i> Appointments</a>
    <a href="settings.php"><i class="fa fa-cog"></i> Settings</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</aside>

<div class="main-content">
    <h2>Appointment Calendar</h2>
    <div id="calendar"></div>
</div>
<div style="text-align:center; margin:15px;">
  <a href="export_ics.php" class="btn btn-primary" style="background:#1e3a8a; color:white; padding:10px 15px; border-radius:8px; text-decoration:none;">
    <i class="fa fa-download"></i> Export Appointments (.ics)
  </a>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: <?= json_encode($events); ?>,
        eventClick: function(info) {
            let event = info.event;
            let notes = event.extendedProps.notes || 'No additional notes';
            alert(
                'Appointment ID: ' + event.id +
                '\nTitle: ' + event.title +
                '\nDate & Time: ' + event.start.toLocaleString() +
                '\nStatus: ' + event.extendedProps.status +
                '\nNotes: ' + notes
            );
        },
        height: 650
    });
    calendar.render();
});
</script>

</body>
</html>
