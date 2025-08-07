<?php
session_start();
require 'connect.php';

// Check if the ID is provided via GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $userId = (int) $_GET['id'];

    // Prepare and execute the delete query
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        // Success: redirect back with optional success message
        header("Location: manage_users.php?msg=deleted");
        exit();
    } else {
        // Deletion failed
        echo "Error deleting user.";
    }

    $stmt->close();
} else {
    // Invalid access (missing or wrong ID)
    header("Location: manage_users.php");
    exit();
}

$conn->close();
?>
