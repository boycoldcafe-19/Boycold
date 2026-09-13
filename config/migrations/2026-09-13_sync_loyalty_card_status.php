<?php
require_once __DIR__ . '/../db_config.php';

$sql = "UPDATE users
        SET loyalty_card_status = CASE
            WHEN loyalty_stamps >= 10 THEN 'completed'
            WHEN loyalty_card_status = 'inactive' THEN 'inactive'
            ELSE 'active'
        END";

if (!$connect->query($sql)) {
    throw new RuntimeException('Failed to synchronize loyalty card statuses: ' . $connect->error);
}

echo 'Loyalty card statuses synchronized; rows updated: ' . $connect->affected_rows . PHP_EOL;
