<?php
require_once __DIR__ . '/../db_config.php';

$recipes = [
    'Blueberry Milk' => [
        ['Milk', 180], ['Blueberry Syrup', 30], ['Condense', 15]
    ],
    'Choco Banana Pudding' => [
        ['Milk', 150], ['Banana', 60], ['Chocolate Pudding', 50],
        ['Chocolate Syrup', 20], ['Crushed Cookies', 20], ['Whipped Cream', 20]
    ],
    'Choco Berry' => [
        ['Milk', 180], ['Strawberry Syrup', 25], ['Chocolate Syrup', 20], ['Condense', 15]
    ],
    'Choco Vanilla Cookie' => [
        ['Milk', 180], ['Chocolate Syrup', 20], ['Vanilla Syrup', 20], ['Condense', 15]
    ],
    'Milky Oreo' => [
        ['Milk', 180], ['Oreo Cookies', 30], ['Condensed Milk', 25], ['Whipped Cream', 20]
    ],
    'Strawberry Milk' => [
        ['Milk', 180], ['Strawberry Syrup', 30], ['Condense', 15]
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
    echo 'Non-coffee recipe mappings saved: ' . count($recipes) . PHP_EOL;
} catch (Throwable $e) {
    $connect->rollback();
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
