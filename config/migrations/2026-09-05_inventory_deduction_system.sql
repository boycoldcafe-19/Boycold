-- Automatic inventory deduction support.
-- Safe to run repeatedly after checking for existing columns/indexes, or use
-- config/inventory_service.php's boycold_ensure_inventory_schema() helper.

CREATE TABLE IF NOT EXISTS product_ingredients (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_name VARCHAR(150) NOT NULL,
    ingredient_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_product_name (product_name),
    KEY idx_ingredient_id (ingredient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ingredient_stock_movements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ingredient_id INT UNSIGNED NOT NULL,
    movement_type ENUM('stock_in','deduction','adjustment') NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    resulting_stock DECIMAL(10,3) NOT NULL,
    order_id INT NULL DEFAULT NULL,
    source VARCHAR(30) NULL DEFAULT NULL,
    product_name VARCHAR(150) NULL DEFAULT NULL,
    reference VARCHAR(120) NULL DEFAULT NULL,
    created_by INT NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ingredient_stock_movements_ingredient (ingredient_id),
    KEY idx_stock_movement_ingredient_id (ingredient_id),
    KEY idx_stock_movements_order (order_id),
    KEY idx_stock_movements_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- For existing databases, add these columns if they do not already exist:
-- ALTER TABLE orders ADD COLUMN inventory_deducted_at DATETIME NULL DEFAULT NULL;
-- ALTER TABLE orders ADD COLUMN inventory_deduction_source VARCHAR(30) NULL DEFAULT NULL;
-- ALTER TABLE orders ADD COLUMN inventory_deduction_error VARCHAR(255) NULL DEFAULT NULL;
-- ALTER TABLE ingredient_stock_movements ADD COLUMN order_id INT NULL DEFAULT NULL;
-- ALTER TABLE ingredient_stock_movements ADD COLUMN source VARCHAR(30) NULL DEFAULT NULL;
-- ALTER TABLE ingredient_stock_movements ADD COLUMN product_name VARCHAR(150) NULL DEFAULT NULL;
-- ALTER TABLE ingredient_stock_movements ADD COLUMN reference VARCHAR(120) NULL DEFAULT NULL;
