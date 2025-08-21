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

// Fetch notifications
$stmt = $conn->prepare("
    SELECT n.id, n.type, n.status, n.sent_at, n.related_id
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

// Unread count
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: #f4f7fa;
    min-height: 100vh;
    padding: 30px 20px;
}
.container { max-width: 800px; margin: auto; }
h1 { color: #003366; font-weight: 700; margin-bottom: 2rem; text-align: center; }
.notification-item {
    background: white;
    border-radius: 8px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    user-select: none;
    cursor: pointer;
}
.notification-item.unread { background-color: #e7f3ff; }
.notification-content { max-width: 80%; }
.notification-content strong { display: block; }
.notification-time { font-size: 0.85rem; color: #888; margin-top: 4px; }
.btn-action { background: none; border: none; color: #00509e; cursor: pointer; font-size: 1.2rem; margin-left: 0.75rem; transition: color 0.3s ease; }
.btn-action:hover { color: #003366; }
.btn-mark-all { display: block; text-align: center; margin: 0 auto 2rem auto; padding: 10px 20px; background-color: #007BFF; color: white; border-radius: 5px; text-decoration: none; font-weight: bold; }
.btn-mark-all:hover { background-color: #0056b3; }
</style>
</head>
<body>

<div class="container">
    <h1>Notifications</h1>

    <?php if($unreadCount > 0): ?>
        <a href="?mark_all_read=1" class="btn-mark-all" onclick="return confirm('Mark all notifications as read?');">Mark All as Read</a>
    <?php endif; ?>

    <?php if(!empty($notifications)): ?>
        <?php foreach($notifications as $n): 
            // Generate actual message for popup
            $popupMessage = '';
            switch($n['type']){
                case 'appointment_created':
                    $popupMessage = "Your appointment (#" . intval($n['related_id']) . ") has been created.";
                    break;
                case 'appointment_cancelled':
                    $popupMessage = "Your appointment (#" . intval($n['related_id']) . ") has been cancelled.";
                    break;
                case 'appointment_rescheduled':
                    $popupMessage = "Your appointment (#" . intval($n['related_id']) . ") has been rescheduled.";
                    break;
                case 'user_created':
                    $popupMessage = "A new user (#" . intval($n['related_id']) . ") was added.";
                    break;
                case 'user_deleted':
                    $popupMessage = "A user (#" . intval($n['related_id']) . ") was deleted.";
                    break;
                default:
                    $popupMessage = "You have a new notification.";
            }
        ?>
            <div class="notification-item <?= $n['status'] === 'unread' ? 'unread' : '' ?>" 
                 data-message="<?= htmlspecialchars($popupMessage) ?>">
                <div class="notification-content">
                    <strong><?= htmlspecialchars($typeLabels[$n['type']] ?? ucfirst(str_replace('_',' ',$n['type']))); ?>
                        <?php
                        switch($n['type']){
                            case 'appointment_created':
                            case 'appointment_cancelled':
                            case 'appointment_rescheduled':
                                echo " | Appointment ID: " . intval($n['related_id']);
                                break;
                            case 'user_created':
                            case 'user_deleted':
                                echo " | User ID: " . intval($n['related_id']);
                                break;
                        }
                        ?>
                    </strong>
                    <div class="notification-time"><?= date('M d, Y H:i', strtotime($n['sent_at'])); ?></div>
                </div>
                <div>
                    <a href="?mark_read=<?= $n['id'] ?>" class="btn-action" title="Mark as read"><i class="fas fa-check-circle"></i></a>
                    <a href="?delete=<?= $n['id'] ?>" class="btn-action" title="Delete"><i class="fas fa-trash-alt"></i></a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align:center; font-weight:bold;">No notifications.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Show popup with actual message when a notification is clicked
document.querySelectorAll('.notification-item').forEach(item => {
    item.addEventListener('click', function(e){
        // Prevent triggering on action buttons
        if(e.target.closest('.btn-action')) return;
        let message = this.getAttribute('data-message');
        alert(message); // Or replace with a nicer toast
    });
});
</script>

</body>
</html>
