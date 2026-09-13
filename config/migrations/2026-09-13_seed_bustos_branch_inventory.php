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
        "SELECT name, category, unit, min_stock
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
     SELECT ?, ?, ?, ?, 0, ?
     WHERE NOT EXISTS (
         SELECT 1 FROM ingredients WHERE branch_id = ? AND LOWER(name) = LOWER(?)
     )'
);

$created = 0;
foreach ($rows as $row) {
    $name = (string) $row['name'];
    $category = (string) ($row['category'] ?? 'Other');
    $unit = (string) $row['unit'];
    $minStock = (float) ($row['min_stock'] ?? 0);
    $insert->bind_param('sssidis', $name, $category, $unit, $targetBranchId, $minStock, $targetBranchId, $name);
    $insert->execute();
    $created += $insert->affected_rows;
}
$insert->close();

echo "Bustos branch inventory ready; created {$created} ingredient records with zero opening stock." . PHP_EOL;
