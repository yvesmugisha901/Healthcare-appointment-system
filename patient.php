<?php
session_start();
require 'connect.php';

// Make sure user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit;
}

$doctor_id = $_SESSION['user_id'];

// Get distinct patients who have appointments with this doctor
$query = "
    SELECT 
        u.id, 
        u.name, 
        u.email, 
        COUNT(a.id) AS total_appointments
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ?
    GROUP BY u.id, u.name, u.email
    ORDER BY u.name ASC
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
  <title>My Patients</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #eef2f5;
      padding: 40px;
    }
    h2 {
      text-align: center;
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
      padding: 12px;
      border: 1px solid #ddd;
      text-align: center;
    }
    th {
      background: #007BFF;
      color: white;
    }
    tr:nth-child(even) {
      background: #f9f9f9;
    }
  </style>
</head>
<body>
  <h2>My Patients</h2>

  <table>
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Email</th>
      <th>Total Appointments</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo htmlspecialchars($row['email']); ?></td>
        <td><?php echo $row['total_appointments']; ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>
