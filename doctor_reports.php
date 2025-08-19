<?php
session_start();
require 'connect.php';

// Only allow logged-in doctors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctorId = $_SESSION['user_id'];

// Fetch counts grouped by status (trim and lowercase for consistency)
$stmt = $conn->prepare("
    SELECT TRIM(LOWER(status)) AS status_lower, COUNT(*) AS count
    FROM appointments
    WHERE doctor_id = ?
    GROUP BY status_lower
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();

$reportData = [
    'Scheduled' => 0,
    'Completed' => 0,
    'Cancelled' => 0
];

$statusMap = [
    'booked' => 'Scheduled',
    'completed' => 'Completed',
    'done' => 'Completed',
    'cancelled' => 'Cancelled',
    'canceled' => 'Cancelled'
];

while ($row = $result->fetch_assoc()) {
    $dbStatus = $row['status_lower'];
    $displayStatus = $statusMap[$dbStatus] ?? ucfirst($dbStatus);
    if (!isset($reportData[$displayStatus])) {
        $reportData[$displayStatus] = 0;
    }
    $reportData[$displayStatus] += $row['count'];
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Doctor Reports - Healthcare System</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
h1 { margin-bottom: 20px; color:#007BFF; }
table {
  width: 300px;
  border-collapse: collapse;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  margin-bottom: 40px;
}
th, td {
  padding: 12px 15px;
  border-bottom: 1px solid #eee;
  text-align: left;
}
thead tr { background-color: #007BFF; color: white; }
canvas { background:white; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); padding:20px; }
</style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
  <h1>Appointment Reports</h1>

  <!-- Table Summary -->
  <table>
    <thead>
      <tr><th>Status</th><th>Count</th></tr>
    </thead>
    <tbody>
      <tr><td>Scheduled</td><td><?= $reportData['Scheduled']; ?></td></tr>
      <tr><td>Completed</td><td><?= $reportData['Completed']; ?></td></tr>
      <tr><td>Cancelled</td><td><?= $reportData['Cancelled']; ?></td></tr>
    </tbody>
  </table>

  <!-- Chart Visualization -->
  <canvas id="appointmentChart" height="150"></canvas>
</div>

<script>
const ctx = document.getElementById('appointmentChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Scheduled', 'Completed', 'Cancelled'],
        datasets: [{
            data: [
                <?= $reportData['Scheduled']; ?>,
                <?= $reportData['Completed']; ?>,
                <?= $reportData['Cancelled']; ?>
            ],
            backgroundColor: [
                'rgba(54, 162, 235, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(255, 99, 132, 0.7)'
            ],
            borderColor: [
                'rgba(54, 162, 235, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(255, 99, 132, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

</body>
</html>
