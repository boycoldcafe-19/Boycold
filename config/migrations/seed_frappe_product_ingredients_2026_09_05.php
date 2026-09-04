<?php
require_once __DIR__ . '/../db_config.php';

$recipes = [
    'Biscoff frappe' => [
        ['Milk', 180], ['Biscoff', 30], ['Biscoff Syrup', 20],
        ['Condense', 15], ['Whipped Cream', 20]
    ],
    'Black Forrest' => [
        ['Milk', 180], ['Chocolate Syrup', 25], ['Cherry Syrup', 20],
        ['Condense', 15], ['Whipped Cream', 20], ['Chocolate Chips', 20]
    ],
    'Cheesecake Frappe' => [
        ['Milk', 180], ['Cheesecake powder', 30], ['Cream Cheese', 25],
        ['Condense', 15], ['Whipped Cream', 20], ['Crushed Graham', 20]
    ],
    'hershey delight' => [
        ['Milk', 180], ["Hershey's Chocolate Syrup", 30], ['Chocolate Powder', 20],
        ['Condense', 15], ['Whipped Cream', 20], ['Chocolate Chips', 20]
    ],
    'Java Chips' => [
        ['Milk', 180], ['Espresso', 36], ['Chocolate Syrup', 25],
        ['Chocolate Chips', 30], ['Condense', 15], ['Whipped Cream', 20]
    ],
    'Matcha Frappe' => [
        ['Milk', 180], ['Matcha Powder', 5], ['Condense', 15],
        ['Vanilla Syrup', 15], ['Whipped Cream', 20]
    ],
    'Oreo Frappe' => [
        ['Milk', 180], ['Oreo Cookies', 30], ['Condense', 15],
        ['Vanilla Syrup', 15], ['Whipped Cream', 20]
    ],
    'Ube Frappe' => [
        ['Milk', 180], ['Ube Syrup', 30], ['Ube Powder', 20],
        ['Condense', 15], ['Whipped Cream', 20]
    ],
];

$branchId = 1;
$connect->begin_transaction();
try {
    $findIngredient = $connect->prepare(
        'SELECT id FROM ingredients WHERE LOWER(name) = LOWER(?) AND branch_id = ? LIMIT 1'
    );
    $ingredientIds = [];

    foreach ($recipes as $items) {
        foreach ($items as [$ingredientName, $amount]) {
            if (isset($ingredientIds[$ingredientName])) continue;
            $findIngredient->bind_param('si', $ingredientName, $branchId);
            $findIngredient->execute();
            $row = $findIngredient->get_result()->fetch_assoc();
            if (!$row) throw new RuntimeException("Ingredient not found: $ingredientName");
            $ingredientIds[$ingredientName] = (int) $row['id'];
        }
    }

    $delete = $connect->prepare('DELETE FROM product_ingredients WHERE product_name = ?');
    $insert = $connect->prepare(
        'INSERT INTO product_ingredients (id, product_name, ingredient_id, amount) VALUES (?, ?, ?, ?)'
    );
    $nextMappingId = (int) ($connect->query(
        'SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM product_ingredients'
    )->fetch_assoc()['next_id'] ?? 1);

    foreach ($recipes as $productName => $items) {
        $delete->bind_param('s', $productName);
        $delete->execute();
        foreach ($items as [$ingredientName, $amount]) {
            $mappingId = $nextMappingId++;
            $ingredientId = $ingredientIds[$ingredientName];
            $insert->bind_param('isid', $mappingId, $productName, $ingredientId, $amount);
            $insert->execute();
        }
    }

    $connect->commit();
    echo 'Frappe recipe mappings saved: ' . count($recipes) . PHP_EOL;
} catch (Throwable $e) {
    $connect->rollback();
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
