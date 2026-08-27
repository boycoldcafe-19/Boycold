<?php
require_once __DIR__ . '/config/db_config.php';

echo "=== Current Branch Data ===\n\n";
$result = $connect->query('SELECT * FROM branches ORDER BY id');
while($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Code: " . $row['branch_code'] . "\n";
    echo "Name: " . $row['branch_name'] . "\n";
    echo "Address: " . $row['address'] . "\n";
    echo "Status: " . $row['status'] . "\n";
    echo "Created: " . $row['created_at'] . "\n";
    echo "Updated: " . $row['updated_at'] . "\n";
    echo "---\n";
}
