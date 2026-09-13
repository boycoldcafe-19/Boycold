<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../inventory_service.php';

boycold_ensure_inventory_schema($connect);

if (!boycold_inventory_index_exists($connect, 'product_ingredients', 'uq_product_ingredient')) {
    $connect->query(
        'ALTER TABLE product_ingredients
         ADD UNIQUE KEY uq_product_ingredient (product_name, ingredient_id)'
    );
}

$canonicalId = 14; // Condensed Milk
$candidateDuplicateIds = [22, 28]; // Condense, Condensed

$connect->begin_transaction();
try {
    $ingredientStmt = $connect->prepare(
        'SELECT id, name, unit, category, branch_id, stock, min_stock
         FROM ingredients WHERE id IN (?, ?, ?) ORDER BY id FOR UPDATE'
    );
    $ingredientStmt->bind_param('iii', $canonicalId, $candidateDuplicateIds[0], $candidateDuplicateIds[1]);
    $ingredientStmt->execute();
    $ingredients = [];
    $result = $ingredientStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ingredients[(int) $row['id']] = $row;
    }
    $ingredientStmt->close();

    if (!isset($ingredients[$canonicalId])) {
        throw new RuntimeException('Canonical Condensed Milk ingredient was not found.');
    }

    $canonical = $ingredients[$canonicalId];
    $duplicateIds = array_values(array_filter(
        $candidateDuplicateIds,
        static fn (int $duplicateId): bool => isset($ingredients[$duplicateId])
    ));
    foreach ($duplicateIds as $duplicateId) {
        $duplicate = $ingredients[$duplicateId];
        if ((int) $duplicate['branch_id'] !== (int) $canonical['branch_id'] || $duplicate['unit'] !== $canonical['unit']) {
            throw new RuntimeException("Cannot merge {$duplicate['name']}: branch or unit differs from Condensed Milk.");
        }
    }

    // Same-unit duplicate inventory is consolidated; historical movement rows are untouched.
    $stock = (float) $canonical['stock'];
    $minStock = (float) $canonical['min_stock'];
    foreach ($duplicateIds as $duplicateId) {
        $stock += (float) $ingredients[$duplicateId]['stock'];
        $minStock = max($minStock, (float) $ingredients[$duplicateId]['min_stock']);
    }
    $updateStock = $connect->prepare('UPDATE ingredients SET stock = ?, min_stock = ? WHERE id = ?');
    $updateStock->bind_param('ddi', $stock, $minStock, $canonicalId);
    $updateStock->execute();
    $updateStock->close();

    $findMapping = $connect->prepare(
        'SELECT id, product_name, amount FROM product_ingredients WHERE ingredient_id = ?'
    );
    $findCanonicalMapping = $connect->prepare(
        'SELECT id, amount FROM product_ingredients
         WHERE ingredient_id = ? AND LOWER(product_name) = LOWER(?) LIMIT 1'
    );
    $insertMapping = $connect->prepare(
        'INSERT INTO product_ingredients (product_name, ingredient_id, amount)
         VALUES (?, ?, ?)'
    );
    $deleteMapping = $connect->prepare('DELETE FROM product_ingredients WHERE id = ?');

    foreach ($duplicateIds as $duplicateId) {
        $findMapping->bind_param('i', $duplicateId);
        $findMapping->execute();
        $mappingRows = $findMapping->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($mappingRows as $mapping) {
            $mappingId = (int) $mapping['id'];
            $productName = (string) $mapping['product_name'];

            $canonicalIdForLookup = $canonicalId;
            $findCanonicalMapping->bind_param('is', $canonicalIdForLookup, $productName);
            $findCanonicalMapping->execute();
            $canonicalMapping = $findCanonicalMapping->get_result()->fetch_assoc();

            if ($canonicalMapping) {
                if ((float) $canonicalMapping['amount'] !== (float) $mapping['amount']) {
                    throw new RuntimeException("Conflicting recipe amounts found for {$productName}; merge stopped.");
                }
            } else {
                $amount = (float) $mapping['amount'];
                $insertMapping->bind_param('sid', $productName, $canonicalId, $amount);
                $insertMapping->execute();
            }

            $deleteMapping->bind_param('i', $mappingId);
            $deleteMapping->execute();
        }
    }

    $findMapping->close();
    $findCanonicalMapping->close();
    $insertMapping->close();
    $deleteMapping->close();

    foreach ($duplicateIds as $duplicateId) {
        $remainingMappings = $connect->prepare('SELECT COUNT(*) AS total FROM product_ingredients WHERE ingredient_id = ?');
        $remainingMappings->bind_param('i', $duplicateId);
        $remainingMappings->execute();
        $remainingCount = (int) ($remainingMappings->get_result()->fetch_assoc()['total'] ?? 0);
        $remainingMappings->close();
        if ($remainingCount !== 0) {
            throw new RuntimeException("Could not safely remove duplicate ingredient ID {$duplicateId}: active mappings remain.");
        }

        $deleteIngredient = $connect->prepare('DELETE FROM ingredients WHERE id = ?');
        $deleteIngredient->bind_param('i', $duplicateId);
        $deleteIngredient->execute();
        if ($deleteIngredient->affected_rows !== 1) {
            throw new RuntimeException("Could not safely remove duplicate ingredient ID {$duplicateId}.");
        }
        $deleteIngredient->close();
    }

    $connect->commit();
    echo $duplicateIds
        ? "Merged Condense and Condensed into Condensed Milk (ID {$canonicalId})." . PHP_EOL
        : "Confirmed duplicate ingredients were already cleaned; Condensed Milk (ID {$canonicalId}) is canonical." . PHP_EOL;
    echo "Historical ingredient_stock_movements rows were preserved." . PHP_EOL;
} catch (Throwable $exception) {
    $connect->rollback();
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
