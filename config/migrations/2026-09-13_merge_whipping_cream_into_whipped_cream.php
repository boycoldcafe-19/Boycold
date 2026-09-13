<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../inventory_service.php';

boycold_ensure_inventory_schema($connect);

$canonicalId = 4; // Whipped Cream
$duplicateId = 52; // Whipping Cream

$connect->begin_transaction();
try {
    $ingredientStmt = $connect->prepare(
        'SELECT id, name, unit, category, branch_id, stock, min_stock
         FROM ingredients WHERE id IN (?, ?) ORDER BY id FOR UPDATE'
    );
    $ingredientStmt->bind_param('ii', $canonicalId, $duplicateId);
    $ingredientStmt->execute();
    $ingredients = [];
    $result = $ingredientStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ingredients[(int) $row['id']] = $row;
    }
    $ingredientStmt->close();

    if (!isset($ingredients[$canonicalId])) {
        throw new RuntimeException('Canonical Whipped Cream ingredient was not found.');
    }

    if (!isset($ingredients[$duplicateId])) {
        $connect->commit();
        echo 'Whipping Cream was already removed; no changes needed.' . PHP_EOL;
        exit;
    }

    $canonical = $ingredients[$canonicalId];
    $duplicate = $ingredients[$duplicateId];
    if ((int) $canonical['branch_id'] !== (int) $duplicate['branch_id']) {
        throw new RuntimeException('Whipped Cream and Whipping Cream belong to different branches.');
    }

    // The user confirmed these are one physical ingredient. Keep the canonical
    // record's unit and preserve all numeric stock rather than rewriting history.
    $stock = (float) $canonical['stock'] + (float) $duplicate['stock'];
    $minStock = max((float) $canonical['min_stock'], (float) $duplicate['min_stock']);
    $update = $connect->prepare('UPDATE ingredients SET stock = ?, min_stock = ? WHERE id = ?');
    $update->bind_param('ddi', $stock, $minStock, $canonicalId);
    $update->execute();
    $update->close();

    $findMappings = $connect->prepare(
        'SELECT id, product_name, amount FROM product_ingredients WHERE ingredient_id = ?'
    );
    $findMappings->bind_param('i', $duplicateId);
    $findMappings->execute();
    $mappings = $findMappings->get_result()->fetch_all(MYSQLI_ASSOC);
    $findMappings->close();

    $findCanonical = $connect->prepare(
        'SELECT id, amount FROM product_ingredients
         WHERE ingredient_id = ? AND LOWER(product_name) = LOWER(?) LIMIT 1'
    );
    $insertMapping = $connect->prepare(
        'INSERT INTO product_ingredients (product_name, ingredient_id, amount) VALUES (?, ?, ?)'
    );
    $deleteMapping = $connect->prepare('DELETE FROM product_ingredients WHERE id = ?');

    foreach ($mappings as $mapping) {
        $mappingId = (int) $mapping['id'];
        $productName = (string) $mapping['product_name'];
        $findCanonical->bind_param('is', $canonicalId, $productName);
        $findCanonical->execute();
        $existing = $findCanonical->get_result()->fetch_assoc();

        if ($existing && (float) $existing['amount'] !== (float) $mapping['amount']) {
            throw new RuntimeException("Conflicting Whipped Cream recipe amounts found for {$productName}.");
        }
        if (!$existing) {
            $amount = (float) $mapping['amount'];
            $insertMapping->bind_param('sid', $productName, $canonicalId, $amount);
            $insertMapping->execute();
        }

        $deleteMapping->bind_param('i', $mappingId);
        $deleteMapping->execute();
    }

    $findCanonical->close();
    $insertMapping->close();
    $deleteMapping->close();

    $remaining = $connect->prepare('SELECT COUNT(*) AS total FROM product_ingredients WHERE ingredient_id = ?');
    $remaining->bind_param('i', $duplicateId);
    $remaining->execute();
    $remainingCount = (int) ($remaining->get_result()->fetch_assoc()['total'] ?? 0);
    $remaining->close();
    if ($remainingCount !== 0) {
        throw new RuntimeException('Whipping Cream still has active product mappings.');
    }

    // Historical ingredient_stock_movements intentionally retain ingredient_id 52.
    $deleteIngredient = $connect->prepare('DELETE FROM ingredients WHERE id = ?');
    $deleteIngredient->bind_param('i', $duplicateId);
    $deleteIngredient->execute();
    if ($deleteIngredient->affected_rows !== 1) {
        throw new RuntimeException('Whipping Cream could not be removed safely.');
    }
    $deleteIngredient->close();

    $connect->commit();
    echo "Merged Whipping Cream into Whipped Cream (ID {$canonicalId}) and removed ID {$duplicateId}." . PHP_EOL;
    echo 'Historical ingredient_stock_movements rows were preserved.' . PHP_EOL;
} catch (Throwable $exception) {
    $connect->rollback();
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
