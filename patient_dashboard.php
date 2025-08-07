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

// Fetch upcoming appointments
$stmt = $conn->prepare("SELECT a.id, a.appointment_datetime, u.name AS doctor_name, a.status 
                        FROM appointments a 
                        JOIN users u ON a.doctor_id = u.id 
                        WHERE a.patient_id = ? AND a.appointment_datetime >= NOW() 
                        ORDER BY a.appointment_datetime ASC");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$upcomingResult = $stmt->get_result();
$upcomingAppointments = $upcomingResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch past appointments
$stmt = $conn->prepare("SELECT a.id, a.appointment_datetime, u.name AS doctor_name, a.status 
                        FROM appointments a 
                        JOIN users u ON a.doctor_id = u.id 
                        WHERE a.patient_id = ? AND a.appointment_datetime < NOW() 
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
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Patient Dashboard - Healthcare System</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    margin: 0; padding: 0;
    display: flex;
    height: 100vh;
    overflow: hidden;
  }
  /* Sidebar */
  .sidebar {
    width: 220px;
    background-color: #0056b3;
    color: white;
    padding: 30px 20px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0; bottom: 0;
  }
  .sidebar h2 {
    margin: 0 0 30px 0;
    font-weight: 700;
    font-size: 24px;
    letter-spacing: 1.5px;
    text-align: center;
    user-select: none;
  }
  .sidebar nav {
    flex-grow: 1;
  }
  .sidebar nav a {
    display: block;
    color: #cce5ff;
    text-decoration: none;
    font-size: 16px;
    padding: 10px 15px;
    margin-bottom: 20px;
    border-radius: 6px;
    transition: background-color 0.3s ease, color 0.3s ease;
  }
  .sidebar nav a:hover,
  .sidebar nav a.active {
    background-color: #003d80;
    color: #ffffff;
  }

  /* Main content */
  .main-content {
    margin-left: 220px;
    padding: 40px 30px;
    box-sizing: border-box;
    overflow-y: auto;
    flex: 1;
    background: #fff;
  }
  h1, h2 {
    color: #007bff;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
  }
  th, td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    text-align: left;
  }
  th {
    background-color: #007bff;
    color: white;
  }
  tr:hover {
    background-color: #f1f1f1;
  }
  form {
    display: inline-block;
    margin: 0;
  }
  input[type="datetime-local"], select {
    padding: 5px;
    font-size: 14px;
  }
  button {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 6px 12px;
    margin-left: 5px;
    border-radius: 4px;
    cursor: pointer;
  }
  button.cancel {
    background-color: #dc3545;
  }
  button:hover {
    opacity: 0.9;
  }
  .message {
    margin: 15px 0;
    padding: 12px;
    border-radius: 5px;
    font-weight: bold;
  }
  .success {
    background-color: #d4edda;
    color: #155724;
  }
  .error {
    background-color: #f8d7da;
    color: #721c24;
  }

  @media (max-width: 768px) {
    body {
      flex-direction: column;
      height: auto;
    }
    .sidebar {
      width: 100%;
      position: relative;
      flex-direction: row;
      padding: 10px 5px;
      overflow-x: auto;
      white-space: nowrap;
    }
    .sidebar h2 {
      font-size: 18px;
      margin: 0 15px 0 0;
      line-height: 40px;
      flex-shrink: 0;
    }
    .sidebar nav a {
      margin: 0 10px 0 0;
      padding: 8px 12px;
      font-size: 14px;
      border-radius: 4px;
      display: inline-block;
    }
    .main-content {
      margin-left: 0;
      padding: 20px 15px;
      height: auto;
    }
  }
</style>
</head>
<body>

  <aside class="sidebar">
    <h2>Patient Dashboard</h2>
    <nav>
      <a href="patient_dashboard.php" class="active">Home</a>
      <a href="bookappointment.php">Book Appointment</a>
      <a href="patientprofile.php">Profile</a>
      <a href="payment_history.php">Payment History</a>
      <a href="logout.php">Logout</a>
    </nav>
  </aside>

  <main class="main-content">
    <h1>Welcome, <?= htmlspecialchars($patientName) ?></h1>

    <?php if ($success): ?>
      <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <h2>Book New Appointment</h2>
    <form method="POST">
      <label for="doctor_id">Select Doctor:</label><br>
      <select name="doctor_id" id="doctor_id" required>
        <option value="">-- Choose Doctor --</option>
        <?php foreach ($doctors as $doctor): ?>
          <option value="<?= $doctor['id'] ?>"><?= htmlspecialchars($doctor['name']) ?></option>
        <?php endforeach; ?>
      </select><br><br>

      <label for="appointment_datetime">Choose Date & Time:</label><br>
      <input type="datetime-local" name="appointment_datetime" id="appointment_datetime" required><br><br>

      <button type="submit" name="book_appointment">Book Appointment</button>
    </form>

    <hr style="margin: 30px 0;">

    <h2>Upcoming Appointments</h2>
    <?php if (count($upcomingAppointments) > 0): ?>
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
        <?php foreach ($upcomingAppointments as $app): ?>
        <tr>
          <td><?= date('Y-m-d H:i', strtotime($app['appointment_datetime'])) ?></td>
          <td><?= htmlspecialchars($app['doctor_name']) ?></td>
          <td><?= htmlspecialchars($app['status']) ?></td>
          <td>
            <!-- Cancel Form -->
            <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this appointment?');" style="display:inline;">
              <input type="hidden" name="cancel_appointment_id" value="<?= $app['id'] ?>">
              <button type="submit" class="cancel">Cancel</button>
            </form>

            <!-- Reschedule Form -->
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
    <?php else: ?>
      <p>No upcoming appointments.</p>
    <?php endif; ?>

    <h2 style="margin-top: 40px;">Past Appointments</h2>
    <?php if (count($pastAppointments) > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Date & Time</th>
          <th>Doctor</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pastAppointments as $app): ?>
        <tr>
          <td><?= date('Y-m-d H:i', strtotime($app['appointment_datetime'])) ?></td>
          <td><?= htmlspecialchars($app['doctor_name']) ?></td>
          <td><?= htmlspecialchars($app['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <p>No past appointments.</p>
    <?php endif; ?>
  </main>

</body>
</html>
