-- Persistent five-attempt lockout fields for the branch-specific POS accounts.
-- A credential remains locked until an administrator changes that credential
-- from Admin > POS Settings.

ALTER TABLE employees
    ADD COLUMN pos_password_failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER password,
    ADD COLUMN pos_password_locked_at DATETIME NULL DEFAULT NULL AFTER pos_password_failed_attempts,
    ADD COLUMN pos_pin_failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER pin,
    ADD COLUMN pos_pin_locked_at DATETIME NULL DEFAULT NULL AFTER pos_pin_failed_attempts;
