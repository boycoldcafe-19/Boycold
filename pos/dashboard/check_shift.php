<?php
require_once '../auth/guard.php';
pos_start_session();
require_once '../config/db_config.php';

$employee = pos_require_employee($connect);
$employeeId = (int) $employee['id'];
$branchId = (int) $employee['branch_id'];
$deviceId = isset($_SESSION['device_id']) ? (int) $_SESSION['device_id'] : 0;

// Check for active shift
$shiftStmt = $connect->prepare("SELECT id FROM shift_logs WHERE branch_id = ? AND status = 'open' LIMIT 1");
$shiftStmt->bind_param('i', $branchId);
$shiftStmt->execute();
$shiftResult = $shiftStmt->get_result()->fetch_assoc();
$shiftStmt->close();

// If no open shift, redirect to shift page
if (!$shiftResult) {
    header('Location: pos-shift.php');
    exit;
}
?>
