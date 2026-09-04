CREATE TABLE IF NOT EXISTS product_ingredients (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_name VARCHAR(150) NOT NULL,
    ingredient_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_product_name (product_name),
    KEY idx_ingredient_id (ingredient_id),
    CONSTRAINT fk_product_ingredients_ingredient
        FOREIGN KEY (ingredient_id) REFERENCES ingredients(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;