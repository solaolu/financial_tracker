<?php
// Logs out the user.

session_start();
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session
header('Location: index.php'); // Redirect to login page
exit();
?>