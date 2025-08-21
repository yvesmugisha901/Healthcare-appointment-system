<?php
session_start();
require 'connect.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle update status form submission
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

// Get filter values
$filterStatus = $_GET['status'] ?? '';
$filterMethod = $_GET['method'] ?? '';

// Build SQL query with optional filters
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
    --primary: #1e3a8a;
    --primary-light: #3b82f6;
    --success: #28a745;
    --danger: #dc3545;
    --bg: #f0f2f5;
    --text: #333;
}

body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: var(--bg);
    display: flex;
    min-height: 100vh;
}

/* Sidebar */
.sidebar {
    width: 220px;
    background-color: var(--primary);
    color: white;
    padding: 25px 20px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.sidebar h2 {
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 24px;
}

.sidebar h2 i { font-size: 28px; }

.sidebar nav a {
    color: #cce5ff;
    text-decoration: none;
    margin: 10px 0;
    padding: 10px 12px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.sidebar nav a.active,
.sidebar nav a:hover {
    background-color: var(--primary-light);
    color: #fff;
}

/* Main content */
.main-content {
    flex: 1;
    padding: 30px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    align-items: center;
}

h1 {
    color: var(--primary);
    margin-bottom: 20px;
    text-align: center;
}

/* Scrollable Table Card */
.table-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    padding: 20px;
    overflow-x: auto;
    max-width: 100%;
}

.table-card table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
    table-layout: fixed;
}

.table-card thead tr {
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    color: #fff;
    text-align: center;
}

.table-card th, .table-card td {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
    text-align: center;
    white-space: normal;
    word-wrap: break-word;
}

/* Specific widths for long columns */
.table-card th:nth-child(8), .table-card td:nth-child(8) { width: 150px; } /* Transaction ID */
.table-card th:nth-child(9), .table-card td:nth-child(9) { width: 120px; } /* Payment Date */

.table-card tbody {
    display: block;
    max-height: 400px;
    overflow-y: auto;
}

.table-card thead, .table-card tbody tr {
    display: table;
    width: 100%;
    table-layout: fixed;
}

.table-card tbody tr:hover {
    background-color: #f1f5fb;
}

/* Scrollbar Styling */
.table-card tbody::-webkit-scrollbar { width: 8px; }
.table-card tbody::-webkit-scrollbar-track { background: #f0f2f5; border-radius: 10px; }
.table-card tbody::-webkit-scrollbar-thumb { background-color: var(--primary-light); border-radius: 10px; }

/* Status update select */
.table-card select {
    padding: 5px;
    border-radius: 5px;
    border: 1px solid #ccc;
    cursor: pointer;
    font-weight: bold;
}
.table-card select:hover { border-color: var(--primary-light); }

/* Filter Form */
.filter-form { margin-bottom: 20px; text-align: center; }
.filter-form select { margin: 0 10px; font-weight: bold; }
</style>
</head>
<body>

<!-- Sidebar -->
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

<!-- Main Content -->
<div class="main-content">
    <h1>Payments Management</h1>

    <!-- Filter Form -->
    <form class="filter-form" method="GET" action="">
        <label for="status">Filter by Status:</label>
        <select name="status" id="status" onchange="this.form.submit()">
            <option value="">-- All --</option>
            <?php foreach ($allowedStatuses as $statusOption): ?>
                <option value="<?= $statusOption ?>" <?= $filterStatus === $statusOption ? 'selected' : '' ?>>
                    <?= ucfirst($statusOption) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="method">Filter by Payment Method:</label>
        <select name="method" id="method" onchange="this.form.submit()">
            <option value="">-- All --</option>
            <?php foreach ($allowedMethods as $methodOption): ?>
                <option value="<?= $methodOption ?>" <?= $filterMethod === $methodOption ? 'selected' : '' ?>>
                    <?= $methodOption ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- Table Card -->
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Appointment</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Amount (USD)</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Transaction ID</th>
                    <th>Payment Date</th>
                    <th>Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= date('M d, Y H:i', strtotime($row['appointment_datetime'])) ?></td>
                            <td><?= htmlspecialchars($row['patient_name']) ?></td>
                            <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                            <td>$<?= number_format($row['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($row['payment_method']) ?></td>
                            <td><?= ucfirst($row['status']) ?></td>
                            <td><?= htmlspecialchars($row['transaction_id']) ?></td>
                            <td><?= date('M d, Y H:i', strtotime($row['payment_date'])) ?></td>
                            <td>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="payment_id" value="<?= $row['id'] ?>">
                                    <select name="new_status" onchange="this.form.submit()">
                                        <?php foreach($allowedStatuses as $statusOption): ?>
                                            <option value="<?= $statusOption ?>" <?= $row['status'] === $statusOption ? 'selected' : '' ?>>
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
