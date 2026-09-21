<?php
require_once __DIR__ . '/guard.php';
pos_start_session();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../../config/activity_logger.php';

$employeeId = (int) ($_SESSION['employee_id'] ?? 0);
if ($employeeId > 0) {
    $employeeStmt = $connect->prepare(
        "SELECT id, employee_name, branch_id
         FROM employees
         WHERE id = ? AND role = 'cashier'
         LIMIT 1"
    );
    $employeeStmt->bind_param('i', $employeeId);
    $employeeStmt->execute();
    $employee = $employeeStmt->get_result()->fetch_assoc();
    $employeeStmt->close();

    if ($employee) {
        boycold_log_activity($connect, [
            'category' => 'login',
            'action' => 'logout',
            'summary' => 'Log Out',
            'details' => 'POS employee logged out from the system.',
            'actor_id' => (int) $employee['id'],
            'actor_type' => 'employee',
            'branch_id' => (int) ($employee['branch_id'] ?? 0),
            'entity_type' => 'employee',
            'entity_id' => (int) $employee['id'],
        ]);
    }
}

pos_clear_session();

// Redirect to flash screen
header('Location: ../../User/login.php');
exit;
?>
