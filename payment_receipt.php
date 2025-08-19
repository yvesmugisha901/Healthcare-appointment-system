<?php
session_start();
require 'connect.php';

// Must be logged-in patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$patientId = $_SESSION['user_id'];
$paymentId = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
$txnId     = $_GET['txn_id'] ?? '';

// Build query depending on provided identifier
if ($paymentId > 0) {
    $sql = "
        SELECT p.id AS payment_id, p.transaction_id, p.amount, p.status, p.payment_date, p.payment_method,
               a.id AS appointment_id, a.appointment_datetime,
               d.name AS doctor_name, pat.name AS patient_name
        FROM payments p
        JOIN appointments a ON p.appointment_id = a.id
        JOIN users d ON a.doctor_id = d.id
        JOIN users pat ON a.patient_id = pat.id
        WHERE p.id = ? AND a.patient_id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $paymentId, $patientId);
} elseif (!empty($txnId)) {
    $sql = "
        SELECT p.id AS payment_id, p.transaction_id, p.amount, p.status, p.payment_date, p.payment_method,
               a.id AS appointment_id, a.appointment_datetime,
               d.name AS doctor_name, pat.name AS patient_name
        FROM payments p
        JOIN appointments a ON p.appointment_id = a.id
        JOIN users d ON a.doctor_id = d.id
        JOIN users pat ON a.patient_id = pat.id
        WHERE p.transaction_id = ? AND a.patient_id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $txnId, $patientId);
} else {
    die("No payment specified.");
}

$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("Invalid payment ID.");
}
$payment = $res->fetch_assoc();
$stmt->close();
$conn->close();

// Helper for formatting
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Receipt</title>
<style>
    body { font-family: Arial, sans-serif; background:#f7f9fc; padding:40px; }
    .receipt {
        max-width: 700px; margin: 0 auto; background:#fff; border-radius:10px;
        box-shadow:0 6px 18px rgba(0,0,0,0.08); padding:30px;
    }
    h1 { margin-top:0; color:#0d6efd; }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px 24px; margin-top:10px; }
    .row { display:flex; justify-content:space-between; margin:8px 0; }
    .label { color:#6c757d; }
    .value { font-weight:600; }
    .divider { height:1px; background:#e9ecef; margin:20px 0; }
    .actions { display:flex; gap:12px; margin-top:20px; }
    .btn {
        padding:10px 16px; border-radius:8px; border:none; cursor:pointer; font-weight:600;
        background:#0d6efd; color:#fff; text-decoration:none; display:inline-block;
    }
    .btn.secondary { background:#6c757d; }
    .status {
        display:inline-block; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:700;
        color:#fff;
    }
    .status.paid { background:#28a745; }
    .status.pending { background:#ffc107; color:#212529; }
    .status.failed { background:#dc3545; }
    @media print {
        .actions { display:none; }
        body { background:#fff; padding:0; }
        .receipt { box-shadow:none; border:none; }
    }
</style>
</head>
<body>
<div class="receipt">
    <h1>Payment Receipt</h1>

    <div class="grid">
        <div class="row"><span class="label">Receipt #</span><span class="value">#<?= h($payment['payment_id']); ?></span></div>
        <div class="row"><span class="label">Transaction ID</span><span class="value"><?= h($payment['transaction_id']); ?></span></div>
        <div class="row"><span class="label">Patient</span><span class="value"><?= h($payment['patient_name']); ?></span></div>
        <div class="row"><span class="label">Doctor</span><span class="value"><?= h($payment['doctor_name']); ?></span></div>
        <div class="row"><span class="label">Appointment</span><span class="value">#<?= h($payment['appointment_id']); ?></span></div>
        <div class="row"><span class="label">Appointment Date</span><span class="value"><?= h(date('M d, Y H:i', strtotime($payment['appointment_datetime']))); ?></span></div>
        <div class="row"><span class="label">Amount</span><span class="value">$<?= number_format((float)$payment['amount'], 2); ?></span></div>
        <div class="row"><span class="label">Method</span><span class="value"><?= h($payment['payment_method']); ?></span></div>
        <div class="row">
            <span class="label">Status</span>
            <span class="value">
                <?php $st = strtolower($payment['status']); ?>
                <span class="status <?= h($st); ?>"><?= h(ucfirst($payment['status'])); ?></span>
            </span>
        </div>
        <div class="row"><span class="label">Payment Date</span><span class="value"><?= h(date('M d, Y H:i', strtotime($payment['payment_date']))); ?></span></div>
    </div>

    <div class="divider"></div>

    <div class="actions">
        <button class="btn" onclick="window.print()">Print Receipt</button>
        <a class="btn secondary" href="patient_dashboard.php">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
