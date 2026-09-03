-- Ensure employee PINs can store password_hash() output.
-- PIN values are stored as hashes, never as plain four-digit numbers.

ALTER TABLE employees
  MODIFY COLUMN pin varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL;