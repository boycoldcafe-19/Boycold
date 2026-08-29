<?php
// Script to run the loyalty system SQL migration
// This adds the necessary columns to the database

require_once 'config/db_config.php';

echo "Running loyalty system migration...\n\n";

try {
    // Add loyalty_token column to users table
    echo "Adding loyalty_token column to users table...\n";
    $connect->query("ALTER TABLE users ADD COLUMN loyalty_token VARCHAR(64) UNIQUE DEFAULT NULL COMMENT 'Unique secure token for loyalty QR code'");
    echo "✓ loyalty_token column added\n";

    // Add index for loyalty_token
    echo "Adding index for loyalty_token...\n";
    $connect->query("ALTER TABLE users ADD INDEX idx_loyalty_token (loyalty_token)");
    echo "✓ Index added\n";

    // Add user_id column to orders table
    echo "Adding user_id column to orders table...\n";
    $connect->query("ALTER TABLE orders ADD COLUMN user_id INT DEFAULT NULL COMMENT 'Direct reference to users.id'");
    echo "✓ user_id column added\n";

    // Add index for user_id
    echo "Adding index for user_id...\n";
    $connect->query("ALTER TABLE orders ADD INDEX idx_user_id (user_id)");
    echo "✓ Index added\n";

    // Add loyalty_awarded column to orders table
    echo "Adding loyalty_awarded column to orders table...\n";
    $connect->query("ALTER TABLE orders ADD COLUMN loyalty_awarded TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether loyalty has been awarded for this order'");
    echo "✓ loyalty_awarded column added\n";

    // Add foreign key constraint
    echo "Adding foreign key constraint...\n";
    $connect->query("ALTER TABLE orders ADD CONSTRAINT fk_orders_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE");
    echo "✓ Foreign key constraint added\n";

    echo "\n=== Migration completed successfully! ===\n";
    echo "\nYou can now run: php generate_loyalty_tokens.php\n";

} catch (mysqli_sql_exception $e) {
    // Check if error is about duplicate column
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⚠ Column already exists. Migration may have already been run.\n";
        echo "You can proceed to: php generate_loyalty_tokens.php\n";
    } else {
        echo "✗ Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

$connect->close();
