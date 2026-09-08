-- Fix the schema expected by the BoyCold POS + customer auth system.
-- Run this in phpMyAdmin or via MySQL CLI against the project database.
-- This script adds any missing auth/POS columns and creates the required tables
-- when the database was partially imported or manually edited.

-- Ensure branches contain the fields used by the unified login and POS routing.
ALTER TABLE `branches`
  ADD COLUMN IF NOT EXISTS `branch_code` VARCHAR(20) NOT NULL DEFAULT 'MAIN' AFTER `id`,
  ADD COLUMN IF NOT EXISTS `branch_name` VARCHAR(100) NOT NULL DEFAULT 'Main Branch' AFTER `branch_code`;

ALTER TABLE `branches`
  ADD COLUMN IF NOT EXISTS `status` ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER `address`;

-- Ensure employees have the fields used by POS auth and device locking.
ALTER TABLE `employees`
  ADD COLUMN IF NOT EXISTS `employee_name` VARCHAR(255) GENERATED ALWAYS AS (CONCAT(COALESCE(firstname, ''), ' ', COALESCE(lastname, ''))) STORED AFTER `lastname`,
  ADD COLUMN IF NOT EXISTS `pin` VARCHAR(255) NULL DEFAULT NULL AFTER `password`,
  ADD COLUMN IF NOT EXISTS `branch_id` INT NULL DEFAULT NULL AFTER `updated_at`,
  ADD COLUMN IF NOT EXISTS `current_device_id` INT NULL DEFAULT NULL AFTER `branch_id`,
  ADD COLUMN IF NOT EXISTS `last_login_device_id` INT NULL DEFAULT NULL AFTER `current_device_id`;

-- Some older DB dumps keep `employee_name` as a real column instead of generated.
-- If it already exists as a normal column, this makes sure it stays usable.
UPDATE `employees`
SET `employee_name` = CONCAT(COALESCE(firstname, ''), ' ', COALESCE(lastname, ''))
WHERE `employee_name` IS NULL OR TRIM(`employee_name`) = '';

-- Make sure hashed PINs are stored with enough size for password_hash() output.
ALTER TABLE `employees`
  MODIFY COLUMN `pin` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

-- The POS device tracking tables used by the dashboard/device lock logic.
CREATE TABLE IF NOT EXISTS `pos_devices` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `device_code` VARCHAR(50) NOT NULL,
  `device_name` VARCHAR(100) NOT NULL,
  `branch_id` INT NOT NULL,
  `location` VARCHAR(255) NULL DEFAULT NULL,
  `device_status` ENUM('active','inactive','pending') NOT NULL DEFAULT 'pending',
  `current_employee_id` INT NULL DEFAULT NULL,
  `session_id` VARCHAR(255) NULL DEFAULT NULL,
  `last_activity` TIMESTAMP NULL DEFAULT NULL,
  `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_current_employee` (`current_employee_id`),
  KEY `idx_session_id` (`session_id`),
  CONSTRAINT `fk_pos_devices_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_id` INT NULL DEFAULT NULL,
  `branch_id` INT NULL DEFAULT NULL,
  `device_id` INT NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `browser` VARCHAR(255) NULL DEFAULT NULL,
  `operating_system` VARCHAR(100) NULL DEFAULT NULL,
  `login_status` ENUM('success','failed','inactive','branch_mismatch','device_not_registered','missing_branch','invalid_credentials') NOT NULL DEFAULT 'failed',
  `login_datetime` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_device_id` (`device_id`),
  CONSTRAINT `fk_login_logs_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_login_logs_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_login_logs_device` FOREIGN KEY (`device_id`) REFERENCES `pos_devices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The OTP verification flow used in registration/reset requires an `otp` table.
CREATE TABLE IF NOT EXISTS `otp` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `firstname` VARCHAR(100) NULL DEFAULT NULL,
  `lastname` VARCHAR(100) NULL DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NULL DEFAULT NULL,
  `otp` VARCHAR(6) NOT NULL,
  `type` ENUM('register','reset') NOT NULL DEFAULT 'register',
  `status` ENUM('pending','verified','expired') NOT NULL DEFAULT 'pending',
  `attempts` INT NOT NULL DEFAULT 0,
  `otp_sent` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NULL DEFAULT NULL,
  `ip` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_otp_email` (`email`),
  KEY `idx_otp_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Keep the default POS employee data aligned with the login flow.
INSERT INTO `branches` (`id`, `branch_code`, `branch_name`, `address`, `status`)
VALUES
  (1, 'MAIN', 'Baliuag Branch', 'Baliuag, Bulacan', 'active'),
  (2, 'BUSTOS', 'Bustos Branch', 'Bustos, Bulacan', 'active')
ON DUPLICATE KEY UPDATE
  `branch_code` = VALUES(`branch_code`),
  `branch_name` = VALUES(`branch_name`),
  `address` = VALUES(`address`),
  `status` = VALUES(`status`);

INSERT INTO `employees`
  (`id`, `firstname`, `lastname`, `email`, `password`, `pin`, `role`, `is_active`, `branch_id`)
VALUES
  (1, 'Baliuag', 'Cashier', 'testboycold@gmail.com', '$2y$10$xFOrjRPdx5El1DICxr3bY.8y73sT65hfdUfo1yrOYZk5c5pldBUmS', '$2y$10$6fnJnwK8YYUXs3OJxZG.1..42aDzpEa3xaMI4aP0g8ONzMOuS69.2', 'cashier', 1, 1),
  (2, 'Bustos', 'Cashier', 'gitboycold@gmail.com', '$2y$10$OZntlXH0B61YonQRsg1.vufRX/XE8v4lVCqy6NqLTwRwLmoXMCPFi', '$2y$10$EJ82oOUh1A3iDf3tqdbavu4ujCMGeG/dLkrBlNL6BLCOHb8Ps1Y8i', 'cashier', 1, 2)
ON DUPLICATE KEY UPDATE
  `firstname` = VALUES(`firstname`),
  `lastname` = VALUES(`lastname`),
  `email` = VALUES(`email`),
  `password` = VALUES(`password`),
  `pin` = VALUES(`pin`),
  `role` = VALUES(`role`),
  `is_active` = VALUES(`is_active`),
  `branch_id` = VALUES(`branch_id`),
  `updated_at` = CURRENT_TIMESTAMP;

-- Safety cleanup: ensure the generated employee name matches actual first/last names.
UPDATE `employees`
SET `employee_name` = CONCAT(COALESCE(firstname, ''), ' ', COALESCE(lastname, ''))
WHERE `employee_name` IS NULL OR TRIM(`employee_name`) = '';

-- If the app is using device-based login, make sure `employees.current_device_id`
-- points to a valid `pos_devices.id` when a device is assigned.
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_current_device`
  FOREIGN KEY (`current_device_id`) REFERENCES `pos_devices` (`id`) ON DELETE SET NULL;

-- Add the branch-based auth indexes commonly used by the POS queries.
CREATE INDEX IF NOT EXISTS `idx_employees_branch_role`
  ON `employees` (`branch_id`, `role`, `is_active`);

CREATE INDEX IF NOT EXISTS `idx_employees_email_branch`
  ON `employees` (`email`, `branch_id`);
