<?php
require_once __DIR__ . '/auth/guard.php';
pos_start_session();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/payments.php';

boycold_ensure_payment_schema($connect);

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

if (isset($_GET['action']) && $_GET['action'] === 'payment_status') {
    $orderId = (int) ($_GET['order_id'] ?? 0);
    $statusStmt = $connect->prepare(
        "SELECT id, payment_method, payment_status, status, payment_expires_at
         FROM orders
         WHERE id = ? AND branch_id = ?
         LIMIT 1"
    );
    $statusStmt->bind_param('ii', $orderId, $branchId);
    $statusStmt->execute();
    $paymentOrder = $statusStmt->get_result()->fetch_assoc();
    $statusStmt->close();

    if (!$paymentOrder) {
        echo json_encode(['success' => false, 'error' => 'Order not found.']);
        exit;
    }

    if ($paymentOrder['payment_method'] === 'qrph'
        && $paymentOrder['payment_status'] === 'pending'
        && !empty($paymentOrder['payment_expires_at'])
        && strtotime($paymentOrder['payment_expires_at']) <= time()) {
        $expireStmt = $connect->prepare(
            "UPDATE orders SET payment_status = 'expired', status = IF(status = 'pending', 'cancelled', status)
             WHERE id = ? AND branch_id = ? AND payment_status = 'pending'"
        );
        $expireStmt->bind_param('ii', $orderId, $branchId);
        $expireStmt->execute();
        $expireStmt->close();
        $paymentOrder['payment_status'] = 'expired';
        if ($paymentOrder['status'] === 'pending') $paymentOrder['status'] = 'cancelled';
    }

    echo json_encode([
        'success' => true,
        'payment_method' => $paymentOrder['payment_method'],
        'payment_status' => $paymentOrder['payment_status'],
        'order_status' => $paymentOrder['status'],
        'payment_expires_at' => $paymentOrder['payment_expires_at'],
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
