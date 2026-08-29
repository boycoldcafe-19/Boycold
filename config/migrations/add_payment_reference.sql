-- Payment columns for QRPh (PayMongo) and COD.
-- Does not duplicate payment_method. Adds payment_reference and expands payment_status.

ALTER TABLE orders
  MODIFY COLUMN payment_method ENUM('cod','qrph') COLLATE utf8mb4_unicode_ci DEFAULT 'cod';

ALTER TABLE orders
  MODIFY COLUMN payment_status ENUM('unpaid','pending','paid','failed','expired','cancelled')
  COLLATE utf8mb4_unicode_ci DEFAULT 'unpaid';

-- Ignore error if column already exists when running manually.
ALTER TABLE orders
  ADD COLUMN payment_reference VARCHAR(80) DEFAULT NULL;

ALTER TABLE orders
  ADD INDEX idx_payment_reference (payment_reference);

CREATE TABLE IF NOT EXISTS paymongo_webhook_events (
  event_id VARCHAR(80) NOT NULL,
  event_type VARCHAR(80) NOT NULL,
  order_id INT DEFAULT NULL,
  processed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
