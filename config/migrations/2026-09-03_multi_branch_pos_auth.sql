-- Multi-branch POS authentication seed.
-- Passwords and PINs are bcrypt hashes; source credentials are never stored in plaintext.

UPDATE branches
SET branch_code = 'MAIN', branch_name = 'Baliuag Branch'
WHERE id = 1;

UPDATE branches
SET branch_code = 'BUSTOS', branch_name = 'Bustos Branch'
WHERE id = 2;

INSERT INTO employees
    (firstname, lastname, email, password, pin, role, is_active, branch_id)
VALUES
    ('Baliuag', 'Cashier', 'testboycold@gmail.com',
     '$2y$10$nTzUpN5DH6oBmcWfwGM7Zu5HMkVGlqpm.X1I784Rw6Eb6TbVWyL6m',
     '$2y$10$eb7qikx5dxrfpJb7pN782.OTcPPtpOaKS7UetFz2eqWo4ZtSxere6',
     'cashier', 1, 1),
    ('Bustos', 'Cashier', 'gitboycold@gmail.com',
     '$2y$10$mOHabN7UcNx.NnfsTUFEqurJDjPQgI7zW.E6mvECpBBXn/D.Zao/2',
    '$2y$10$8lzADPSayfFUIn3LG7YQ8.DfJScnxl65sQEeCJJHwXNV4IZD6jTC6',
     'cashier', 1, 2)
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    pin = VALUES(pin),
    role = VALUES(role),
    is_active = VALUES(is_active),
    branch_id = VALUES(branch_id),
    updated_at = CURRENT_TIMESTAMP;

-- Existing nullable branch columns are kept for historical customer orders.
-- All new POS writes obtain branch_id from the authenticated employee session.
