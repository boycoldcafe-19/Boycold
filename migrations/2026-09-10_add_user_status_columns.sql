-- Add customer status fields required by login, registration, and admin customer management.
-- Compatible with older MySQL/MariaDB versions that do not support
-- ALTER TABLE ... ADD COLUMN IF NOT EXISTS.

SET @database_name = DATABASE();

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE users ADD COLUMN account_status ENUM(''active'', ''inactive'') NOT NULL DEFAULT ''active'' AFTER is_verified',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @database_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'account_status'
);
PREPARE add_account_status FROM @sql;
EXECUTE add_account_status;
DEALLOCATE PREPARE add_account_status;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE users ADD COLUMN loyalty_card_status ENUM(''active'', ''inactive'', ''completed'') NOT NULL DEFAULT ''active'' AFTER loyalty_stamps',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @database_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'loyalty_card_status'
);
PREPARE add_loyalty_card_status FROM @sql;
EXECUTE add_loyalty_card_status;
DEALLOCATE PREPARE add_loyalty_card_status;