<?php
session_start();
require 'connect.php';

// Check if logged in and role is patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$patientId = $_SESSION['user_id'];

// Get patient name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$stmt->bind_result($patientName);
$stmt->fetch();
$stmt->close();

// Fetch payment history for this patient
$sql = "SELECT p.payment_date, p.amount, p.status, p.transaction_id, 
               a.appointment_datetime, a.notes
        FROM payments p
        JOIN appointments a ON p.appointment_id = a.id
        WHERE a.patient_id = ?
        ORDER BY p.payment_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment History</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #f4f6f9;
      display: flex;
      min-height: 100vh;
    }
    .sidebar {
      width: 220px;
      background-color: #0056b3;
      color: white;
      padding: 30px 20px;
      box-sizing: border-box;
      position: fixed;
      height: 100%;
    }
    .sidebar h2 {
      font-size: 24px;
      text-align: center;
      margin-bottom: 30px;
    }
    .sidebar nav a {
      display: block;
      padding: 12px 15px;
      color: #cce5ff;
      text-decoration: none;
      margin-bottom: 12px;
      border-radius: 6px;
      transition: background 0.3s;
    }
    .sidebar nav a:hover,
    .sidebar nav a.active {
      background-color: #003d80;
      color: #ffffff;
    }
    .main-content {
      margin-left: 220px;
      padding: 30px;
      flex: 1;
      background-color: white;
    }
    .main-content h1 {
      color: #333;
      margin-bottom: 25px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #f9f9f9;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    th {
      background-color: #007bff;
      color: white;
      font-weight: bold;
    }
    tr:hover {
      background-color: #e6f0ff;
    }
    @media (max-width: 768px) {
      .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        flex-direction: row;
        display: flex;
        justify-content: space-around;
        padding: 10px;
      }
      .main-content {
        margin-left: 0;
        padding: 20px;
      }
      table, th, td {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>

  <aside class="sidebar">
    <h2>Patient Dashboard</h2>
    <nav>
      <a href="patient_dashboard.php">Home</a>
      <a href="bookappointment.php">Book Appointment</a>
      <a href="patientprofile.php">Profile</a>
      <a href="payment_history.php" class="active">Payment History</a>
      <a href="logout.php">Logout</a>
    </nav>
  </aside>

  <main class="main-content">
    <h1>Payment History for <?= htmlspecialchars($patientName) ?></h1>

    <?php if (count($payments) > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Payment Date</th>
            <th>Appointment Date</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Transaction ID</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $payment): ?>
            <tr>
              <td><?= date('M d, Y H:i', strtotime($payment['payment_date'])) ?></td>
              <td><?= date('M d, Y H:i', strtotime($payment['appointment_datetime'])) ?></td>
              <td><?= number_format($payment['amount'], 2) ?> USD</td>
              <td><?= ucfirst(htmlspecialchars($payment['status'])) ?></td>
              <td><?= htmlspecialchars($payment['transaction_id']) ?></td>
              <td><?= htmlspecialchars($payment['notes']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No payment history available.</p>
    <?php endif; ?>
  </main>

</body>
</html>
