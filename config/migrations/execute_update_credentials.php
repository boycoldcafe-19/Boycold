<?php
require_once __DIR__ . '/../db_config.php';

// Update Baliuag POS (branch_id=1)
$baliuagEmail = 'testboycold@gmail.com';
$baliuagPassword = '$2y$10$xFOrjRPdx5El1DICxr3bY.8y73sT65hfdUfo1yrOYZk5c5pldBUmS';
$baliuagPin = '$2y$10$6fnJnwK8YYUXs3OJxZG.1..42aDzpEa3xaMI4aP0g8ONzMOuS69.2';
$stmt = $connect->prepare(
    "UPDATE `employees` 
     SET `email` = ?, `password` = ?, `pin` = ?
     WHERE `id` = 1 AND `branch_id` = 1"
);
$stmt->bind_param('sss', $baliuagEmail, $baliuagPassword, $baliuagPin);
$stmt->execute();
$baliuagAffected = $stmt->affected_rows;
$stmt->close();

// Update Bustos POS (branch_id=2)
$bustosEmail = 'gitboycold@gmail.com';
$bustosPassword = '$2y$10$OZntlXH0B61YonQRsg1.vufRX/XE8v4lVCqy6NqLTwRwLmoXMCPFi';
$bustosPin = '$2y$10$EJ82oOUh1A3iDf3tqdbavu4ujCMGeG/dLkrBlNL6BLCOHb8Ps1Y8i';
$stmt = $connect->prepare(
    "UPDATE `employees` 
     SET `email` = ?, `password` = ?, `pin` = ?
     WHERE `id` = 2 AND `branch_id` = 2"
);
$stmt->bind_param('sss', $bustosEmail, $bustosPassword, $bustosPin);
$stmt->execute();
$bustosAffected = $stmt->affected_rows;
$stmt->close();

echo "Baliuag POS updated: " . ($baliuagAffected > 0 ? "SUCCESS" : "FAILED") . PHP_EOL;
echo "Bustos POS updated: " . ($bustosAffected > 0 ? "SUCCESS" : "FAILED") . PHP_EOL;
