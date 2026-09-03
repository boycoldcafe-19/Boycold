-- BoyCold loyalty card database update
-- Run this in phpMyAdmin on the active boycold database.
-- Existing customer/order rows are not deleted.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS loyalty_token VARCHAR(64) NULL,
    ADD UNIQUE KEY IF NOT EXISTS uq_users_card_no (card_no),
    ADD UNIQUE KEY IF NOT EXISTS uq_users_loyalty_token (loyalty_token);

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS user_id INT NULL,
    ADD COLUMN IF NOT EXISTS loyalty_awarded TINYINT(1) NOT NULL DEFAULT 0,
    ADD KEY IF NOT EXISTS idx_orders_user_id (user_id);

-- Generate card numbers for users that do not have one.
UPDATE users
SET card_no = CONCAT('BY-', YEAR(CURRENT_DATE), LPAD(id, 3, '0'))
WHERE card_no IS NULL OR card_no = '';

-- Generate unique QR tokens for users that do not have one.
UPDATE users
SET loyalty_token = LOWER(REPLACE(UUID(), '-', ''))
WHERE loyalty_token IS NULL OR loyalty_token = '';
