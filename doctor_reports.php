<?php
session_start();
require 'connect.php';

// Only allow logged-in doctors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctorId = $_SESSION['user_id'];

// === 1. Appointment Summary by Status ===
$stmt = $conn->prepare("
    SELECT status, COUNT(*) AS count
    FROM appointments
    WHERE doctor_id = ?
    GROUP BY status
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();

$reportData = ['Scheduled' => 0, 'Completed' => 0, 'Cancelled' => 0];
while ($row = $result->fetch_assoc()) {
    $reportData[$row['status']] = $row['count'];
}
$stmt->close();

// === 2. Total unique patients handled ===
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT patient_id) AS total_patients
    FROM appointments
    WHERE doctor_id = ?
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$stmt->bind_result($totalPatients);
$stmt->fetch();
$stmt->close();

// === 3. Upcoming appointments (next 5) ===
$stmt = $conn->prepare("
    SELECT a.id, u.name AS patient_name, a.appointment_datetime, a.status
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ? AND a.appointment_datetime >= NOW()
    ORDER BY a.appointment_datetime ASC
    LIMIT 5
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$upcomingAppointments = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Doctor Reports - Healthcare System</title>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        display: flex;
        background-color: #f4f6f9;
        min-height: 100vh;
    }
    .main-content {
        flex: 1;
        padding: 30px;
        margin-left: 200px;
        box-sizing: border-box;
    }
    h1 {
        margin-bottom: 20px;
    }
    .card-container {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }
    .card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        flex: 1;
        min-width: 180px;
        text-align: center;
    }
    .card h2 {
        margin: 0;
        font-size: 2em;
        color: #007BFF;
    }
    .card p {
        margin: 5px 0 0;
        font-size: 1.1em;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    th, td {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        text-align: left;
    }
    thead tr {
        background-color: #007BFF;
        color: white;
    }
    tbody tr:hover {
        background-color: #f1f1f1;
    }
</style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
    <h1>Doctor Reports</h1>

    <!-- Summary Cards -->
    <div class="card-container">
        <div class="card">
            <h2><?php echo $reportData['Scheduled']; ?></h2>
            <p>Scheduled Appointments</p>
        </div>
        <div class="card">
            <h2><?php echo $reportData['Completed']; ?></h2>
            <p>Completed Appointments</p>
        </div>
        <div class="card">
            <h2><?php echo $reportData['Cancelled']; ?></h2>
            <p>Cancelled Appointments</p>
        </div>
        <div class="card">
            <h2><?php echo $totalPatients; ?></h2>
            <p>Total Patients</p>
        </div>
    </div>

    <!-- Upcoming Appointments Table -->
    <h2>Upcoming Appointments</h2>
    <table>
        <thead>
            <tr>
                <th>Patient Name</th>
                <th>Date & Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $upcomingAppointments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                    <td><?php echo date("d M Y, h:i A", strtotime($row['appointment_datetime'])); ?></td>
                    <td><?php echo $row['status']; ?></td>
                </tr>
            <?php endwhile; ?>
            <?php if ($upcomingAppointments->num_rows === 0): ?>
                <tr>
                    <td colspan="3">No upcoming appointments</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
