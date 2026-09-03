<?php

// Hostinger/cPanel cron: run every minute with the Asia/Manila timezone handled here.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/shift_manager.php';

date_default_timezone_set(POS_BUSINESS_TIMEZONE);

$branches = $connect->query('SELECT id FROM branches WHERE status = \'active\' ORDER BY id');
if (!$branches) {
    throw new RuntimeException($connect->error);
}

while ($branch = $branches->fetch_assoc()) {
    $branchId = (int) $branch['id'];
    $shift = pos_reconcile_branch_shift($connect, $branchId);
    $shiftDate = $shift['shift_date'] ?? pos_sales_date();
    echo sprintf("Branch %d: sales day %s, shift %s\n", $branchId, $shiftDate, $shift['id'] ?? 'none');
}
