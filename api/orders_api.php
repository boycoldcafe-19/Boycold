<?php
// ── orders_api.php ────────────────────────────────────────────
// Handles order creation and retrieval.
// Regular users only see their own orders (WHERE user_name = ?).
// Admins (is_admin = 1 in users table, or use a session flag) can
// see all orders by omitting the user_name filter.
//
// Actions:
//   place   → create a new order from the current cart (POST JSON)
//   list    → get orders for this user (or all, if admin)
//   detail  → get one order with its items
//   cancel  → cancel a pending order (user's own only)
//   update_status → admin-only: change order status
//
// PAYMENT FLOW:
//   COD:  status=confirmed, payment_status=unpaid until staff confirms cash
//   QRPh: status=pending, payment_status=pending until PayMongo webhook marks paid
// ─────────────────────────────────────────────────────────────

// Enable error catching
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Simple CORS support for local development where frontend may run on a
// different port (e.g. a dev server on :3000 while PHP runs elsewhere).
// Mirrors pos-order-api.php's handling.
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
    if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

function orderApiSessionHasAuth(): bool
{
    return !empty($_SESSION['user_id']) || !empty($_SESSION['employee_id']);
}

function orderApiRequestCameFromPos(): bool
{
    $refererPath = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH);

    return is_string($refererPath) && stripos($refererPath, '/POS/') !== false;
}

// Customer pages use the normal application session. POS pages use their
// isolated session. Select the name from the request path instead of
// repeatedly switching sessions, which is unreliable on HTTPS hosting.
require_once __DIR__ . '/../config/session_config.php';
boycold_start_session(orderApiRequestCameFromPos() ? 'POS_SESSION' : 'PHPSESSID');
require_once '../config/db_config.php';
require_once '../config/loyalty.php';
require_once '../config/payments.php';

boycold_ensure_payment_schema($connect);

header('Content-Type: application/json');

// Set up error handler (catches classic PHP warnings/notices)
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    exit;
});

// ── Catch-all for uncaught exceptions ──────────────────────────
// PHP 8.1+ makes mysqli throw mysqli_sql_exception on query errors by
// default. set_error_handler() above does NOT catch these (they're
// Throwables, not classic PHP errors), so without this handler any
// SQL problem here would kill the script with a completely blank
// response body — the browser gets nothing to parse as JSON, and the
// orders list silently fails to load with no visible clue why.
set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Server error: ' . $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine()
    ]);
    exit;
});

// ── Auth guard ────────────────────────────────────────────────
$userId     = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$employeeId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

if ($userId <= 0 && $employeeId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

// ── Always derive user_name fresh from the users table ─────────
// `orders.user_name` is filled from the SAME generated column as
// `users.user_name` (firstname + ' ' + lastname). Trusting
// $_SESSION['user_name'] instead is what was causing "No orders
// yet" even when rows exist: if that session value was ever set
// to something else at login (or the name changed since), it no
// longer matches orders.user_name and every WHERE user_name = ?
// query silently returns zero rows. Looking it up by user_id here
// guarantees an exact match every single time.
$userName = '';
if ($userId > 0) {
    $uStmt  = $connect->prepare("SELECT user_name FROM users WHERE id = ?");
    $uStmt->bind_param("i", $userId);
    $uStmt->execute();
    $uRow = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();

    if ($uRow) {
        $userName = $uRow['user_name'];
        $_SESSION['user_name'] = $userName; // keep session in sync for any other code reading it
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User not found in database.']);
        exit;
    }
}

if ($userName === '' && $employeeId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not found.']);
    exit;
}

$isEmployee = $employeeId > 0;
$isAdmin = !empty($_SESSION['is_admin']) || $isEmployee; // POS employees can manage order statuses.

$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = $body['action'] ?? ($_GET['action'] ?? '');

$connect->query("CREATE TABLE IF NOT EXISTS order_reports (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    issue VARCHAR(120) NOT NULL,
    details VARCHAR(500) NOT NULL DEFAULT '',
    photo_paths TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_reports_order (order_id),
    KEY idx_order_reports_user (user_id),
    KEY idx_order_reports_created_at (created_at),
    CONSTRAINT fk_order_report_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_report_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Log the request for debugging
$actorLabel = $userName !== '' ? $userName : ('employee#' . $employeeId);
error_log("Orders API - User: $actorLabel, Action: $action, Body Keys: " . implode(',', array_keys($body)));

switch ($action) {

    // ── TEST ACTION (for debugging) ──────────────────────────
    case 'test':
        echo json_encode([
            'success' => true,
            'test' => 'API is working',
            'user_name' => $userName,
            'session_id' => session_id(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;

    // ── PLACE ORDER ───────────────────────────────────────────
    // Expects JSON:
    // {
    //   "action": "place",
    //   "items": [
    //     { "name": "Americano", "unitPrice": 69, "qty": 2,
    //       "image": "/picture/...", "milk": "", "addons": "",
    //       "orderType": "dine-in", "notes": "" }
    //   ],
    //   "order_type": "dine-in",
    //   "payment_method": "cod" | "qrph",
    //   "address": "...",
    //   "delivery_fee": 30,
    //   "tax": 5,
    //   "notes": ""
    // }
    case 'place':
        $items          = $body['items']        ?? [];
        $orderType      = strtolower(substr(trim($body['order_type'] ?? 'dine-in'), 0, 20));
        if (in_array($orderType, ['pick-up', 'pick up', 'takeout'], true)) {
            $orderType = 'pickup';
        }
        $address        = trim($body['address']  ?? '');
        $contactNumber  = trim($body['contact_number'] ?? '');
        $deliveryFee    = (float) ($body['delivery_fee'] ?? 0);
        $tax            = (float) ($body['tax']          ?? 0);
        $orderNotes     = trim($body['notes']   ?? '');
        $branchId       = isset($body['branch_id']) && $body['branch_id'] !== '' ? (int) $body['branch_id'] : null;

        // ── Payment method / status ────────────────────────────
        $paymentMethod = strtolower(trim($body['payment_method'] ?? 'cod'));
        if (!in_array($paymentMethod, ['cod', 'qrph'], true)) {
            $paymentMethod = 'cod';
        }
        
        $orderStatus = ($paymentMethod === 'qrph') ? 'pending' : 'confirmed';
        $paymentStatus = ($paymentMethod === 'qrph') ? 'pending' : 'unpaid';

        if (empty($items)) {
            echo json_encode(['success' => false, 'error' => 'No items in order.']);
            break;
        }

        if ($paymentMethod === 'qrph' && $userId <= 0) {
            echo json_encode(['success' => false, 'error' => 'QRPh checkout requires a customer account.']);
            break;
        }

        if (!preg_match('/^09\d{9}$/', $contactNumber)) {
            echo json_encode(['success' => false, 'error' => 'A valid 11-digit mobile number starting with 09 is required before QRPh payment.']);
            break;
        }

        if ($userId > 0) {
            $phoneStmt = $connect->prepare('UPDATE users SET phone = ? WHERE id = ?');
            $phoneStmt->bind_param('si', $contactNumber, $userId);
            $phoneStmt->execute();
            $phoneStmt->close();
        }

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float)($item['unitPrice'] ?? 0) * max(1, (int)($item['qty'] ?? 1));
        }
        $total = $subtotal + $deliveryFee + $tax;

        $connect->begin_transaction();
        try {
            $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
            $stmt = $connect->prepare(
                "INSERT INTO orders
                   (user_name, user_id, status, order_type, payment_method, payment_status,
                    subtotal, delivery_fee, tax, total, branch_id, address, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "sissssddddiss",
                $userName,
                $userId,
                $orderStatus,
                $orderType,
                $paymentMethod,
                $paymentStatus,
                $subtotal,
                $deliveryFee,
                $tax,
                $total,
                $branchId,
                $address,
                $orderNotes
            );
            if (!$stmt->execute()) {
                throw new RuntimeException('Failed to create order.');
            }
            $orderId = (int) $connect->insert_id;

            $itemStmt = $connect->prepare(
                "INSERT INTO order_items
                   (order_id, product_name, product_image, unit_price, quantity,
                    line_total, milk, addons, order_type, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            foreach ($items as $item) {
                $name      = substr(trim($item['name']      ?? ''), 0, 150);
                $image     = substr(trim($item['image']     ?? ''), 0, 255);
                $unitPrice = (float)  ($item['unitPrice']  ?? 0);
                $qty       = max(1, (int) ($item['qty']    ?? 1));
                $lineTotal = $unitPrice * $qty;
                $milk      = substr(trim($item['milk']      ?? ''), 0, 80);
                $addons    = substr(trim($item['addons']    ?? ''), 0, 255);
                $oType     = substr(trim($item['orderType'] ?? ''), 0, 40);
                $notes     = trim($item['notes'] ?? '');

                $itemStmt->bind_param(
                    "issdidssss",
                    $orderId,
                    $name,
                    $image,
                    $unitPrice,
                    $qty,
                    $lineTotal,
                    $milk,
                    $addons,
                    $oType,
                    $notes
                );
                $itemStmt->execute();
            }

            $fromCart = array_key_exists('from_cart', $body) ? (bool) $body['from_cart'] : true;
            if ($fromCart) {
                $clr = $connect->prepare("DELETE FROM cart WHERE user_name = ?");
                $clr->bind_param("s", $userName);
                $clr->execute();
            }

            $connect->commit();
        } catch (Throwable $e) {
            $connect->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            break;
        }

        $qrPayload = null;
        if ($paymentMethod === 'qrph') {
            try {
                $qrPayload = boycold_create_qrph_for_order($connect, $orderId, $total);
            } catch (Throwable $e) {
                $fail = $connect->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?");
                $fail->bind_param('i', $orderId);
                $fail->execute();
                $fail->close();
                echo json_encode(['success' => false, 'error' => $e->getMessage(), 'order_id' => $orderId]);
                break;
            }
        }

        $response = [
            'success'        => true,
            'order_id'       => $orderId,
            'total'          => number_format($total, 2),
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'order_status'   => $orderStatus,
            'message'        => 'Order placed successfully.',
        ];
        if ($qrPayload) {
            $response['qr_image_url'] = $qrPayload['qr_image_url'];
            $response['payment_reference'] = $qrPayload['payment_intent_id'];
        }
        echo json_encode($response);
        break;

    // ── LIST ORDERS ───────────────────────────────────────────
    // Regular users: only their own orders.
    // Admins: all orders (pass ?action=list&all=1 or set is_admin).
    case 'list':

    $status = strtolower(trim(
        $_POST['status']
        ?? $body['status']
        ?? $_GET['status']
        ?? ''
    ));

    $allowedStatus = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'completed', 'cancelled'];

    if (!in_array($status, $allowedStatus, true)) {
        $status = '';
    }

    if ($isAdmin && (!empty($_GET['all']) || !empty($body['all']))) {

        // LEFT JOIN on user_id (not user_name): two customers can share the
        // exact same generated "Firstname Lastname" user_name (it has no
        // UNIQUE constraint), and joining on that string used to duplicate
        // every one of their orders — one row per matching users record.
        // Legacy orders placed before user_id existed (NULL) still fall back
        // to a name match so they don't disappear from this view.
        $sql = "
            SELECT
                o.id,
                ROW_NUMBER() OVER (PARTITION BY COALESCE(o.user_id, o.user_name) ORDER BY o.created_at ASC, o.id ASC) AS order_number,
                o.user_name,
                o.status,
                o.order_type,
                o.payment_method,
                o.payment_status,
                o.subtotal,
                o.delivery_fee,
                o.tax,
                o.total,
                o.created_at,
                u.firstname,
                u.lastname,
                u.email
            FROM orders o
            LEFT JOIN users u
                ON (o.user_id IS NOT NULL AND u.id = o.user_id)
                OR (o.user_id IS NULL AND u.user_name = o.user_name)
        ";

        if ($status != '') {
            $sql .= " WHERE o.status = ?";
        }

        $sql .= " ORDER BY o.created_at DESC";

        $stmt = $connect->prepare($sql);

        if ($status != '') {
            $stmt->bind_param("s", $status);
        }

    } else {

        // Matched by user_id first (a real foreign key, unlike user_name
        // which has no UNIQUE constraint and can collide between two
        // customers with the same name). Legacy orders placed before
        // user_id existed (NULL) fall back to a name match.
        $sql = "
            SELECT
                id,
                ROW_NUMBER() OVER (PARTITION BY COALESCE(user_id, user_name) ORDER BY created_at ASC, id ASC) AS order_number,
                user_name,
                status,
                order_type,
                payment_method,
                payment_status,
                subtotal,
                delivery_fee,
                tax,
                total,
                created_at
            FROM orders
            WHERE (user_id = ? OR (user_id IS NULL AND user_name = ?))
        ";

        if ($status != '') {
            $sql .= " AND status = ?";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $connect->prepare($sql);

        if ($status != '') {
            $stmt->bind_param("iss", $userId, $userName, $status);
        } else {
            $stmt->bind_param("is", $userId, $userName);
        }
    }

    $stmt->execute();

    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "success" => true,
        "orders" => $orders
    ]);

    break;

    // ── DETAIL: one order + its items ─────────────────────────
    case 'detail':
        $orderId = isset($body['order_id']) ? (int) $body['order_id']
            : (isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0);

        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid order_id.']);
            break;
        }

        // Non-admins can only see their own order
        if ($isAdmin) {
            $stmt = $connect->prepare(
                "SELECT o.*,
                        (SELECT COUNT(*) FROM orders sequence_order
                         WHERE COALESCE(sequence_order.user_id, sequence_order.user_name) = COALESCE(o.user_id, o.user_name)
                           AND (sequence_order.created_at < o.created_at
                                OR (sequence_order.created_at = o.created_at AND sequence_order.id <= o.id))) AS order_number
                 FROM orders o WHERE o.id = ?"
            );
            $stmt->bind_param("i", $orderId);
        } else {
            // Matched by user_id, not user_name — two customers can share
            // the exact same generated "Firstname Lastname" string, and
            // matching on that would let one see the other's order detail.
            $stmt = $connect->prepare(
                                "SELECT o.*,
                                                (SELECT COUNT(*) FROM orders sequence_order
                                                 WHERE COALESCE(sequence_order.user_id, sequence_order.user_name) = COALESCE(o.user_id, o.user_name)
                                                     AND (sequence_order.created_at < o.created_at
                                                                OR (sequence_order.created_at = o.created_at AND sequence_order.id <= o.id))) AS order_number
                                 FROM orders o
                                 WHERE o.id = ?
                                   AND (o.user_id = ? OR (o.user_id IS NULL AND o.user_name = ?))"
            );
            $stmt->bind_param("iis", $orderId, $userId, $userName);
        }
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found.']);
            break;
        }

        // Fetch line items
        $items = $connect->prepare(
            "SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC"
        );
        $items->bind_param("i", $orderId);
        $items->execute();
        $order['items'] = $items->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'order' => $order]);
        break;

    // ── CANCEL: user cancels their own pending order ──────────
    case 'cancel':
        $orderId = isset($body['order_id']) ? (int) $body['order_id'] : 0;
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid order_id.']);
            break;
        }

        if ($isAdmin) {
            $stmt = $connect->prepare(
                "UPDATE orders
                 SET status = 'cancelled',
                     payment_status = IF(payment_method = 'qrph' AND payment_status <> 'paid', 'cancelled', payment_status)
                 WHERE id = ?
                   AND status NOT IN ('ready', 'delivered', 'completed', 'cancelled')"
            );
            $stmt->bind_param("i", $orderId);
        } else {
            // Matched by user_id directly — no join through user_name needed,
            // and no risk of a stale/changed name ever blocking a cancel.
            // Legacy orders placed before user_id existed (NULL) still fall
            // back to a name match.
            $stmt = $connect->prepare(
                "UPDATE orders
                 SET status = 'cancelled',
                     payment_status = IF(payment_method = 'qrph' AND payment_status <> 'paid', 'cancelled', payment_status)
                 WHERE id = ?
                   AND (user_id = ? OR (user_id IS NULL AND user_name = ?))
                   AND status NOT IN ('ready', 'delivered', 'completed', 'cancelled')"
            );
            $stmt->bind_param("iis", $orderId, $userId, $userName);
        }
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Cannot cancel: order not found, not yours, or already in a final state.'
            ]);
            break;
        }

        echo json_encode(['success' => true, 'message' => 'Order cancelled.']);
        break;

    case 'payment_status':
        $orderId = isset($body['order_id']) ? (int) $body['order_id']
            : (isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0);
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid order_id.']);
            break;
        }

        $stmt = $connect->prepare(
            "SELECT id, status, payment_method, payment_status, payment_expires_at, total
             FROM orders
             WHERE id = ?
               AND (user_id = ? OR (user_id IS NULL AND user_name = ?))"
        );
        $stmt->bind_param('iis', $orderId, $userId, $userName);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found.']);
            break;
        }

        if ($order['payment_method'] === 'qrph'
            && $order['payment_status'] === 'pending'
            && !empty($order['payment_expires_at'])
            && strtotime($order['payment_expires_at']) <= time()) {
            $expireStmt = $connect->prepare(
                "UPDATE orders
                 SET payment_status = 'expired', status = IF(status = 'pending', 'cancelled', status)
                 WHERE id = ? AND payment_status = 'pending'"
            );
            $expireStmt->bind_param('i', $orderId);
            $expireStmt->execute();
            $expireStmt->close();
            $order['payment_status'] = 'expired';
            if ($order['status'] === 'pending') $order['status'] = 'cancelled';
        }

        echo json_encode([
            'success' => true,
            'order_id' => (int) $order['id'],
            'order_status' => $order['status'],
            'payment_method' => $order['payment_method'],
            'payment_status' => $order['payment_status'],
            'payment_expires_at' => $order['payment_expires_at'],
            'total' => (float) $order['total'],
        ]);
        break;

    case 'report':
        $orderId = (int)($body['order_id'] ?? 0);
        $issue = trim((string)($body['issue'] ?? ''));
        $details = trim((string)($body['details'] ?? ''));
        $photos = is_array($body['photos'] ?? null) ? $body['photos'] : [];
        if ($userId <= 0 || $orderId <= 0 || $issue === '' || $details === '' || strlen($details) > 500) {
            echo json_encode(['success' => false, 'error' => 'Please provide the issue and report details.']);
            break;
        }

        $orderCheck = $connect->prepare('SELECT id FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
        $orderCheck->bind_param('ii', $orderId, $userId);
        $orderCheck->execute();
        if (!$orderCheck->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'error' => 'Order not found.']);
            break;
        }

        $savedPhotos = [];
        $uploadDir = __DIR__ . '/../User/uploads/reports';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        foreach (array_slice($photos, 0, 3) as $photo) {
            if (!is_string($photo) || !preg_match('#^data:image/(jpeg|png|webp);base64,#i', $photo, $match)) continue;
            $binary = base64_decode(substr($photo, strpos($photo, ',') + 1), true);
            if ($binary === false || strlen($binary) > 5 * 1024 * 1024) continue;
            $extension = strtolower($match[1]) === 'jpeg' ? 'jpg' : strtolower($match[1]);
            $filename = 'report_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            if (file_put_contents($uploadDir . '/' . $filename, $binary) !== false) {
                $savedPhotos[] = 'User/uploads/reports/' . $filename;
            }
        }

        $photoPaths = json_encode($savedPhotos, JSON_UNESCAPED_SLASHES);
        $reportStmt = $connect->prepare('INSERT INTO order_reports (order_id, user_id, issue, details, photo_paths) VALUES (?, ?, ?, ?, ?)');
        $reportStmt->bind_param('iisss', $orderId, $userId, $issue, $details, $photoPaths);
        $reportStmt->execute();
        echo json_encode(['success' => true, 'message' => 'Report submitted successfully.']);
        break;

    case 'review':
        $orderId = (int)($body['order_id'] ?? 0);
        $rating = (int)($body['rating'] ?? 0);
        $review = trim((string)($body['review'] ?? ''));
        if ($userId <= 0 || $orderId <= 0 || $rating < 1 || $rating > 5 || strlen($review) > 1000) {
            echo json_encode(['success' => false, 'error' => 'Invalid review details.']);
            break;
        }
        $check = $connect->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'completed' LIMIT 1");
        $check->bind_param('ii', $orderId, $userId);
        $check->execute();
        if (!$check->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'error' => 'Only your completed orders can be reviewed.']);
            break;
        }
        $stmt = $connect->prepare('INSERT INTO order_reviews (order_id, user_id, rating, review) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), review = VALUES(review), updated_at = CURRENT_TIMESTAMP');
        $stmt->bind_param('iiis', $orderId, $userId, $rating, $review);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Thank you for your feedback.']);
        break;

    case 'qrph_details':
        $orderId = isset($body['order_id']) ? (int) $body['order_id'] : 0;
        if ($orderId <= 0 || $userId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid order.']);
            break;
        }

        $stmt = $connect->prepare(
            "SELECT id, payment_method, payment_status, payment_reference, total
             FROM orders WHERE id = ? AND user_name = ?"
        );
        $stmt->bind_param('is', $orderId, $userName);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order || strtolower((string) $order['payment_method']) !== 'qrph') {
            echo json_encode(['success' => false, 'error' => 'QRPh order not found.']);
            break;
        }

        $qrImage = '';
        $ref = trim((string) ($order['payment_reference'] ?? ''));
        if ($ref !== '' && strtolower((string) $order['payment_status']) === 'pending') {
            try {
                $intent = paymongo_retrieve_intent($ref);
                $qrImage = paymongo_extract_qr_image($intent);
            } catch (Throwable $e) {
                $qrImage = '';
            }
        }

        echo json_encode([
            'success' => true,
            'order_id' => (int) $order['id'],
            'payment_status' => $order['payment_status'],
            'qr_image_url' => $qrImage,
            'total' => (float) $order['total'],
        ]);
        break;

    case 'confirm_cod_payment':
        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden.']);
            break;
        }

        $orderId = isset($body['order_id']) ? (int) $body['order_id'] : 0;
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid order_id.']);
            break;
        }

        $branchId = isset($_SESSION['branch_id']) ? (int) $_SESSION['branch_id'] : 0;
        echo json_encode(boycold_confirm_cod_payment($connect, $orderId, $branchId));
        break;

    case 'update_status':
        $orderId   = isset($body['order_id']) ? (int) $body['order_id'] : 0;
        $newStatus = trim($body['status'] ?? '');
        $allowed   = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'completed', 'cancelled'];

        if ($orderId <= 0 || !in_array($newStatus, $allowed, true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid order_id or status.']);
            break;
        }

        $existingOrderStmt = $connect->prepare(
            "SELECT user_name, status, payment_method, payment_status FROM orders WHERE id = ?"
        );
        $existingOrderStmt->bind_param("i", $orderId);
        $existingOrderStmt->execute();
        $existingOrder = $existingOrderStmt->get_result()->fetch_assoc();

        if (!$existingOrder) {
            echo json_encode(['success' => false, 'error' => 'Order not found.']);
            break;
        }

        $paymentMethod = strtolower((string) ($existingOrder['payment_method'] ?? 'cod'));
        $paymentStatus = strtolower((string) ($existingOrder['payment_status'] ?? 'unpaid'));

        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden.']);
            break;
        }

        if ($paymentMethod === 'qrph' && $paymentStatus !== 'paid' && $newStatus !== 'cancelled') {
            echo json_encode(['success' => false, 'error' => 'QRPh payment must be verified by PayMongo before this order can proceed.']);
            break;
        }

        $shouldAwardLoyalty = ($newStatus === 'completed' && ($existingOrder['status'] ?? '') !== 'completed');

        if ($newStatus === 'completed') {
            // Auto-settle COD orders on completion; leave QR Ph (already paid) untouched
            $stmt = $connect->prepare(
                "UPDATE orders
                 SET status = ?,
                     payment_status = IF(payment_method = 'cod', 'paid', payment_status)
                 WHERE id = ?"
            );
        } else {
            $stmt = $connect->prepare("UPDATE orders SET status = ? WHERE id = ?");
        }
        $stmt->bind_param("si", $newStatus, $orderId);
        $stmt->execute();

        if ($shouldAwardLoyalty && !empty($existingOrder['user_name'])) {
            awardLoyaltyForCompletedOrder($connect, $orderId, $existingOrder['user_name']);
        }

        echo json_encode(['success' => true, 'message' => "Order status set to '$newStatus'."]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}