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
    <!-- FontAwesome for icons -->
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
            margin-left: 220px; /* width of sidebar */
            box-sizing: border-box;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h1 i {
            color: #007BFF;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        thead tr {
            background-color: #007BFF;
            color: white;
            font-size: 15px;
        }

        th, td {
            padding: 14px 16px;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 14px;
        }

        tbody tr:hover {
            background-color: #f9f9f9;
            transition: background 0.2s;
        }

        .status-scheduled {
            color: #007BFF;
            font-weight: 600;
        }
        .status-completed {
            color: #28a745;
            font-weight: 600;
        }
        .status-cancelled {
            color: #dc3545;
            font-weight: 600;
        }

        .empty {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            color: #666;
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
    <h1><i class="fa-solid fa-calendar-check"></i> Your Appointments</h1>

    <?php if (count($appointments) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><i class="fa-regular fa-calendar"></i> Date & Time</th>
                    <th><i class="fa-solid fa-user"></i> Patient</th>
                    <th><i class="fa-solid fa-note-sticky"></i> Notes</th>
                    <th><i class="fa-solid fa-circle-check"></i> Status</th>
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
        <div class="empty">
            <i class="fa-regular fa-calendar-xmark fa-2x" style="color:#999;"></i>
            <p>No appointments found.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
