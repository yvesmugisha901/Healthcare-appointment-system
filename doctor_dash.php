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

// Handle AJAX for availability, days off, and vacation
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action_type'])){
    header('Content-Type: application/json');
    $action = $_POST['action_type'] ?? '';
    
    // --- Availability CRUD ---
    if(isset($_POST['table_type']) && $_POST['table_type']==='availability'){
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

    // --- Days Off / Vacation CRUD ---
    if(isset($_POST['table_type']) && $_POST['table_type']==='days_off'){
        if($action==='add'){
            $type = $_POST['type'];
            $start = $_POST['start_date'];
            $end = $_POST['end_date'];
            $stmt = $conn->prepare("INSERT INTO days_off (doctor_id,type,start_date,end_date) VALUES (?,?,?,?)");
            $stmt->bind_param("isss",$doctorId,$type,$start,$end);
            if($stmt->execute()){
                $id = $stmt->insert_id;
                echo json_encode(['success'=>true,'slot'=>['id'=>$id,'type'=>$type,'start_date'=>$start,'end_date'=>$end]]);
            } else echo json_encode(['success'=>false,'error'=>$stmt->error]);
            $stmt->close();
            exit;
        }
        if($action==='update'){
            $id = $_POST['id'];
            $type = $_POST['type'];
            $start = $_POST['start_date'];
            $end = $_POST['end_date'];
            $stmt = $conn->prepare("UPDATE days_off SET type=?, start_date=?, end_date=? WHERE id=? AND doctor_id=?");
            $stmt->bind_param("sssii",$type,$start,$end,$id,$doctorId);
            if($stmt->execute()){
                echo json_encode(['success'=>true,'slot'=>['id'=>$id,'type'=>$type,'start_date'=>$start,'end_date'=>$end]]);
            } else echo json_encode(['success'=>false,'error'=>$stmt->error]);
            $stmt->close();
            exit;
        }
        if($action==='delete'){
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM days_off WHERE id=? AND doctor_id=?");
            $stmt->bind_param("ii",$id,$doctorId);
            if($stmt->execute()) echo json_encode(['success'=>true]);
            else echo json_encode(['success'=>false,'error'=>$stmt->error]);
            $stmt->close();
            exit;
        }
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

// Days Off / Vacation
$stmt = $conn->prepare("SELECT id, type, start_date, end_date FROM days_off WHERE doctor_id=? ORDER BY start_date ASC");
$stmt->bind_param("i",$doctorId);
$stmt->execute();
$result = $stmt->get_result();
$daysOff = $result->fetch_all(MYSQLI_ASSOC);
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
:root {
    --primary: #1e3a8a;
    --primary-light: #3b82f6;
    --success: #28a745;
    --info: #17a2b8;
    --danger: #dc3545;
    --text: #333;
    --bg: #f0f2f5;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    display: flex;
    font-family: "Segoe UI", sans-serif;
    background: var(--bg);
}

/* Sidebar */
.sidebar {
    width: 200px;
    background: var(--primary);
    color: #fff;
    position: fixed;
    height: 100vh;
    padding: 20px;
    transition: all 0.3s ease;
}
.sidebar a {
    display: block;
    color: #fff;
    text-decoration: none;
    margin-bottom: 10px;
    padding: 8px;
    border-radius: 5px;
    transition: 0.3s;
}
.sidebar a:hover {
    background: var(--primary-light);
}

/* Main Content */
.main-content {
    flex: 1;
    padding: 30px;
    margin-left: 200px;
}

/* Dashboard Cards */
.dashboard-cards {
    display: flex;
    gap: 20px;
    margin-bottom: 40px;
    flex-wrap: wrap;
}
.card {
    flex: 1;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: 0.3s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}
.card h3 {
    color: var(--primary);
    margin-bottom: 15px;
    font-weight: 600;
}
.card p {
    font-size: 28px;
    font-weight: bold;
    margin: 0;
}

/* Tables */
.table-card {
    max-width: 100%;
    margin: 20px auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    overflow-x: auto; /* horizontal scroll for mobile */
    padding: 20px;
}
.table-card table {
    width: 100%;
    border-collapse: collapse;
}
.table-card th, .table-card td {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    text-align: center;
}
.table-card thead tr {
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    color: #fff;
}

/* Tabs */
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.tabs button {
    flex: 1;
    padding: 10px;
    background: #fff;
    border: 1px solid #ccc;
    border-bottom: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 6px 6px 0 0;
    transition: 0.3s;
}
.tabs button.active {
    background: var(--primary);
    color: #fff;
}

/* Tab Content */
.tab-content {
    background: #fff;
    padding: 20px;
    border-radius: 0 12px 12px 12px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

/* Forms & Buttons */
input, select, button {
    padding: 8px;
    margin: 5px 0;
    border-radius: 5px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}
button.update-btn {
    background: #ffc107;
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
}
button.delete-btn {
    background: var(--danger);
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
}
.add-btn {
    background: var(--info);
    color: #fff;
    border: none;
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
    margin-bottom: 15px;
}
.add-btn:hover {
    background: #138496;
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
}
.modal-content {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    width: 400px;
    position: relative;
}
.modal .close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 20px;
    cursor: pointer;
}

/* Responsive Media Queries */

/* Small laptops */
@media (max-width: 1200px) {
    .main-content {
        margin-left: 180px;
        padding: 20px;
    }
    .dashboard-cards {
        flex-direction: column;
        gap: 15px;
    }
}

/* Tablets and small desktops */
@media (max-width: 768px) {
    body {
        flex-direction: column;
    }
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
    }
    .sidebar a {
        flex: 1 1 45%;
        margin: 5px;
        text-align: center;
    }
    .main-content {
        margin-left: 0;
        padding: 15px;
    }
    .tabs {
        flex-direction: column;
    }
    .tabs button {
        margin-bottom: 5px;
    }
}

/* Mobile phones */
@media (max-width: 480px) {
    input, select, button {
        width: 100%;
    }
    .modal-content {
        width: 90%;
    }
    .card p {
        font-size: 22px;
    }
    .table-card th, .table-card td {
        padding: 6px 8px;
    }
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

<div class="tabs">
<button class="tab-link active" onclick="openTab('appointments')">Appointments</button>
<button class="tab-link" onclick="openTab('availability')">Availability</button>
<button class="tab-link" onclick="openTab('profile')">Profile</button>
<button class="tab-link" onclick="openTab('password')">Change Password</button>
</div>

<!-- Appointments -->
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

<!-- Availability Tab -->
<div id="availability" class="tab-content" style="display:none;">
<div class="table-card">
<h2>Your Availability</h2>
<button class="add-btn" onclick="openAvailabilityModal()">+ Add Availability</button>
<table id="availabilityTable">
<thead><tr><th>Day</th><th>Start</th><th>End</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($availability as $slot): ?>
<tr data-id="<?php echo $slot['id']; ?>">
<td><?php echo $slot['day_of_week']; ?></td>
<td><?php echo $slot['start_time']; ?></td>
<td><?php echo $slot['end_time']; ?></td>
<td>
<button class="update-btn" onclick="editAvailability(<?php echo $slot['id']; ?>)">Update</button>
<button class="delete-btn" onclick="deleteAvailability(<?php echo $slot['id']; ?>)">Delete</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Days Off / Vacation Section -->
<div class="table-card">
<h2>Days Off / Vacation</h2>
<button class="add-btn" onclick="openDaysOffModal()">+ Add Entry</button>
<table id="daysOffTable">
<thead><tr><th>Type</th><th>Start Date</th><th>End Date</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($daysOff as $d): ?>
<tr data-id="<?php echo $d['id']; ?>">
<td><?php echo $d['type']; ?></td>
<td><?php echo $d['start_date']; ?></td>
<td><?php echo $d['end_date']; ?></td>
<td>
<button class="update-btn" onclick="editDaysOff(<?php echo $d['id']; ?>)">Update</button>
<button class="delete-btn" onclick="deleteDaysOff(<?php echo $d['id']; ?>)">Delete</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<!-- Profile Tab -->
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

<!-- Password Tab -->
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
<div id="availabilityModal" class="modal" style="display:none;">
<div class="modal-content">
<span class="close" onclick="closeAvailabilityModal()">&times;</span>
<h3 id="availabilityModalTitle">Add Availability</h3>
<form id="availabilityForm">
<select name="day_of_week" required>
<option value="">Select Day</option>
<?php foreach($days as $d) echo "<option value='$d'>$d</option>"; ?>
</select>
<input type="time" name="start_time" required>
<input type="time" name="end_time" required>
<input type="hidden" name="id" value="">
<input type="hidden" name="action_type" value="add">
<input type="hidden" name="table_type" value="availability">
<button type="submit" style="background:var(--success);color:#fff;">Save</button>
</form>
</div>
</div>

<!-- Days Off / Vacation Modal -->
<div id="daysOffModal" class="modal" style="display:none;">
<div class="modal-content">
<span class="close" onclick="closeDaysOffModal()">&times;</span>
<h3 id="daysOffModalTitle">Add Entry</h3>
<form id="daysOffForm">
<select name="type" required>
<option value="">Select Type</option>
<option value="Day Off">Day Off</option>
<option value="Vacation">Vacation</option>
</select>
<input type="date" name="start_date" required>
<input type="date" name="end_date" required>
<input type="hidden" name="id" value="">
<input type="hidden" name="action_type" value="add">
<input type="hidden" name="table_type" value="days_off">
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

// --- Availability JS ---
let currentAvailabilityRow=null;
function openAvailabilityModal(){
    currentAvailabilityRow=null;
    document.getElementById('availabilityForm').reset();
    document.getElementById('availabilityForm').action_type.value='add';
    document.getElementById('availabilityModalTitle').innerText='Add Availability';
    document.getElementById('availabilityModal').style.display='flex';
}
function closeAvailabilityModal(){document.getElementById('availabilityModal').style.display='none';}
function editAvailability(id){
    let row=document.querySelector(`#availabilityTable tr[data-id='${id}']`);
    currentAvailabilityRow=row;
    document.getElementById('availabilityForm').day_of_week.value=row.cells[0].innerText;
    document.getElementById('availabilityForm').start_time.value=row.cells[1].innerText;
    document.getElementById('availabilityForm').end_time.value=row.cells[2].innerText;
    document.getElementById('availabilityForm').id.value=id;
    document.getElementById('availabilityForm').action_type.value='update';
    document.getElementById('availabilityModalTitle').innerText='Update Availability';
    document.getElementById('availabilityModal').style.display='flex';
}
function deleteAvailability(id){
    if(confirm('Delete this slot?')){
        fetch('',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`action_type=delete&table_type=availability&id=${id}`
        }).then(r=>r.json()).then(data=>{
            if(data.success) document.querySelector(`#availabilityTable tr[data-id='${id}']`).remove();
        });
    }
}

// --- Days Off JS ---
let currentDaysOffRow=null;
function openDaysOffModal(){
    currentDaysOffRow=null;
    document.getElementById('daysOffForm').reset();
    document.getElementById('daysOffForm').action_type.value='add';
    document.getElementById('daysOffModalTitle').innerText='Add Entry';
    document.getElementById('daysOffModal').style.display='flex';
}
function closeDaysOffModal(){document.getElementById('daysOffModal').style.display='none';}
function editDaysOff(id){
    let row=document.querySelector(`#daysOffTable tr[data-id='${id}']`);
    currentDaysOffRow=row;
    document.getElementById('daysOffForm').type.value=row.cells[0].innerText;
    document.getElementById('daysOffForm').start_date.value=row.cells[1].innerText;
    document.getElementById('daysOffForm').end_date.value=row.cells[2].innerText;
    document.getElementById('daysOffForm').id.value=id;
    document.getElementById('daysOffForm').action_type.value='update';
    document.getElementById('daysOffModalTitle').innerText='Update Entry';
    document.getElementById('daysOffModal').style.display='flex';
}
function deleteDaysOff(id){
    if(confirm('Delete this entry?')){
        fetch('',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`action_type=delete&table_type=days_off&id=${id}`
        }).then(r=>r.json()).then(data=>{
            if(data.success) document.querySelector(`#daysOffTable tr[data-id='${id}']`).remove();
        });
    }
}

// --- AJAX Form Submission ---
document.getElementById('availabilityForm').addEventListener('submit',function(e){
    e.preventDefault();
    let fd = new FormData(this);
    fetch('',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(data=>{
        if(data.success){
            closeAvailabilityModal();
            let t = document.getElementById('availabilityTable').querySelector('tbody');
            let slot = data.slot;
            let rowHtml = `<tr data-id="${slot.id}">
            <td>${slot.day_of_week}</td>
            <td>${slot.start_time}</td>
            <td>${slot.end_time}</td>
            <td>
            <button class="update-btn" onclick="editAvailability(${slot.id})">Update</button>
            <button class="delete-btn" onclick="deleteAvailability(${slot.id})">Delete</button>
            </td></tr>`;
            if(currentAvailabilityRow) currentAvailabilityRow.outerHTML=rowHtml;
            else t.insertAdjacentHTML('beforeend',rowHtml);
        } else alert(data.error);
    });
});

document.getElementById('daysOffForm').addEventListener('submit',function(e){
    e.preventDefault();
    let fd = new FormData(this);
    fetch('',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(data=>{
        if(data.success){
            closeDaysOffModal();
            let t = document.getElementById('daysOffTable').querySelector('tbody');
            let slot = data.slot;
            let rowHtml = `<tr data-id="${slot.id}">
            <td>${slot.type}</td>
            <td>${slot.start_date}</td>
            <td>${slot.end_date}</td>
            <td>
            <button class="update-btn" onclick="editDaysOff(${slot.id})">Update</button>
            <button class="delete-btn" onclick="deleteDaysOff(${slot.id})">Delete</button>
            </td></tr>`;
            if(currentDaysOffRow) currentDaysOffRow.outerHTML=rowHtml;
            else t.insertAdjacentHTML('beforeend',rowHtml);
        } else alert(data.error);
    });
});
</script>
</body>
</html>
