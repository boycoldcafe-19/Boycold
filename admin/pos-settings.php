<?php
require_once __DIR__ . '/admin_guard.php';
require_once '../config/db_config.php';
require_once '../config/activity_logger.php';
require_once '../config/pos_login_lockout.php';
$guardEmployee = $adminAccount;

// Session guard — redirect to flash screen if not logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../auth/flashscreen.php');
    exit;
}

$employeeId = (int) $_SESSION['employee_id'];

// Fetch fresh employee data from DB to validate session
$stmt = $connect->prepare("SELECT id, employee_name, email, password, pin, is_active, branch_id FROM employees WHERE id=?");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee || (int) $employee['is_active'] === 0) {
    session_destroy();
    header('Location: ../auth/flashscreen.php');
    exit;
}
$stmt->close();

// Only these two branch POS accounts can be managed from this screen.  A
// branch is always selected; the settings page must never update an account
// using an "all branches" or client-supplied employee value.
$branchName = 'Administrator';
$branches = [];
$branchesById = [];
$branchesResult = $connect->query(
    "SELECT id, branch_name
     FROM branches
     WHERE status = 'active' AND id IN (1, 2)
     ORDER BY FIELD(id, 1, 2)"
);
if ($branchesResult instanceof mysqli_result) {
    while ($branch = $branchesResult->fetch_assoc()) {
        $branchId = (int) $branch['id'];
        $branches[] = $branch;
        $branchesById[$branchId] = $branch;
    }
}

$requestedBranchValue = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['branch_id'] ?? '')
    : ($_GET['branch_id'] ?? '');
$requestedBranchId = filter_var($requestedBranchValue, FILTER_VALIDATE_INT);
$defaultBranchId = isset($branchesById[1]) ? 1 : (array_key_first($branchesById) ?? 0);
$hasValidRequestedBranch = $requestedBranchId !== false && isset($branchesById[$requestedBranchId]);
$selectedBranchId = $hasValidRequestedBranch
    ? (int) $requestedBranchId
    : ($_SERVER['REQUEST_METHOD'] === 'POST' ? 0 : $defaultBranchId);
$selectedBranchName = $selectedBranchId > 0
    ? (string) $branchesById[$selectedBranchId]['branch_name']
    : '';

function pos_settings_branch_pos_account(mysqli $connect, int $branchId): ?array
{
    $targetStmt = $connect->prepare(
        "SELECT id, employee_name, password, pin, branch_id
         FROM employees
         WHERE branch_id = ? AND role = 'cashier' AND is_active = 1
         ORDER BY id ASC
         LIMIT 1"
    );
    $targetStmt->bind_param('i', $branchId);
    $targetStmt->execute();
    $targetEmployee = $targetStmt->get_result()->fetch_assoc() ?: null;
    $targetStmt->close();

    return $targetEmployee;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_pin') {
    header('Content-Type: application/json');

    if ($selectedBranchId <= 0 || $selectedBranchName === '') {
        echo json_encode(['success' => false, 'errors' => ['form' => 'Please select Baliuag Branch or Bustos Branch.']]);
        exit;
    }

    $targetEmployee = pos_settings_branch_pos_account($connect, $selectedBranchId);
    if (!$targetEmployee) {
        echo json_encode(['success' => false, 'errors' => ['form' => 'No active POS account is configured for ' . $selectedBranchName . '.']]);
        exit;
    }

    $currentPin = trim((string) ($_POST['current_pin'] ?? ''));
    $newPin = trim((string) ($_POST['new_pin'] ?? ''));
    $confirmPin = trim((string) ($_POST['confirm_pin'] ?? ''));
    $errors = [];

    if (!preg_match('/^\d{4}$/', $currentPin)) {
        $errors['current_pin'] = 'Current PIN must contain exactly 4 digits.';
    } elseif (empty($targetEmployee['pin']) || !password_verify($currentPin, $targetEmployee['pin'])) {
        $errors['current_pin'] = 'Current PIN is incorrect.';
    }
    if (!preg_match('/^\d{4}$/', $newPin)) {
        $errors['new_pin'] = 'New PIN must contain exactly 4 digits.';
    }
    if ($newPin !== $confirmPin) {
        $errors['confirm_pin'] = 'PIN confirmation does not match.';
    }
    if (!$errors && password_verify($newPin, $targetEmployee['pin'])) {
        $errors['new_pin'] = 'New PIN must be different from your current PIN.';
    }

    if ($errors) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $hashedPin = password_hash($newPin, PASSWORD_DEFAULT);
    $pinStmt = $connect->prepare(
        "UPDATE employees
         SET pin = ?,
             pos_pin_failed_attempts = 0,
             pos_pin_locked_at = NULL
         WHERE id = ? AND branch_id = ? AND role = 'cashier' AND is_active = 1"
    );
    $targetEmployeeId = (int) $targetEmployee['id'];
    $pinStmt->bind_param('sii', $hashedPin, $targetEmployeeId, $selectedBranchId);
    $updated = $pinStmt->execute() && $pinStmt->affected_rows === 1;
    $pinStmt->close();

    if ($updated) {
        boycold_log_activity($connect, [
            'category' => 'admin',
            'action' => 'pos_pin_updated',
            'summary' => 'POS PIN Updated',
            'details' => 'The administrator updated the POS PIN for ' . $selectedBranchName . '.',
            'actor_id' => $employeeId,
            'actor_type' => 'admin',
            'branch_id' => $selectedBranchId,
            'entity_type' => 'employee',
            'entity_id' => $targetEmployeeId,
        ]);
    }

    echo json_encode(
        $updated
            ? ['success' => true, 'message' => 'PIN changed successfully.']
            : ['success' => false, 'errors' => ['form' => 'Unable to change PIN. Please try again.']]
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    header('Content-Type: application/json');

    if ($selectedBranchId <= 0 || $selectedBranchName === '') {
        echo json_encode(['success' => false, 'errors' => ['form' => 'Please select Baliuag Branch or Bustos Branch.']]);
        exit;
    }

    $targetEmployee = pos_settings_branch_pos_account($connect, $selectedBranchId);
    if (!$targetEmployee) {
        echo json_encode(['success' => false, 'errors' => ['form' => 'No active POS account is configured for ' . $selectedBranchName . '.']]);
        exit;
    }

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $errors = [];

    if ($currentPassword === '' || !password_verify($currentPassword, $targetEmployee['password'])) {
        $errors['current_password'] = 'Current password is incorrect.';
    }
    if (strlen($newPassword) < 8) {
        $errors['new_password'] = 'New password must be at least 8 characters.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Password confirmation does not match.';
    }
    if (!$errors && password_verify($newPassword, $targetEmployee['password'])) {
        $errors['new_password'] = 'New password must be different from your current password.';
    }

    if ($errors) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    // Credential maintenance is intentionally independent from shift_logs.
    // Changing a POS password must keep the branch's current open shift open.
    $passwordStmt = $connect->prepare(
        "UPDATE employees
         SET password = ?,
             pos_password_failed_attempts = 0,
             pos_password_locked_at = NULL
         WHERE id = ? AND branch_id = ? AND role = 'cashier' AND is_active = 1"
    );
    $targetEmployeeId = (int) $targetEmployee['id'];
    $passwordStmt->bind_param('sii', $hashedPassword, $targetEmployeeId, $selectedBranchId);
    $updated = $passwordStmt->execute() && $passwordStmt->affected_rows === 1;
    $passwordStmt->close();

    if ($updated) {
        boycold_log_activity($connect, [
            'category' => 'admin',
            'action' => 'pos_password_updated',
            'summary' => 'POS Password Updated',
            'details' => 'The administrator updated the POS password for ' . $selectedBranchName . '.',
            'actor_id' => $employeeId,
            'actor_type' => 'admin',
            'branch_id' => $selectedBranchId,
            'entity_type' => 'employee',
            'entity_id' => $targetEmployeeId,
        ]);
    }

    echo json_encode(
        $updated
            ? ['success' => true, 'message' => 'Password changed successfully. The current POS shift remains open.']
            : ['success' => false, 'errors' => ['form' => 'Unable to change password. Please try again.']]
    );
    exit;
}

// Get employee name for display
$employeeName = isset($_SESSION['employee_name']) ? $_SESSION['employee_name'] : 'Cashier';

$lastLoginText = 'No recorded login';
$deviceText = 'Unknown device';
$loginTableCheck = $connect->query("SHOW TABLES LIKE 'login_logs'");
if ($loginTableCheck && $loginTableCheck->num_rows > 0) {
    $loginStmt = $connect->prepare(
        'SELECT login_datetime, browser, operating_system
         FROM login_logs
         WHERE employee_id = ? AND login_status = \'success\'
         ORDER BY login_datetime DESC, id DESC
         LIMIT 1'
    );
    $loginStmt->bind_param('i', $employeeId);
    $loginStmt->execute();
    $lastLogin = $loginStmt->get_result()->fetch_assoc();
    $loginStmt->close();

    if ($lastLogin) {
        $lastLoginDate = new DateTimeImmutable($lastLogin['login_datetime'], new DateTimeZone('Asia/Manila'));
        $lastLoginText = $lastLoginDate->format('M j, Y g:i A');
        $deviceText = trim(($lastLogin['browser'] ?? '') . ' on ' . ($lastLogin['operating_system'] ?? ''));
    }
}

?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-css/pos-settings.css">
    <link rel="stylesheet" href="admin-css/admin-sidebar.css">
    <link rel="stylesheet" href="admin-css/admin-responsive.css">
    <link rel="icon" href="../img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - POS Settings</title>
</head>

<body>
    <div class="app-shell">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">

            <div class="sidebar-brand">
                <span class="brand-mark" aria-hidden="true">
                    <img src="../img/ChatGPT Image Jun 23, 2026, 09_22_57 PM 1.png" alt="">
                </span>
                <span class="brand-text">
                    <span class="brand-name">B<span class="special-letter">o</span><span class="special-letter-2">y</span>C<span class="special-letter">o</span>LD CAFE</span>
                    <span class="brand-sub">Administration Panel</span>
                </span>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-top">
                    <ul>
                        <li>
                            <a href="dashboard.php">
                                <span class="nav-icon1"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M0.5 5C0.367392 5 0.240215 4.94732 0.146447 4.85355C0.0526785 4.75979 0 4.63261 0 4.5V0.5C0 0.367392 0.0526785 0.240215 0.146447 0.146447C0.240215 0.0526785 0.367392 0 0.5 0H4.5C4.63261 0 4.75979 0.0526785 4.85355 0.146447C4.94732 0.240215 5 0.367392 5 0.5V4.5C5 4.63261 4.94732 4.75979 4.85355 4.85355C4.75979 4.94732 4.63261 5 4.5 5H0.5ZM7.5 5C7.36739 5 7.24021 4.94732 7.14645 4.85355C7.05268 4.75979 7 4.63261 7 4.5V0.5C7 0.367392 7.05268 0.240215 7.14645 0.146447C7.24021 0.0526785 7.36739 0 7.5 0H11.5C11.6326 0 11.7598 0.0526785 11.8536 0.146447C11.9473 0.240215 12 0.367392 12 0.5V4.5C12 4.63261 11.9473 4.75979 11.8536 4.85355C11.7598 4.94732 11.6326 5 11.5 5H7.5ZM0.5 12C0.367392 12 0.240215 11.9473 0.146447 11.8536C0.0526785 11.7598 0 11.6326 0 11.5V7.5C0 7.36739 0.0526785 7.24021 0.146447 7.14645C0.240215 7.05268 0.367392 7 0.5 7H4.5C4.63261 7 4.75979 7.05268 4.85355 7.14645C4.94732 7.24021 5 7.36739 5 7.5V11.5C5 11.6326 4.94732 11.7598 4.85355 11.8536C4.75979 11.9473 4.63261 12 4.5 12H0.5ZM7.5 12C7.36739 12 7.24021 11.9473 7.14645 11.8536C7.05268 11.7598 7 11.6326 7 11.5V7.5C7 7.36739 7.05268 7.24021 7.14645 7.14645C7.24021 7.05268 7.36739 7 7.5 7H11.5C11.6326 7 11.7598 7.05268 11.8536 7.14645C11.9473 7.24021 12 7.36739 12 7.5V11.5C12 11.6326 11.9473 11.7598 11.8536 11.8536C11.7598 11.9473 11.6326 12 11.5 12H7.5Z"
                                            fill="currentColor" />
                                    </svg></span>
                                <span class="nav-label">Dashboard</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="orders.php">
                                <span class="nav-icon"><svg width="19" height="22" viewBox="0 0 19 22" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M14.8882 1H3.31469C2.03632 1 1 2.03632 1 3.31469V18.3602C1 19.6386 2.03632 20.6749 3.31469 20.6749H14.8882C16.1665 20.6749 17.2029 19.6386 17.2029 18.3602V3.31469C17.2029 2.03632 16.1665 1 14.8882 1Z"
                                            stroke="currentColor" stroke-width="2" />
                                        <path
                                            d="M5.62939 6.78662H12.5735M5.62939 11.416H12.5735M5.62939 16.0454H10.2588"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg></span>
                                <span class="nav-label">Orders</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="data-analytics.php">
                                <span class="nav-icon2"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M15.8601 4.39V19.39C15.8601 21.06 17.0001 22 18.2501 22C19.3901 22 20.6401 21.21 20.6401 19.39V4.5C20.6401 2.96 19.5001 2 18.2501 2C17.0001 2 15.8601 3.06 15.8601 4.39ZM9.61011 12V19.39C9.61011 21.07 10.7701 22 12.0001 22C13.1401 22 14.3901 21.21 14.3901 19.39V12.11C14.3901 10.57 13.2501 9.61 12.0001 9.61C10.7501 9.61 9.61011 10.67 9.61011 12ZM5.75011 17.23C7.07011 17.23 8.14011 18.3 8.14011 19.61C8.14011 20.2439 7.88831 20.8518 7.44009 21.3C6.99188 21.7482 6.38398 22 5.75011 22C5.11624 22 4.50833 21.7482 4.06012 21.3C3.61191 20.8518 3.36011 20.2439 3.36011 19.61C3.36011 18.3 4.43011 17.23 5.75011 17.23Z"
                                            fill="white" />
                                    </svg></span>
                                <span class="nav-label">Data Analytics</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="forecasting.php">
                                <span class="nav-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M21.3751 6C21.0698 6.00008 20.7692 6.0747 20.4993 6.21737C20.2294 6.36005 19.9984 6.56647 19.8264 6.81869C19.6545 7.07092 19.5467 7.36132 19.5124 7.66468C19.4782 7.96803 19.5185 8.27516 19.6299 8.55938L15.6845 12.5048C15.2447 12.3317 14.7556 12.3317 14.3157 12.5048L11.4953 9.68438C11.6069 9.40009 11.6475 9.09283 11.6134 8.78931C11.5792 8.48579 11.4715 8.1952 11.2995 7.94281C11.1275 7.69042 10.8964 7.48387 10.6264 7.34113C10.3563 7.19839 10.0555 7.12377 9.75011 7.12377C9.44467 7.12377 9.14386 7.19839 8.87384 7.34113C8.60381 7.48387 8.37274 7.69042 8.20073 7.94281C8.02872 8.1952 7.92096 8.48579 7.88684 8.78931C7.85272 9.09283 7.89327 9.40009 8.00495 9.68438L3.30948 14.3798C2.90848 14.2225 2.46554 14.2081 2.05514 14.339C1.64474 14.4698 1.29192 14.738 1.056 15.0984C0.82007 15.4588 0.715432 15.8895 0.759675 16.3179C0.803918 16.7464 0.994344 17.1466 1.29893 17.4512C1.60352 17.7558 2.0037 17.9462 2.43218 17.9904C2.86065 18.0347 3.2913 17.93 3.6517 17.6941C4.0121 17.4582 4.28028 17.1054 4.41114 16.695C4.542 16.2846 4.52757 15.8416 4.37026 15.4406L9.06573 10.7452C9.50556 10.9183 9.99466 10.9183 10.4345 10.7452L13.2549 13.5656C13.1433 13.8499 13.1027 14.1572 13.1368 14.4607C13.171 14.7642 13.2787 15.0548 13.4507 15.3072C13.6227 15.5596 13.8538 15.7661 14.1238 15.9089C14.3939 16.0516 14.6947 16.1262 15.0001 16.1262C15.3055 16.1262 15.6063 16.0516 15.8764 15.9089C16.1464 15.7661 16.3775 15.5596 16.5495 15.3072C16.7215 15.0548 16.8293 14.7642 16.8634 14.4607C16.8975 14.1572 16.8569 13.8499 16.7453 13.5656L20.6907 9.62016C20.9475 9.72102 21.2233 9.76399 21.4986 9.74601C21.7738 9.72803 22.0417 9.64953 22.2832 9.51613C22.5246 9.38272 22.7336 9.19768 22.8953 8.97421C23.0571 8.75073 23.1675 8.49433 23.2187 8.22329C23.2699 7.95225 23.2607 7.67324 23.1918 7.40616C23.1228 7.13907 22.9957 6.8905 22.8197 6.67816C22.6436 6.46582 22.4228 6.29495 22.1731 6.17773C21.9234 6.0605 21.651 5.99982 21.3751 6Z"
                                            fill="white" />
                                    </svg></span>
                                <span class="nav-label">Forecasting</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="inventory.php">
                                <span class="nav-icon"><svg width="30" height="30" viewBox="0 0 30 30" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M25.9126 20.6502V9.48774C25.9136 9.34411 25.8793 9.20245 25.8126 9.07524C25.7205 8.87674 25.5611 8.71729 25.3626 8.62524L15.3626 4.15024C15.2409 4.09499 15.1088 4.06641 14.9751 4.06641C14.8414 4.06641 14.7093 4.09499 14.5876 4.15024L4.5876 8.62524C4.42677 8.70617 4.29077 8.82903 4.19397 8.98084C4.09716 9.13265 4.04314 9.30778 4.0376 9.48774V20.5127C4.04694 20.6918 4.10252 20.8653 4.19891 21.0165C4.2953 21.1676 4.42922 21.2912 4.5876 21.3752L14.5876 25.8502C14.7086 25.908 14.841 25.9379 14.9751 25.9379C15.1092 25.9379 15.2416 25.908 15.3626 25.8502L25.3626 21.3752C25.507 21.3091 25.6327 21.2083 25.7287 21.0818C25.8247 20.9553 25.8878 20.8071 25.9126 20.6502ZM5.9126 10.9252L14.0376 14.5752V23.5502L5.9126 19.9127V10.9252ZM15.9126 14.5752L24.0376 10.9252V19.9127L15.9126 23.5502V14.5752ZM15.0001 6.02524L22.7126 9.48774L15.0001 12.9377L7.2876 9.48774L15.0001 6.02524Z"
                                            fill="white" />
                                    </svg></span>
                                <span class="nav-label">Inventory</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="mapping.php">
                                <span class="nav-icon"><svg width="27" height="27" viewBox="0 0 27 27" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M23.1154 4.13114C22.9044 3.92024 22.6183 3.80176 22.32 3.80176C22.0217 3.80176 21.7356 3.92024 21.5246 4.13114L15.4901 10.1656H2.25V12.4156H2.259C2.50425 18.4119 7.443 23.1988 13.5 23.1988C19.557 23.1988 24.4958 18.4119 24.741 12.4156H24.75V10.1656H18.6716L23.1154 5.72189C23.3263 5.51092 23.4448 5.22483 23.4448 4.92652C23.4448 4.62821 23.3263 4.34211 23.1154 4.13114ZM15.9491 12.4156H22.4888C22.3733 14.7218 21.3759 16.8954 19.7029 18.4869C18.0298 20.0783 15.8091 20.9658 13.5 20.9658C11.1909 20.9658 8.97019 20.0783 7.29713 18.4869C5.62406 16.8954 4.62667 14.7218 4.51125 12.4156H15.9491Z"
                                            fill="white" />
                                    </svg></span>
                                <span class="nav-label">Ingredients Mapping</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                    </ul>

                    <div class="sidebar-divider"></div>

                    <ul>
                        <li>
                            <a href="menu-management.php">
                                <span class="nav-icon"><i class="fa-solid fa-bars"></i></span>
                                <span class="nav-label">Menu Management</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="customers.php">
                                <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
                                <span class="nav-label">Customers</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>

                        <li>
                            <a href="loyalty-card.php">
                                <span class="nav-icon"><svg width="22" height="18" viewBox="0 0 22 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M0.75 8.75C0.75 4.979 0.75 3.093 1.922 1.922C3.094 0.751 4.979 0.75 8.75 0.75H12.75C16.521 0.75 18.407 0.75 19.578 1.922C20.749 3.094 20.75 4.979 20.75 8.75C20.75 12.521 20.75 14.407 19.578 15.578C18.406 16.749 16.521 16.75 12.75 16.75H8.75C4.979 16.75 3.093 16.75 1.922 15.578C0.751 14.406 0.75 12.521 0.75 8.75Z"
                                            stroke="currentColor" stroke-width="1.5" />
                                        <path d="M8.75 12.75H4.75M12.75 12.75H11.25M0.75 6.75H20.75"
                                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                    </svg></span>
                                <span class="nav-label">Loyalty Card</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="activitylog.php">
                                <span class="nav-icon">
                                    <svg width="25" height="25" viewBox="0 0 25 25" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M12.4962 3.38333C12.5214 3.63063 12.4473 3.8778 12.2902 4.07049C12.1332 4.26317 11.906 4.38559 11.6587 4.41083C10.1302 4.56979 8.67811 5.15855 7.47051 6.1089C6.26292 7.05926 5.3493 8.3323 4.8355 9.78056C4.32169 11.2288 4.22874 12.793 4.5674 14.2919C4.90605 15.7909 5.66247 17.1632 6.749 18.2498C7.83554 19.3365 9.20773 20.0931 10.7066 20.432C12.2055 20.7709 13.7697 20.6781 15.218 20.1645C16.6663 19.6509 17.9395 18.7375 18.89 17.53C19.8406 16.3226 20.4295 14.8705 20.5887 13.3421C20.5995 13.2182 20.6348 13.0978 20.6926 12.9877C20.7504 12.8776 20.8295 12.7801 20.9253 12.7009C21.0211 12.6217 21.1317 12.5623 21.2507 12.5262C21.3697 12.4902 21.4946 12.4781 21.6183 12.4908C21.742 12.5035 21.8619 12.5407 21.9711 12.6001C22.0803 12.6596 22.1765 12.7402 22.2543 12.8372C22.332 12.9342 22.3897 13.0457 22.4239 13.1652C22.4582 13.2847 22.4683 13.4099 22.4537 13.5333C22.2584 15.4145 21.5341 17.2019 20.3646 18.6883C19.195 20.1747 17.6283 21.2992 15.8458 21.9316C14.0633 22.564 12.1382 22.6783 10.2934 22.2613C8.44859 21.8444 6.75973 20.9132 5.42251 19.5756C4.0853 18.2381 3.15448 16.5491 2.73793 14.7042C2.32137 12.8593 2.43614 10.9341 3.06893 9.15182C3.70172 7.3695 4.82662 5.80298 6.31327 4.63381C7.79992 3.46463 9.58745 2.74067 11.4687 2.54583C11.716 2.52068 11.9632 2.59478 12.1559 2.75183C12.3485 2.90888 12.471 3.13603 12.4962 3.38333ZM13.7712 3.30083C13.7998 3.18094 13.8518 3.06787 13.9242 2.9681C13.9966 2.86832 14.0879 2.7838 14.193 2.71936C14.298 2.65492 14.4148 2.61184 14.5365 2.59257C14.6583 2.57331 14.7826 2.57824 14.9025 2.60708C15.3758 2.72041 15.8354 2.86625 16.2812 3.04458C16.5058 3.14092 16.6839 3.32118 16.7776 3.54694C16.8712 3.7727 16.873 4.02611 16.7826 4.25316C16.6921 4.48021 16.5165 4.66296 16.2933 4.76246C16.0701 4.86196 15.8168 4.87035 15.5875 4.78583C15.225 4.64166 14.8508 4.52333 14.465 4.43083C14.2233 4.37273 14.0147 4.22106 13.8848 4.00917C13.7549 3.79728 13.7133 3.5425 13.7712 3.30083ZM21.9562 8.71708C21.8642 8.48601 21.6842 8.30095 21.4557 8.20261C21.2273 8.10427 20.9692 8.10071 20.7381 8.1927C20.507 8.2847 20.322 8.46472 20.2236 8.69316C20.1253 8.92161 20.1217 9.17976 20.2137 9.41083C20.3579 9.77416 20.4766 10.1483 20.57 10.5333C20.628 10.7752 20.7797 10.9841 20.9917 11.1141C21.0967 11.1784 21.2133 11.2215 21.335 11.2408C21.4566 11.26 21.5808 11.2552 21.7006 11.2265C21.8203 11.1977 21.9332 11.1457 22.0329 11.0733C22.1325 11.001 22.2169 10.9097 22.2813 10.8047C22.3457 10.6997 22.3887 10.5831 22.408 10.4614C22.4273 10.3398 22.4224 10.2156 22.3937 10.0958C22.2813 9.62629 22.1351 9.1655 21.9562 8.71708ZM17.8625 4.90708C18.0257 4.71963 18.2568 4.60472 18.5048 4.58761C18.7528 4.5705 18.9975 4.65259 19.185 4.81583C19.5691 5.15 19.9266 5.51125 20.2575 5.89958C20.4186 6.08904 20.4978 6.33475 20.4778 6.58265C20.4577 6.83055 20.34 7.06034 20.1506 7.22145C19.9611 7.38257 19.7154 7.46183 19.4675 7.44179C19.2196 7.42174 18.9898 7.30404 18.8287 7.11458C18.5584 6.79918 18.266 6.50344 17.9537 6.22958C17.7663 6.06629 17.6513 5.83523 17.6342 5.58722C17.6171 5.33922 17.6992 5.09457 17.8625 4.90708ZM12.5 7.18708C12.5 6.93844 12.4012 6.69998 12.2254 6.52417C12.0496 6.34835 11.8111 6.24958 11.5625 6.24958C11.3138 6.24958 11.0754 6.34835 10.8995 6.52417C10.7237 6.69998 10.625 6.93844 10.625 7.18708V13.4371C10.625 13.9558 11.045 14.3746 11.5625 14.3746H15.3125C15.5611 14.3746 15.7996 14.2758 15.9754 14.1C16.1512 13.9242 16.25 13.6857 16.25 13.4371C16.25 13.1884 16.1512 12.95 15.9754 12.7742C15.7996 12.5984 15.5611 12.4996 15.3125 12.4996H12.5V7.18708Z"
                                            fill="white" />
                                    </svg>
                                </span>
                                <span class="nav-label">Activity Log</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="feedback.php">
                                <span class="nav-icon"><i class="fa-solid fa-star"></i></span>
                                <span class="nav-label">Feedback &amp; Reviews</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                    </ul>

                    <div class="sidebar-divider"></div>
                    <ul>
                        <li>
                            <a href="pos-settings.php" class="active">
                                <span class="nav-icon"><i class="fa-solid fa-gear"></i></span>
                                <span class="nav-label">POS Settings</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="adminsettings.php">
                                <span class="nav-icon"><i class="fa-solid fa-gear"></i></span>
                                <span class="nav-label">Admin Settings</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="logout.php" class="logout-link">
                                <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                                <span class="nav-label">Log Out</span>
                            </a>
                        </li>
                    </ul>
            </nav>
        </aside>

        <!-- MAIN PANEL -->
        <div class="main-panel">

            <div class="top-header">
                <div class="notif-wrap">
                    <button class="icon-btn" id="notifBtn" type="button" aria-label="Inventory warnings" aria-expanded="false">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </button>
                </div>
                <button class="profile-btn" aria-label="Admin profile">
                    <div class="profile-avatar">
                        <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.75737 25.5989C10.0298 24.6259 11.452 23.8589 13.0238 23.298C14.5957 22.7372 16.2424 22.4562 17.964 22.4552C19.6855 22.4542 21.3322 22.7352 22.9041 23.298C24.4759 23.8609 25.8981 24.6279 27.1705 25.5989C28.0438 24.576 28.7239 23.4158 29.211 22.1184C29.698 20.821 29.941 19.4363 29.94 17.9642C29.94 14.6458 28.7738 11.82 26.4415 9.48666C24.1092 7.15332 21.2833 5.98715 17.964 5.98815C14.6446 5.98915 11.8187 7.15582 9.48641 9.48815C7.15408 11.8205 5.98791 14.6458 5.98791 17.9642C5.98791 19.4363 6.23142 20.821 6.71845 22.1184C7.20547 23.4158 7.88512 24.576 8.75737 25.5989ZM14.2409 17.9447C13.2299 16.9358 12.7244 15.6947 12.7244 14.2217C12.7244 12.7486 13.2299 11.5071 14.2409 10.4971C15.2519 9.48715 16.4929 8.98216 17.964 8.98216C19.435 8.98216 20.6765 9.48765 21.6885 10.4986C22.7005 11.5096 23.2055 12.7506 23.2035 14.2217C23.2015 15.6927 22.6965 16.9343 21.6885 17.9462C20.6805 18.9582 19.439 19.4632 17.964 19.4612C16.4889 19.4592 15.2474 18.9542 14.2394 17.9462M17.964 32.9343C15.8931 32.9343 13.947 32.541 12.1256 31.7546C10.3043 30.9682 8.71995 29.9018 7.37264 28.5555C6.02534 27.2092 4.95897 25.6249 4.17354 23.8025C3.38811 21.9802 2.9949 20.0341 2.9939 17.9642C2.9929 15.8943 3.38611 13.9482 4.17354 12.1259C4.96096 10.3035 6.02733 8.71919 7.37264 7.37288C8.71795 6.02658 10.3023 4.96021 12.1256 4.17378C13.949 3.38735 15.8951 2.99414 17.964 2.99414C20.0328 2.99414 21.9789 3.38735 23.8023 4.17378C25.6256 4.96021 27.21 6.02658 28.5553 7.37288C29.9006 8.71919 30.9675 10.3035 31.7559 12.1259C32.5443 13.9482 32.937 15.8943 32.934 17.9642C32.931 20.0341 32.5378 21.9802 31.7544 23.8025C30.9709 25.6249 29.9046 27.2092 28.5553 28.5555C27.206 29.9018 25.6216 30.9687 23.8023 31.7561C21.9829 32.5435 20.0368 32.9363 17.964 32.9343Z" fill="black"/>
                        </svg>
                    </div>
                    <div class="profile-info">
                        <span class="profile-role">Admin</span>
                    </div>
                </button>
            </div>

            <div class="settings-workspace">
                <div class="settings-header-row">
                    <div class="settings-heading">
                        <h1>POS Settings</h1>
                        <p>Manage your account and system preferences.</p>
                    </div>

                    <div class="settings-header-controls">
                        <div class="branch-selector">
                            <label class="visually-hidden" for="posSettingsBranchSelect">Selected branch</label>
                            <select class="branch-select" id="posSettingsBranchSelect" aria-label="Selected branch">
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo (int) $branch['id']; ?>" <?php echo $selectedBranchId === (int) $branch['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars((string) $branch['branch_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>
                </div>

                <div class="settings-grid">

                    <!-- Change Password -->
                    <section class="settings-card">
                        <div class="settings-card-header">
                            <h2>Change Password (<?php echo htmlspecialchars($selectedBranchName, ENT_QUOTES, 'UTF-8'); ?>)</h2>
                            <p>Update the POS password for the selected branch only.</p>
                        </div>
                        <form class="password-form" id="passwordForm" novalidate>
                            <input type="hidden" name="branch_id" value="<?php echo (int) $selectedBranchId; ?>">
                            <div class="password-group">
                                <label for="currentPassword">Current Password</label>
                                <div class="password-field">
                                    <input type="password" id="currentPassword" name="current_password" autocomplete="current-password" placeholder="Enter your current password" required>
                                    <button type="button" class="toggle-visibility" aria-label="Show password">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="password-group">
                                <label for="newPassword">New Password</label>
                                <div class="password-field">
                                    <input type="password" id="newPassword" name="new_password" autocomplete="new-password" minlength="8" placeholder="Enter your new password" required>
                                    <button type="button" class="toggle-visibility" aria-label="Show password">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="password-group">
                                <label for="confirmPassword">Confirm New Password</label>
                                <div class="password-field">
                                    <input type="password" id="confirmPassword" name="confirm_password" autocomplete="new-password" minlength="8" placeholder="Confirm your new password" required>
                                    <button type="button" class="toggle-visibility" aria-label="Show password">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <p class="form-message" id="passwordMessage" role="status" aria-live="polite"></p>
                            <button type="submit" class="btn-solid" id="changePasswordBtn">Update Password</button>
                        </form>
                    </section>

                    <!-- Change PIN -->
                    <section class="settings-card">
                        <div class="settings-card-header">
                            <h2>Change PIN (POS PIN, <?php echo htmlspecialchars($selectedBranchName, ENT_QUOTES, 'UTF-8'); ?>)</h2>
                            <p>Update the 4-digit POS PIN for the selected branch only.</p>
                        </div>
                        <form class="password-form" id="pinForm" novalidate>
                            <input type="hidden" name="branch_id" value="<?php echo (int) $selectedBranchId; ?>">
                            <div class="password-group">
                                <label for="currentPin">Current PIN</label>
                                <div class="password-field">
                                    <input type="password" id="currentPin" name="current_pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" autocomplete="current-password" placeholder="Enter your current PIN">
                                    <button type="button" class="toggle-visibility" aria-label="Show PIN">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="password-group">
                                <label for="newPin">New PIN</label>
                                <div class="password-field">
                                    <input type="password" id="newPin" name="new_pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" autocomplete="new-password" placeholder="Enter a new 4-digit PIN">
                                    <button type="button" class="toggle-visibility" aria-label="Show PIN">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="password-group">
                                <label for="confirmPin">Confirm New PIN</label>
                                <div class="password-field">
                                    <input type="password" id="confirmPin" name="confirm_pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" autocomplete="new-password" placeholder="Confirm your new PIN">
                                    <button type="button" class="toggle-visibility" aria-label="Show PIN">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <p class="form-message" id="pinMessage" role="status" aria-live="polite"></p>
                            <button type="submit" class="btn-solid">Change PIN</button>
                        </form>
                    </section>

                    <!-- Authorization PIN -->
                    <!-- UI only for now: no backend handler yet, so the form is blocked from submitting. -->
                    <section class="settings-card">
                        <div class="settings-card-header">
                            <h2>Authorization PIN</h2>
                            <p>Set the PIN used to authorize sensitive actions</p>
                        </div>
                        <form class="password-form" id="authPinForm" method="post" onsubmit="return false;" novalidate>
                            <div class="password-group">
                                <label for="currentAuthPin">Current Authorization PIN</label>
                                <div class="password-field">
                                    <input type="password" id="currentAuthPin" name="current_auth_pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" autocomplete="off" placeholder="Enter current authorization PIN">
                                    <button type="button" class="toggle-visibility" aria-label="Show PIN">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="password-group">
                                <label for="newAuthPin">New Authorization PIN</label>
                                <div class="password-field">
                                    <input type="password" id="newAuthPin" name="new_auth_pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" autocomplete="off" placeholder="Enter new authorization PIN">
                                    <button type="button" class="toggle-visibility" aria-label="Show PIN">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="password-group">
                                <label for="confirmAuthPin">Confirm Authorization PIN</label>
                                <div class="password-field">
                                    <input type="password" id="confirmAuthPin" name="confirm_auth_pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" autocomplete="off" placeholder="Confirm authorization PIN">
                                    <button type="button" class="toggle-visibility" aria-label="Show PIN">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <p class="form-message" id="authPinMessage" role="status" aria-live="polite"></p>
                            <button type="submit" class="btn-solid">Update Authorization PIN</button>
                        </form>
                    </section>

                    <!-- Session -->
                    <section class="settings-card">
                        <div class="settings-card-header">
                            <h2>Session</h2>
                            <p>Manage your active session.</p>
                        </div>
                        <dl class="settings-info-list">
                            <div>
                                <dt>Last Log In</dt>
                                <dd><?= htmlspecialchars($lastLoginText) ?></dd>
                            </div>
                            <div>
                                <dt>Device</dt>
                                <dd><?= htmlspecialchars($deviceText) ?></dd>
                            </div>
                        </dl>
                    </section>

                    <!-- About -->
                    <section class="settings-card">
                        <div class="settings-card-header">
                            <h2>About</h2>
                            <p>System information about this POS</p>
                        </div>
                        <dl class="settings-info-list settings-info-list--rows">
                            <div>
                                <dt>POS Version</dt>
                                <dd>1.0.0</dd>
                            </div>
                            <div>
                                <dt>Build</dt>
                                <dd>2026.05.25</dd>
                            </div>
                            <div>
                                <dt>Last Updated</dt>
                                <dd>May 25, 2026</dd>
                            </div>
                        </dl>
                    </section>


                </div>
            </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('.toggle-visibility').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                const isHidden = input.getAttribute('type') === 'password';
                input.setAttribute('type', isHidden ? 'text' : 'password');
                icon.classList.toggle('fa-eye', !isHidden);
                icon.classList.toggle('fa-eye-slash', isHidden);
                this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            });
        });

        const pinForm = document.getElementById('pinForm');
        const pinMessage = document.getElementById('pinMessage');

        pinForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            pinMessage.textContent = '';
            pinMessage.className = 'form-message';

            const formData = new FormData(pinForm);
            formData.append('action', 'change_pin');
            const submitButton = pinForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            try {
                const response = await fetch('pos-settings.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (!data.success) {
                    pinMessage.textContent = Object.values(data.errors || {})[0] || 'Unable to change PIN.';
                    pinMessage.classList.add('error');
                    return;
                }

                pinMessage.textContent = data.message;
                pinMessage.classList.add('success');
                pinForm.reset();
            } catch (error) {
                pinMessage.textContent = 'Unable to change PIN. Please try again.';
                pinMessage.classList.add('error');
            } finally {
                submitButton.disabled = false;
            }
        });

        const passwordForm = document.getElementById('passwordForm');
        const passwordMessage = document.getElementById('passwordMessage');
        const changePasswordBtn = document.getElementById('changePasswordBtn');

        passwordForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            passwordMessage.textContent = '';
            passwordMessage.className = 'form-message';
            changePasswordBtn.disabled = true;

            try {
                const formData = new FormData(passwordForm);
                formData.append('action', 'change_password');
                const response = await fetch('pos-settings.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await response.json();

                if (!data.success) {
                    passwordMessage.textContent = Object.values(data.errors || {})[0] || 'Unable to change password.';
                    passwordMessage.classList.add('error');
                    return;
                }

                passwordMessage.textContent = data.message;
                passwordMessage.classList.add('success');
                passwordForm.reset();
            } catch (error) {
                passwordMessage.textContent = 'Unable to change password. Please try again.';
                passwordMessage.classList.add('error');
            } finally {
                changePasswordBtn.disabled = false;
            }
        });


        const lightbtn = document.getElementById("lightMode");
        const darkbtn = document.getElementById("darkMode");

        document.getElementById('posSettingsBranchSelect')?.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('branch_id', this.value);
            window.location.href = url.toString();
        });

        function applyTheme(theme) {
            if (theme === "dark") {
                document.body.classList.add("dark-theme");
                darkbtn?.classList.add("active");
                lightbtn?.classList.remove("active");
            } else {
                document.body.classList.remove("dark-theme");
                lightbtn?.classList.add("active");
                darkbtn?.classList.remove("active");
            }
        }

        const notifBtn = document.getElementById("notifBtn");
        const notifDropdown = document.getElementById("notifDropdown");
        const markAllRead = document.getElementById("markAllRead");
        const notifBadge = document.getElementById("notifBadge");
        const notifList = document.getElementById("notifList");

        if (notifBtn?.dataset.inventoryAlert !== "true") {
            notifBtn?.addEventListener("click", (e) => {
                e.stopPropagation();
                notifDropdown?.classList.toggle("open");
            });

            document.addEventListener("click", (e) => {
                if (!notifDropdown?.contains(e.target) && !notifBtn?.contains(e.target)) {
                    notifDropdown?.classList.remove("open");
                }
            });

            markAllRead?.addEventListener("click", (e) => {
                e.preventDefault();
                notifList?.querySelectorAll(".notif-item.unread").forEach(item => {
                    item.classList.remove("unread");
                });
                if (notifBadge) notifBadge.style.display = "none";
            });
        }

        // Apply saved theme on load (defaults to dark to match this page, matching body class already in markup)
        const savedTheme = localStorage.getItem("boycold_theme") || "dark";
        applyTheme(savedTheme);

        if (lightbtn) {
            lightbtn.onclick = () => {
                localStorage.setItem("boycold_theme", "light");
                applyTheme("light");
            };
        }

        if (darkbtn) {
            darkbtn.onclick = () => {
                localStorage.setItem("boycold_theme", "dark");
                applyTheme("dark");
            };
        }
    </script>
    <script src="admin-js/inventory-warning.js"></script>
    <script src="admin-js/admin-responsive.js"></script>
</body>

</html>
