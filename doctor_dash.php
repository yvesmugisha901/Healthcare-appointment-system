<?php
session_start();
require 'connect.php';

// Redirect if not logged in or not doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctorId = $_SESSION['user_id'];

// Get doctor's name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$stmt->bind_result($doctorName);
$stmt->fetch();
$stmt->close();

// Total appointments this week
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM appointments 
    WHERE doctor_id = ? 
      AND appointment_datetime BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
      AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
      AND status != 'Cancelled'
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$stmt->bind_result($totalThisWeek);
$stmt->fetch();
$stmt->close();

// Upcoming appointments
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM appointments 
    WHERE doctor_id = ? 
      AND appointment_datetime >= NOW() 
      AND status = 'Scheduled'
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$stmt->bind_result($upcomingAppointments);
$stmt->fetch();
$stmt->close();

// Today's appointments
$stmt = $conn->prepare("
    SELECT a.id, TIME(a.appointment_datetime) AS time, u.name AS patient, a.notes
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ?
      AND DATE(a.appointment_datetime) = CURDATE()
      AND a.status = 'Scheduled'
    ORDER BY a.appointment_datetime ASC
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Doctor availability
$stmt = $conn->prepare("
    SELECT id, day_of_week, start_time, end_time
    FROM availability
    WHERE doctor_id = ?
    ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();
$availability = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Doctor Dashboard - Healthcare System</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body {
  font-family: 'Segoe UI', sans-serif;
  margin: 0;
  display: flex;
  background: #f0f2f5;
  min-height: 100vh;
}

.main-content {
  flex: 1;
  padding: 30px;
  margin-left: 200px;
  box-sizing: border-box;
}

h1, h2 {
  color: #333;
}

.dashboard-cards {
  display: flex;
  gap: 20px;
  margin-bottom: 40px;
}

.card {
  flex: 1;
  background: #fff;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  text-align: center;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.card h3 {
  color: #007BFF;
  margin-bottom: 15px;
  font-weight: 600;
}

.card p {
  font-size: 28px;
  font-weight: bold;
  margin: 0;
}

table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0,0,0,0.08);
  margin-bottom: 40px;
}

thead tr {
  background-color: #007BFF;
  color: #fff;
  text-align: left;
}

th, td {
  padding: 12px 15px;
  border-bottom: 1px solid #eee;
}

tbody tr:hover {
  background-color: #f1f1f1;
}

button {
  background: #28a745;
  color: #fff;
  border: none;
  padding: 8px 14px;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: 0.3s;
}

button:hover {
  background-color: #218838;
}

input[type="time"] {
  border:1px solid #ccc;
  border-radius:5px;
  padding:5px;
  width:120px;
}

/* Availability Modal */
.modal {
  display: none;
  position: fixed;
  z-index: 10;
  left: 0; top: 0;
  width: 100%; height: 100%;
  overflow: auto;
  background: rgba(0,0,0,0.5);
}

.modal-content {
  background: #0a0a0aff;
  margin: 10% auto;
  padding: 30px;
  border-radius: 10px;
  width: 400px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

.modal-content h3 {
  margin-top: 0;
  margin-bottom: 20px;
}

.close {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
}

.close:hover {
  color: #000;
}

input, select {
  width: 100%;
  padding: 10px;
  margin-bottom: 15px;
  border-radius: 5px;
  border: 1px solid #ccc;
  box-sizing: border-box;
}

.add-btn {
  background: #17a2b8;
  margin-bottom: 15px;
}

.add-btn:hover {
  background: #138496;
}
</style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
  <h1>Welcome, <?php echo htmlspecialchars($doctorName); ?></h1>

  <div class="dashboard-cards">
    <div class="card">
      <h3>Total Appointments This Week</h3>
      <p><?php echo $totalThisWeek; ?></p>
    </div>
    <div class="card">
      <h3>Upcoming Appointments</h3>
      <p><?php echo $upcomingAppointments; ?></p>
    </div>
  </div>

  <section>
    <h2>Today's Appointments</h2>
    <table>
      <thead>
        <tr>
          <th>Time</th>
          <th>Patient</th>
          <th>Notes</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if(count($appointments) > 0): ?>
          <?php foreach($appointments as $app): ?>
          <tr>
            <td><?php echo date("h:i A", strtotime($app['time'])); ?></td>
            <td><?php echo htmlspecialchars($app['patient']); ?></td>
            <td><?php echo htmlspecialchars($app['notes']); ?></td>
            <td>
              <form method="POST" action="mark_completed.php" style="margin:0;">
                <input type="hidden" name="appointment_id" value="<?php echo $app['id']; ?>" />
                <button type="submit">Mark Completed</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4">No appointments scheduled for today.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <section>
    <h2>Your Availability</h2>
    <button class="add-btn" onclick="openModal()">+ Add Availability</button>
    <table>
      <thead>
        <tr>
          <th>Day</th>
          <th>Start Time</th>
          <th>End Time</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if(count($availability) > 0): ?>
          <?php foreach($availability as $slot): ?>
          <tr>
            <td><?php echo htmlspecialchars($slot['day_of_week']); ?></td>
            <td>
              <input type="time" value="<?php echo $slot['start_time']; ?>" 
                     onchange="updateTime(<?php echo $slot['id']; ?>, 'start_time', this.value)">
            </td>
            <td>
              <input type="time" value="<?php echo $slot['end_time']; ?>" 
                     onchange="updateTime(<?php echo $slot['id']; ?>, 'end_time', this.value)">
            </td>
            <td>
              <button onclick="deleteSlot(<?php echo $slot['id']; ?>)">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4">No availability set. Please add your schedule.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>
</div>

<!-- Add Availability Modal -->
<div id="availabilityModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h3>Add Availability</h3>
    <form method="POST" action="add_availability.php">
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
      <input type="time" name="start_time" required>
      <input type="time" name="end_time" required>
      <input type="hidden" name="doctor_id" value="<?php echo $doctorId; ?>">
      <button type="submit">Save</button>
    </form>
  </div>
</div>

<script>
function openModal(){ document.getElementById('availabilityModal').style.display='block'; }
function closeModal(){ document.getElementById('availabilityModal').style.display='none'; }
window.onclick = function(event){ 
  if(event.target == document.getElementById('availabilityModal')) closeModal();
}

function updateTime(id, column, value){
    fetch('update_availability.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `id=${id}&column=${column}&value=${value}`
    }).then(res=>res.text()).then(data=>{
        console.log(data);
    });
}

function deleteSlot(id){
    if(confirm("Are you sure you want to delete this availability?")){
        fetch('delete_availability.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `availability_id=${id}`
        }).then(res=>res.text()).then(data=>{
            location.reload();
        });
    }
}
</script>

</body>
</html>
