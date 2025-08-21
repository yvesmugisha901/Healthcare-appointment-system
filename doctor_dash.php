<?php
session_start();
require 'connect.php';

// Redirect if not doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role']!=='doctor'){
    header("Location: login.php");
    exit;
}

$doctorId = $_SESSION['user_id'];

// Fetch doctor info
$stmt = $conn->prepare("SELECT name,email,specialization FROM users WHERE id=?");
$stmt->bind_param("i",$doctorId);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();
$stmt->close();

$doctorName = $doctor['name'];
$doctorEmail = $doctor['email'];
$doctorSpec = $doctor['specialization'];

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

// Handle AJAX for availability (unchanged)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['availability_action'])){
    header('Content-Type: application/json');
    $action = $_POST['availability_action'] ?? '';
    
    if($action==='add'){
        $day = $_POST['day_of_week'];
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        $stmt = $conn->prepare("INSERT INTO availability (doctor_id, day_of_week, start_time, end_time) VALUES (?,?,?,?)");
        $stmt->bind_param("isss",$doctorId,$day,$start,$end);
        if($stmt->execute()){
            $id = $stmt->insert_id;
            echo json_encode(['success'=>true,'slot'=>['id'=>$id,'day_of_week'=>$day,'start_time'=>$start,'end_time'=>$end]]);
        } else echo json_encode(['success'=>false,'error'=>$stmt->error]);
        $stmt->close();
        exit;
    }
    
    if($action==='update'){
        $id = $_POST['id'];
        $day = $_POST['day_of_week'];
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        $stmt = $conn->prepare("UPDATE availability SET day_of_week=?, start_time=?, end_time=? WHERE id=? AND doctor_id=?");
        $stmt->bind_param("sssii",$day,$start,$end,$id,$doctorId);
        if($stmt->execute()){
            echo json_encode(['success'=>true,'slot'=>['id'=>$id,'day_of_week'=>$day,'start_time'=>$start,'end_time'=>$end]]);
        } else echo json_encode(['success'=>false,'error'=>$stmt->error]);
        $stmt->close();
        exit;
    }
    
    if($action==='delete'){
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM availability WHERE id=? AND doctor_id=?");
        $stmt->bind_param("ii",$id,$doctorId);
        if($stmt->execute()) echo json_encode(['success'=>true]);
        else echo json_encode(['success'=>false,'error'=>$stmt->error]);
        $stmt->close();
        exit;
    }
}

// Fetch appointments this week
$stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appointment_datetime BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY) AND status!='Cancelled'");
$stmt->bind_param("i",$doctorId);
$stmt->execute();
$stmt->bind_result($totalThisWeek);
$stmt->fetch();
$stmt->close();

// Upcoming appointments
$stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appointment_datetime>=NOW() AND status='Scheduled'");
$stmt->bind_param("i",$doctorId);
$stmt->execute();
$stmt->bind_result($upcomingAppointments);
$stmt->fetch();
$stmt->close();

// Today's appointments
$stmt = $conn->prepare("SELECT a.id, TIME(a.appointment_datetime) AS time, DAYNAME(a.appointment_datetime) AS day, u.name AS patient, a.notes FROM appointments a JOIN users u ON a.patient_id=u.id WHERE a.doctor_id=? AND DATE(a.appointment_datetime)=CURDATE() AND a.status='Scheduled' ORDER BY a.appointment_datetime ASC");
$stmt->bind_param("i",$doctorId);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Availability
$stmt = $conn->prepare("SELECT id, day_of_week, start_time, end_time FROM availability WHERE doctor_id=? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
$stmt->bind_param("i",$doctorId);
$stmt->execute();
$result = $stmt->get_result();
$availability = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Doctor Dashboard</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root{--primary:#1e3a8a;--primary-light:#3b82f6;--success:#28a745;--info:#17a2b8;--danger:#dc3545;--text:#333;--bg:#f0f2f5;}
body{margin:0;display:flex;font-family:Segoe UI;background:var(--bg);}
.main-content{flex:1;padding:30px;margin-left:200px;}
.sidebar{width:200px;background:var(--primary);color:#fff;position:fixed;height:100vh;padding:20px;}
.sidebar a{display:block;color:#fff;text-decoration:none;margin-bottom:10px;padding:8px;border-radius:5px;}
.sidebar a:hover{background:var(--primary-light);}
.dashboard-cards{display:flex;gap:20px;margin-bottom:40px;}
.card{flex:1;background:#fff;padding:25px;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);text-align:center;transition:0.3s;}
.card:hover{transform:translateY(-5px);box-shadow:0 8px 20px rgba(0,0,0,0.15);}
.card h3{color:var(--primary);margin-bottom:15px;font-weight:600;}
.card p{font-size:28px;font-weight:bold;margin:0;}
.table-card{max-width:850px;margin:20px auto;background:#fff;border-radius:12px;box-shadow:0 8px 25px rgba(0,0,0,0.1);overflow:hidden;padding:20px;}
.table-card table{width:100%;border-collapse:collapse;}
.table-card th, .table-card td{padding:10px 12px;border-bottom:1px solid #eee;text-align:center;}
.table-card thead tr{background:linear-gradient(90deg,var(--primary),var(--primary-light));color:#fff;}
.tabs{display:flex;gap:10px;margin-bottom:20px;}
.tabs button{flex:1;padding:10px;background:#fff;border:1px solid #ccc;border-bottom:none;cursor:pointer;font-weight:bold;border-radius:6px 6px 0 0;}
.tabs button.active{background:var(--primary);color:#fff;}
.tab-content{background:#fff;padding:20px;border-radius:0 12px 12px 12px;box-shadow:0 8px 25px rgba(0,0,0,0.1);}
input,select,button{padding:8px;margin:5px 0;border-radius:5px;border:1px solid #ccc;box-sizing:border-box;}
button.update-btn{background:#ffc107;color:#fff;border:none;padding:5px 10px;border-radius:5px;}
button.delete-btn{background:var(--danger);color:#fff;border:none;padding:5px 10px;border-radius:5px;}
.add-btn{background:var(--info);color:#fff;border:none;padding:8px 14px;border-radius:6px;cursor:pointer;margin-bottom:15px;}
.add-btn:hover{background:#138496;}
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

<div class="tabs">
<button class="tab-link active" onclick="openTab('appointments')">Appointments</button>
<button class="tab-link" onclick="openTab('availability')">Availability</button>
<button class="tab-link" onclick="openTab('profile')">Profile</button>
<button class="tab-link" onclick="openTab('password')">Change Password</button>
</div>

<div id="appointments" class="tab-content">
<div class="table-card">
<h2>Today's Appointments</h2>
<table>
<thead><tr><th>Day</th><th>Time</th><th>Patient</th><th>Notes</th><th>Actions</th></tr></thead>
<tbody>
<?php if(count($appointments)>0): foreach($appointments as $app): ?>
<tr>
<td><?php echo $app['day'];?></td>
<td><?php echo date("h:i A",strtotime($app['time']));?></td>
<td><?php echo htmlspecialchars($app['patient']);?></td>
<td><?php echo htmlspecialchars($app['notes']);?></td>
<td>
<form method="POST" action="mark_completed.php">
<input type="hidden" name="appointment_id" value="<?php echo $app['id'];?>">
<button type="submit" style="background:var(--success);color:#fff;">Mark Completed</button>
</form>
</td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="5">No appointments today.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

<div id="availability" class="tab-content" style="display:none;">
<div class="table-card">
<h2>Your Availability</h2>
<button class="add-btn" onclick="openModal()">+ Add Availability</button>
<table id="availabilityTable">
<thead><tr><th>Day</th><th>Start</th><th>End</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($availability as $slot): ?>
<tr data-id="<?php echo $slot['id']; ?>">
<td><?php echo $slot['day_of_week']; ?></td>
<td><?php echo $slot['start_time']; ?></td>
<td><?php echo $slot['end_time']; ?></td>
<td>
<button class="update-btn" onclick="editSlot(<?php echo $slot['id']; ?>)">Update</button>
<button class="delete-btn" onclick="deleteSlot(<?php echo $slot['id']; ?>)">Delete</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<div id="profile" class="tab-content" style="display:none;">
<h2>Profile</h2>
<form>
<label>Name:</label>
<input type="text" value="<?php echo htmlspecialchars($doctorName); ?>" disabled>
<label>Specialization:</label>
<input type="text" value="<?php echo htmlspecialchars($doctorSpec); ?>" disabled>
<label>Email:</label>
<input type="email" value="<?php echo htmlspecialchars($doctorEmail); ?>" disabled>
<p style="color:#555;font-style:italic;">To change your email, request update and admin approval is required.</p>
</form>
</div>

<div id="password" class="tab-content" style="display:none;">
<h2>Change Password</h2>
<form method="POST" action="change_password.php">
<label>Current Password:</label>
<input type="password" name="current_password" required>
<label>New Password:</label>
<input type="password" name="new_password" required>
<label>Confirm Password:</label>
<input type="password" name="confirm_password" required>
<button type="submit" style="background:var(--success);color:#fff;">Update Password</button>
</form>
</div>

</div>

<!-- Availability Modal -->
<div id="availabilityModal" class="modal">
<div class="modal-content">
<span class="close" onclick="closeModal()">&times;</span>
<h3 id="modalTitle">Add Availability</h3>
<form id="availabilityForm">
<select name="day_of_week" required>
<option value="">Select Day</option>
<?php foreach($days as $d) echo "<option value='$d'>$d</option>"; ?>
</select>
<input type="time" name="start_time" required>
<input type="time" name="end_time" required>
<input type="hidden" name="id" value="">
<input type="hidden" name="availability_action" value="add">
<button type="submit" style="background:var(--success);color:#fff;">Save</button>
</form>
</div>
</div>

<script>
function openTab(tabName){
    document.querySelectorAll('.tab-content').forEach(t=>t.style.display='none');
    document.getElementById(tabName).style.display='block';
    document.querySelectorAll('.tab-link').forEach(b=>b.classList.remove('active'));
    event.currentTarget.classList.add('active');
}

// Availability JS
let currentAction='add';
let editRow=null;
function openModal(){document.getElementById('availabilityModal').style.display='block';}
function closeModal(){document.getElementById('availabilityModal').style.display='none';document.getElementById('availabilityForm').reset();currentAction='add';document.getElementById('modalTitle').innerText='Add Availability';}
function editSlot(id){
    editRow = document.querySelector(`tr[data-id='${id}']`);
    document.querySelector('[name="day_of_week"]').value = editRow.children[0].innerText;
    document.querySelector('[name="start_time"]').value = editRow.children[1].innerText;
    document.querySelector('[name="end_time"]').value = editRow.children[2].innerText;
    document.querySelector('[name="id"]').value = id;
    document.querySelector('[name="availability_action"]').value = 'update';
    currentAction='update';
    document.getElementById('modalTitle').innerText='Update Availability';
    openModal();
}
function deleteSlot(id){
    if(confirm("Are you sure?")){
        fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`availability_action=delete&id=${id}`})
        .then(res=>res.json()).then(data=>{
            if(data.success) document.querySelector(`tr[data-id='${id}']`).remove();
            else alert(data.error);
        });
    }
}
document.getElementById('availabilityForm').addEventListener('submit', function(e){
    e.preventDefault();
    let formData = new FormData(this);
    fetch('',{method:'POST',body:formData}).then(res=>res.json()).then(data=>{
        if(data.success){
            if(currentAction==='add'){
                let slot=data.slot;
                let tbody=document.querySelector('#availabilityTable tbody');
                let tr=document.createElement('tr');
                tr.setAttribute('data-id',slot.id);
                tr.innerHTML=`<td>${slot.day_of_week}</td><td>${slot.start_time}</td><td>${slot.end_time}</td>
                <td><button class="update-btn" onclick="editSlot(${slot.id})">Update</button>
                <button class="delete-btn" onclick="deleteSlot(${slot.id})">Delete</button></td>`;
                tbody.appendChild(tr);
            }
            if(currentAction==='update'){
                let slot=data.slot;
                editRow.children[0].innerText=slot.day_of_week;
                editRow.children[1].innerText=slot.start_time;
                editRow.children[2].innerText=slot.end_time;
            }
            closeModal();
        } else alert(data.error);
    });
});
window.onclick = function(event){ if(event.target==document.getElementById('availabilityModal')) closeModal();}
</script>

</body>
</html>
