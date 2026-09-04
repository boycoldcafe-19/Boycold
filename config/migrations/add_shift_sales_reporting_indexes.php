<?php
require_once __DIR__ . '/../db_config.php';

$indexes = [
    'idx_orders_shift_sales' => 'ALTER TABLE orders ADD INDEX idx_orders_shift_sales (shift_id, user_id, payment_method, status)',
    'idx_orders_online_sales' => 'ALTER TABLE orders ADD INDEX idx_orders_online_sales (branch_id, user_id, created_at, status)'
];

foreach ($indexes as $indexName => $sql) {
    $check = $connect->query("SHOW INDEX FROM orders WHERE Key_name = '" . $connect->real_escape_string($indexName) . "'");
    if ($check && $check->num_rows > 0) {
        echo $indexName . ': already present' . PHP_EOL;
        continue;
    }

    $connect->query($sql);
    echo $indexName . ': added' . PHP_EOL;
}
