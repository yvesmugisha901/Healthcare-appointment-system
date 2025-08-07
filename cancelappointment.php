<?php
session_start();

// Mock patient data and appointments for demo (replace with DB queries)
$patientName = "Jane Doe";

$appointments = [
    ['id' => 101, 'doctor' => 'Dr. John Doe', 'date' => '2025-08-20', 'time' => '10:00'],
    ['id' => 102, 'doctor' => 'Dr. Alice Smith', 'date' => '2025-08-22', 'time' => '14:30'],
];

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = $_POST['appointment_id'] ?? '';

    if (!$appointmentId) {
        $errorMessage = "Please select an appointment to cancel.";
    } else {
        // Here, you would delete or mark appointment as canceled in your DB (not implemented)
        $successMessage = "Appointment #$appointmentId has been canceled successfully.";
        
        // Optionally, remove it from the array for display (simulated)
        foreach ($appointments as $key => $appt) {
            if ($appt['id'] == $appointmentId) {
                unset($appointments[$key]);
                break;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cancel Appointment - Healthcare System</title>
  <link rel="stylesheet" href="css/appointment.css" />
</head>
<body>
  <div class="container">
    <h1>Cancel Appointment</h1>
    <p>Hello, <?php echo htmlspecialchars($patientName); ?>. Select an appointment to cancel below.</p>

    <?php if ($successMessage): ?>
      <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
      <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <?php if (count($appointments) > 0): ?>
    <form method="POST" action="cancelappointment.php">
      <label for="appointment_id">Your Appointments</label>
      <select id="appointment_id" name="appointment_id" required>
        <option value="">-- Select Appointment --</option>
        <?php foreach ($appointments as $appt): ?>
          <option value="<?php echo $appt['id']; ?>">
            <?php 
              echo htmlspecialchars("{$appt['doctor']} - {$appt['date']} at {$appt['time']}");
            ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit">Cancel Appointment</button>
    </form>
    <?php else: ?>
      <p>You have no appointments to cancel.</p>
    <?php endif; ?>
  </div>
</body>
</html>
