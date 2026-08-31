<?php
require_once 'config/db_config.php';

echo "Checking orders table structure...\n\n";

$result = $connect->query("SHOW COLUMNS FROM orders");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

$connect->close();
