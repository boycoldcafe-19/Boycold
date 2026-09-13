<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../inventory_service.php';

boycold_ensure_inventory_schema($connect);

$sourceBranchId = 1;
$targetBranchId = 2;

$branch = $connect->prepare(
    "INSERT INTO branches (id, branch_code, branch_name, status)
     VALUES (?, 'BUSTOS', 'Bustos Branch', 'active')
     ON DUPLICATE KEY UPDATE branch_code = VALUES(branch_code), branch_name = VALUES(branch_name), status = VALUES(status)"
);
$branch->bind_param('i', $targetBranchId);
$branch->execute();
$branch->close();

$source = $connect->prepare(
    "SELECT name, category, unit, stock, min_stock
         FROM ingredients
         WHERE branch_id = ?
             AND LOWER(name) NOT IN ('whipping cream', 'condense', 'condensed')
         ORDER BY id"
);
$source->bind_param('i', $sourceBranchId);
$source->execute();
$rows = $source->get_result()->fetch_all(MYSQLI_ASSOC);
$source->close();

$insert = $connect->prepare(
    'INSERT INTO ingredients (name, category, unit, branch_id, stock, min_stock)
     SELECT ?, ?, ?, ?, ?, ?
     WHERE NOT EXISTS (
         SELECT 1 FROM ingredients WHERE branch_id = ? AND LOWER(name) = LOWER(?)
     )'
);

$created = 0;
foreach ($rows as $row) {
    $name = (string) $row['name'];
    $category = (string) ($row['category'] ?? 'Other');
    $unit = (string) $row['unit'];
    $stock = (float) ($row['stock'] ?? 0);
    $minStock = (float) ($row['min_stock'] ?? 0);
    $insert->bind_param('sssiddis', $name, $category, $unit, $targetBranchId, $stock, $minStock, $targetBranchId, $name);
    $insert->execute();
    $created += $insert->affected_rows;
}
$insert->close();

$sync = $connect->prepare(
    'UPDATE ingredients target
     INNER JOIN ingredients source
         ON LOWER(source.name) = LOWER(target.name)
        AND source.branch_id = ?
     SET target.stock = source.stock
     WHERE target.branch_id = ?
       AND target.stock = 0
       AND NOT EXISTS (
           SELECT 1
           FROM ingredient_stock_movements movement
           WHERE movement.ingredient_id = target.id
       )'
);
$sync->bind_param('ii', $sourceBranchId, $targetBranchId);
$sync->execute();
$synced = $sync->affected_rows;
$sync->close();

$movement = $connect->prepare(
    "INSERT INTO ingredient_stock_movements
        (ingredient_id, movement_type, quantity, resulting_stock, source, reference)
     SELECT target.id, 'stock_in', target.stock, target.stock, 'branch_seed', ?
     FROM ingredients target
     INNER JOIN ingredients source
         ON LOWER(source.name) = LOWER(target.name)
        AND source.branch_id = ?
     WHERE target.branch_id = ?
       AND target.stock > 0
       AND NOT EXISTS (
           SELECT 1
           FROM ingredient_stock_movements movement
           WHERE movement.ingredient_id = target.id
       )"
);
$reference = 'Initial Bustos stock copied from Baliuag';
$movement->bind_param('sii', $reference, $sourceBranchId, $targetBranchId);
$movement->execute();
$movementCount = $movement->affected_rows;
$movement->close();

echo "Bustos branch inventory ready; created {$created} ingredient records and synced {$synced} stock values." . PHP_EOL;
echo "Recorded {$movementCount} opening stock movements for Bustos." . PHP_EOL;
