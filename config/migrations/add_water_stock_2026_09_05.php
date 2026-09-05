<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../inventory_service.php';

boycold_ensure_inventory_schema($connect);

$quantity = 37854.12;
$branchId = 1;
$ingredientName = 'Water';

$connect->begin_transaction();

try {
    $stmt = $connect->prepare(
        'SELECT id, stock FROM ingredients WHERE name = ? AND branch_id = ? LIMIT 1 FOR UPDATE'
    );
    $stmt->bind_param('si', $ingredientName, $branchId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new RuntimeException('Water ingredient not found for branch 1.');
    }

    $ingredientId = (int) $row['id'];
    $newStock = (float) $row['stock'] + $quantity;

    $update = $connect->prepare('UPDATE ingredients SET stock = ? WHERE id = ?');
    $update->bind_param('di', $newStock, $ingredientId);
    $update->execute();
    $update->close();

    $source = 'admin';
    $reference = 'Manual stock-in: Water availability';
    $movement = $connect->prepare(
        "INSERT INTO ingredient_stock_movements
            (ingredient_id, movement_type, quantity, resulting_stock, source, reference)
         VALUES (?, 'stock_in', ?, ?, ?, ?)"
    );
    $movement->bind_param('iddss', $ingredientId, $quantity, $newStock, $source, $reference);
    $movement->execute();
    $movement->close();

    $connect->commit();

    echo 'Water updated: +' . number_format($quantity, 2) . ' g, new stock=' . number_format($newStock, 2) . " g\n";
} catch (Throwable $e) {
    $connect->rollback();
    throw $e;
}
