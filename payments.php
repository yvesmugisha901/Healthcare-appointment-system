<?php
session_start();
require 'connect.php';

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'], $_POST['new_status'])) {
    $paymentId = intval($_POST['payment_id']);
    $newStatus = $_POST['new_status'];
    $allowedStatuses = ['pending', 'completed', 'failed', 'refunded'];

    if (in_array($newStatus, $allowedStatuses)) {
        $stmt = $conn->prepare("UPDATE payments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $paymentId);
        $stmt->execute();
        $stmt->close();
    }
}

// Filters
$filterStatus = $_GET['status'] ?? '';
$filterMethod = $_GET['method'] ?? '';

$sql = "SELECT p.id, p.appointment_id, p.amount, p.status, p.transaction_id, p.payment_date, p.payment_method,
        a.appointment_datetime, u_patient.name AS patient_name, u_doctor.name AS doctor_name
        FROM payments p
        JOIN appointments a ON p.appointment_id = a.id
        JOIN users u_patient ON a.patient_id = u_patient.id
        JOIN users u_doctor ON a.doctor_id = u_doctor.id";

$params = [];
$types = "";
$whereClauses = [];

$allowedStatuses = ['pending', 'completed', 'failed', 'refunded'];
$allowedMethods = ['Mobile Money', 'Credit Card', 'Cash'];

if ($filterStatus && in_array($filterStatus, $allowedStatuses)) {
    $whereClauses[] = "p.status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

if ($filterMethod && in_array($filterMethod, $allowedMethods)) {
    $whereClauses[] = "p.payment_method = ?";
    $params[] = $filterMethod;
    $types .= "s";
}

if (count($whereClauses) > 0) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " ORDER BY p.payment_date DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Payments Management - Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #2a9d8f;
    --primary-dark: #1d7870;
    --primary-light: #7fcdc3;
    --secondary: #e76f51;
    --neutral-dark: #264653;
    --neutral-light: #f8f9fa;
    --success: #28a745;
    --pending: #ff9900;
}

/* Reset */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }

body { display:flex; min-height:100vh; background: var(--neutral-light); color: var(--neutral-dark); }

/* Sidebar */
.sidebar {
    width: 240px; background: var(--primary); color: #fff; height: 100vh; position: fixed; top:0; left:0;
    padding:25px 20px; display:flex; flex-direction:column; transition:0.3s; z-index:1000;
}
.sidebar h2 { display:flex; align-items:center; gap:10px; margin-bottom:40px; font-size:24px; }
.sidebar h2 i { font-size:28px; }
.sidebar nav { display:flex; flex-direction:column; gap:10px; flex-grow:1; }
.sidebar nav a { color:#cce5ff; text-decoration:none; padding:10px 15px; border-radius:8px; display:flex; align-items:center; gap:10px; transition:0.3s; }
.sidebar nav a.active, .sidebar nav a:hover { background: var(--primary-dark); color:#fff; }

/* Main content */
.main-content { flex:1; margin-left:240px; padding:30px; display:flex; flex-direction:column; align-items:center; }
h1 { color: var(--primary); margin-bottom:20px; text-align:center; }

/* Filter Form */
.filter-form { margin-bottom:20px; text-align:center; }
.filter-form select { margin: 0 10px; font-weight:bold; padding:5px; }

/* Table Card */
.table-card { background:#fff; border-radius:12px; box-shadow:0 8px 25px rgba(0,0,0,0.1); padding:20px; overflow-x:auto; width:100%; }
.table-card table { width:100%; border-collapse: collapse; min-width: 1000px; table-layout: fixed; }
.table-card thead tr { background: linear-gradient(90deg,var(--primary),var(--primary-light)); color:#fff; text-align:center; }
.table-card th, .table-card td { padding:10px; text-align:center; border-bottom:1px solid #eee; font-size:14px; word-wrap: break-word; }
.table-card tbody { display:block; max-height:400px; overflow-y:auto; }
.table-card thead, .table-card tbody tr { display:table; width:100%; table-layout:fixed; }
.table-card tbody tr:hover { background:#f1f5fb; }
.table-card select { padding:5px; border-radius:5px; border:1px solid #ccc; cursor:pointer; font-weight:bold; }
.table-card select:hover { border-color: var(--primary-light); }

/* Status Colors */
.status-pending { color: var(--pending); font-weight:600; }
.status-completed { color: var(--success); font-weight:600; }
.status-failed { color: var(--secondary); font-weight:600; }
.status-refunded { color: var(--primary-light); font-weight:600; }

/* Scrollbar */
.table-card tbody::-webkit-scrollbar { width:8px; }
.table-card tbody::-webkit-scrollbar-track { background:#f8f9fa; border-radius:10px; }
.table-card tbody::-webkit-scrollbar-thumb { background: var(--primary-light); border-radius:10px; }

/* Responsive */
@media(max-width:768px){
    .sidebar { width:100%; height:auto; flex-direction:row; justify-content:space-around; padding:15px; }
    .main-content{ margin-left:0; padding:20px; }
}
</style>
</head>
<body>

<aside class="sidebar">
    <h2><i class="fa fa-stethoscope"></i> HealthSys</h2>
    <nav>
        <a href="admin_dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_users.php"><i class="fa fa-users"></i> Manage Users</a>
        <a href="appointments.php"><i class="fa fa-calendar-check"></i> Appointments</a>
        <a href="payments.php" class="active"><i class="fa fa-credit-card"></i> Payments</a>
        <a href="settings.php"><i class="fa fa-cog"></i> Settings</a>
        <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>

<div class="main-content">
    <h1>Payments Management</h1>

    <form class="filter-form" method="GET" action="">
        <label for="status">Status:</label>
        <select name="status" id="status" onchange="this.form.submit()">
            <option value="">-- All --</option>
            <?php foreach($allowedStatuses as $statusOption): ?>
                <option value="<?= $statusOption ?>" <?= $filterStatus === $statusOption ? 'selected' : '' ?>>
                    <?= ucfirst($statusOption) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="method">Method:</label>
        <select name="method" id="method" onchange="this.form.submit()">
            <option value="">-- All --</option>
            <?php foreach($allowedMethods as $methodOption): ?>
                <option value="<?= $methodOption ?>" <?= $filterMethod === $methodOption ? 'selected' : '' ?>>
                    <?= $methodOption ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Appointment</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Transaction ID</th>
                    <th>Payment Date</th>
                    <th>Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= date('M d, Y H:i', strtotime($row['appointment_datetime'])) ?></td>
                            <td><?= htmlspecialchars($row['patient_name']) ?></td>
                            <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                            <td>$<?= number_format($row['amount'],2) ?></td>
                            <td><?= htmlspecialchars($row['payment_method']) ?></td>
                            <td class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></td>
                            <td><?= htmlspecialchars($row['transaction_id']) ?></td>
                            <td><?= date('M d, Y H:i', strtotime($row['payment_date'])) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="payment_id" value="<?= $row['id'] ?>">
                                    <select name="new_status" onchange="this.form.submit()">
                                        <?php foreach($allowedStatuses as $statusOption): ?>
                                            <option value="<?= $statusOption ?>" <?= $row['status']===$statusOption?'selected':'' ?>>
                                                <?= ucfirst($statusOption) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="10">No payments found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
