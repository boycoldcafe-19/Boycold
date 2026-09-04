<?php
require_once __DIR__ . '/../db_config.php';

$column = $connect->query("SHOW COLUMNS FROM ingredients LIKE 'min_stock'")->fetch_assoc();
if (!$column) {
    $connect->query("ALTER TABLE ingredients ADD COLUMN min_stock decimal(10,3) NOT NULL DEFAULT 5.000 AFTER stock");
    echo "min_stock column added with default 5.000\n";
} else {
    $connect->query("ALTER TABLE ingredients MODIFY COLUMN min_stock decimal(10,3) NOT NULL DEFAULT 5.000");
    echo "min_stock default set to 5.000\n";
}
