<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../order_void_service.php';

boycold_ensure_order_void_schema($connect);
$backfilled = boycold_backfill_legacy_pos_voids($connect);
echo "POS order void status migration completed. Legacy voids updated: {$backfilled}.\n";
