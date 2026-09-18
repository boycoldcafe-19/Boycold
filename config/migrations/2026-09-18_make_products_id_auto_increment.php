<?php

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../menu_catalog_service.php';

boycold_ensure_product_id_auto_increment($connect);

echo "Product IDs are ready for new menu items.\n";
