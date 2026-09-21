<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth/guard.php';
pos_start_session();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/order_void_service.php';

header('Content-Type: application/json; charset=utf-8');

function pos_history_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        pos_history_response(['success' => false, 'error' => 'Method not allowed.'], 405);
    }

    // pos_require_employee also validates the active POS session and returns
    // the cashier's branch. That branch is used below to prevent a POS from
    // opening a receipt that belongs to another branch.
    $employee = pos_require_employee($connect, true);
    $branchId = (int) ($employee['branch_id'] ?? 0);
    boycold_ensure_order_void_schema($connect);
    $orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

    if (!$orderId || $orderId <= 0 || $branchId <= 0) {
        pos_history_response(['success' => false, 'error' => 'Invalid order request.'], 422);
    }

    $orderStmt = $connect->prepare(
        "SELECT o.id, o.user_name, o.status, o.voided_at, o.order_type, o.payment_method,
                o.payment_status, o.payment_reference, o.subtotal, o.delivery_fee,
                o.tax, o.total, o.address, o.notes, o.created_at,
                COALESCE(NULLIF(CONCAT_WS(' ', u.firstname, u.lastname), ''), o.user_name, 'Guest') AS customer_name,
                COALESCE(u.phone, '') AS customer_phone,
                COALESCE(NULLIF(e.employee_name, ''), 'Online Order') AS cashier_name,
                COALESCE(NULLIF(b.branch_name, ''), 'Branch') AS branch_name
         FROM orders o
         LEFT JOIN users u
           ON (u.id = o.user_id OR (o.user_id IS NULL AND u.user_name = o.user_name))
         LEFT JOIN employees e ON e.id = o.cashier_id
         LEFT JOIN branches b ON b.id = o.branch_id
         WHERE o.id = ? AND o.branch_id = ?
         LIMIT 1"
    );
    $orderStmt->bind_param('ii', $orderId, $branchId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();

    if (!$order) {
        pos_history_response(['success' => false, 'error' => 'Order not found.'], 404);
    }

    $itemsStmt = $connect->prepare(
        'SELECT product_name, quantity, unit_price, line_total, milk, addons, notes
         FROM order_items
         WHERE order_id = ?
         ORDER BY id ASC'
    );
    $itemsStmt->bind_param('i', $orderId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = [
            'name' => (string) ($item['product_name'] ?? 'Item'),
            'qty' => max(1, (int) ($item['quantity'] ?? 1)),
            'price' => (float) ($item['unit_price'] ?? 0),
            'line_total' => (float) ($item['line_total'] ?? 0),
            'milk' => trim((string) ($item['milk'] ?? '')),
            'addons' => trim((string) ($item['addons'] ?? '')),
            'notes' => trim((string) ($item['notes'] ?? '')),
        ];
    }
    $itemsStmt->close();

    $order['id'] = (int) $order['id'];
    foreach (['subtotal', 'delivery_fee', 'tax', 'total'] as $amountField) {
        $order[$amountField] = (float) ($order[$amountField] ?? 0);
    }
    $order['items'] = $items;

    pos_history_response(['success' => true, 'order' => $order]);
} catch (Throwable $error) {
    error_log('[POS history receipt] ' . $error->getMessage());
    pos_history_response(['success' => false, 'error' => 'Unable to load this order receipt.'], 500);
}
