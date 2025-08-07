<?php
session_start();
session_unset();   // Clear all session variables
session_destroy(); // Destroy the session

// Redirect to index page instead of login
header("Location: index.php");
exit;
?>
