-- Migration: Add loyalty_token to users table
-- Add user_id and loyalty_awarded to orders table
-- Run this migration to enable the improved loyalty QR code system

-- Add loyalty_token column to users table
ALTER TABLE users
ADD COLUMN loyalty_token VARCHAR(64) UNIQUE DEFAULT NULL COMMENT 'Unique secure token for loyalty QR code';

-- Add index for faster lookups
ALTER TABLE users
ADD INDEX idx_loyalty_token (loyalty_token);

-- Add user_id column to orders table (for direct user association)
ALTER TABLE orders
ADD COLUMN user_id INT DEFAULT NULL COMMENT 'Direct reference to users.id',
ADD INDEX idx_user_id (user_id);

-- Add loyalty_awarded column to orders table (to prevent duplicate awards)
ALTER TABLE orders
ADD COLUMN loyalty_awarded TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether loyalty has been awarded for this order';

-- Add foreign key constraint for orders.user_id
ALTER TABLE orders
ADD CONSTRAINT fk_orders_user_id
FOREIGN KEY (user_id) REFERENCES users(id)
ON DELETE SET NULL
ON UPDATE CASCADE;
