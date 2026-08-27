<?php
session_name('POS_SESSION');
session_start();

// Clear all session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to flash screen
header('Location: flashscreen.php');
exit;
?>
