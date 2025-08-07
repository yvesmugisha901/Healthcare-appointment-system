<?php
session_start();
require 'connect.php';

// Redirect if not logged in or not doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctorId = $_SESSION['user_id'];

// Get doctor's name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$stmt->bind_result($doctorName);
$stmt->fetch();
$stmt->close();

// Get total appointments this week (Mon-Sun)
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM appointments 
    WHERE doctor_id = ? 
      AND appointment_datetime BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
      AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
      AND status != 'Cancelled'
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$stmt->bind_result($totalThisWeek);
$stmt->fetch();
$stmt->close();

// Get upcoming appointments (from now on, scheduled only)
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM appointments 
    WHERE doctor_id = ? 
      AND appointment_datetime >= NOW() 
      AND status = 'Scheduled'
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$stmt->bind_result($upcomingAppointments);
$stmt->fetch();
$stmt->close();

// Get today's scheduled appointments with patient name and notes
$stmt = $conn->prepare("
    SELECT a.id, TIME(a.appointment_datetime) AS time, u.name AS patient, a.notes
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ?
      AND DATE(a.appointment_datetime) = CURDATE()
      AND a.status = 'Scheduled'
    ORDER BY a.appointment_datetime ASC
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Doctor Dashboard - Healthcare System</title>
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
      margin-bottom: 30px;
      color: #333;
    }

    .dashboard-cards {
      display: flex;
      gap: 20px;
      margin-bottom: 40px;
    }

    .card {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      flex: 1;
      text-align: center;
    }

    .card h3 {
      margin-bottom: 15px;
      color: #007BFF;
      font-weight: 600;
    }

    .card p {
      font-size: 24px;
      font-weight: bold;
      margin: 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    thead tr {
      background-color: #007BFF;
      color: white;
    }

    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }

    tbody tr:hover {
      background-color: #f1f1f1;
    }

    button {
      background: #28a745;
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: 600;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #218838;
    }
  </style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
  <h1>Welcome, <?php echo htmlspecialchars($doctorName); ?></h1>

  <div class="dashboard-cards">
    <div class="card">
      <h3>Total Appointments This Week</h3>
      <p><?php echo $totalThisWeek; ?></p>
    </div>
    <div class="card">
      <h3>Upcoming Appointments</h3>
      <p><?php echo $upcomingAppointments; ?></p>
    </div>
  </div>

  <section>
    <h2>Today's Appointments</h2>
    <table>
      <thead>
        <tr>
          <th>Time</th>
          <th>Patient</th>
          <th>Notes</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($appointments) > 0): ?>
          <?php foreach ($appointments as $app): ?>
            <tr>
              <td><?php echo date("h:i A", strtotime($app['time'])); ?></td>
              <td><?php echo htmlspecialchars($app['patient']); ?></td>
              <td><?php echo htmlspecialchars($app['notes']); ?></td>
              <td>
                <form method="POST" action="mark_completed.php" style="margin:0;">
                  <input type="hidden" name="appointment_id" value="<?php echo $app['id']; ?>" />
                  <button type="submit">Mark Completed</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4">No appointments scheduled for today.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>
</div>

</body>
</html>
