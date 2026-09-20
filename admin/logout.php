<?php
require_once __DIR__ . '/../config/session_config.php';
boycold_start_session();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/activity_logger.php';

$adminId = (int) ($_SESSION['employee_id'] ?? $_SESSION['admin_account_id'] ?? 0);
if ($adminId > 0) {
    boycold_log_activity($connect, [
        'category' => 'login',
        'action' => 'logout',
        'summary' => 'Log Out',
        'details' => 'Administrator logged out from the system.',
        'actor_id' => $adminId,
        'actor_type' => 'admin',
        'branch_id' => (int) ($_SESSION['branch_id'] ?? 0),
        'entity_type' => 'employee',
        'entity_id' => $adminId,
    ]);
}

unset(
    $_SESSION['admin_logged_in'],
    $_SESSION['admin_key'],
    $_SESSION['admin_account_id'],
    $_SESSION['employee_id'],
    $_SESSION['employee_name'],
    $_SESSION['employee_email'],
    $_SESSION['employee_role'],
    $_SESSION['user_type'],
    $_SESSION['branch_id']
);

session_write_close();
header('Location: ../User/login.php?logged_out=1');
exit;
