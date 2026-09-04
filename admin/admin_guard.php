<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../config/db_config.php';

$adminKey = (int)($_SESSION['admin_key'] ?? $_SESSION['admin_account_id'] ?? -1);
if ($adminKey < 0 || ($_SESSION['employee_role'] ?? '') !== 'admin') {
    header('Location: adminlogin.php');
    exit;
}

$stmt = $connect->prepare("SELECT id, employee_name, email, avatar, branch_id
                           FROM employees
                           WHERE id = ? AND role = 'admin' AND is_active = 1
                           LIMIT 1");
$stmt->bind_param('i', $adminKey);
$stmt->execute();
$adminAccount = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$adminAccount) {
    session_unset();
    session_destroy();
    header('Location: adminlogin.php');
    exit;
}

$_SESSION['admin_key'] = (int)$adminAccount['id'];
$_SESSION['admin_account_id'] = (int)$adminAccount['id'];
$_SESSION['employee_name'] = $adminAccount['employee_name'];
$_SESSION['employee_email'] = $adminAccount['email'];
