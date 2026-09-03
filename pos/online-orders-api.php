<?php
session_name('POS_SESSION');
session_start();
require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json');

if (
    empty($_SESSION['employee_id']) &&
    empty($_SESSION['employee_name']) &&
    empty($_SESSION['employee_email']) &&
    (empty($_SESSION['user_id']) || empty($_SESSION['user_email']))
) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get branch_id from session - POS employees are assigned to specific branches
$branchId = isset($_SESSION['branch_id']) ? (int) $_SESSION['branch_id'] : 0;

if ($branchId <= 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No branch assigned']);
    exit;
}

$lastOrderId = isset($_GET['last_order_id']) ? (int) $_GET['last_order_id'] : 0;

if ($lastOrderId < 0) {
    $lastOrderId = 0;
}

$onlineOrderTypeSql = "
     (
       order_type IN ('delivery', 'pickup')
       OR (order_type = 'takeout' AND user_id IS NOT NULL)
     )";

$countStmt = $connect->prepare(
    "SELECT COUNT(*) AS pending_count
     FROM orders
     WHERE $onlineOrderTypeSql
       AND status = 'pending'
       AND branch_id = ?"
);
$countStmt->bind_param('i', $branchId);
$countStmt->execute();
$pendingCount = (int) ($countStmt->get_result()->fetch_assoc()['pending_count'] ?? 0);
$countStmt->close();

if (isset($_GET['action']) && $_GET['action'] === 'counts') {
    echo json_encode([
        'success' => true,
        'pending_count' => $pendingCount
    ]);
    exit;
}

$stmt = $connect->prepare(
    "SELECT id, user_name, status, payment_method, payment_status, order_type, subtotal, delivery_fee, tax, total, address, created_at
     FROM orders
     WHERE $onlineOrderTypeSql
       AND status IN ('pending', 'confirmed')
       AND id > ?
       AND branch_id = ?
     ORDER BY id ASC"
);
$stmt->bind_param('ii', $lastOrderId, $branchId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$latestOrderId = 0;
foreach ($orders as $order) {
    if ((int) $order['id'] > $latestOrderId) {
        $latestOrderId = (int) $order['id'];
    }
}

echo json_encode([
    'success' => true,
    'orders' => $orders,
    'pending_count' => $pendingCount,
    'latest_order_id' => $latestOrderId,
    'debug' => [
        'branch_id' => $branchId,
        'last_order_id' => $lastOrderId,
        'orders_count' => count($orders)
    ]
]);
