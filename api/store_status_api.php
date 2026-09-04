<?php
session_start();
require_once '../config/db_config.php';
require_once '../config/shift_manager.php';

header('Content-Type: application/json');

try {
    $branchId = (int) ($_GET['branch_id'] ?? 1);

    $status = boycold_get_branch_order_status($connect, $branchId);
    $isOpen = $status['exists'] && $status['is_open'];

    echo json_encode([
        'success' => true,
        'is_open' => $isOpen,
        'branch_id' => $branchId,
        'branch_name' => $status['branch_name'],
        'message' => $isOpen
            ? $status['branch_name'] . ' is currently accepting orders.'
            : ($status['exists']
                ? 'Online orders are currently unavailable for ' . $status['branch_name'] . ' because the branch is closed.'
                : 'The selected branch is not available.')
    ]);
} catch (Throwable $e) {
    error_log('store_status_api failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to check store availability.']);
}
