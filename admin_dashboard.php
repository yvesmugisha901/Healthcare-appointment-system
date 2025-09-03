<?php
session_start();
require 'connect.php';

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch admin name
$adminId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$stmt->bind_result($adminName);
$stmt->fetch();
$stmt->close();

// Dashboard cards
$totalAppointments = 0;
if ($res = $conn->query("SELECT COUNT(*) AS total FROM appointments")) {
    if ($row = $res->fetch_assoc()) $totalAppointments = (int)$row['total'];
}

$pendingDoctorApprovals = 0;
if ($res = $conn->query("SELECT COUNT(*) AS pending FROM doctors WHERE status = 'pending'")) {
    if ($row = $res->fetch_assoc()) $pendingDoctorApprovals = (int)$row['pending'];
}

$activeUsers = 0;
if ($res = $conn->query("SELECT COUNT(*) AS active FROM users WHERE role IN ('patient','doctor','admin')")) {
    if ($row = $res->fetch_assoc()) $activeUsers = (int)$row['active'];
}

// Handle approve/reject pending doctors
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doctor_id'], $_POST['action'])) {
    $doctor_id = (int)$_POST['doctor_id'];
    $action = $_POST['action'];

    if (in_array($action, ['approve', 'reject'], true)) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE doctors SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $doctor_id);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_dashboard.php");
        exit;
    }
}

// Fetch doctors (all statuses)
$doctors = [];
$res = $conn->query("
    SELECT d.id AS doctor_id,
           u.name AS doctor_name,
           u.email,
           d.specialization,
           d.qualification,
           d.license_no,
           d.status
    FROM doctors d
    LEFT JOIN users u ON d.user_id = u.id
    ORDER BY d.id ASC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Fetch recent appointments with specialization
$recentAppointments = [];
$res = $conn->query("
    SELECT a.id,
           p.name AS patient_name,
           u.name AS doctor_name,
           COALESCE(d.specialization,'-') AS specialization,
           a.appointment_datetime,
           a.status
    FROM appointments a
    LEFT JOIN users p ON a.patient_id = p.id
    LEFT JOIN users u ON a.doctor_id = u.id
    LEFT JOIN doctors d ON d.user_id = a.doctor_id
    ORDER BY a.appointment_datetime DESC
    LIMIT 5
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $recentAppointments[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - MedConnect</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #2a9d8f;
    --primary-dark: #1d7870;
    --primary-light: #7fcdc3;
    --secondary: #e76f51;
    --neutral-dark: #264653;
    --neutral-medium: #6c757d;
    --neutral-light: #f8f9fa;
    --white: #ffffff;
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
    --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
    --transition: all 0.3s ease;
    --radius: 12px;
    --radius-lg: 16px;
}
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
body { display:flex; min-height:100vh; background: var(--neutral-light); color: var(--neutral-dark); }

.sidebar { width: 240px; background: var(--primary); color: var(--white); height: 100vh; position: fixed; top: 0; left:0; padding: 25px 20px; display:flex; flex-direction:column; transition: 0.3s; z-index:1000; }
.sidebar-header { display:flex; align-items:center; justify-content:center; gap:10px; margin-bottom:40px; }
.sidebar-header i { font-size:28px; }
.sidebar-header h2 { font-size:24px; font-weight:800; }
.sidebar nav { display:flex; flex-direction:column; gap:10px; flex-grow:1; }
.sidebar nav a { display:flex; align-items:center; gap:12px; padding:10px 15px; border-radius: var(--radius); color:#cce5ff; text-decoration:none; transition: var(--transition); }
.sidebar nav a.active { background: var(--primary-dark); font-weight:700; }
.sidebar nav a:hover { background: var(--primary-light); transform: translateX(5px); }
.sidebar nav a.logout { color:#ff6b6b; }
.sidebar nav a.logout:hover { background:#b33939; color:#fff; }

.main-content { flex:1; margin-left:240px; padding:30px; }
.main-content h1 { margin-bottom:30px; color: var(--neutral-dark); }
.dashboard-cards { display:flex; flex-wrap:wrap; gap:20px; margin-bottom:30px; }
.card { flex:1; min-width:180px; background: var(--white); padding:25px; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); text-align:center; transition: var(--transition); }
.card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
.card h3 { color: var(--primary); margin-bottom:12px; font-weight:600; }
.card p { font-size:28px; font-weight:bold; color: var(--neutral-dark); }

.table-card { background: var(--white); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); overflow:hidden; padding:20px; margin-top:25px; transition: var(--transition); }
.table-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
.table-card h2 { text-align:center; color: var(--primary-dark); margin-bottom:20px; }

table { width:100%; border-collapse: collapse; }
thead tr { background: linear-gradient(90deg,var(--primary),var(--secondary)); color:#fff; }
th, td { padding:12px; text-align:center; border-bottom:1px solid #eee; }

button.approve-btn { background: green; color:#fff; padding:6px 12px; border:none; border-radius:8px; cursor:pointer; transition: var(--transition);}
button.approve-btn:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }
button.reject-btn { background: var(--secondary); color:#fff; padding:6px 12px; border:none; border-radius:8px; cursor:pointer; transition: var(--transition);}
button.reject-btn:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }

.status-pending { color: #ff9900; font-weight:600; }
.status-approved { color: green; font-weight:600; }
.status-rejected { color: var(--secondary); font-weight:600; }

#sidebarToggle { display:none; position:fixed; top:15px; left:15px; z-index:1001; background: var(--primary); color:#fff; border:none; padding:10px 15px; border-radius:5px; cursor:pointer; }

@media(max-width:768px){
    .sidebar{ width:100%; height:auto; flex-direction:row; justify-content:space-around; padding:15px; }
    .main-content{ margin-left:0; padding:20px; }
    .dashboard-cards{ flex-direction:column; }
}
@media(max-width:500px){
    #sidebarToggle { display:block; }
    .sidebar { left:-260px; position:fixed; top:0; height:100%; flex-direction:column; transition:0.3s; }
    .sidebar.show { left:0; }
    .sidebar nav { flex-direction:column; overflow-y:auto; max-height:90vh; }
}
</style>
</head>
<body>

<button id="sidebarToggle"><i class="fa fa-bars"></i></button>

<aside class="sidebar">
    <div class="sidebar-header">
        <i class="fa fa-cogs"></i>
        <h2>Admin Panel</h2>
    </div>
    <nav>
      <a href="admin_dashboard.php" class="active"><i class="fa fa-home"></i> Home</a>
      <a href="manage_users.php"><i class="fa fa-users"></i> Manage Users</a>
      <a href="appointments.php"><i class="fa fa-calendar-check"></i> Appointments</a>
      <a href="payments.php"><i class="fa fa-credit-card"></i> Payments</a>
      <a href="reports.php"><i class="fa fa-file-alt"></i> Reports</a>
      <a href="settings.php"><i class="fa fa-cog"></i> Settings</a>
      <a href="logout.php" class="logout"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
<h1>Welcome, <?= htmlspecialchars($adminName) ?> 👋</h1>

<div class="dashboard-cards">
    <div class="card">
        <h3>Total Appointments</h3>
        <p><?= $totalAppointments ?></p>
    </div>
    <div class="card">
        <h3>Pending Doctor Approvals</h3>
        <p><?= $pendingDoctorApprovals ?></p>
    </div>
    <div class="card">
        <h3>Active Users</h3>
        <p><?= $activeUsers ?></p>
    </div>
</div>

<!-- Doctor table -->
<div class="table-card">
    <h2>Doctor Records & Approvals</h2>
    <?php if(count($doctors) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Doctor Name</th>
                <th>Email</th>
                <th>Specialization</th>
                <th>Qualification</th>
                <th>License No</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($doctors as $doc): ?>
            <tr>
                <td><?= htmlspecialchars($doc['doctor_name'] ?: '-') ?></td>
                <td><?= htmlspecialchars($doc['email'] ?: '-') ?></td>
                <td><?= htmlspecialchars($doc['specialization'] ?: '-') ?></td>
                <td><?= htmlspecialchars($doc['qualification'] ?: '-') ?></td>
                <td><?= htmlspecialchars($doc['license_no'] ?: '-') ?></td>
                <td class="status-<?= htmlspecialchars($doc['status'] ?: 'pending') ?>"><?= ucfirst($doc['status'] ?: 'pending') ?></td>
                <td>
                    <?php if(($doc['status'] ?? 'pending') === 'pending'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="doctor_id" value="<?= (int)$doc['doctor_id'] ?>">
                        <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                        <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                    </form>
                    <?php else: ?>
                    <span>—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="text-align:center; padding:20px; color: var(--neutral-medium);">No doctor records found</p>
    <?php endif; ?>
</div>

<!-- Recent appointments -->
<div class="table-card">
    <h2>Recent Appointments</h2>
    <?php if(count($recentAppointments) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Specialization</th>
                <th>Date & Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($recentAppointments as $appt): ?>
            <tr>
                <td><?= htmlspecialchars($appt['patient_name']) ?></td>
                <td><?= htmlspecialchars($appt['doctor_name']) ?></td>
                <td><?= htmlspecialchars($appt['specialization']) ?></td>
                <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($appt['appointment_datetime']))) ?></td>
                <td>
                    <?php
                        $s = strtolower($appt['status']);
                        if ($s === 'confirmed' || $s === 'booked' || $s === 'scheduled') {
                            echo '<span class="status-approved">Scheduled</span>';
                        } elseif ($s === 'pending') {
                            echo '<span class="status-pending">Pending</span>';
                        } else {
                            echo '<span class="status-rejected">'.htmlspecialchars(ucfirst($appt['status'])).'</span>';
                        }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="text-align:center; padding:20px; color: var(--neutral-medium);">No recent appointments</p>
    <?php endif; ?>
</div>

</main>

<script>
const sidebar = document.querySelector('.sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
toggleBtn.addEventListener('click', () => sidebar.classList.toggle('show'));
</script>

</body>
</html>
