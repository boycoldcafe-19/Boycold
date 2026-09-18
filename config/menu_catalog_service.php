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

function boycold_menu_store_uploaded_image(?array $upload): ?string
{
    if (!$upload || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ((int) ($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The product image could not be uploaded.');
    }
    if ((int) ($upload['size'] ?? 0) < 1 || (int) $upload['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Product images must be 2MB or smaller.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string) $upload['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Use a PNG, JPG, or WEBP image.');
    }

    $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'menu';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The product image folder could not be created.');
    }

    $filename = 'menu_' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
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
