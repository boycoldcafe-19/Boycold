<?php
require_once __DIR__ . '/../db_config.php';

// Check if account_status column exists
$columnCheck = $connect->query("SHOW COLUMNS FROM users LIKE 'account_status'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    echo "account_status column already exists in users table.\n";
} else {
    // Add account_status column
    $alterSql = "ALTER TABLE users ADD COLUMN account_status ENUM('active', 'inactive') DEFAULT 'active' AFTER is_verified";
    if ($connect->query($alterSql)) {
        echo "Successfully added account_status column to users table.\n";
    } else {
        echo "Error adding account_status column: " . $connect->error . "\n";
    }
}

// Update existing inactive loyalty cards to have inactive account status
$updateSql = "UPDATE users SET account_status = 'inactive' WHERE loyalty_card_status = 'inactive'";
if ($connect->query($updateSql)) {
    echo "Updated existing inactive users to have inactive account status.\n";
} else {
    echo "Error updating existing users: " . $connect->error . "\n";
}

echo "Migration completed.\n";
?>
