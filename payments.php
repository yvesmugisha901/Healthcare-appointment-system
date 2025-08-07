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
$allowedMethods = ['Mobile Money', 'Credit Card', 'Cash']; // Add other methods if you have

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
    <meta charset="UTF-8" />
    <title>Payments Management - Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px 40px;
            background: #f9f9f9;
        }
        h1 {
            text-align: center;
            margin-bottom: 25px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        select, button {
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            cursor: pointer;
        }
        select {
            width: 150px;
            font-weight: bold;
        }
        button {
            background-color: #28a745;
            color: white;
            border: none;
        }
        button:hover {
            background-color: #218838;
        }
        .filter-form {
            margin-bottom: 20px;
            text-align: center;
        }
        .filter-form select {
            margin: 0 10px;
        }
    </style>
</head>
<body>

<h1>Payments Management</h1>

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

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Appointment Date</th>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Amount (USD)</th>
            <th>Payment Method</th>
            <th>Status</th>
            <th>Transaction ID</th>
            <th>Payment Date</th>
            <th>Update Status</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
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
                        <?php foreach ($allowedStatuses as $statusOption): ?>
                            <option value="<?= $statusOption ?>" <?= $row['status'] === $statusOption ? 'selected' : '' ?>>
                                <?= ucfirst($statusOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    <?php if ($result->num_rows === 0): ?>
        <tr><td colspan="10">No payments found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
