-- Food categories in the POS use quantity and order type only.
-- Normalize older singular category values before using category-based rules.

UPDATE products
SET category = 'waffles'
WHERE LOWER(TRIM(category)) = 'waffle';

UPDATE products
SET category = 'light-snack'
WHERE LOWER(TRIM(category)) IN ('snack', 'snacks');

UPDATE products
SET category = 'rice-meal'
WHERE LOWER(TRIM(category)) IN ('rice meal', 'rice_meals');

UPDATE products
SET category = 'quesadilla'
WHERE LOWER(TRIM(category)) IN ('quesadillas');