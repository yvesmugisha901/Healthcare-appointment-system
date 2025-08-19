<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
?>

<div class="sidebar">
    <div class="sidebar-header">
        <i class="fa fa-stethoscope"></i>
        <h2>HealthSys</h2>
    </div>
    <ul>
        <?php if ($role === 'admin'): ?>
            <li><a href="admin_dashboard.php" class="active"><i class="fa fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="appointments.php"><i class="fa fa-calendar-check"></i> Appointments</a></li>
            <li><a href="calendar.php"><i class="fa fa-calendar"></i> Calendar</a></li>
            <li>
                <a href="notifications.php">
                    <i class="fa fa-bell"></i> Notifications
                    <?php if ($unreadCount > 0): ?>
                        <span class="notif-badge"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="reports.php"><i class="fa fa-file-alt"></i> Reports</a></li>
            <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="manage_users.php"><i class="fa fa-users"></i> Manage Users</a></li>
            <li><a href="logout.php" class="logout"><i class="fa fa-sign-out-alt"></i> Logout</a></li>

        <?php elseif ($role === 'doctor'): ?>
            <li><a href="doctor_dash.php" class="active"><i class="fa fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="doctor_appointments.php"><i class="fa fa-calendar-check"></i> Appointments</a></li>
            <li><a href="calendar.php"><i class="fa fa-calendar"></i> Calendar</a></li>
            <li>
                <a href="notifications.php">
                    <i class="fa fa-bell"></i> Notifications
                    <?php if ($unreadCount > 0): ?>
                        <span class="notif-badge"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="doctor_reports.php"><i class="fa fa-file-alt"></i> Reports</a></li>
            <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="logout.php" class="logout"><i class="fa fa-sign-out-alt"></i> Logout</a></li>

        <?php elseif ($role === 'patient'): ?>
            <li><a href="patient_dashboard.php" class="active"><i class="fa fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="patient_appointments.php"><i class="fa fa-calendar-check"></i> Appointments</a></li>
            <li><a href="calendar.php"><i class="fa fa-calendar"></i> Calendar</a></li>
            <li>
                <a href="notifications.php">
                    <i class="fa fa-bell"></i> Notifications
                    <?php if ($unreadCount > 0): ?>
                        <span class="notif-badge"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="patient_reports.php"><i class="fa fa-file-alt"></i> Reports</a></li>
            <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="logout.php" class="logout"><i class="fa fa-sign-out-alt"></i> Logout</a></li>

        <?php else: ?>
            <li><a href="login.php"><i class="fa fa-sign-in-alt"></i> Login</a></li>
        <?php endif; ?>
    </ul>
</div>

<style>
/* Sidebar general */
.sidebar {
  width: 220px;
  height: 100vh;
  background-color: #0056b3;
  color: #fff;
  padding: 30px 20px;
  box-sizing: border-box;
  position: fixed;
  top: 0;
  left: 0;
  font-family: 'Segoe UI', sans-serif;
  display: flex;
  flex-direction: column;
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 30px;
}

.sidebar-header i {
  font-size: 24px;
  color: #fff;
}

.sidebar-header h2 {
  margin: 0;
  font-weight: 800;
  font-size: 24px;
  letter-spacing: 1px;
  user-select: none;
}

/* Menu */
.sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
  flex-grow: 1;
}

.sidebar ul li {
  margin-bottom: 15px;
}

.sidebar ul li a {
  color: #cce5ff;
  text-decoration: none;
  font-size: 16px;
  padding: 10px 15px;
  border-radius: 8px;
  display: flex;
  justify-content: flex-start;
  align-items: center;
  gap: 10px;
  transition: all 0.3s ease;
}

.sidebar ul li a:hover {
  background-color: #003d80;
  color: #fff;
  transform: translateX(5px);
}

.sidebar ul li a.active {
  background-color: #003366;
  font-weight: 700;
}

/* Logout link */
.sidebar ul li a.logout {
  color: #ff6b6b;
  font-weight: bold;
}

.sidebar ul li a.logout:hover {
  background-color: #b33939;
  color: #fff;
}

/* Notification badge */
.notif-badge {
  background: #ff4d4d;
  color: white;
  font-size: 12px;
  font-weight: 600;
  padding: 3px 7px;
  border-radius: 50%;
  min-width: 20px;
  text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    position: relative;
    width: 100%;
    height: auto;
    padding: 15px 10px;
    flex-direction: row;
    justify-content: space-around;
  }

  .sidebar-header {
    margin-bottom: 0;
  }

  .sidebar ul {
    display: flex;
    flex-direction: row;
    gap: 10px;
    flex-grow: 0;
    overflow-x: auto;
  }

  .sidebar ul li {
    margin: 0;
  }

  .sidebar ul li a {
    padding: 8px 10px;
    font-size: 14px;
  }

  .notif-badge {
    font-size: 10px;
    padding: 1px 6px;
    min-width: 16px;
    margin-left: 4px;
  }
}
</style>
