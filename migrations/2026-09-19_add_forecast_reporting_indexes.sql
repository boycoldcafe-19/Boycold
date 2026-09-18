-- Indexes used by Admin Data Analytics and Forecasting top-item reports.
ALTER TABLE orders
    ADD KEY idx_orders_forecast_branch_date (branch_id, created_at, status, payment_status);

ALTER TABLE order_items
    ADD KEY idx_order_items_order_product (order_id, product_name);