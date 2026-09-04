<?php
require_once __DIR__ . '/../db_config.php';

$recipes = [
    'Berry mango' => [
        ['Milk', 180], ['Strawberry Syrup', 25], ['Mango Syrup', 30], ['Condense', 15]
    ],
    'Blueberry' => [
        ['Milk', 180], ['Blueberry Syrup', 30], ['Condense', 15]
    ],
    'Mango Graham' => [
        ['Milk', 180], ['Mango Syrup', 30], ['Condense', 15],
        ['Crushed Graham', 30], ['Whipped Cream', 20]
    ],
    'Strawberry' => [
        ['Milk', 180], ['Strawberry Syrup', 30], ['Condense', 15]
    ],
    'Tropical Matcha Yogurt' => [
        ['Matcha Powder', 5], ['Yogurt', 100], ['Milk', 100],
        ['Mango Syrup', 20], ['Strawberry Syrup', 20], ['Condense', 15]
    ],
    'Ube Yogurt' => [
        ['Yogurt', 100], ['Milk', 100], ['Ube Syrup', 30], ['Condense', 15]
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
    echo 'Smoothie recipe mappings saved: ' . count($recipes) . PHP_EOL;
} catch (Throwable $e) {
    $connect->rollback();
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
