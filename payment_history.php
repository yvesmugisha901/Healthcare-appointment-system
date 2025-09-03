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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #2a9d8f;
    --primary-dark: #1d7870;
    --primary-light: #7fcdc3;
    --secondary: #e76f51;
    --neutral-dark: #264653;
    --neutral-light: #f8f9fa;
    --white: #ffffff;
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
    --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
    --radius: 12px;
    --transition: all 0.3s ease;
}

/* Reset & Body */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
body { display:flex; min-height:100vh; background: var(--neutral-light); color: var(--neutral-dark); }

/* Sidebar */
.sidebar {
    width: 220px; background: var(--primary); color: var(--white);
    display:flex; flex-direction:column; padding:25px 20px; min-height:100vh;
}
.sidebar h2 { margin-bottom:30px; display:flex; align-items:center; gap:10px; font-size:22px; }
.sidebar h2 i { font-size:26px; }
.sidebar nav a {
    display:flex; align-items:center; gap:10px; padding:10px 12px;
    border-radius: var(--radius); text-decoration:none; color:#cce5ff; transition: var(--transition);
    margin:6px 0;
}
.sidebar nav a.active, .sidebar nav a:hover { background: var(--primary-light); color:#fff; }

/* Main content */
.main-content { flex:1; padding:30px; display:flex; justify-content:center; }
.content-wrapper { width:100%; max-width:1000px; }

/* Heading */
h1 { text-align:center; color: var(--primary); margin-bottom:25px; }

/* Table Card */
.table-card {
    background: var(--white); border-radius: var(--radius);
    box-shadow: var(--shadow-md); padding:20px; overflow:auto; max-height:600px;
}
table { width:100%; border-collapse: collapse; min-width: 800px; }
th, td { padding:12px; border-bottom:1px solid #eee; text-align:center; white-space:nowrap; }
th { background: linear-gradient(90deg,var(--primary),var(--secondary)); color:#fff; }
tr:nth-child(even) { background: #f2f2f2; }
tr:hover { background: #e0f0ff; transition: var(--transition); }

/* Responsive */
@media(max-width:768px){
    .sidebar { width:100%; height:auto; flex-direction:row; justify-content:space-around; padding:10px; }
    .main-content { margin-left:0; padding:20px; }
    table, th, td { font-size:14px; }
}
</style>
</head>
<body>

<aside class="sidebar">
    <h2><i class="fa fa-user"></i> Patient</h2>
    <nav>
        <a href="patient_dashboard.php"><i class="fa fa-home"></i> Home</a>
        <a href="bookappointment.php"><i class="fa fa-calendar-plus"></i> Book Appointment</a>
        <a href="patientprofile.php"><i class="fa fa-id-card"></i> Profile</a>
        <a href="payment_history.php" class="active"><i class="fa fa-credit-card"></i> Payment History</a>
        <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>

<div class="main-content">
    <div class="content-wrapper">
        <h1>Payment History for <?= htmlspecialchars($patientName) ?></h1>
        <div class="table-card">
        <?php if(count($payments) > 0): ?>
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
                    <?php foreach($payments as $payment): ?>
                        <tr>
                            <td><?= date('M d, Y H:i', strtotime($payment['payment_date'])) ?></td>
                            <td><?= date('M d, Y H:i', strtotime($payment['appointment_datetime'])) ?></td>
                            <td>$<?= number_format($payment['amount'],2) ?></td>
                            <td><?= ucfirst(htmlspecialchars($payment['status'])) ?></td>
                            <td><?= htmlspecialchars($payment['transaction_id']) ?></td>
                            <td><?= htmlspecialchars($payment['notes']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center; font-weight:bold;">No payment history available.</p>
        <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
