<?php
session_start();
require 'connect.php'; // Make sure this connects $conn

// Assuming patient is logged in and patient ID stored in session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$patientId = $_SESSION['user_id'];
$patientName = "Jane Doe"; // Optional, you can fetch real name from DB if you want

// Fetch doctors from DB instead of hardcoding (optional but recommended)
$doctors = [];
$docResult = $conn->query("SELECT id, name, specialization FROM users WHERE role='doctor'");
if ($docResult) {
    while ($row = $docResult->fetch_assoc()) {
        $doctors[] = $row;
    }
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = $_POST['doctor'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (!$doctorId || !$date || !$time) {
        $errorMessage = "Please fill in all required fields.";
    } else {
        $appointment_datetime = $date . ' ' . $time . ':00'; // Format for datetime

        // Prepare and bind to avoid SQL injection
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_datetime, notes) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            $errorMessage = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("iiss", $patientId, $doctorId, $appointment_datetime, $notes);
            if ($stmt->execute()) {
                $successMessage = "Appointment booked successfully for $date at $time.";
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
  <link rel="stylesheet" href="css/appointment.css" />
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
          <option value="<?php echo $doc['id']; ?>" <?php if (isset($doctorId) && $doctorId == $doc['id']) echo 'selected'; ?>>
            <?php echo htmlspecialchars($doc['name'] . " ({$doc['specialization']})"); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="date">Date <span style="color:red">*</span></label>
      <input type="date" id="date" name="date" required value="<?php echo htmlspecialchars($date ?? ''); ?>" />

      <label for="time">Time <span style="color:red">*</span></label>
      <input type="time" id="time" name="time" required value="<?php echo htmlspecialchars($time ?? ''); ?>" />

      <label for="notes">Notes (optional)</label>
      <textarea id="notes" name="notes" placeholder="Reason for visit or special requests"><?php echo htmlspecialchars($notes ?? ''); ?></textarea>

      <button type="submit">Book Appointment</button>
    </form>
  </div>
</body>
</html>
