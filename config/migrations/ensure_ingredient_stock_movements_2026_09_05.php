<?php
require_once __DIR__ . '/../db_config.php';

$connect->query(
    "CREATE TABLE IF NOT EXISTS ingredient_stock_movements (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        ingredient_id INT UNSIGNED NOT NULL,
        movement_type ENUM('stock_in', 'deduction', 'adjustment') NOT NULL,
        quantity DECIMAL(10,3) NOT NULL,
        resulting_stock DECIMAL(10,3) NOT NULL,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_ingredient_stock_movements_ingredient (ingredient_id),
        KEY idx_stock_movement_ingredient_id (ingredient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

echo "ingredient_stock_movements: ready\n";
