<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect them to the login page
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    // Append a message to the URL for clarity (optional)
    header("location: login.php?reason=unauthorized");
    exit;
}
?>
