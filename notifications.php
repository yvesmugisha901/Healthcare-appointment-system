<?php
session_start();
require 'connect.php';

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    header("Location: login.php");
    exit();
}

// Fetch user role
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userRole);
$stmt->fetch();
$stmt->close();

// Mark single notification as read
if (isset($_GET['mark_read'])) {
    $notifId = intval($_GET['mark_read']);
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET status = 'read' 
        WHERE id = ? 
          AND appointment_id IN (
              SELECT id FROM appointments WHERE patient_id = ? OR doctor_id = ?
          )
    ");
    $stmt->bind_param("iii", $notifId, $userId, $userId);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications.php");
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET status = 'read' 
        WHERE appointment_id IN (
            SELECT id FROM appointments WHERE patient_id = ? OR doctor_id = ?
        )
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications.php");
    exit();
}

// Map notification types
$typeLabels = [
    'appointment_created' => 'Appointment Created',
    'appointment_cancelled' => 'Appointment Cancelled',
    'appointment_rescheduled' => 'Appointment Rescheduled',
    'reminder_24h' => '24-Hour Reminder',
    'reminder_1h' => '1-Hour Reminder',
];

// Get notifications
function getUserNotifications($conn, $userId) {
    $sql = "
        SELECT n.id, n.appointment_id, n.type, n.sent_at, n.status,
               a.appointment_datetime, p.name AS patient_name, d.name AS doctor_name
        FROM notifications n
        JOIN appointments a ON n.appointment_id = a.id
        JOIN users p ON a.patient_id = p.id
        JOIN users d ON a.doctor_id = d.id
        WHERE a.patient_id = ? OR a.doctor_id = ?
        ORDER BY n.sent_at DESC
        LIMIT 50
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $notifications;
}

// Get unread count for badge
function getUnreadCount($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM notifications n
        JOIN appointments a ON n.appointment_id = a.id
        WHERE (a.patient_id = ? OR a.doctor_id = ?) AND n.status = 'unread'
    ");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

$notifications = getUserNotifications($conn, $userId);
$unreadCount = getUnreadCount($conn, $userId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Your Notifications</title>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f4f6f9; }
    .container { max-width: 800px; margin: auto; }
    h1 { margin-bottom: 20px; }
    .notif {
        padding: 15px; 
        border-bottom: 1px solid #ccc; 
        background: white; 
        margin-bottom: 10px; 
        border-radius: 5px; 
        position: relative;
    }
    .notif.unread { background-color: #e7f3ff; }
    .notif a.mark-read {
        position: absolute;
        right: 15px;
        top: 15px;
        color: #007BFF;
        text-decoration: none;
        font-weight: bold;
    }
    .notif a.mark-read:hover { text-decoration: underline; }
    .btn-mark-all {
        display: inline-block;
        margin-bottom: 20px;
        padding: 8px 16px;
        background-color: #007BFF;
        color: white;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }
    .btn-mark-all:hover {
        background-color: #0056b3;
    }
    .appointment-link {
        display: block;
        margin-top: 8px;
        font-size: 13px;
        color: #555;
    }
    /* Header notification dropdown */
    .header {
        display: flex;
        justify-content: flex-end;
        padding: 10px 20px;
        background-color: #007BFF;
        color: white;
        position: relative;
    }
    .notif-icon {
        cursor: pointer;
        position: relative;
        font-size: 20px;
        user-select: none;
    }
    .notif-badge {
        position: absolute;
        top: -6px;
        right: -10px;
        background: red;
        color: white;
        font-size: 12px;
        padding: 2px 6px;
        border-radius: 50%;
        font-weight: bold;
    }
    .notif-dropdown {
        display: none;
        position: absolute;
        right: 20px;
        top: 40px;
        width: 350px;
        max-height: 400px;
        overflow-y: auto;
        background: white;
        color: black;
        border-radius: 5px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        z-index: 1000;
    }
    .notif-dropdown.active { display: block; }
    .notif-dropdown .notif {
        margin: 0;
        border-bottom: 1px solid #ddd;
        border-radius: 0;
        padding: 10px;
    }
    .notif-dropdown .notif a.mark-read {
        right: 10px;
        top: 10px;
        font-size: 12px;
    }
    .notif-dropdown .empty {
        padding: 15px;
        text-align: center;
        color: #666;
    }
</style>
<script>
    function toggleNotifDropdown() {
        const dropdown = document.getElementById('notifDropdown');
        dropdown.classList.toggle('active');
    }
</script>
</head>
<body>

<!-- Header with notification bell -->
<div class="header">
    <div class="notif-icon" onclick="toggleNotifDropdown()" title="Notifications">
        🔔
        <?php if ($unreadCount > 0): ?>
            <span class="notif-badge"><?= $unreadCount ?></span>
        <?php endif; ?>
    </div>

    <div id="notifDropdown" class="notif-dropdown">
        <?php if (count($notifications) > 0): ?>
            <?php foreach ($notifications as $n): ?>
                <div class="notif <?= $n['status'] === 'unread' ? 'unread' : '' ?>">
                    <strong><?= htmlspecialchars($typeLabels[$n['type']] ?? ucwords(str_replace('_', ' ', $n['type']))); ?></strong><br>
                    Appointment on <?= date('M d, Y H:i', strtotime($n['appointment_datetime'])); ?><br>
                    Patient: <?= htmlspecialchars($n['patient_name']); ?> | Doctor: <?= htmlspecialchars($n['doctor_name']); ?>
                    <a href="?mark_read=<?= $n['id']; ?>" class="mark-read" title="Mark as read" onclick="event.stopPropagation();">Mark</a>
                    <a href="appointment.php?id=<?= intval($n['appointment_id']); ?>" class="appointment-link">View</a>
                </div>
            <?php endforeach; ?>
            <a href="?mark_all_read=1" class="btn-mark-all" onclick="return confirm('Mark all notifications as read?');" style="display:block; margin: 10px;">Mark All as Read</a>
        <?php else: ?>
            <div class="empty">No notifications.</div>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <h1>Your Notifications</h1>
    <?php if (count($notifications) > 0): ?>
        <a href="?mark_all_read=1" class="btn-mark-all" onclick="return confirm('Mark all notifications as read?');">Mark All as Read</a>

        <?php foreach ($notifications as $n): ?>
            <div class="notif <?= $n['status'] === 'unread' ? 'unread' : '' ?>">
                <strong><?= htmlspecialchars($typeLabels[$n['type']] ?? ucwords(str_replace('_', ' ', $n['type']))); ?></strong><br>
                Appointment on <?= date('M d, Y H:i', strtotime($n['appointment_datetime'])); ?><br>
                Patient: <?= htmlspecialchars($n['patient_name']); ?> | Doctor: <?= htmlspecialchars($n['doctor_name']); ?>

                <a href="?mark_read=<?= $n['id']; ?>" class="mark-read" title="Mark as read">Mark as read</a>

                <a href="appointment.php?id=<?= intval($n['appointment_id']); ?>" class="appointment-link">View Appointment Details</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No notifications.</p>
    <?php endif; ?>
</div>

</body>
</html>
