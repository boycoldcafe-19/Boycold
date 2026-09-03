<?php
require_once __DIR__ . '/guard.php';
pos_start_session();
pos_clear_session();

// Redirect to flash screen
header('Location: flashscreen.php');
exit;
?>
