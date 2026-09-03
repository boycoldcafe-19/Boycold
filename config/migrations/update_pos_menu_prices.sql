-- Migration: Update POS menu prices to match pos-menu.php
-- Date: 2026-09-03
-- Description: Update product prices to match the prices displayed in pos-menu.php

UPDATE `products` SET `price` = 89.00 WHERE `id` = 1 AND `product_name` = 'Americano';
UPDATE `products` SET `price` = 109.00 WHERE `id` = 2 AND `product_name` = 'Cafe Latte';
UPDATE `products` SET `price` = 129.00 WHERE `id` = 3 AND `product_name` = 'Spanish Latte';
UPDATE `products` SET `price` = 149.00 WHERE `id` = 9 AND `product_name` = 'Sea Salt Latte';
UPDATE `products` SET `price` = 149.00 WHERE `id` = 49 AND `product_name` = 'French Vanilla';
UPDATE `products` SET `price` = 129.00 WHERE `id` = 5 AND `product_name` = 'White Mocha';
