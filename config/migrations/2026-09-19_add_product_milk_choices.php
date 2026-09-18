<?php

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../menu_catalog_service.php';

// Adds typed product modifiers and the explicit milk-choice configuration
// flag. Existing product_addons rows are retained as ordinary add-ons.
boycold_ensure_product_addons_schema($connect);

echo "Product add-ons and milk choices are ready.\n";
