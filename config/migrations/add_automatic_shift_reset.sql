-- Automatic 2:00 AM Asia/Manila shift lifecycle
-- Run once against boycold_db before enabling the cron job.

ALTER TABLE shift_logs ADD COLUMN shift_date date NULL AFTER employee_id;
ALTER TABLE shift_logs ADD COLUMN close_reason varchar(20) NULL AFTER status;
ALTER TABLE shift_logs ADD COLUMN open_reason varchar(20) NULL AFTER close_reason;

ALTER TABLE shift_logs
  MODIFY COLUMN status enum('open','closed','auto-closed') NOT NULL DEFAULT 'open';

ALTER TABLE shift_logs
  MODIFY COLUMN opening_cash_float decimal(10,3) NOT NULL DEFAULT 0.000,
  MODIFY COLUMN closing_cash_count decimal(10,3) DEFAULT NULL,
  MODIFY COLUMN cash_difference decimal(10,3) DEFAULT 0.000;

UPDATE shift_logs
SET shift_date = CASE
    WHEN TIME(opened_at) < '02:00:00' THEN DATE_SUB(DATE(opened_at), INTERVAL 1 DAY)
    ELSE DATE(opened_at)
END
WHERE shift_date IS NULL;

ALTER TABLE shift_logs
  MODIFY COLUMN shift_date date NOT NULL,
  ADD UNIQUE KEY uq_shift_branch_sales_day (branch_id, shift_date),
  ADD KEY idx_shift_sales_day_status (shift_date, status);

CREATE TABLE IF NOT EXISTS shift_events (
  id int NOT NULL AUTO_INCREMENT,
  shift_id int NOT NULL,
  branch_id int NOT NULL,
  employee_id int DEFAULT NULL,
  event_type enum('manual-open','manual-close','automatic-open','automatic-close') NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_shift_events_shift (shift_id),
  KEY idx_shift_events_branch_date (branch_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE orders ADD COLUMN shift_id int NULL AFTER cashier_id;
ALTER TABLE orders ADD KEY idx_orders_shift_id (shift_id);

-- Existing POS orders are associated with the shift whose time window contains them.
UPDATE orders o
JOIN shift_logs s ON s.branch_id = o.branch_id
    AND o.created_at >= s.opened_at
    AND o.created_at < COALESCE(s.closed_at, '9999-12-31 23:59:59')
SET o.shift_id = s.id
WHERE o.shift_id IS NULL AND o.branch_id IS NOT NULL;
