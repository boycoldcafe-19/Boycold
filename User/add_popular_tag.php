<?php
require_once __DIR__ . '/../config/db_config.php';

// Clean up legacy tags without adding them back.
$stmt = $connect->prepare(
    "UPDATE products
     SET category = TRIM(REPLACE(REPLACE(LOWER(category), ' popular', ''), 'popular', ''))
     WHERE LOWER(category) LIKE '%popular%'"
);
$stmt->execute();
echo "Removed legacy 'popular' category tags: " . $stmt->affected_rows . " product(s).\n";
$stmt->close();
?>
