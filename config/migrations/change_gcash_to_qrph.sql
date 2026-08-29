-- Migration: Change payment_method enum from 'gcash' to 'qrph'
-- This changes the payment method label from GCash to QR Ph

-- Modify the orders table payment_method enum
ALTER TABLE orders 
MODIFY COLUMN payment_method ENUM('cod', 'qrph') COLLATE utf8mb4_unicode_ci DEFAULT 'cod';

-- Update existing records with 'gcash' to 'qrph'
UPDATE orders SET payment_method = 'qrph' WHERE payment_method = 'gcash';
