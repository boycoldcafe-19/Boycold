<?php
require_once '../auth/guard.php';
pos_start_session();
require_once '../config/db_config.php';
require_once '../../config/inventory_service.php';

header('Content-Type: application/json');

$employee = pos_require_employee($connect, true);
$employeeId = (int) $employee['id'];
$branchId = (int) $employee['branch_id'];
boycold_ensure_inventory_schema($connect);

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_inventory':
            echo json_encode(getInventory($connect, $branchId));
            break;

        case 'product_availability':
            echo json_encode([
                'success' => true,
                'branch_id' => $branchId,
                'availability' => boycold_get_product_inventory_availability($connect, $branchId),
            ]);
            break;
            
        case 'update_inventory':
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode(updateInventory($connect, $branchId, $input));
            break;
            
        case 'reset_inventory':
            echo json_encode(resetInventory($connect, $branchId));
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getInventory(mysqli $connect, int $branchId): array {
    $hasMaxStock = boycold_inventory_column_exists($connect, 'ingredients', 'max_stock');
    $maxStockSql = $hasMaxStock ? 'max_stock' : 'GREATEST(stock, min_stock, 1) AS max_stock';
    $stmt = $connect->prepare("SELECT name, unit, stock, {$maxStockSql} FROM ingredients WHERE branch_id = ?");
    $stmt->bind_param('i', $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $inventory = [];
    while ($row = $result->fetch_assoc()) {
        $key = mapIngredientToKey($row['name']);
        if ($key) {
            $inventory[$key] = [
                'current' => (float) $row['stock'],
                'max' => (float) $row['max_stock'],
                'unit' => $row['unit']
            ];
        }
    }
    $stmt->close();
    
    // Ensure all required keys exist with defaults
    $defaults = [
        'coffeeBeans' => ['current' => 1000, 'max' => 1000, 'unit' => 'g'],
        'milk' => ['current' => 1000, 'max' => 1000, 'unit' => 'ml'],
        'matcha' => ['current' => 1000, 'max' => 1000, 'unit' => 'g'],
        'chocolate' => ['current' => 1000, 'max' => 1000, 'unit' => 'g'],
        'cups' => ['current' => 100, 'max' => 100, 'unit' => 'pcs']
    ];
    
    foreach ($defaults as $key => $default) {
        if (!isset($inventory[$key])) {
            $inventory[$key] = $default;
        }
    }
    
    return ['success' => true, 'inventory' => $inventory];
}

function updateInventory(mysqli $connect, int $branchId, mixed $input): array {
    if (!is_array($input)) {
        return ['success' => false, 'error' => 'Invalid inventory data'];
    }

    foreach ($input as $key => $data) {
        $ingredientName = mapKeyToIngredient($key);
        if ($ingredientName && is_array($data)) {
            $current = (float) ($data['current'] ?? 0);
            $stmt = $connect->prepare("UPDATE ingredients SET stock = ? WHERE branch_id = ? AND name = ?");
            $stmt->bind_param('dis', $current, $branchId, $ingredientName);
            $stmt->execute();
            $stmt->close();
        }
    }
    return ['success' => true];
}

function resetInventory(mysqli $connect, int $branchId): array {
    $defaults = [
        'Coffee Beans' => 1000,
        'Whole Milk' => 1000,
        'Oat Milk' => 1000,
        'Matcha' => 1000,
        'Chocolate' => 1000,
        'Espresso Shot' => 1000,
        'Whipped Cream' => 1000,
        'Cups' => 100
    ];
    
    foreach ($defaults as $name => $stock) {
        $stmt = $connect->prepare("UPDATE ingredients SET stock = ? WHERE branch_id = ? AND name = ?");
        $stmt->bind_param('dis', $stock, $branchId, $name);
        $stmt->execute();
        $stmt->close();
    }
    
    return ['success' => true];
}

function mapIngredientToKey(string $name): ?string {
    $map = [
        'Coffee Beans' => 'coffeeBeans',
        'Whole Milk' => 'milk',
        'Oat Milk' => 'milk',
        'Matcha' => 'matcha',
        'Chocolate' => 'chocolate',
        'Cups' => 'cups'
    ];
    return $map[$name] ?? null;
}

function mapKeyToIngredient(string $key): ?string {
    $map = [
        'coffeeBeans' => 'Coffee Beans',
        'milk' => 'Whole Milk',
        'matcha' => 'Matcha',
        'chocolate' => 'Chocolate',
        'cups' => 'Cups'
    ];
    return $map[$key] ?? null;
}
