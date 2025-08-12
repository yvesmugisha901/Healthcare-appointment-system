<?php
session_start();
require 'connect.php';

// Only allow logged-in doctors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctorId = $_SESSION['user_id'];

// Prepare and execute query
$stmt = $conn->prepare("
    SELECT LOWER(status) AS status_lower, COUNT(*) AS count
    FROM appointments
    WHERE doctor_id = ?
    GROUP BY status_lower
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();

$reportData = [];
$statusMap = [
    'booked' => 'Scheduled',    // Map 'booked' in DB to 'Scheduled' in display
    'completed' => 'Completed',
    'done' => 'Completed',       // If you have 'done' as synonym
    'cancelled' => 'Cancelled',
    'canceled' => 'Cancelled',   // Handle US spelling variant
    // add more mappings if needed
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
  table {
    width: 300px;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
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
</style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
  <h1>Appointment Reports</h1>
  <table>
    <thead>
      <tr><th>Status</th><th>Count</th></tr>
    </thead>
    <tbody>
      <tr><td>Scheduled</td><td><?php echo $reportData['Scheduled'] ?? 0; ?></td></tr>
      <tr><td>Completed</td><td><?php echo $reportData['Completed'] ?? 0; ?></td></tr>
      <tr><td>Cancelled</td><td><?php echo $reportData['Cancelled'] ?? 0; ?></td></tr>
    </tbody>
  </table>
</div>

</body>
</html>
