<?php
session_start();
require 'connect.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header("Location: login.php");
    exit();
}

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=appointments.ics');

// Fetch all appointments for the logged-in user
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

// Generate ICS format
echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//HealthSys Calendar Export//EN\r\n";

while ($row = $result->fetch_assoc()) {
    $start = date('Ymd\THis', strtotime($row['appointment_datetime']));
    $end = date('Ymd\THis', strtotime($row['appointment_datetime'] . ' +1 hour'));
    $title = "Appointment: Dr. {$row['doctor_name']} & {$row['patient_name']}";
    $uid = uniqid();

    echo "BEGIN:VEVENT\r\n";
    echo "UID:$uid@example.com\r\n";
    echo "DTSTAMP:$start\r\n";
    echo "DTSTART:$start\r\n";
    echo "DTEND:$end\r\n";
    echo "SUMMARY:" . addslashes($title) . "\r\n";
    echo "DESCRIPTION:" . addslashes($row['notes'] ?? 'No notes') . "\r\n";
    echo "STATUS:" . strtoupper($row['status']) . "\r\n";
    echo "END:VEVENT\r\n";
}

echo "END:VCALENDAR\r\n";
