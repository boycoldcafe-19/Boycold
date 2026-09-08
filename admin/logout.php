<?php
require_once __DIR__ . '/../config/session_config.php';
boycold_start_session();

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