-- Align admin and POS credentials with the app's database-backed employee system.
-- This updates the real employee records used by admin login and branch-specific POS login.

UPDATE `employees`
SET `role` = 'admin',
    `is_active` = 1,
    `branch_id` = 0,
    `email` = 'boycoldcafe19@gmail.com',
    `password` = '$2y$10$MTJzIv02dDw/Pg6V3a/mSObH1qxixLNIluTH27R4TPQj.aeUHf31u'
WHERE `role` = 'admin' OR `email` = 'boycoldcafe19@gmail.com'
LIMIT 1;

INSERT INTO `employees` (`id`, `firstname`, `lastname`, `email`, `password`, `pin`, `role`, `is_active`, `branch_id`)
SELECT COALESCE(MAX(`id`), 0) + 1,
       'Admin',
       'User',
       'boycoldcafe19@gmail.com',
       '$2y$10$MTJzIv02dDw/Pg6V3a/mSObH1qxixLNIluTH27R4TPQj.aeUHf31u',
       '$2y$10$MdxYZx6PVb.NEnN4W90oGeEtbAbj4WFEbKKhY//42N8cRZYDLja0u',
       'admin',
       1,
       0
FROM `employees`
ON DUPLICATE KEY UPDATE
  `firstname` = VALUES(`firstname`),
  `lastname` = VALUES(`lastname`),
  `password` = VALUES(`password`),
  `pin` = VALUES(`pin`),
  `role` = VALUES(`role`),
  `is_active` = VALUES(`is_active`),
  `branch_id` = VALUES(`branch_id`),
  `updated_at` = CURRENT_TIMESTAMP;

-- Correct Bustos branch POS account: password = BustosPOS, pin = 1234
UPDATE `employees`
SET `email` = 'gitboycold@gmail.com',
    `password` = '$2y$10$hpSer.8hyzMjIMCMRQ7qrOAbN.q1qsSW2jquQqqQerWen8fglleE6',
    `pin` = '$2y$10$EJ82oOUh1A3iDf3tqdbavu4ujCMGeG/dLkrBlNL6BLCOHb8Ps1Y8i',
    `role` = 'cashier',
    `is_active` = 1,
    `branch_id` = 2
WHERE `email` = 'gitboycold@gmail.com' OR `branch_id` = 2
LIMIT 1;

-- Correct Baliuag branch POS account: password = BaliuagPOS, pin = 1234
UPDATE `employees`
SET `email` = 'testboycold@gmail.com',
    `password` = '$2y$10$/nwKepJIubi1cNwaDfwQaewVMAGqYgSRp.OOz2R1b3T2s76sbZqx.',
    `pin` = '$2y$10$MdxYZx6PVb.NEnN4W90oGeEtbAbj4WFEbKKhY//42N8cRZYDLja0u',
    `role` = 'cashier',
    `is_active` = 1,
    `branch_id` = 1
WHERE `email` = 'testboycold@gmail.com' OR `branch_id` = 1
LIMIT 1;

-- Ensure a valid branch exists for both POS accounts.
INSERT INTO `branches` (`id`, `branch_code`, `branch_name`, `address`, `status`)
VALUES
  (1, 'MAIN', 'Baliuag Branch', 'Baliuag, Bulacan', 'active'),
  (2, 'BUSTOS', 'Bustos Branch', 'Bustos, Bulacan', 'active')
ON DUPLICATE KEY UPDATE
  `branch_code` = VALUES(`branch_code`),
  `branch_name` = VALUES(`branch_name`),
  `address` = VALUES(`address`),
  `status` = VALUES(`status`);
