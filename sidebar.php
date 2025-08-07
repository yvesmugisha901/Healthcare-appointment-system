<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'connect.php';

$userId = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';
$unreadCount = 0;

if ($userId) {
    // Count unread notifications depending on role
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
        // Admin sees all unread notifications
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
    <h2>HealthSys</h2>
    <ul>
        <?php if ($role === 'admin'): ?>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="calendar.php">Calendar</a></li>
            <li>
                <a href="notifications.php">
                    Notifications
                    <?php if ($unreadCount > 0): ?>
                        <span class="notif-badge"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="settings.php">Settings</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="logout.php" class="logout">Logout</a></li>

        <?php elseif ($role === 'doctor'): ?>
            <li><a href="doctor_dash.php">Dashboard</a></li>
            <li><a href="doctor_appointments.php">Appointments</a></li>
            <li><a href="calendar.php">Calendar</a></li>
            <li>
                <a href="notifications.php">
                    Notifications
                    <?php if ($unreadCount > 0): ?>
                        <span class="notif-badge"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="doctor_reports.php">Reports</a></li>
            <li><a href="settings.php">Settings</a></li>
            <li><a href="logout.php" class="logout">Logout</a></li>

        <?php elseif ($role === 'patient'): ?>
            <li><a href="patient_dashboard.php">Dashboard</a></li>
            <li><a href="patient_appointments.php">Appointments</a></li>
            <li><a href="calendar.php">Calendar</a></li>
            <li>
                <a href="notifications.php">
                    Notifications
                    <?php if ($unreadCount > 0): ?>
                        <span class="notif-badge"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="patient_reports.php">Reports</a></li>
            <li><a href="settings.php">Settings</a></li>
            <li><a href="logout.php" class="logout">Logout</a></li>

        <?php else: ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</div>

<style>
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
  font-family: Arial, sans-serif;
  display: flex;
  flex-direction: column;
}

.sidebar h2 {
  margin: 0 0 30px 0;
  font-weight: 700;
  font-size: 24px;
  text-align: center;
  user-select: none;
}

.sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
  flex-grow: 1;
}

.sidebar ul li {
  margin-bottom: 20px;
}

.sidebar ul li a {
  color: #cce5ff;
  text-decoration: none;
  font-size: 16px;
  padding: 10px 15px;
  border-radius: 6px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: background-color 0.3s ease;
}

.sidebar ul li a:hover {
  background-color: #003d80;
  color: #fff;
}

.sidebar ul li a.logout {
  color: #ff6b6b;
  font-weight: 600;
}

.sidebar ul li a.logout:hover {
  background-color: #b33939;
  color: #fff;
}

.notif-badge {
  background: #ff4d4d;
  color: white;
  font-size: 12px;
  font-weight: bold;
  padding: 2px 8px;
  border-radius: 12px;
  min-width: 20px;
  text-align: center;
  margin-left: 8px;
}

@media (max-width: 768px) {
  .sidebar {
    position: relative;
    width: 100%;
    height: auto;
    padding: 15px 10px;
    display: flex;
    flex-direction: row;
    justify-content: space-around;
  }

  .sidebar h2 {
    font-size: 18px;
    margin: 0;
    padding-right: 10px;
    line-height: 40px;
  }

  .sidebar ul {
    display: flex;
    flex-direction: row;
    gap: 10px;
    flex-grow: 0;
  }

  .sidebar ul li {
    margin: 0;
  }

  .sidebar ul li a {
    padding: 8px 12px;
    font-size: 14px;
    border-radius: 4px;
  }

  .notif-badge {
    font-size: 10px;
    padding: 1px 6px;
    min-width: 16px;
    margin-left: 4px;
  }
}
</style>
