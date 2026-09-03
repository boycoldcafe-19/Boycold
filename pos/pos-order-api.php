<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Simple CORS support for local development where frontend may run on a different port.
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
    if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

require_once __DIR__ . '/auth/guard.php';
pos_start_session();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/shift_manager.php';

header('Content-Type: application/json');

function pos_json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function pos_normalize_order_type(string $orderType): string
{
    $normalized = strtolower(trim(str_replace('_', '-', $orderType)));
    $normalized = preg_replace('/\s+/', '-', $normalized);

    if ($normalized === 'dinein') {
        return 'dine-in';
    }
    if ($normalized === 'take-out') {
        return 'takeout';
    }
    if ($normalized === 'pick-up') {
        return 'pickup';
    }

    return in_array($normalized, ['dine-in', 'takeout', 'delivery', 'pickup'], true)
        ? $normalized
        : 'dine-in';
}

function pos_addons_to_text($addons): string
{
    if (empty($addons)) {
        return '';
    }

    if (!is_array($addons)) {
        return substr(trim((string) $addons), 0, 255);
    }

    $names = [];
    foreach ($addons as $addon) {
        if (is_array($addon)) {
            $name = trim((string) ($addon['value'] ?? $addon['name'] ?? ''));
        } else {
            $name = trim((string) $addon);
        }
        if ($name !== '') {
            $names[] = $name;
        }
    }

    return substr(implode(', ', $names), 0, 255);
}

function pos_table_exists(mysqli $connect, string $table): bool
{
    $safeTable = $connect->real_escape_string($table);
    $result = $connect->query("SHOW TABLES LIKE '{$safeTable}'");
    return $result && $result->num_rows > 0;
}

function pos_ensure_walk_in_customer(mysqli $connect): string
{
    $firstName = 'Walk-in';
    $lastName = 'Customer';
    $userName = $firstName . ' ' . $lastName;

    $stmt = $connect->prepare('SELECT user_name FROM users WHERE user_name = ? LIMIT 1');
    $stmt->bind_param('s', $userName);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        return $userName;
    }

    $email = 'walkin-' . bin2hex(random_bytes(6)) . '@boycold.local';
    $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    try {
        $insert = $connect->prepare(
            "INSERT INTO users
               (firstname, lastname, email, password, is_verified, phone, address)
             VALUES (?, ?, ?, ?, 1, '', '')"
        );
        $insert->bind_param('ssss', $firstName, $lastName, $email, $password);
        $insert->execute();
    } catch (mysqli_sql_exception $e) {
        // A concurrent request may have created it after the first lookup.
        $stmt = $connect->prepare('SELECT user_name FROM users WHERE user_name = ? LIMIT 1');
        $stmt->bind_param('s', $userName);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            return $userName;
        }
        throw $e;
    }

    return $userName;
}

function pos_format_order_no(int $orderId, string $orderType, string $createdAt): string
{
    $typeCodes = [
        'delivery' => 'DEL',
        'pickup' => 'PU',
        'dine-in' => 'DI',
        'takeout' => 'TO',
    ];
    $typeCode = $typeCodes[$orderType] ?? 'GEN';
    $date = new DateTime($createdAt);

    return sprintf('POS-%s-%s-%05d', $typeCode, $date->format('Y'), $orderId);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        pos_json_response(['success' => false, 'error' => 'Method not allowed'], 405);
    }

    $employee = pos_require_employee($connect, true);

    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!is_array($body)) {
        pos_json_response(['success' => false, 'error' => 'Invalid JSON payload'], 400);
    }
    if (empty($body['items']) || !is_array($body['items'])) {
        pos_json_response(['success' => false, 'error' => 'No order items provided'], 400);
    }

    $items = $body['items'];
    $orderType = pos_normalize_order_type((string) ($body['order_type'] ?? 'dine-in'));
    $notes = trim((string) ($body['notes'] ?? ''));
    $deliveryFee = max(0, (float) ($body['delivery_fee'] ?? 0));
    $tax = max(0, (float) ($body['tax'] ?? 0));
    $paymentMethod = strtolower(trim((string) ($body['payment_method'] ?? 'cod')));
    if (!in_array($paymentMethod, ['cod', 'qrph'], true)) {
        $paymentMethod = 'cod';
    }

    $paymentStatus = 'paid';
    $status = 'completed';
    $address = '';

    $subtotal = 0;
    foreach ($items as $item) {
        $unitPrice = max(0, (float) ($item['unitPrice'] ?? 0));
        $qty = max(1, (int) ($item['qty'] ?? 1));
        $subtotal += $unitPrice * $qty;
    }
    $total = $subtotal + $deliveryFee + $tax;

    // Reconcile the branch before writing so a late request cannot use a prior sales day.
    $branchId = (int) $employee['branch_id'];
    $cashierId = (int) $employee['id'];
    $currentShift = pos_reconcile_branch_shift($connect, $branchId, $cashierId);
    if (!$currentShift) {
        pos_json_response(['success' => false, 'error' => 'There is no open POS shift for this sales day.'], 409);
    }
    $shiftId = (int) $currentShift['id'];

    $connect->begin_transaction();

    $userName = pos_ensure_walk_in_customer($connect);

    $userId = null; // POS orders are typically walk-in customers

    $stmt = $connect->prepare(
        "INSERT INTO orders
           (user_name, user_id, status, order_type, payment_method, payment_status,
            subtotal, delivery_fee, tax, total, address, notes, branch_id, cashier_id, shift_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
    $stmt->bind_param(
        'sissssddddssiii',
        $userName,
        $userId,
        $status,
        $orderType,
        $paymentMethod,
        $paymentStatus,
        $subtotal,
        $deliveryFee,
        $tax,
        $total,
        $address,
        $notes,
        $branchId,
        $cashierId,
        $shiftId
    );
    $stmt->execute();
    $orderId = (int) $connect->insert_id;

    $itemStmt = $connect->prepare(
        "INSERT INTO order_items
           (order_id, product_name, product_image, unit_price, quantity,
            line_total, milk, addons, order_type, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $trackIngredients = pos_table_exists($connect, 'product_ingredients') && pos_table_exists($connect, 'order_ingredients');
    $piStmt = null;
    $oiStmt = null;
    if ($trackIngredients) {
        $piStmt = $connect->prepare('SELECT ingredient_id, amount FROM product_ingredients WHERE product_name = ?');
        $oiStmt = $connect->prepare('INSERT INTO order_ingredients (order_id, ingredient_id, amount) VALUES (?, ?, ?)');
    }

    foreach ($items as $item) {
        $name = substr(trim((string) ($item['name'] ?? 'Unknown Item')), 0, 150);
        if ($name === '') {
            $name = 'Unknown Item';
        }
        $image = substr(trim((string) ($item['image'] ?? '')), 0, 255);
        $unitPrice = max(0, (float) ($item['unitPrice'] ?? 0));
        $qty = max(1, (int) ($item['qty'] ?? 1));
        $lineTotal = $unitPrice * $qty;
        $milk = substr(trim((string) ($item['milk'] ?? '')), 0, 80);
        $addons = pos_addons_to_text($item['addons'] ?? '');
        $orderItemType = pos_normalize_order_type((string) ($item['orderType'] ?? $orderType));
        $itemNotes = trim((string) ($item['notes'] ?? ''));

        $itemStmt->bind_param(
            'issdidssss',
            $orderId,
            $name,
            $image,
            $unitPrice,
            $qty,
            $lineTotal,
            $milk,
            $addons,
            $orderItemType,
            $itemNotes
        );
        $itemStmt->execute();

        if ($piStmt && $oiStmt) {
            $piStmt->bind_param('s', $name);
            $piStmt->execute();
            $res = $piStmt->get_result();
            while ($prow = $res->fetch_assoc()) {
                $ingredientId = (int) $prow['ingredient_id'];
                $totalAmount = (float) $prow['amount'] * $qty;
                $oiStmt->bind_param('iid', $orderId, $ingredientId, $totalAmount);
                $oiStmt->execute();
            }
        }
    }

    $createdAt = date('Y-m-d H:i:s');
    $createdStmt = $connect->prepare('SELECT created_at FROM orders WHERE id = ?');
    $createdStmt->bind_param('i', $orderId);
    $createdStmt->execute();
    $createdRow = $createdStmt->get_result()->fetch_assoc();
    if (!empty($createdRow['created_at'])) {
        $createdAt = $createdRow['created_at'];
    }

    // Award loyalty stamps for POS orders (they're completed immediately)
    // Skip for walk-in customers
    if ($userName !== 'Walk-in Customer') {
        $userStmt = $connect->prepare("SELECT id, loyalty_beans, loyalty_stamps, card_no FROM users WHERE user_name = ?");
        $userStmt->bind_param("s", $userName);
        $userStmt->execute();
        $userInfo = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();

        if ($userInfo) {
            // Calculate previous balance (using direct stamp counting: 1 stamp = 10 points)
            $previousBalance = (int) $userInfo['loyalty_beans'] + ((int) $userInfo['loyalty_stamps'] * 10);

            // Update loyalty: increment stamps directly, keep beans at 0
            $loyaltyStmt = $connect->prepare(
                "UPDATE users
                 SET loyalty_beans = 0,
                     loyalty_stamps = loyalty_stamps + 1
                 WHERE user_name = ?"
            );
            $loyaltyStmt->bind_param("s", $userName);
            $loyaltyStmt->execute();
            $loyaltyStmt->close();

            // Get updated balance
            $refreshStmt = $connect->prepare("SELECT loyalty_beans, loyalty_stamps FROM users WHERE user_name = ?");
            $refreshStmt->bind_param("s", $userName);
            $refreshStmt->execute();
            $updated = $refreshStmt->get_result()->fetch_assoc();
            $refreshStmt->close();

            $newBalance = (int) ($updated['loyalty_beans'] ?? 0) + ((int) ($updated['loyalty_stamps'] ?? 0) * 10);

            // Get device_id from session
            $deviceId = isset($_SESSION['device_id']) ? (int) $_SESSION['device_id'] : 0;

            // Record transaction in loyalty_transactions table
            $transactionStmt = $connect->prepare(
                "INSERT INTO loyalty_transactions (user_id, card_no, branch_id, device_id, employee_id, transaction_type, points_awarded, previous_balance, new_balance, order_id)
                 VALUES (?, ?, ?, ?, ?, 'bean_award', 10, ?, ?, ?)"
            );
            $transactionStmt->bind_param('isiiiii', $userInfo['id'], $userInfo['card_no'], $branchId, $deviceId, $cashierId, $previousBalance, $newBalance, $orderId);
            $transactionStmt->execute();
            $transactionStmt->close();
        }
    }

    $connect->commit();

    pos_json_response([
        'success' => true,
        'order_id' => $orderId,
        'order_no' => pos_format_order_no($orderId, $orderType, $createdAt),
        'order_type' => $orderType,
        'created_at' => $createdAt,
        'total' => number_format($total, 2, '.', ''),
        'message' => 'POS order saved to history.',
    ]);
} catch (Throwable $e) {
    if (isset($connect) && $connect instanceof mysqli) {
        try {
            $connect->rollback();
        } catch (Throwable $rollbackError) {
            // Keep the original exception for the response.
        }
    }

    error_log('POS order save failed: ' . $e->getMessage());
    pos_json_response([
        'success' => false,
        'error' => 'Could not save POS order: ' . $e->getMessage(),
    ], 500);
}
