<?php
require_once __DIR__ . '/../db_config.php';

$branchId = 1;
$ingredients = [
    ['Milk', 'L', 10],
    ['Blueberry Syrup', 'L', 10],
    ['Condense', 'kg', 10],
    ['Banana', 'kg', 10],
    ['Chocolate Pudding', 'kg', 10],
    ['Chocolate Syrup', 'L', 10],
    ['Crushed Cookies', 'kg', 10],
    ['Whipped Cream', 'L', 20],
    ['Strawberry Syrup', 'L', 10],
    ['Vanilla Syrup', 'L', 10],
    ['Oreo Cookies', 'kg', 10],
    ['Condensed', 'kg', 10],
];

$stmt = $connect->prepare(
    'SELECT id FROM ingredients WHERE name = ? AND branch_id = ? LIMIT 1'
);
$update = $connect->prepare(
    'UPDATE ingredients SET stock = stock + ?, unit = ? WHERE id = ?'
);
$insert = $connect->prepare(
    'INSERT INTO ingredients (name, unit, branch_id, stock) VALUES (?, ?, ?, ?)'
);

foreach ($ingredients as [$name, $unit, $amount]) {
    $stmt->bind_param('si', $name, $branchId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        $id = (int) $row['id'];
        $update->bind_param('dsi', $amount, $unit, $id);
        $update->execute();
        echo "$name: stock increased by $amount $unit" . PHP_EOL;
    } else {
        $insert->bind_param('ssid', $name, $unit, $branchId, $amount);
        $insert->execute();
        echo "$name: added with $amount $unit" . PHP_EOL;
    }
}

$stmt->close();
$update->close();
$insert->close();
