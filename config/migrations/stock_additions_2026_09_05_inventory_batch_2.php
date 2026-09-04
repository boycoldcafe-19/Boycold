<?php
require_once __DIR__ . '/../db_config.php';

$branchId = 1;
$additions = [
    ['Milk', 'L', 30.000],
    ['Biscoff', 'kg', 20.000],
    ['Chocolate Syrup', 'L', 20.000],
    ['Condensed Milk', 'kg', 10.000],
    ['Vanilla Syrup', 'L', 20.000],
    ['Blueberry Syrup', 'L', 20.000],
    ['Banana', 'kg', 10.000],
    ['Chocolate Pudding', 'kg', 10.000],
    ['Crushed Cookies', 'kg', 10.000],
    ['Strawberry Syrup', 'L', 20.000],
    ['Oreo Cookies', 'kg', 20.000],
    ['Matcha Powder', 'kg', 15.000],
    ['Cheesecake Sauce', 'L', 10.000],
    ['Cream Cheese', 'kg', 20.000],
    ['Mango Syrup', 'L', 20.000],
    ['Matcha Pudding', 'kg', 10.000],
    ['Sea Salt Cream', 'L', 10.000],
    ['Ube Syrup', 'L', 30.000],
    ['Condense', 'kg', 20.000],
    ['Crushed Graham', 'kg', 20.000],
    ['Yogurt', 'kg', 10.000],
    ['Biscoff Syrup', 'L', 10.000],
    ['Cherry Syrup', 'L', 10.000],
    ['Chocolate Chips', 'kg', 10.000],
    ['Cheesecake Powder', 'kg', 20.000],
    ['Hershey\'s Chocolate Syrup', 'L', 10.000],
    ['Chocolate Powder', 'kg', 10.000],
    ['Espresso', 'kg', 0.500],
    ['Ube Powder', 'kg', 10.000],
];

$find = $connect->prepare(
    'SELECT id FROM ingredients WHERE LOWER(name) = LOWER(?) AND branch_id = ? LIMIT 1'
);
$update = $connect->prepare(
    'UPDATE ingredients SET stock = stock + ? WHERE id = ?'
);
$insert = $connect->prepare(
    'INSERT INTO ingredients (name, unit, branch_id, stock, min_stock) VALUES (?, ?, ?, ?, 5.000)'
);

foreach ($additions as [$name, $unit, $amount]) {
    $find->bind_param('si', $name, $branchId);
    $find->execute();
    $existing = $find->get_result()->fetch_assoc();

    if ($existing) {
        $ingredientId = (int) $existing['id'];
        $update->bind_param('di', $amount, $ingredientId);
        $update->execute();
        echo "$name: added $amount $unit\n";
    } else {
        $insert->bind_param('ssid', $name, $unit, $branchId, $amount);
        $insert->execute();
        echo "$name: created with $amount $unit\n";
    }
}

$find->close();
$update->close();
$insert->close();
