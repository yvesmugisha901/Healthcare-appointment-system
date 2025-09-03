<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Fetch counts
$doctorCount = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'doctor'")->fetch_assoc()['total'];
$patientCount = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'patient'")->fetch_assoc()['total'];
$appointmentCount = $conn->query("SELECT COUNT(*) AS total FROM appointments")->fetch_assoc()['total'];

// Fetch appointments per doctor
$doctorAppointments = [];
$result1 = $conn->query("
    SELECT u.name AS doctor, COUNT(a.id) AS count
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    GROUP BY a.doctor_id
");
while ($row = $result1->fetch_assoc()) {
    $doctorAppointments[] = $row;
}

// Fetch appointments per day (last 7 days)
$dayAppointments = [];
$result2 = $conn->query("
    SELECT DATE(appointment_datetime) AS day, COUNT(id) AS count
    FROM appointments
    WHERE appointment_datetime >= CURDATE() - INTERVAL 7 DAY
    GROUP BY day
    ORDER BY day ASC
");
while ($row = $result2->fetch_assoc()) {
    $dayAppointments[] = $row;
}

// Fetch recent appointments (last 7 days)
$recentAppointments = [];
$result3 = $conn->query("
    SELECT a.id, a.appointment_datetime, a.status, u1.name AS patient_name, u2.name AS doctor_name
    FROM appointments a
    JOIN users u1 ON a.patient_id = u1.id
    JOIN users u2 ON a.doctor_id = u2.id
    WHERE a.appointment_datetime >= CURDATE() - INTERVAL 7 DAY
    ORDER BY a.appointment_datetime DESC
");
while ($row = $result3->fetch_assoc()) {
    $recentAppointments[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Reports</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<style>
:root {
    --primary: #2a9d8f;
    --primary-dark: #1d7870;
    --primary-light: #7fcdc3;
    --secondary: #e76f51;
    --neutral-light: #f8f9fa;
    --neutral-dark: #264653;
    --success: #28a745;
    --pending: #ff9900;
    --failed: #dc3545;
}

/* Reset */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }

body { background: var(--neutral-light); color: var(--neutral-dark); }

/* Container */
.container { max-width: 1200px; margin:auto; padding:30px; }

/* Heading */
h1 { text-align:center; margin-bottom:30px; color: var(--primary); }

/* Cards */
.card-container { display:flex; flex-wrap:wrap; gap:15px; justify-content:center; margin-bottom:30px; }
.card { flex:1 1 150px; min-width:120px; background:#fff; border-radius:12px; padding:20px; text-align:center;
       box-shadow:0 2px 6px rgba(0,0,0,0.08); transition:0.2s; }
.card:hover { transform: translateY(-3px); box-shadow:0 4px 12px rgba(0,0,0,0.12); }
.card h2 { font-size:1.6em; color: var(--primary); margin-bottom:5px; }
.card p { font-size:0.95em; margin:0; }

/* Charts */
canvas { background:#fff; padding:20px; border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.1); margin:30px 0; }

/* Table */
.table-wrapper { max-height:400px; overflow-y:auto; margin-top:20px; border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.1); }
table { width:100%; border-collapse:collapse; }
thead { background: linear-gradient(90deg,var(--primary),var(--primary-light)); color:#fff; position:sticky; top:0; z-index:2; }
th, td { padding:12px; text-align:left; }
tbody tr:nth-child(even) { background:#f9fbfd; }
tbody tr:hover { background:#eef4ff; }

/* Status Colors */
.status-scheduled, .status-pending { color: var(--pending); font-weight:600; }
.status-completed { color: var(--success); font-weight:600; }
.status-cancelled, .status-failed { color: var(--failed); font-weight:600; }

/* PDF Button */
button#downloadPdf { padding:12px 20px; margin:30px 0; background: var(--primary); color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:15px; }
button#downloadPdf:hover { background: var(--primary-dark); }

/* Responsive */
@media(max-width:768px){
    .card-container { flex-direction:column; align-items:center; }
}
</style>
</head>
<body>

<div class="container">
<h1>Admin Dashboard - Reports</h1>

<div class="card-container">
  <div class="card"><h2><?= $doctorCount ?></h2><p>Total Doctors</p></div>
  <div class="card"><h2><?= $patientCount ?></h2><p>Total Patients</p></div>
  <div class="card"><h2><?= $appointmentCount ?></h2><p>Total Appointments</p></div>
</div>

<canvas id="doctorChart" height="100"></canvas>
<canvas id="dailyChart" height="100"></canvas>

<h2>Recent Appointments (Last 7 Days)</h2>
<div class="table-wrapper">
  <table id="appointmentsTable">
    <thead>
      <tr>
        <th>Date & Time</th>
        <th>Patient</th>
        <th>Doctor</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($recentAppointments as $app): ?>
      <tr>
        <td><?= date('Y-m-d H:i', strtotime($app['appointment_datetime'])) ?></td>
        <td><?= htmlspecialchars($app['patient_name']) ?></td>
        <td><?= htmlspecialchars($app['doctor_name']) ?></td>
        <td class="status-<?= strtolower($app['status']) ?>">
            <?= htmlspecialchars($app['status']) ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<button id="downloadPdf">Download PDF</button>
</div>

<script>
// Charts
const doctorData = <?= json_encode($doctorAppointments) ?>;
const doctorLabels = doctorData.map(item => item.doctor);
const doctorCounts = doctorData.map(item => item.count);

new Chart(document.getElementById('doctorChart'), {
  type:'bar',
  data:{ labels: doctorLabels, datasets:[{label:'Appointments per Doctor', data: doctorCounts, backgroundColor:'rgba(42,157,143,0.7)', borderColor:'rgba(42,157,143,1)', borderWidth:1 }]},
  options:{ responsive:true, scales:{ y:{ beginAtZero:true } } }
});

const dayData = <?= json_encode($dayAppointments) ?>;
const dayLabels = dayData.map(item=>item.day);
const dayCounts = dayData.map(item=>item.count);

new Chart(document.getElementById('dailyChart'), {
  type:'line',
  data:{ labels:dayLabels, datasets:[{label:'Appointments per Day (Last 7 Days)', data:dayCounts, backgroundColor:'rgba(42,157,143,0.2)', borderColor:'rgba(42,157,143,1)', fill:true, tension:0.3, borderWidth:2}]},
  options:{ responsive:true, scales:{ y:{ beginAtZero:true } } }
});

// PDF download
document.getElementById('downloadPdf').addEventListener('click', async () => {
  const { jsPDF } = window.jspdf;
  const pdf = new jsPDF('p','pt','a4');
  let y = 40;

  pdf.setFontSize(18);
  pdf.text("Healthcare Admin Report", 40, y); y+=30;

  pdf.setFontSize(12);
  pdf.text(`Total Doctors: <?= $doctorCount ?>`, 40, y); y+=15;
  pdf.text(`Total Patients: <?= $patientCount ?>`, 40, y); y+=15;
  pdf.text(`Total Appointments: <?= $appointmentCount ?>`, 40, y); y+=30;

  const doctorChartImage = document.getElementById('doctorChart').toDataURL('image/png',1.0);
  pdf.addImage(doctorChartImage,'PNG',40,y,520,220); y+=230;

  const dailyChartImage = document.getElementById('dailyChart').toDataURL('image/png',1.0);
  pdf.addImage(dailyChartImage,'PNG',40,y,520,220); y+=230;

  pdf.text("Recent Appointments (Last 7 Days)",40,y); y+=20;

  const rows = [];
  const table = document.getElementById('appointmentsTable').getElementsByTagName('tbody')[0].rows;
  for(let i=0;i<table.length;i++){
    const row = table[i];
    rows.push([row.cells[0].innerText, row.cells[1].innerText, row.cells[2].innerText, row.cells[3].innerText]);
  }
  pdf.autoTable({
    startY: y,
    head: [['Date & Time','Patient','Doctor','Status']],
    body: rows,
    theme: 'striped',
    headStyles:{ fillColor:[42,157,143] },
    styles:{ fontSize:10 }
  });

  pdf.save('admin_report.pdf');
});
</script>
</body>
</html>
