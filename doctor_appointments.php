<?php
session_start();
require 'connect.php';

// Redirect if not logged in or not doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctorId = $_SESSION['user_id'];

// Handle AJAX actions for cancel, reschedule, or payment update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $appointmentId = intval($_POST['appointment_id']);

    if ($_POST['action'] === 'cancel') {
        $stmt = $conn->prepare("UPDATE appointments SET status='canceled' WHERE id=? AND doctor_id=?");
        $stmt->bind_param("ii", $appointmentId, $doctorId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'status' => 'canceled']);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($_POST['action'] === 'reschedule') {
        $newDateTime = $_POST['new_datetime'];
        $stmt = $conn->prepare("UPDATE appointments SET appointment_datetime=?, status='booked' WHERE id=? AND doctor_id=?");
        $stmt->bind_param("sii", $newDateTime, $appointmentId, $doctorId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'new_datetime' => $newDateTime]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($_POST['action'] === 'update_payment') {
        $paymentStatus = $_POST['payment_status'];
        $stmt = $conn->prepare("UPDATE appointments SET payment_status=? WHERE id=? AND doctor_id=?");
        $stmt->bind_param("sii", $paymentStatus, $appointmentId, $doctorId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }
}

// Fetch all appointments for this doctor (ordered by date)
$stmt = $conn->prepare("
    SELECT a.id, a.appointment_datetime, u.name AS patient_name, a.status, a.notes, a.payment_status
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_datetime DESC
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Doctor Appointments - Healthcare System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --primary: #2a9d8f;
    --primary-dark: #1d7870;
    --primary-light: #7fcdc3;
    --danger: #dc3545;
    --warning: #ffc107;
    --success: #28a745;
    --neutral-light: #f4f6f9;
    --neutral-dark: #333;
    --radius: 10px;
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
}

body { 
    font-family: 'Segoe UI', Tahoma, sans-serif; 
    margin: 0; 
    display: flex; 
    background-color: var(--neutral-light); 
    min-height: 100vh; 
}

.main-content { 
    flex: 1; 
    padding: 30px; 
    margin-left: 220px; 
    box-sizing: border-box; 
}

h1 { 
    color: var(--neutral-dark); 
    margin-bottom: 20px; 
    font-size: 24px; 
    display: flex; 
    align-items: center; 
    gap: 10px; 
}
h1 i { color: var(--primary); }

table { 
    width: 100%; 
    border-collapse: collapse; 
    background: white; 
    border-radius: var(--radius); 
    overflow: hidden; 
    box-shadow: var(--shadow-md); 
}
thead tr { 
    background-color: var(--primary); 
    color: white; 
    font-size: 15px; 
}
th, td { 
    padding: 14px 16px; 
    border-bottom: 1px solid #eee; 
    text-align: left; 
    font-size: 14px; 
}
tbody tr:hover { 
    background-color: #f9f9f9; 
    transition: background 0.2s; 
}
.status-booked { color: var(--primary); font-weight: 600; }
.status-completed { color: var(--success); font-weight: 600; }
.status-canceled { color: var(--danger); font-weight: 600; }

select.payment-select {
    padding: 5px;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

.button { 
    padding: 5px 10px; 
    margin: 2px; 
    border: none; 
    border-radius: 5px; 
    cursor: pointer; 
    color: #fff; 
}
.reschedule-btn { background: var(--warning); }
.cancel-btn { background: var(--danger); }

.empty { 
    background: #fff; 
    padding: 20px; 
    border-radius: 8px; 
    text-align: center; 
    color: #666; 
    box-shadow: var(--shadow-sm); 
}

.modal { 
    position: fixed; 
    top:0; left:0; 
    width:100%; height:100%; 
    background:rgba(0,0,0,0.5); 
    justify-content:center; 
    align-items:center; 
    display:none; 
}
.modal-content { 
    background:#fff; 
    padding:20px; 
    border-radius:10px; 
    width:350px; 
    position:relative; 
}
.modal .close { 
    position:absolute; 
    top:10px; 
    right:15px; 
    font-size:20px; 
    cursor:pointer; 
}
input[type=datetime-local] { 
    width:100%; 
    padding:8px; 
    margin:8px 0; 
    border:1px solid #ccc; 
    border-radius:5px; 
}
button.save-btn { 
    background: var(--success); 
    width:100%; 
    padding: 10px;
    color: #fff;
    font-weight: 600;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
</style>
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="main-content">
<h1><i class="fa-solid fa-calendar-check"></i> Your Appointments</h1>

<?php if (count($appointments) > 0): ?>
<table>
<thead>
<tr>
<th>Date & Time</th>
<th>Patient</th>
<th>Notes</th>
<th>Status</th>
<th>Payment Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($appointments as $app): ?>
<tr id="row-<?php echo $app['id']; ?>">
<td id="datetime-<?php echo $app['id']; ?>"><?php echo date("Y-m-d H:i", strtotime($app['appointment_datetime'])); ?></td>
<td><?php echo htmlspecialchars($app['patient_name']); ?></td>
<td><?php echo htmlspecialchars($app['notes']); ?></td>
<td class="status-<?php echo strtolower($app['status']); ?>" id="status-<?php echo $app['id']; ?>"><?php echo htmlspecialchars($app['status']); ?></td>
<td>
<select class="payment-select" onchange="updatePaymentStatus(<?php echo $app['id']; ?>, this.value)">
    <option value="pending" <?php if($app['payment_status']=='pending') echo 'selected'; ?>>Pending</option>
    <option value="paid" <?php if($app['payment_status']=='paid') echo 'selected'; ?>>Paid</option>
    <option value="unpaid" <?php if($app['payment_status']=='unpaid') echo 'selected'; ?>>Unpaid</option>
</select>
</td>
<td id="actions-<?php echo $app['id']; ?>">
<?php if ($app['status'] === 'booked'): ?>
<button class="button reschedule-btn" onclick="openRescheduleModal(<?php echo $app['id']; ?>)">Reschedule</button>
<button class="button cancel-btn" onclick="cancelAppointment(<?php echo $app['id']; ?>)">Cancel</button>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div class="empty">
<i class="fa-regular fa-calendar-xmark fa-2x" style="color:#999;"></i>
<p>No appointments found.</p>
</div>
<?php endif; ?>
</div>

<!-- Reschedule Modal -->
<div class="modal" id="rescheduleModal">
<div class="modal-content">
<span class="close" onclick="closeRescheduleModal()">&times;</span>
<h3>Reschedule Appointment</h3>
<input type="datetime-local" id="newDatetime">
<button class="save-btn" onclick="saveReschedule()">Save</button>
</div>
</div>

<script>
let currentAppointmentId = null;

function cancelAppointment(id){
    if(!confirm('Cancel this appointment?')) return;
    fetch('', {
        method:'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=cancel&appointment_id='+id
    }).then(r=>r.json()).then(data=>{
        if(data.success){
            document.getElementById('status-'+id).innerText = 'canceled';
            document.getElementById('actions-'+id).innerHTML = '';
        } else alert(data.error);
    });
}

function openRescheduleModal(id){
    currentAppointmentId = id;
    let datetime = document.getElementById('datetime-'+id).innerText;
    let formatted = new Date(datetime).toISOString().slice(0,16);
    document.getElementById('newDatetime').value = formatted;
    document.getElementById('rescheduleModal').style.display = 'flex';
}

function closeRescheduleModal(){
    currentAppointmentId = null;
    document.getElementById('rescheduleModal').style.display = 'none';
}

function saveReschedule(){
    let newDateTime = document.getElementById('newDatetime').value;
    if(!newDateTime) return alert('Select a new date and time');
    fetch('', {
        method:'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=reschedule&appointment_id='+currentAppointmentId+'&new_datetime='+encodeURIComponent(newDateTime)
    }).then(r=>r.json()).then(data=>{
        if(data.success){
            document.getElementById('datetime-'+currentAppointmentId).innerText = newDateTime.replace('T',' ');
            closeRescheduleModal();
        } else alert(data.error);
    });
}

function updatePaymentStatus(appointmentId, status){
    fetch('', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=update_payment&appointment_id='+appointmentId+'&payment_status='+status
    }).then(r=>r.json()).then(data=>{
        if(!data.success){
            alert('Error updating payment: '+data.error);
        }
    });
}
</script>

</body>
</html>
