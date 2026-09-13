-- Existing installations may have login_logs.id without AUTO_INCREMENT.
-- Run this once against the project database.
SET @login_logs_has_primary = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'login_logs'
    AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);

SET @login_logs_id_repair = IF(
  @login_logs_has_primary > 0,
  'ALTER TABLE login_logs MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT',
  'ALTER TABLE login_logs MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id)'
);

PREPARE login_logs_id_repair_statement FROM @login_logs_id_repair;
EXECUTE login_logs_id_repair_statement;
DEALLOCATE PREPARE login_logs_id_repair_statement;