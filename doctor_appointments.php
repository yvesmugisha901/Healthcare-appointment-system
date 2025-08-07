<?php
session_start();
require 'connect.php';

// Redirect if not logged in or not doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctorId = $_SESSION['user_id'];

// Fetch all appointments for this doctor (ordered by date)
$stmt = $conn->prepare("
    SELECT a.id, a.appointment_datetime, u.name AS patient_name, a.status, a.notes
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_datetime DESC
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
    <title>Doctor Appointments - Healthcare System</title>
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
            margin-left: 200px; /* width of sidebar */
            box-sizing: border-box;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
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
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        tbody tr:hover {
            background-color: #f1f1f1;
        }
        .status-scheduled {
            color: #007BFF;
            font-weight: bold;
        }
        .status-completed {
            color: #28a745;
            font-weight: bold;
        }
        .status-cancelled {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
    <h1>Your Appointments</h1>
    <?php if (count($appointments) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Patient</th>
                    <th>Notes</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $app): ?>
                    <tr>
                        <td><?php echo date("Y-m-d H:i", strtotime($app['appointment_datetime'])); ?></td>
                        <td><?php echo htmlspecialchars($app['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($app['notes']); ?></td>
                        <td class="status-<?php echo strtolower($app['status']); ?>">
                            <?php echo htmlspecialchars($app['status']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No appointments found.</p>
    <?php endif; ?>
</div>

</body>
</html>
