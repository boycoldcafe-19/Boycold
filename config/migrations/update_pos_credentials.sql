-- Migration: Update POS Employee Credentials
-- Date: 2026-09-08
-- Description: Update Baliuag and Bustos POS employee credentials

-- Update Baliuag POS (branch_id=1)
UPDATE `employees` 
SET `email` = 'testboycold@gmail.com',
    `password` = '$2y$10$xFOrjRPdx5El1DICxr3bY.8y73sT65hfdUfo1yrOYZk5c5pldBUmS',
    `pin` = '$2y$10$6fnJnwK8YYUXs3OJxZG.1..42aDzpEa3xaMI4aP0g8ONzMOuS69.2'
WHERE `id` = 1 AND `branch_id` = 1;

-- Update Bustos POS (branch_id=2)
UPDATE `employees` 
SET `email` = 'gitboycold@gmail.com',
    `password` = '$2y$10$OZntlXH0B61YonQRsg1.vufRX/XE8v4lVCqy6NqLTwRwLmoXMCPFi',
    `pin` = '$2y$10$EJ82oOUh1A3iDf3tqdbavu4ujCMGeG/dLkrBlNL6BLCOHb8Ps1Y8i'
WHERE `id` = 2 AND `branch_id` = 2;
