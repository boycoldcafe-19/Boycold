<?php
require_once __DIR__ . '/../db_config.php';

$recipes = [
    'Biscoff Matcha' => [
        ['Matcha Powder', 5], ['Milk', 180], ['Biscoff', 25], ['Condense', 15]
    ],
    'Cheesecake Matcha' => [
        ['Matcha Powder', 5], ['Milk', 180], ['Cheesecake Sauce', 25],
        ['Condense', 15], ['Cream Cheese', 20]
    ],
    'Mango matcha' => [
        ['Matcha Powder', 5], ['Milk', 180], ['Mango Syrup', 30], ['Condense', 15]
    ],
    'Matcha banana Pudding' => [
        ['Matcha Powder', 5], ['Milk', 150], ['Banana', 60], ['Matcha Pudding', 50],
        ['Condense', 15], ['Crushed Cookies', 20], ['Whipped Cream', 20]
    ],
    'Matcha Freddo' => [
        ['Matcha Powder', 5], ['Milk', 180], ['Condense', 15],
        ['Vanilla Syrup', 15], ['Whipped Cream', 20]
    ],
    'Matcha Latte' => [
        ['Matcha Powder', 5], ['Milk', 200], ['Condense', 15]
    ],
    'Seasalt Matcha' => [
        ['Matcha Powder', 5], ['Milk', 180], ['Sea Salt Cream', 30], ['Condense', 15]
    ],
    'Strawberry Matcha' => [
        ['Matcha Powder', 5], ['Milk', 180], ['Strawberry Syrup', 30], ['Condense', 15]
    ],
    'Ube Matcha' => [
        ['Matcha Powder', 5], ['Milk', 180], ['Ube Syrup', 30], ['Condense', 15]
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
    echo 'Matcha Fusion recipe mappings saved: ' . count($recipes) . PHP_EOL;
} catch (Throwable $e) {
    $connect->rollback();
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
