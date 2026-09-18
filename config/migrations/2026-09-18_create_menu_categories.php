<?php

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../menu_catalog_service.php';

boycold_ensure_menu_category_schema($connect);

echo "Menu category table is ready.\n";
