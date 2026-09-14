<?php
require 'config/db_config.php';

echo "=== VERIFYING QRPH PAYMENT SCHEMA ===\n\n";

// Check orders table columns
echo "Checking 'orders' table columns:\n";
$result = $connect->query("DESCRIBE orders");
$requiredColumns = ['payment_status', 'payment_method', 'payment_reference', 'id', 'status'];
$foundColumns = [];

while ($row = $result->fetch_assoc()) {
    if (in_array($row['Field'], $requiredColumns)) {
        $foundColumns[] = $row['Field'];
        echo "  ✓ {$row['Field']}: {$row['Type']}\n";
    }
}

if (count($foundColumns) === count($requiredColumns)) {
    echo "\n✓ All required columns exist in 'orders' table.\n";
} else {
    $missing = array_diff($requiredColumns, $foundColumns);
    echo "\n✗ MISSING COLUMNS: " . implode(', ', $missing) . "\n";
}

// Verify payment_status enum values
echo "\n\nVerifying 'payment_status' enum values:\n";
$statusResult = $connect->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_status' AND TABLE_SCHEMA = DATABASE()");
if ($statusRow = $statusResult->fetch_assoc()) {
    $columnType = $statusRow['COLUMN_TYPE'];
    echo "  Current: $columnType\n";
    
    $requiredStatuses = ['unpaid', 'pending', 'paid', 'failed', 'expired', 'cancelled'];
    if (preg_match("/enum\((.*?)\)/i", $columnType, $matches)) {
        $values = str_getcsv($matches[1], ',', "'");
        $values = array_map('trim', $values);
        echo "  Values found: " . implode(', ', $values) . "\n";
        
        $missing = array_diff($requiredStatuses, $values);
        if (empty($missing)) {
            echo "  ✓ All required enum values present.\n";
        } else {
            echo "  ✗ Missing values: " . implode(', ', $missing) . "\n";
        }
    }
}

// Check payment_method enum values
echo "\n\nVerifying 'payment_method' enum values:\n";
$methodResult = $connect->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_method' AND TABLE_SCHEMA = DATABASE()");
if ($methodRow = $methodResult->fetch_assoc()) {
    $columnType = $methodRow['COLUMN_TYPE'];
    echo "  Current: $columnType\n";
    
    if (preg_match("/enum\((.*?)\)/i", $columnType, $matches)) {
        $values = str_getcsv($matches[1], ',', "'");
        $values = array_map('trim', $values);
        echo "  Values found: " . implode(', ', $values) . "\n";
        
        if (in_array('qrph', $values)) {
            echo "  ✓ QRPh payment method supported.\n";
        } else {
            echo "  ✗ QRPh payment method NOT found.\n";
        }
    }
}

echo "\n=== VERIFICATION COMPLETE ===\n";
?>
