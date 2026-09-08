<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/admin_auth.php';

if (!($adminAccount = boycold_admin_account($connect))) {
    header('Location: ../User/login.php');
    exit;
}

$_SESSION['admin_logged_in'] = true;
$_SESSION['user_type'] = 'admin';
$_SESSION['employee_id'] = (int) $adminAccount['id'];
$_SESSION['admin_key'] = (int) $adminAccount['id'];
$_SESSION['admin_account_id'] = (int) $adminAccount['id'];
$_SESSION['employee_name'] = $adminAccount['employee_name'];
$_SESSION['employee_email'] = $adminAccount['email'];
$_SESSION['employee_role'] = 'admin';
$_SESSION['branch_id'] = (int) ($adminAccount['branch_id'] ?? 0);
