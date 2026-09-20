<?php
require_once __DIR__ . '/../db_config.php';

// This runner is idempotent so it can be safely used on local and deployed
// databases that may already have some of these columns.
$columns = [
    'pos_password_failed_attempts' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER password',
    'pos_password_locked_at' => 'DATETIME NULL DEFAULT NULL AFTER pos_password_failed_attempts',
    'pos_pin_failed_attempts' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER pin',
    'pos_pin_locked_at' => 'DATETIME NULL DEFAULT NULL AFTER pos_pin_failed_attempts',
];

foreach ($columns as $column => $definition) {
    // MySQL/MariaDB do not accept a bound parameter in SHOW COLUMNS, so the
    // known migration column name is escaped before building this statement.
    $safeColumn = $connect->real_escape_string($column);
    $check = $connect->query("SHOW COLUMNS FROM employees LIKE '$safeColumn'");
    $exists = $check instanceof mysqli_result && $check->num_rows > 0;

    if ($exists) {
        echo "$column already exists.\n";
        continue;
    }

    if (!$connect->query("ALTER TABLE employees ADD COLUMN `$column` $definition")) {
        throw new RuntimeException("Unable to add $column: " . $connect->error);
    }

    echo "Added $column.\n";
}

echo "POS login lockout migration completed.\n";
