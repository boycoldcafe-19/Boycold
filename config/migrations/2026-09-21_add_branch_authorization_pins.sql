-- Branch-specific PINs for sensitive POS actions (for example, order voids).
-- The first request for Branch 1 / Baliuag seeds 1234; Branch 2 / Bustos
-- seeds 4321. The application stores both defaults and later updates hashed.
CREATE TABLE IF NOT EXISTS `branch_authorization_pins` (
  `branch_id` INT NOT NULL,
  `pin_hash` VARCHAR(255) NOT NULL,
  `failed_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_at` DATETIME NULL DEFAULT NULL,
  `updated_by` INT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`branch_id`),
  KEY `idx_branch_authorization_pins_locked_at` (`locked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
