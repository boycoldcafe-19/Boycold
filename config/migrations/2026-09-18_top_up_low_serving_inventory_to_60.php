<?php
/**
 * Raise mapped ingredients with 20 or fewer servings remaining to 60 servings.
 *
 * The serving capacity for a shared ingredient is based on the largest quantity
 * used by any one mapped product. This matches the capacity shown by Inventory
 * and Forecasting, and guarantees at least 60 servings for every mapped recipe
 * that uses the ingredient.
 */
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../inventory_service.php';

boycold_ensure_inventory_schema($connect);

$servingThreshold = 20;
$targetServings = 60;
$reference = 'Automatic top-up from 20 to 60 servings';

$recipeResult = $connect->query(
    "SELECT
        LOWER(TRIM(i.name)) AS ingredient_key,
        MAX(pi.amount) AS required_per_serving
     FROM product_ingredients pi
     INNER JOIN ingredients i ON i.id = pi.ingredient_id
     WHERE pi.amount > 0
     GROUP BY LOWER(TRIM(i.name))"
);

$requiredPerServing = [];
while ($recipeResult && ($recipe = $recipeResult->fetch_assoc())) {
    $ingredientKey = boycold_inventory_normalize_name((string) $recipe['ingredient_key']);
    $amount = (float) $recipe['required_per_serving'];

    if ($ingredientKey !== '' && is_finite($amount) && $amount > 0) {
        $requiredPerServing[$ingredientKey] = $amount;
    }
}

$updateStock = $connect->prepare('UPDATE ingredients SET stock = ? WHERE id = ?');
$recordMovement = $connect->prepare(
    "INSERT INTO ingredient_stock_movements
        (ingredient_id, movement_type, quantity, resulting_stock, source, reference)
     VALUES (?, 'stock_in', ?, ?, 'admin', ?)"
);

$connect->begin_transaction();

try {
    $ingredients = $connect->query(
        'SELECT id, name, unit, branch_id, stock FROM ingredients ORDER BY branch_id, id FOR UPDATE'
    );
    $updated = [];

    while ($ingredients && ($ingredient = $ingredients->fetch_assoc())) {
        $ingredientKey = boycold_inventory_normalize_name((string) $ingredient['name']);
        $perServing = $requiredPerServing[$ingredientKey] ?? null;

        if ($perServing === null) {
            continue;
        }

        $currentStock = max(0.0, (float) $ingredient['stock']);
        $remainingServings = boycold_inventory_remaining_servings($currentStock, $perServing);

        if ($remainingServings === null || $remainingServings > $servingThreshold) {
            continue;
        }

        $newStock = $perServing * $targetServings;
        $quantityAdded = $newStock - $currentStock;
        $ingredientId = (int) $ingredient['id'];

        $updateStock->bind_param('di', $newStock, $ingredientId);
        $updateStock->execute();

        $recordMovement->bind_param('idds', $ingredientId, $quantityAdded, $newStock, $reference);
        $recordMovement->execute();

        $updated[] = sprintf(
            'Branch %d - %s: %.3f %s (%d to %d servings)',
            (int) $ingredient['branch_id'],
            $ingredient['name'],
            $quantityAdded,
            $ingredient['unit'],
            $remainingServings,
            $targetServings
        );
    }

    $connect->commit();

    echo 'Updated ' . count($updated) . " ingredient stocks.\n";
    foreach ($updated as $line) {
        echo $line . "\n";
    }
} catch (Throwable $e) {
    $connect->rollback();
    throw $e;
} finally {
    $updateStock->close();
    $recordMovement->close();
}
