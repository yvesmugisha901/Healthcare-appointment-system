<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'connect.php';

$userId = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';
$unreadCount = 0;

// Count unread notifications depending on role
if ($userId) {
    if ($role === 'doctor') {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM notifications n
            JOIN appointments a ON n.appointment_id = a.id
            WHERE a.doctor_id = ? AND n.status = 'unread'
        ");
        $stmt->bind_param("i", $userId);
    } elseif ($role === 'patient') {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM notifications n
            JOIN appointments a ON n.appointment_id = a.id
            WHERE a.patient_id = ? AND n.status = 'unread'
        ");
        $stmt->bind_param("i", $userId);
    } elseif ($role === 'admin') {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE status = 'unread'");
    } else {
        $stmt = null;
    }

    if ($stmt) {
        $stmt->execute();
        $stmt->bind_result($unreadCount);
        $stmt->fetch();
        $stmt->close();
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Hamburger toggle for mobile -->
<button id="sidebarToggle">
    <i class="fa fa-bars"></i>
</button>

<div class="sidebar">
    <div class="sidebar-header">
        <i class="fa fa-stethoscope"></i>
        <h2>HealthSys</h2>
    </div>
    <ul>
        <?php if ($role === 'admin'): ?>
            <li><a href="admin_dashboard.php" class="<?= ($currentPage=='admin_dashboard.php')?'active':'' ?>"><i class="fa fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="appointments.php" class="<?= ($currentPage=='appointments.php')?'active':'' ?>"><i class="fa fa-calendar-check"></i> <span>Appointments</span></a></li>
            <li><a href="calendar.php" class="<?= ($currentPage=='calendar.php')?'active':'' ?>"><i class="fa fa-calendar"></i> <span>Calendar</span></a></li>
            <li><a href="notifications.php" class="<?= ($currentPage=='notifications.php')?'active':'' ?>"><i class="fa fa-bell"></i> <span>Notifications</span>
                <?php if ($unreadCount>0): ?><span class="notif-badge"><?= $unreadCount ?></span><?php endif; ?></a></li>
            <li><a href="reports.php" class="<?= ($currentPage=='reports.php')?'active':'' ?>"><i class="fa fa-file-alt"></i> <span>Reports</span></a></li>
            <li><a href="settings.php" class="<?= ($currentPage=='settings.php')?'active':'' ?>"><i class="fa fa-cog"></i> <span>Settings</span></a></li>
            <li><a href="manage_users.php" class="<?= ($currentPage=='manage_users.php')?'active':'' ?>"><i class="fa fa-users"></i> <span>Manage Users</span></a></li>
            <li><a href="logout.php" class="logout"><i class="fa fa-sign-out-alt"></i> <span>Logout</span></a></li>

        <?php elseif ($role === 'doctor'): ?>
            <li><a href="doctor_dash.php" class="<?= ($currentPage=='doctor_dash.php')?'active':'' ?>"><i class="fa fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="doctor_appointments.php" class="<?= ($currentPage=='doctor_appointments.php')?'active':'' ?>"><i class="fa fa-calendar-check"></i> <span>Appointments</span></a></li>
            <li><a href="calendar.php" class="<?= ($currentPage=='calendar.php')?'active':'' ?>"><i class="fa fa-calendar"></i> <span>Calendar</span></a></li>
            <li><a href="notifications.php" class="<?= ($currentPage=='notifications.php')?'active':'' ?>"><i class="fa fa-bell"></i> <span>Notifications</span>
                <?php if ($unreadCount>0): ?><span class="notif-badge"><?= $unreadCount ?></span><?php endif; ?></a></li>
            <li><a href="doctor_reports.php" class="<?= ($currentPage=='doctor_reports.php')?'active':'' ?>"><i class="fa fa-file-alt"></i> <span>Reports</span></a></li>
            <li><a href="settings.php" class="<?= ($currentPage=='settings.php')?'active':'' ?>"><i class="fa fa-cog"></i> <span>Settings</span></a></li>
            <li><a href="logout.php" class="logout"><i class="fa fa-sign-out-alt"></i> <span>Logout</span></a></li>

        <?php elseif ($role === 'patient'): ?>
            <li><a href="patient_dashboard.php" class="<?= ($currentPage=='patient_dashboard.php')?'active':'' ?>"><i class="fa fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="bookappointment.php" class="<?= ($currentPage=='bookappointment.php')?'active':'' ?>"><i class="fa fa-calendar-check"></i> <span>Book Appointment</span></a></li>
            <li><a href="calendar.php" class="<?= ($currentPage=='calendar.php')?'active':'' ?>"><i class="fa fa-calendar"></i> <span>Calendar</span></a></li>
            <li><a href="notifications.php" class="<?= ($currentPage=='notifications.php')?'active':'' ?>"><i class="fa fa-bell"></i> <span>Notifications</span>
                <?php if ($unreadCount>0): ?><span class="notif-badge"><?= $unreadCount ?></span><?php endif; ?></a></li>
            <li><a href="payment_history.php" class="<?= ($currentPage=='payment_history.php')?'active':'' ?>"><i class="fa fa-credit-card"></i> <span>Payment History</span></a></li>
            <li><a href="patientprofile.php" class="<?= ($currentPage=='patientprofile.php')?'active':'' ?>"><i class="fa fa-user"></i> <span>Profile</span></a></li>
            <li><a href="settings.php" class="<?= ($currentPage=='settings.php')?'active':'' ?>"><i class="fa fa-cog"></i> <span>Settings</span></a></li>
            <li><a href="logout.php" class="logout"><i class="fa fa-sign-out-alt"></i> <span>Logout</span></a></li>
        <?php else: ?>
            <li><a href="login.php"><i class="fa fa-sign-in-alt"></i> <span>Login</span></a></li>
        <?php endif; ?>
    </ul>
</div>

<style>
:root {
  --primary:#2a9d8f;
  --primary-light:#7fcdc3;
  --danger:#dc3545;
  --success:#28a745;
  --text:#fff;
}

/* Hamburger toggle */
#sidebarToggle {
  display: none;
  position: fixed;
  top: 10px;
  left: 10px;
  z-index: 1001;
  background: var(--primary);
  color: #fff;
  border: none;
  padding: 10px 15px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
}

/* Sidebar */
.sidebar {
  width: 220px;
  background: var(--primary);
  color: #fff;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  padding: 25px 20px;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  transition: 0.3s;
  z-index: 1000;
  border-right: 2px solid var(--primary-light);
  box-shadow: 2px 0 15px rgba(0,0,0,0.05);
  font-family: 'Inter', sans-serif;
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 40px;
}
.sidebar-header i {
  font-size: 28px;
}
.sidebar-header h2 {
  margin: 0;
  font-size: 26px;
  font-weight: 800;
  letter-spacing: -1px;
}

/* Menu */
.sidebar ul { list-style:none; padding:0; margin:0; flex-grow:1; }
.sidebar ul li { margin-bottom: 18px; }
.sidebar ul li a {
  color: #cce5ff;
  text-decoration:none;
  font-size:16px;
  padding:12px 18px;
  border-radius:10px;
  display:flex;
  align-items:center;
  gap:12px;
  transition: all 0.3s ease;
}
.sidebar ul li a:hover { 
  background: var(--primary-light); 
  transform: translateX(5px);
}
.sidebar ul li a.active { 
  background:#145d56; 
  font-weight:700;
}
.sidebar ul li a.logout { 
  color:#ff6b6b; 
}
.sidebar ul li a.logout:hover { 
  background:#b33939; 
  color:#fff; 
}

/* Notification badge */
.notif-badge {
  background:#ff4d4d;
  color:#fff;
  font-size:12px;
  font-weight:600;
  padding:3px 7px;
  border-radius:50%;
  min-width:20px;
  text-align:center;
}

/* Responsive for tablets */
@media (max-width:768px) {
  .sidebar { width: 100%; height:auto; position:relative; flex-direction:row; justify-content:space-around; padding:10px; }
  .sidebar-header { margin-bottom:0; }
  .sidebar ul { flex-direction:row; gap:10px; overflow-x:auto; flex-grow:0; }
  .sidebar ul li { margin:0; }
  .sidebar ul li a { padding:8px 10px; font-size:14px; }
  .notif-badge { font-size:10px; padding:1px 6px; min-width:16px; margin-left:4px; }
}

/* Responsive for mobile */
@media (max-width:500px) {
  #sidebarToggle { display:block; }
  .sidebar { left:-250px; top:0; height:100%; flex-direction:column; transition:0.3s; }
  .sidebar.show { left:0; }
  .sidebar ul { flex-direction:column; overflow-y:auto; max-height:90vh; }
  .sidebar ul li a span { display:inline-block; }
  .sidebar-header h2 { font-size:18px; }
  .notif-badge { font-size:9px; min-width:14px; padding:2px 4px; }
}
</style>

<script>
const sidebar = document.querySelector('.sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('show');
});
</script>
