<?php
require_once __DIR__ . '/auth/guard.php';
pos_start_session();
require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json');

$employee = pos_require_employee($connect, true);
$branchId = (int) $employee['branch_id'];

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

if (isset($_GET['action']) && $_GET['action'] === 'notifications') {
    $notificationStmt = $connect->prepare(
        "SELECT id, status, created_at
         FROM orders
         WHERE $onlineOrderTypeSql
           AND status IN ('pending', 'confirmed')
           AND branch_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT 10"
    );
    $notificationStmt->bind_param('i', $branchId);
    $notificationStmt->execute();
    $notifications = $notificationStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $notificationStmt->close();

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
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
