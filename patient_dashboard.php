<?php 
session_start();
require 'connect.php';

// Only allow logged-in patients
if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['role'])) !== 'patient') {
    header("Location: login.php");
    exit;
}
// Handle CSV download
if(isset($_GET['download']) && $_GET['download']=='past_appointments'){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="past_appointments.csv"');
    $output = fopen('php://output','w');
    fputcsv($output, ['Date','Time','Doctor','Status']); // CSV headers
    foreach($pastAppointments as $app){
        fputcsv($output, [$app['date'], date("h:i A", strtotime($app['time'])), $app['doctor'], ucfirst($app['status'])]);
    }
    fclose($output);
    exit;
}

$patientId = $_SESSION['user_id'];
$patientName = $_SESSION['name'];

// Handle AJAX actions (cancel/update)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])){
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if($action==='cancel'){
        $id = $_POST['appointment_id'];
        $stmt = $conn->prepare("UPDATE appointments SET status='Cancelled' WHERE id=? AND patient_id=? AND status='Booked'");
        $stmt->bind_param("ii",$id,$patientId);
        if($stmt->execute()) echo json_encode(['success'=>true]);
        else echo json_encode(['success'=>false,'message'=>$stmt->error]);
        $stmt->close();
        exit;
    }

    if($action==='update'){
        $id = $_POST['appointment_id'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        $dateTime = $date.' '.$time;
        $stmt = $conn->prepare("UPDATE appointments SET appointment_datetime=? WHERE id=? AND patient_id=? AND status='Booked'");
        $stmt->bind_param("sii",$dateTime,$id,$patientId);
        if($stmt->execute()) echo json_encode(['success'=>true,'date'=>$date,'time'=>$time]);
        else echo json_encode(['success'=>false,'message'=>$stmt->error]);
        $stmt->close();
        exit;
    }
}

// Fetch patient appointments summary
$stmt = $conn->prepare("
    SELECT TRIM(LOWER(status)) AS status_lower, COUNT(*) AS count
    FROM appointments
    WHERE patient_id = ?
    GROUP BY status_lower
");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();

$reportData = ['Scheduled'=>0,'Completed'=>0,'Cancelled'=>0];
$statusMap = ['booked'=>'Scheduled','completed'=>'Completed','done'=>'Completed','cancelled'=>'Cancelled','canceled'=>'Cancelled'];

while ($row = $result->fetch_assoc()) {
    $dbStatus = $row['status_lower'];
    $displayStatus = $statusMap[$dbStatus] ?? ucfirst($dbStatus);
    if(!isset($reportData[$displayStatus])) $reportData[$displayStatus] = 0;
    $reportData[$displayStatus] += $row['count'];
}
$stmt->close();

// Fetch upcoming appointments
$stmt = $conn->prepare("
    SELECT a.id, DATE(a.appointment_datetime) AS date, TIME(a.appointment_datetime) AS time, u.name AS doctor
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    WHERE a.patient_id = ? AND TRIM(LOWER(a.status)) = 'booked'
    ORDER BY a.appointment_datetime ASC
");
$stmt->bind_param("i",$patientId);
$stmt->execute();
$result = $stmt->get_result();
$upcomingAppointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch past appointments
$stmt = $conn->prepare("
    SELECT a.id, DATE(a.appointment_datetime) AS date, TIME(a.appointment_datetime) AS time, u.name AS doctor, a.status
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    WHERE a.patient_id = ? AND TRIM(LOWER(a.status)) IN ('completed','done','cancelled','canceled')
    ORDER BY a.appointment_datetime DESC
");
$stmt->bind_param("i",$patientId);
$stmt->execute();
$result = $stmt->get_result();
$pastAppointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Dashboard - Healthcare System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary:#1e3a8a;
  --primary-light:#3b82f6;
  --success:#28a745;
  --danger:#dc3545;
  --bg:#f0f2f5;
  --text:#333;
}

body{
  font-family:Segoe UI;
  margin:0;
  display:flex;
  background:var(--bg);
  min-height:100vh;
  flex-direction:row;
}

.main-content{
  flex:1;
  padding:30px;
  margin-left:200px;
  box-sizing:border-box;
}

h1{
  color:var(--primary);
  margin-bottom:25px;
}

.dashboard-cards{
  display:flex;
  gap:20px;
  margin-bottom:40px;
  flex-wrap:wrap; /* makes cards wrap on small screens */
}

.card{
  flex:1;
  min-width:150px; /* ensures card doesn't shrink too much */
  background:#fff;
  padding:25px;
  border-radius:12px;
  box-shadow:0 4px 15px rgba(0,0,0,0.1);
  text-align:center;
  transition:.3s;
}
.card:hover{
  transform:translateY(-5px);
  box-shadow:0 8px 20px rgba(0,0,0,0.15);
}
.card h3{color:var(--primary);margin-bottom:15px;font-weight:600;}
.card p{font-size:28px;font-weight:bold;margin:0;}

.table-card{
  max-width:100%;
  overflow-x:auto; /* horizontal scroll on small screens */
  margin-bottom:40px;
  background:#fff;
  border-radius:12px;
  box-shadow:0 8px 25px rgba(0,0,0,0.1);
  overflow:hidden;
  padding:20px;
  transition:.3s;
}
.table-card:hover{
  transform:translateY(-5px);
  box-shadow:0 12px 30px rgba(0,0,0,0.15);
}

.table-card h2{
  margin-bottom:15px;
  color:var(--primary);
  font-size:22px;
  text-align:center;
}

.table-card table{
  width:100%;
  border-collapse:collapse;
  min-width:600px; /* ensures table doesn't collapse too much */
}
.table-card th, .table-card td{
  padding:12px;
  border-bottom:1px solid #eee;
  font-size:14px;
  text-align:left;
}

.table-card thead tr{
  background:linear-gradient(90deg,var(--primary),var(--primary-light));
  color:#fff;
  text-align:left;
}

.table-card tbody tr:hover{
  background-color:#f1f5fb;
}

.table-card input{
  padding:5px;
  border-radius:5px;
  border:1px solid #ccc;
  width:100px;
  max-width:90%; /* mobile-friendly */
  box-sizing:border-box;
}

button{
  padding:6px 12px;
  font-size:13px;
  border-radius:5px;
  border:none;
  cursor:pointer;
  font-weight:600;
  transition:.3s;
  margin:2px 0; /* spacing for mobile */
}

.cancel-btn{background:var(--danger);color:#fff;}
.update-btn{background:var(--primary-light);color:#fff;}

/* Responsive adjustments */
@media (max-width:1024px){
  .main-content{margin-left:200px; padding:20px;}
  .dashboard-cards{gap:15px;}
  .card{padding:20px;}
}

@media (max-width:768px){
  body{flex-direction:column;}
  .main-content{margin-left:0; padding:15px;}
  .dashboard-cards{flex-direction:column; gap:15px;}
  .table-card table{min-width:100%; display:block;}
  .table-card th, .table-card td{font-size:13px; padding:10px;}
  .table-card input{width:100%; max-width:150px;}
  button{width:100%; margin:5px 0;}
}

@media (max-width:500px){
  h1{font-size:20px; text-align:center;}
  .table-card h2{font-size:18px;}
  .dashboard-cards .card p{font-size:24px;}
}
</style>

</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
<h1>Welcome, <?= htmlspecialchars($patientName) ?> 👋</h1>

<div class="dashboard-cards">
    <div class="card"><h3>Scheduled</h3><p id="scheduled-count"><?= $reportData['Scheduled'] ?></p></div>
    <div class="card"><h3>Completed</h3><p id="completed-count"><?= $reportData['Completed'] ?></p></div>
    <div class="card"><h3>Cancelled</h3><p id="cancelled-count"><?= $reportData['Cancelled'] ?></p></div>
</div>

<div class="table-card">
<h2>Upcoming Appointments</h2>
<table id="upcoming-table">
<thead><tr><th>Date</th><th>Time</th><th>Doctor</th><th>Actions</th></tr></thead>
<tbody>
<?php if(count($upcomingAppointments)>0): foreach($upcomingAppointments as $app): ?>
<tr id="row-<?= $app['id'] ?>">
<td><input type="date" value="<?= $app['date'] ?>" id="date-<?= $app['id'] ?>"></td>
<td><input type="time" value="<?= $app['time'] ?>" id="time-<?= $app['id'] ?>"></td>
<td><?= htmlspecialchars($app['doctor']) ?></td>
<td>
<button class="update-btn" onclick="updateAppointment(<?= $app['id'] ?>)">Update</button>
<button class="cancel-btn" onclick="cancelAppointment(<?= $app['id'] ?>)">Cancel</button>
</td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="4" style="text-align:center;">No upcoming appointments.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>


<div class="table-card">

<h2>
  Past Appointments 
  <button onclick="toggleHistory()" style="float:right;margin-right:10px;padding:5px 10px;background:#28a745;color:#fff;border:none;border-radius:5px;cursor:pointer;">Show/Hide</button>
  <a href="?download=past_appointments" style="float:right;padding:5px 10px;background:#007bff;color:#fff;border:none;border-radius:5px;text-decoration:none;margin-right:10px;">Download CSV</a>
</h2>
<div id="history-section" style="display:none;">
<table>
<thead><tr><th>Date</th><th>Time</th><th>Doctor</th><th>Status</th></tr></thead>
<tbody>
<?php if(count($pastAppointments)>0): foreach($pastAppointments as $app): ?>
<tr>
<td><?= htmlspecialchars($app['date']) ?></td>
<td><?= date("h:i A", strtotime($app['time'])) ?></td>
<td><?= htmlspecialchars($app['doctor']) ?></td>
<td><?= ucfirst($app['status']) ?></td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="4" style="text-align:center;">No past appointments.</td></tr>
<?php endif; ?>
</tbody>
</table>

</div>
</div>
</div>

<script>
function cancelAppointment(id){
    if(!confirm('Are you sure you want to cancel this appointment?')) return;
    fetch('',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`action=cancel&appointment_id=${id}`
    }).then(res=>res.json()).then(data=>{
        if(data.success){
            const row = document.getElementById('row-'+id);
            if(row) row.remove();
            const scheduled = document.getElementById('scheduled-count');
            const cancelled = document.getElementById('cancelled-count');
            scheduled.textContent = parseInt(scheduled.textContent)-1;
            cancelled.textContent = parseInt(cancelled.textContent)+1;
        } else alert(data.message||'Failed to cancel');
    });
}

function updateAppointment(id){
    const date = document.getElementById('date-'+id).value;
    const time = document.getElementById('time-'+id).value;
    fetch('',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`action=update&appointment_id=${id}&date=${date}&time=${time}`
    }).then(res=>res.json()).then(data=>{
        if(data.success){
            alert('Appointment updated successfully!');
        } else alert(data.message||'Failed to update');
    });
}

function toggleHistory(){
    const section = document.getElementById('history-section');
    section.style.display = section.style.display === 'none' ? 'block' : 'none';
}
</script>

</body>
</html>
