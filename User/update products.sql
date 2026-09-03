-- Update existing products with new prices and add new products from the menu
-- Run this script to update the products table

-- Update existing products with new prices
UPDATE products SET price = 89.00 WHERE product_name = 'Americano';
UPDATE products SET price = 109.00 WHERE product_name = 'Cafe Latte';
UPDATE products SET price = 129.00 WHERE product_name = 'Spanish Latte';
UPDATE products SET price = 149.00 WHERE product_name = 'Sea Salt Latte';
UPDATE products SET price = 149.00 WHERE product_name = 'French Vanilla';
UPDATE products SET price = 129.00 WHERE product_name = 'White Mocha';
UPDATE products SET price = 159.00 WHERE product_name = 'Caramel Macchiato';
UPDATE products SET price = 159.00 WHERE product_name = 'Salted Caramel';
UPDATE products SET price = 169.00 WHERE product_name = 'Einspanner Latte';
UPDATE products SET price = 179.00 WHERE product_name = 'Cheesecake Latte';
UPDATE products SET price = 199.00 WHERE product_name = 'Dark Mocha';
UPDATE products SET price = 199.00 WHERE product_name = 'Biscoff Creamy Latte';
UPDATE products SET price = 89.00 WHERE product_name = 'Strawberry Milk';
UPDATE products SET price = 89.00 WHERE product_name = 'Blueberry Milk';
UPDATE products SET price = 99.00 WHERE product_name = 'Milky Oreo';
UPDATE products SET price = 129.00 WHERE product_name = 'Choco Berry';
UPDATE products SET price = 209.00 WHERE product_name = 'Choco Banana Pudding';
UPDATE products SET price = 139.00 WHERE product_name = 'Choco Vanilla Cookie';
UPDATE products SET price = 155.00 WHERE product_name = 'Strawberry Matcha';
UPDATE products SET price = 225.00 WHERE product_name = 'Matcha banana Pudding';
UPDATE products SET price = 205.00 WHERE product_name = 'Biscoff Matcha';
UPDATE products SET price = 145.00 WHERE product_name = 'Mango matcha';
UPDATE products SET price = 95.00 WHERE product_name = 'Seasalt Matcha';
UPDATE products SET price = 99.00 WHERE product_name = 'Matcha Freddo';
UPDATE products SET price = 85.00 WHERE product_name = 'Matcha Latte';
UPDATE products SET price = 145.00 WHERE product_name = 'Ube matcha';
UPDATE products SET price = 155.00 WHERE product_name = 'Cheesecake Matcha';
UPDATE products SET price = 85.00 WHERE product_name = 'Strawberry shake';
UPDATE products SET price = 99.00 WHERE product_name = 'Berry mango';
UPDATE products SET price = 109.00 WHERE product_name = 'Tropical Matcha Yogurt';
UPDATE products SET price = 199.00 WHERE product_name = 'Ube yogurt';
UPDATE products SET price = 85.00 WHERE product_name = 'Blueberry shake';
UPDATE products SET price = 85.00 WHERE product_name = 'Mango graham';
UPDATE products SET price = 129.00 WHERE product_name = 'hershey delight';
UPDATE products SET price = 149.00 WHERE product_name = 'Ube Frappe';
UPDATE products SET price = 139.00 WHERE product_name = 'Oreo Frappe';
UPDATE products SET price = 129.00 WHERE product_name = 'Matcha Frappe';
UPDATE products SET price = 149.00 WHERE product_name = 'Java Chips';
UPDATE products SET price = 159.00 WHERE product_name = 'Cheesecake Frappe';
UPDATE products SET price = 169.00 WHERE product_name = 'Biscoff frappe';
UPDATE products SET price = 129.00 WHERE product_name = 'Cheezy Fries';
UPDATE products SET price = 219.00 WHERE product_name = 'Fries and Chicken Poppers';
UPDATE products SET price = 129.00 WHERE product_name = 'Onion rings';
UPDATE products SET price = 179.00 WHERE product_name = 'Beef Natchos';
UPDATE products SET price = 239.00 WHERE product_name = 'Chicken Alfredo';
UPDATE products SET price = 239.00 WHERE product_name = 'Chicken Pesto';
UPDATE products SET price = 239.00 WHERE product_name = 'Aglio olio sardines';
UPDATE products SET price = 249.00 WHERE product_name = 'Carbonara';
UPDATE products SET price = 119.00 WHERE product_name = 'Lolly Biscoff waffle';
UPDATE products SET price = 89.00 WHERE product_name = 'Lolly Chocolate waffle';
UPDATE products SET price = 89.00 WHERE product_name = 'Lolly Matcha waffle';
UPDATE products SET price = 89.00 WHERE product_name = 'Lolly Strawberry waffle';
UPDATE products SET price = 89.00 WHERE product_name = 'Lolly Oreo waffle';
UPDATE products SET price = 89.00 WHERE product_name = 'Lolly tiramisu waffle';
UPDATE products SET price = 179.00 WHERE product_name = 'Chicken Quesadilla';
UPDATE products SET price = 179.00 WHERE product_name = 'Beef Quesadilla';

-- Update categories to match new structure
UPDATE products SET category = 'coffee' WHERE category = 'special-coffee';
UPDATE products SET category = 'smoothie' WHERE category = 'fruit-shake';
UPDATE products SET category = 'light-snack' WHERE category = 'bites';
UPDATE products SET category = 'waffles' WHERE category = 'waffle';

-- Keep image paths relative to the application's shared picture directory.
UPDATE products SET image = CONCAT('/picture/', SUBSTRING_INDEX(image, '/', -1))
WHERE image IS NOT NULL AND image <> '';

-- Add new products
INSERT INTO products (product_name, description, price, image, category, is_available) VALUES
('Mont Blanc', 'Mont Blanc coffee blend', 179.00, '/picture/Mont Blanc.png', 'coffee', 1),
('Horchata', 'Coffee Horchata', 189.00, '/picture/Coffee Horchata.png', 'coffee', 1),
('Ocean Mist', 'Ocean Mist coffee', 189.00, '/picture/Ocean mist.png', 'coffee', 1),
('Creme Brulee', 'Creme Brulee coffee', 199.00, '/picture/ChatGPT Image Aug 29, 2026, 12_20_12 AM.png', 'coffee', 1),
('Black Forrest', 'Black Forrest frappe', 169.00, '/picture/blackforest.png', 'frappe-series', 1),
('Honey Gochujang Katsu', 'Honey Gochujang Katsu rice meal', 219.00, '/picture/Honey Gochujang Katsu 1.png', 'rice-meal', 1),
('Dak Galbi', 'Dak Galbi rice meal', 199.00, '/picture/Dak galbi 1.png', 'rice-meal', 1),
('Salted Egg Fish Fillet', 'Salted Egg Fish Fillet rice meal', 229.00, '/picture/Salted egg Fish fillet.png', 'rice-meal', 1),
('Fries & Chicken Tenders', 'Fries and Chicken tenders', 219.00, '/picture/Fries and Chicken tenders.png', 'light-snack', 1),
('Nachos', 'Nachos', 179.00, '/picture/Nachos.png', 'light-snack', 1),
('Aglio Olio', 'Aglio Olio pasta', 239.00, '/picture/Aglio olio sardines 1.png', 'pasta', 1);
