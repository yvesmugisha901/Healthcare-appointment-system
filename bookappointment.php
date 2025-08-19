<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'connect.php';

// Check logged-in patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$patientId = $_SESSION['user_id'];
$patientName = $_SESSION['name'] ?? 'Patient';

// Fetch doctors list
$doctors = [];
$docResult = $conn->query("SELECT id, name, specialization FROM users WHERE role='doctor'");
if ($docResult) {
    while ($row = $docResult->fetch_assoc()) {
        $doctors[] = $row;
    }
}

$successMessage = '';
$errorMessage = '';
$paymentLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = $_POST['doctor'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (!$doctorId || !$date || !$time) {
        $errorMessage = "Please fill in all required fields.";
    } else {
        $appointment_datetime = $date . ' ' . $time . ':00';

        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_datetime, notes) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            $errorMessage = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("iiss", $patientId, $doctorId, $appointment_datetime, $notes);
            if ($stmt->execute()) {
                $appointmentId = $stmt->insert_id;
                $successMessage = "Appointment booked successfully for $date at $time.";
                $paymentLink = "patient_payment.php?appointment_id=$appointmentId";

                // Insert notification for doctor
                $notifStmt = $conn->prepare("
                    INSERT INTO notifications
                    (appointment_id, type, sent_at, status, recipient_id, recipient_role, related_table, related_id)
                    VALUES (?, ?, NOW(), 'unread', ?, 'doctor', 'appointments', ?)
                ");
                $type = 'appointment_created';
                $notifStmt->bind_param("isii", $appointmentId, $type, $doctorId, $appointmentId);
                $notifStmt->execute();
                $notifStmt->close();
            } else {
                $errorMessage = "Error booking appointment: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Book Appointment - Healthcare System</title>
<style>
    body { font-family: Arial, sans-serif; background: #f0f4f8; padding: 40px; }
    .container { max-width: 600px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { text-align: center; margin-bottom: 25px; }
    label { display: block; margin-top: 15px; font-weight: bold; }
    select, input, textarea { width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; font-size: 16px; }
    button { margin-top: 25px; width: 100%; padding: 12px; background-color: #007bff; border: none; border-radius: 6px; color: white; font-size: 18px; cursor: pointer; transition: background-color 0.3s ease; }
    button:hover { background-color: #0056b3; }
    .message { padding: 15px; margin-top: 20px; border-radius: 6px; font-weight: bold; text-align: center; }
    .success { background-color: #d4edda; color: #155724; }
    .error { background-color: #f8d7da; color: #721c24; }
    .payment-link { text-align: center; margin-top: 20px; font-weight: bold; }
    .payment-link a { color: #007bff; text-decoration: none; font-size: 18px; }
    .payment-link a:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="container">
  <h1>Book Appointment</h1>
  <p>Welcome, <?php echo htmlspecialchars($patientName); ?>. Please fill the form below to book an appointment.</p>

  <?php if ($successMessage): ?>
    <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
  <?php endif; ?>

  <?php if ($errorMessage): ?>
    <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <label for="doctor">Choose Doctor <span style="color:red">*</span></label>
    <select id="doctor" name="doctor" required>
      <option value="">-- Select Doctor --</option>
      <?php foreach ($doctors as $doc): ?>
        <option value="<?= $doc['id']; ?>" <?= (isset($doctorId) && $doctorId == $doc['id']) ? 'selected' : ''; ?>>
            <?= htmlspecialchars($doc['name'] . " ({$doc['specialization']})"); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="date">Date <span style="color:red">*</span></label>
    <input type="date" id="date" name="date" required value="<?= htmlspecialchars($date ?? ''); ?>" />

    <label for="time">Time <span style="color:red">*</span></label>
    <input type="time" id="time" name="time" required value="<?= htmlspecialchars($time ?? ''); ?>" />

    <label for="notes">Notes (optional)</label>
    <textarea id="notes" name="notes" placeholder="Reason for visit or special requests"><?= htmlspecialchars($notes ?? ''); ?></textarea>

    <button type="submit">Book Appointment</button>
  </form>

  <?php if ($paymentLink): ?>
    <div class="payment-link">
      <p><a href="<?= htmlspecialchars($paymentLink); ?>">Click here to Pay Now for your appointment</a></p>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
