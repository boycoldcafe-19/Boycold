<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../inventory_service.php';

boycold_ensure_inventory_schema($connect);

$recipes = [
    'Spanish Latte' => [['Espresso', 36], ['Milk', 170], ['Full Cream Milk', 30], ['Condensed Milk', 30]],
    'Sea Salt Latte' => [['Espresso', 36], ['Milk', 180], ['Condensed Milk', 25], ['Sea Salt', 2]],
    'Caramel Macchiato' => [['Espresso', 36], ['Milk', 180], ['Caramel Drizzle', 15]],
    'Salted Caramel' => [['Espresso', 36], ['Salted Caramel Syrup', 20], ['Salted Caramel Drizzle', 15], ['Condensed Milk', 25]],
    'Horchata' => [['Espresso', 36], ['Milk', 180], ['Cinnamon', 2], ['Condensed Milk', 25]],
    'Ocean Mist' => [['Espresso', 36], ['Milk', 160], ['Blue Syrup', 20], ['Condensed Milk', 20], ['Full Cream Milk', 30], ['Cinnamon', 2]],
    'Creme Brulee' => [['Espresso', 36], ['Milk', 170], ['Full Cream Milk', 30], ['Brown Sugar', 20]],
    'Biscoff Creamy Latte' => [['Espresso', 36], ['Milk', 180], ['Biscoff', 25]],
    'Choco Vanilla Cookie' => [['Milk', 180], ['Chocolate Syrup', 20], ['Vanilla Syrup', 20], ['Condense', 15]],
    'Choco Banana Pudding' => [['Milk', 150], ['Banana', 60], ['Chocolate Pudding', 50], ['Chocolate Syrup', 20], ['Crushed Cookies', 20], ['Whipped Cream', 20]],
    'Seasalt Matcha' => [['Matcha Powder', 5], ['Milk', 180], ['Sea Salt Cream', 30], ['Condense', 15]],
    'Matcha Freddo' => [['Matcha Powder', 5], ['Milk', 180], ['Condense', 15], ['Vanilla Syrup', 15], ['Whipped Cream', 20]],
    'Cheesecake Matcha' => [['Matcha Powder', 5], ['Milk', 180], ['Cheesecake Sauce', 25], ['Condense', 15], ['Cream Cheese', 20]],
    'Biscoff Matcha' => [['Matcha Powder', 5], ['Milk', 180], ['Biscoff', 25], ['Condense', 15]],
    'Matcha banana Pudding' => [['Matcha Powder', 5], ['Milk', 150], ['Banana', 60], ['Matcha Pudding', 50], ['Condense', 15], ['Crushed Cookies', 20], ['Whipped Cream', 20]],
    'hershey delight' => [['Milk', 180], ["Hershey's Chocolate Syrup", 30], ['Chocolate Powder', 20], ['Condense', 15], ['Whipped Cream', 20], ['Chocolate Chips', 20]],
    'Java Chips' => [['Milk', 180], ['Espresso', 36], ['Chocolate Syrup', 25], ['Chocolate Chips', 30], ['Condense', 15], ['Whipped Cream', 20]],
    'Black Forrest' => [['Milk', 180], ['Chocolate Syrup', 25], ['Cherry Syrup', 20], ['Condense', 15], ['Whipped Cream', 20], ['Chocolate Chips', 20]],
];

$connect->begin_transaction();
try {
    $findIngredient = $connect->prepare(
        'SELECT id FROM ingredients WHERE LOWER(name) = LOWER(?) AND branch_id = ? LIMIT 1'
    );
    $insertIngredient = $connect->prepare(
        "INSERT INTO ingredients (name, unit, branch_id, stock, min_stock) VALUES (?, ?, ?, 0, 5.000)"
    );
    $ingredientIds = [];

    foreach ($recipes as $items) {
        foreach ($items as [$ingredientName, $amount]) {
            if (isset($ingredientIds[$ingredientName])) {
                continue;
            }
            $branchId = 1;
            $findIngredient->bind_param('si', $ingredientName, $branchId);
            $findIngredient->execute();
            $row = $findIngredient->get_result()->fetch_assoc();
            if (!$row) {
                $unit = 'g';
                $insertIngredient->bind_param('ssi', $ingredientName, $unit, $branchId);
                $insertIngredient->execute();
                $ingredientIds[$ingredientName] = (int) $connect->insert_id;
            } else {
                $ingredientIds[$ingredientName] = (int) $row['id'];
            }
        }
    }

    $delete = $connect->prepare('DELETE FROM product_ingredients WHERE LOWER(product_name) = LOWER(?)');
    $insert = $connect->prepare(
        'INSERT INTO product_ingredients (product_name, ingredient_id, amount) VALUES (?, ?, ?)'
    );

    foreach ($recipes as $productName => $items) {
        $delete->bind_param('s', $productName);
        $delete->execute();
        foreach ($items as [$ingredientName, $amount]) {
            $ingredientId = $ingredientIds[$ingredientName];
            $insert->bind_param('sid', $productName, $ingredientId, $amount);
            $insert->execute();
        }
    }

    $connect->commit();
    echo 'Popular Categories inventory mappings saved: ' . count($recipes) . PHP_EOL;
} catch (Throwable $e) {
    $connect->rollback();
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
