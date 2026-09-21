<?php
require_once __DIR__ . '/../db_config.php';

$statusColumn = $connect->query("SHOW COLUMNS FROM users LIKE 'loyalty_card_status'");
if ($statusColumn instanceof mysqli_result && $statusColumn->num_rows > 0) {
    $connect->query("UPDATE users SET loyalty_card_status = 'active' WHERE loyalty_card_status = 'completed'");
}

echo "Loyalty card status normalization completed.\n";
