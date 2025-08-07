<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Fetch counts
$doctorCount = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'doctor'")->fetch_assoc()['total'];
$patientCount = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'patient'")->fetch_assoc()['total'];
$appointmentCount = $conn->query("SELECT COUNT(*) AS total FROM appointments")->fetch_assoc()['total'];

// Fetch appointments per doctor
$doctorAppointments = [];
$result1 = $conn->query("
    SELECT u.name AS doctor, COUNT(a.id) AS count
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    GROUP BY a.doctor_id
");
while ($row = $result1->fetch_assoc()) {
    $doctorAppointments[] = $row;
}

// Fetch appointments per day (last 7 days)
$dayAppointments = [];
$result2 = $conn->query("
    SELECT DATE(appointment_datetime) AS day, COUNT(id) AS count
    FROM appointments
    WHERE appointment_datetime >= CURDATE() - INTERVAL 7 DAY
    GROUP BY day
    ORDER BY day ASC
");
while ($row = $result2->fetch_assoc()) {
    $dayAppointments[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Reports with Charts</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f8;
      margin: 0;
      padding: 0;
    }
    .container {
      padding: 30px;
      max-width: 1000px;
      margin: auto;
    }
    h1 {
      text-align: center;
      margin-bottom: 30px;
    }
    .card-container {
      display: flex;
      justify-content: space-around;
      gap: 20px;
      flex-wrap: wrap;
    }
    .card {
      flex: 1 1 250px;
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      text-align: center;
    }
    .card h2 {
      font-size: 2.5em;
      color: #007BFF;
    }
    canvas {
      background: #fff;
      margin-top: 40px;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Admin Reports</h1>

    <div class="card-container">
      <div class="card">
        <h2><?php echo $doctorCount; ?></h2>
        <p>Total Doctors</p>
      </div>
      <div class="card">
        <h2><?php echo $patientCount; ?></h2>
        <p>Total Patients</p>
      </div>
      <div class="card">
        <h2><?php echo $appointmentCount; ?></h2>
        <p>Total Appointments</p>
      </div>
    </div>

    <canvas id="doctorChart" height="100"></canvas>
    <canvas id="dailyChart" height="100"></canvas>
  </div>

  <script>
    const doctorData = <?php echo json_encode($doctorAppointments); ?>;
    const doctorLabels = doctorData.map(item => item.doctor);
    const doctorCounts = doctorData.map(item => item.count);

    const ctx1 = document.getElementById('doctorChart').getContext('2d');
    new Chart(ctx1, {
      type: 'bar',
      data: {
        labels: doctorLabels,
        datasets: [{
          label: 'Appointments per Doctor',
          data: doctorCounts,
          backgroundColor: 'rgba(54, 162, 235, 0.7)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: { beginAtZero: true }
        }
      }
    });

    const dayData = <?php echo json_encode($dayAppointments); ?>;
    const dayLabels = dayData.map(item => item.day);
    const dayCounts = dayData.map(item => item.count);

    const ctx2 = document.getElementById('dailyChart').getContext('2d');
    new Chart(ctx2, {
      type: 'line',
      data: {
        labels: dayLabels,
        datasets: [{
          label: 'Appointments per Day (Last 7 Days)',
          data: dayCounts,
          backgroundColor: 'rgba(255, 99, 132, 0.2)',
          borderColor: 'rgba(255, 99, 132, 1)',
          fill: true,
          tension: 0.3,
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  </script>
</body>
</html>
