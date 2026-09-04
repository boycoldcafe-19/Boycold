-- Indexes used by live order and payment-status polling.

ALTER TABLE orders
    ADD INDEX idx_orders_user_created (user_id, created_at, id),
    ADD INDEX idx_orders_branch_created (branch_id, created_at, id);
