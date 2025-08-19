<?php
session_start();
require 'connect.php';

// Allow both admin and doctor to access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'doctor'])) {
    header("Location: login.php");
    exit;
}

// Get list of doctors from users table
$doctorQuery = $conn->prepare("SELECT id, name FROM users WHERE role = 'doctor'");
$doctorQuery->execute();
$doctorResult = $doctorQuery->get_result();
$doctors = $doctorResult->fetch_all(MYSQLI_ASSOC);
$doctorQuery->close();

// Handle form submission
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_POST['doctor_id'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if ($doctor_id && $day_of_week && $start_time && $end_time) {
        $stmt = $conn->prepare("INSERT INTO availability (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $doctor_id, $day_of_week, $start_time, $end_time);
        if ($stmt->execute()) {
            $success = "Availability added successfully!";
        } else {
            $error = "Error adding availability: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "All fields are required.";
    }
}

// Fetch all availability
$availQuery = $conn->prepare("
    SELECT a.id, u.name AS doctor_name, a.day_of_week, a.start_time, a.end_time
    FROM availability a
    JOIN users u ON a.doctor_id = u.id
    ORDER BY a.doctor_id, FIELD(a.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
");
$availQuery->execute();
$availResult = $availQuery->get_result();
$availabilities = $availResult->fetch_all(MYSQLI_ASSOC);
$availQuery->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Add Doctor Availability</title>
<style>
    body { font-family: 'Poppins', sans-serif; margin:0; padding:0; background:#f4f6f9; }
    .container { max-width: 1000px; margin: 30px auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
    h1 { color:#007BFF; margin-bottom: 20px; }
    form { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; }
    select, input[type="time"] { padding: 8px; border-radius: 5px; border: 1px solid #ccc; flex:1; }
    button { padding: 10px 20px; border:none; background:#007BFF; color:#fff; border-radius:5px; cursor:pointer; transition: 0.3s; }
    button:hover { background:#0056b3; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px 15px; text-align: left; border-bottom:1px solid #eee; }
    th { background:#007BFF; color:#fff; }
    tr:hover { background:#f1f1f1; }
    .message { margin-bottom: 20px; padding:10px; border-radius:5px; }
    .success { background:#d4edda; color:#155724; }
    .error { background:#f8d7da; color:#721c24; }
</style>
</head>
<body>
<div class="container">
    <h1>Add Doctor Availability</h1>

    <?php if($success): ?>
        <div class="message success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="message error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <select name="doctor_id" required>
            <option value="">Select Doctor</option>
            <?php foreach($doctors as $doc): ?>
                <option value="<?php echo $doc['id']; ?>"><?php echo htmlspecialchars($doc['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="day_of_week" required>
            <option value="">Select Day</option>
            <option>Monday</option>
            <option>Tuesday</option>
            <option>Wednesday</option>
            <option>Thursday</option>
            <option>Friday</option>
            <option>Saturday</option>
            <option>Sunday</option>
        </select>
        <input type="time" name="start_time" required />
        <input type="time" name="end_time" required />
        <button type="submit">Add Availability</button>
    </form>

    <h2>Current Availabilities</h2>
    <table>
        <thead>
            <tr>
                <th>Doctor</th>
                <th>Day</th>
                <th>Start</th>
                <th>End</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($availabilities) > 0): ?>
                <?php foreach($availabilities as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['doctor_name']); ?></td>
                        <td><?php echo $a['day_of_week']; ?></td>
                        <td><?php echo date("h:i A", strtotime($a['start_time'])); ?></td>
                        <td><?php echo date("h:i A", strtotime($a['end_time'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">No availability added yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
