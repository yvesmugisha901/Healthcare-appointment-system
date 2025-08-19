<?php
session_start();
require 'connect.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$patientId = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch patient name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$stmt->bind_result($patientName);
$stmt->fetch();
$stmt->close();

// Fetch doctors list
$doctorsResult = $conn->query("SELECT id, name FROM users WHERE role = 'doctor'");
$doctors = $doctorsResult->fetch_all(MYSQLI_ASSOC);

// Handle Cancel appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment_id'])) {
    $appointmentId = (int)$_POST['cancel_appointment_id'];
    $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $appointmentId, $patientId);
    if ($stmt->execute()) {
        $success = "Appointment cancelled successfully.";
    } else {
        $error = "Failed to cancel appointment.";
    }
    $stmt->close();
}

// Handle Reschedule appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reschedule_appointment_id'], $_POST['new_datetime'])) {
    $appointmentId = (int)$_POST['reschedule_appointment_id'];
    $newDatetime = $_POST['new_datetime'];
    $stmt = $conn->prepare("UPDATE appointments SET appointment_datetime = ?, status = 'rescheduled' WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("sii", $newDatetime, $appointmentId, $patientId);
    if ($stmt->execute()) {
        $success = "Appointment rescheduled successfully.";
    } else {
        $error = "Failed to reschedule appointment.";
    }
    $stmt->close();
}

// Handle Book New Appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $doctorId = (int)$_POST['doctor_id'];
    $datetime = $_POST['appointment_datetime'];

    if ($doctorId && $datetime) {
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_datetime, status) VALUES (?, ?, ?, 'booked')");
        $stmt->bind_param("iis", $patientId, $doctorId, $datetime);
        if ($stmt->execute()) {
            $success = "Appointment booked successfully.";
        } else {
            $error = "Failed to book appointment.";
        }
        $stmt->close();
    } else {
        $error = "Please select doctor and date/time.";
    }
}

// Fetch upcoming appointments (exclude cancelled)
$stmt = $conn->prepare("SELECT a.id, a.appointment_datetime, u.name AS doctor_name, a.status 
                        FROM appointments a 
                        JOIN users u ON a.doctor_id = u.id 
                        WHERE a.patient_id = ? AND a.status != 'cancelled' AND a.appointment_datetime >= NOW()
                        ORDER BY a.appointment_datetime ASC");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$upcomingResult = $stmt->get_result();
$upcomingAppointments = $upcomingResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch past appointments (including cancelled)
$stmt = $conn->prepare("SELECT a.id, a.appointment_datetime, u.name AS doctor_name, a.status 
                        FROM appointments a 
                        JOIN users u ON a.doctor_id = u.id 
                        WHERE a.patient_id = ? AND (a.appointment_datetime < NOW() OR a.status = 'cancelled')
                        ORDER BY a.appointment_datetime DESC");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$pastResult = $stmt->get_result();
$pastAppointments = $pastResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Dashboard - Healthcare System</title>
<style>
/* Reset */
* { margin:0; padding:0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }

/* Body & Layout */
body { display: flex; background: #f4f6f9; min-height: 100vh; }
.sidebar {
    width: 220px; background: #0056b3; color: white; position: fixed; top:0; left:0; height: 100%;
    padding: 30px 20px; display:flex; flex-direction: column;
}
.sidebar h2 { text-align:center; font-size:24px; margin-bottom:30px; user-select:none; }
.sidebar nav a {
    display:block; color:#cce5ff; text-decoration:none; padding:12px 15px; margin-bottom:15px;
    border-radius:6px; transition: 0.3s;
}
.sidebar nav a:hover, .sidebar nav a.active { background:#003d80; color:white; }

.main-content {
    margin-left:220px; flex:1; padding:30px; min-height:100vh; overflow-y:auto;
}

/* Header & Cards */
h1 { font-size:28px; color:#007bff; margin-bottom:20px; }
.dashboard-cards { display:flex; gap:20px; flex-wrap:wrap; margin-bottom:30px; }
.card {
    flex:1; min-width:200px; background:white; padding:20px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease; text-align:center;
}
.card:hover { transform: translateY(-5px); box-shadow:0 8px 25px rgba(0,0,0,0.15);}
.card h3 { color:#007bff; margin-bottom:10px; font-weight:600; }
.card p { font-size:24px; font-weight:bold; margin:0; }

/* Messages */
.message { margin:15px 0; padding:12px; border-radius:6px; font-weight:600; }
.success { background:#d4edda; color:#155724; }
.error { background:#f8d7da; color:#721c24; }

/* Forms */
form { margin-bottom:20px; }
input, select, button { padding:8px; font-size:14px; border-radius:5px; border:1px solid #ccc; }
button { cursor:pointer; border:none; background:#007bff; color:white; transition:0.3s; }
button:hover { background:#0056b3; }
button.cancel { background:#dc3545; }
button.cancel:hover { background:#a71d2a; }

/* Tables */
table { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.08); margin-bottom:40px; }
th { background:#007bff; color:white; padding:12px 15px; text-align:left; }
td { padding:12px 15px; border-bottom:1px solid #eee; }
tr:hover { background:#f1f1f1; }
.status-cancelled { color:red; font-weight:bold; }

/* Responsive */
@media(max-width:768px){
    .sidebar { width:100%; height:auto; position:relative; padding:15px; flex-direction:row; justify-content:space-around; }
    .sidebar nav { display:flex; gap:10px; }
    .main-content { margin-left:0; padding:15px; }
    .dashboard-cards { flex-direction:column; gap:15px; }
}
</style>
</head>
<body>

<aside class="sidebar">
    <h2>Patient Panel</h2>
    <nav>
        <a href="patient_dashboard.php" class="active">Dashboard</a>
        <a href="bookappointment.php">Book Appointment</a>
        <a href="patientprofile.php">Profile</a>
        <a href="payment_history.php">Payments</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<main class="main-content">
    <h1>Welcome, <?= htmlspecialchars($patientName) ?></h1>

    <?php if($success): ?><div class="message success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if($error): ?><div class="message error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="dashboard-cards">
        <div class="card">
            <h3>Upcoming Appointments</h3>
            <p><?= count($upcomingAppointments) ?></p>
        </div>
        <div class="card">
            <h3>Past Appointments</h3>
            <p><?= count($pastAppointments) ?></p>
        </div>
    </div>

    <h2>Book New Appointment</h2>
    <form method="POST">
        <label>Doctor:</label>
        <select name="doctor_id" required>
            <option value="">-- Choose Doctor --</option>
            <?php foreach($doctors as $doctor): ?>
                <option value="<?= $doctor['id'] ?>"><?= htmlspecialchars($doctor['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Date & Time:</label>
        <input type="datetime-local" name="appointment_datetime" required>
        <button type="submit" name="book_appointment">Book</button>
    </form>

    <h2>Upcoming Appointments</h2>
    <?php if(count($upcomingAppointments) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Doctor</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($upcomingAppointments as $app): ?>
            <tr>
                <td><?= date('Y-m-d H:i', strtotime($app['appointment_datetime'])) ?></td>
                <td><?= htmlspecialchars($app['doctor_name']) ?></td>
                <td><?= htmlspecialchars($app['status']) ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="cancel_appointment_id" value="<?= $app['id'] ?>">
                        <button type="submit" class="cancel">Cancel</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="reschedule_appointment_id" value="<?= $app['id'] ?>">
                        <input type="datetime-local" name="new_datetime" required>
                        <button type="submit">Reschedule</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?><p>No upcoming appointments.</p><?php endif; ?>

    <h2>Past Appointments</h2>
    <?php if(count($pastAppointments) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Doctor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($pastAppointments as $app): ?>
            <tr>
                <td><?= date('Y-m-d H:i', strtotime($app['appointment_datetime'])) ?></td>
                <td><?= htmlspecialchars($app['doctor_name']) ?></td>
                <td class="<?= $app['status']==='cancelled'?'status-cancelled':'' ?>"><?= htmlspecialchars($app['status']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?><p>No past appointments.</p><?php endif; ?>
</main>

</body>
</html>
