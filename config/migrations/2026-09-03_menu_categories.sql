-- Normalize product categories used by User/menu.php.
-- Product categories are independent from order_type on carts and orders.

UPDATE products
SET category = 'light-snack'
WHERE category = 'bites';

UPDATE products
SET category = 'waffles'
WHERE category = 'waffle';

INSERT INTO products
    (product_name, description, price, image, category, is_available)
SELECT 'Honey Gochujang Katsu', 'Honey Gochujang Katsu rice meal', 219.00,
       '/picture/Honey Gochujang Katsu 1.png', 'rice-meal', 1
WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Honey Gochujang Katsu');

INSERT INTO products
    (product_name, description, price, image, category, is_available)
SELECT 'Dak Galbi', 'Dak Galbi rice meal', 199.00,
       '/picture/Dak galbi 1.png', 'rice-meal', 1
WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Dak Galbi');

INSERT INTO products
    (product_name, description, price, image, category, is_available)
SELECT 'Salted Egg Fish Fillet', 'Salted Egg Fish Fillet rice meal', 229.00,
       '/picture/Salted egg Fish fillet.png', 'rice-meal', 1
WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Salted Egg Fish Fillet');

INSERT INTO products
    (product_name, description, price, image, category, is_available)
SELECT 'Fries & Chicken Tenders', 'Fries and Chicken tenders', 219.00,
       '/picture/Fries and Chicken tenders.png', 'light-snack', 1
WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Fries & Chicken Tenders');

INSERT INTO products
    (product_name, description, price, image, category, is_available)
SELECT 'Nachos', 'Nachos', 179.00,
       '/picture/Nachos.png', 'light-snack', 1
WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Nachos');

INSERT INTO products
    (product_name, description, price, image, category, is_available)
SELECT 'Aglio Olio', 'Aglio Olio pasta', 239.00,
       '/picture/Aglio olio sardines 1.png', 'pasta', 1
WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Aglio Olio');

-- Keep the checked-in database dump aligned with the live migration.
UPDATE products
SET category = 'light-snack'
WHERE category = 'bites';
