-- Preserve the existing branch data while removing duplicate rows from a
-- partially imported branches table. Run once before the schema repair script.
DELETE FROM `branches` WHERE `id` = 1 LIMIT 2;
DELETE FROM `branches` WHERE `id` = 2 LIMIT 2;

ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branch_code` (`branch_code`);