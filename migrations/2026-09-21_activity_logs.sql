-- Persistent audit records for actions that cannot be inferred from existing
-- POS tables (logout, report exports, deleted menu items, and admin changes).
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(32) NOT NULL,
  `action` VARCHAR(80) NOT NULL,
  `summary` VARCHAR(255) NOT NULL,
  `details` VARCHAR(500) NULL DEFAULT NULL,
  `actor_id` INT NULL DEFAULT NULL,
  `actor_type` VARCHAR(32) NOT NULL DEFAULT 'system',
  `branch_id` INT NULL DEFAULT NULL,
  `entity_type` VARCHAR(60) NULL DEFAULT NULL,
  `entity_id` BIGINT NULL DEFAULT NULL,
  `metadata` TEXT NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_created_at` (`created_at`),
  KEY `idx_activity_logs_category_created_at` (`category`, `created_at`),
  KEY `idx_activity_logs_actor_created_at` (`actor_id`, `created_at`),
  KEY `idx_activity_logs_branch_created_at` (`branch_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
