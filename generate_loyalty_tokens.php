<?php
// Script to generate loyalty tokens for existing users
// Run this after executing the SQL migration

require_once 'config/db_config.php';
require_once 'config/loyalty.php';

echo "Generating loyalty tokens for existing users...\n\n";

// Get all users without loyalty tokens
$stmt = $connect->prepare("SELECT id FROM users WHERE loyalty_token IS NULL OR loyalty_token = ''");
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row['id'];
}
$stmt->close();

if (empty($users)) {
    echo "All users already have loyalty tokens.\n";
    exit;
}

echo "Found " . count($users) . " users without loyalty tokens.\n";

$successCount = 0;
$failCount = 0;

foreach ($users as $userId) {
    $token = ensureUserLoyaltyToken($connect, $userId);
    if ($token !== '') {
        $successCount++;
        echo "✓ User ID $userId: Token generated\n";
    } else {
        $failCount++;
        echo "✗ User ID $userId: Failed to generate token\n";
    }
}

echo "\n=== Summary ===\n";
echo "Success: $successCount\n";
echo "Failed: $failCount\n";
echo "\nToken generation complete!\n";

$connect->close();
