<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth/guard.php';
pos_start_session();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/inventory_service.php';
require_once __DIR__ . '/../config/menu_catalog_service.php';
require_once __DIR__ . '/../config/activity_logger.php';
require_once __DIR__ . '/../config/branch_authorization_pin.php';
require_once __DIR__ . '/../config/order_void_service.php';

header('Content-Type: application/json; charset=utf-8');

function pos_void_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function pos_void_require_pin(mysqli $connect, array $employee, mixed $submittedPin): void
{
    $pin = trim((string) $submittedPin);
    $verification = boycold_verify_branch_authorization_pin(
        $connect,
        (int) ($employee['branch_id'] ?? 0),
        $pin,
        (string) ($employee['branch_name'] ?? '')
    );

    if (!$verification['success']) {
        $statusCode = !empty($verification['locked']) ? 423 : 403;
        $error = (string) ($verification['error'] ?? 'Incorrect authorization PIN.');
        if (empty($verification['locked']) && isset($verification['remaining_attempts'])) {
            $remainingAttempts = max(0, (int) $verification['remaining_attempts']);
            $error .= ' ' . $remainingAttempts . ' attempt' . ($remainingAttempts === 1 ? '' : 's') . ' remaining.';
        }
        pos_void_response(['success' => false, 'error' => $error], $statusCode);
    }
}

function pos_void_order_number(array $order): string
{
    $typeCodes = ['dine-in' => 'DI', 'takeout' => 'TO', 'delivery' => 'DEL', 'pickup' => 'PU'];
    $type = strtolower((string) ($order['order_type'] ?? 'dine-in'));
    $typeCode = $typeCodes[$type] ?? 'GEN';
    $date = new DateTime((string) ($order['created_at'] ?? 'now'));

    return sprintf('POS-%s-%s-%05d', $typeCode, $date->format('Y'), (int) ($order['id'] ?? 0));
}

function pos_void_load_order(mysqli $connect, int $orderId, int $branchId, bool $forUpdate = false): ?array
{
    $lock = $forUpdate ? ' FOR UPDATE' : '';
    $statement = $connect->prepare(
        "SELECT id, user_id, user_name, status, order_type, payment_method,
                payment_status, payment_reference, subtotal, delivery_fee, tax,
                total, address, notes, branch_id, cashier_id, shift_id, voided_at, voided_by,
                void_replacement_for_order_id, created_at
         FROM orders
         WHERE id = ? AND branch_id = ?
         LIMIT 1{$lock}"
    );
    $statement->bind_param('ii', $orderId, $branchId);
    $statement->execute();
    $order = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!$order) {
        return null;
    }

    $itemsStatement = $connect->prepare(
        'SELECT id, product_name, product_image, quantity, unit_price, line_total, milk, addons, notes
         FROM order_items
         WHERE order_id = ?
         ORDER BY id ASC'
    );
    $itemsStatement->bind_param('i', $orderId);
    $itemsStatement->execute();
    $items = $itemsStatement->get_result()->fetch_all(MYSQLI_ASSOC);
    $itemsStatement->close();

    foreach (['subtotal', 'delivery_fee', 'tax', 'total'] as $amountField) {
        $order[$amountField] = (float) ($order[$amountField] ?? 0);
    }
    foreach ($items as &$item) {
        $item['id'] = (int) $item['id'];
        $item['quantity'] = max(1, (int) $item['quantity']);
        $item['unit_price'] = (float) $item['unit_price'];
        $item['line_total'] = (float) $item['line_total'];
    }
    unset($item);
    $order['items'] = $items;

    return $order;
}

function pos_void_menu_products(mysqli $connect): array
{
    boycold_ensure_product_addons_schema($connect);
    $result = $connect->query(
        'SELECT id, product_name, price, image, category, addons_configured, milk_choices_configured
         FROM products
         WHERE is_available = 1
         ORDER BY product_name ASC'
    );
    $products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $modifiers = boycold_menu_get_product_modifiers($connect, array_column($products, 'id'));

    foreach ($products as &$product) {
        $productId = (int) $product['id'];
        $product['id'] = $productId;
        $product['price'] = (float) $product['price'];
        $legacyModifiers = pos_void_legacy_modifiers($product);
        $product['addons'] = !empty($product['addons_configured'])
            ? ($modifiers[$productId]['addons'] ?? [])
            : $legacyModifiers['addons'];
        $product['milk_choices'] = !empty($product['milk_choices_configured'])
            ? ($modifiers[$productId]['milk_choices'] ?? [])
            : $legacyModifiers['milk_choices'];
    }
    unset($product);

    return $products;
}

/**
 * Orders made before per-product modifiers were configured use the same
 * legacy choices shown by POS Order Summary. Supplying them here lets an
 * adjustment reopen the choices saved on the receipt instead of presenting a
 * blank milk/add-on section.
 *
 * @return array{addons: array<int, array{name:string,price:float}>, milk_choices: array<int, array{name:string,price:float}>}
 */
function pos_void_legacy_modifiers(array $product): array
{
    $category = strtolower(trim((string) ($product['category'] ?? '')));
    $productName = strtolower((string) ($product['product_name'] ?? ''));
    $has = static fn (string $term): bool => str_contains($productName, $term);
    $noCustomizationCategories = ['rice-meal', 'light-snack', 'pasta', 'waffle', 'waffles', 'quesadilla'];

    if (in_array($category, $noCustomizationCategories, true)
        || ($category === 'snacks' && ($has('waffle') || $has('quesadilla') || $has('nachos') || $has('tuna')))) {
        return ['addons' => [], 'milk_choices' => []];
    }

    if ($category === 'snacks' && $has('poppers')) {
        return [
            'addons' => [['name' => 'Cheese Sauce', 'price' => 40.0]],
            'milk_choices' => [],
        ];
    }

    if ($category === 'snacks' && $has('fries')) {
        return [
            'addons' => [
                ['name' => 'Cheese Sauce', 'price' => 30.0],
                ['name' => 'Cheese Powder', 'price' => 30.0],
                ['name' => 'BBQ Powder', 'price' => 30.0],
                ['name' => 'Sour Cream Powder', 'price' => 30.0],
            ],
            'milk_choices' => [],
        ];
    }

    return [
        'addons' => [
            ['name' => 'Espresso Shot', 'price' => 15.0],
            ['name' => 'Whipped Cream', 'price' => 15.0],
            ['name' => 'Chocolate Drizzle', 'price' => 15.0],
        ],
        'milk_choices' => [
            ['name' => 'Original', 'price' => 0.0],
            ['name' => 'Oat Milk', 'price' => 15.0],
        ],
    ];
}

function pos_void_clean_modifier_names(mixed $value): array
{
    if (!is_array($value)) {
        return [];
    }

    $names = [];
    foreach ($value as $modifier) {
        $name = preg_replace('/\s+/', ' ', trim((string) $modifier));
        if ($name !== '' && mb_strlen($name) <= 100) {
            $names[] = $name;
        }
    }

    return array_values(array_unique($names));
}

function pos_void_match_modifier(string $name, array $available): ?array
{
    foreach ($available as $modifier) {
        if (strcasecmp((string) ($modifier['name'] ?? ''), $name) === 0) {
            return $modifier;
        }
    }

    return null;
}

function pos_void_normalize_adjusted_items(mysqli $connect, mixed $requestedItems): array
{
    if (!is_array($requestedItems) || !$requestedItems || count($requestedItems) > 50) {
        throw new RuntimeException('Provide between one and fifty adjusted order items.');
    }

    $products = pos_void_menu_products($connect);
    $productsById = [];
    foreach ($products as $product) {
        $productsById[(int) $product['id']] = $product;
    }

    $items = [];
    foreach ($requestedItems as $requested) {
        if (!is_array($requested)) {
            throw new RuntimeException('Each adjusted item is invalid.');
        }

        $productId = (int) ($requested['product_id'] ?? 0);
        $quantity = (int) ($requested['quantity'] ?? 0);
        $product = $productsById[$productId] ?? null;
        if (!$product || $quantity < 1 || $quantity > 99) {
            throw new RuntimeException('Choose an available menu item and valid quantity.');
        }

        $milkName = preg_replace('/\s+/', ' ', trim((string) ($requested['milk'] ?? '')));
        $milk = null;
        if ($milkName !== '') {
            $milk = pos_void_match_modifier($milkName, (array) ($product['milk_choices'] ?? []));
            if (!$milk) {
                throw new RuntimeException('One of the selected milk options is no longer available.');
            }
        }

        $selectedAddons = [];
        foreach (pos_void_clean_modifier_names($requested['addons'] ?? []) as $addonName) {
            $addon = pos_void_match_modifier($addonName, (array) ($product['addons'] ?? []));
            if (!$addon) {
                throw new RuntimeException('One of the selected add-ons is no longer available.');
            }
            $selectedAddons[] = $addon;
        }

        $unitPrice = (float) $product['price'] + (float) ($milk['price'] ?? 0);
        foreach ($selectedAddons as $addon) {
            $unitPrice += (float) ($addon['price'] ?? 0);
        }
        $unitPrice = round($unitPrice, 2);
        $lineTotal = round($unitPrice * $quantity, 2);
        $notes = substr(trim((string) ($requested['notes'] ?? '')), 0, 1000);

        $items[] = [
            'product_id' => $productId,
            'name' => (string) $product['product_name'],
            'image' => (string) ($product['image'] ?? ''),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'milk' => (string) ($milk['name'] ?? ''),
            'addons' => implode(', ', array_column($selectedAddons, 'name')),
            'notes' => $notes,
        ];
    }

    return $items;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        pos_void_response(['success' => false, 'error' => 'Method not allowed.'], 405);
    }

    $employee = pos_require_employee($connect, true);
    $branchId = (int) ($employee['branch_id'] ?? 0);
    $employeeId = (int) ($employee['id'] ?? 0);
    boycold_ensure_order_void_schema($connect);
    boycold_ensure_inventory_schema($connect);

    $payload = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        pos_void_response(['success' => false, 'error' => 'Invalid request data.'], 400);
    }

    $action = (string) ($payload['action'] ?? '');
    $orderId = (int) ($payload['order_id'] ?? 0);
    if ($orderId <= 0 || $branchId <= 0) {
        pos_void_response(['success' => false, 'error' => 'Invalid order request.'], 422);
    }
    pos_void_require_pin($connect, $employee, $payload['authorization_pin'] ?? '');

    if ($action === 'authorize') {
        $order = pos_void_load_order($connect, $orderId, $branchId);
        if (!$order) {
            pos_void_response(['success' => false, 'error' => 'Order not found.'], 404);
        }
        if (in_array(strtolower((string) $order['status']), ['cancelled'], true) || boycold_order_was_voided($order)) {
            pos_void_response(['success' => false, 'error' => 'This order was already voided.'], 409);
        }
        if (!in_array((string) $order['order_type'], ['dine-in', 'takeout'], true)) {
            pos_void_response(['success' => false, 'error' => 'Only physical POS orders can be adjusted here.'], 422);
        }

        pos_void_response([
            'success' => true,
            'order' => $order,
            'order_number' => pos_void_order_number($order),
            'products' => pos_void_menu_products($connect),
        ]);
    }

    if ($action !== 'void_adjust') {
        pos_void_response(['success' => false, 'error' => 'Unknown void action.'], 400);
    }

    $adjustedItems = pos_void_normalize_adjusted_items($connect, $payload['items'] ?? []);
    $connect->begin_transaction();
    try {
        $originalOrder = pos_void_load_order($connect, $orderId, $branchId, true);
        if (!$originalOrder) {
            throw new RuntimeException('Order not found.');
        }
        if (strtolower((string) $originalOrder['status']) === 'cancelled' || boycold_order_was_voided($originalOrder)) {
            throw new RuntimeException('This order was already voided.');
        }
        if (!in_array((string) $originalOrder['order_type'], ['dine-in', 'takeout'], true)) {
            throw new RuntimeException('Only physical POS orders can be adjusted here.');
        }

        $restore = boycold_restore_deducted_inventory_for_order_in_transaction(
            $connect,
            $orderId,
            'pos_void_restore',
            $employeeId
        );
        if (!$restore['success']) {
            throw new RuntimeException((string) ($restore['error'] ?? 'Unable to restore the original order inventory.'));
        }

        $voidOriginal = $connect->prepare(
            "UPDATE orders
             SET status = 'cancelled', payment_status = 'cancelled',
                 voided_at = NOW(), voided_by = ?
             WHERE id = ? AND branch_id = ? AND status <> 'cancelled' AND voided_at IS NULL"
        );
        $voidOriginal->bind_param('iii', $employeeId, $orderId, $branchId);
        $voidOriginal->execute();
        if ($voidOriginal->affected_rows !== 1) {
            throw new RuntimeException('The original order could not be voided.');
        }
        $voidOriginal->close();

        $shiftStmt = $connect->prepare(
            "SELECT id FROM shift_logs WHERE branch_id = ? AND status = 'open' ORDER BY opened_at DESC LIMIT 1 FOR UPDATE"
        );
        $shiftStmt->bind_param('i', $branchId);
        $shiftStmt->execute();
        $shift = $shiftStmt->get_result()->fetch_assoc();
        $shiftStmt->close();
        if (!$shift) {
            throw new RuntimeException('An open shift is required to save the adjusted order.');
        }

        $subtotal = round(array_sum(array_column($adjustedItems, 'line_total')), 2);
        $deliveryFee = max(0, (float) $originalOrder['delivery_fee']);
        $tax = max(0, (float) $originalOrder['tax']);
        $total = round($subtotal + $deliveryFee + $tax, 2);
        $status = 'completed';
        $paymentStatus = 'paid';
        $orderType = (string) $originalOrder['order_type'];
        $paymentMethod = (string) $originalOrder['payment_method'];
        $paymentReference = $originalOrder['payment_reference'];
        $userName = (string) $originalOrder['user_name'];
        $userId = $originalOrder['user_id'] !== null ? (int) $originalOrder['user_id'] : null;
        $address = (string) ($originalOrder['address'] ?? '');
        $notes = (string) ($originalOrder['notes'] ?? '');
        $shiftId = (int) $shift['id'];

        $createOrder = $connect->prepare(
            "INSERT INTO orders
                (user_name, user_id, status, order_type, payment_method, payment_status,
                 payment_reference, subtotal, delivery_fee, tax, total, address, notes,
                 branch_id, cashier_id, shift_id, void_replacement_for_order_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $createOrder->bind_param(
            'sisssssddddssiiii',
            $userName,
            $userId,
            $status,
            $orderType,
            $paymentMethod,
            $paymentStatus,
            $paymentReference,
            $subtotal,
            $deliveryFee,
            $tax,
            $total,
            $address,
            $notes,
            $branchId,
            $employeeId,
            $shiftId,
            $orderId
        );
        $createOrder->execute();
        $newOrderId = (int) $connect->insert_id;
        $createOrder->close();

        $createItem = $connect->prepare(
            "INSERT INTO order_items
                (order_id, product_name, product_image, unit_price, quantity, line_total, milk, addons, order_type, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        foreach ($adjustedItems as $item) {
            $name = $item['name'];
            $image = $item['image'];
            $unitPrice = $item['unit_price'];
            $quantity = $item['quantity'];
            $lineTotal = $item['line_total'];
            $milk = $item['milk'];
            $addons = $item['addons'];
            $itemNotes = $item['notes'];
            $createItem->bind_param(
                'issdidssss',
                $newOrderId,
                $name,
                $image,
                $unitPrice,
                $quantity,
                $lineTotal,
                $milk,
                $addons,
                $orderType,
                $itemNotes
            );
            $createItem->execute();
        }
        $createItem->close();

        $deduction = boycold_deduct_inventory_for_order_in_transaction($connect, $newOrderId, 'pos', $employeeId);
        if (!$deduction['success']) {
            throw new RuntimeException((string) ($deduction['error'] ?? 'Unable to deduct inventory for the adjusted order.'));
        }

        $connect->commit();
    } catch (Throwable $error) {
        $connect->rollback();
        throw $error;
    }

    boycold_log_activity($connect, [
        'category' => 'orders',
        'action' => 'order_voided_adjusted',
        'summary' => 'Void Order',
        'details' => 'Order #' . $orderId . ' was voided. Replacement order #' . $newOrderId . ' was created.',
        'actor_id' => $employeeId,
        'actor_type' => 'employee',
        'branch_id' => $branchId,
        'entity_type' => 'order',
        'entity_id' => $newOrderId,
        'metadata' => [
            'voided_order_id' => $orderId,
            'replacement_order_id' => $newOrderId,
            'original_total' => (float) $originalOrder['total'],
            'adjusted_total' => $total,
            'difference' => round($total - (float) $originalOrder['total'], 2),
        ],
    ]);

    $newOrder = pos_void_load_order($connect, $newOrderId, $branchId);
    pos_void_response([
        'success' => true,
        'message' => 'Order voided and adjusted successfully.',
        'voided_order_id' => $orderId,
        'replacement_order' => $newOrder,
        'replacement_order_number' => $newOrder ? pos_void_order_number($newOrder) : null,
    ]);
} catch (Throwable $error) {
    error_log('[POS void order] ' . $error->getMessage());
    pos_void_response(['success' => false, 'error' => $error instanceof RuntimeException ? $error->getMessage() : 'Unable to save the void adjustment.'], 500);
}
