<?php
session_start();
include 'connect.php'; // your DB connection

$userRole = $_SESSION['role'] ?? 'patient';
$userId   = $_SESSION['user_id'] ?? 2;

// Handle AJAX request to fetch notifications
if(isset($_GET['action']) && $_GET['action'] === 'fetch'){
    if ($userRole === 'admin') {
        $sql = "SELECT * FROM notifications ORDER BY sent_at DESC LIMIT 10";
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "SELECT * FROM notifications WHERE recipient_id=? AND recipient_role=? ORDER BY sent_at DESC LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $userId, $userRole);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode($notifications);
    exit;
}

// Handle AJAX request to mark as read
if(isset($_POST['action']) && $_POST['action'] === 'mark_read'){
    $id = intval($_POST['id'] ?? 0);
    if($id > 0){
        $stmt = $conn->prepare("UPDATE notifications SET status='read' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Notifications</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f9f9f9;
    margin: 0;
    padding: 0;
}
.container {
    max-width: 600px;
    margin: 50px auto;
    text-align: center;
}
h1 {
    margin-bottom: 30px;
    color: #333;
}
#notif-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
    align-items: center;
}
.notif {
    background: #fff;
    border: 1px solid #ccc;
    padding: 15px 20px;
    border-radius: 8px;
    width: 100%;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.2s;
}
.notif:hover {
    transform: translateY(-2px);
}
.notif.unread {
    border-left: 4px solid #007bff;
    font-weight: bold;
}
small {
    color: #666;
}
.btn-go-back {
    padding: 8px 15px;
    background: #555;
    color: white;
    border: none;
    border-radius: 5px;
    margin-bottom: 20px;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-go-back:hover {
    background: #333;
}

</style>
</head>
<body>

<div class="container">
    <h1>Notifications</h1>
    <div id="notif-container"></div>
</div>

<script>
function fetchNotifications(){
    fetch('notifications.php?action=fetch')
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById('notif-container');
        container.innerHTML = '';
        data.forEach(n => {
            const div = document.createElement('div');
            div.className = 'notif ' + (n.status === 'unread' ? 'unread' : '');
            div.innerHTML = `<strong>${n.message}</strong><br><small>${n.sent_at}</small>`;
            div.onclick = () => markRead(n.id, div);
            container.appendChild(div);
        });
    });
}

function markRead(id, element){
    fetch('notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=mark_read&id=' + id
    })
    .then(res => res.json())
    .then(resp => {
        if(resp.success){
            element.classList.remove('unread');
        }
    });
}

// Fetch notifications every 5 seconds
setInterval(fetchNotifications, 5000);
fetchNotifications();
</script>
<div class="container">
    <button onclick="window.history.back()" class="btn-go-back">Go Back</button>
    <div id="notif-container"></div>
</div>


</body>
</html>
