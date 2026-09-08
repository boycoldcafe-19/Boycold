<?php
require_once __DIR__ . '/session_config.php';
boycold_start_session();

function boycold_admin_account(mysqli $connect): ?array
{
    if (($_SESSION['admin_logged_in'] ?? false) !== true || ($_SESSION['user_type'] ?? '') !== 'admin' || ($_SESSION['employee_role'] ?? '') !== 'admin') {
        return null;
    }

    $adminId = (int) ($_SESSION['employee_id'] ?? $_SESSION['admin_account_id'] ?? -1);
    if ($adminId < 0) {
        return null;
    }

    $stmt = $connect->prepare(
        "SELECT id, employee_name, email, avatar, branch_id, role, is_active
         FROM employees
         WHERE id = ? AND role = 'admin' AND is_active = 1
         LIMIT 1"
    );
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $admin;
}
