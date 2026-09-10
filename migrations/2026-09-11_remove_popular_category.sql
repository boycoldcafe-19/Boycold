-- Remove the legacy Popular category token from product categories.
-- Safe to run on existing databases; normal product categories remain.

UPDATE products
SET category = TRIM(REPLACE(REPLACE(LOWER(category), ' popular', ''), 'popular', ''))
WHERE LOWER(category) LIKE '%popular%';