<?php
session_start();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/inventory_service.php';

header('Content-Type: application/json; charset=utf-8');

try {
    boycold_ensure_inventory_schema($connect);

    $branchId = isset($_GET['branch_id']) && (int) $_GET['branch_id'] > 0
        ? (int) $_GET['branch_id']
        : (int) ($_SESSION['branch_id'] ?? 1);

    echo json_encode([
        'success' => true,
        'branch_id' => $branchId,
        'availability' => boycold_get_product_inventory_availability($connect, $branchId),
    ]);
} catch (Throwable $e) {
    error_log('Inventory availability API failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Inventory availability could not be loaded.']);
}
