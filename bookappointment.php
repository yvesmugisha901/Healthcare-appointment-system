<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require 'connect.php';

// Check logged-in patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$patientId = $_SESSION['user_id'];
$patientName = $_SESSION['name'] ?? 'Patient';

// Fetch doctors list with specialization
$doctors = [];
$docResult = $conn->query("SELECT id, name, specialization FROM users WHERE role='doctor'");
if ($docResult) {
    while ($row = $docResult->fetch_assoc()) $doctors[] = $row;
}

// Fetch doctor availability
$availability = [];
$availResult = $conn->query("SELECT doctor_id, day_of_week, start_time, end_time FROM availability");
if ($availResult) {
    while ($row = $availResult->fetch_assoc()) {
        $did = $row['doctor_id'];
        $day = $row['day_of_week'];
        if (!isset($availability[$did])) $availability[$did] = [];
        if (!isset($availability[$did][$day])) $availability[$did][$day] = [];
        $availability[$did][$day][] = ['start'=>$row['start_time'],'end'=>$row['end_time']];
    }
}

// Fetch booked appointments
$bookedAppointments = [];
$apptResult = $conn->query("SELECT doctor_id, DATE(appointment_datetime) AS date, TIME(appointment_datetime) AS time FROM appointments WHERE TRIM(LOWER(status))='booked'");
if ($apptResult) {
    while ($row = $apptResult->fetch_assoc()) {
        $did = $row['doctor_id'];
        $date = $row['date'];
        $time = $row['time'];
        if (!isset($bookedAppointments[$did])) $bookedAppointments[$did] = [];
        if (!isset($bookedAppointments[$did][$date])) $bookedAppointments[$did][$date] = [];
        $bookedAppointments[$did][$date][] = $time;
    }
}

$successMessage = '';
$errorMessage = '';
$paymentLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = $_POST['doctor'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (!$doctorId || !$date || !$time) $errorMessage = "Please fill in all required fields.";
    else {
        $appointment_datetime = $date . ' ' . $time . ':00';

        // Check if patient already has appointment at that time
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id=? AND appointment_datetime=? AND TRIM(LOWER(status))='booked'");
        $stmtCheck->bind_param("is", $patientId, $appointment_datetime);
        $stmtCheck->execute();
        $stmtCheck->bind_result($exists);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($exists>0) $errorMessage = "You already have an appointment at this time.";
        else {
            $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_datetime, notes) VALUES (?, ?, ?, ?)");
            if (!$stmt) $errorMessage = "Prepare failed: " . $conn->error;
            else {
                $stmt->bind_param("iiss", $patientId, $doctorId, $appointment_datetime, $notes);
                if ($stmt->execute()) {
                    $appointmentId = $stmt->insert_id;
                    $successMessage = "Appointment booked successfully for $date at $time.";
                    $paymentLink = "patient_payment.php?appointment_id=$appointmentId";

                    // Notification for doctor
                    $notifStmt = $conn->prepare("INSERT INTO notifications (appointment_id,type,sent_at,status,recipient_id,recipient_role,related_table,related_id) VALUES (?, ?, NOW(), 'unread', ?, 'doctor', 'appointments', ?)");
                    $type='appointment_created';
                    $notifStmt->bind_param("isii",$appointmentId,$type,$doctorId,$appointmentId);
                    $notifStmt->execute();
                    $notifStmt->close();
                } else $errorMessage = "Error booking appointment: " . $stmt->error;
                $stmt->close();
            }
        }
    }
}

$conn->close();
$daysOfWeek = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$appointmentDuration = 60; // duration in minutes
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Book Appointment - Healthcare System</title>
<style>
body { font-family: Arial, sans-serif; background: #f0f4f8; padding: 30px; }
.container { max-width: 600px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);}
h1 { text-align:center; margin-bottom:25px; }
label { display:block; margin-top:15px; font-weight:bold; }
select, input, textarea { width:100%; padding:10px; margin-top:5px; border-radius:5px; border:1px solid #ccc; font-size:16px;}
button { margin-top:25px; width:100%; padding:12px; background:#007bff; border:none; border-radius:6px; color:#fff; font-size:18px; cursor:pointer; transition:0.3s; }
button:hover { background:#0056b3; }
.message { padding:15px; margin-top:20px; border-radius:6px; font-weight:bold; text-align:center; }
.success { background:#d4edda; color:#155724; }
.error { background:#f8d7da; color:#721c24; }
.payment-link { text-align:center; margin-top:20px; font-weight:bold; }
.payment-link a { color:#007bff; text-decoration:none; font-size:18px; }
.payment-link a:hover { text-decoration:underline; }
</style>
</head>
<body>

<div class="container">
<h1>Book Appointment</h1>
<p>Welcome, <?=htmlspecialchars($patientName)?>. Fill the form below.</p>

<?php if($successMessage):?><div class="message success"><?=htmlspecialchars($successMessage)?></div><?php endif;?>
<?php if($errorMessage):?><div class="message error"><?=htmlspecialchars($errorMessage)?></div><?php endif;?>

<form method="POST" action="">
<label for="specialization">Specialization</label>
<select id="specialization" required>
  <option value="">-- Select Specialization --</option>
  <?php
  $specs = array_unique(array_map(fn($d)=>$d['specialization'],$doctors));
  foreach($specs as $s) echo "<option value=\"$s\">$s</option>";
  ?>
</select>

<label for="doctor">Choose Doctor <span style="color:red">*</span></label>
<select id="doctor" name="doctor" required>
  <option value="">-- Select Doctor --</option>
  <?php foreach($doctors as $doc): ?>
    <option value="<?= $doc['id']; ?>" data-specialization="<?= htmlspecialchars($doc['specialization']); ?>">
      <?= htmlspecialchars($doc['name']." ({$doc['specialization']})"); ?>
    </option>
  <?php endforeach; ?>
</select>

<label for="date">Date <span style="color:red">*</span></label>
<input type="date" id="date" name="date" required value="<?=htmlspecialchars($date??'')?>">

<label for="time">Time <span style="color:red">*</span></label>
<select id="time" name="time" required>
  <option value="">-- Select Time --</option>
</select>

<label for="notes">Notes (optional)</label>
<textarea id="notes" name="notes" placeholder="Reason for visit"><?=htmlspecialchars($notes??'')?></textarea>

<button type="submit">Book Appointment</button>
</form>

<?php if($paymentLink):?>
<div class="payment-link"><a href="<?=htmlspecialchars($paymentLink)?>">Click here to Pay Now</a></div>
<?php endif;?>
</div>

<script>
const doctorsSelect = document.getElementById('doctor');
const specSelect = document.getElementById('specialization');
const dateInput = document.getElementById('date');
const timeSelect = document.getElementById('time');

const availability = <?=json_encode($availability)?>;
const bookedAppointments = <?=json_encode($bookedAppointments)?>;
const daysOfWeek = <?=json_encode($daysOfWeek)?>;
const appointmentDuration = <?= $appointmentDuration ?>;

// Filter doctors by specialization
specSelect.addEventListener('change', ()=>{
    const spec = specSelect.value;
    for (let opt of doctorsSelect.options){
        if(opt.value==='') { opt.style.display=''; continue; }
        opt.style.display = (opt.dataset.specialization===spec)?'':'none';
    }
    doctorsSelect.value=''; dateInput.value=''; timeSelect.innerHTML='<option value="">-- Select Time --</option>';
});

// Update datepicker and time slots
doctorsSelect.addEventListener('change', ()=>{
    dateInput.value=''; timeSelect.innerHTML='<option value="">-- Select Time --</option>';
    const docId = doctorsSelect.value;
    if(!docId) return;

    const docAvail = availability[docId];
    if(!docAvail) return;

    const today = new Date();
    const maxDate = new Date(); maxDate.setMonth(today.getMonth()+3);
    dateInput.min = today.toISOString().split('T')[0];
    dateInput.max = maxDate.toISOString().split('T')[0];

    dateInput.oninput = ()=>{
        const selectedDate = dateInput.value;
        const dayStr = new Date(selectedDate).toLocaleDateString('en-US',{weekday:'long'});

        if(!docAvail[dayStr]){
            alert('Doctor is not available on this day. Pick another.');
            dateInput.value=''; timeSelect.innerHTML='<option value="">-- Select Time --</option>';
            return;
        }

        // Split availability into appointmentDuration slots
        timeSelect.innerHTML='<option value="">-- Select Time --</option>';
        const slots = docAvail[dayStr];
        slots.forEach(s=>{
            let [sh, sm] = s.start.split(':').map(Number);
            let [eh, em] = s.end.split(':').map(Number);
            let startDate = new Date(0,0,0,sh,sm);
            let endDate = new Date(0,0,0,eh,em);

            while(startDate < endDate){
                let slotEnd = new Date(startDate.getTime() + appointmentDuration*60000);
                if(slotEnd > endDate) break;

                let hh = startDate.getHours().toString().padStart(2,'0');
                let mm = startDate.getMinutes().toString().padStart(2,'0');
                let slotVal = hh+':'+mm;

                let hhEnd = slotEnd.getHours().toString().padStart(2,'0');
                let mmEnd = slotEnd.getMinutes().toString().padStart(2,'0');
                let slotText = slotVal+' - '+hhEnd+':'+mmEnd;

                const opt = document.createElement('option');
                opt.value = slotVal;
                opt.textContent = slotText;

                // Disable if booked
                if(bookedAppointments[docId]?.[selectedDate]?.includes(slotVal)){
                    opt.disabled = true;
                    opt.textContent += ' (Booked)';
                }

                timeSelect.appendChild(opt);
                startDate = slotEnd;
            }
        });
    };
});
</script>

</body>
</html>
