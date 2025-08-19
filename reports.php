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
<title>Admin Reports with Charts & PDF</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<style>
body { font-family: Arial, sans-serif; background: #f4f6f8; margin:0; padding:0; }
.container { padding: 30px; max-width:1200px; margin:auto; }
h1 { text-align:center; margin-bottom:30px; }
.card-container { display:flex; gap:20px; flex-wrap:wrap; justify-content:center; margin-bottom:30px; }
.card { flex:1 1 250px; background:#fff; border-radius:10px; padding:20px; text-align:center; box-shadow:0 0 10px rgba(0,0,0,0.1);}
.card h2 { font-size:2.5em; color:#007BFF; margin-bottom:5px; }
canvas { background:#fff; margin:30px 0; padding:20px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 0 10px rgba(0,0,0,0.1); margin-top:30px;}
th, td { padding:12px; border-bottom:1px solid #eee; text-align:left; }
th { background:#007BFF; color:#fff; }
tr:hover { background:#f1f1f1; }
button#downloadPdf { padding:10px 15px; margin:20px 0; background:#007BFF; color:white; border:none; border-radius:5px; cursor:pointer; }
</style>
</head>
<body>
<div class="container">
<h1>Admin Reports</h1>

<div class="card-container">
    <div class="card"><h2><?= $doctorCount ?></h2><p>Total Doctors</p></div>
    <div class="card"><h2><?= $patientCount ?></h2><p>Total Patients</p></div>
    <div class="card"><h2><?= $appointmentCount ?></h2><p>Total Appointments</p></div>
</div>

<canvas id="doctorChart" height="100"></canvas>
<canvas id="dailyChart" height="100"></canvas>

<h2>Recent Appointments (Last 7 Days)</h2>
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
            <td><?= htmlspecialchars($app['status']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<button id="downloadPdf">Download PDF</button>

<script>
// Charts
const doctorData = <?= json_encode($doctorAppointments) ?>;
const doctorLabels = doctorData.map(item => item.doctor);
const doctorCounts = doctorData.map(item => item.count);

const ctx1 = document.getElementById('doctorChart').getContext('2d');
new Chart(ctx1, {
    type:'bar',
    data:{ labels: doctorLabels, datasets:[{label:'Appointments per Doctor', data: doctorCounts, backgroundColor:'rgba(54,162,235,0.7)', borderColor:'rgba(54,162,235,1)', borderWidth:1 }]},
    options:{ responsive:true, scales:{ y:{ beginAtZero:true } } }
});

const dayData = <?= json_encode($dayAppointments) ?>;
const dayLabels = dayData.map(item=>item.day);
const dayCounts = dayData.map(item=>item.count);

const ctx2 = document.getElementById('dailyChart').getContext('2d');
new Chart(ctx2, {
    type:'line',
    data:{ labels:dayLabels, datasets:[{label:'Appointments per Day (Last 7 Days)', data:dayCounts, backgroundColor:'rgba(255,99,132,0.2)', borderColor:'rgba(255,99,132,1)', fill:true, tension:0.3, borderWidth:2}]},
    options:{ responsive:true, scales:{ y:{ beginAtZero:true } } }
});

// PDF download
document.getElementById('downloadPdf').addEventListener('click', async () => {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('p','pt','a4');
    let y = 40;

    pdf.setFontSize(18);
    pdf.text("Healthcare Admin Report", 40, y); y+=20;

    pdf.setFontSize(12);
    pdf.text(`Total Doctors: <?= $doctorCount ?>`, 40, y); y+=15;
    pdf.text(`Total Patients: <?= $patientCount ?>`, 40, y); y+=15;
    pdf.text(`Total Appointments: <?= $appointmentCount ?>`, 40, y); y+=30;

    // Charts
    const doctorChartImage = document.getElementById('doctorChart').toDataURL('image/png',1.0);
    pdf.addImage(doctorChartImage,'PNG',40,y,520,220); y+=230;
    const dailyChartImage = document.getElementById('dailyChart').toDataURL('image/png',1.0);
    pdf.addImage(dailyChartImage,'PNG',40,y,520,220); y+=230;

    // Table
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
        theme: 'grid',
        headStyles:{ fillColor:[0,123,255] },
        styles:{ fontSize:10 }
    });

    pdf.save('admin_report.pdf');
});
</script>

<!-- jsPDF AutoTable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

</body>
</html>
