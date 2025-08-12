<?php
session_start();
require 'connect.php';

// Check logged-in patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$patientId = $_SESSION['user_id'];
$appointmentId = $_GET['appointment_id'] ?? null;
$successMessage = '';
$errorMessage = '';

// Validate appointment belongs to patient
if (!$appointmentId) {
    die("No appointment specified.");
}

$stmt = $conn->prepare("SELECT a.appointment_datetime, u.name AS doctor_name 
                        FROM appointments a 
                        JOIN users u ON a.doctor_id = u.id 
                        WHERE a.id = ? AND a.patient_id = ?");
$stmt->bind_param("ii", $appointmentId, $patientId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Appointment not found or you don't have permission.");
}

$appointment = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $method = $_POST['payment_method'] ?? '';
    $allowedMethods = ['Mobile Money', 'Credit Card', 'Cash'];

    if ($amount <= 0) {
        $errorMessage = "Invalid amount.";
    } elseif (!in_array($method, $allowedMethods)) {
        $errorMessage = "Invalid payment method.";
    } else {
        // Generate transaction ID (simple example)
        $transactionId = uniqid('TXN');

        $stmt = $conn->prepare("INSERT INTO payments (appointment_id, amount, status, transaction_id, payment_date, payment_method) 
                                VALUES (?, ?, 'pending', ?, NOW(), ?)");
        $stmt->bind_param("idss", $appointmentId, $amount, $transactionId, $method);
        if ($stmt->execute()) {
            $successMessage = "Payment recorded successfully! Transaction ID: $transactionId";
        } else {
            $errorMessage = "Payment failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Make Payment</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f9f9f9; }
        .container { max-width: 450px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input, select, button { width: 100%; padding: 10px; margin-top: 8px; border-radius: 5px; border: 1px solid #ccc; font-size: 16px; }
        button { background-color: #007bff; color: white; border: none; cursor: pointer; margin-top: 20px; }
        button:hover { background-color: #0056b3; }
        .success { background-color: #d4edda; color: #155724; padding: 10px; margin-top: 15px; border-radius: 5px; }
        .error { background-color: #f8d7da; color: #721c24; padding: 10px; margin-top: 15px; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Pay for Appointment</h2>
    <p><strong>Doctor:</strong> <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
    <p><strong>Date & Time:</strong> <?php echo htmlspecialchars($appointment['appointment_datetime']); ?></p>

    <?php if ($successMessage): ?>
        <div class="success"><?php echo $successMessage; ?></div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="error"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="amount">Amount (USD):</label>
        <input type="number" name="amount" id="amount" min="1" step="0.01" required />

        <label for="payment_method">Payment Method:</label>
        <select name="payment_method" id="payment_method" required>
            <option value="">-- Select Method --</option>
            <option value="Mobile Money">Mobile Money</option>
            <option value="Credit Card">Credit Card</option>
            <option value="Cash">Cash</option>
        </select>

        <button type="submit">Pay Now</button>
    </form>
</div>
</body>
</html>
