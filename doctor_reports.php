<?php
session_start();
require 'connect.php';

// Only allow logged-in doctors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctorId = $_SESSION['user_id'];

// Fetch counts grouped by status
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
  font-family: 'Segoe UI', Tahoma, sans-serif;
  margin: 0;
  display: flex;
  background-color: #f4f6f9;
  min-height: 100vh;
}
.main-content {
  flex: 1;
  padding: 30px;
  margin-left: 220px; /* match sidebar */
  box-sizing: border-box;
}
h1 {
  margin-bottom: 20px;
  color:#333;
  font-size: 24px;
  display: flex;
  align-items: center;
  gap: 10px;
}
h1 i { color:#007BFF; }

.report-card {
  background: #fff;
  border-radius: 12px;
  padding: 25px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.1);
  max-width: 400px;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th, td {
  padding: 14px 16px;
  border-bottom: 1px solid #eee;
  font-size: 15px;
}
thead tr {
  background-color: #007BFF;
  color: white;
}
.status-scheduled { color: #007BFF; font-weight: 600; }
.status-completed { color: #28a745; font-weight: 600; }
.status-cancelled { color: #dc3545; font-weight: 600; }
</style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
  <h1><i class="fa-solid fa-chart-line"></i> Appointment Reports</h1>

  <div class="report-card">
    <table>
      <thead>
        <tr>
          <th>Status</th>
          <th>Count</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="status-scheduled"><i class="fa-regular fa-calendar"></i> Scheduled</td>
          <td><?= $reportData['Scheduled']; ?></td>
        </tr>
        <tr>
          <td class="status-completed"><i class="fa-solid fa-circle-check"></i> Completed</td>
          <td><?= $reportData['Completed']; ?></td>
        </tr>
        <tr>
          <td class="status-cancelled"><i class="fa-solid fa-ban"></i> Cancelled</td>
          <td><?= $reportData['Cancelled']; ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
