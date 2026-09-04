<?php
require_once __DIR__ . '/../db_config.php';

$branchId = 1;
$missingIngredients = [
    ['Water', 'g'],
    ['Cheesecake', 'g'],
    ['Salted Caramel Drizzle', 'g'],
    ['Whipping Cream', 'g'],
];

$recipes = [
    'Americano' => [
        ['Espresso', 36], ['Water', 240]
    ],
    'Biscoff Creamy Latte' => [
        ['Espresso', 36], ['Milk', 180], ['Biscoff', 25]
    ],
    'Cafe Latte' => [
        ['Espresso', 36], ['Milk', 200]
    ],
    'Caramel Macchiato' => [
        ['Espresso', 36], ['Milk', 180], ['Caramel Drizzle', 15]
    ],
    'Cheesecake Latte' => [
        ['Espresso', 36], ['Milk', 160], ['Cheesecake', 30], ['Full Cream Milk', 30]
    ],
    'Creme Brulee' => [
        ['Espresso', 36], ['Milk', 170], ['Full Cream Milk', 30], ['Brown Sugar', 20]
    ],
    'Dark Mocha' => [
        ['Espresso', 36], ['Milk', 180], ['Chocolate Syrup', 25]
    ],
    'Einspanner Latte' => [
        ['Espresso', 36], ['Milk', 180], ['Whipping Cream', 30], ['Condensed Milk', 25]
    ],
    'French Vanilla' => [
        ['Espresso', 36], ['Milk', 180], ['Vanilla Syrup', 20], ['Condensed Milk', 25]
    ],
    'Horchata' => [
        ['Espresso', 36], ['Milk', 180], ['Cinnamon', 2], ['Condensed Milk', 25]
    ],
    'Mont Blanc' => [
        ['Espresso', 36], ['Milk', 160], ['Chestnut', 20], ['Condensed Milk', 25], ['Full Cream Milk', 30]
    ],
    'Ocean Mist' => [
        ['Espresso', 36], ['Milk', 160], ['Blue Syrup', 20], ['Condensed Milk', 20], ['Full Cream Milk', 30], ['Cinnamon', 2]
    ],
    'Salted Caramel' => [
        ['Espresso', 36], ['Salted Caramel Syrup', 20], ['Salted Caramel Drizzle', 15], ['Condensed Milk', 25]
    ],
    'Sea Salt Latte' => [
        ['Espresso', 36], ['Milk', 180], ['Condensed Milk', 25], ['Sea Salt', 2]
    ],
    'Spanish Latte' => [
        ['Espresso', 36], ['Milk', 170], ['Full Cream Milk', 30], ['Condensed Milk', 30]
    ],
    'White Mocha' => [
        ['Espresso', 36], ['Milk', 180], ['Chocolate Syrup', 25], ['Condensed Milk', 20]
    ],
];

$connect->begin_transaction();
try {
    $findIngredient = $connect->prepare(
        'SELECT id FROM ingredients WHERE LOWER(name) = LOWER(?) AND branch_id = ? LIMIT 1'
    );
    $insertIngredient = $connect->prepare(
        'INSERT INTO ingredients (name, unit, branch_id, stock, min_stock) VALUES (?, ?, ?, 0, 5.000)'
    );
    $ingredientIds = [];

    foreach ($missingIngredients as [$name, $unit]) {
        $findIngredient->bind_param('si', $name, $branchId);
        $findIngredient->execute();
        $row = $findIngredient->get_result()->fetch_assoc();
        if ($row) {
            $ingredientIds[$name] = (int) $row['id'];
            continue;
        }
        $insertIngredient->bind_param('ssi', $name, $unit, $branchId);
        $insertIngredient->execute();
        $ingredientIds[$name] = (int) $connect->insert_id;
    }

    $allNames = [];
    foreach ($recipes as $productName => $items) {
        $allNames[] = $productName;
        foreach ($items as [$ingredientName, $amount]) {
            $findIngredient->bind_param('si', $ingredientName, $branchId);
            $findIngredient->execute();
            $row = $findIngredient->get_result()->fetch_assoc();
            if (!$row) {
                throw new RuntimeException("Ingredient not found: $ingredientName");
            }
            $ingredientIds[$ingredientName] = (int) $row['id'];
        }
    }

    $delete = $connect->prepare('DELETE FROM product_ingredients WHERE product_name = ?');
    $insert = $connect->prepare(
        'INSERT INTO product_ingredients (id, product_name, ingredient_id, amount) VALUES (?, ?, ?, ?)'
    );
    $nextMappingId = (int) ($connect->query('SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM product_ingredients')->fetch_assoc()['next_id'] ?? 1);

    foreach ($recipes as $productName => $items) {
        $delete->bind_param('s', $productName);
        $delete->execute();
        foreach ($items as [$ingredientName, $amount]) {
            $ingredientId = $ingredientIds[$ingredientName];
            $mappingId = $nextMappingId++;
            $insert->bind_param('isid', $mappingId, $productName, $ingredientId, $amount);
            $insert->execute();
        }
    }

    $connect->commit();
    echo 'Coffee recipe mappings saved: ' . count($recipes) . PHP_EOL;
} catch (Throwable $e) {
    $connect->rollback();
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
