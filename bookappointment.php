<?php
session_start();
require 'connect.php';

// ==========================
// Check logged-in patient
// ==========================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$patientId = $_SESSION['user_id'];
$patientName = $_SESSION['name'] ?? 'Patient';

// ==========================
// Fetch doctors list
// ==========================
$doctors = [];
$docResult = $conn->query("SELECT id, name, specialization, location FROM users WHERE role='doctor'");
if ($docResult) {
    while ($row = $docResult->fetch_assoc()) $doctors[] = $row;
}

// ==========================
// Fetch doctor availability
// ==========================
$availability = [];
$availResult = $conn->query("SELECT doctor_id, day_of_week, start_time, end_time FROM availability");
if ($availResult) {
    while ($row = $availResult->fetch_assoc()) {
        $did = $row['doctor_id'];
        $day = ucfirst(strtolower($row['day_of_week']));
        if (!isset($availability[$did])) $availability[$did] = [];
        if (!isset($availability[$did][$day])) $availability[$did][$day] = [];
        $availability[$did][$day][] = ['start'=>$row['start_time'],'end'=>$row['end_time']];
    }
}

// ==========================
// Fetch booked appointments
// ==========================
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

// ==========================
// Booking logic
// ==========================
$successMessage = '';
$errorMessage = '';
$paymentLink = '';
$appointmentDuration = 60; // minutes

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = $_POST['doctor'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $type = $_POST['type'] ?? '';

    if (!$doctorId || !$date || !$time || !$type) {
        $errorMessage = "Please fill in all required fields.";
    } else {
        $timezone = new DateTimeZone('Africa/Kigali'); 
        $dt = new DateTime($date . ' ' . $time, $timezone);
        $dt->setTimezone(new DateTimeZone('UTC'));
        $appointment_datetime = $dt->format('Y-m-d H:i:s');

        // Check patient double booking
        $stmtCheck1 = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id=? AND appointment_datetime=? AND TRIM(LOWER(status))='booked'");
        $stmtCheck1->bind_param("is", $patientId, $appointment_datetime);
        $stmtCheck1->execute();
        $stmtCheck1->bind_result($existsPatient);
        $stmtCheck1->fetch();
        $stmtCheck1->close();

        // Check doctor double booking
        $stmtCheck2 = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appointment_datetime=? AND TRIM(LOWER(status))='booked'");
        $stmtCheck2->bind_param("is", $doctorId, $appointment_datetime);
        $stmtCheck2->execute();
        $stmtCheck2->bind_result($existsDoctor);
        $stmtCheck2->fetch();
        $stmtCheck2->close();

        if ($existsPatient > 0) $errorMessage = "You already have an appointment at this time.";
        else if ($existsDoctor > 0) $errorMessage = "This doctor is already booked at this time.";
        else {
            $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_datetime, notes, type) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) $errorMessage = "Prepare failed: " . $conn->error;
            else {
                $stmt->bind_param("iisss", $patientId, $doctorId, $appointment_datetime, $notes, $type);
                if ($stmt->execute()) {
                    $appointmentId = $stmt->insert_id;
                    $successMessage = "Appointment booked successfully for $date at $time ($type).";
                    $paymentLink = "patient_payment.php?appointment_id=$appointmentId";

                    // ==========================
                    // Insert notification for doctor
                    // ==========================
                    $notifStmt = $conn->prepare("INSERT INTO notifications (appointment_id,type,sent_at,status,recipient_id,recipient_role,related_table,related_id) VALUES (?, ?, NOW(), 'unread', ?, 'doctor', 'appointments', ?)");
                    $notifType='appointment_created';
                    $notifStmt->bind_param("isii",$appointmentId,$notifType,$doctorId,$appointmentId);
                    $notifStmt->execute();
                    $notifStmt->close();

                    // ==========================
                    // Simulated email to patient
                    // ==========================
                    $simulatedEmail = "To: $patientName\n";
                    $simulatedEmail .= "Subject: Appointment Reminder\n";
                    $simulatedEmail .= "Message: Your appointment is scheduled on $date at $time.\n";
                    $successMessage .= "<br><br><strong>Simulated Email Sent:</strong><pre>$simulatedEmail</pre>";

                } else $errorMessage = "Error booking appointment: " . $stmt->error;
                $stmt->close();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Book Appointment - Healthcare System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Sidebar-safe styling */
body { font-family: Arial, sans-serif; background: #f0f4f8; margin:0; display:flex; }
.main-content { flex:1; padding:30px; min-height:100vh; box-sizing:border-box; }
.container { max-width:600px; margin:auto; background:white; padding:25px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1);}
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

<?php include('sidebar.php'); ?>

<div class="main-content">
<div class="container">
<h1>Book Appointment</h1>
<p>Welcome, <?=htmlspecialchars($patientName)?>. Fill the form below.</p>

<?php if($successMessage):?><div class="message success"><?= $successMessage ?></div><?php endif;?>
<?php if($errorMessage):?><div class="message error"><?= $errorMessage ?></div><?php endif;?>

<form method="POST" action="">
<label for="specialization">Specialization</label>
<select id="specialization">
  <option value="">-- Select Specialization --</option>
  <?php
  $specs = array_unique(array_map(fn($d)=>$d['specialization'],$doctors));
  foreach($specs as $s) echo "<option value=\"$s\">$s</option>";
  ?>
</select>

<label for="location">Location</label>
<select id="location">
  <option value="">-- Select Location --</option>
  <?php
  $locations = array_unique(array_map(fn($d)=>$d['location'],$doctors));
  foreach($locations as $loc) echo "<option value=\"" . htmlspecialchars($loc) . "\">" . htmlspecialchars($loc) . "</option>";
  ?>
</select>

<label for="doctor">Choose Doctor <span style="color:red">*</span></label>
<select id="doctor" name="doctor" required>
  <option value="">-- Select Doctor --</option>
  <?php foreach($doctors as $doc): ?>
    <option value="<?= $doc['id']; ?>" data-specialization="<?= htmlspecialchars($doc['specialization']); ?>" data-location="<?= htmlspecialchars($doc['location']); ?>">
      <?= htmlspecialchars($doc['name']." ({$doc['specialization']}, {$doc['location']})"); ?>
    </option>
  <?php endforeach; ?>
</select>

<label for="type">Appointment Type</label>
<select id="type" name="type" required>
  <option value="in-person">In-person</option>
  <option value="teleconsultation">Teleconsultation</option>
</select>

<label for="date">Date <span style="color:red">*</span></label>
<input type="date" id="date" name="date" required>

<label for="time">Time <span style="color:red">*</span></label>
<select id="time" name="time" required>
  <option value="">-- Select Time --</option>
</select>

<label for="notes">Notes (optional)</label>
<textarea id="notes" name="notes" placeholder="Reason for visit"></textarea>

<button type="submit">Book Appointment</button>
</form>

<?php if($paymentLink):?>
<div class="payment-link"><a href="<?=htmlspecialchars($paymentLink)?>">Click here to Pay Now</a></div>
<?php endif;?>
</div>
</div>

<script>
const doctorsSelect = document.getElementById('doctor');
const specSelect = document.getElementById('specialization');
const locationSelect = document.getElementById('location');
const dateInput = document.getElementById('date');
const timeSelect = document.getElementById('time');

const availability = <?=json_encode($availability)?>;
const bookedAppointments = <?=json_encode($bookedAppointments)?>;
const appointmentDuration = <?= $appointmentDuration ?>;

function filterDoctors() {
    const spec = specSelect.value;
    const loc = locationSelect.value;
    for (let opt of doctorsSelect.options){
        if(opt.value==='') { opt.style.display=''; continue; }
        const matchSpec = !spec || opt.dataset.specialization === spec;
        const matchLoc = !loc || opt.dataset.location === loc;
        opt.style.display = (matchSpec && matchLoc) ? '' : 'none';
    }
    doctorsSelect.value=''; dateInput.value=''; timeSelect.innerHTML='<option value="">-- Select Time --</option>';
}

specSelect.addEventListener('change', filterDoctors);
locationSelect.addEventListener('change', filterDoctors);

doctorsSelect.addEventListener('change', ()=> {
    dateInput.value=''; timeSelect.innerHTML='<option value="">-- Select Time --</option>';
    const docId = doctorsSelect.value;
    if(!docId) return;

    const today = new Date();
    const maxDate = new Date(); maxDate.setMonth(today.getMonth()+3);
    dateInput.min = today.toISOString().split('T')[0];
    dateInput.max = maxDate.toISOString().split('T')[0];

    dateInput.oninput = ()=> {
        const selectedDate = dateInput.value;
        if(!selectedDate) return;
        const dayStr = new Date(selectedDate).toLocaleDateString('en-US',{weekday:'long'});

        const docAvail = availability[docId];
        if(!docAvail || !docAvail[dayStr]){
            alert('Doctor is not available on this day. Pick another.');
            dateInput.value=''; timeSelect.innerHTML='<option value="">-- Select Time --</option>';
            return;
        }

        timeSelect.innerHTML='<option value="">-- Select Time --</option>';
        docAvail[dayStr].forEach(slot=>{
            let [sh, sm] = slot.start.split(':').map(Number);
            let [eh, em] = slot.end.split(':').map(Number);
            let startDate = new Date(0,0,0,sh,sm);
            let endDate = new Date(0,0,0,eh,em);
            if(endDate <= startDate) endDate.setDate(endDate.getDate()+1);

            while(startDate < endDate){
                let slotEnd = new Date(startDate.getTime() + appointmentDuration*60000);
                if(slotEnd > endDate) break;

                const hh = startDate.getHours().toString().padStart(2,'0');
                const mm = startDate.getMinutes().toString().padStart(2,'0');
                const hhEnd = slotEnd.getHours().toString().padStart(2,'0');
                const mmEnd = slotEnd.getMinutes().toString().padStart(2,'0');

                const slotVal = `${hh}:${mm}`;
                const slotText = `${hh}:${mm} - ${hhEnd}:${mmEnd}`;

                const opt = document.createElement('option');
                opt.value = slotVal;
                opt.textContent = slotText;

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
