<?php
session_start();
require 'connect.php';

$user_id = $_SESSION['user_id'] ?? 1;

// Fetch appointments where user is patient or doctor
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
<html>
<head>
    <meta charset="UTF-8" />
    <title>Appointment Calendar</title>

    <!-- FullCalendar CSS & JS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 200px; /* width of sidebar */
            background-color: #f4f6f9;
            min-height: 100vh;
        }

        h2 {
            margin-bottom: 20px;
        }

        #calendar {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h2>Appointment Calendar</h2>
    <div id="calendar"></div>
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
