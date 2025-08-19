<?php
session_start();
require 'connect.php';

// Check logged-in patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$patientId = $_SESSION['user_id'];
$patientName = $_SESSION['name'] ?? 'Patient';

$successMessage = '';
$errorMessage = '';

// Fetch patient's upcoming appointments from DB
$appointments = [];
$stmt = $conn->prepare("
    SELECT a.id, a.appointment_datetime, u.name AS doctor_name, u.id AS doctor_id
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    WHERE a.patient_id = ? AND a.appointment_datetime >= NOW()
    ORDER BY a.appointment_datetime ASC
");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $appointments[] = [
        'id' => $row['id'],
        'doctor' => $row['doctor_name'],
        'doctor_id' => $row['doctor_id'],
        'datetime' => $row['appointment_datetime']
    ];
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = $_POST['appointment_id'] ?? '';

    if (!$appointmentId) {
        $errorMessage = "Please select an appointment to cancel.";
    } else {
        // Get doctor ID and appointment info before deleting
        $stmt = $conn->prepare("SELECT doctor_id FROM appointments WHERE id = ? AND patient_id = ?");
        $stmt->bind_param("ii", $appointmentId, $patientId);
        $stmt->execute();
        $stmt->bind_result($doctorId);
        if ($stmt->fetch()) {
            $stmt->close();

            // Delete the appointment
            $stmtDel = $conn->prepare("DELETE FROM appointments WHERE id = ? AND patient_id = ?");
            $stmtDel->bind_param("ii", $appointmentId, $patientId);
            if ($stmtDel->execute()) {
                $successMessage = "Appointment #$appointmentId has been canceled successfully.";

                // Insert notification for the doctor
                $notifStmt = $conn->prepare("
                    INSERT INTO notifications
                    (appointment_id, type, sent_at, status, recipient_id, recipient_role, related_table, related_id)
                    VALUES (?, ?, NOW(), 'unread', ?, ?, 'appointments', ?)
                ");
                $type = 'appointment_cancelled';
                $recipientRole = 'doctor';
                $notifStmt->bind_param("isisi", $appointmentId, $type, $doctorId, $recipientRole, $appointmentId);
                $notifStmt->execute();
                $notifStmt->close();
            } else {
                $errorMessage = "Error canceling appointment: " . $stmtDel->error;
            }
            $stmtDel->close();
        } else {
            $errorMessage = "Appointment not found or you don't have permission.";
            $stmt->close();
        }
    }

    // Refresh upcoming appointments after cancellation
    $appointments = [];
    $stmt = $conn->prepare("
        SELECT a.id, a.appointment_datetime, u.name AS doctor_name, u.id AS doctor_id
        FROM appointments a
        JOIN users u ON a.doctor_id = u.id
        WHERE a.patient_id = ? AND a.appointment_datetime >= NOW()
        ORDER BY a.appointment_datetime ASC
    ");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = [
            'id' => $row['id'],
            'doctor' => $row['doctor_name'],
            'doctor_id' => $row['doctor_id'],
            'datetime' => $row['appointment_datetime']
        ];
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Cancel Appointment - Healthcare System</title>
<style>
body { font-family: Arial, sans-serif; background: #f0f4f8; padding: 40px; }
.container { max-width: 600px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { text-align: center; margin-bottom: 25px; }
label { display: block; margin-top: 15px; font-weight: bold; }
select { width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; font-size: 16px; }
button { margin-top: 25px; width: 100%; padding: 12px; background-color: #dc3545; border: none; border-radius: 6px; color: white; font-size: 18px; cursor: pointer; transition: background-color 0.3s ease; }
button:hover { background-color: #a71d2a; }
.message { padding: 15px; margin-top: 20px; border-radius: 6px; font-weight: bold; text-align: center; }
.success { background-color: #d4edda; color: #155724; }
.error { background-color: #f8d7da; color: #721c24; }
</style>
</head>
<body>

<div class="container">
  <h1>Cancel Appointment</h1>
  <p>Hello, <?= htmlspecialchars($patientName); ?>. Select an appointment to cancel below.</p>

  <?php if ($successMessage): ?>
    <div class="message success"><?= htmlspecialchars($successMessage); ?></div>
  <?php endif; ?>
  <?php if ($errorMessage): ?>
    <div class="message error"><?= htmlspecialchars($errorMessage); ?></div>
  <?php endif; ?>

  <?php if (count($appointments) > 0): ?>
    <form method="POST" action="">
      <label for="appointment_id">Your Appointments</label>
      <select id="appointment_id" name="appointment_id" required>
        <option value="">-- Select Appointment --</option>
        <?php foreach ($appointments as $appt): 
            $dt = date('Y-m-d H:i', strtotime($appt['datetime']));
        ?>
          <option value="<?= $appt['id']; ?>">
            <?= htmlspecialchars("{$appt['doctor']} - {$dt}"); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Cancel Appointment</button>
    </form>
  <?php else: ?>
    <p>You have no upcoming appointments to cancel.</p>
  <?php endif; ?>
</div>

</body>
</html>


