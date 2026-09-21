<?php

function boycold_inventory_normalize_name(string $name): string
{
    $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $name = preg_replace('/\s+/', ' ', trim($name));
    return strtolower($name);
}

function boycold_inventory_duplicate_key(string $name): string
{
    $normalized = boycold_inventory_normalize_name($name);
    $aliases = [
        'whipping cream' => 'whipped cream',
        'condense' => 'condensed milk',
        'condensed' => 'condensed milk',
    ];

    return $aliases[$normalized] ?? $normalized;
}

function boycold_inventory_clean_label(string $label): string
{
    $label = html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $label = preg_replace('/\s+/', ' ', trim($label));
    $label = preg_replace('/\s*\+\s*(?:PHP|P)?\s*\d+(?:\.\d{1,2})?\s*$/i', '', $label);
    return trim((string) $label);
}

function boycold_inventory_identifier(string $identifier): string
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
        throw new InvalidArgumentException('Invalid database identifier.');
    }

    return '`' . $identifier . '`';
}

function boycold_inventory_table_exists(mysqli $connect, string $table): bool
{
    $safe = $connect->real_escape_string($table);
    $result = $connect->query("SHOW TABLES LIKE '{$safe}'");
    return $result && $result->num_rows > 0;
}

function boycold_inventory_column_exists(mysqli $connect, string $table, string $column): bool
{
    $safeColumn = $connect->real_escape_string($column);
    $result = $connect->query('SHOW COLUMNS FROM ' . boycold_inventory_identifier($table) . " LIKE '{$safeColumn}'");
    return $result && $result->num_rows > 0;
}

function boycold_inventory_index_exists(mysqli $connect, string $table, string $index): bool
{
    $safeIndex = $connect->real_escape_string($index);
    $result = $connect->query('SHOW INDEX FROM ' . boycold_inventory_identifier($table) . " WHERE Key_name = '{$safeIndex}'");
    return $result && $result->num_rows > 0;
}

function boycold_inventory_add_column_if_missing(
    mysqli $connect,
    string $table,
    string $column,
    string $definition
): void {
    if (!boycold_inventory_column_exists($connect, $table, $column)) {
        $connect->query(
            'ALTER TABLE ' . boycold_inventory_identifier($table) .
            ' ADD COLUMN ' . boycold_inventory_identifier($column) . ' ' . $definition
        );
    }
}

function boycold_inventory_add_index_if_missing(
    mysqli $connect,
    string $table,
    string $index,
    string $columns
): void {
    if (!boycold_inventory_index_exists($connect, $table, $index)) {
        $connect->query(
            'ALTER TABLE ' . boycold_inventory_identifier($table) .
            ' ADD INDEX ' . boycold_inventory_identifier($index) . ' (' . $columns . ')'
        );
    }
}

function boycold_ensure_inventory_schema(mysqli $connect): void
{
    static $checked = [];
    $key = spl_object_id($connect);
    if (!empty($checked[$key])) {
        return;
    }
    $checked[$key] = true;

    $connect->query(
        "CREATE TABLE IF NOT EXISTS product_ingredients (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_name VARCHAR(150) NOT NULL,
            ingredient_id INT UNSIGNED NOT NULL,
            amount DECIMAL(10,3) NOT NULL DEFAULT 0.000,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_product_ingredient (product_name, ingredient_id),
            KEY idx_product_name (product_name),
            KEY idx_ingredient_id (ingredient_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if (boycold_inventory_table_exists($connect, 'ingredients')) {
        boycold_inventory_add_column_if_missing(
            $connect,
            'ingredients',
            'category',
            "VARCHAR(80) NOT NULL DEFAULT 'Other'"
        );
        boycold_inventory_add_column_if_missing(
            $connect,
            'ingredients',
            'min_stock',
            'DECIMAL(10,3) NOT NULL DEFAULT 0.000'
        );
        boycold_inventory_add_index_if_missing(
            $connect,
            'ingredients',
            'idx_ingredients_branch_name',
            '`branch_id`, `name`'
        );
    }

    $connect->query(
        "CREATE TABLE IF NOT EXISTS ingredient_stock_movements (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            ingredient_id INT UNSIGNED NOT NULL,
            movement_type ENUM('stock_in','deduction','adjustment') NOT NULL,
            quantity DECIMAL(10,3) NOT NULL,
            resulting_stock DECIMAL(10,3) NOT NULL,
            order_id INT NULL DEFAULT NULL,
            source VARCHAR(30) NULL DEFAULT NULL,
            product_name VARCHAR(150) NULL DEFAULT NULL,
            reference VARCHAR(120) NULL DEFAULT NULL,
            created_by INT NULL DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ingredient_stock_movements_ingredient (ingredient_id),
            KEY idx_stock_movement_ingredient_id (ingredient_id),
            KEY idx_stock_movements_order (order_id),
            KEY idx_stock_movements_source (source)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    boycold_inventory_add_column_if_missing($connect, 'ingredient_stock_movements', 'order_id', 'INT NULL DEFAULT NULL');
    boycold_inventory_add_column_if_missing($connect, 'ingredient_stock_movements', 'source', 'VARCHAR(30) NULL DEFAULT NULL');
    boycold_inventory_add_column_if_missing($connect, 'ingredient_stock_movements', 'product_name', 'VARCHAR(150) NULL DEFAULT NULL');
    boycold_inventory_add_column_if_missing($connect, 'ingredient_stock_movements', 'reference', 'VARCHAR(120) NULL DEFAULT NULL');
    boycold_inventory_add_column_if_missing($connect, 'ingredient_stock_movements', 'created_by', 'INT NULL DEFAULT NULL');
    boycold_inventory_add_index_if_missing($connect, 'ingredient_stock_movements', 'idx_stock_movements_order', '`order_id`');
    boycold_inventory_add_index_if_missing($connect, 'ingredient_stock_movements', 'idx_stock_movements_source', '`source`');

    if (boycold_inventory_table_exists($connect, 'orders')) {
        boycold_inventory_add_column_if_missing($connect, 'orders', 'inventory_deducted_at', 'DATETIME NULL DEFAULT NULL');
        boycold_inventory_add_column_if_missing($connect, 'orders', 'inventory_restored_at', 'DATETIME NULL DEFAULT NULL');
        boycold_inventory_add_column_if_missing($connect, 'orders', 'inventory_deduction_source', 'VARCHAR(30) NULL DEFAULT NULL');
        boycold_inventory_add_column_if_missing($connect, 'orders', 'inventory_deduction_error', 'VARCHAR(255) NULL DEFAULT NULL');
        boycold_inventory_add_index_if_missing($connect, 'orders', 'idx_orders_inventory_deducted_at', '`inventory_deducted_at`');
    }

    boycold_inventory_sync_branch_ingredient_rows($connect);
}

function boycold_inventory_active_branch_ids(mysqli $connect): array
{
    $ids = [];
    if (!boycold_inventory_table_exists($connect, 'branches')) {
        return $ids;
    }

    $result = $connect->query("SELECT id FROM branches WHERE status = 'active' ORDER BY id");
    while ($result && ($row = $result->fetch_assoc())) {
        $id = (int) ($row['id'] ?? 0);
        if ($id > 0) {
            $ids[] = $id;
        }
    }

    return $ids;
}

function boycold_inventory_sync_branch_ingredient_rows(mysqli $connect): void
{
    if (!boycold_inventory_table_exists($connect, 'ingredients')) {
        return;
    }

    $connect->query('UPDATE ingredients SET branch_id = 1 WHERE branch_id IS NULL OR branch_id <= 0');

    $branchIds = boycold_inventory_active_branch_ids($connect);
    if (!$branchIds) {
        return;
    }

    $hasCategory = boycold_inventory_column_exists($connect, 'ingredients', 'category');
    $hasMinStock = boycold_inventory_column_exists($connect, 'ingredients', 'min_stock');
    $hasMaxStock = boycold_inventory_column_exists($connect, 'ingredients', 'max_stock');

    $catalog = [];
    $existing = [];
    $result = $connect->query('SELECT * FROM ingredients ORDER BY id');
    while ($result && ($row = $result->fetch_assoc())) {
        $name = (string) ($row['name'] ?? '');
        $key = boycold_inventory_duplicate_key($name);
        if ($key === '') {
            continue;
        }
        if (!isset($catalog[$key])) {
            $catalog[$key] = $row;
        }
        $branchId = (int) ($row['branch_id'] ?? 0);
        if ($branchId > 0) {
            $existing[$branchId . '|' . $key] = true;
        }
    }

    if (!$catalog) {
        return;
    }

    $columns = ['name', 'unit', 'branch_id', 'stock'];
    $values = ['?', '?', '?', '0'];
    $types = 'ssi';
    if ($hasCategory) {
        array_splice($columns, 1, 0, ['category']);
        array_splice($values, 1, 0, ['?']);
        $types = 'sssi';
    }
    if ($hasMinStock) {
        $columns[] = 'min_stock';
        $values[] = '?';
        $types .= 'd';
    }
    if ($hasMaxStock) {
        $columns[] = 'max_stock';
        $values[] = '?';
        $types .= 'd';
    }

    $insert = $connect->prepare(
        'INSERT INTO ingredients (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')'
    );

    foreach ($branchIds as $branchId) {
        foreach ($catalog as $key => $source) {
            if (!empty($existing[$branchId . '|' . $key])) {
                continue;
            }

            $name = (string) $source['name'];
            $unit = (string) ($source['unit'] ?? 'unit');
            $params = $hasCategory
                ? [$name, (string) ($source['category'] ?? 'Other'), $unit, $branchId]
                : [$name, $unit, $branchId];
            if ($hasMinStock) {
                $params[] = (float) ($source['min_stock'] ?? 0);
            }
            if ($hasMaxStock) {
                $params[] = (float) ($source['max_stock'] ?? 0);
            }

            boycold_inventory_bind($insert, $types, $params);
            $insert->execute();
            $existing[$branchId . '|' . $key] = true;
        }
    }

    $insert->close();
}

function boycold_inventory_replicate_ingredient_catalog(
    mysqli $connect,
    string $name,
    string $category,
    string $unit,
    float $minStock,
    int $sourceBranchId
): void {
    $branchIds = boycold_inventory_active_branch_ids($connect);
    $key = boycold_inventory_duplicate_key($name);
    if ($key === '' || !$branchIds) {
        return;
    }

    $hasCategory = boycold_inventory_column_exists($connect, 'ingredients', 'category');
    $hasMinStock = boycold_inventory_column_exists($connect, 'ingredients', 'min_stock');

    foreach ($branchIds as $branchId) {
        if ($branchId === $sourceBranchId) {
            continue;
        }

        $existing = $connect->prepare('SELECT id, name FROM ingredients WHERE branch_id = ?');
        $existing->bind_param('i', $branchId);
        $existing->execute();
        $found = false;
        $result = $existing->get_result();
        while ($row = $result->fetch_assoc()) {
            if (boycold_inventory_duplicate_key((string) $row['name']) === $key) {
                $found = true;
                break;
            }
        }
        $existing->close();
        if ($found) {
            continue;
        }

        if ($hasCategory && $hasMinStock) {
            $insert = $connect->prepare(
                'INSERT INTO ingredients (name, category, unit, stock, min_stock, branch_id) VALUES (?, ?, ?, 0, ?, ?)'
            );
            $insert->bind_param('sssdi', $name, $category, $unit, $minStock, $branchId);
        } elseif ($hasCategory) {
            $insert = $connect->prepare(
                'INSERT INTO ingredients (name, category, unit, stock, branch_id) VALUES (?, ?, ?, 0, ?)'
            );
            $insert->bind_param('sssi', $name, $category, $unit, $branchId);
        } else {
            $insert = $connect->prepare(
                'INSERT INTO ingredients (name, unit, stock, branch_id) VALUES (?, ?, 0, ?)'
            );
            $insert->bind_param('ssi', $name, $unit, $branchId);
        }
        $insert->execute();
        $insert->close();
    }
}

function boycold_inventory_bind(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types === '') {
        return;
    }

    $refs = [];
    foreach ($params as $index => $value) {
        $refs[$index] = &$params[$index];
    }
    $stmt->bind_param($types, ...$refs);
}

function boycold_inventory_fetch_products(mysqli $connect, array $productNames = []): array
{
    if ($productNames) {
        $keys = array_values(array_unique(array_map('boycold_inventory_normalize_name', $productNames)));
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $connect->prepare(
            "SELECT id, product_name, category, is_available
             FROM products
             WHERE LOWER(product_name) IN ($placeholders)"
        );
        boycold_inventory_bind($stmt, str_repeat('s', count($keys)), $keys);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $connect->query('SELECT id, product_name, category, is_available FROM products ORDER BY category, product_name');
    }

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[boycold_inventory_normalize_name((string) $row['product_name'])] = $row;
    }

    if (isset($stmt)) {
        $stmt->close();
    }

    return $products;
}

function boycold_inventory_fetch_mapping_rows(mysqli $connect, array $names): array
{
    if (!$names) {
        return [];
    }

    $keys = array_values(array_unique(array_map('boycold_inventory_normalize_name', $names)));
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $stmt = $connect->prepare(
        "SELECT
            pi.product_name,
            pi.ingredient_id,
            pi.amount,
            i.name AS ingredient_name,
            i.unit,
            i.branch_id,
            i.stock,
            i.min_stock
         FROM product_ingredients pi
         JOIN ingredients i ON i.id = pi.ingredient_id
         WHERE LOWER(pi.product_name) IN ($placeholders)
         ORDER BY pi.product_name, pi.id"
    );
    boycold_inventory_bind($stmt, str_repeat('s', count($keys)), $keys);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[boycold_inventory_normalize_name((string) $row['product_name'])][] = $row;
    }
    $stmt->close();

    return $rows;
}

function boycold_inventory_branch_ingredient_map(mysqli $connect, int $branchId): array
{
    static $maps = [];
    if ($branchId <= 0) {
        return [];
    }
    if (isset($maps[$branchId])) {
        return $maps[$branchId];
    }

    $map = [];
    $stmt = $connect->prepare(
        'SELECT id, name, unit, branch_id, stock, min_stock FROM ingredients WHERE branch_id = ? ORDER BY id'
    );
    $stmt->bind_param('i', $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $key = boycold_inventory_duplicate_key((string) ($row['name'] ?? ''));
        if ($key === '' || isset($map[$key])) {
            continue;
        }
        $map[$key] = $row;
    }
    $stmt->close();

    $maps[$branchId] = $map;
    return $map;
}

function boycold_inventory_resolve_ingredient_for_branch(mysqli $connect, array $mappingRow, int $branchId): array
{
    $name = (string) ($mappingRow['ingredient_name'] ?? '');
    $unavailable = [
        'id' => 0,
        'name' => $name,
        'unit' => (string) ($mappingRow['unit'] ?? ''),
        'branch_id' => $branchId,
        'stock' => 0.0,
        'min_stock' => 0.0,
        'branch_available' => false,
    ];

    if ($branchId <= 0 || $name === '') {
        return $unavailable;
    }

    $mappedBranchId = isset($mappingRow['branch_id']) ? (int) $mappingRow['branch_id'] : 0;
    if ($mappedBranchId === $branchId && (int) ($mappingRow['ingredient_id'] ?? 0) > 0) {
        return [
            'id' => (int) $mappingRow['ingredient_id'],
            'name' => $name,
            'unit' => (string) ($mappingRow['unit'] ?? ''),
            'branch_id' => $branchId,
            'stock' => (float) ($mappingRow['stock'] ?? 0),
            'min_stock' => (float) ($mappingRow['min_stock'] ?? 0),
            'branch_available' => true,
        ];
    }

    $row = boycold_inventory_branch_ingredient_map($connect, $branchId)[boycold_inventory_duplicate_key($name)] ?? null;
    if (!$row) {
        return $unavailable;
    }

    return [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'unit' => (string) ($row['unit'] ?? ''),
        'branch_id' => (int) ($row['branch_id'] ?? $branchId),
        'stock' => (float) ($row['stock'] ?? 0),
        'min_stock' => (float) ($row['min_stock'] ?? 0),
        'branch_available' => true,
    ];
}

/**
 * Resolve an ingredient against the combined stock of every active branch.
 *
 * This is for customer-facing browsing only, before the customer has chosen
 * a store. It must never be used to validate or deduct an order: both of
 * those operations continue to call the branch-specific helpers above.
 */
function boycold_inventory_resolve_ingredient_for_active_branches(mysqli $connect, array $mappingRow): array
{
    static $activeBranchIdsByConnection = [];

    $name = (string) ($mappingRow['ingredient_name'] ?? '');
    $key = boycold_inventory_duplicate_key($name);
    $connectionKey = spl_object_id($connect);
    if (!isset($activeBranchIdsByConnection[$connectionKey])) {
        $activeBranchIdsByConnection[$connectionKey] = boycold_inventory_active_branch_ids($connect);
    }
    $branchIds = $activeBranchIdsByConnection[$connectionKey];
    $totalStock = 0.0;
    $totalMinStock = 0.0;
    $matchedBranchIds = [];
    $unit = (string) ($mappingRow['unit'] ?? '');

    foreach ($branchIds as $branchId) {
        $row = boycold_inventory_branch_ingredient_map($connect, $branchId)[$key] ?? null;
        if (!$row) {
            continue;
        }

        $totalStock += max(0.0, (float) ($row['stock'] ?? 0));
        $totalMinStock += max(0.0, (float) ($row['min_stock'] ?? 0));
        $matchedBranchIds[] = $branchId;
        if ($unit === '') {
            $unit = (string) ($row['unit'] ?? '');
        }
    }

    return [
        'id' => 0,
        'name' => $name,
        'unit' => $unit,
        'branch_id' => 0,
        'stock' => $totalStock,
        'min_stock' => $totalMinStock,
        'branch_available' => !empty($matchedBranchIds),
        'matched_branch_ids' => $matchedBranchIds,
    ];
}

function boycold_inventory_ingredient_status(float $stock, float $minStock, float $required = 0): string
{
    if ($stock <= 0 || ($required > 0 && $stock + 0.0001 < $required)) {
        return 'insufficient';
    }
    if ($stock <= $minStock) {
        return 'low';
    }
    return 'sufficient';
}

/**
 * Return the number of complete servings an ingredient can still make.
 *
 * Stock is always read from the inventory record.  Invalid or negative stock
 * cannot produce a serving, so it is deliberately treated as zero here.
 * A non-positive recipe amount is not a valid serving calculation and returns
 * null instead of incorrectly reporting an out-of-stock ingredient.
 */
function boycold_inventory_remaining_servings(float $stock, float $requiredPerServing): ?int
{
    if (!is_finite($requiredPerServing) || $requiredPerServing <= 0) {
        return null;
    }

    $availableStock = is_finite($stock) && $stock > 0 ? $stock : 0.0;
    return max(0, (int) floor($availableStock / $requiredPerServing));
}

/**
 * Forecasting uses one serving threshold everywhere: no warning above 25,
 * restock at 10-25, and critical at 0-9 complete servings remaining.
 */
function boycold_inventory_restock_status(?int $remainingServings): string
{
    if ($remainingServings === null || $remainingServings > 25) {
        return 'ok';
    }

    return $remainingServings <= 9 ? 'critical' : 'soon';
}

function boycold_inventory_serving_status(int $remainingServings): string
{
    if ($remainingServings <= 0) {
        return 'unavailable';
    }
    if ($remainingServings < 15) {
        return 'low';
    }
    if ($remainingServings < 25) {
        return 'insufficient';
    }
    return 'sufficient';
}

/**
 * Build ingredient serving capacities from the live Inventory stock and the
 * existing product_ingredients recipe quantities.  When one ingredient is
 * used by more than one recipe, the largest quantity used by a single serving
 * is used so the displayed capacity never overstates what can be produced.
 */
function boycold_get_ingredient_restock_capacities(mysqli $connect, int $branchId = 0): array
{
    boycold_ensure_inventory_schema($connect);

    $mappingResult = $connect->query(
        "SELECT i.name AS ingredient_name, pi.amount
         FROM product_ingredients pi
         INNER JOIN ingredients i ON i.id = pi.ingredient_id
         WHERE pi.amount > 0"
    );

    $requiredByIngredientName = [];
    while ($mappingResult && ($row = $mappingResult->fetch_assoc())) {
        $name = (string) ($row['ingredient_name'] ?? '');
        $key = boycold_inventory_normalize_name($name);
        $amount = (float) ($row['amount'] ?? 0);
        if ($key === '' || !is_finite($amount) || $amount <= 0) {
            continue;
        }

        // A shared ingredient has no single product mix.  Use the most
        // demanding mapped serving as the safe, recipe-based capacity.
        if (!isset($requiredByIngredientName[$key]) || $amount > $requiredByIngredientName[$key]['amount']) {
            $requiredByIngredientName[$key] = [
                'name' => $name,
                'amount' => $amount,
            ];
        }
    }

    if (!$requiredByIngredientName) {
        return [];
    }

    if ($branchId > 0) {
        $ingredientStmt = $connect->prepare(
            'SELECT id, name, unit, branch_id, stock FROM ingredients WHERE branch_id = ? ORDER BY name, id'
        );
        $ingredientStmt->bind_param('i', $branchId);
        $ingredientStmt->execute();
        $ingredientResult = $ingredientStmt->get_result();
    } else {
        $ingredientResult = $connect->query(
            'SELECT id, name, unit, branch_id, stock FROM ingredients ORDER BY name, branch_id, id'
        );
    }

    $capacities = [];
    while ($ingredientResult && ($row = $ingredientResult->fetch_assoc())) {
        $key = boycold_inventory_normalize_name((string) ($row['name'] ?? ''));
        $recipe = $requiredByIngredientName[$key] ?? null;
        if (!$recipe) {
            // There is no mapped per-serving quantity, so a serving capacity
            // cannot be calculated from the inventory data.
            continue;
        }

        $rawStock = (float) ($row['stock'] ?? 0);
        $availableStock = is_finite($rawStock) && $rawStock > 0 ? $rawStock : 0.0;
        $remainingServings = boycold_inventory_remaining_servings($rawStock, (float) $recipe['amount']);
        if ($remainingServings === null) {
            continue;
        }

        $capacities[] = [
            'ingredient_id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'unit' => (string) ($row['unit'] ?? ''),
            'branch_id' => isset($row['branch_id']) ? (int) $row['branch_id'] : null,
            'stock' => round($availableStock, 3),
            'required_per_serving' => round((float) $recipe['amount'], 3),
            'remaining_servings' => $remainingServings,
            'branch_servings' => [$remainingServings],
            'status' => boycold_inventory_restock_status($remainingServings),
        ];
    }

    if (isset($ingredientStmt)) {
        $ingredientStmt->close();
    }

    usort($capacities, static function (array $a, array $b): int {
        $servingComparison = $a['remaining_servings'] <=> $b['remaining_servings'];
        return $servingComparison !== 0 ? $servingComparison : strcasecmp($a['name'], $b['name']);
    });

    return $capacities;
}

function boycold_inventory_add_requirement(array &$requirements, array $ingredient, float $amount, string $sourceName): void
{
    if ($amount <= 0 || empty($ingredient['id']) || ($ingredient['branch_available'] ?? true) === false) {
        return;
    }

    $ingredientId = (int) $ingredient['id'];
    if (!isset($requirements[$ingredientId])) {
        $requirements[$ingredientId] = [
            'ingredient_id' => $ingredientId,
            'branch_id' => (int) ($ingredient['branch_id'] ?? 0),
            'name' => (string) $ingredient['name'],
            'unit' => (string) ($ingredient['unit'] ?? ''),
            'required' => 0.0,
            'stock' => (float) ($ingredient['stock'] ?? 0),
            'min_stock' => (float) ($ingredient['min_stock'] ?? 0),
            'products' => [],
        ];
    }

    $requirements[$ingredientId]['required'] += $amount;
    $requirements[$ingredientId]['products'][$sourceName] =
        ($requirements[$ingredientId]['products'][$sourceName] ?? 0) + $amount;
}

function boycold_inventory_item_name(array $item): string
{
    foreach (['name', 'product_name', 'productName'] as $key) {
        $value = boycold_inventory_clean_label((string) ($item[$key] ?? ''));
        if ($value !== '') {
            return substr($value, 0, 150);
        }
    }

    return '';
}

function boycold_inventory_item_quantity(array $item): int
{
    foreach (['qty', 'quantity'] as $key) {
        if (isset($item[$key])) {
            return max(1, (int) $item[$key]);
        }
    }

    return 1;
}

function boycold_inventory_modifier_candidates(string $label): array
{
    $clean = boycold_inventory_clean_label($label);
    if ($clean === '') {
        return [];
    }

    $skip = ['original', 'original milk', 'no add-ons', 'no addons', 'no sauce', 'none', 'n/a'];
    if (in_array(boycold_inventory_normalize_name($clean), $skip, true)) {
        return [];
    }

    $candidates = [$clean];
    if (preg_match('/\s+Milk$/i', $clean)) {
        $candidates[] = trim((string) preg_replace('/\s+Milk$/i', '', $clean));
    }

    return array_values(array_unique(array_filter($candidates)));
}

function boycold_inventory_extract_modifier_groups(array $item): array
{
    $groups = [];

    $milk = (string) ($item['milk'] ?? '');
    if ($milk !== '') {
        $milkCandidates = boycold_inventory_modifier_candidates($milk);
        if ($milkCandidates) {
            $groups[] = ['label' => $milkCandidates[0], 'candidates' => $milkCandidates];
        }
    }

    $addons = $item['addons'] ?? '';
    $addonValues = [];
    if (is_array($addons)) {
        foreach ($addons as $addon) {
            if (is_array($addon)) {
                $addonValues[] = (string) ($addon['value'] ?? $addon['name'] ?? '');
            } else {
                $addonValues[] = (string) $addon;
            }
        }
    } elseif (is_string($addons) && trim($addons) !== '') {
        $decoded = json_decode($addons, true);
        if (is_array($decoded)) {
            foreach ($decoded as $addon) {
                if (is_array($addon)) {
                    $addonValues[] = (string) ($addon['value'] ?? $addon['name'] ?? '');
                } else {
                    $addonValues[] = (string) $addon;
                }
            }
        } else {
            $addonValues = preg_split('/[,;|]/', $addons) ?: [];
        }
    }

    foreach ($addonValues as $addonLabel) {
        $candidates = boycold_inventory_modifier_candidates((string) $addonLabel);
        if ($candidates) {
            $groups[] = ['label' => $candidates[0], 'candidates' => $candidates];
        }
    }

    return $groups;
}

function boycold_inventory_calculate_requirements(
    mysqli $connect,
    array $items,
    int $branchId,
    bool $requireBaseMappings = true
): array {
    boycold_ensure_inventory_schema($connect);

    $baseProducts = [];
    $modifierGroups = [];
    $allMappingNames = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $productName = boycold_inventory_item_name($item);
        if ($productName === '') {
            continue;
        }

        $qty = boycold_inventory_item_quantity($item);
        $productKey = boycold_inventory_normalize_name($productName);
        if (!isset($baseProducts[$productKey])) {
            $baseProducts[$productKey] = ['name' => $productName, 'qty' => 0];
        }
        $baseProducts[$productKey]['qty'] += $qty;
        $allMappingNames[] = $productName;

        foreach (boycold_inventory_extract_modifier_groups($item) as $group) {
            $group['qty'] = $qty;
            $group['base_product'] = $productName;
            $modifierGroups[] = $group;
            foreach ($group['candidates'] as $candidate) {
                $allMappingNames[] = $candidate;
            }
        }
    }

    $products = boycold_inventory_fetch_products($connect, array_column($baseProducts, 'name'));
    $mappingRows = boycold_inventory_fetch_mapping_rows($connect, $allMappingNames);
    $requirements = [];
    $missingMappings = [];
    $unavailableProducts = [];

    foreach ($baseProducts as $productKey => $product) {
        $catalogProduct = $products[$productKey] ?? null;
        if (!$catalogProduct) {
            $unavailableProducts[] = $product['name'] . ' is not in the product catalog.';
            continue;
        }
        if ((int) ($catalogProduct['is_available'] ?? 0) !== 1) {
            $unavailableProducts[] = $product['name'] . ' is disabled in Menu Management.';
            continue;
        }

        $rows = $mappingRows[$productKey] ?? [];
        if (!$rows) {
            if ($requireBaseMappings) {
                $missingMappings[] = $product['name'];
            }
            continue;
        }

        foreach ($rows as $row) {
            $amount = (float) ($row['amount'] ?? 0) * (int) $product['qty'];
            $ingredient = boycold_inventory_resolve_ingredient_for_branch($connect, $row, $branchId);
            if ((int) ($ingredient['id'] ?? 0) <= 0 || ($ingredient['branch_available'] ?? false) === false) {
                $unavailableProducts[] = $product['name'] . ' cannot use ' . ($ingredient['name'] ?: 'a required ingredient') . ' at this branch.';
                continue;
            }
            boycold_inventory_add_requirement($requirements, $ingredient, $amount, $product['name']);
        }
    }

    foreach ($modifierGroups as $group) {
        $selectedRows = [];
        $modifierName = '';
        foreach ($group['candidates'] as $candidate) {
            $candidateKey = boycold_inventory_normalize_name($candidate);
            if (!empty($mappingRows[$candidateKey])) {
                $selectedRows = $mappingRows[$candidateKey];
                $modifierName = $candidate;
                break;
            }
        }

        if (!$selectedRows) {
            continue;
        }

        $sourceName = $group['base_product'] . ' + ' . $modifierName;
        foreach ($selectedRows as $row) {
            $amount = (float) ($row['amount'] ?? 0) * (int) $group['qty'];
            $ingredient = boycold_inventory_resolve_ingredient_for_branch($connect, $row, $branchId);
            if ((int) ($ingredient['id'] ?? 0) <= 0 || ($ingredient['branch_available'] ?? false) === false) {
                $unavailableProducts[] = $sourceName . ' cannot use ' . ($ingredient['name'] ?: 'a required ingredient') . ' at this branch.';
                continue;
            }
            boycold_inventory_add_requirement($requirements, $ingredient, $amount, $sourceName);
        }
    }

    return [
        'requirements' => $requirements,
        'missing_mappings' => $missingMappings,
        'unavailable_products' => $unavailableProducts,
    ];
}

function boycold_inventory_refresh_requirement_stocks(mysqli $connect, array &$requirements, int $branchId, bool $lockRows): void
{
    if (!$requirements) {
        return;
    }

    $ids = array_map('intval', array_keys($requirements));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    if ($branchId <= 0) {
        foreach ($requirements as &$requirement) {
            $requirement['stock'] = 0.0;
        }
        unset($requirement);
        return;
    }
    $lockSql = $lockRows ? ' FOR UPDATE' : '';
    $stmt = $connect->prepare(
        "SELECT id, name, unit, stock, min_stock, branch_id
         FROM ingredients
         WHERE id IN ($placeholders) AND branch_id = ?$lockSql"
    );
    $bindValues = $ids;
    $bindValues[] = $branchId;
    boycold_inventory_bind($stmt, str_repeat('i', count($ids)) . 'i', $bindValues);
    $stmt->execute();
    $result = $stmt->get_result();

    $found = [];
    while ($row = $result->fetch_assoc()) {
        $id = (int) $row['id'];
        if (!isset($requirements[$id])) {
            continue;
        }
        $found[$id] = true;
        $requirements[$id]['name'] = (string) $row['name'];
        $requirements[$id]['unit'] = (string) ($row['unit'] ?? '');
        $requirements[$id]['stock'] = (float) ($row['stock'] ?? 0);
        $requirements[$id]['min_stock'] = (float) ($row['min_stock'] ?? 0);
        $requirements[$id]['branch_id'] = (int) ($row['branch_id'] ?? 0);
    }
    $stmt->close();

    foreach ($requirements as $id => &$requirement) {
        if (empty($found[$id])) {
            $requirement['stock'] = 0.0;
            $requirement['branch_id'] = $branchId;
        }
    }
    unset($requirement);
}

/**
 * Update an ingredient only when it belongs to the order's branch.
 *
 * Every deduction and reservation is calculated from branch-specific stock.
 * Keeping the same branch condition in the UPDATE is a final database-level
 * guard against a malformed recipe mapping ever changing another branch.
 */
function boycold_inventory_set_branch_stock(
    mysqli_stmt $statement,
    float $stock,
    int $ingredientId,
    int $branchId
): void {
    if ($ingredientId <= 0 || $branchId <= 0) {
        throw new RuntimeException('Inventory stock update requires a valid ingredient and branch.');
    }

    $statement->bind_param('dii', $stock, $ingredientId, $branchId);
    if (!$statement->execute() || $statement->affected_rows !== 1) {
        throw new RuntimeException('Ingredient does not belong to the order branch.');
    }
}

function boycold_inventory_format_requirement_products(array $products): string
{
    $names = array_keys($products);
    return substr(implode(', ', $names), 0, 150);
}

function boycold_inventory_validation_message(array $result): string
{
    if (!empty($result['unavailable_products'])) {
        return $result['unavailable_products'][0];
    }

    if (!empty($result['missing_mappings'])) {
        return 'No ingredient mapping found for ' . implode(', ', $result['missing_mappings']) . '.';
    }

    if (!empty($result['insufficient'])) {
        $item = $result['insufficient'][0];
        return sprintf(
            'Not enough %s. Need %s %s, available %s %s.',
            $item['name'],
            rtrim(rtrim(number_format((float) $item['required'], 3, '.', ''), '0'), '.'),
            $item['unit'],
            rtrim(rtrim(number_format((float) $item['stock'], 3, '.', ''), '0'), '.'),
            $item['unit']
        );
    }

    return 'Inventory is sufficient.';
}

function boycold_validate_inventory_for_items(
    mysqli $connect,
    array $items,
    int $branchId,
    bool $lockRows = false,
    bool $requireBaseMappings = true
): array {
    $calculated = boycold_inventory_calculate_requirements($connect, $items, $branchId, $requireBaseMappings);
    $requirements = $calculated['requirements'];
    boycold_inventory_refresh_requirement_stocks($connect, $requirements, $branchId, $lockRows);

    $insufficient = [];
    foreach ($requirements as $requirement) {
        if ((float) $requirement['stock'] + 0.0001 < (float) $requirement['required']) {
            $insufficient[] = [
                'ingredient_id' => (int) $requirement['ingredient_id'],
                'name' => (string) $requirement['name'],
                'unit' => (string) $requirement['unit'],
                'stock' => (float) $requirement['stock'],
                'required' => (float) $requirement['required'],
                'products' => array_keys($requirement['products']),
            ];
        }
    }

    $result = [
        'success' => !$calculated['missing_mappings'] && !$calculated['unavailable_products'] && !$insufficient,
        'requirements' => array_values($requirements),
        'missing_mappings' => $calculated['missing_mappings'],
        'unavailable_products' => $calculated['unavailable_products'],
        'insufficient' => $insufficient,
    ];
    $result['error'] = boycold_inventory_validation_message($result);

    return $result;
}

function boycold_get_product_inventory_availability(
    mysqli $connect,
    int $branchId = 0,
    array $productNames = []
): array {
    boycold_ensure_inventory_schema($connect);

    $products = boycold_inventory_fetch_products($connect, $productNames);
    $names = array_map(static fn ($row) => (string) $row['product_name'], array_values($products));
    $mappingRows = boycold_inventory_fetch_mapping_rows($connect, $names);
    $availability = [];
    $isAllBranchesView = $branchId <= 0;

    foreach ($products as $productKey => $product) {
        $productName = (string) $product['product_name'];
        $rows = $mappingRows[$productKey] ?? [];
        $details = [];
        $availableServings = null;
        $canOrder = (int) ($product['is_available'] ?? 0) === 1;
        $reasons = [];

        if (!$canOrder) {
            $reasons[] = 'Disabled in Menu Management';
        } elseif (!$rows) {
            $canOrder = false;
            $reasons[] = 'No ingredient mapping';
        }

        foreach ($rows as $row) {
            $amount = max(0.0, (float) ($row['amount'] ?? 0));
            if ($branchId > 0) {
                $ingredient = boycold_inventory_resolve_ingredient_for_branch($connect, $row, $branchId);
            } else {
                $ingredient = boycold_inventory_resolve_ingredient_for_active_branches($connect, $row);
            }
            $stock = (float) ($ingredient['stock'] ?? 0);
            $minStock = (float) ($ingredient['min_stock'] ?? 0);
            $servings = boycold_inventory_remaining_servings($stock, $amount) ?? 0;

            if ($availableServings === null || $servings < $availableServings) {
                $availableServings = $servings;
            }

            if ($amount <= 0 || $stock + 0.0001 < $amount || empty($ingredient['branch_available'])) {
                $canOrder = false;
                $reasons[] = $ingredient['name'] . (
                    empty($ingredient['branch_available'])
                        ? ($isAllBranchesView ? ' is not stocked at any active branch' : ' is not stocked at this branch')
                        : ($stock <= 0 ? ' is out of stock' : ' is insufficient')
                );
            }

            $details[] = [
                'ingredient_id' => (int) $ingredient['id'],
                'name' => (string) $ingredient['name'],
                'unit' => (string) $ingredient['unit'],
                'required_per_serving' => $amount,
                'stock' => $stock,
                'min_stock' => $minStock,
                'servings' => $servings,
                'status' => boycold_inventory_serving_status($servings),
            ];
        }

        $availableServings = $availableServings ?? 0;
        $servingStatus = boycold_inventory_serving_status($availableServings);
        $status = 'available';
        $label = 'Available';
        $ingredientLabel = 'Sufficient';
        if (!$rows || $servingStatus === 'unavailable' || !$canOrder && $availableServings <= 0) {
            $status = 'unavailable';
            $label = 'Unavailable';
            $ingredientLabel = $rows ? 'Unavailable' : 'No mapping';
        } elseif ($servingStatus === 'low') {
            $ingredientLabel = 'Low ingredients';
        } elseif ($servingStatus === 'insufficient') {
            $ingredientLabel = 'Insufficient';
        }

        $availability[$productKey] = [
            'key' => $productKey,
            'product_id' => (int) ($product['id'] ?? 0),
            'product_name' => $productName,
            'status' => $status,
            'status_label' => $label,
            'ingredient_status' => $ingredientLabel,
            'can_order' => $canOrder,
            'available_servings' => $availableServings,
            'reason' => implode('; ', array_unique($reasons)),
            'ingredients' => $details,
        ];
    }

    return $availability;
}

function boycold_inventory_order_is_deductible(array $order): bool
{
    return strtolower((string) ($order['status'] ?? '')) === 'completed'
        && strtolower((string) ($order['payment_status'] ?? '')) === 'paid';
}

function boycold_deduct_inventory_for_order_in_transaction(
    mysqli $connect,
    int $orderId,
    string $source,
    ?int $actorId = null
): array {
    boycold_ensure_inventory_schema($connect);

    if ($orderId <= 0) {
        return ['success' => false, 'error' => 'Invalid order id.'];
    }

    $source = strtolower(trim($source));
    if (!in_array($source, ['pos', 'online'], true)) {
        $source = 'online';
    }

    $orderStmt = $connect->prepare(
        "SELECT id, status, payment_status, branch_id, inventory_deducted_at
         FROM orders
         WHERE id = ?
         LIMIT 1
         FOR UPDATE"
    );
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();

    if (!$order) {
        return ['success' => false, 'error' => 'Order not found.'];
    }

    if (!empty($order['inventory_deducted_at'])) {
        return [
            'success' => true,
            'deducted' => false,
            'already_deducted' => true,
            'message' => 'Inventory was already deducted for this order.',
        ];
    }

    if (!boycold_inventory_order_is_deductible($order)) {
        return [
            'success' => false,
            'error' => 'Inventory can only be deducted after the order is completed and paid.',
        ];
    }

    $itemsStmt = $connect->prepare(
        "SELECT product_name AS name, quantity AS qty, milk, addons
         FROM order_items
         WHERE order_id = ?
         ORDER BY id"
    );
    $itemsStmt->bind_param('i', $orderId);
    $itemsStmt->execute();
    $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $itemsStmt->close();

    if (!$items) {
        return ['success' => false, 'error' => 'Order has no items to deduct from inventory.'];
    }

    $branchId = (int) ($order['branch_id'] ?? 0);
    if ($branchId <= 0) {
        return [
            'success' => false,
            'error' => 'Inventory deduction requires a valid order branch.',
        ];
    }
    $validation = boycold_validate_inventory_for_items($connect, $items, $branchId, true, true);
    if (!$validation['success']) {
        $message = substr((string) $validation['error'], 0, 255);
        $errorStmt = $connect->prepare('UPDATE orders SET inventory_deduction_error = ? WHERE id = ?');
        $errorStmt->bind_param('si', $message, $orderId);
        $errorStmt->execute();
        $errorStmt->close();

        return [
            'success' => false,
            'error' => $validation['error'],
            'validation' => $validation,
        ];
    }

    $updateStock = $connect->prepare('UPDATE ingredients SET stock = ? WHERE id = ? AND branch_id = ?');
    $insertMovement = $connect->prepare(
        "INSERT INTO ingredient_stock_movements
            (ingredient_id, movement_type, quantity, resulting_stock, order_id, source, product_name, reference, created_by)
         VALUES (?, 'deduction', ?, ?, ?, ?, ?, ?, ?)"
    );

    $reference = ($source === 'pos' ? 'POS Order #' : 'Online Order #') . $orderId;
    $movementCount = 0;
    foreach ($validation['requirements'] as $requirement) {
        $ingredientId = (int) $requirement['ingredient_id'];
        $required = (float) $requirement['required'];
        if ($ingredientId <= 0 || $required <= 0) {
            continue;
        }

        $newStock = max(0.0, (float) $requirement['stock'] - $required);
        $productNames = boycold_inventory_format_requirement_products((array) ($requirement['products'] ?? []));
        $actorParam = $actorId && $actorId > 0 ? $actorId : null;

        boycold_inventory_set_branch_stock($updateStock, $newStock, $ingredientId, $branchId);

        $insertMovement->bind_param(
            'iddisssi',
            $ingredientId,
            $required,
            $newStock,
            $orderId,
            $source,
            $productNames,
            $reference,
            $actorParam
        );
        $insertMovement->execute();
        $movementCount++;
    }

    $updateStock->close();
    $insertMovement->close();

    $markStmt = $connect->prepare(
        "UPDATE orders
         SET inventory_deducted_at = NOW(),
             inventory_deduction_source = ?,
             inventory_deduction_error = NULL
         WHERE id = ? AND inventory_deducted_at IS NULL"
    );
    $markStmt->bind_param('si', $source, $orderId);
    $markStmt->execute();
    $marked = $markStmt->affected_rows > 0;
    $markStmt->close();

    if (!$marked) {
        return [
            'success' => true,
            'deducted' => false,
            'already_deducted' => true,
            'message' => 'Inventory was already deducted for this order.',
        ];
    }

    return [
        'success' => true,
        'deducted' => true,
        'already_deducted' => false,
        'movement_count' => $movementCount,
        'requirements' => $validation['requirements'],
    ];
}

function boycold_deduct_inventory_for_order(
    mysqli $connect,
    int $orderId,
    string $source,
    ?int $actorId = null
): array {
    $connect->begin_transaction();
    try {
        $result = boycold_deduct_inventory_for_order_in_transaction($connect, $orderId, $source, $actorId);
        if (!$result['success']) {
            $connect->rollback();
            return $result;
        }
        $connect->commit();
        return $result;
    } catch (Throwable $e) {
        $connect->rollback();
        throw $e;
    }
}

function boycold_reserve_inventory_for_order_in_transaction(
    mysqli $connect,
    int $orderId,
    ?int $actorId = null
): array {
    boycold_ensure_inventory_schema($connect);

    $orderStmt = $connect->prepare(
        "SELECT id, branch_id, inventory_deducted_at, inventory_restored_at
         FROM orders WHERE id = ? LIMIT 1 FOR UPDATE"
    );
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();

    if (!$order) {
        return ['success' => false, 'error' => 'Order not found.'];
    }
    if (!empty($order['inventory_deducted_at'])) {
        return ['success' => true, 'reserved' => false, 'already_reserved' => true];
    }

    $itemsStmt = $connect->prepare(
        'SELECT product_name AS name, quantity AS qty, milk, addons FROM order_items WHERE order_id = ? ORDER BY id'
    );
    $itemsStmt->bind_param('i', $orderId);
    $itemsStmt->execute();
    $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $itemsStmt->close();

    $branchId = (int) ($order['branch_id'] ?? 0);
    if ($branchId <= 0) {
        return ['success' => false, 'error' => 'Inventory reservation requires a valid order branch.'];
    }

    $validation = boycold_validate_inventory_for_items($connect, $items, $branchId, true, true);
    if (!$validation['success']) {
        return $validation + ['reserved' => false];
    }

    $updateStock = $connect->prepare('UPDATE ingredients SET stock = ? WHERE id = ? AND branch_id = ?');
    $insertMovement = $connect->prepare(
        "INSERT INTO ingredient_stock_movements
            (ingredient_id, movement_type, quantity, resulting_stock, order_id, source, product_name, reference, created_by)
         VALUES (?, 'deduction', ?, ?, ?, 'online_reservation', ?, ?, ?)"
    );
    $reference = 'Online reservation #' . $orderId;
    foreach ($validation['requirements'] as $requirement) {
        $ingredientId = (int) $requirement['ingredient_id'];
        $required = (float) $requirement['required'];
        if ($ingredientId <= 0 || $required <= 0) continue;
        $newStock = max(0.0, (float) $requirement['stock'] - $required);
        $productNames = boycold_inventory_format_requirement_products((array) $requirement['products']);
        $actor = $actorId && $actorId > 0 ? $actorId : null;
        boycold_inventory_set_branch_stock($updateStock, $newStock, $ingredientId, $branchId);
        $insertMovement->bind_param('iddissi', $ingredientId, $required, $newStock, $orderId, $productNames, $reference, $actor);
        $insertMovement->execute();
    }
    $updateStock->close();
    $insertMovement->close();

    $mark = $connect->prepare(
        "UPDATE orders SET inventory_deducted_at = NOW(), inventory_deduction_source = 'online_reservation'
         WHERE id = ? AND inventory_deducted_at IS NULL"
    );
    $mark->bind_param('i', $orderId);
    $mark->execute();
    $mark->close();

    return ['success' => true, 'reserved' => true, 'requirements' => $validation['requirements']];
}

function boycold_restore_reserved_inventory_for_order_in_transaction(mysqli $connect, int $orderId): array
{
    boycold_ensure_inventory_schema($connect);

    $orderStmt = $connect->prepare(
        "SELECT branch_id, inventory_deducted_at, inventory_deduction_source, inventory_restored_at
         FROM orders WHERE id = ? LIMIT 1 FOR UPDATE"
    );
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();

    if (!$order || $order['inventory_deduction_source'] !== 'online_reservation' || !empty($order['inventory_restored_at'])) {
        return ['success' => true, 'restored' => false];
    }

    $branchId = (int) ($order['branch_id'] ?? 0);
    if ($branchId <= 0) {
        return ['success' => false, 'error' => 'Inventory restoration requires a valid order branch.'];
    }

    $movementStmt = $connect->prepare(
        "SELECT ingredient_id, SUM(quantity) AS quantity
         FROM ingredient_stock_movements
         WHERE order_id = ? AND source = 'online_reservation' AND movement_type = 'deduction'
         GROUP BY ingredient_id"
    );
    $movementStmt->bind_param('i', $orderId);
    $movementStmt->execute();
    $movements = $movementStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $movementStmt->close();

    $stockStmt = $connect->prepare('SELECT stock FROM ingredients WHERE id = ? AND branch_id = ? FOR UPDATE');
    $updateStmt = $connect->prepare('UPDATE ingredients SET stock = ? WHERE id = ? AND branch_id = ?');
    $insertStmt = $connect->prepare(
        "INSERT INTO ingredient_stock_movements
            (ingredient_id, movement_type, quantity, resulting_stock, order_id, source, reference)
         VALUES (?, 'adjustment', ?, ?, ?, 'online_restore', ? )"
    );
    $reference = 'Online reservation restore #' . $orderId;
    foreach ($movements as $movement) {
        $ingredientId = (int) $movement['ingredient_id'];
        $quantity = (float) $movement['quantity'];
        $stockStmt->bind_param('ii', $ingredientId, $branchId);
        $stockStmt->execute();
        $stockRow = $stockStmt->get_result()->fetch_assoc();
        if (!$stockRow) {
            throw new RuntimeException('Reserved ingredient does not belong to the order branch.');
        }
        $newStock = (float) $stockRow['stock'] + $quantity;
        boycold_inventory_set_branch_stock($updateStmt, $newStock, $ingredientId, $branchId);
        $insertStmt->bind_param('iddis', $ingredientId, $quantity, $newStock, $orderId, $reference);
        $insertStmt->execute();
    }
    $stockStmt->close();
    $updateStmt->close();
    $insertStmt->close();

    $restore = $connect->prepare('UPDATE orders SET inventory_restored_at = NOW() WHERE id = ? AND inventory_restored_at IS NULL');
    $restore->bind_param('i', $orderId);
    $restore->execute();
    $restore->close();

    return ['success' => true, 'restored' => true];
}

function boycold_restore_reserved_inventory_for_order(mysqli $connect, int $orderId): array
{
    $connect->begin_transaction();
    try {
        $result = boycold_restore_reserved_inventory_for_order_in_transaction($connect, $orderId);
        if (!$result['success']) {
            $connect->rollback();
            return $result;
        }
        $connect->commit();
        return $result;
    } catch (Throwable $e) {
        $connect->rollback();
        throw $e;
    }
}

/**
 * Restores inventory that was already deducted for a completed order. This is
 * used by the POS void flow; it deliberately relies on the original recorded
 * stock movements instead of recalculating today's recipe mappings.
 */
function boycold_restore_deducted_inventory_for_order_in_transaction(
    mysqli $connect,
    int $orderId,
    string $restoreSource = 'pos_void_restore',
    ?int $actorId = null
): array {
    boycold_ensure_inventory_schema($connect);

    $orderStmt = $connect->prepare(
        'SELECT branch_id, inventory_deducted_at, inventory_deduction_source, inventory_restored_at
         FROM orders
         WHERE id = ?
         LIMIT 1
         FOR UPDATE'
    );
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();

    if (!$order) {
        return ['success' => false, 'error' => 'Order not found.'];
    }
    if (empty($order['inventory_deducted_at'])) {
        return ['success' => true, 'restored' => false, 'message' => 'No inventory was deducted for this order.'];
    }
    if (!empty($order['inventory_restored_at'])) {
        return ['success' => true, 'restored' => false, 'already_restored' => true];
    }

    $branchId = (int) ($order['branch_id'] ?? 0);
    $deductionSource = trim((string) ($order['inventory_deduction_source'] ?? ''));
    if ($branchId <= 0 || $deductionSource === '') {
        return ['success' => false, 'error' => 'This order has no recoverable inventory record.'];
    }

    $movementStmt = $connect->prepare(
        "SELECT ingredient_id, SUM(quantity) AS quantity,
                GROUP_CONCAT(DISTINCT NULLIF(product_name, '') ORDER BY product_name SEPARATOR ', ') AS product_names
         FROM ingredient_stock_movements
         WHERE order_id = ? AND source = ? AND movement_type = 'deduction'
         GROUP BY ingredient_id"
    );
    $movementStmt->bind_param('is', $orderId, $deductionSource);
    $movementStmt->execute();
    $movements = $movementStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $movementStmt->close();

    if (!$movements) {
        return ['success' => false, 'error' => 'The original inventory movements could not be found.'];
    }

    $stockStmt = $connect->prepare('SELECT stock FROM ingredients WHERE id = ? AND branch_id = ? FOR UPDATE');
    $updateStmt = $connect->prepare('UPDATE ingredients SET stock = ? WHERE id = ? AND branch_id = ?');
    $insertStmt = $connect->prepare(
        "INSERT INTO ingredient_stock_movements
            (ingredient_id, movement_type, quantity, resulting_stock, order_id, source, product_name, reference, created_by)
         VALUES (?, 'adjustment', ?, ?, ?, ?, ?, ?, ?)"
    );
    $reference = 'Void restore #' . $orderId;
    $restoreSource = substr(trim($restoreSource), 0, 30) ?: 'pos_void_restore';
    $actor = $actorId && $actorId > 0 ? $actorId : null;
    $movementCount = 0;

    foreach ($movements as $movement) {
        $ingredientId = (int) ($movement['ingredient_id'] ?? 0);
        $quantity = (float) ($movement['quantity'] ?? 0);
        if ($ingredientId <= 0 || $quantity <= 0) {
            continue;
        }

        $stockStmt->bind_param('ii', $ingredientId, $branchId);
        $stockStmt->execute();
        $stockRow = $stockStmt->get_result()->fetch_assoc();
        if (!$stockRow) {
            throw new RuntimeException('An order ingredient does not belong to this branch.');
        }

        $newStock = (float) $stockRow['stock'] + $quantity;
        $productNames = (string) ($movement['product_names'] ?? 'Voided order item');
        boycold_inventory_set_branch_stock($updateStmt, $newStock, $ingredientId, $branchId);
        $insertStmt->bind_param(
            'iddisssi',
            $ingredientId,
            $quantity,
            $newStock,
            $orderId,
            $restoreSource,
            $productNames,
            $reference,
            $actor
        );
        $insertStmt->execute();
        $movementCount++;
    }

    $stockStmt->close();
    $updateStmt->close();
    $insertStmt->close();

    if ($movementCount === 0) {
        return ['success' => false, 'error' => 'The original inventory movements are invalid.'];
    }

    $restoreStmt = $connect->prepare(
        'UPDATE orders
         SET inventory_restored_at = NOW()
         WHERE id = ? AND inventory_restored_at IS NULL'
    );
    $restoreStmt->bind_param('i', $orderId);
    $restoreStmt->execute();
    $restoreStmt->close();

    return ['success' => true, 'restored' => true, 'movement_count' => $movementCount];
}
