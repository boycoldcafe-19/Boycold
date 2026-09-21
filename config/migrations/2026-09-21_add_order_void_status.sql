-- Distinguishes a cashier-confirmed POS void from a customer cancellation.
-- The orders.status column remains cancelled so existing sales reports keep
-- excluding the original voided transaction.
ALTER TABLE `orders`
  ADD COLUMN `voided_at` DATETIME NULL DEFAULT NULL AFTER `payment_status`,
  ADD COLUMN `voided_by` INT NULL DEFAULT NULL AFTER `voided_at`,
  ADD COLUMN `void_replacement_for_order_id` INT NULL DEFAULT NULL AFTER `voided_by`;
