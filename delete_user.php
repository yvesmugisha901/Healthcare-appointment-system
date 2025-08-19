<?php
session_start();
require 'connect.php';

// Check if the ID is provided via GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $userId = (int) $_GET['id'];

    // Fetch the user's role and name before deletion (optional)
    $stmtFetch = $conn->prepare("SELECT name, role FROM users WHERE id = ?");
    $stmtFetch->bind_param("i", $userId);
    $stmtFetch->execute();
    $stmtFetch->bind_result($userName, $userRole);
    if (!$stmtFetch->fetch()) {
        $stmtFetch->close();
        header("Location: manage_users.php?msg=notfound");
        exit();
    }
    $stmtFetch->close();

    // Delete the user
    $stmtDel = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmtDel->bind_param("i", $userId);

    if ($stmtDel->execute()) {
        // Insert notification for admin(s)
        $notifStmt = $conn->prepare("
            INSERT INTO notifications
            (appointment_id, type, sent_at, status, recipient_role, related_table, related_id)
            VALUES (NULL, ?, NOW(), 'unread', 'admin', 'users', ?)
        ");
        $type = 'user_deleted';
        $notifStmt->bind_param("si", $type, $userId);
        $notifStmt->execute();
        $notifStmt->close();

        // Success: redirect back with success message
        header("Location: manage_users.php?msg=deleted");
        exit();
    } else {
        echo "Error deleting user: " . $stmtDel->error;
    }

    $stmtDel->close();
} else {
    // Invalid access (missing or wrong ID)
    header("Location: manage_users.php");
    exit();
}

$conn->close();
?>
