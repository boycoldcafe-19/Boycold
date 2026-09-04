<?php
session_start();
require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json; charset=utf-8');

function response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

function requireValue(array $data, string $key): string
{
    $value = trim((string)($data[$key] ?? ''));
    if ($value === '') response(['success' => false, 'error' => "$key is required"], 422);
    return $value;
}

function currentAdmin(mysqli $connect): ?array
{
    $id = (int)($_SESSION['admin_account_id'] ?? $_SESSION['employee_id'] ?? -1);
    if ($id < 0 || ($_SESSION['employee_role'] ?? '') !== 'admin') return null;
    $stmt = $connect->prepare("SELECT id, employee_name AS full_name, email, password, avatar
                               FROM employees WHERE id = ? AND role = 'admin' AND is_active = 1 LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = input();

if (!currentAdmin($connect)) {
    response(['success' => false, 'error' => 'Admin login required'], 401);
}

try {
    switch ($action) {
        case 'settings_get':
            $admin = currentAdmin($connect);
            if (!$admin) response(['success' => false, 'error' => 'Admin login required'], 401);
            response(['success' => true, 'settings' => ['full_name' => $admin['full_name'] ?: 'Admin', 'email' => $admin['email'], 'avatar' => $admin['avatar']]]);

        case 'settings_profile':
            $name = requireValue($data, 'full_name');
            $email = requireValue($data, 'email');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) response(['success' => false, 'error' => 'Enter a valid email address'], 422);
            $admin = currentAdmin($connect);
            if (!$admin) response(['success' => false, 'error' => 'Admin login required'], 401);
            $parts = preg_split('/\s+/', $name, 2);
            $firstname = $parts[0];
            $lastname = $parts[1] ?? '';
            $stmt = $connect->prepare('UPDATE employees SET firstname = ?, lastname = ?, email = ? WHERE id = ?');
            $stmt->bind_param('sssi', $firstname, $lastname, $email, $admin['id']);
            $stmt->execute();
            $_SESSION['employee_name'] = $name;
            $_SESSION['employee_email'] = $email;
            response(['success' => true]);

        case 'settings_password':
            $current = requireValue($data, 'current_password');
            $new = requireValue($data, 'new_password');
            if (strlen($new) < 8) response(['success' => false, 'error' => 'New password must be at least 8 characters'], 422);
            $admin = currentAdmin($connect);
            if (!$admin) response(['success' => false, 'error' => 'Admin login required'], 401);
            if (!password_verify($current, $admin['password'])) {
                response(['success' => false, 'error' => 'Current password is incorrect'], 422);
            }
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $connect->prepare('UPDATE employees SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $admin['id']);
            $stmt->execute();
            response(['success' => true]);

        case 'customers':
            $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.phone, u.is_verified,
                           u.account_status, u.card_no, u.created_at, u.loyalty_card_status,
                           COUNT(DISTINCT o.id) AS order_count
                    FROM users u
                    LEFT JOIN orders o ON o.user_name = u.user_name
                    GROUP BY u.id, u.firstname, u.lastname, u.email, u.phone, u.is_verified,
                             u.account_status, u.card_no, u.created_at, u.loyalty_card_status
                    ORDER BY u.created_at DESC";
            $result = $connect->query($sql);
            $customers = [];
            while ($row = $result->fetch_assoc()) $customers[] = $row;
            response(['success' => true, 'customers' => $customers]);

        case 'customer_status':
            $id = (int)($data['id'] ?? 0);
            $status = ($data['status'] ?? '') === 'inactive' ? 'inactive' : 'active';
            if ($id < 1) response(['success' => false, 'error' => 'Invalid customer'], 422);
            $stmt = $connect->prepare('UPDATE users SET account_status = ? WHERE id = ?');
            $stmt->bind_param('si', $status, $id);
            $stmt->execute();
            response(['success' => true]);

        case 'loyalty':
            $statusColumn = $connect->query("SHOW COLUMNS FROM users LIKE 'loyalty_card_status'");
            $hasStatusColumn = $statusColumn && $statusColumn->num_rows > 0;
            if (!$hasStatusColumn) {
                $connect->query("ALTER TABLE users ADD COLUMN loyalty_card_status ENUM('active','inactive','completed') NOT NULL DEFAULT 'active' AFTER loyalty_stamps");
                $hasStatusColumn = true;
            }
            $statusSelect = $hasStatusColumn ? 'MAX(u.loyalty_card_status) AS loyalty_card_status' : "'active' AS loyalty_card_status";
                 $result = $connect->query("SELECT u.id, u.card_no, u.firstname, u.lastname, u.phone, u.created_at,
                                    LEAST(10, GREATEST(0, u.loyalty_stamps)) AS loyalty_stamps, $statusSelect,
                                    COALESCE(MIN(lt.created_at), u.created_at) AS activation_date,
                                    COUNT(DISTINCT o.id) AS order_count
                                FROM users u
                                LEFT JOIN loyalty_transactions lt ON lt.user_id = u.id
                                LEFT JOIN orders o ON o.user_name = u.user_name
                                WHERE u.card_no IS NOT NULL AND u.card_no <> ''
                                GROUP BY u.id, u.card_no, u.firstname, u.lastname, u.phone, u.created_at, u.loyalty_stamps
                                HAVING COUNT(DISTINCT o.id) > 0
                                ORDER BY u.created_at DESC");
            $cards = [];
            while ($row = $result->fetch_assoc()) $cards[] = $row;
            response(['success' => true, 'cards' => $cards]);

        case 'reviews':
            $result = $connect->query("SELECT r.id, r.order_id, r.rating, r.review, r.created_at,
                                              CONCAT(u.firstname, ' ', u.lastname) AS customer_name,
                                              u.email
                                       FROM order_reviews r JOIN users u ON u.id = r.user_id
                                       ORDER BY r.created_at DESC, r.id DESC");
            $reviews = [];
            while ($row = $result->fetch_assoc()) $reviews[] = $row;
            response(['success' => true, 'reviews' => $reviews]);

        case 'loyalty_status':
            $id = (int)($data['id'] ?? 0);
            $status = $data['status'] ?? '';
            if ($id < 1 || !in_array($status, ['active', 'inactive', 'completed'], true)) response(['success' => false, 'error' => 'Invalid loyalty update'], 422);
            $stmt = $connect->prepare('UPDATE users SET loyalty_card_status = ? WHERE id = ?');
            $stmt->bind_param('si', $status, $id);
            $stmt->execute();
            response(['success' => true]);

        case 'ingredients':
            $result = $connect->query('SELECT id, name, category, unit, stock, min_stock, branch_id FROM ingredients ORDER BY name');
            $ingredients = [];
            while ($row = $result->fetch_assoc()) $ingredients[] = $row;
            response(['success' => true, 'ingredients' => $ingredients]);

        case 'products':
            $result = $connect->query('SELECT id, product_name, description, price, image, category, is_available FROM products ORDER BY category, product_name');
            $products = [];
            while ($row = $result->fetch_assoc()) $products[] = $row;
            response(['success' => true, 'products' => $products]);

        case 'orders':
            $result = $connect->query("SELECT id, user_name, status, order_type, payment_method, payment_status,
                                              payment_reference, total, created_at
                                       FROM orders ORDER BY created_at DESC, id DESC");
            $orders = [];
            $itemsStmt = $connect->prepare('SELECT product_name, quantity, unit_price, line_total, milk, addons, notes FROM order_items WHERE order_id = ? ORDER BY id');
            while ($order = $result->fetch_assoc()) {
                $itemsStmt->bind_param('i', $order['id']);
                $itemsStmt->execute();
                $itemsResult = $itemsStmt->get_result();
                $items = [];
                while ($item = $itemsResult->fetch_assoc()) {
                    $details = array_values(array_filter([$item['milk'], $item['addons'], $item['notes']]));
                    $items[] = ['name' => $item['product_name'], 'qty' => (int)$item['quantity'], 'price' => (float)$item['unit_price'], 'details' => $details];
                }
                $order['items'] = $items;
                $orders[] = $order;
            }
            response(['success' => true, 'orders' => $orders]);

        case 'product_create':
            $name = requireValue($data, 'product_name');
            $category = requireValue($data, 'category');
            $price = max(0, (float)($data['price'] ?? 0));
            $image = trim((string)($data['image'] ?? ''));
            $available = !empty($data['is_available']) ? 1 : 0;
            $stmt = $connect->prepare('INSERT INTO products (product_name, description, price, image, category, is_available) VALUES (?, ?, ?, ?, ?, ?)');
            $description = '';
            $stmt->bind_param('ssdssi', $name, $description, $price, $image, $category, $available);
            $stmt->execute();
            response(['success' => true, 'id' => $connect->insert_id]);

        case 'product_update':
            $id = (int)($data['id'] ?? 0);
            $name = requireValue($data, 'product_name');
            $category = requireValue($data, 'category');
            $price = max(0, (float)($data['price'] ?? 0));
            $available = !empty($data['is_available']) ? 1 : 0;
            $stmt = $connect->prepare('UPDATE products SET product_name = ?, category = ?, price = ?, is_available = ? WHERE id = ?');
            $stmt->bind_param('ssdii', $name, $category, $price, $available, $id);
            $stmt->execute();
            response(['success' => true]);

        case 'product_delete':
            $id = (int)($data['id'] ?? 0);
            $stmt = $connect->prepare('DELETE FROM products WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            response(['success' => true]);

        case 'ingredient_create':
            $name = requireValue($data, 'name');
            $category = requireValue($data, 'category');
            $unit = requireValue($data, 'unit');
            $stock = (float)($data['stock'] ?? 0);
            $minStock = (float)($data['min_stock'] ?? 0);
            $stmt = $connect->prepare('INSERT INTO ingredients (name, category, unit, stock, min_stock, branch_id) VALUES (?, ?, ?, ?, ?, ?)');
            $branch = isset($data['branch_id']) && $data['branch_id'] !== '' ? (int)$data['branch_id'] : null;
            $stmt->bind_param('sssddi', $name, $category, $unit, $stock, $minStock, $branch);
            $stmt->execute();
            response(['success' => true, 'id' => $connect->insert_id]);

        case 'ingredient_update':
            $id = (int)($data['id'] ?? 0);
            $name = requireValue($data, 'name');
            $category = requireValue($data, 'category');
            $unit = requireValue($data, 'unit');
            $stock = (float)($data['stock'] ?? 0);
            $minStock = (float)($data['min_stock'] ?? 0);
            $stmt = $connect->prepare('UPDATE ingredients SET name = ?, category = ?, unit = ?, stock = ?, min_stock = ? WHERE id = ?');
            $stmt->bind_param('sssddi', $name, $category, $unit, $stock, $minStock, $id);
            $stmt->execute();
            response(['success' => true]);

        case 'ingredient_delete':
            $id = (int)($data['id'] ?? 0);
            $stmt = $connect->prepare('DELETE FROM ingredients WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            response(['success' => true]);

        case 'stock_in':
            $items = $data['items'] ?? [];
            if (!is_array($items) || !$items) response(['success' => false, 'error' => 'No stock items supplied'], 422);
            $connect->begin_transaction();
            $update = $connect->prepare('UPDATE ingredients SET stock = stock + ? WHERE id = ?');
            $movement = $connect->prepare("INSERT INTO ingredient_stock_movements (ingredient_id, movement_type, quantity, resulting_stock) SELECT id, 'stock_in', ?, stock FROM ingredients WHERE id = ?");
            foreach ($items as $item) {
                $id = (int)($item['id'] ?? 0);
                if ($id < 1 && !empty($item['name'])) {
                    $lookup = $connect->prepare('SELECT id FROM ingredients WHERE name = ? LIMIT 1');
                    $lookup->bind_param('s', $item['name']);
                    $lookup->execute();
                    $id = (int)($lookup->get_result()->fetch_assoc()['id'] ?? 0);
                }
                $quantity = (float)($item['quantity'] ?? 0);
                if ($id < 1 || $quantity <= 0) continue;
                $update->bind_param('di', $quantity, $id);
                $update->execute();
                $movement->bind_param('di', $quantity, $id);
                $movement->execute();
            }
            $connect->commit();
            response(['success' => true]);

        case 'stock_history':
            $result = $connect->query("SELECT m.id, i.name, m.movement_type, m.quantity, m.resulting_stock, m.created_at
                                       FROM ingredient_stock_movements m JOIN ingredients i ON i.id = m.ingredient_id
                                       ORDER BY m.created_at DESC LIMIT 200");
            $history = [];
            while ($row = $result->fetch_assoc()) $history[] = $row;
            response(['success' => true, 'history' => $history]);

        case 'mapping_get':
            $product = requireValue($_GET, 'product_name');
            $stmt = $connect->prepare('SELECT pi.ingredient_id, i.name AS ingredient, i.unit, pi.amount FROM product_ingredients pi JOIN ingredients i ON i.id = pi.ingredient_id WHERE pi.product_name = ? ORDER BY pi.id');
            $stmt->bind_param('s', $product);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            response(['success' => true, 'mapping' => $rows]);

        case 'mapping_save':
            $product = requireValue($data, 'product_name');
            $rows = $data['rows'] ?? [];
            if (!is_array($rows)) response(['success' => false, 'error' => 'Invalid mapping rows'], 422);
            $connect->begin_transaction();
            $delete = $connect->prepare('DELETE FROM product_ingredients WHERE product_name = ?');
            $delete->bind_param('s', $product);
            $delete->execute();
            $insert = $connect->prepare('INSERT INTO product_ingredients (product_name, ingredient_id, amount) VALUES (?, ?, ?)');
            foreach ($rows as $row) {
                $ingredientId = (int)($row['ingredient_id'] ?? 0);
                if ($ingredientId < 1 && !empty($row['ingredient'])) {
                    $lookup = $connect->prepare('SELECT id FROM ingredients WHERE name = ? LIMIT 1');
                    $lookup->bind_param('s', $row['ingredient']);
                    $lookup->execute();
                    $ingredientId = (int)($lookup->get_result()->fetch_assoc()['id'] ?? 0);
                }
                $amount = (float)($row['amount'] ?? 0);
                if ($ingredientId < 1 || $amount <= 0) continue;
                $insert->bind_param('sid', $product, $ingredientId, $amount);
                $insert->execute();
            }
            $connect->commit();
            response(['success' => true]);

        default:
            response(['success' => false, 'error' => 'Invalid action'], 400);
    }
} catch (Throwable $e) {
    if ($connect->errno) $connect->rollback();
    error_log('Admin data API: ' . $e->getMessage());
    response(['success' => false, 'error' => 'Request could not be completed'], 500);
}
