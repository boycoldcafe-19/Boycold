<?php

session_name('POS_SESSION');
session_start();
require_once '../../config/db_config.php';
require_once '../../config/shift_manager.php';

header('Content-Type: application/json');

if (empty($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$employeeId = (int) $_SESSION['employee_id'];
$stmt = $connect->prepare('SELECT branch_id, is_active FROM employees WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $employeeId);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$employee || (int) $employee['is_active'] === 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $branchId = (int) $employee['branch_id'];
    $shift = pos_reconcile_branch_shift($connect, $branchId, $employeeId);
    echo json_encode([
        'success' => true,
        'sales_date' => pos_sales_date(),
        'shift' => $shift ? [
            'id' => (int) $shift['id'],
            'shift_date' => $shift['shift_date'],
            'opened_at' => $shift['opened_at'],
            'status' => $shift['status'],
        ] : null,
    ]);
} catch (Throwable $e) {
    error_log('shift-status failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to check shift status']);
}
