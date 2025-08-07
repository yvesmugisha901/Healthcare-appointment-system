<?php
session_start();
require 'connect.php';

// Ensure user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit;
}

$doctor_id = $_SESSION['user_id'];

// Fetch upcoming appointments for this doctor, ordered by date/time
$query = "
    SELECT 
        a.id, 
        a.appointment_datetime, 
        u.name AS patient_name, 
        a.status
    FROM 
        appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE 
        a.doctor_id = ? 
        AND a.appointment_datetime >= NOW()
    ORDER BY a.appointment_datetime ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>My Schedule - Doctor</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #f0f4f8;
    padding: 20px;
  }
  h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #004080;
  }
  table {
    width: 90%;
    margin: auto;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }
  th, td {
    padding: 12px 15px;
    border: 1px solid #ccc;
    text-align: center;
  }
  th {
    background: #004080;
    color: white;
  }
  tr:nth-child(even) {
    background: #f9f9f9;
  }
  .status-confirmed {
    color: green;
    font-weight: bold;
  }
  .status-pending {
    color: orange;
    font-weight: bold;
  }
  .status-cancelled {
    color: red;
    font-weight: bold;
  }
</style>
</head>
<body>
  <h2>My Upcoming Appointments</h2>
  <table>
    <tr>
      <th>ID</th>
      <th>Patient</th>
      <th>Date</th>
      <th>Time</th>
      <th>Status</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): 
      $datetime = new DateTime($row['appointment_datetime']);
      $date = $datetime->format('Y-m-d');
      $time = $datetime->format('H:i');
      $status = strtolower($row['status']);
    ?>
      <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
        <td><?php echo $date; ?></td>
        <td><?php echo $time; ?></td>
        <td class="status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></td>
      </tr>
    <?php endwhile; ?>

  </table>
</body>
</html>
