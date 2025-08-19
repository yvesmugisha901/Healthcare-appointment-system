<?php
session_start();
require 'connect.php';

// Check user login
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
    $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND (recipient_id = ? OR recipient_role = ?)");
    $stmt->bind_param("iis", $notifId, $userId, $userRole);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications.php");
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE (recipient_id = ? OR recipient_role = ?)");
    $stmt->bind_param("is", $userId, $userRole);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications.php");
    exit();
}

// Notification type labels
$typeLabels = [
    'appointment_created' => 'Appointment Created',
    'appointment_cancelled' => 'Appointment Cancelled',
    'appointment_rescheduled' => 'Appointment Rescheduled',
    'user_created' => 'New User Added',
    'user_deleted' => 'User Deleted',
];

// Fetch notifications for the user based on role or user_id
$stmt = $conn->prepare("
    SELECT n.id, n.type, n.status, n.sent_at, n.related_id, n.related_table
    FROM notifications n
    WHERE n.recipient_id = ? OR n.recipient_role = ?
    ORDER BY n.sent_at DESC
    LIMIT 50
");
$stmt->bind_param("is", $userId, $userRole);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unread count for badge
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE status='unread' AND (recipient_id = ? OR recipient_role = ?)");
$stmt->bind_param("is", $userId, $userRole);
$stmt->execute();
$stmt->bind_result($unreadCount);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
.container { max-width: 800px; margin: auto; }
h1 { margin-bottom: 20px; }
.notif { padding: 15px; border-bottom: 1px solid #ccc; background: white; margin-bottom: 10px; border-radius: 5px; position: relative; }
.notif.unread { background-color: #e7f3ff; }
.notif a.mark-read { position: absolute; right: 15px; top: 15px; color: #007BFF; text-decoration: none; font-weight: bold; }
.notif a.mark-read:hover { text-decoration: underline; }
.btn-mark-all { display: inline-block; margin-bottom: 20px; padding: 8px 16px; background-color: #007BFF; color: white; border-radius: 5px; text-decoration: none; font-weight: bold; }
.btn-mark-all:hover { background-color: #0056b3; }
</style>
</head>
<body>
<div class="container">
    <h1>Notifications</h1>

    <?php if ($unreadCount > 0): ?>
        <a href="?mark_all_read=1" class="btn-mark-all" onclick="return confirm('Mark all notifications as read?');">Mark All as Read</a>
    <?php endif; ?>

    <?php if (!empty($notifications)): ?>
        <?php foreach ($notifications as $n): ?>
            <div class="notif <?= $n['status'] === 'unread' ? 'unread' : '' ?>">
                <strong><?= htmlspecialchars($typeLabels[$n['type']] ?? ucfirst(str_replace('_',' ',$n['type']))); ?></strong><br>
                <?php
                    switch($n['type']){
                        case 'appointment_created':
                        case 'appointment_cancelled':
                        case 'appointment_rescheduled':
                            echo "Appointment ID: " . intval($n['related_id']);
                            break;
                        case 'user_created':
                        case 'user_deleted':
                            echo "User ID: " . intval($n['related_id']);
                            break;
                        default:
                            echo "You have a new notification.";
                    }
                ?><br>
                Sent at: <?= date('M d, Y H:i', strtotime($n['sent_at'])); ?><br>
                <a href="?mark_read=<?= $n['id']; ?>" class="mark-read">Mark as read</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No notifications.</p>
    <?php endif; ?>
</div>
</body>
</html>
