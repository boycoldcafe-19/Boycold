<?php
session_start();
require_once '../config/db_config.php';

header('Content-Type: application/json');

try {
    $branchId = (int) ($_GET['branch_id'] ?? 1);

    if ($branchId > 0) {
        $stmt = $connect->prepare(
            "SELECT b.id
             FROM branches b
             INNER JOIN shift_logs s ON s.branch_id = b.id AND s.status = 'open'
             WHERE b.id = ? AND b.status = 'active'
             LIMIT 1"
        );
        $stmt->bind_param('i', $branchId);
    } else {
        $stmt = $connect->prepare(
            "SELECT b.id
             FROM branches b
             INNER JOIN shift_logs s ON s.branch_id = b.id AND s.status = 'open'
             WHERE b.status = 'active'
             LIMIT 1"
        );
    }

    $stmt->execute();
    $isOpen = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'is_open' => $isOpen,
        'message' => $isOpen ? '' : 'Store is closed as of now. Please come back later.'
    ]);
} catch (Throwable $e) {
    error_log('store_status_api failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to check store availability.']);
}
