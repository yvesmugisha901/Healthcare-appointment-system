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
        'color' => $row['status'] === 'cancelled' ? '#e76f51' : ($row['status'] === 'completed' ? '#2a9d8f' : '#7fcdc3'),
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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MedConnect | Appointment Calendar</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<style>
:root {
  --primary: #2a9d8f;
  --primary-dark: #1d7870;
  --primary-light: #7fcdc3;
  --secondary: #e76f51;
  --neutral-dark: #264653;
  --neutral-medium: #6c757d;
  --neutral-light: #f8f9fa;
  --white: #ffffff;
  --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
  --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
  --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
  --transition: all 0.3s ease;
  --radius: 10px;
}

body {
  font-family: 'Inter', sans-serif;
  background: var(--neutral-light);
  color: var(--neutral-dark);
  display:flex;
  min-height:100vh;
  margin:0;
}

/* Sidebar */
.sidebar {
  width: 220px;
  background-color: var(--primary);
  color: var(--white);
  display:flex;
  flex-direction: column;
  padding: 2rem 1rem;
}
.sidebar h2 {
  font-size: 1.8rem;
  margin-bottom: 2rem;
  display:flex;
  align-items:center;
  gap:0.5rem;
}
.sidebar h2 i { font-size:1.5rem; }
.sidebar a {
  color: var(--white);
  text-decoration:none;
  margin:0.5rem 0;
  padding:0.7rem 1rem;
  border-radius: var(--radius);
  display:flex;
  align-items:center;
  gap:0.5rem;
  font-weight:500;
  transition: var(--transition);
}
.sidebar a:hover, .sidebar a.active {
  background-color: var(--primary-dark);
}

/* Main content */
.main-content {
  flex:1;
  padding: 2rem;
}
#calendar {
  max-width:900px;
  margin:2rem auto;
  background: var(--white);
  padding: 1rem;
  border-radius: var(--radius);
  box-shadow: var(--shadow-md);
}
h2.calendar-title {
  text-align:center;
  color: var(--primary);
  margin-bottom:1rem;
}

/* Export button */
.export-btn {
  display:inline-flex;
  background: var(--primary);
  color: var(--white);
  padding:10px 15px;
  border-radius: var(--radius);
  text-decoration:none;
  font-weight: 600;
  margin: 1rem auto;
  transition: var(--transition);
}
.export-btn:hover {
  background: var(--primary-dark);
  transform: translateY(-2px);
}

/* Responsive */
@media (max-width:768px){
  body { flex-direction: column; }
  .sidebar { width:100%; flex-direction:row; justify-content:space-around; padding:1rem; }
  .sidebar h2 { display:none; }
}
</style>
</head>
<body>

<aside class="sidebar">
  <h2><i class="fa fa-stethoscope"></i> MedConnect</h2>
  <a href="<?= $dashboardLink ?>"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
  <a href="appointments.php" class="active"><i class="fa fa-calendar-check"></i> Appointments</a>
  <a href="settings.php"><i class="fa fa-cog"></i> Settings</a>
  <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</aside>

<div class="main-content">
  <h2 class="calendar-title">Appointment Calendar</h2>
  <div id="calendar"></div>
  <div style="text-align:center;">
    <a href="export_ics.php" class="export-btn"><i class="fa fa-download"></i> Export Appointments (.ics)</a>
  </div>
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
