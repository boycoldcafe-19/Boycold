<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/admin_auth.php';
require_once __DIR__ . '/../config/inventory_service.php';
require_once __DIR__ . '/../config/menu_catalog_service.php';
require_once __DIR__ . '/../config/activity_logger.php';
require_once __DIR__ . '/../config/order_void_service.php';

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

function validateIngredientUnit(array $data): string
{
    $unit = trim((string)($data['unit'] ?? ''));
    $allowedUnits = [
        'g', 'kg', 'mg', 'ml', 'L', 'cl', 'pcs', 'box', 'pack',
        'bottle', 'sachet', 'jar', 'can', 'shot', 'unit'
    ];

    if ($unit === '' || !in_array($unit, $allowedUnits, true)) {
        response(['success' => false, 'error' => 'Please select a valid unit of measure.'], 422);
    }

    return $unit;
}

function currentAdmin(mysqli $connect): ?array
{
    $admin = boycold_admin_account($connect);
    if (!$admin) return null;
    $admin['full_name'] = $admin['employee_name'];
    return $admin;
}

function ensureProductIngredientsTable(mysqli $connect): void
{
    $sql = "CREATE TABLE IF NOT EXISTS product_ingredients (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        product_name VARCHAR(150) NOT NULL,
        ingredient_id INT UNSIGNED NOT NULL,
        amount DECIMAL(10,3) NOT NULL DEFAULT 0.000,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_product_ingredient (product_name, ingredient_id),
        KEY idx_product_name (product_name),
        KEY idx_ingredient_id (ingredient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!$connect->query($sql)) {
        throw new RuntimeException('Could not prepare the product mapping table: ' . $connect->error);
    }
}

function findDuplicateIngredient(mysqli $connect, string $name, int $branchId): ?array
{
    $stmt = $connect->prepare(
        'SELECT id, name, unit FROM ingredients WHERE branch_id = ? ORDER BY id'
    );
    $stmt->bind_param('i', $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    $candidateKey = boycold_inventory_duplicate_key($name);
    $duplicate = null;

    while ($row = $result->fetch_assoc()) {
        if (boycold_inventory_duplicate_key((string) $row['name']) === $candidateKey) {
            $duplicate = $row;
            break;
        }
    }
    $stmt->close();

    return $duplicate;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = input();

if (!currentAdmin($connect)) {
    response(['success' => false, 'error' => 'Admin login required'], 401);
}

// When an upload is bigger than post_max_size, PHP throws away the whole POST body. That used
// to surface as a confusing "product_name is required" - report the real reason instead.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && empty($_POST) && empty($_FILES)
    && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0
    && stripos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'multipart/form-data') === 0) {
    response(['success' => false, 'error' => 'The upload is larger than the server allows (post_max_size = ' . ini_get('post_max_size') . '). Use a smaller image.'], 413);
}

try {
    boycold_ensure_inventory_schema($connect);
    boycold_ensure_menu_category_schema($connect);
    boycold_ensure_product_id_auto_increment($connect);
    boycold_ensure_product_addons_schema($connect);

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
            $updated = $stmt->execute();
            $stmt->close();
            if (!$updated) response(['success' => false, 'error' => 'Profile could not be updated. Please try again.'], 500);
            $_SESSION['employee_name'] = $name;
            $_SESSION['employee_email'] = $email;
            boycold_log_activity($connect, [
                'category' => 'admin',
                'action' => 'profile_updated',
                'summary' => 'Admin Profile Updated',
                'details' => 'The administrator profile details were updated.',
                'actor_id' => (int) $admin['id'],
                'actor_type' => 'admin',
                'branch_id' => (int) ($admin['branch_id'] ?? 0),
                'entity_type' => 'employee',
                'entity_id' => (int) $admin['id'],
            ]);
            response(['success' => true]);

        case 'settings_password':
            $current = requireValue($data, 'current_password');
            $new = requireValue($data, 'new_password');
            if (strlen($new) < 8) response(['success' => false, 'error' => 'New password must be at least 8 characters'], 422);
            if (hash_equals($current, $new)) response(['success' => false, 'error' => 'New password must be different from your current password'], 422);
            $admin = currentAdmin($connect);
            if (!$admin) response(['success' => false, 'error' => 'Admin login required'], 401);
            if (!password_verify($current, $admin['password'])) {
                response(['success' => false, 'error' => 'Current password is incorrect'], 422);
            }
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $connect->prepare('UPDATE employees SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $admin['id']);
            $stmt->execute();
            $updated = $stmt->affected_rows;
            $stmt->close();
            if ($updated !== 1) response(['success' => false, 'error' => 'Password could not be updated. Please try again.'], 500);
            boycold_log_activity($connect, [
                'category' => 'admin',
                'action' => 'password_updated',
                'summary' => 'Admin Password Updated',
                'details' => 'The administrator account password was updated.',
                'actor_id' => (int) $admin['id'],
                'actor_type' => 'admin',
                'branch_id' => (int) ($admin['branch_id'] ?? 0),
                'entity_type' => 'employee',
                'entity_id' => (int) $admin['id'],
            ]);
            response(['success' => true, 'message' => 'Password updated successfully.']);

        case 'activity_export':
            $report = strtolower(trim((string) ($data['report'] ?? '')));
            $reports = [
                'dashboard' => 'Dashboard report',
                'analytics' => 'Data analytics report',
                'forecast' => 'Forecast report',
                'inventory' => 'Inventory report',
            ];
            if (!isset($reports[$report])) {
                response(['success' => false, 'error' => 'Invalid report export'], 422);
            }

            $admin = currentAdmin($connect);
            if (!$admin) response(['success' => false, 'error' => 'Admin login required'], 401);
            boycold_log_activity($connect, [
                'category' => 'exports',
                'action' => 'report_export_requested',
                'summary' => 'Report Export Requested',
                'details' => $reports[$report] . ' export was started by the administrator.',
                'actor_id' => (int) $admin['id'],
                'actor_type' => 'admin',
                'branch_id' => (int) ($admin['branch_id'] ?? 0),
                'entity_type' => 'report',
                'metadata' => ['report' => $report],
            ]);
            response(['success' => true]);

        case 'customers':
            // A POS void creates a replacement sale for audit/history. It is
            // not a new customer transaction, so do not include it in the
            // order total displayed on the Customers page.
            boycold_ensure_order_void_schema($connect);
            $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.phone, u.is_verified,
                           u.account_status, u.card_no, u.created_at, u.loyalty_card_status, u.avatar,
                           COUNT(DISTINCT o.id) AS order_count
                    FROM users u
                    LEFT JOIN orders o ON o.user_id = u.id
                        AND o.void_replacement_for_order_id IS NULL
                    GROUP BY u.id, u.firstname, u.lastname, u.email, u.phone, u.is_verified,
                             u.account_status, u.card_no, u.created_at, u.loyalty_card_status, u.avatar
                    ORDER BY u.created_at DESC";
            $result = $connect->query($sql);
            if (!$result) {
                response(['success' => false, 'error' => 'Database query failed: ' . $connect->error], 500);
            }
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
                $connect->query("ALTER TABLE users ADD COLUMN loyalty_card_status ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER loyalty_stamps");
                $hasStatusColumn = true;
            }
            // The reward (not the card status) indicates when ten stamps are
            // ready to redeem. Normalize legacy completed cards to Active.
            $connect->query("UPDATE users SET loyalty_card_status = 'active' WHERE loyalty_card_status = 'completed'");
            $statusSelect = $hasStatusColumn ? 'MAX(u.loyalty_card_status) AS loyalty_card_status' : "'active' AS loyalty_card_status";
                 $result = $connect->query("SELECT u.id, u.card_no, u.firstname, u.lastname, u.phone, u.avatar, u.created_at,
                                    LEAST(10, GREATEST(0, u.loyalty_stamps)) AS loyalty_stamps, $statusSelect,
                                    COALESCE(MIN(lt.created_at), u.created_at) AS activation_date,
                                    MAX(CASE WHEN lt.transaction_type = 'redemption' THEN lt.created_at END) AS date_redeemed,
                                    COUNT(DISTINCT o.id) AS order_count
                                FROM users u
                                LEFT JOIN loyalty_transactions lt ON lt.user_id = u.id
                                LEFT JOIN orders o ON o.user_name = u.user_name
                                WHERE u.card_no IS NOT NULL AND u.card_no <> ''
                                GROUP BY u.id, u.card_no, u.firstname, u.lastname, u.phone, u.avatar, u.created_at, u.loyalty_stamps
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
            if ($id < 1 || !in_array($status, ['active', 'inactive'], true)) response(['success' => false, 'error' => 'Invalid loyalty update'], 422);
            
            $stmt = $connect->prepare('UPDATE users SET loyalty_card_status = ? WHERE id = ?');
            $stmt->bind_param('si', $status, $id);
            $stmt->execute();
            response(['success' => true]);

        case 'ingredients':
            ensureProductIngredientsTable($connect);
            $requestedBranch = filter_input(INPUT_GET, 'branch_id', FILTER_VALIDATE_INT);
            $branchId = $requestedBranch && $requestedBranch > 0
                ? $requestedBranch
                : (int) ($_SESSION['branch_id'] ?? 1);
            $branchStmt = $connect->prepare('SELECT id FROM branches WHERE id = ? AND status = \'active\' LIMIT 1');
            $branchStmt->bind_param('i', $branchId);
            $branchStmt->execute();
            if (!$branchStmt->get_result()->fetch_assoc()) {
                $branchStmt->close();
                response(['success' => false, 'error' => 'Invalid branch selected.'], 422);
            }
            $branchStmt->close();

            $view = (string) ($_GET['view'] ?? 'all');
            if (!in_array($view, ['all', 'analytics', 'forecasting'], true)) {
                response(['success' => false, 'error' => 'Invalid inventory view.'], 422);
            }
            $hasMaxStock = $connect->query("SHOW COLUMNS FROM ingredients LIKE 'max_stock'")->num_rows > 0;
            $maxStockSelect = $hasMaxStock ? 'i.max_stock' : 'NULL';
            $maxStockGroup = $hasMaxStock ? ', i.max_stock' : '';
                        $mappedFilter = $view === 'all' ? '' : "AND EXISTS (
                                SELECT 1
                                FROM product_ingredients filter_pi
                                INNER JOIN ingredients mapped_filter_i ON mapped_filter_i.id = filter_pi.ingredient_id
                                WHERE LOWER(TRIM(mapped_filter_i.name)) = LOWER(TRIM(i.name))
                                    AND filter_pi.amount > 0
                        )";
            $result = $connect->query(
                "SELECT i.id, i.name, i.category, i.unit, i.stock, i.min_stock, {$maxStockSelect} AS max_stock, i.branch_id,
                        COUNT(DISTINCT pi.id) AS mapping_count,
                        MAX(pi.amount) AS required_per_serving,
                        GROUP_CONCAT(DISTINCT pi.product_name ORDER BY pi.product_name SEPARATOR ', ') AS mapped_products
                 FROM ingredients i
                                 LEFT JOIN product_ingredients pi ON EXISTS (
                                         SELECT 1 FROM ingredients mapped_i
                                         WHERE mapped_i.id = pi.ingredient_id
                                             AND LOWER(TRIM(mapped_i.name)) = LOWER(TRIM(i.name))
                                 )
                 WHERE i.branch_id = {$branchId} {$mappedFilter}
                 GROUP BY i.id, i.name, i.category, i.unit, i.stock, i.min_stock, i.branch_id {$maxStockGroup}
                 ORDER BY i.name, i.id"
            );
            $ingredients = [];
            while ($row = $result->fetch_assoc()) {
                $requiredPerServing = (float) ($row['required_per_serving'] ?? 0);
                $row['servings_left'] = $requiredPerServing > 0
                    ? max(0, (int) floor(max(0, (float) $row['stock']) / $requiredPerServing))
                    : null;
                $row['sufficiency_status'] = boycold_inventory_ingredient_status(
                    (float) $row['stock'],
                    (float) $row['min_stock']
                );
                $row['sufficiency_label'] = ucfirst($row['sufficiency_status']);
                $ingredients[] = $row;
            }
            response(['success' => true, 'ingredients' => $ingredients, 'branch_id' => $branchId, 'view' => $view]);

        case 'ingredient_library':
            $result = $connect->query(
                "SELECT name, unit
                 FROM ingredients
                 WHERE name <> ''
                 ORDER BY name, id"
            );
            $library = [];
            $seen = [];
            while ($result && ($row = $result->fetch_assoc())) {
                $key = boycold_inventory_duplicate_key((string) $row['name']);
                if ($key === '' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $library[] = [
                    'name' => $row['name'],
                    'unit' => $row['unit'],
                ];
            }
            response(['success' => true, 'ingredients' => $library]);

        case 'branches':
            $result = $connect->query("SELECT id, branch_code, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name, id");
            $branches = [];
            while ($row = $result->fetch_assoc()) $branches[] = $row;
            response(['success' => true, 'branches' => $branches]);

        case 'products':
            $result = $connect->query(
                "SELECT p.id, p.product_name, p.description, p.price, p.image, p.category, p.popular_category, p.is_available, p.addons_configured, p.milk_choices_configured,
                        COUNT(pi.id) AS mapping_count
                 FROM products p
                 LEFT JOIN product_ingredients pi ON pi.product_name = p.product_name
                 GROUP BY p.id, p.product_name, p.description, p.price, p.image, p.category, p.popular_category, p.is_available, p.addons_configured, p.milk_choices_configured
                 ORDER BY p.category, p.product_name"
            );
            $products = [];
            while ($row = $result->fetch_assoc()) $products[] = $row;
            $modifiersByProduct = boycold_menu_get_product_modifiers($connect, array_column($products, 'id'));
            $branchId = isset($_GET['branch_id']) ? (int) $_GET['branch_id'] : 0;
            $availability = boycold_get_product_inventory_availability(
                $connect,
                $branchId,
                array_column($products, 'product_name')
            );
            foreach ($products as &$product) {
                $info = $availability[boycold_inventory_normalize_name((string) $product['product_name'])] ?? [];
                $product['inventory_status'] = $info['status'] ?? 'unavailable';
                $product['inventory_label'] = $info['status_label'] ?? 'Unavailable';
                $product['ingredient_status'] = $info['ingredient_status'] ?? 'No mapping';
                $product['inventory_reason'] = $info['reason'] ?? '';
                $product['available_servings'] = $info['available_servings'] ?? 0;
                $product['inventory_can_order'] = !empty($info['can_order']);
                $product['ingredient_details'] = $info['ingredients'] ?? [];
                $product['addons_configured'] = !empty($product['addons_configured']);
                $product['milk_choices_configured'] = !empty($product['milk_choices_configured']);
                $modifiers = $modifiersByProduct[(int) $product['id']] ?? ['addons' => [], 'milk_choices' => []];
                $product['addons'] = $modifiers['addons'];
                $product['milk_choices'] = $modifiers['milk_choices'];
            }
            unset($product);
            response([
                'success' => true,
                'products' => $products,
                'categories' => boycold_menu_get_categories($connect),
            ]);

        case 'menu_categories_sync':
            $requestedCategories = $data['categories'] ?? null;
            if (!is_array($requestedCategories) || !$requestedCategories) {
                response(['success' => false, 'error' => 'Keep at least one menu category.'], 422);
            }

            $existingCategories = boycold_menu_get_categories($connect, false);
            $existingBySlug = [];
            foreach ($existingCategories as $existingCategory) {
                $existingBySlug[(string) $existingCategory['slug']] = $existingCategory;
            }

            $categories = [];
            foreach ($requestedCategories as $position => $requestedCategory) {
                if (!is_array($requestedCategory)) {
                    response(['success' => false, 'error' => 'Invalid menu category.'], 422);
                }
                $slug = boycold_menu_category_slug((string) ($requestedCategory['slug'] ?? $requestedCategory['name'] ?? ''));
                if ($slug === '') {
                    response(['success' => false, 'error' => 'Each category needs a valid name.'], 422);
                }
                if (isset($categories[$slug])) {
                    response(['success' => false, 'error' => 'Category names must be unique.'], 422);
                }
                $categories[$slug] = [
                    'slug' => $slug,
                    'name' => boycold_menu_category_label((string) ($requestedCategory['name'] ?? ''), $slug),
                    'display_order' => (int) $position,
                ];
            }

            $usedCategorySlugs = [];
            $productCategories = $connect->query("SELECT DISTINCT category FROM products WHERE TRIM(COALESCE(category, '')) <> ''");
            while ($productCategories && ($productCategory = $productCategories->fetch_assoc())) {
                $usedSlug = boycold_menu_category_slug((string) $productCategory['category']);
                if ($usedSlug !== '') {
                    $usedCategorySlugs[$usedSlug] = true;
                }
            }
            foreach ($existingBySlug as $slug => $existingCategory) {
                if ((int) $existingCategory['is_active'] === 1 && !isset($categories[$slug]) && !empty($usedCategorySlugs[$slug])) {
                    response([
                        'success' => false,
                        'error' => "The {$existingCategory['name']} category still has menu items. Move or delete those items before removing the category.",
                    ], 422);
                }
            }

            $connect->begin_transaction();
            try {
                $updateCategory = $connect->prepare(
                    'UPDATE menu_categories SET name = ?, display_order = ?, is_active = 1 WHERE slug = ?'
                );
                $insertCategory = $connect->prepare(
                    'INSERT INTO menu_categories (slug, name, display_order, is_active) VALUES (?, ?, ?, 1)'
                );
                foreach ($categories as $category) {
                    if (isset($existingBySlug[$category['slug']])) {
                        $updateCategory->bind_param('sis', $category['name'], $category['display_order'], $category['slug']);
                        if (!$updateCategory->execute()) {
                            throw new RuntimeException('Could not update a menu category.');
                        }
                    } else {
                        $insertCategory->bind_param('ssi', $category['slug'], $category['name'], $category['display_order']);
                        if (!$insertCategory->execute()) {
                            throw new RuntimeException('Could not save a menu category.');
                        }
                    }
                }
                $updateCategory->close();
                $insertCategory->close();

                $deactivateCategory = $connect->prepare('UPDATE menu_categories SET is_active = 0 WHERE slug = ?');
                foreach ($existingBySlug as $slug => $existingCategory) {
                    if ((int) $existingCategory['is_active'] === 1 && !isset($categories[$slug])) {
                        $deactivateCategory->bind_param('s', $slug);
                        if (!$deactivateCategory->execute()) {
                            throw new RuntimeException('Could not remove a menu category.');
                        }
                    }
                }
                $deactivateCategory->close();
                $connect->commit();
            } catch (Throwable $error) {
                $connect->rollback();
                throw $error;
            }

            response(['success' => true, 'categories' => boycold_menu_get_categories($connect)]);

        case 'orders':
            boycold_ensure_order_void_schema($connect);
                 $result = $connect->query("SELECT o.id, o.user_id, o.user_name,
                                    COALESCE(NULLIF(CONCAT_WS(' ', u.firstname, u.lastname), ''), o.user_name) AS customer_name,
                                    u.email AS customer_email,
                                    o.status, o.voided_at, o.order_type, o.payment_method, o.payment_status,
                                              o.payment_reference, o.subtotal, o.delivery_fee, o.tax, o.total,
                                              o.branch_id, b.branch_code, b.branch_name,
                                              o.cashier_id, e.employee_name AS cashier_name,
                                              o.address, o.notes,
                                              o.created_at, o.updated_at
                                       FROM orders o
                                LEFT JOIN users u ON u.id = o.user_id
                                       LEFT JOIN branches b ON b.id = o.branch_id
                                       LEFT JOIN employees e ON e.id = o.cashier_id
                                      WHERE o.voided_at IS NULL
                                       ORDER BY o.created_at DESC, o.id DESC");
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
            $category = boycold_menu_category_slug(requireValue($data, 'category'));
            if ($category === '') response(['success' => false, 'error' => 'Choose a valid category.'], 422);
            boycold_menu_ensure_category($connect, $category);
            $price = max(0, (float)($data['price'] ?? 0));
            $addons = boycold_menu_normalize_addons($data['addons'] ?? []);
            $milkChoices = boycold_menu_normalize_addons($data['milk_choices'] ?? []);
            $uploadedImage = boycold_menu_store_uploaded_image($_FILES['image_file'] ?? null);
            $image = $uploadedImage ?? '';
            $available = !empty($data['is_available']) ? 1 : 0;
            $addonsConfigured = 1;
            $milkChoicesConfigured = 1;
            $stmt = $connect->prepare('INSERT INTO products (product_name, description, price, image, category, is_available, addons_configured, milk_choices_configured) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            if (!$stmt) {
                if ($uploadedImage) boycold_menu_remove_uploaded_image($uploadedImage);
                response(['success' => false, 'error' => 'Could not prepare menu item insert: ' . $connect->error], 500);
            }
            $description = '';
            $stmt->bind_param('ssdssiii', $name, $description, $price, $image, $category, $available, $addonsConfigured, $milkChoicesConfigured);
            $saved = false;
            try {
                $saved = $stmt->execute();
            } catch (Throwable $error) {
                $stmt->close();
                if ($uploadedImage) boycold_menu_remove_uploaded_image($uploadedImage);
                response(['success' => false, 'error' => 'Menu item could not be saved: ' . $error->getMessage()], 409);
            }
            if (!$saved) {
                $error = $stmt->error ?: $connect->error;
                $stmt->close();
                if ($uploadedImage) boycold_menu_remove_uploaded_image($uploadedImage);
                response(['success' => false, 'error' => 'Menu item could not be saved: ' . $error], 409);
            }
            $newProductId = (int) $stmt->insert_id;
            $stmt->close();
            if ($newProductId < 1) {
                if ($uploadedImage) boycold_menu_remove_uploaded_image($uploadedImage);
                response(['success' => false, 'error' => 'Menu item was not saved because no database ID was returned.'], 500);
            }
            try {
                boycold_menu_save_product_addons($connect, $newProductId, $addons);
                boycold_menu_save_product_milk_choices($connect, $newProductId, $milkChoices);
            } catch (Throwable $error) {
                $cleanup = $connect->prepare('DELETE FROM products WHERE id = ?');
                if ($cleanup) {
                    $cleanup->bind_param('i', $newProductId);
                    $cleanup->execute();
                    $cleanup->close();
                }
                if ($uploadedImage) boycold_menu_remove_uploaded_image($uploadedImage);
                response(['success' => false, 'error' => 'Menu item add-ons could not be saved: ' . $error->getMessage()], 409);
            }
            response(['success' => true, 'id' => $newProductId, 'product_name' => $name, 'addons' => $addons, 'milk_choices' => $milkChoices]);

        case 'product_update':
            $id = (int)($data['id'] ?? 0);
            if ($id < 1) response(['success' => false, 'error' => 'Invalid menu item.'], 422);
            $name = requireValue($data, 'product_name');
            $category = boycold_menu_category_slug(requireValue($data, 'category'));
            if ($category === '') response(['success' => false, 'error' => 'Choose a valid category.'], 422);
            boycold_menu_ensure_category($connect, $category);
            $price = max(0, (float)($data['price'] ?? 0));
            $addonsProvided = array_key_exists('addons', $data);
            $addons = $addonsProvided ? boycold_menu_normalize_addons($data['addons']) : [];
            $milkChoicesProvided = array_key_exists('milk_choices', $data);
            $milkChoices = $milkChoicesProvided ? boycold_menu_normalize_addons($data['milk_choices']) : [];
            $available = !empty($data['is_available']) ? 1 : 0;
            $currentStmt = $connect->prepare('SELECT id, image, is_available, addons_configured, milk_choices_configured FROM products WHERE id = ? LIMIT 1');
            $currentStmt->bind_param('i', $id);
            $currentStmt->execute();
            $currentProduct = $currentStmt->get_result()->fetch_assoc();
            $currentStmt->close();
            if (!$currentProduct) response(['success' => false, 'error' => 'Menu item was not found.'], 404);
            $uploadedImage = boycold_menu_store_uploaded_image($_FILES['image_file'] ?? null);
            $removeImage = !empty($data['remove_image']);
            $image = $uploadedImage ?? ($removeImage ? '' : (string) ($currentProduct['image'] ?? ''));
            $addonsConfigured = $addonsProvided ? 1 : (int) ($currentProduct['addons_configured'] ?? 0);
            $milkChoicesConfigured = $milkChoicesProvided ? 1 : (int) ($currentProduct['milk_choices_configured'] ?? 0);
            $stmt = $connect->prepare('UPDATE products SET product_name = ?, category = ?, price = ?, image = ?, is_available = ?, addons_configured = ?, milk_choices_configured = ? WHERE id = ?');
            $stmt->bind_param('ssdsiiii', $name, $category, $price, $image, $available, $addonsConfigured, $milkChoicesConfigured, $id);
            $updated = false;
            try {
                $updated = $stmt->execute();
            } catch (Throwable $error) {
                $stmt->close();
                if ($uploadedImage) boycold_menu_remove_uploaded_image($uploadedImage);
                response(['success' => false, 'error' => 'Menu item could not be updated: ' . $error->getMessage()], 409);
            }
            if (!$updated) {
                $error = $stmt->error ?: $connect->error;
                $stmt->close();
                if ($uploadedImage) boycold_menu_remove_uploaded_image($uploadedImage);
                response(['success' => false, 'error' => 'Menu item could not be updated: ' . $error], 409);
            }
            $stmt->close();
            if ($addonsProvided) {
                boycold_menu_save_product_addons($connect, $id, $addons);
            }
            if ($milkChoicesProvided) {
                boycold_menu_save_product_milk_choices($connect, $id, $milkChoices);
            }
            if (($uploadedImage || $removeImage) && $image !== (string) ($currentProduct['image'] ?? '')) {
                boycold_menu_remove_uploaded_image((string) ($currentProduct['image'] ?? ''));
            }
            response(['success' => true]);

        case 'product_delete':
            $id = (int)($data['id'] ?? 0);
            $productName = trim((string) ($data['product_name'] ?? ''));
            if ($id < 1 && $productName === '') {
                response(['success' => false, 'error' => 'Product ID or product name is required.'], 422);
            }
            if ($id < 1) {
                $lookupStmt = $connect->prepare(
                    'SELECT id FROM products WHERE LOWER(TRIM(product_name)) = LOWER(TRIM(?)) LIMIT 1'
                );
                if (!$lookupStmt) {
                    response(['success' => false, 'error' => 'Could not prepare product lookup: ' . $connect->error], 500);
                }
                $lookupStmt->bind_param('s', $productName);
                $lookupStmt->execute();
                $id = (int) ($lookupStmt->get_result()->fetch_assoc()['id'] ?? 0);
                $lookupStmt->close();
                if ($id < 1) {
                    response(['success' => false, 'error' => 'Product was not found in the database.'], 404);
                }
            }
            $stmt = $connect->prepare('DELETE FROM products WHERE id = ?');
            if (!$stmt) {
                response(['success' => false, 'error' => 'Could not prepare product deletion: ' . $connect->error], 500);
            }
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                $error = $stmt->error ?: $connect->error;
                $stmt->close();
                response(['success' => false, 'error' => 'Product could not be deleted: ' . $error], 409);
            }
            $deleted = $stmt->affected_rows;
            $stmt->close();
            if ($deleted === 1) {
                boycold_log_activity($connect, [
                    'category' => 'menu',
                    'action' => 'product_deleted',
                    'summary' => 'Menu Item Deleted',
                    'details' => $productName !== ''
                        ? $productName . ' was removed from the menu.'
                        : 'Menu item #' . $id . ' was removed from the menu.',
                    'actor_type' => 'admin',
                    'entity_type' => 'product',
                    'entity_id' => $id,
                ]);
            }
            response(['success' => $deleted === 1, 'error' => $deleted === 1 ? null : 'Product was not found.']);

        case 'ingredient_create':
            $name = requireValue($data, 'name');
            $category = requireValue($data, 'category');
            $unit = requireValue($data, 'unit');
            $stock = (float)($data['stock'] ?? 0);
            $minStock = max(0, (float)($data['min_stock'] ?? 0));
            $branch = isset($data['branch_id']) && $data['branch_id'] !== ''
                ? (int) $data['branch_id']
                : (int) ($_SESSION['branch_id'] ?? 1);
            if ($branch < 1) $branch = 1;
            $duplicate = findDuplicateIngredient($connect, $name, $branch);
            if ($duplicate) {
                $sameUnit = strtolower((string) $duplicate['unit']) === strtolower($unit);
                response([
                    'success' => false,
                    'error' => $sameUnit
                        ? "Possible duplicate: {$duplicate['name']} already exists in this branch with the same unit."
                        : "Possible duplicate: {$duplicate['name']} already exists in this branch with unit {$duplicate['unit']}. Review it before creating another ingredient."
                ], 409);
            }
            $stmt = $connect->prepare('INSERT INTO ingredients (name, category, unit, stock, min_stock, branch_id) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssddi', $name, $category, $unit, $stock, $minStock, $branch);
            $stmt->execute();
            $newId = (int) $connect->insert_id;
            boycold_inventory_replicate_ingredient_catalog($connect, $name, $category, $unit, $minStock, $branch);
            response(['success' => true, 'id' => $newId]);

        case 'ingredient_update':
            $id = (int)($data['id'] ?? 0);
            if ($id < 1) response(['success' => false, 'error' => 'Invalid ingredient.'], 422);
            $unit = validateIngredientUnit($data);
            $branchId = isset($data['branch_id']) && (int) $data['branch_id'] > 0
                ? (int) $data['branch_id']
                : (int) ($_SESSION['branch_id'] ?? 0);
            if ($branchId > 0) {
                $stmt = $connect->prepare('UPDATE ingredients SET unit = ? WHERE id = ? AND branch_id = ?');
                $stmt->bind_param('sii', $unit, $id, $branchId);
            } else {
                $stmt = $connect->prepare('UPDATE ingredients SET unit = ? WHERE id = ?');
                $stmt->bind_param('si', $unit, $id);
            }
            if (!$stmt->execute()) {
                response(['success' => false, 'error' => 'Unable to update the unit of measure.'], 500);
            }
            if ($stmt->affected_rows < 1) {
                $exists = $connect->prepare('SELECT id FROM ingredients WHERE id = ? LIMIT 1');
                $exists->bind_param('i', $id);
                $exists->execute();
                if (!$exists->get_result()->fetch_assoc()) {
                    response(['success' => false, 'error' => 'Ingredient not found.'], 404);
                }
            }
            response(['success' => true, 'message' => 'Ingredient unit updated successfully.']);

        case 'ingredient_delete':
            $id = (int)($data['id'] ?? 0);
            $branchId = isset($data['branch_id']) && (int) $data['branch_id'] > 0
                ? (int) $data['branch_id']
                : (int) ($_SESSION['branch_id'] ?? 0);
            if ($branchId > 0) {
                $stmt = $connect->prepare('DELETE FROM ingredients WHERE id = ? AND branch_id = ?');
                $stmt->bind_param('ii', $id, $branchId);
            } else {
                $stmt = $connect->prepare('DELETE FROM ingredients WHERE id = ?');
                $stmt->bind_param('i', $id);
            }
            $stmt->execute();
            response(['success' => true]);

        case 'stock_in':
            $items = $data['items'] ?? [];
            if (!is_array($items) || !$items) response(['success' => false, 'error' => 'No stock items supplied'], 422);
            $branchId = isset($data['branch_id']) && (int) $data['branch_id'] > 0
                ? (int) $data['branch_id']
                : (int) ($_SESSION['branch_id'] ?? 1);
            $connect->begin_transaction();
            $update = $connect->prepare('UPDATE ingredients SET stock = stock + ? WHERE id = ? AND branch_id = ?');
            $movement = $connect->prepare("INSERT INTO ingredient_stock_movements (ingredient_id, movement_type, quantity, resulting_stock) SELECT id, 'stock_in', ?, stock FROM ingredients WHERE id = ? AND branch_id = ?");
            foreach ($items as $item) {
                $id = (int)($item['id'] ?? 0);
                if ($id < 1 && !empty($item['name'])) {
                    $lookup = $connect->prepare('SELECT id FROM ingredients WHERE name = ? AND branch_id = ? LIMIT 1');
                    $lookup->bind_param('si', $item['name'], $branchId);
                    $lookup->execute();
                    $id = (int)($lookup->get_result()->fetch_assoc()['id'] ?? 0);
                }
                $quantity = (float)($item['quantity'] ?? 0);
                if ($id < 1 || $quantity <= 0) continue;
                $update->bind_param('dii', $quantity, $id, $branchId);
                $update->execute();
                $movement->bind_param('dii', $quantity, $id, $branchId);
                $movement->execute();
            }
            $connect->commit();
            response(['success' => true]);

        case 'stock_history':
            $requestedBranch = filter_input(INPUT_GET, 'branch_id', FILTER_VALIDATE_INT);
            $branchId = $requestedBranch && $requestedBranch > 0
                ? $requestedBranch
                : (int) ($_SESSION['branch_id'] ?? 1);
            $result = $connect->query("SELECT m.id, i.name, i.unit, m.movement_type, m.quantity, m.resulting_stock,
                                              m.order_id, m.source, m.product_name, m.reference, m.created_at
                                       FROM ingredient_stock_movements m JOIN ingredients i ON i.id = m.ingredient_id
                                       WHERE i.branch_id = {$branchId}
                                       ORDER BY m.created_at DESC LIMIT 200");
            $history = [];
            while ($row = $result->fetch_assoc()) $history[] = $row;
            response(['success' => true, 'history' => $history]);

        case 'mapping_get':
            ensureProductIngredientsTable($connect);
            $product = requireValue($_GET, 'product_name');
            $stmt = $connect->prepare('SELECT pi.ingredient_id, i.name AS ingredient, i.unit, pi.amount FROM product_ingredients pi JOIN ingredients i ON i.id = pi.ingredient_id WHERE pi.product_name = ? ORDER BY pi.id');
            $stmt->bind_param('s', $product);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            response(['success' => true, 'mapping' => $rows]);

        case 'mapping_save':
            ensureProductIngredientsTable($connect);
            $product = requireValue($data, 'product_name');
            $rows = $data['rows'] ?? [];
            if (!is_array($rows)) response(['success' => false, 'error' => 'Invalid mapping rows'], 422);
            $connect->begin_transaction();
            $delete = $connect->prepare('DELETE FROM product_ingredients WHERE product_name = ?');
            if (!$delete) {
                throw new RuntimeException('Could not prepare mapping deletion: ' . $connect->error);
            }
            $delete->bind_param('s', $product);
            if (!$delete->execute()) {
                throw new RuntimeException('Could not delete the existing mapping: ' . $delete->error);
            }

            $lookup = $connect->prepare(
                'SELECT id FROM ingredients WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) ORDER BY id LIMIT 1'
            );
            if (!$lookup) {
                throw new RuntimeException('Could not prepare ingredient lookup: ' . $connect->error);
            }

            $insert = $connect->prepare('INSERT INTO product_ingredients (product_name, ingredient_id, amount) VALUES (?, ?, ?)');
            if (!$insert) {
                throw new RuntimeException('Could not prepare mapping insert: ' . $connect->error);
            }

            $savedRows = 0;
            foreach ($rows as $row) {
                $ingredientId = (int)($row['ingredient_id'] ?? 0);
                if ($ingredientId < 1 && !empty($row['ingredient'])) {
                    $ingredientName = trim((string)$row['ingredient']);
                    $lookup->bind_param('s', $ingredientName);
                    $lookup->execute();
                    $ingredientId = (int)($lookup->get_result()->fetch_assoc()['id'] ?? 0);
                }
                $amount = (float)($row['amount'] ?? 0);
                if ($ingredientId < 1 || $amount <= 0) {
                    throw new RuntimeException('Each mapping must have a valid ingredient and quantity greater than zero.');
                }
                $insert->bind_param('sid', $product, $ingredientId, $amount);
                if (!$insert->execute()) {
                    throw new RuntimeException('Could not save the ingredient mapping: ' . $insert->error);
                }
                $savedRows++;
            }
            $connect->commit();
            response(['success' => true, 'saved_rows' => $savedRows]);

        default:
            response(['success' => false, 'error' => 'Invalid action'], 400);
    }
} catch (Throwable $e) {
    if ($connect->errno) $connect->rollback();
    error_log('Admin data API: ' . $e->getMessage());
    $message = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Request could not be completed';
    response(['success' => false, 'error' => $message], 500);
}
