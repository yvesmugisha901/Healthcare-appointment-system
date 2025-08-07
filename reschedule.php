<?php
session_start();

// Mock patient and appointments data (replace with DB queries)
$patientName = "Jane Doe";

$appointments = [
    ['id' => 101, 'doctor' => 'Dr. John Doe', 'date' => '2025-08-20', 'time' => '10:00'],
    ['id' => 102, 'doctor' => 'Dr. Alice Smith', 'date' => '2025-08-22', 'time' => '14:30'],
];

$successMessage = '';
$errorMessage = '';
$selectedAppointment = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = $_POST['appointment_id'] ?? '';
    $newDate = $_POST['new_date'] ?? '';
    $newTime = $_POST['new_time'] ?? '';

    if (!$appointmentId) {
        $errorMessage = "Please select an appointment to reschedule.";
    } elseif (!$newDate || !$newTime) {
        $errorMessage = "Please provide the new date and time.";
    } else {
        // Here, update the appointment date/time in DB (not implemented)
        $successMessage = "Appointment #$appointmentId has been rescheduled to $newDate at $newTime.";

        // Optionally update the mock array for display (simulate update)
        foreach ($appointments as &$appt) {
            if ($appt['id'] == $appointmentId) {
                $appt['date'] = $newDate;
                $appt['time'] = $newTime;
                $selectedAppointment = $appt;
                break;
            }
        }
        unset($appt); // break reference
    }
}

// Pre-fill form if appointment selected via GET (optional)
if (isset($_GET['appointment_id'])) {
    $aid = $_GET['appointment_id'];
    foreach ($appointments as $appt) {
        if ($appt['id'] == $aid) {
            $selectedAppointment = $appt;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reschedule Appointment - Healthcare System</title>
  <link rel="stylesheet" href="css/appointment.css" />
</head>
<body>
  <div class="container">
    <h1>Reschedule Appointment</h1>
    <p>Hello, <?php echo htmlspecialchars($patientName); ?>. Select an appointment and pick a new date/time.</p>

    <?php if ($successMessage): ?>
      <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
      <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <form method="POST" action="reschedule.php">
      <label for="appointment_id">Select Appointment</label>
      <select id="appointment_id" name="appointment_id" required onchange="this.form.submit()">
        <option value="">-- Select Appointment --</option>
        <?php foreach ($appointments as $appt): ?>
          <option value="<?php echo $appt['id']; ?>" <?php echo ($selectedAppointment && $selectedAppointment['id'] == $appt['id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars("{$appt['doctor']} - {$appt['date']} at {$appt['time']}"); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>

    <?php if ($selectedAppointment): ?>
      <form method="POST" action="reschedule.php">
        <input type="hidden" name="appointment_id" value="<?php echo $selectedAppointment['id']; ?>" />

        <label for="new_date">New Date</label>
        <input type="date" id="new_date" name="new_date" required value="<?php echo htmlspecialchars($selectedAppointment['date']); ?>" />

        <label for="new_time">New Time</label>
        <input type="time" id="new_time" name="new_time" required value="<?php echo htmlspecialchars($selectedAppointment['time']); ?>" />

        <button type="submit">Reschedule Appointment</button>
      </form>
    <?php else: ?>
      <p>Please select an appointment to reschedule.</p>
    <?php endif; ?>
  </div>
</body>
</html>
