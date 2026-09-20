<?php

/**
 * Normalize a category into the value stored by products and used by filters.
 * Category records are shared by the public menu and every branch.
 */
function boycold_menu_category_slug(string $value): string
{
    $value = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = strtolower($value);
    $aliases = [
        'waffle' => 'waffles',
        'bites' => 'light-snack',
    ];
    if (isset($aliases[$value])) {
        return $aliases[$value];
    }

    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    $value = trim((string) $value, '-');
    return substr($value, 0, 80);
}

function boycold_menu_category_default_label(string $slug): string
{
    $labels = [
        'coffee' => 'Coffee',
        'non-coffee' => 'Non-Coffee',
        'matcha-fusion' => 'Matcha Fusion',
        'smoothie' => 'Smoothie',
        'frappe-series' => 'Frappe Series',
        'rice-meal' => 'Rice Meal',
        'light-snack' => 'Light Snack',
        'pasta' => 'Pasta',
        'waffles' => 'Waffles',
        'quesadilla' => 'Quesadilla',
    ];

    return $labels[$slug] ?? ucwords(str_replace('-', ' ', $slug));
}

function boycold_menu_category_label(string $value, string $slug = ''): string
{
    $value = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('/\s+/', ' ', $value);
    $value = trim((string) $value);
    if ($value === '') {
        $value = boycold_menu_category_default_label($slug);
    }

    return substr($value, 0, 100);
}

function boycold_ensure_menu_category_schema(mysqli $connect): void
{
    $sql = "CREATE TABLE IF NOT EXISTS menu_categories (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        slug VARCHAR(80) NOT NULL,
        name VARCHAR(100) NOT NULL,
        display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_menu_categories_slug (slug),
        KEY idx_menu_categories_active_order (is_active, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!$connect->query($sql)) {
        throw new RuntimeException('Could not prepare menu categories: ' . $connect->error);
    }

    $categories = $connect->query(
        "SELECT DISTINCT category FROM products WHERE TRIM(COALESCE(category, '')) <> '' ORDER BY category"
    );
    while ($categories && ($row = $categories->fetch_assoc())) {
        $slug = boycold_menu_category_slug((string) $row['category']);
        if ($slug !== '') {
            boycold_menu_ensure_category($connect, $slug);
        }
    }
}

/**
 * Older database dumps define products.id as a primary key but omit
 * AUTO_INCREMENT. New products then fail before they can be saved because
 * MySQL requires a manually supplied ID. Keep the catalog insert-ready.
 */
function boycold_ensure_product_id_auto_increment(mysqli $connect): void
{
    $columnResult = $connect->query("SHOW COLUMNS FROM products LIKE 'id'");
    $column = $columnResult ? $columnResult->fetch_assoc() : null;
    if (!$column) {
        throw new RuntimeException('The products table is missing its ID column.');
    }

    if (stripos((string) ($column['Extra'] ?? ''), 'auto_increment') !== false) {
        return;
    }

    $indexResult = $connect->query("SHOW INDEX FROM products WHERE Key_name = 'PRIMARY' AND Column_name = 'id'");
    if (!$indexResult || $indexResult->num_rows < 1) {
        throw new RuntimeException('The products table must use id as its primary key before menu items can be created.');
    }

    $references = [];
    $referenceResult = $connect->query(
        "SELECT k.TABLE_NAME, k.CONSTRAINT_NAME, k.COLUMN_NAME,
                r.UPDATE_RULE, r.DELETE_RULE
         FROM information_schema.KEY_COLUMN_USAGE k
         INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS r
                 ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
                AND r.TABLE_NAME = k.TABLE_NAME
                AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
         WHERE k.REFERENCED_TABLE_SCHEMA = DATABASE()
           AND k.REFERENCED_TABLE_NAME = 'products'
           AND k.REFERENCED_COLUMN_NAME = 'id'"
    );
    while ($referenceResult && ($reference = $referenceResult->fetch_assoc())) {
        $references[] = $reference;
    }

    $quoteIdentifier = static function (string $identifier): string {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new RuntimeException('Invalid database relationship identifier.');
        }
        return '`' . $identifier . '`';
    };

    $droppedReferences = [];
    try {
        foreach ($references as $reference) {
            $table = $quoteIdentifier((string) $reference['TABLE_NAME']);
            $constraint = $quoteIdentifier((string) $reference['CONSTRAINT_NAME']);
            if (!$connect->query("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}")) {
                throw new RuntimeException('Could not prepare product IDs for new menu items: ' . $connect->error);
            }
            $droppedReferences[] = $reference;
        }

        if (!$connect->query('ALTER TABLE products MODIFY id INT NOT NULL AUTO_INCREMENT')) {
            throw new RuntimeException('Could not prepare product IDs for new menu items: ' . $connect->error);
        }

        foreach ($droppedReferences as $reference) {
            $table = $quoteIdentifier((string) $reference['TABLE_NAME']);
            $constraint = $quoteIdentifier((string) $reference['CONSTRAINT_NAME']);
            $childColumn = $quoteIdentifier((string) $reference['COLUMN_NAME']);
            $updateRule = strtoupper((string) $reference['UPDATE_RULE']);
            $deleteRule = strtoupper((string) $reference['DELETE_RULE']);
            if (!in_array($updateRule, ['RESTRICT', 'CASCADE', 'SET NULL', 'NO ACTION'], true)
                || !in_array($deleteRule, ['RESTRICT', 'CASCADE', 'SET NULL', 'NO ACTION'], true)
                || !$connect->query(
                    "ALTER TABLE {$table}
                     ADD CONSTRAINT {$constraint}
                     FOREIGN KEY ({$childColumn}) REFERENCES `products` (`id`)
                     ON UPDATE {$updateRule} ON DELETE {$deleteRule}"
                )) {
                throw new RuntimeException('Product ID setup completed, but a related database constraint could not be restored.');
            }
        }
    } catch (Throwable $error) {
        // If the ID alteration did not happen, restore any relationships that
        // were temporarily removed before returning the original failure.
        $updatedColumnResult = $connect->query("SHOW COLUMNS FROM products LIKE 'id'");
        $updatedColumn = $updatedColumnResult ? $updatedColumnResult->fetch_assoc() : null;
        $stillMissingAutoIncrement = stripos((string) ($updatedColumn['Extra'] ?? ''), 'auto_increment') === false;
        if ($stillMissingAutoIncrement) {
            foreach ($droppedReferences as $reference) {
                try {
                    $table = $quoteIdentifier((string) $reference['TABLE_NAME']);
                    $constraint = $quoteIdentifier((string) $reference['CONSTRAINT_NAME']);
                    $childColumn = $quoteIdentifier((string) $reference['COLUMN_NAME']);
                    $updateRule = strtoupper((string) $reference['UPDATE_RULE']);
                    $deleteRule = strtoupper((string) $reference['DELETE_RULE']);
                    $connect->query(
                        "ALTER TABLE {$table}
                         ADD CONSTRAINT {$constraint}
                         FOREIGN KEY ({$childColumn}) REFERENCES `products` (`id`)
                         ON UPDATE {$updateRule} ON DELETE {$deleteRule}"
                    );
                } catch (Throwable $ignored) {
                    // Preserve the initial, more useful error. The next API
                    // request will retry the idempotent schema repair.
                }
            }
        }
        throw $error;
    }
}

/**
 * Store an administrator's add-on choices per menu item.  The configured
 * flag deliberately distinguishes older items (which keep their historic
 * generic choices) from a newly created item for which the admin selected no
 * add-ons at all.
 */
function boycold_ensure_product_addons_schema(mysqli $connect): void
{
    $configuredColumn = $connect->query("SHOW COLUMNS FROM products LIKE 'addons_configured'");
    if (!$configuredColumn || $configuredColumn->num_rows === 0) {
        if (!$connect->query(
            'ALTER TABLE products ADD COLUMN addons_configured TINYINT(1) NOT NULL DEFAULT 0 AFTER is_available'
        )) {
            throw new RuntimeException('Could not prepare product add-on settings: ' . $connect->error);
        }
    }

    // Milk choices are modifiers too, but have a single-select UI.  Keep a
    // separate flag so an intentionally empty list does not fall back to the
    // legacy, hard-coded Original/Oat Milk choices.
    $milkConfiguredColumn = $connect->query("SHOW COLUMNS FROM products LIKE 'milk_choices_configured'");
    if (!$milkConfiguredColumn || $milkConfiguredColumn->num_rows === 0) {
        if (!$connect->query(
            'ALTER TABLE products ADD COLUMN milk_choices_configured TINYINT(1) NOT NULL DEFAULT 0 AFTER addons_configured'
        )) {
            throw new RuntimeException('Could not prepare product milk-choice settings: ' . $connect->error);
        }
    }

    $sql = "CREATE TABLE IF NOT EXISTS product_addons (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id INT NOT NULL,
        modifier_type ENUM('addon', 'milk') NOT NULL DEFAULT 'addon',
        addon_name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_product_addons_type_name (product_id, modifier_type, addon_name),
        KEY idx_product_addons_product_order (product_id, modifier_type, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!$connect->query($sql)) {
        throw new RuntimeException('Could not prepare product add-ons: ' . $connect->error);
    }

    $modifierColumn = $connect->query("SHOW COLUMNS FROM product_addons LIKE 'modifier_type'");
    if (!$modifierColumn || $modifierColumn->num_rows === 0) {
        if (!$connect->query(
            "ALTER TABLE product_addons ADD COLUMN modifier_type ENUM('addon', 'milk') NOT NULL DEFAULT 'addon' AFTER product_id"
        )) {
            throw new RuntimeException('Could not prepare product milk choices: ' . $connect->error);
        }
    }

    // The original uniqueness rule did not allow the same label in the two
    // separate groups.  Replace it with a type-aware key without altering
    // any existing add-on records (they remain type=addon by default).
    $legacyIndex = $connect->query("SHOW INDEX FROM product_addons WHERE Key_name = 'uq_product_addons_name'");
    if ($legacyIndex && $legacyIndex->num_rows > 0
        && !$connect->query('ALTER TABLE product_addons DROP INDEX uq_product_addons_name')) {
        throw new RuntimeException('Could not update the product modifier index: ' . $connect->error);
    }
    $typeIndex = $connect->query("SHOW INDEX FROM product_addons WHERE Key_name = 'uq_product_addons_type_name'");
    if (!$typeIndex || $typeIndex->num_rows === 0) {
        if (!$connect->query(
            'ALTER TABLE product_addons ADD UNIQUE KEY uq_product_addons_type_name (product_id, modifier_type, addon_name)'
        )) {
            throw new RuntimeException('Could not finish the product modifier setup: ' . $connect->error);
        }
    }
}

/**
 * Decode and validate add-ons sent by the menu-management form.
 *
 * @return array<int, array{name: string, price: float}>
 */
function boycold_menu_normalize_addons(mixed $value): array
{
    if (is_string($value)) {
        $value = trim($value) === '' ? [] : json_decode($value, true);
        if ($value === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Product add-ons must be valid data.');
        }
    }
    if (!is_array($value)) {
        throw new RuntimeException('Product add-ons must be a list.');
    }

    $addons = [];
    $seen = [];
    foreach ($value as $addon) {
        if (!is_array($addon)) {
            throw new RuntimeException('Each product add-on must include a name and price.');
        }
        $name = preg_replace('/\s+/', ' ', trim((string) ($addon['name'] ?? $addon['value'] ?? '')));
        if ($name === '') {
            continue;
        }
        if (mb_strlen($name) > 100) {
            throw new RuntimeException('Product add-on names must be 100 characters or fewer.');
        }
        $priceValue = $addon['price'] ?? null;
        if (!is_numeric($priceValue) || (float) $priceValue < 0 || (float) $priceValue > 99999999.99) {
            throw new RuntimeException("Enter a valid price for the {$name} add-on.");
        }
        $key = mb_strtolower($name, 'UTF-8');
        if (isset($seen[$key])) {
            throw new RuntimeException("The {$name} add-on was entered more than once.");
        }
        $seen[$key] = true;
        $addons[] = ['name' => $name, 'price' => round((float) $priceValue, 2)];
    }

    return $addons;
}

/** @return array<int, array<int, array{name: string, price: float}>> */
function boycold_menu_get_product_addons(mysqli $connect, array $productIds): array
{
    $modifiers = boycold_menu_get_product_modifiers($connect, $productIds);
    return array_map(static fn (array $groups): array => $groups['addons'], $modifiers);
}

/**
 * @return array<int, array{addons: array<int, array{name: string, price: float}>, milk_choices: array<int, array{name: string, price: float}>}>
 */
function boycold_menu_get_product_modifiers(mysqli $connect, array $productIds): array
{
    boycold_ensure_product_addons_schema($connect);
    $ids = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn (int $id): bool => $id > 0)));
    if (!$ids) {
        return [];
    }

    $result = $connect->query(
        'SELECT product_id, modifier_type, addon_name, price FROM product_addons WHERE product_id IN (' . implode(',', $ids) . ') ORDER BY product_id, modifier_type, display_order, id'
    );
    if (!$result) {
        throw new RuntimeException('Could not load product add-ons: ' . $connect->error);
    }

    $modifiers = [];
    while ($row = $result->fetch_assoc()) {
        $productId = (int) $row['product_id'];
        if (!isset($modifiers[$productId])) {
            $modifiers[$productId] = ['addons' => [], 'milk_choices' => []];
        }
        $group = ($row['modifier_type'] ?? 'addon') === 'milk' ? 'milk_choices' : 'addons';
        $modifiers[$productId][$group][] = [
            'name' => (string) $row['addon_name'],
            'price' => (float) $row['price'],
        ];
    }
    return $modifiers;
}

/** @param array<int, array{name: string, price: float}> $modifiers */
function boycold_menu_save_product_modifier_group(mysqli $connect, int $productId, array $modifiers, string $modifierType): void
{
    boycold_ensure_product_addons_schema($connect);
    if ($productId < 1) {
        throw new RuntimeException('A product ID is required to save modifiers.');
    }
    if (!in_array($modifierType, ['addon', 'milk'], true)) {
        throw new RuntimeException('Invalid product modifier type.');
    }

    $delete = $connect->prepare('DELETE FROM product_addons WHERE product_id = ? AND modifier_type = ?');
    if (!$delete) {
        throw new RuntimeException('Could not prepare existing modifiers for replacement.');
    }
    $delete->bind_param('is', $productId, $modifierType);
    if (!$delete->execute()) {
        $error = $delete->error;
        $delete->close();
        throw new RuntimeException('Could not replace product modifiers: ' . $error);
    }
    $delete->close();

    if (!$modifiers) {
        return;
    }
    $insert = $connect->prepare(
        'INSERT INTO product_addons (product_id, modifier_type, addon_name, price, display_order) VALUES (?, ?, ?, ?, ?)'
    );
    if (!$insert) {
        throw new RuntimeException('Could not prepare product modifier save.');
    }
    foreach ($modifiers as $position => $modifier) {
        $name = (string) $modifier['name'];
        $price = (float) $modifier['price'];
        $displayOrder = (int) $position;
        $insert->bind_param('issdi', $productId, $modifierType, $name, $price, $displayOrder);
        if (!$insert->execute()) {
            $error = $insert->error;
            $insert->close();
            throw new RuntimeException('Could not save product modifiers: ' . $error);
        }
    }
    $insert->close();
}

/** @param array<int, array{name: string, price: float}> $addons */
function boycold_menu_save_product_addons(mysqli $connect, int $productId, array $addons): void
{
    boycold_menu_save_product_modifier_group($connect, $productId, $addons, 'addon');
}

/** @param array<int, array{name: string, price: float}> $milkChoices */
function boycold_menu_save_product_milk_choices(mysqli $connect, int $productId, array $milkChoices): void
{
    boycold_menu_save_product_modifier_group($connect, $productId, $milkChoices, 'milk');
}

/**
 * Create a category when needed without overwriting an administrator's label
 * or its manually chosen display position.
 */
function boycold_menu_ensure_category(mysqli $connect, string $value, ?string $label = null): ?array
{
    $slug = boycold_menu_category_slug($value);
    if ($slug === '') {
        return null;
    }

    $lookup = $connect->prepare('SELECT id, slug, name, display_order, is_active FROM menu_categories WHERE slug = ? LIMIT 1');
    $lookup->bind_param('s', $slug);
    $lookup->execute();
    $existing = $lookup->get_result()->fetch_assoc();
    $lookup->close();

    if ($existing) {
        if ((int) $existing['is_active'] !== 1) {
            $activate = $connect->prepare('UPDATE menu_categories SET is_active = 1 WHERE id = ?');
            $activate->bind_param('i', $existing['id']);
            $activate->execute();
            $activate->close();
            $existing['is_active'] = 1;
        }
        return $existing;
    }

    $orderResult = $connect->query('SELECT COALESCE(MAX(display_order), -1) + 1 AS next_order FROM menu_categories');
    $displayOrder = (int) (($orderResult ? $orderResult->fetch_assoc()['next_order'] : 0) ?? 0);
    $name = boycold_menu_category_label($label ?? '', $slug);
    $insert = $connect->prepare(
        'INSERT INTO menu_categories (slug, name, display_order, is_active) VALUES (?, ?, ?, 1)'
    );
    $insert->bind_param('ssi', $slug, $name, $displayOrder);
    if (!$insert->execute()) {
        $error = $insert->error;
        $insert->close();
        throw new RuntimeException('Could not save menu category: ' . $error);
    }
    $id = (int) $insert->insert_id;
    $insert->close();

    return [
        'id' => $id,
        'slug' => $slug,
        'name' => $name,
        'display_order' => $displayOrder,
        'is_active' => 1,
    ];
}

function boycold_menu_get_categories(mysqli $connect, bool $activeOnly = true): array
{
    boycold_ensure_menu_category_schema($connect);
    $where = $activeOnly ? 'WHERE is_active = 1' : '';
    $result = $connect->query(
        "SELECT id, slug, name, display_order, is_active
         FROM menu_categories
         {$where}
         ORDER BY display_order ASC, name ASC, id ASC"
    );
    if (!$result) {
        throw new RuntimeException('Could not load menu categories: ' . $connect->error);
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function boycold_menu_resolve_public_image_path(string $image, string $basePrefix = '../'): string
{
    $image = trim($image);
    if ($image === '') {
        return $basePrefix . 'img/default.png';
    }

    if (preg_match('/^(?:https?:)?\/\//i', $image) || preg_match('/^data:image\//i', $image)) {
        return $image;
    }

    if (preg_match('/^\.\.?\//', $image)) {
        return $image;
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $publicRoot = '';
    if ($scriptName !== '') {
        $pathParts = array_values(array_filter(explode('/', trim($scriptName, '/')), static fn ($segment) => $segment !== ''));
        if (count($pathParts) > 1) {
            $rootSegments = [];
            foreach ($pathParts as $index => $segment) {
                if ($index === count($pathParts) - 1) {
                    break;
                }
                if (in_array($segment, ['User', 'admin', 'pos', 'dashboard', 'auth', 'api', 'config', 'store', 'footer-link'], true)) {
                    break;
                }
                $rootSegments[] = $segment;
            }
            $publicRoot = '/' . implode('/', $rootSegments);
        }
    }

    $normalized = ltrim($image, '/');
    if ($publicRoot !== '') {
        return rtrim($publicRoot, '/') . '/' . $normalized;
    }

    return '/' . $normalized;
}

/**
 * Detect the real image type of a file from its CONTENT (not its filename or
 * browser-supplied MIME type). Returns 'jpg', 'png' or 'webp', or null when the
 * file is not one of those image types.
 */
function boycold_menu_detect_image_extension(string $path): ?string
{
    // 1) getimagesize reads the file header. It is part of core PHP, needs no
    //    extra extension, and understands JPEG, PNG and WebP (PHP 7.1+).
    $info = @getimagesize($path);
    if (is_array($info) && isset($info[2])) {
        $byType = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
        ];
        if (defined('IMAGETYPE_WEBP')) {
            $byType[IMAGETYPE_WEBP] = 'webp';
        }
        if (isset($byType[$info[2]])) {
            return $byType[$info[2]];
        }
    }

    // 2) Fallback for hosts where getimagesize cannot read a valid WebP: check the
    //    RIFF....WEBP signature directly. No dependency on the fileinfo extension.
    $handle = @fopen($path, 'rb');
    if ($handle) {
        $header = (string) fread($handle, 12);
        fclose($handle);
        if (strlen($header) === 12 && substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WEBP') {
            return 'webp';
        }
    }

    return null;
}

function boycold_menu_store_uploaded_image(?array $upload): ?string
{
    if (!$upload || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $uploadError = (int) ($upload['error'] ?? UPLOAD_ERR_OK);
    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('Product images must be 2MB or smaller.');
    }
    if ($uploadError !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The product image could not be uploaded.');
    }
    if ((int) ($upload['size'] ?? 0) < 1 || (int) $upload['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Product images must be 2MB or smaller.');
    }

    $resolvedExt = boycold_menu_detect_image_extension((string) $upload['tmp_name']);
    if ($resolvedExt === null) {
        throw new RuntimeException('Use a PNG, JPG, or WEBP image.');
    }

    $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'menu';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The product image folder could not be created.');
    }

    $filename = 'menu_' . bin2hex(random_bytes(16)) . '.' . $resolvedExt;
    $target = $directory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file((string) $upload['tmp_name'], $target)) {
        throw new RuntimeException('The product image could not be saved.');
    }

    return 'uploads/menu/' . $filename;
}

function boycold_menu_remove_uploaded_image(string $image): void
{
    if (!str_starts_with($image, 'uploads/menu/')) {
        return;
    }

    $directory = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'menu');
    $file = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $image));
    if ($directory && $file && str_starts_with($file, $directory . DIRECTORY_SEPARATOR) && is_file($file)) {
        @unlink($file);
    }
}