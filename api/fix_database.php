<?php
require_once __DIR__ . '/../config/db_config.php';

function tableExists(mysqli $connect, string $table): bool
{
    $stmt = $connect->prepare("SHOW TABLES LIKE ?");
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function columnExists(mysqli $connect, string $table, string $column): bool
{
    $stmt = $connect->prepare('SHOW COLUMNS FROM `'. $table .'` LIKE ?');
    $stmt->bind_param('s', $column);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function indexExists(mysqli $connect, string $table, string $index): bool
{
    $stmt = $connect->prepare('SHOW INDEX FROM `'. $table .'` WHERE Key_name = ?');
    $stmt->bind_param('s', $index);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function runSql(mysqli $connect, string $sql, string $label): bool
{
    if ($connect->query($sql)) {
        echo "✓ $label\n";
        return true;
    }

    echo "✗ $label: " . $connect->error . "\n";
    error_log('DB repair failed for ' . $label . ': ' . $connect->error);
    return false;
}

function ensureBranches(mysqli $connect): void
{
    if (!tableExists($connect, 'branches')) {
        runSql($connect,
            "CREATE TABLE `branches` (
                `id` int NOT NULL AUTO_INCREMENT,
                `branch_code` varchar(20) NOT NULL,
                `branch_name` varchar(100) NOT NULL,
                `address` text DEFAULT NULL,
                `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `branch_code` (`branch_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            'Created branches table'
        );
    }

    foreach (['branch_code', 'branch_name', 'address', 'status'] as $column) {
        if (!columnExists($connect, 'branches', $column)) {
            $sql = match ($column) {
                'branch_code' => "ALTER TABLE `branches` ADD COLUMN `branch_code` varchar(20) NOT NULL DEFAULT 'MAIN' AFTER `id`",
                'branch_name' => "ALTER TABLE `branches` ADD COLUMN `branch_name` varchar(100) NOT NULL DEFAULT 'Main Branch' AFTER `branch_code`",
                'address' => "ALTER TABLE `branches` ADD COLUMN `address` text DEFAULT NULL AFTER `branch_name`",
                'status' => "ALTER TABLE `branches` ADD COLUMN `status` enum('active','inactive') NOT NULL DEFAULT 'active' AFTER `address`",
                default => ''
            };

            if ($sql !== '') {
                runSql($connect, $sql, "Added branches.$column");
            }
        }
    }

    $count = $connect->query('SELECT COUNT(*) AS total FROM branches')->fetch_assoc()['total'] ?? 0;
    if ((int) $count === 0) {
        $connect->query("INSERT INTO `branches` (`id`, `branch_code`, `branch_name`, `address`, `status`) VALUES
            (1, 'MAIN', 'Baliuag Branch', 'Baliuag, Bulacan', 'active'),
            (2, 'BUSTOS', 'Bustos Branch', 'Bustos, Bulacan', 'active')
            ON DUPLICATE KEY UPDATE `branch_code` = VALUES(`branch_code`), `branch_name` = VALUES(`branch_name`), `address` = VALUES(`address`), `status` = VALUES(`status`) ");
        echo "✓ Seeded default branches\n";
    }
}

function ensureEmployees(mysqli $connect): void
{
    if (!tableExists($connect, 'employees')) {
        runSql($connect,
            "CREATE TABLE `employees` (
                `id` int NOT NULL AUTO_INCREMENT,
                `firstname` varchar(100) DEFAULT NULL,
                `lastname` varchar(100) DEFAULT NULL,
                `employee_name` varchar(255) DEFAULT NULL,
                `email` varchar(255) NOT NULL,
                `password` varchar(255) NOT NULL,
                `pin` varchar(255) DEFAULT NULL,
                `role` enum('cashier','admin') NOT NULL DEFAULT 'cashier',
                `is_active` tinyint NOT NULL DEFAULT 1,
                `avatar` varchar(255) DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `branch_id` int DEFAULT NULL,
                `current_device_id` int DEFAULT NULL,
                `last_login_device_id` int DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `email` (`email`),
                KEY `idx_employees_branch_role` (`branch_id`, `role`, `is_active`),
                KEY `idx_employees_email_branch` (`email`, `branch_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            'Created employees table'
        );
    }

    foreach (['firstname', 'lastname', 'employee_name', 'email', 'password', 'pin', 'role', 'is_active', 'branch_id', 'current_device_id', 'last_login_device_id'] as $column) {
        if (!columnExists($connect, 'employees', $column)) {
            $sql = match ($column) {
                'firstname' => "ALTER TABLE `employees` ADD COLUMN `firstname` varchar(100) DEFAULT NULL AFTER `id`",
                'lastname' => "ALTER TABLE `employees` ADD COLUMN `lastname` varchar(100) DEFAULT NULL AFTER `firstname`",
                'employee_name' => "ALTER TABLE `employees` ADD COLUMN `employee_name` varchar(255) DEFAULT NULL AFTER `lastname`",
                'email' => "ALTER TABLE `employees` ADD COLUMN `email` varchar(255) NOT NULL AFTER `employee_name`",
                'password' => "ALTER TABLE `employees` ADD COLUMN `password` varchar(255) NOT NULL AFTER `email`",
                'pin' => "ALTER TABLE `employees` ADD COLUMN `pin` varchar(255) DEFAULT NULL AFTER `password`",
                'role' => "ALTER TABLE `employees` ADD COLUMN `role` enum('cashier','admin') NOT NULL DEFAULT 'cashier' AFTER `pin`",
                'is_active' => "ALTER TABLE `employees` ADD COLUMN `is_active` tinyint NOT NULL DEFAULT 1 AFTER `role`",
                'branch_id' => "ALTER TABLE `employees` ADD COLUMN `branch_id` int DEFAULT NULL AFTER `updated_at`",
                'current_device_id' => "ALTER TABLE `employees` ADD COLUMN `current_device_id` int DEFAULT NULL AFTER `branch_id`",
                'last_login_device_id' => "ALTER TABLE `employees` ADD COLUMN `last_login_device_id` int DEFAULT NULL AFTER `current_device_id`",
                default => ''
            };

            if ($sql !== '') {
                runSql($connect, $sql, "Added employees.$column");
            }
        }
    }

    if (!columnExists($connect, 'employees', 'employee_name')) {
        runSql($connect, "ALTER TABLE `employees` ADD COLUMN `employee_name` varchar(255) DEFAULT NULL AFTER `lastname`", 'Added employees.employee_name fallback column');
    }

    $connect->query("UPDATE `employees` SET `employee_name` = CONCAT(COALESCE(`firstname`, ''), ' ', COALESCE(`lastname`, '')) WHERE `employee_name` IS NULL OR TRIM(`employee_name`) = ''");
    $connect->query("ALTER TABLE `employees` MODIFY COLUMN `pin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL");

    $count = $connect->query('SELECT COUNT(*) AS total FROM employees')->fetch_assoc()['total'] ?? 0;
    if ((int) $count === 0) {
        $connect->query("INSERT INTO `employees` (`id`, `firstname`, `lastname`, `email`, `password`, `pin`, `role`, `is_active`, `branch_id`) VALUES
            (1, 'Baliuag', 'Cashier', 'testboycold@gmail.com', '\$2y\$10\$xFOrjRPdx5El1DICxr3bY.8y73sT65hfdUfo1yrOYZk5c5pldBUmS', '\$2y\$10\$6fnJnwK8YYUXs3OJxZG.1..42aDzpEa3xaMI4aP0g8ONzMOuS69.2', 'cashier', 1, 1),
            (2, 'Bustos', 'Cashier', 'gitboycold@gmail.com', '\$2y\$10\$OZntlXH0B61YonQRsg1.vufRX/XE8v4lVCqy6NqLTwRwLmoXMCPFi', '\$2y\$10\$EJ82oOUh1A3iDf3tqdbavu4ujCMGeG/dLkrBlNL6BLCOHb8Ps1Y8i', 'cashier', 1, 2)
            ON DUPLICATE KEY UPDATE `firstname` = VALUES(`firstname`), `lastname` = VALUES(`lastname`), `email` = VALUES(`email`), `password` = VALUES(`password`), `pin` = VALUES(`pin`), `role` = VALUES(`role`), `is_active` = VALUES(`is_active`), `branch_id` = VALUES(`branch_id`), `updated_at` = CURRENT_TIMESTAMP");
        echo "✓ Seeded default POS employees\n";
    }

    if (!indexExists($connect, 'employees', 'idx_employees_branch_role')) {
        runSql($connect, 'ALTER TABLE `employees` ADD INDEX `idx_employees_branch_role` (`branch_id`, `role`, `is_active`)', 'Added idx_employees_branch_role');
    }

    if (!indexExists($connect, 'employees', 'idx_employees_email_branch')) {
        runSql($connect, 'ALTER TABLE `employees` ADD INDEX `idx_employees_email_branch` (`email`, `branch_id`)', 'Added idx_employees_email_branch');
    }
}

function ensurePosTables(mysqli $connect): void
{
    $tables = [
        'pos_devices' => "CREATE TABLE IF NOT EXISTS `pos_devices` (
            `id` int NOT NULL AUTO_INCREMENT,
            `device_code` varchar(50) NOT NULL,
            `device_name` varchar(100) NOT NULL,
            `branch_id` int NOT NULL,
            `location` varchar(255) DEFAULT NULL,
            `device_status` enum('active','inactive','pending') NOT NULL DEFAULT 'pending',
            `current_employee_id` int DEFAULT NULL,
            `session_id` varchar(255) DEFAULT NULL,
            `last_activity` timestamp NULL DEFAULT NULL,
            `is_locked` tinyint NOT NULL DEFAULT 0,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_branch_id` (`branch_id`),
            KEY `idx_current_employee` (`current_employee_id`),
            KEY `idx_session_id` (`session_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        'login_logs' => "CREATE TABLE IF NOT EXISTS `login_logs` (
            `id` int NOT NULL AUTO_INCREMENT,
            `employee_id` int DEFAULT NULL,
            `branch_id` int DEFAULT NULL,
            `device_id` int DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `browser` varchar(255) DEFAULT NULL,
            `operating_system` varchar(100) DEFAULT NULL,
            `login_status` enum('success','failed','inactive','branch_mismatch','device_not_registered','missing_branch','invalid_credentials') NOT NULL DEFAULT 'failed',
            `login_datetime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_employee_id` (`employee_id`),
            KEY `idx_branch_id` (`branch_id`),
            KEY `idx_device_id` (`device_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        'otp' => "CREATE TABLE IF NOT EXISTS `otp` (
            `id` int NOT NULL AUTO_INCREMENT,
            `firstname` varchar(100) DEFAULT NULL,
            `lastname` varchar(100) DEFAULT NULL,
            `email` varchar(255) NOT NULL,
            `password` varchar(255) DEFAULT NULL,
            `otp` varchar(6) NOT NULL,
            `type` enum('register','reset') NOT NULL DEFAULT 'register',
            `status` enum('pending','verified','expired') NOT NULL DEFAULT 'pending',
            `attempts` int NOT NULL DEFAULT 0,
            `otp_sent` datetime DEFAULT CURRENT_TIMESTAMP,
            `expires_at` datetime DEFAULT NULL,
            `ip` varchar(45) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_otp_email` (`email`),
            KEY `idx_otp_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    ];

    foreach ($tables as $table => $sql) {
        if (!tableExists($connect, $table)) {
            runSql($connect, $sql, "Created $table table");
        }
    }

    foreach (['current_device_id', 'last_login_device_id'] as $col) {
        if (!columnExists($connect, 'employees', $col)) {
            $sql = $col === 'current_device_id'
                ? "ALTER TABLE `employees` ADD COLUMN `current_device_id` int DEFAULT NULL AFTER `branch_id`"
                : "ALTER TABLE `employees` ADD COLUMN `last_login_device_id` int DEFAULT NULL AFTER `current_device_id`";
            runSql($connect, $sql, "Added employees.$col");
        }
    }
}

function ensureForeignKeys(mysqli $connect): void
{
    if (tableExists($connect, 'pos_devices') && !columnExists($connect, 'pos_devices', 'branch_id')) {
        runSql($connect, 'ALTER TABLE `pos_devices` ADD COLUMN `branch_id` int NOT NULL DEFAULT 1 AFTER `device_name`', 'Added pos_devices.branch_id');
    }

    if (tableExists($connect, 'pos_devices') && !indexExists($connect, 'pos_devices', 'idx_branch_id')) {
        runSql($connect, 'ALTER TABLE `pos_devices` ADD INDEX `idx_branch_id` (`branch_id`)', 'Added idx_branch_id on pos_devices');
    }

    if (tableExists($connect, 'login_logs') && !indexExists($connect, 'login_logs', 'idx_employee_id')) {
        runSql($connect, 'ALTER TABLE `login_logs` ADD INDEX `idx_employee_id` (`employee_id`)', 'Added idx_employee_id on login_logs');
    }

    if (tableExists($connect, 'employees') && !indexExists($connect, 'employees', 'idx_employees_branch_role')) {
        runSql($connect, 'ALTER TABLE `employees` ADD INDEX `idx_employees_branch_role` (`branch_id`, `role`, `is_active`)', 'Added idx_employees_branch_role');
    }
}

function main(): void
{
    global $connect;

    if (!isset($connect) || !($connect instanceof mysqli)) {
        die('Database connection is not available.');
    }

    echo "Running BoyCold database repair...\n";
    ensureBranches($connect);
    ensureEmployees($connect);
    ensurePosTables($connect);
    ensureForeignKeys($connect);

    echo "\nDatabase repair complete.\n";
    echo "Run this file again safely; it is idempotent.\n";
}

main();
