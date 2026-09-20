<?php
require_once __DIR__ . '/../config/session_config.php';
boycold_start_session();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/activity_logger.php';

// Admins normally leave through admin/logout.php. This fallback preserves the
// audit trail when an admin session is ended from a shared User-page route.
$isAdminSession = ($_SESSION['admin_logged_in'] ?? false) === true
    && ($_SESSION['user_type'] ?? '') === 'admin';
$adminId = (int) ($_SESSION['employee_id'] ?? $_SESSION['admin_account_id'] ?? 0);
if ($isAdminSession && $adminId > 0) {
    $adminStmt = $connect->prepare(
        "SELECT id, branch_id
         FROM employees
         WHERE id = ? AND role = 'admin' AND is_active = 1
         LIMIT 1"
    );
    $adminStmt->bind_param('i', $adminId);
    $adminStmt->execute();
    $admin = $adminStmt->get_result()->fetch_assoc();
    $adminStmt->close();

    if ($admin) {
        boycold_log_activity($connect, [
            'category' => 'login',
            'action' => 'logout',
            'summary' => 'Log Out',
            'details' => 'Administrator logged out from the system.',
            'actor_id' => (int) $admin['id'],
            'actor_type' => 'admin',
            'branch_id' => (int) ($admin['branch_id'] ?? 0),
            'entity_type' => 'employee',
            'entity_id' => (int) $admin['id'],
        ]);
    }
}

session_destroy();
setcookie('remember_email', '', time() - 3600, '/');
header('Location: login.php');
exit;
