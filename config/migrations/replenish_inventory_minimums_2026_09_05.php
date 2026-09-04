<?php
require_once __DIR__ . '/../db_config.php';

$stmt = $connect->prepare(
    'UPDATE ingredients
     SET stock = min_stock + 1
     WHERE name = ? AND branch_id = 1 AND unit = \'L\' AND stock <= min_stock'
);

foreach (['Caramel Drizzle', 'Whole Milk'] as $name) {
    $stmt->bind_param('s', $name);
    $stmt->execute();
    echo $name . ': replenished above minimum stock\n';
}

$stmt->close();
