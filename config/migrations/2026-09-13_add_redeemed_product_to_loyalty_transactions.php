<?php
require_once __DIR__ . '/../db_config.php';

// Allow redemptions to record the specific menu product selected by the customer.
function columnExists(mysqli $connect, string $table, string $column): bool
{
    $stmt = $connect->prepare(
        "SELECT COUNT(*) AS cnt
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ((int) ($row['cnt'] ?? 0)) > 0;
}

function foreignKeyExists(mysqli $connect, string $table, string $constraint): bool
{
    $stmt = $connect->prepare(
        "SELECT COUNT(*) AS cnt
         FROM information_schema.TABLE_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND CONSTRAINT_NAME = ?
           AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
    );
    $stmt->bind_param('ss', $table, $constraint);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ((int) ($row['cnt'] ?? 0)) > 0;
}

function uniqueIndexExists(mysqli $connect, string $table, string $column): bool
{
    $stmt = $connect->prepare(
        "SELECT COUNT(*) AS cnt
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?
           AND NON_UNIQUE = 0"
    );
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ((int) ($row['cnt'] ?? 0)) > 0;
}

$table = 'loyalty_transactions';
$productKeyReady = uniqueIndexExists($connect, 'products', 'id');
if (!$productKeyReady) {
    if (!$connect->query('ALTER TABLE products ADD PRIMARY KEY (id)')) {
        throw new RuntimeException('Failed to add products primary key: ' . $connect->error);
    }
    echo "Added products.id primary key for redeemed product references.\n";
}

if (!columnExists($connect, $table, 'redeemed_product_id')) {
    $sql = "ALTER TABLE loyalty_transactions
            ADD COLUMN redeemed_product_id INT NULL AFTER order_id";
    if (!$connect->query($sql)) {
        throw new RuntimeException('Failed to add redeemed_product_id: ' . $connect->error);
    }
    echo "Added loyalty_transactions.redeemed_product_id.\n";
} else {
    echo "redeemed_product_id already exists, skipping.\n";
}

if (!columnExists($connect, $table, 'redeemed_product_name')) {
    $sql = "ALTER TABLE loyalty_transactions
            ADD COLUMN redeemed_product_name VARCHAR(150) NULL AFTER redeemed_product_id";
    if (!$connect->query($sql)) {
        throw new RuntimeException('Failed to add redeemed_product_name: ' . $connect->error);
    }
    echo "Added loyalty_transactions.redeemed_product_name.\n";
} else {
    echo "redeemed_product_name already exists, skipping.\n";
}

$constraint = 'fk_loyalty_redeemed_product';
if (!foreignKeyExists($connect, $table, $constraint)) {
    $sql = "ALTER TABLE loyalty_transactions
            ADD CONSTRAINT {$constraint}
            FOREIGN KEY (redeemed_product_id) REFERENCES products(id) ON DELETE SET NULL";
    if (!$connect->query($sql)) {
        throw new RuntimeException('Failed to add redeemed product foreign key: ' . $connect->error);
    }
    echo "Added {$constraint}.\n";
} else {
    echo "{$constraint} already exists, skipping.\n";
}

echo "Redeemed product columns are ready.\n";
