<?php
session_start();
require 'connect.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

$doctor_id = $_SESSION['user_id'];

// Handle adding new availability
if (isset($_POST['add_availability'])) {
    $day = $_POST['day_of_week'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $stmt = $conn->prepare("INSERT INTO availability (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $doctor_id, $day, $start, $end);
    $stmt->execute();
    $stmt->close();
    header("Location: doctors_availability.php");
}

// Handle deleting availability
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM availability WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param("ii", $id, $doctor_id);
    $stmt->execute();
    $stmt->close();
    header("Location: doctors_availability.php");
}

// Fetch doctor's availability
$availabilities = $conn->query("SELECT * FROM availability WHERE doctor_id = $doctor_id ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Doctor Availability</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #0066cc;
    --primary-dark: #004080;
    --secondary: #0070f3;
    --bg: #f9fafb;
    --white: #fff;
    --shadow: 0 3px 8px rgba(0,0,0,0.12);
    --transition: all 0.3s ease;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Poppins', sans-serif; background:var(--bg); color:#222; padding:2rem; }
h1 { text-align:center; margin-bottom:2rem; color:var(--primary-dark); }
table { width:100%; border-collapse:collapse; margin-bottom:2rem; }
table, th, td { border:1px solid #ccc; }
th, td { padding:0.8rem; text-align:center; }
th { background:var(--primary); color:white; }
tr:hover { background:#f1f1f1; }
form { max-width:600px; margin:0 auto; background:white; padding:1.5rem; border-radius:10px; box-shadow:var(--shadow); }
form label { display:block; margin-bottom:0.5rem; font-weight:500; }
form select, form input { width:100%; padding:0.5rem; margin-bottom:1rem; border-radius:6px; border:1px solid #ccc; }
.btn { padding:0.6rem 1.2rem; border:none; border-radius:6px; cursor:pointer; font-weight:600; transition:var(--transition); }
.btn-primary { background:var(--primary); color:white; }
.btn-primary:hover { background:var(--primary-dark); }
.btn-danger { background:#e74c3c; color:white; }
.btn-danger:hover { background:#c0392b; }
</style>
</head>
<body>

<h1>My Availability</h1>

<!-- Availability Table -->
<table>
<tr>
    <th>Day</th>
    <th>Start Time</th>
    <th>End Time</th>
    <th>Action</th>
</tr>
<?php if ($availabilities->num_rows > 0): ?>
    <?php while($row = $availabilities->fetch_assoc()): ?>
        <tr>
            <td><?= $row['day_of_week'] ?></td>
            <td><?= date("h:i A", strtotime($row['start_time'])) ?></td>
            <td><?= date("h:i A", strtotime($row['end_time'])) ?></td>
            <td><a href="?delete=<?= $row['id'] ?>" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</a></td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="4">No availability set yet.</td></tr>
<?php endif; ?>
</table>

<!-- Add Availability Form -->
<form method="POST">
    <h2 style="text-align:center; margin-bottom:1rem;">Add New Availability</h2>
    <label for="day_of_week">Day of the Week</label>
    <select name="day_of_week" id="day_of_week" required>
        <option value="">Select Day</option>
        <option>Monday</option>
        <option>Tuesday</option>
        <option>Wednesday</option>
        <option>Thursday</option>
        <option>Friday</option>
        <option>Saturday</option>
        <option>Sunday</option>
    </select>

    <label for="start_time">Start Time</label>
    <input type="time" name="start_time" id="start_time" required>

    <label for="end_time">End Time</label>
    <input type="time" name="end_time" id="end_time" required>

    <button type="submit" name="add_availability" class="btn btn-primary">Add Availability</button>
</form>

</body>
</html>
