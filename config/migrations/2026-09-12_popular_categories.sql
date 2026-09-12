-- Add database-backed Popular Categories without changing the existing menu categories.
-- Safe to run repeatedly.

SET @popular_category_exists = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'products'
      AND column_name = 'popular_category'
);
SET @popular_category_sql = IF(
    @popular_category_exists = 0,
    'ALTER TABLE products ADD COLUMN popular_category VARCHAR(40) NULL AFTER category',
    'SELECT 1'
);
PREPARE popular_category_statement FROM @popular_category_sql;
EXECUTE popular_category_statement;
DEALLOCATE PREPARE popular_category_statement;

INSERT INTO products
    (product_name, description, price, image, category, is_available, popular_category)
SELECT 'Biscoff Matcha', 'Biscoff matcha fusion', 205.00,
       '/picture/Biscoff Matcha 1.png', 'matcha-fusion', 1, 'matcha-fusion'
WHERE NOT EXISTS (
    SELECT 1 FROM products WHERE LOWER(TRIM(product_name)) = 'biscoff matcha'
);

INSERT INTO products
    (product_name, description, price, image, category, is_available, popular_category)
SELECT 'Black Forrest', 'Black Forrest frappe', 169.00,
       '/picture/blackforest.png', 'frappe-series', 1, 'frappe-series'
WHERE NOT EXISTS (
    SELECT 1 FROM products WHERE LOWER(TRIM(product_name)) = 'black forrest'
);

UPDATE products
SET popular_category = CASE LOWER(TRIM(product_name))
    WHEN 'spanish latte' THEN 'coffee'
    WHEN 'sea salt latte' THEN 'coffee'
    WHEN 'caramel macchiato' THEN 'coffee'
    WHEN 'salted caramel' THEN 'coffee'
    WHEN 'horchata' THEN 'coffee'
    WHEN 'ocean mist' THEN 'coffee'
    WHEN 'creme brulee' THEN 'coffee'
    WHEN 'biscoff creamy latte' THEN 'coffee'
    WHEN 'choco vanilla cookie' THEN 'non-coffee'
    WHEN 'choco banana pudding' THEN 'non-coffee'
    WHEN 'sea salt matcha' THEN 'matcha-fusion'
    WHEN 'seasalt matcha' THEN 'matcha-fusion'
    WHEN 'matcha freddo' THEN 'matcha-fusion'
    WHEN 'cheesecake matcha' THEN 'matcha-fusion'
    WHEN 'biscoff matcha' THEN 'matcha-fusion'
    WHEN 'matcha banana pudding' THEN 'matcha-fusion'
    WHEN 'hershey delight' THEN 'frappe-series'
    WHEN 'java chips' THEN 'frappe-series'
    WHEN 'black forrest' THEN 'frappe-series'
    ELSE popular_category
END
WHERE LOWER(TRIM(product_name)) IN (
    'spanish latte', 'sea salt latte', 'caramel macchiato', 'salted caramel',
    'horchata', 'ocean mist', 'creme brulee', 'biscoff creamy latte',
    'choco vanilla cookie', 'choco banana pudding', 'sea salt matcha',
    'seasalt matcha', 'matcha freddo', 'cheesecake matcha', 'biscoff matcha',
    'matcha banana pudding', 'hershey delight', 'java chips', 'black forrest'
);
