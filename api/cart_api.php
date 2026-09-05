<?php
// ── cart_api.php ─────────────────────────────────────────────
// Handles all cart operations for the logged-in user only.
// Every read/write is scoped to $_SESSION['user_name'] — the user
// name is NEVER taken from POST/GET input.
//
// Actions (POST JSON body  { "action": "...", ... }):
//   get       → return all cart items for this user
//   add       → insert or increment a cart row
//   update    → change quantity for a specific cart row
//   remove    → delete a specific cart row
//   clear     → delete all rows for this user
//
// Response: always JSON  { success: bool, ... }
// ─────────────────────────────────────────────────────────────
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
require_once '../config/db_config.php';
require_once '../config/inventory_service.php';

header('Content-Type: application/json');
boycold_ensure_inventory_schema($connect);

// ── Auth guard ────────────────────────────────────────────────
if (!isset($_SESSION['user_name'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

// Always use session — never trust user input for the user name
$userName = $_SESSION['user_name'];

// ── Parse request ─────────────────────────────────────────────
$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = $body['action'] ?? ($_GET['action'] ?? '');

// ── Route ────────────────────────────────────────────────────
switch ($action) {

    // ── GET: return all cart items for this user ──────────────
    case 'get':
        // cart now stores product_name instead of product_id
        $stmt = $connect->prepare(
            "SELECT c.id, c.product_name, c.quantity, c.milk, c.addons,
                    c.order_type, c.notes,
                    p.price, p.image
             FROM   cart c
             JOIN   products p ON p.product_name = c.product_name
             WHERE  c.user_name = ?
             ORDER  BY c.created_at ASC"
        );
        $stmt->bind_param("s", $userName);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Shape into the same format addtocart.php / cart.php expects
        $items = array_map(function($r) {
            $unitPrice = (float) $r['price'];
            $qty       = (int)   $r['quantity'];
            return [
                'cartId'      => (int) $r['id'],
                'productName' => $r['product_name'],
                'name'        => $r['product_name'],
                'unitPrice'   => $unitPrice,
                'qty'         => $qty,
                'total'       => $unitPrice * $qty,
                'image'       => $r['image'] ?? '',
                'milk'        => $r['milk']       ?? '',
                'addons'      => $r['addons']     ?? '',
                'orderType'   => $r['order_type'] ?? '',
                'notes'       => $r['notes']      ?? '',
            ];
        }, $rows);

        echo json_encode(['success' => true, 'items' => $items]);
        break;

    // ── ADD: insert or increment ──────────────────────────────
    case 'add':
        $productName = isset($body['product_name']) ? trim($body['product_name']) : '';
        $qty         = isset($body['quantity'])     ? max(1, (int) $body['quantity']) : 1;
        $milk        = substr(trim($body['milk']       ?? ''), 0, 80);
        $addons      = substr(trim($body['addons']     ?? ''), 0, 255);
        $orderType   = substr(trim($body['order_type'] ?? ''), 0, 40);
        $notes       = trim($body['notes'] ?? '');
        $branchId    = isset($body['branch_id']) && (int) $body['branch_id'] > 0
            ? (int) $body['branch_id']
            : (int) ($_SESSION['branch_id'] ?? 1);

        if (empty($productName)) {
            echo json_encode(['success' => false, 'error' => 'Invalid product_name.']);
            break;
        }

        // INSERT … ON DUPLICATE KEY UPDATE increments quantity.
        // The UNIQUE KEY is (user_name, product_name) in the schema.
        $inventoryItems = cartApiInventoryItemsAfterAdd($connect, $userName, [
            'name' => $productName,
            'qty' => $qty,
            'milk' => $milk,
            'addons' => $addons,
        ]);
        $inventoryCheck = boycold_validate_inventory_for_items($connect, $inventoryItems, $branchId, false, true);
        if (!$inventoryCheck['success']) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => $inventoryCheck['error'], 'inventory' => $inventoryCheck]);
            break;
        }

        $stmt = $connect->prepare(
            "INSERT INTO cart (user_name, product_name, quantity, milk, addons, order_type, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               quantity   = quantity + VALUES(quantity),
               milk       = VALUES(milk),
               addons     = VALUES(addons),
               order_type = VALUES(order_type),
               notes      = VALUES(notes)"
        );
        $stmt->bind_param("ssissss", $userName, $productName, $qty, $milk, $addons, $orderType, $notes);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Item added to cart.']);
        break;

    // ── UPDATE: set exact quantity for a cart row ─────────────
    case 'update':
        $cartId = isset($body['cart_id'])  ? (int) $body['cart_id']  : 0;
        $qty    = isset($body['quantity']) ? max(1, (int) $body['quantity']) : 1;
        $branchId = isset($body['branch_id']) && (int) $body['branch_id'] > 0
            ? (int) $body['branch_id']
            : (int) ($_SESSION['branch_id'] ?? 1);

        if ($cartId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid cart_id.']);
            break;
        }

        $inventoryItems = cartApiInventoryItemsAfterUpdate($connect, $userName, $cartId, $qty);
        $inventoryCheck = boycold_validate_inventory_for_items($connect, $inventoryItems, $branchId, false, true);
        if (!$inventoryCheck['success']) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => $inventoryCheck['error'], 'inventory' => $inventoryCheck]);
            break;
        }

        // WHERE user_name = $userName prevents editing another user's row
        $stmt = $connect->prepare(
            "UPDATE cart SET quantity = ? WHERE id = ? AND user_name = ?"
        );
        $stmt->bind_param("iis", $qty, $cartId, $userName);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Cart updated.']);
        break;

    // ── REMOVE: delete one row ────────────────────────────────
    case 'remove':
        $cartId = isset($body['cart_id']) ? (int) $body['cart_id'] : 0;

        if ($cartId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid cart_id.']);
            break;
        }

        // WHERE user_name = $userName prevents deleting another user's row
        $stmt = $connect->prepare(
            "DELETE FROM cart WHERE id = ? AND user_name = ?"
        );
        $stmt->bind_param("is", $cartId, $userName);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Item removed.']);
        break;

    // ── CLEAR: empty the entire cart for this user ────────────
    case 'clear':
        $stmt = $connect->prepare("DELETE FROM cart WHERE user_name = ?");
        $stmt->bind_param("s", $userName);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Cart cleared.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}

function cartApiInventoryItems(mysqli $connect, string $userName): array
{
    $stmt = $connect->prepare(
        "SELECT product_name AS name, quantity AS qty, milk, addons
         FROM cart
         WHERE user_name = ?
         ORDER BY created_at ASC"
    );
    $stmt->bind_param('s', $userName);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $items;
}

function cartApiInventoryItemsAfterAdd(mysqli $connect, string $userName, array $nextItem): array
{
    $items = cartApiInventoryItems($connect, $userName);
    $nextKey = boycold_inventory_normalize_name((string) $nextItem['name']);
    $merged = false;

    foreach ($items as &$item) {
        if (boycold_inventory_normalize_name((string) $item['name']) !== $nextKey) {
            continue;
        }
        $item['qty'] = max(1, (int) ($item['qty'] ?? 1)) + max(1, (int) ($nextItem['qty'] ?? 1));
        $item['milk'] = (string) ($nextItem['milk'] ?? '');
        $item['addons'] = (string) ($nextItem['addons'] ?? '');
        $merged = true;
        break;
    }
    unset($item);

    if (!$merged) {
        $items[] = $nextItem;
    }

    return $items;
}

function cartApiInventoryItemsAfterUpdate(mysqli $connect, string $userName, int $cartId, int $quantity): array
{
    $stmt = $connect->prepare(
        "SELECT id, product_name AS name, quantity AS qty, milk, addons
         FROM cart
         WHERE user_name = ?
         ORDER BY created_at ASC"
    );
    $stmt->bind_param('s', $userName);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as &$row) {
        if ((int) $row['id'] === $cartId) {
            $row['qty'] = $quantity;
            break;
        }
    }
    unset($row);

    return $rows;
}
