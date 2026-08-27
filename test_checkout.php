<?php
session_start();
require_once 'config/db_config.php';

echo "Testing checkout.php dependencies...\n\n";

// Check session
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Session user_name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "\n\n";

// Try the addresses query
if (isset($_SESSION['user_name'])) {
    $userName = $_SESSION['user_name'];
    echo "Testing addresses query with user_name: $userName\n";
    
    try {
        $addrStmt = $connect->prepare(
            "SELECT id, label, recipient_name, phone, street_address, barangay, city, province, zip_code, is_default
             FROM addresses
             WHERE user_name = ?
             ORDER BY is_default DESC, created_at DESC"
        );
        $addrStmt->bind_param("s", $userName);
        $addrStmt->execute();
        $savedAddresses = $addrStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $addrStmt->close();
        
        echo "✓ Addresses query successful. Found " . count($savedAddresses) . " addresses\n";
    } catch (Exception $e) {
        echo "✗ Addresses query failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ Cannot test addresses query - user_name not set in session\n";
}

// Try the branches query
echo "\nTesting branches query...\n";
try {
    $branchStmt = $connect->prepare("SELECT id, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name");
    $branchStmt->execute();
    $branches = $branchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $branchStmt->close();
    
    echo "✓ Branches query successful. Found " . count($branches) . " branches\n";
} catch (Exception $e) {
    echo "✗ Branches query failed: " . $e->getMessage() . "\n";
}

$connect->close();
