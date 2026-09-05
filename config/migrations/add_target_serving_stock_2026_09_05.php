<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../inventory_service.php';

boycold_ensure_inventory_schema($connect);

$branchId = 1;

$drinkTargets = [
    'Cheesecake Latte' => [
        'servings' => 150,
        'recipe' => [
            ['Espresso', 'g', 36],
            ['Milk', 'g', 160],
            ['Cheesecake', 'g', 30],
            ['Full Cream Milk', 'g', 30],
        ],
    ],
    'Einspanner Latte' => [
        'servings' => 150,
        'recipe' => [
            ['Espresso', 'g', 36],
            ['Milk', 'g', 180],
            ['Whipping Cream', 'g', 30],
            ['Condensed Milk', 'g', 25],
        ],
    ],
    'Salted Caramel' => [
        'servings' => 150,
        'recipe' => [
            ['Espresso', 'g', 36],
            ['Salted Caramel Syrup', 'g', 20],
            ['Salted Caramel Drizzle', 'g', 15],
            ['Condensed Milk', 'g', 25],
        ],
    ],
];

$foodRecipes = [
    'Honey Gochujang Katsu' => [
        ['Rice', 'g', 180],
        ['Chicken Katsu', 'g', 120],
        ['Honey Gochujang Sauce', 'g', 40],
    ],
    'Dak Galbi' => [
        ['Rice', 'g', 180],
        ['Chicken Dak Galbi', 'g', 140],
        ['Cabbage', 'g', 40],
        ['Gochujang Sauce', 'g', 30],
    ],
    'Salted Egg Fish Fillet' => [
        ['Rice', 'g', 180],
        ['Fish Fillet', 'g', 140],
        ['Salted Egg Sauce', 'g', 40],
    ],
    'French Fries' => [
        ['Fries', 'g', 150],
    ],
    'Cheezy Fries' => [
        ['Fries', 'g', 150],
        ['Cheese Sauce', 'g', 40],
    ],
    'Chicken Poppers' => [
        ['Chicken Poppers', 'g', 150],
    ],
    'Chicken poppers and fries' => [
        ['Fries', 'g', 100],
        ['Chicken Poppers', 'g', 100],
    ],
    'Fries and Chicken Poppers' => [
        ['Fries', 'g', 100],
        ['Chicken Poppers', 'g', 100],
    ],
    'Fries & Chicken Tenders' => [
        ['Fries', 'g', 100],
        ['Chicken Tenders', 'g', 120],
    ],
    'Beef Natchos' => [
        ['Nacho Chips', 'g', 100],
        ['Beef Topping', 'g', 60],
        ['Cheese Sauce', 'g', 40],
    ],
    'Nachos' => [
        ['Nacho Chips', 'g', 100],
        ['Beef Topping', 'g', 60],
        ['Cheese Sauce', 'g', 40],
    ],
    'Aglio Olio' => [
        ['Pasta Noodles', 'g', 160],
        ['Garlic Oil Sauce', 'g', 50],
        ['Sardines', 'g', 60],
    ],
    'Carbonara' => [
        ['Pasta Noodles', 'g', 160],
        ['Carbonara Sauce', 'g', 90],
        ['Bacon Bits', 'g', 30],
    ],
    'Chicken Alfredo' => [
        ['Pasta Noodles', 'g', 160],
        ['Alfredo Sauce', 'g', 90],
        ['Chicken Strips', 'g', 80],
    ],
    'Chicken Pesto' => [
        ['Pasta Noodles', 'g', 160],
        ['Pesto Sauce', 'g', 70],
        ['Chicken Strips', 'g', 80],
    ],
    'Lolly Chocolate waffle' => [
        ['Waffle Batter', 'g', 120],
        ['Chocolate Syrup', 'g', 25],
    ],
    'Lolly Matcha waffle' => [
        ['Waffle Batter', 'g', 120],
        ['Matcha Powder', 'g', 5],
    ],
    'Lolly Biscoff waffle' => [
        ['Waffle Batter', 'g', 120],
        ['Biscoff', 'g', 25],
    ],
    'Lolly Oreo waffle' => [
        ['Waffle Batter', 'g', 120],
        ['Oreo Cookies', 'g', 25],
    ],
    'Lolly Strawberry waffle' => [
        ['Waffle Batter', 'g', 120],
        ['Strawberry Syrup', 'g', 25],
    ],
    'Lolly Tiramisu waffle' => [
        ['Waffle Batter', 'g', 120],
        ['Tiramisu', 'g', 25],
    ],
    'Lolly tiramisu waffle' => [
        ['Waffle Batter', 'g', 120],
        ['Tiramisu', 'g', 25],
    ],
    'Lolly Ube waffle' => [
        ['Waffle Batter', 'g', 120],
        ['Ube Syrup', 'g', 25],
    ],
    'Lolly ube waffle' => [
        ['Waffle Batter', 'g', 120],
        ['Ube Syrup', 'g', 25],
    ],
    'Beef Quesadilla' => [
        ['Tortilla', 'pcs', 1],
        ['Beef Filling', 'g', 100],
        ['Cheese', 'g', 40],
    ],
    'Chicken Quesadilla' => [
        ['Tortilla', 'pcs', 1],
        ['Chicken Filling', 'g', 100],
        ['Cheese', 'g', 40],
    ],
    'Messy Tuna Quesadilla' => [
        ['Tortilla', 'pcs', 1],
        ['Tuna Filling', 'g', 100],
        ['Spinach', 'g', 30],
        ['Cheese', 'g', 40],
    ],
];

function findProductName(mysqli $connect, string $name): ?string
{
    $stmt = $connect->prepare(
        'SELECT product_name FROM products WHERE LOWER(product_name) = LOWER(?) LIMIT 1'
    );
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? (string) $row['product_name'] : null;
}

function findOrCreateIngredient(mysqli $connect, string $name, string $unit, int $branchId): int
{
    $stmt = $connect->prepare(
        'SELECT id FROM ingredients WHERE LOWER(name) = LOWER(?) AND branch_id = ? LIMIT 1'
    );
    $stmt->bind_param('si', $name, $branchId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        return (int) $row['id'];
    }

    $category = 'Inventory';
    $stock = 0.0;
    $minStock = 5.0;
    $insert = $connect->prepare(
        'INSERT INTO ingredients (name, category, unit, stock, min_stock, branch_id) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insert->bind_param('sssddi', $name, $category, $unit, $stock, $minStock, $branchId);
    $insert->execute();
    $id = (int) $connect->insert_id;
    $insert->close();

    return $id;
}

function mappingCount(mysqli $connect, string $productName): int
{
    $stmt = $connect->prepare('SELECT COUNT(*) AS cnt FROM product_ingredients WHERE product_name = ?');
    $stmt->bind_param('s', $productName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($row['cnt'] ?? 0);
}

function replaceMapping(mysqli $connect, string $productName, array $recipe, int $branchId): void
{
    $delete = $connect->prepare('DELETE FROM product_ingredients WHERE product_name = ?');
    $delete->bind_param('s', $productName);
    $delete->execute();
    $delete->close();

    $insert = $connect->prepare(
        'INSERT INTO product_ingredients (product_name, ingredient_id, amount) VALUES (?, ?, ?)'
    );
    foreach ($recipe as [$ingredientName, $unit, $amount]) {
        $ingredientId = findOrCreateIngredient($connect, $ingredientName, $unit, $branchId);
        $amount = (float) $amount;
        $insert->bind_param('sid', $productName, $ingredientId, $amount);
        $insert->execute();
    }
    $insert->close();
}

function topUpProductForServings(mysqli $connect, string $productName, int $servings, int $branchId, string $reference): array
{
    $mapping = $connect->prepare(
        'SELECT pi.amount, i.id, i.name, i.unit, i.stock
         FROM product_ingredients pi
         JOIN ingredients i ON i.id = pi.ingredient_id
         WHERE pi.product_name = ?
         ORDER BY i.name
         FOR UPDATE'
    );
    $mapping->bind_param('s', $productName);
    $mapping->execute();
    $rows = $mapping->get_result()->fetch_all(MYSQLI_ASSOC);
    $mapping->close();

    $added = [];
    foreach ($rows as $row) {
        $ingredientId = (int) $row['id'];
        $neededStock = (float) $row['amount'] * $servings;
        $currentStock = (float) $row['stock'];
        $addQuantity = max(0.0, $neededStock - $currentStock);

        if ($addQuantity <= 0) {
            continue;
        }

        $newStock = $currentStock + $addQuantity;
        $update = $connect->prepare('UPDATE ingredients SET stock = ? WHERE id = ?');
        $update->bind_param('di', $newStock, $ingredientId);
        $update->execute();
        $update->close();

        $source = 'admin';
        $movement = $connect->prepare(
            "INSERT INTO ingredient_stock_movements
                (ingredient_id, movement_type, quantity, resulting_stock, source, product_name, reference)
             VALUES (?, 'stock_in', ?, ?, ?, ?, ?)"
        );
        $movement->bind_param('iddsss', $ingredientId, $addQuantity, $newStock, $source, $productName, $reference);
        $movement->execute();
        $movement->close();

        $added[] = [
            'ingredient' => (string) $row['name'],
            'quantity' => $addQuantity,
            'unit' => (string) $row['unit'],
            'new_stock' => $newStock,
        ];
    }

    return $added;
}

$connect->begin_transaction();

try {
    $summary = [];

    foreach ($drinkTargets as $requestedName => $target) {
        $productName = findProductName($connect, $requestedName);
        if (!$productName) {
            $summary[] = "$requestedName: product not found";
            continue;
        }

        if (mappingCount($connect, $productName) === 0) {
            replaceMapping($connect, $productName, $target['recipe'], $branchId);
        }

        $added = topUpProductForServings(
            $connect,
            $productName,
            (int) $target['servings'],
            $branchId,
            "Stock top-up for {$target['servings']} servings"
        );
        $summary[] = $productName . ': ' . (count($added) ? json_encode($added) : 'already enough stock');
    }

    foreach ($foodRecipes as $requestedName => $recipe) {
        $productName = findProductName($connect, $requestedName);
        if (!$productName) {
            continue;
        }

        if (mappingCount($connect, $productName) === 0) {
            replaceMapping($connect, $productName, $recipe, $branchId);
        }

        $added = topUpProductForServings(
            $connect,
            $productName,
            20,
            $branchId,
            'Food category stock top-up for 20 servings'
        );
        $summary[] = $productName . ': ' . (count($added) ? json_encode($added) : 'already enough stock');
    }

    $connect->commit();

    echo implode(PHP_EOL, $summary) . PHP_EOL;
} catch (Throwable $e) {
    $connect->rollback();
    throw $e;
}
