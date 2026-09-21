<?php
// Pin PHP's date/time functions to Manila local time. Without this, the
// server's own default timezone (often UTC on shared hosting) is used,
// which throws off both the displayed order times and the "Today" /
// "Yesterday" grouping below whenever the server's day boundary doesn't
// line up with Manila's.
date_default_timezone_set('Asia/Manila');

require_once '../auth/guard.php';
pos_start_session();
require_once '../config/db_config.php';
$guardEmployee = pos_require_employee($connect);
require_once '../../config/shift_manager.php';
require_once '../../config/payments.php';
require_once '../../config/order_void_service.php';

// Session guard — redirect to flash screen if not logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../auth/flashscreen.php');
    exit;
}

$employeeId = (int) $_SESSION['employee_id'];

// Fetch fresh employee data from DB to validate session
$stmt = $connect->prepare("SELECT id, employee_name, email, is_active, branch_id FROM employees WHERE id=?");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee || (int) $employee['is_active'] === 0) {
    session_destroy();
    header('Location: ../auth/flashscreen.php');
    exit;
}
$stmt->close();

// Reconcile missed 2:00 AM boundaries and use the shared branch shift.
$branchId = (int) ($employee['branch_id'] ?? $_SESSION['branch_id'] ?? 0);
boycold_ensure_order_void_schema($connect);
pos_reconcile_branch_shift($connect, $branchId, $employeeId);
$shiftStmt = $connect->prepare("SELECT id, opening_cash_float, opened_at FROM shift_logs WHERE branch_id = ? AND status = 'open' LIMIT 1");
$shiftStmt->bind_param('i', $branchId);
$shiftStmt->execute();
$shiftResult = $shiftStmt->get_result()->fetch_assoc();
$shiftStmt->close();

if (!$shiftResult) {
    header('Location: pos-shift.php');
    exit;
}

// Store shift info for use in the page
$shiftId = $shiftResult['id'];
$openingCash = $shiftResult['opening_cash_float'];
$shiftOpenedAt = $shiftResult['opened_at'];

// Get branch name for profile display
$branchName = 'Main Branch';
$branchId = (int) ($employee['branch_id'] ?? $_SESSION['branch_id'] ?? 0);

// Get employee name for display
$employeeName = isset($_SESSION['employee_name']) ? $_SESSION['employee_name'] : 'Cashier';

if ($branchId > 0) {
    $branchStmt = $connect->prepare("SELECT branch_name FROM branches WHERE id = ?");
    $branchStmt->bind_param('i', $branchId);
    $branchStmt->execute();
    $branchResult = $branchStmt->get_result()->fetch_assoc();
    if ($branchResult) {
        $branchName = strtoupper($guardEmployee['branch_code'] . ' - ' . $guardEmployee['branch_name']);
    }
    $branchStmt->close();
}

// Pull every order, newest first, joining the customer's phone number
// from the users table (matched on user_name, since that's how orders
// links back to an account).
$sql = "SELECT o.id, o.user_name, o.status, o.voided_at, o.order_type, o.payment_method,
               o.payment_status, o.total, o.address, o.created_at,
               u.phone AS user_phone
        FROM orders o
        LEFT JOIN users u ON u.user_name = o.user_name
        WHERE o.branch_id = ?
        ORDER BY o.created_at DESC";

$orders = [];
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $branchId);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
$stmt->close();

$rewardHistorySql = "SELECT lt.id, lt.user_id, lt.card_no, lt.created_at, lt.transaction_type,
                           lt.points_awarded, lt.redeemed_product_name,
                           u.user_name, u.phone AS user_phone,
                           CONCAT(COALESCE(u.firstname, ''), ' ', COALESCE(u.lastname, '')) AS customer_name
                    FROM loyalty_transactions lt
                    LEFT JOIN users u ON u.id = lt.user_id
                    WHERE lt.branch_id = ? AND lt.transaction_type = 'redemption'
                    ORDER BY lt.created_at DESC";

$rewardHistory = [];
$rewardStmt = $connect->prepare($rewardHistorySql);
$rewardStmt->bind_param('i', $branchId);
$rewardStmt->execute();
$rewardResult = $rewardStmt->get_result();
if ($rewardResult) {
    while ($row = $rewardResult->fetch_assoc()) {
        $rewardHistory[] = $row;
    }
}
$rewardStmt->close();

$historyEntries = [];
foreach ($orders as $order) {
    $historyEntries[] = ['kind' => 'order', 'data' => $order];
}
foreach ($rewardHistory as $reward) {
    $historyEntries[] = ['kind' => 'reward', 'data' => $reward];
}

usort($historyEntries, function ($a, $b) {
    $aTime = $a['kind'] === 'order' ? ($a['data']['created_at'] ?? '') : ($a['data']['created_at'] ?? '');
    $bTime = $b['kind'] === 'order' ? ($b['data']['created_at'] ?? '') : ($b['data']['created_at'] ?? '');
    return strcmp($bTime, $aTime);
});

// Orders placed by a customer through the app are "Online" (delivery /
// pick up); orders keyed in by staff for a walk-in customer are
// "Physical" (dine-in / take out).
$onlineTypes = ['delivery', 'pickup'];

$typeLabels = [
    'delivery' => 'Delivery',
    'pickup'   => 'Pick Up',
    'dine-in'  => 'Dine In',
    'takeout'  => 'Take Out',
];
$typeCodes = [
    'delivery' => 'DEL',
    'pickup'   => 'PU',
    'dine-in'  => 'DI',
    'takeout'  => 'TO',
];
$paymentLabels = [
    'cod'   => 'Cash',
    'qrph' => 'QR Ph',
];

function orderhis_format_group_label(string $dateStr): string {
    // Explicitly pin both dates to Manila so "Today" / "Yesterday" always
    // matches the Philippine calendar day, regardless of the server's
    // default PHP timezone.
    $manila = new DateTimeZone('Asia/Manila');
    $orderDate = new DateTime($dateStr, $manila);
    $today = new DateTime('today', $manila);
    $yesterday = (clone $today)->modify('-1 day');

    if ($orderDate->format('Y-m-d') === $today->format('Y-m-d')) {
        return 'Today';
    }
    if ($orderDate->format('Y-m-d') === $yesterday->format('Y-m-d')) {
        return 'Yesterday';
    }
    return $orderDate->format('F j, Y');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dash-css/pos-history.css">
    <link rel="stylesheet" href="dash-css/pos-controls.css">
    <link rel="stylesheet" href="dash-css/pos-responsive.css">
    <link rel="stylesheet" href="dash-css/order-notify.css">
    <link rel="icon" href="../img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Order History</title>
</head>
<body>
    <script>
        document.body.classList.toggle(
            "dark-theme",
            (localStorage.getItem("boycold_theme") || "dark") === "dark"
        );
    </script>

    <div class="app-shell">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">

            <div class="sidebar-brand">
                <span class="brand-mark" aria-hidden="true">
                     <img class="logo-light" src="../img/icon2.png" alt="LOGO">
                     <img class="logo-dark" src="../img/ChatGPT Image Jul 1, 2026, 12_58_44 PM 1.png" alt="LOGO">
                </span>
                <span class="brand-text">
                    <span class="brand-name">BoyCold Cafe</span>
                    <span class="brand-sub">Point of Sale</span>
                </span>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="pos-menu.php">
                            <span class="nav-icon1"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"  xmlns="http://www.w3.org/2000/svg">
                                <path d="M0.5 5C0.367392 5 0.240215 4.94732 0.146447 4.85355C0.0526785 4.75979 0 4.63261 0 4.5V0.5C0 0.367392 0.0526785 0.240215 0.146447 0.146447C0.240215 0.0526785 0.367392 0 0.5 0H4.5C4.63261 0 4.75979 0.0526785 4.85355 0.146447C4.94732 0.240215 5 0.367392 5 0.5V4.5C5 4.63261 4.94732 4.75979 4.85355 4.85355C4.75979 4.94732 4.63261 5 4.5 5H0.5ZM7.5 5C7.36739 5 7.24021 4.94732 7.14645 4.85355C7.05268 4.75979 7 4.63261 7 4.5V0.5C7 0.367392 7.05268 0.240215 7.14645 0.146447C7.24021 0.0526785 7.36739 0 7.5 0H11.5C11.6326 0 11.7598 0.0526785 11.8536 0.146447C11.9473 0.240215 12 0.367392 12 0.5V4.5C12 4.63261 11.9473 4.75979 11.8536 4.85355C11.7598 4.94732 11.6326 5 11.5 5H7.5ZM0.5 12C0.367392 12 0.240215 11.9473 0.146447 11.8536C0.0526785 11.7598 0 11.6326 0 11.5V7.5C0 7.36739 0.0526785 7.24021 0.146447 7.14645C0.240215 7.05268 0.367392 7 0.5 7H4.5C4.63261 7 4.75979 7.05268 4.85355 7.14645C4.94732 7.24021 5 7.36739 5 7.5V11.5C5 11.6326 4.94732 11.7598 4.85355 11.8536C4.75979 11.9473 4.63261 12 4.5 12H0.5ZM7.5 12C7.36739 12 7.24021 11.9473 7.14645 11.8536C7.05268 11.7598 7 11.6326 7 11.5V7.5C7 7.36739 7.05268 7.24021 7.14645 7.14645C7.24021 7.05268 7.36739 7 7.5 7H11.5C11.6326 7 11.7598 7.05268 11.8536 7.14645C11.9473 7.24021 12 7.36739 12 7.5V11.5C12 11.6326 11.9473 11.7598 11.8536 11.8536C11.7598 11.9473 11.6326 12 11.5 12H7.5Z" fill="currentColor"/>
                            </svg></span>
                            <span class="nav-label">Menu</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-status.php">
                            <span class="nav-icon"><svg width="19" height="22" viewBox="0 0 19 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14.8882 1H3.31469C2.03632 1 1 2.03632 1 3.31469V18.3602C1 19.6386 2.03632 20.6749 3.31469 20.6749H14.8882C16.1665 20.6749 17.2029 19.6386 17.2029 18.3602V3.31469C17.2029 2.03632 16.1665 1 14.8882 1Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M5.62939 6.78662H12.5735M5.62939 11.416H12.5735M5.62939 16.0454H10.2588" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg></span>
                            <span class="nav-label">Order Status</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-online.php">
                            <span class="nav-icon2"><i class="fa-solid fa-bag-shopping"></i></span>
                            <span class="nav-label">Online Orders</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-history.php" class="active">
                            <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.64456 19.2891C7.17984 19.2891 5.03232 18.4722 3.20199 16.8383C1.37167 15.2045 0.3222 13.1638 0.0535808 10.7162H2.2504C2.50044 12.5737 3.32666 14.1096 4.72905 15.3241C6.13144 16.5386 7.76994 17.1459 9.64456 17.1459C11.7342 17.1459 13.507 16.4183 14.963 14.963C16.419 13.5077 17.1466 11.7349 17.1459 9.64456C17.1452 7.55419 16.4175 5.78174 14.963 4.32719C13.5085 2.87265 11.7356 2.14466 9.64456 2.14324C8.4122 2.14324 7.26021 2.429 6.18859 3.00053C5.11698 3.57206 4.21503 4.35791 3.48276 5.35809H6.42971V7.50133H0V1.07162H2.14324V3.58992C3.05411 2.44686 4.16609 1.56278 5.47918 0.937666C6.79227 0.312555 8.18073 0 9.64456 0C10.9841 0 12.2389 0.254688 13.4092 0.764064C14.5794 1.27344 15.5974 1.9607 16.4633 2.82586C17.3291 3.69101 18.0168 4.70905 18.5261 5.87997C19.0355 7.05089 19.2898 8.30575 19.2891 9.64456C19.2884 10.9834 19.0341 12.2382 18.5261 13.4092C18.0182 14.5801 17.3306 15.5981 16.4633 16.4633C15.596 17.3284 14.5779 18.016 13.4092 18.5261C12.2404 19.0362 10.9855 19.2906 9.64456 19.2891ZM12.6451 14.1454L8.57294 10.0732V4.28647H10.7162V9.21591L14.1454 12.6451L12.6451 14.1454Z" fill="currentColor"/>
                            </svg></span>
                            <span class="nav-label">Order History</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                </ul>

                <div class="sidebar-divider"></div>

                <ul>
                    <li>
                        <a href="pos-shift.php">
                            <span class="nav-icon"><svg width="23" height="23" viewBox="0 0 23 23" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M6.94408 0C5.83907 0 4.77932 0.438964 3.99796 1.22033C3.2166 2.00169 2.77763 3.06144 2.77763 4.16645V9.16619C3.22165 8.93937 3.68681 8.75656 4.16645 8.62039V4.16645C4.16645 3.42978 4.45909 2.72327 4.98 2.20237C5.50091 1.68146 6.20741 1.38882 6.94408 1.38882H18.0546C18.7913 1.38882 19.4978 1.68146 20.0187 2.20237C20.5396 2.72327 20.8323 3.42978 20.8323 4.16645V15.277C20.8323 16.0137 20.5396 16.7202 20.0187 17.2411C19.4978 17.762 18.7913 18.0546 18.0546 18.0546H13.6007C13.4627 18.5398 13.2808 19.0027 13.0549 19.4434H18.0546C19.1596 19.4434 20.2194 19.0045 21.0007 18.2231C21.7821 17.4418 22.2211 16.382 22.2211 15.277V4.16645C22.2211 3.06144 21.7821 2.00169 21.0007 1.22033C20.2194 0.438964 19.1596 0 18.0546 0H6.94408ZM6.24968 22.2211C7.90719 22.2211 9.49682 21.5626 10.6689 20.3906C11.8409 19.2185 12.4994 17.6289 12.4994 15.9714C12.4994 14.3139 11.8409 12.7242 10.6689 11.5522C9.49682 10.3802 7.90719 9.72172 6.24968 9.72172C4.59216 9.72172 3.00253 10.3802 1.83049 11.5522C0.658446 12.7242 0 14.3139 0 15.9714C0 17.6289 0.658446 19.2185 1.83049 20.3906C3.00253 21.5626 4.59216 22.2211 6.24968 22.2211ZM6.24968 12.4994C6.43384 12.4994 6.61047 12.5725 6.7407 12.7027C6.87092 12.833 6.94408 13.0096 6.94408 13.1938V15.277H9.02731C9.21148 15.277 9.3881 15.3501 9.51833 15.4804C9.64856 15.6106 9.72172 15.7872 9.72172 15.9714C9.72172 16.1556 9.64856 16.3322 9.51833 16.4624C9.3881 16.5926 9.21148 16.6658 9.02731 16.6658H6.94408V18.749C6.94408 18.9332 6.87092 19.1098 6.7407 19.2401C6.61047 19.3703 6.43384 19.4434 6.24968 19.4434C6.06551 19.4434 5.88888 19.3703 5.75866 19.2401C5.62843 19.1098 5.55527 18.9332 5.55527 18.749V16.6658H3.47204C3.28787 16.6658 3.11125 16.5926 2.98102 16.4624C2.85079 16.3322 2.77763 16.1556 2.77763 15.9714C2.77763 15.7872 2.85079 15.6106 2.98102 15.4804C3.11125 15.3501 3.28787 15.277 3.47204 15.277H5.55527V13.1938C5.55527 13.0096 5.62843 12.833 5.75866 12.7027C5.88888 12.5725 6.06551 12.4994 6.24968 12.4994ZM13.8882 4.86086C13.8882 4.67669 13.815 4.50007 13.6848 4.36984C13.5546 4.23961 13.3779 4.16645 13.1938 4.16645C13.0096 4.16645 12.833 4.23961 12.7027 4.36984C12.5725 4.50007 12.4994 4.67669 12.4994 4.86086V9.02731C12.4994 9.21148 12.5725 9.3881 12.7027 9.51833C12.833 9.64856 13.0096 9.72172 13.1938 9.72172H15.9714C16.1556 9.72172 16.3322 9.64856 16.4624 9.51833C16.5926 9.3881 16.6658 9.21148 16.6658 9.02731C16.6658 8.84314 16.5926 8.66652 16.4624 8.53629C16.3322 8.40606 16.1556 8.3329 15.9714 8.3329H13.8882V4.86086Z" fill="currentColor"/>
                            </svg></span>
                            <span class="nav-label">Open / Close Shift</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-loyalty.php">
                            <span class="nav-icon"><svg width="22" height="18" viewBox="0 0 22 18" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M0.75 8.75C0.75 4.979 0.75 3.093 1.922 1.922C3.094 0.751 4.979 0.75 8.75 0.75H12.75C16.521 0.75 18.407 0.75 19.578 1.922C20.749 3.094 20.75 4.979 20.75 8.75C20.75 12.521 20.75 14.407 19.578 15.578C18.406 16.749 16.521 16.75 12.75 16.75H8.75C4.979 16.75 3.093 16.75 1.922 15.578C0.751 14.406 0.75 12.521 0.75 8.75Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                    <path d="M8.75 12.75H4.75M12.75 12.75H11.25M0.75 6.75H20.75" stroke="currentColor"
                                        stroke-width="1.5" stroke-linecap="round" />
                                </svg></span>
                            <span class="nav-label">Loyalty Card</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="logout-link">
                    <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                    <span class="nav-label">Log Out</span>
                </a>
            </div>
        </aside>

        <!-- MAIN PANEL -->
        <div class="main-panel">

            <div class="top-header">
                <div id="popupHost" style="display:none;"></div>

                <div class="shift-pill is-open" id="shiftPill">
                    <span class="shift-dot"></span>
                    <span id="shiftPillLabel">Shift Open</span>
                </div>

                <div class="header-divider"></div>

                <div class="theme-switch-wrap" title="Toggle Dark / Light Mode">
                    <button class="theme-toggle-btn" id="themeToggleBtn" type="button" role="switch" aria-label="Toggle Dark Mode" aria-checked="false">
                        <span class="theme-icon sun-icon"><i class="fa-solid fa-sun"></i></span>
                        <span class="theme-icon moon-icon"><i class="fa-solid fa-moon"></i></span>
                        <span class="theme-thumb"></span>
                    </button>
                </div>

                <div class="header-divider"></div>

                <button class="sound-btn" id="soundToggleBtn" type="button" aria-label="Toggle Sound" title="Sound On (Click to Mute)">
                    <i class="fa-solid fa-volume-high" id="soundIcon"></i>
                </button>

                <div class="header-divider"></div>

                <button class="profile-btn">
                    <div class="profile-avatar">
                        <svg class="logo-light" width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8.75762 25.5987C10.0301 24.6256 11.4522 23.8587 13.0241 23.2978C14.5959 22.7369 16.2426 22.456 17.9642 22.455C19.6858 22.454 21.3325 22.7349 22.9043 23.2978C24.4762 23.8607 25.8983 24.6276 27.1708 25.5987C28.044 24.5757 28.7242 23.4156 29.2112 22.1181C29.6982 20.8207 29.9412 19.436 29.9403 17.964C29.9403 14.6456 28.7741 11.8197 26.4417 9.48641C24.1094 7.15308 21.2836 5.98691 17.9642 5.98791C14.6448 5.98891 11.819 7.15557 9.48666 9.48791C7.15432 11.8202 5.98815 14.6456 5.98815 17.964C5.98815 19.436 6.23167 20.8207 6.71869 22.1181C7.20572 23.4156 7.88536 24.5757 8.75762 25.5987ZM14.2411 17.9445C13.2302 16.9355 12.7247 15.6945 12.7247 14.2214C12.7247 12.7484 13.2302 11.5069 14.2411 10.4969C15.2521 9.48691 16.4931 8.98192 17.9642 8.98192C19.4353 8.98192 20.6768 9.48741 21.6888 10.4984C22.7007 11.5094 23.2057 12.7504 23.2037 14.2214C23.2017 15.6925 22.6967 16.934 21.6888 17.946C20.6808 18.958 19.4393 19.463 17.9642 19.461C16.4892 19.459 15.2476 18.954 14.2397 17.946M17.9642 32.934C15.8933 32.934 13.9472 32.5408 12.1259 31.7544C10.3045 30.9679 8.72019 29.9016 7.37289 28.5553C6.02558 27.209 4.95921 25.6246 4.17378 23.8023C3.38835 21.9799 2.99514 20.0338 2.99414 17.964C2.99314 15.8941 3.38636 13.948 4.17378 12.1256C4.96121 10.3033 6.02758 8.71895 7.37289 7.37264C8.71819 6.02633 10.3025 4.95996 12.1259 4.17354C13.9492 3.38711 15.8953 2.9939 17.9642 2.9939C20.0331 2.9939 21.9792 3.38711 23.8025 4.17354C25.6259 4.95996 27.2102 6.02633 28.5555 7.37264C29.9008 8.71895 30.9677 10.3033 31.7561 12.1256C32.5445 13.948 32.9373 15.8941 32.9343 17.964C32.9313 20.0338 32.5381 21.9799 31.7546 23.8023C30.9712 25.6246 29.9048 27.209 28.5555 28.5553C27.2062 29.9016 25.6219 30.9684 23.8025 31.7559C21.9832 32.5433 20.0371 32.936 17.9642 32.934Z" fill="black"/>
                        </svg>
                        <svg class="logo-dark" width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8.75762 25.5988C10.0301 24.6257 11.4522 23.8588 13.0241 23.2979C14.5959 22.737 16.2426 22.4561 17.9642 22.4551C19.6858 22.4541 21.3325 22.735 22.9043 23.2979C24.4762 23.8608 25.8983 24.6277 27.1708 25.5988C28.044 24.5758 28.7242 23.4157 29.2112 22.1183C29.6982 20.8209 29.9412 19.4361 29.9403 17.9641C29.9403 14.6457 28.7741 11.8199 26.4417 9.48653C24.1094 7.15319 21.2836 5.98702 17.9642 5.98802C14.6448 5.98902 11.819 7.15569 9.48666 9.48802C7.15432 11.8204 5.98815 14.6457 5.98815 17.9641C5.98815 19.4361 6.23167 20.8209 6.71869 22.1183C7.20572 23.4157 7.88536 24.5758 8.75762 25.5988ZM14.2411 17.9446C13.2302 16.9356 12.7247 15.6946 12.7247 14.2216C12.7247 12.7485 13.2302 11.507 14.2411 10.497C15.2521 9.48702 16.4931 8.98203 17.9642 8.98203C19.4353 8.98203 20.6768 9.48752 21.6888 10.4985C22.7007 11.5095 23.2057 12.7505 23.2037 14.2216C23.2017 15.6926 22.6967 16.9341 21.6888 17.9461C20.6808 18.9581 19.4393 19.4631 17.9642 19.4611C16.4892 19.4591 15.2476 18.9541 14.2397 17.9461M17.9642 32.9341C15.8933 32.9341 13.9472 32.5409 12.1259 31.7545C10.3045 30.9681 8.72019 29.9017 7.37289 28.5554C6.02558 27.2091 4.95921 25.6247 4.17378 23.8024C3.38835 21.98 2.99514 20.0339 2.99414 17.9641C2.99314 15.8942 3.38636 13.9481 4.17378 12.1257C4.96121 10.3034 6.02758 8.71906 7.37289 7.37275C8.71819 6.02645 10.3025 4.96008 12.1259 4.17365C13.9492 3.38722 15.8953 2.99401 17.9642 2.99401C20.0331 2.99401 21.9792 3.38722 23.8025 4.17365C25.6259 4.96008 27.2102 6.02645 28.5555 7.37275C29.9008 8.71906 30.9677 10.3034 31.7561 12.1257C32.5445 13.9481 32.9373 15.8942 32.9343 17.9641C32.9313 20.0339 32.5381 21.98 31.7546 23.8024C30.9712 25.6247 29.9048 27.2091 28.5555 28.5554C27.2062 29.9017 25.6219 30.9686 23.8025 31.756C21.9832 32.5434 20.0371 32.9361 17.9642 32.9341Z" fill="white"/>
                        </svg>
                    </div>
                    <span class="profile-name"><?= htmlspecialchars($branchName) ?></span>
                </button>
            </div>

            <!-- ORDER HISTORY PAGE -->
            <div class="orderhis-page">

                <div class="page-title">
                    <h2>Order History</h2>
                    <p>View and manage all orders.</p>
                </div>

                <div class="orderhis-controls">

                    <div class="dropdown-wrap">
                        <button class="date-dropdown" id="dateDropdownBtn" type="button">
                            <i class="fa-regular fa-calendar"></i>
                            <span id="dateDropdownLabel">Today</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="date-dropdown-panel" id="dateDropdownPanel">
                            <button type="button" class="date-option active" data-range="today">Today</button>
                            <button type="button" class="date-option" data-range="weekly">Weekly</button>
                            <button type="button" class="date-option" data-range="monthly">Monthly</button>
                            <button type="button" class="date-option" data-range="annual">Annual</button>
                        </div>
                    </div>

                    <div class="dropdown-wrap">
                        <button class="filter-dropdown" id="filterDropdownBtn" type="button">
                            <i class="fa-solid fa-filter"></i>
                            <span>Filter</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="filter-dropdown-panel" id="filterDropdownPanel">

                            <div class="filter-field-row">
                                <div class="filter-field">
                                    <label>Payment Method</label>
                                    <select id="filterPayment">
                                        <option value="">All</option>
                                        <option value="cash">Cash</option>
                                        <option value="qrph">QR Ph</option>
                                        <option value="reward">Reward</option>
                                    </select>
                                </div>
                                <div class="filter-field">
                                    <label>Fulfillment Type</label>
                                    <select id="filterType">
                                        <option value="">All</option>
                                        <option value="delivery">Delivery</option>
                                        <option value="pickup">Pick Up</option>
                                        <option value="dinein">Dine In</option>
                                        <option value="takeout">Take Out</option>
                                        <option value="reward">Reward Redeemed</option>
                                    </select>
                                </div>
                            </div>

                            <div class="filter-field">
                                <label>Amount Range</label>
                                <div class="amount-range-row">
                                    <input type="number" id="filterMinAmount" placeholder="Min Amount" min="0">
                                    <span class="amount-dash">—</span>
                                    <input type="number" id="filterMaxAmount" placeholder="Max Amount" min="0">
                                </div>
                            </div>

                            <div class="filter-actions">
                                <button type="button" id="filterResetBtn" class="filter-reset-btn">Reset</button>
                                <button type="button" id="filterApplyBtn" class="filter-apply-btn">Apply</button>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="orderhis-table-wrap">
                    <div class="orderhis-table">

                        <div class="table-header-row">
                            <span>Time</span>
                            <span>Order No.</span>
                            <span>Customer</span>
                            <span>Type</span>
                            <span>Amount</span>
                            <span>Status</span>
                        </div>

                        <?php if (empty($historyEntries)): ?>
                        <div class="table-empty" id="tableEmpty">No orders yet.</div>
                        <?php else: ?>
                        <?php $lastGroupLabel = null; ?>
                        <?php foreach ($historyEntries as $entry): ?>
                            <?php
                                $kind = $entry['kind'];
                                $createdAt = null;
                                $rowHtml = null;

                                if ($kind === 'reward') {
                                    $reward = $entry['data'];
                                    $customerName = trim((string) ($reward['customer_name'] ?? ''));
                                    $userName = trim((string) ($reward['user_name'] ?? ''));
                                    $displayName = $userName !== '' ? $userName : ($customerName !== '' ? $customerName : 'Customer');
                                    $displayPhone = trim((string) ($reward['user_phone'] ?? '')) ?: '—';
                                    $createdAt = new DateTime($reward['created_at'], new DateTimeZone('Asia/Manila'));
                                    $rewardName = trim((string) ($reward['redeemed_product_name'] ?? 'Free Drink')) ?: 'Free Drink';

                                    $groupLabel = orderhis_format_group_label($reward['created_at']);
                                    $showGroupLabel = ($groupLabel !== $lastGroupLabel);
                                    $lastGroupLabel = $groupLabel;

                                    $value = 'Free';
                            ?>
                            <?php if ($showGroupLabel): ?>
                        <div class="table-group-label"><?= htmlspecialchars($groupLabel) ?></div>
                            <?php endif; ?>
                        <div class="table-row"
                            data-date="<?= $createdAt->format('Y-m-d') ?>"
                            data-payment="reward"
                            data-type="reward"
                            data-amount="0">
                            <span class="col-time"><?= $createdAt->format('g:i a') ?></span>
                            <span class="col-orderno">
                                <p class="order-no">REWARD-<?= $createdAt->format('Y') ?>-<?= (int) ($reward['id'] ?? 0) ?></p>
                                <p class="order-source"><span class="source-dot reward"></span>Loyalty</p>
                            </span>
                            <span class="col-customer">
                                <p class="customer-name"><?= htmlspecialchars($displayName) ?></p>
                                <p class="customer-phone"><?= htmlspecialchars($displayPhone) ?></p>
                            </span>
                            <span class="col-type">Reward Redeemed</span>
                            <span class="col-amount">
                                <p class="amount-value"><?= htmlspecialchars($value) ?></p>
                                <p class="amount-method"><?= htmlspecialchars($rewardName) ?></p>
                            </span>
                            <span class="col-status">Redeemed</span>
                        </div>
                            <?php } else { ?>
                            <?php
                                $order = $entry['data'];
                                $type        = $order['order_type'] ?: 'delivery';
                                $typeLabel   = $typeLabels[$type] ?? ucfirst($type);
                                $typeCode    = $typeCodes[$type] ?? 'GEN';
                                $isOnline    = in_array($type, $onlineTypes, true);
                                $sourceLabel = $isOnline ? 'Online' : 'Physical';
                                $sourceDot   = $isOnline ? 'online' : 'physical';
                                $prefix      = $isOnline ? 'ONL' : 'POS';

                                $createdAt   = new DateTime($order['created_at'], new DateTimeZone('Asia/Manila'));
                                $orderNo     = sprintf('%s-%s-%s-%05d', $prefix, $typeCode, $createdAt->format('Y'), (int)$order['id']);

                                $payment      = $order['payment_method'] ?: 'cod';
                                $paymentLabel = boycold_payment_label($payment, (string) ($order['payment_status'] ?? 'unpaid'));
                                $paymentAttr  = $payment === 'qrph' ? 'qrph' : 'cash';
                                $typeAttr     = str_replace('-', '', $type);
                                $statusLabel  = boycold_order_was_voided($order) ? 'Void Order' : ucfirst((string) $order['status']);

                                $groupLabel = orderhis_format_group_label($order['created_at']);
                                $showGroupLabel = ($groupLabel !== $lastGroupLabel);
                                $lastGroupLabel = $groupLabel;
                            ?>
                            <?php if ($showGroupLabel): ?>
                        <div class="table-group-label"><?= htmlspecialchars($groupLabel) ?></div>
                            <?php endif; ?>
                        <div class="table-row history-order-row"
                            data-order-id="<?= (int) $order['id'] ?>"
                            data-order-number="<?= htmlspecialchars($orderNo, ENT_QUOTES, 'UTF-8') ?>"
                            data-date="<?= $createdAt->format('Y-m-d') ?>"
                            data-payment="<?= $paymentAttr ?>"
                            data-type="<?= $typeAttr ?>"
                            data-amount="<?= number_format((float)$order['total'], 2, '.', '') ?>"
                            role="button"
                            tabindex="0"
                            aria-label="View receipt for <?= htmlspecialchars($orderNo, ENT_QUOTES, 'UTF-8') ?>">
                            <span class="col-time"><?= $createdAt->format('g:i a') ?></span>
                            <span class="col-orderno">
                                <p class="order-no"><?= htmlspecialchars($orderNo) ?></p>
                                <p class="order-source"><span class="source-dot <?= $sourceDot ?>"></span><?= $sourceLabel ?></p>
                            </span>
                            <span class="col-customer">
                                <p class="customer-name"><?= htmlspecialchars($order['user_name']) ?></p>
                                <p class="customer-phone"><?= htmlspecialchars($order['user_phone'] ?: '—') ?></p>
                            </span>
                            <span class="col-type"><?= htmlspecialchars($typeLabel) ?></span>
                            <span class="col-amount">
                                <p class="amount-value">₱<?= number_format((float)$order['total'], 2) ?></p>
                                <p class="amount-method"><?= htmlspecialchars($paymentLabel) ?></p>
                            </span>
                            <span class="col-status"><?= htmlspecialchars($statusLabel) ?></span>
                        </div>
                            <?php } ?>
                        <?php endforeach; ?>
                        <div class="table-empty" id="tableEmpty" style="display:none;">No orders match your filters.</div>
                        <?php endif; ?>

                        
                    </div>
                </div>

            </div>
            <!-- AUTHORIZATION PIN MODAL -->
            <div class="pin-modal" id="pinModal">
                <div class="pin-modal-box">
                    <h2>Enter Authorization PIN</h2>
                    <div class="pin-inputs" id="pinInputs">
                        <input type="password" class="pin-box" maxlength="1" inputmode="numeric" autocomplete="off">
                        <input type="password" class="pin-box" maxlength="1" inputmode="numeric" autocomplete="off">
                        <input type="password" class="pin-box" maxlength="1" inputmode="numeric" autocomplete="off">
                        <input type="password" class="pin-box" maxlength="1" inputmode="numeric" autocomplete="off">
                    </div>
                    <p class="pin-error" id="pinError">*Incorrect PIN</p>
                    <div class="pin-actions">
                        <button type="button" class="pin-cancel-btn" id="pinCancelBtn">Cancel</button>
                        <button type="button" class="pin-confirm-btn" id="pinConfirmBtn">Confirm</button>
                    </div>
                </div>
            </div>
            <!-- VOID ORDER ADJUSTMENT MODAL -->
            <div class="void-modal" id="voidModal">
                <div class="void-modal-box">
                    <div class="void-modal-header">Void Order - Adjustment Mode</div>

                    <div class="void-modal-body">
                        <div class="void-warning">The original paid order will be marked as Void Order. Its inventory will be restored, then your edited items will be saved as a new order.
                        </div>

                        <div class="void-info-row"><span>Order No:</span><span id="voidOrderNo">—</span></div>
                        <div class="void-info-row"><span>Payment:</span><span id="voidPayment">—</span></div>
                        <div class="void-info-row"><span>Status:</span><span id="voidStatus">—</span></div>

                        <div class="void-items-header">
                            <span>Items:</span>
                            <div class="void-items-actions">
                                <button type="button" class="void-add-btn" id="addItemBtn">Item <i
                                        class="fa-solid fa-plus"></i></button>
                                <button type="button" class="void-add-btn" id="addAddonBtn">Add-on <i
                                        class="fa-solid fa-plus"></i></button>
                            </div>
                        </div>

                        <div class="void-items-list" id="voidItemsList"></div>

                        <div class="void-total-row">
                            <span>Total:</span> <span id="voidTotal">₱0.00</span>
                        </div>
                    </div>

                    <div class="void-payadjust" id="voidPayAdjust" style="display:none;">
                        <div class="void-payadjust-label" id="voidPayLabel">Pay In</div>
                        <div class="void-payadjust-value" id="voidPayValue">Collect ₱0.00</div>
                    </div>

                    <div class="void-modal-actions">
                        <button type="button" class="void-cancel-btn" id="voidCancelBtn">Cancel</button>
                        <button type="button" class="void-confirm-btn" id="voidConfirmBtn">Confirm Void</button>
                    </div>
                </div>
            </div>
            <div class="modal-overlay" id="orderModal" hidden>
                <div class="receipt-modal-container">
                    <button type="button" class="receipt-floating-close" id="closeModalBtn" aria-label="Close modal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <div class="receipt-sawtooth-top"></div>
                    <div class="receipt-paper" id="printableReceipt">
                        <div class="receipt-header">
                            <span class="receipt-logo-text">B<span class="special-letter">o</span><span
                                    class="special-letter-2">y</span>C<span class="special-letter">o</span>LD
                                CAFE</span>
                            <p class="receipt-store-sub">Specialty Coffee & Beverages</p>
                            <p class="receipt-store-info" id="modalBranch"><?= htmlspecialchars($branchName) ?></p>
                            <p class="receipt-store-info">Tel: +63 912 345 6789</p>
                            <p class="receipt-store-info">VAT Reg TIN: 000-123-456-000</p>
                        </div>
                        <div class="receipt-divider-double">================================</div>
                        <div class="receipt-meta">
                            <div class="receipt-row">
                                <span class="r-label">RECEIPT NO:</span>
                                <span class="r-val bold" id="modalOrderId">#ORDER-0000</span>
                            </div>
                            <div class="receipt-row">
                                <span class="r-label">DATE & TIME:</span>
                                <span class="r-val" id="modalOrderTime">—</span>
                            </div>
                            <div class="receipt-row">
                                <span class="r-label">CASHIER:</span>
                                <span class="r-val" id="modalCashier"><?= htmlspecialchars($employeeName) ?></span>
                            </div>
                            <div class="receipt-row">
                                <span class="r-label">CUSTOMER:</span>
                                <span class="r-val bold" id="modalCustomerName">N/A</span>
                            </div>
                            <div class="receipt-row">
                                <span class="r-label">ORDER TYPE:</span>
                                <span class="r-val bold" id="modalOrderType">ORDER ADJUSTMENT</span>
                            </div>
                            <div class="receipt-row">
                                <span class="r-label">STATUS:</span>
                                <span class="r-val bold" id="modalOrderStatus">COMPLETED</span>
                            </div>
                        </div>
                        <div class="receipt-divider-dashed">--------------------------------</div>
                        <div class="receipt-items-header">
                            <span class="r-col-qty">QTY</span>
                            <span class="r-col-desc">ITEM</span>
                            <span class="r-col-amt">AMOUNT</span>
                        </div>
                        <div class="receipt-divider-dashed">--------------------------------</div>
                        <div class="receipt-items-list" id="modalItemsList"></div>
                        <div class="receipt-divider-dashed">--------------------------------</div>
                        <div class="receipt-totals">
                            <div class="receipt-row">
                                <span>Subtotal</span>
                                <span id="modalSubtotal">₱0.00</span>
                            </div>
                            <div class="receipt-row" id="modalDeliveryFeeRow" style="display:none;">
                                <span>Delivery Fee</span>
                                <span id="modalDeliveryFee">₱0.00</span>
                            </div>
                            <div class="receipt-row" id="modalTaxRow" style="display:none;">
                                <span>Other Charges</span>
                                <span id="modalTax">₱0.00</span>
                            </div>
                            <div class="receipt-row">
                                <span>Discount</span>
                                <span>₱0.00</span>
                            </div>
                            <div class="receipt-row">
                                <span>VATable Sales (12%)</span>
                                <span id="modalVatSales">₱0.00</span>
                            </div>
                            <div class="receipt-row">
                                <span>VAT Amount</span>
                                <span id="modalVatAmt">₱0.00</span>
                            </div>
                            <div class="receipt-divider-double">================================</div>
                            <div class="receipt-row receipt-grand-total">
                                <span>TOTAL AMOUNT:</span>
                                <span id="modalTotal">₱0.00</span>
                            </div>
                            <div class="receipt-divider-double">================================</div>
                            <div class="receipt-row">
                                <span>Payment Method:</span>
                                <span class="bold uppercase" id="modalPayment">Cash</span>
                            </div>
                            <div class="receipt-row" id="modalTenderedRow">
                                <span id="modalTenderedLabel">Amount Tendered:</span>
                                <span id="modalTendered">₱0.00</span>
                            </div>
                            <div class="receipt-row" id="modalChangeRow">
                                <span id="modalChangeLabel">Change:</span>
                                <span id="modalChange">₱0.00</span>
                            </div>
                            <div class="receipt-row" id="modalRefRow" style="display:none;">
                                <span>Ref / Trans No:</span>
                                <span id="modalRefNo">Not available</span>
                            </div>
                        </div>
                        <div class="receipt-divider-dashed">--------------------------------</div>
                        <div class="receipt-footer">
                            <div class="receipt-barcode-wrap">
                                <span class="barcode-num" id="modalBarcodeNum">* ORDER-1234 *</span>
                            </div>
                            <p class="receipt-thankyou" id="modalReceiptMessage">*** THANK YOU FOR YOUR PURCHASE! ***</p>
                            <p class="receipt-tagline">Brewed with passion, served with love.</p>
                            <p class="receipt-social">Follow us: @boycoldcafe</p>
                            <p class="receipt-pos-system">BoyCold POS v1.0 - Official Receipt</p>
                        </div>
                    </div>
                    <div class="receipt-sawtooth-bottom"></div>
                    <div class="receipt-actions">
                        <button type="button" class="receipt-btn-print" id="modalPrintBtn">
                            <i class="fa-solid fa-print"></i> Print Receipt
                        </button>
                        <button type="button" class="receipt-btn-close" id="modalDoneBtn">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const notifBtn = document.getElementById("notifBtn");
        const notifDropdown = document.getElementById("notifDropdown");
        const markAllRead = document.getElementById("markAllRead");
        const notifBadge = document.getElementById("notifBadge");
        const notifList = document.getElementById("notifList");

        // order-notify.js creates this UI on pages that have notifications.
        // On this page it may not exist yet, so it must not stop the scripts
        // below (including the history date/filter dropdowns).
        if (notifBtn && notifDropdown && notifBtn.dataset.inventoryAlert !== "true") {
            notifBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                notifDropdown.classList.toggle("open");
            });

            document.addEventListener("click", (e) => {
                if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                    notifDropdown.classList.remove("open");
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
        // Date and advanced filters share one function so both controls always
        // show the same set of history rows.
        (() => {
            const dateDropdownBtn = document.getElementById("dateDropdownBtn");
            const dateDropdownPanel = document.getElementById("dateDropdownPanel");
            const dateDropdownLabel = document.getElementById("dateDropdownLabel");
            const filterDropdownBtn = document.getElementById("filterDropdownBtn");
            const filterDropdownPanel = document.getElementById("filterDropdownPanel");
            const filterPayment = document.getElementById("filterPayment");
            const filterType = document.getElementById("filterType");
            const filterMinAmount = document.getElementById("filterMinAmount");
            const filterMaxAmount = document.getElementById("filterMaxAmount");
            const filterApplyBtn = document.getElementById("filterApplyBtn");
            const filterResetBtn = document.getElementById("filterResetBtn");
            const table = document.querySelector(".orderhis-table");
            const tableEmpty = document.getElementById("tableEmpty");

            if (!dateDropdownBtn || !dateDropdownPanel || !dateDropdownLabel ||
                !filterDropdownBtn || !filterDropdownPanel || !filterPayment ||
                !filterType || !filterMinAmount || !filterMaxAmount ||
                !filterApplyBtn || !filterResetBtn || !table || !tableEmpty) {
                return;
            }

            let currentRange = "today";

            function parseHistoryDate(value) {
                const matches = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || ""));
                if (!matches) return null;

                return new Date(
                    Number(matches[1]),
                    Number(matches[2]) - 1,
                    Number(matches[3])
                );
            }

            function isWithinRange(dateValue, range) {
                const rowDate = parseHistoryDate(dateValue);
                if (!rowDate || Number.isNaN(rowDate.getTime())) return false;

                const now = new Date();
                const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                const diffDays = Math.round((today - rowDate) / 86400000);
                if (diffDays < 0) return false;

                if (range === "today") return diffDays === 0;
                if (range === "weekly") return diffDays <= 7;
                if (range === "monthly") return diffDays <= 30;
                if (range === "annual") return diffDays <= 365;
                return true;
            }

            function updateGroupLabels() {
                const children = Array.from(table.children);

                children.forEach((child, index) => {
                    if (!child.classList.contains("table-group-label")) return;

                    let hasVisibleRow = false;
                    for (let nextIndex = index + 1; nextIndex < children.length; nextIndex++) {
                        const nextChild = children[nextIndex];
                        if (nextChild.classList.contains("table-group-label")) break;
                        if (nextChild.classList.contains("table-row") && nextChild.style.display !== "none") {
                            hasVisibleRow = true;
                            break;
                        }
                    }
                    child.style.display = hasVisibleRow ? "" : "none";
                });
            }

            function readAmount(input, fallback) {
                if (input.value.trim() === "") return fallback;
                const amount = Number(input.value);
                return Number.isFinite(amount) ? amount : fallback;
            }

            function applyFilters() {
                const payment = filterPayment.value;
                const type = filterType.value;
                const min = readAmount(filterMinAmount, 0);
                const max = readAmount(filterMaxAmount, Infinity);
                let visibleCount = 0;

                table.querySelectorAll(".table-row").forEach((row) => {
                    const amount = Number(row.dataset.amount || 0);
                    const matches =
                        isWithinRange(row.dataset.date, currentRange) &&
                        (!payment || row.dataset.payment === payment) &&
                        (!type || row.dataset.type === type) &&
                        amount >= min && amount <= max;

                    row.style.display = matches ? "" : "none";
                    if (matches) visibleCount++;
                });

                updateGroupLabels();
                tableEmpty.style.display = visibleCount === 0 ? "block" : "none";
            }

            dateDropdownBtn.addEventListener("click", (event) => {
                event.stopPropagation();
                filterDropdownPanel.classList.remove("open");
                dateDropdownPanel.classList.toggle("open");
            });

            dateDropdownPanel.querySelectorAll(".date-option").forEach((option) => {
                option.addEventListener("click", () => {
                    dateDropdownPanel.querySelectorAll(".date-option").forEach((item) => {
                        item.classList.toggle("active", item === option);
                    });
                    currentRange = option.dataset.range || "today";
                    dateDropdownLabel.textContent = option.textContent.trim();
                    dateDropdownPanel.classList.remove("open");
                    applyFilters();
                });
            });

            filterDropdownBtn.addEventListener("click", (event) => {
                event.stopPropagation();
                dateDropdownPanel.classList.remove("open");
                filterDropdownPanel.classList.toggle("open");
            });

            document.addEventListener("click", (event) => {
                if (!dateDropdownPanel.contains(event.target) && !dateDropdownBtn.contains(event.target)) {
                    dateDropdownPanel.classList.remove("open");
                }
                if (!filterDropdownPanel.contains(event.target) && !filterDropdownBtn.contains(event.target)) {
                    filterDropdownPanel.classList.remove("open");
                }
            });

            filterApplyBtn.addEventListener("click", () => {
                applyFilters();
                filterDropdownPanel.classList.remove("open");
            });

            filterResetBtn.addEventListener("click", () => {
                filterPayment.value = "";
                filterType.value = "";
                filterMinAmount.value = "";
                filterMaxAmount.value = "";
                applyFilters();
            });

            // The periodic history refresh must not wipe a cashier's current
            // date range or filters while they are reviewing orders.
            window.isHistoryFilterActive = function () {
                return dateDropdownPanel.classList.contains("open") ||
                    filterDropdownPanel.classList.contains("open") ||
                    currentRange !== "today" ||
                    filterPayment.value !== "" ||
                    filterType.value !== "" ||
                    filterMinAmount.value.trim() !== "" ||
                    filterMaxAmount.value.trim() !== "";
            };

            // The label starts as Today, so apply that selection immediately.
            applyFilters();
        })();
    </script>

    <script>
        const soundToggleBtn = document.getElementById("soundToggleBtn");
        const soundIcon = document.getElementById("soundIcon");

        function playSoundChime() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                [{ freq: 659.25, start: 0 }, { freq: 880, start: 0.1 }].forEach(({ freq, start }) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = "sine";
                    osc.frequency.value = freq;
                    gain.gain.setValueAtTime(0.18, ctx.currentTime + start);
                    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + start + 0.14);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + start);
                    osc.stop(ctx.currentTime + start + 0.15);
                });
            } catch (e) {}
        }

        function updateSoundUI(isMuted) {
            if (!soundToggleBtn || !soundIcon) return;
            if (isMuted) {
                soundIcon.className = "fa-solid fa-volume-xmark";
                soundToggleBtn.classList.add("muted");
                soundToggleBtn.title = "Sound Muted (Click to Unmute)";
            } else {
                soundIcon.className = "fa-solid fa-volume-high";
                soundToggleBtn.classList.remove("muted");
                soundToggleBtn.title = "Sound On (Click to Mute)";
            }
        }

        let isMuted = localStorage.getItem("boycold_pos_muted") === "true";
        updateSoundUI(isMuted);

        if (soundToggleBtn) {
            soundToggleBtn.addEventListener("click", () => {
                isMuted = !isMuted;
                localStorage.setItem("boycold_pos_muted", isMuted);
                window.dispatchEvent(new CustomEvent('boycold:mute-toggle', { detail: { muted: isMuted } }));
                updateSoundUI(isMuted);
                if (!isMuted) playSoundChime();
            });
        }

        const themeToggleBtn = document.getElementById("themeToggleBtn");

        function updateThemeUI(isDark) {
            if (!themeToggleBtn) return;
            if (isDark) {
                document.body.classList.add("dark-theme");
                themeToggleBtn.setAttribute("aria-checked", "true");
            } else {
                document.body.classList.remove("dark-theme");
                themeToggleBtn.setAttribute("aria-checked", "false");
            }
        }

        updateThemeUI(document.body.classList.contains("dark-theme"));

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener("click", () => {
                const isDark = !document.body.classList.contains("dark-theme");
                localStorage.setItem("boycold_theme", isDark ? "dark" : "light");
                updateThemeUI(isDark);
            });
        }
    </script>

    <script>
        // ══════════════════════════════
        // ROW ACTIONS (⋮) + VOID ORDER
        // ══════════════════════════════
        (function () {
            const table = document.querySelector(".orderhis-table");
            if (!table) return;

            const VOID_ORDER_API = "../pos-void-order-api.php";

            function escapeHtml(value) {
                return String(value).replace(/[&<>"']/g, ch => (
                    { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch]
                ));
            }

            let currentVoidRow = null;    // row waiting for the PIN
            let currentVoidRowEl = null;  // row being voided / adjusted
            let currentVoidAuthorizationPin = "";

            // ── Row dots (⋮) menu ──
            function initRowActions() {
                table.querySelectorAll(".table-row").forEach(row => {
                    if (row.querySelector(".col-actions")) return; // already injected

                    const isPhysical = !!row.querySelector(".source-dot.physical");
                    const status = (row.querySelector(".col-status")?.textContent || "").trim().toLowerCase();
                    const actionsSpan = document.createElement("span");
                    actionsSpan.className = "col-actions";

                    if (isPhysical && status !== "cancelled" && status !== "voided" && status !== "void order") {
                        actionsSpan.innerHTML = `
                <button type="button" class="row-dots-btn" aria-label="More actions">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <div class="row-actions-menu">
                    <button type="button" class="void-order-btn">Void</button>
                </div>
            `;
                    }
                    row.appendChild(actionsSpan);
                });
            }

            function closeRowMenus(except) {
                document.querySelectorAll(".row-actions-menu.open").forEach(m => {
                    if (m !== except) m.classList.remove("open");
                });
            }

            // The table wrapper clips overflow, so the menu is fixed-positioned next to
            // its button (and flips upward near the bottom of the screen).
            function positionRowMenu(btn, menu) {
                const r = btn.getBoundingClientRect();
                const menuHeight = menu.offsetHeight;
                const openUp = r.bottom + menuHeight + 8 > window.innerHeight && r.top - menuHeight - 8 > 0;
                menu.style.top = (openUp ? r.top - menuHeight - 4 : r.bottom + 4) + "px";
                menu.style.right = Math.max(8, window.innerWidth - r.right) + "px";
                menu.style.left = "auto";
            }

            // One delegated listener, so rows added later (e.g. the adjusted order) work too.
            table.addEventListener("click", (e) => {
                const dotsBtn = e.target.closest(".row-dots-btn");
                if (dotsBtn) {
                    const menu = dotsBtn.nextElementSibling;
                    const willOpen = !menu.classList.contains("open");
                    closeRowMenus();
                    if (willOpen) {
                        menu.classList.add("open");
                        positionRowMenu(dotsBtn, menu);
                    }
                    return;
                }

                const voidBtn = e.target.closest(".void-order-btn");
                if (voidBtn) {
                    closeRowMenus();
                    openPinModal(voidBtn.closest(".table-row"));
                    return;
                }

                // A normal history row opens the same thermal receipt-style
                // order summary that administrators see from Orders > View.
                const orderRow = e.target.closest(".history-order-row[data-order-id]");
                if (orderRow && !e.target.closest("button, a, input, select, textarea, .col-actions")) {
                    closeRowMenus();
                    openHistoryReceipt(orderRow);
                }
            });

            table.addEventListener("keydown", (event) => {
                if (event.key !== "Enter" && event.key !== " ") return;

                const orderRow = event.target.closest(".history-order-row[data-order-id]");
                if (!orderRow || event.target.closest("button, a, input, select, textarea, .col-actions")) return;

                event.preventDefault();
                closeRowMenus();
                openHistoryReceipt(orderRow);
            });

            document.addEventListener("click", (e) => {
                if (!e.target.closest(".col-actions")) closeRowMenus();
            });
            window.addEventListener("scroll", () => closeRowMenus(), true);
            window.addEventListener("resize", () => closeRowMenus());

            // ── Authorization PIN modal ──
            const pinModal = document.getElementById("pinModal");
            const pinBoxes = Array.from(document.querySelectorAll(".pin-box"));
            const pinError = document.getElementById("pinError");
            const pinCancelBtn = document.getElementById("pinCancelBtn");
            const pinConfirmBtn = document.getElementById("pinConfirmBtn");

            function openPinModal(row) {
                currentVoidRow = row;
                clearPinBoxes();
                hidePinError();
                pinModal.classList.add("show");
                pinBoxes[0].focus();
            }

            function closePinModal() {
                pinModal.classList.remove("show");
                currentVoidRow = null;
            }

            function clearPinBoxes() {
                pinBoxes.forEach(box => {
                    box.value = "";
                    box.classList.remove("error");
                });
            }

            function showPinError(message = "*Incorrect authorization PIN") {
                pinError.textContent = message;
                pinError.classList.add("show");
                pinBoxes.forEach(box => box.classList.add("error"));
            }

            function hidePinError() {
                pinError.classList.remove("show");
                pinError.textContent = "*Incorrect authorization PIN";
                pinBoxes.forEach(box => box.classList.remove("error"));
            }

            function getEnteredPin() {
                return pinBoxes.map(box => box.value).join("");
            }

            async function requestVoidApi(payload) {
                const response = await fetch(VOID_ORDER_API, {
                    method: "POST",
                    credentials: "same-origin",
                    cache: "no-store",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json"
                    },
                    body: JSON.stringify(payload)
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result.success) {
                    throw new Error(result.error || "Unable to complete this void request.");
                }
                return result;
            }

            pinBoxes.forEach((box, idx) => {
                box.addEventListener("input", () => {
                    box.value = box.value.replace(/[^0-9]/g, "");
                    if (box.classList.contains("error")) hidePinError();
                    if (box.value && idx < pinBoxes.length - 1) {
                        pinBoxes[idx + 1].focus();
                    }
                });

                box.addEventListener("keydown", (e) => {
                    if (e.key === "Backspace" && !box.value && idx > 0) {
                        pinBoxes[idx - 1].focus();
                    }
                });
            });

            pinCancelBtn.addEventListener("click", closePinModal);

            pinConfirmBtn.addEventListener("click", async () => {
                const entered = getEnteredPin();

                if (entered.length !== 4 || !currentVoidRow?.dataset.orderId) {
                    showPinError();
                    return;
                }

                pinConfirmBtn.disabled = true;
                try {
                    const result = await requestVoidApi({
                        action: "authorize",
                        order_id: Number(currentVoidRow.dataset.orderId),
                        authorization_pin: entered
                    });
                    const row = currentVoidRow;
                    currentVoidAuthorizationPin = entered;
                    closePinModal();
                    openVoidModal(row, result);
                } catch (error) {
                    console.error("POS void authorization failed:", error);
                    clearPinBoxes();
                    showPinError("*" + (error.message || "Incorrect authorization PIN."));
                    pinBoxes[0].focus();
                } finally {
                    pinConfirmBtn.disabled = false;
                }
            });

            pinModal.addEventListener("click", (e) => {
                if (e.target === pinModal) closePinModal();
            });

            // ── Void order (adjustment mode) modal ──
            const voidModal = document.getElementById("voidModal");
            const voidOrderNoEl = document.getElementById("voidOrderNo");
            const voidPaymentEl = document.getElementById("voidPayment");
            const voidStatusEl = document.getElementById("voidStatus");
            const voidItemsList = document.getElementById("voidItemsList");
            const voidTotalEl = document.getElementById("voidTotal");
            const voidPayAdjust = document.getElementById("voidPayAdjust");
            const voidPayLabel = document.getElementById("voidPayLabel");
            const voidPayValue = document.getElementById("voidPayValue");
            const addItemBtn = document.getElementById("addItemBtn");
            const addAddonBtn = document.getElementById("addAddonBtn");
            const voidCancelBtn = document.getElementById("voidCancelBtn");
            const voidConfirmBtn = document.getElementById("voidConfirmBtn");

            let voidRows = [];
            let voidProducts = [];
            let voidRowIdCounter = 0;
            let currentVoidOriginalPaid = 0;
            let currentVoidOrderId = 0;

            function productForVoidRow(rowState) {
                return voidProducts.find((product) => Number(product.id) === Number(rowState.product_id)) || null;
            }

            function modifierNames(value) {
                if (Array.isArray(value)) {
                    return value.flatMap((modifier) => {
                        if (modifier && typeof modifier === "object") {
                            return modifierNames(modifier.value || modifier.name || "");
                        }
                        return modifierNames(modifier);
                    });
                }

                const rawValue = String(value || "").trim();
                if (!rawValue) return [];

                // Older cart rows can contain JSON while saved receipts use
                // comma-separated add-ons. Support both representations so
                // the receipt choices are restored in the adjustment modal.
                if ((rawValue.startsWith("[") || rawValue.startsWith("{"))) {
                    try {
                        const parsed = JSON.parse(rawValue);
                        if (parsed !== rawValue) return modifierNames(parsed);
                    } catch (error) {
                        // Fall through to the receipt's comma-separated text.
                    }
                }

                return rawValue
                    .split(",")
                    .map((name) => name.trim())
                    .filter(Boolean);
            }

            function matchingModifierName(value, modifiers) {
                const normalizedValue = String(value || "").trim().replace(/\s+/g, " ").toLowerCase();
                const match = (modifiers || []).find((modifier) =>
                    String(modifier.name || "").trim().replace(/\s+/g, " ").toLowerCase() === normalizedValue
                );
                return match ? String(match.name) : "";
            }

            function openVoidModal(row, result) {
                const order = result.order || {};
                voidProducts = Array.isArray(result.products) ? result.products : [];
                if (!voidProducts.length) {
                    window.alert("No available menu items were found for this adjustment.");
                    currentVoidAuthorizationPin = "";
                    return;
                }

                currentVoidRowEl = row;
                currentVoidOrderId = Number(order.id || row.dataset.orderId || 0);
                currentVoidOriginalPaid = Number(order.total || row.dataset.amount || 0);
                voidOrderNoEl.textContent = result.order_number || row.dataset.orderNumber || "—";
                voidPaymentEl.textContent = String(order.payment_method || "cash").toUpperCase();
                voidStatusEl.textContent = String(order.status || "completed");

                voidRows = (Array.isArray(order.items) ? order.items : []).map((item) => {
                    const product = voidProducts.find((entry) =>
                        String(entry.product_name || "").toLowerCase() === String(item.product_name || "").toLowerCase()
                    ) || null;
                    const addonOptions = product?.addons || [];
                    const milkOptions = product?.milk_choices || [];
                    return {
                        id: voidRowIdCounter++,
                        product_id: product ? Number(product.id) : 0,
                        quantity: Math.max(1, Number.parseInt(item.quantity, 10) || 1),
                        milk: matchingModifierName(item.milk, milkOptions),
                        addons: modifierNames(item.addons)
                            .map((addon) => matchingModifierName(addon, addonOptions))
                            .filter(Boolean),
                        notes: String(item.notes || "")
                    };
                });

                if (!voidRows.length) {
                    voidRows.push({
                        id: voidRowIdCounter++,
                        product_id: Number(voidProducts[0].id),
                        quantity: 1,
                        milk: "",
                        addons: [],
                        notes: ""
                    });
                }

                renderVoidItems();
                voidModal.classList.add("show");
            }

            function closeVoidModal() {
                voidModal.classList.remove("show");
                currentVoidRowEl = null;
                currentVoidOrderId = 0;
                currentVoidAuthorizationPin = "";
                voidRows = [];
            }

            function voidProductOptions(selectedId) {
                return `<option value="">Choose item</option>` + voidProducts.map((product) =>
                    `<option value="${Number(product.id)}" ${Number(product.id) === Number(selectedId) ? "selected" : ""}>${escapeHtml(product.product_name)}</option>`
                ).join("");
            }

            function voidModifierOptions(modifiers, selectedValue, emptyLabel) {
                return `<option value="">${escapeHtml(emptyLabel)}</option>` + (modifiers || []).map((modifier) =>
                    `<option value="${escapeHtml(modifier.name)}" ${String(modifier.name) === String(selectedValue) ? "selected" : ""}>${escapeHtml(modifier.name)}${Number(modifier.price) > 0 ? ` (+${formatVoidPeso(modifier.price)})` : ""}</option>`
                ).join("");
            }

            function calcRowSubtotal(rowState) {
                const product = productForVoidRow(rowState);
                if (!product) return 0;

                let unitPrice = Number(product.price) || 0;
                const milk = (product.milk_choices || []).find((modifier) => String(modifier.name) === String(rowState.milk));
                unitPrice += Number(milk?.price || 0);
                (rowState.addons || []).forEach((addonName) => {
                    const addon = (product.addons || []).find((modifier) => String(modifier.name) === String(addonName));
                    unitPrice += Number(addon?.price || 0);
                });
                return unitPrice * Math.max(1, Number(rowState.quantity) || 1);
            }

            function renderVoidItems() {
                voidItemsList.innerHTML = voidRows.map((rowState) => {
                    const product = productForVoidRow(rowState);
                    const productName = product?.product_name || "Choose a replacement item";
                    const addonOptions = product?.addons || [];
                    const selectedAddons = new Set(rowState.addons || []);
                    const addonMarkup = addonOptions.length
                        ? addonOptions.map((addon) => `
                            <label class="void-addon-choice">
                                <input type="checkbox" value="${escapeHtml(addon.name)}" ${selectedAddons.has(String(addon.name)) ? "checked" : ""}>
                                <span>${escapeHtml(addon.name)}${Number(addon.price) > 0 ? ` (+${formatVoidPeso(addon.price)})` : ""}</span>
                            </label>`).join("")
                        : '<span class="void-no-modifiers">No add-ons available</span>';

                    return `
                        <div class="void-item-row" data-row-id="${rowState.id}">
                            <select class="void-item-select" aria-label="Menu item">${voidProductOptions(rowState.product_id)}</select>
                            <div class="void-item-addons">
                                <select class="void-milk-select" aria-label="Milk option">${voidModifierOptions(product?.milk_choices, rowState.milk, "No milk option")}</select>
                                <div class="void-addon-options" aria-label="Add-ons">${addonMarkup}</div>
                                <input class="void-item-notes" type="text" maxlength="1000" placeholder="Item note (optional)" value="${escapeHtml(rowState.notes)}">
                                <small class="void-item-price">${escapeHtml(productName)} · ${formatVoidPeso(calcRowSubtotal(rowState))}</small>
                            </div>
                            <div class="void-item-right">
                                <div class="void-qty-stepper">
                                    <button type="button" class="void-qty-minus" aria-label="Decrease quantity">−</button>
                                    <span class="void-qty-value">${rowState.quantity}</span>
                                    <button type="button" class="void-qty-plus" aria-label="Increase quantity">+</button>
                                </div>
                                ${voidRows.length > 1 ? '<button type="button" class="void-row-remove" aria-label="Remove item"><i class="fa-solid fa-trash"></i></button>' : ""}
                            </div>
                        </div>`;
                }).join("");

                attachVoidRowListeners();
                updateVoidTotals();
            }

            function attachVoidRowListeners() {
                voidItemsList.querySelectorAll(".void-item-row").forEach((rowEl) => {
                    const rowState = voidRows.find((row) => Number(row.id) === Number(rowEl.dataset.rowId));
                    if (!rowState) return;

                    rowEl.querySelector(".void-item-select").addEventListener("change", (event) => {
                        rowState.product_id = Number(event.target.value) || 0;
                        rowState.milk = "";
                        rowState.addons = [];
                        renderVoidItems();
                    });
                    rowEl.querySelector(".void-milk-select").addEventListener("change", (event) => {
                        rowState.milk = event.target.value;
                        renderVoidItems();
                    });
                    rowEl.querySelectorAll(".void-addon-choice input").forEach((input) => {
                        input.addEventListener("change", () => {
                            rowState.addons = Array.from(rowEl.querySelectorAll(".void-addon-choice input:checked"))
                                .map((checkbox) => checkbox.value);
                            renderVoidItems();
                        });
                    });
                    rowEl.querySelector(".void-item-notes").addEventListener("input", (event) => {
                        rowState.notes = event.target.value;
                    });
                    rowEl.querySelector(".void-qty-plus").addEventListener("click", () => {
                        rowState.quantity = Math.min(99, rowState.quantity + 1);
                        renderVoidItems();
                    });
                    rowEl.querySelector(".void-qty-minus").addEventListener("click", () => {
                        rowState.quantity = Math.max(1, rowState.quantity - 1);
                        renderVoidItems();
                    });
                    rowEl.querySelector(".void-row-remove")?.addEventListener("click", () => {
                        voidRows = voidRows.filter((row) => row !== rowState);
                        renderVoidItems();
                    });
                });
            }

            function updateVoidTotals() {
                const total = voidRows.reduce((sum, row) => sum + calcRowSubtotal(row), 0);
                const isReady = voidRows.length > 0 && voidRows.every((row) => productForVoidRow(row));
                voidTotalEl.textContent = formatVoidPeso(total);
                voidConfirmBtn.disabled = !isReady;

                const diff = total - currentVoidOriginalPaid;
                if (Math.abs(diff) < 0.005) {
                    voidPayAdjust.style.display = "none";
                } else if (diff > 0) {
                    voidPayAdjust.style.display = "block";
                    voidPayLabel.textContent = "Pay In";
                    voidPayValue.textContent = "Collect " + formatVoidPeso(diff);
                } else {
                    voidPayAdjust.style.display = "block";
                    voidPayLabel.textContent = "Pay Out";
                    voidPayValue.textContent = "Refund " + formatVoidPeso(Math.abs(diff));
                }
            }

            function formatVoidPeso(amount) {
                return "₱" + (Number(amount) || 0).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            addItemBtn.addEventListener("click", () => {
                if (!voidProducts.length) return;
                voidRows.push({
                    id: voidRowIdCounter++,
                    product_id: Number(voidProducts[0].id),
                    quantity: 1,
                    milk: "",
                    addons: [],
                    notes: ""
                });
                renderVoidItems();
            });

            addAddonBtn.addEventListener("click", () => {
                const lastRow = voidRows[voidRows.length - 1];
                const product = lastRow && productForVoidRow(lastRow);
                const nextAddon = (product?.addons || []).find((addon) => !lastRow.addons.includes(String(addon.name)));
                if (!nextAddon) return;
                lastRow.addons.push(String(nextAddon.name));
                renderVoidItems();
            });

            voidCancelBtn.addEventListener("click", closeVoidModal);

            voidModal.addEventListener("click", (e) => {
                if (e.target === voidModal) closeVoidModal();
            });

            // ── Receipt for the adjusted (new) order ──
            const orderModal = document.getElementById("orderModal");
            const closeModalBtn = document.getElementById("closeModalBtn");
            const modalDoneBtn = document.getElementById("modalDoneBtn");
            const modalPrintBtn = document.getElementById("modalPrintBtn");
            const currentBranchName = <?= json_encode($branchName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const currentCashierName = <?= json_encode($employeeName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

            function formatReceiptPeso(value) {
                const amount = Number(value);
                return "₱" + (Number.isFinite(amount) ? amount : 0).toLocaleString("en-PH", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function formatReceiptDate(value) {
                const date = new Date(String(value || "").replace(" ", "T"));
                if (Number.isNaN(date.getTime())) return "—";

                return date.toLocaleString("en-PH", {
                    timeZone: "Asia/Manila",
                    month: "short",
                    day: "2-digit",
                    year: "numeric",
                    hour: "numeric",
                    minute: "2-digit",
                    hour12: true
                });
            }

            function receiptStatusLabel(value) {
                return String(value || "Unknown")
                    .replace(/[-_]/g, " ")
                    .replace(/\b\w/g, (letter) => letter.toUpperCase());
            }

            function addReceiptItem(itemsList, item) {
                const qty = Math.max(1, Number.parseInt(item.qty, 10) || 1);
                const unitPrice = Number(item.price || 0);
                const lineTotal = Number.isFinite(Number(item.line_total))
                    ? Number(item.line_total)
                    : qty * unitPrice;
                const itemBlock = document.createElement("div");
                itemBlock.className = "receipt-item-block";
                const itemMain = document.createElement("div");
                itemMain.className = "receipt-item-main";

                const qtyCell = document.createElement("span");
                qtyCell.className = "r-col-qty";
                qtyCell.textContent = String(qty);
                const nameCell = document.createElement("span");
                nameCell.className = "r-col-desc";
                nameCell.textContent = String(item.name || "Item");
                const amountCell = document.createElement("span");
                amountCell.className = "r-col-amt";
                amountCell.textContent = Number.isFinite(lineTotal) ? lineTotal.toFixed(2) : "0.00";

                itemMain.append(qtyCell, nameCell, amountCell);
                itemBlock.appendChild(itemMain);

                const details = [
                    item.milk ? `Milk: ${item.milk}` : "",
                    item.addons ? `Add-ons: ${item.addons}` : "",
                    item.notes ? `Note: ${item.notes}` : ""
                ].filter(Boolean);
                if (details.length) {
                    const detailList = document.createElement("div");
                    detailList.className = "receipt-item-details";
                    details.forEach((detail) => {
                        const detailLine = document.createElement("span");
                        detailLine.className = "r-indent";
                        detailLine.textContent = `• ${detail}`;
                        detailList.appendChild(detailLine);
                    });
                    itemBlock.appendChild(detailList);
                }

                itemsList.appendChild(itemBlock);
                return Number.isFinite(lineTotal) ? lineTotal : 0;
            }

            function renderHistoryReceipt(order, orderNumber) {
                const itemsList = document.getElementById("modalItemsList");
                const items = Array.isArray(order.items) ? order.items : [];
                const typeLabels = {
                    "dine-in": "Dine In",
                    takeout: "Take Out",
                    delivery: "Delivery",
                    pickup: "Pick Up"
                };
                const orderTypeLabel = typeLabels[order.order_type] || "Order";
                const paymentMethod = String(order.payment_method || "cod").toLowerCase();
                const paymentLabel = paymentMethod === "qrph" ? "QRPh" : "Cash";
                const calculatedSubtotal = items.reduce((sum, item) => sum +
                    ((Number(item.line_total) || ((Number(item.qty) || 1) * (Number(item.price) || 0)))), 0);
                const subtotal = Number.isFinite(Number(order.subtotal)) ? Number(order.subtotal) : calculatedSubtotal;
                const deliveryFee = Math.max(0, Number(order.delivery_fee) || 0);
                const tax = Math.max(0, Number(order.tax) || 0);
                const total = Number.isFinite(Number(order.total)) ? Number(order.total) : subtotal + deliveryFee + tax;
                const vatableSales = total / 1.12;
                const vatAmount = total - vatableSales;

                document.getElementById("modalBranch").textContent = order.branch_name || currentBranchName;
                document.getElementById("modalCashier").textContent = order.cashier_name || currentCashierName;
                document.getElementById("modalOrderId").textContent = orderNumber;
                document.getElementById("modalOrderTime").textContent = formatReceiptDate(order.created_at);
                document.getElementById("modalCustomerName").textContent = order.customer_name || order.user_name || "Guest";
                document.getElementById("modalOrderType").textContent = `${orderTypeLabel.toUpperCase()} ORDER`;
                const wasVoided = Boolean(order.voided_at);
                document.getElementById("modalOrderStatus").textContent = wasVoided
                    ? "VOID ORDER"
                    : `${receiptStatusLabel(order.status)} / ${receiptStatusLabel(order.payment_status)}`;
                document.getElementById("modalPayment").textContent = paymentLabel;
                document.getElementById("modalReceiptMessage").textContent = wasVoided
                    ? "*** VOID ORDER ***"
                    : String(order.status).toLowerCase() === "cancelled"
                        ? "*** ORDER CANCELLED ***"
                        : "*** THANK YOU FOR YOUR PURCHASE! ***";
                document.getElementById("modalBarcodeNum").textContent = `* ${orderNumber} *`;

                itemsList.replaceChildren();
                items.forEach((item) => addReceiptItem(itemsList, item));
                if (!items.length) {
                    const emptyItem = document.createElement("p");
                    emptyItem.className = "receipt-item-details";
                    emptyItem.textContent = "Order items are not available.";
                    itemsList.appendChild(emptyItem);
                }

                document.getElementById("modalSubtotal").textContent = formatReceiptPeso(subtotal);
                document.getElementById("modalDeliveryFee").textContent = formatReceiptPeso(deliveryFee);
                document.getElementById("modalTax").textContent = formatReceiptPeso(tax);
                document.getElementById("modalDeliveryFeeRow").style.display = deliveryFee > 0 ? "flex" : "none";
                document.getElementById("modalTaxRow").style.display = tax > 0 ? "flex" : "none";
                document.getElementById("modalVatSales").textContent = formatReceiptPeso(vatableSales);
                document.getElementById("modalVatAmt").textContent = formatReceiptPeso(vatAmount);
                document.getElementById("modalTotal").textContent = formatReceiptPeso(total);

                const tenderedRow = document.getElementById("modalTenderedRow");
                const changeRow = document.getElementById("modalChangeRow");
                const referenceRow = document.getElementById("modalRefRow");
                if (paymentMethod === "qrph") {
                    tenderedRow.style.display = "none";
                    changeRow.style.display = "none";
                    referenceRow.style.display = "flex";
                    document.getElementById("modalRefNo").textContent = order.payment_reference || "Not available";
                } else {
                    document.getElementById("modalTenderedLabel").textContent = "Amount Tendered:";
                    document.getElementById("modalChangeLabel").textContent = "Change:";
                    document.getElementById("modalTendered").textContent = formatReceiptPeso(total);
                    document.getElementById("modalChange").textContent = formatReceiptPeso(0);
                    tenderedRow.style.display = "flex";
                    changeRow.style.display = "flex";
                    referenceRow.style.display = "none";
                }
            }

            async function openHistoryReceipt(row) {
                const orderId = Number.parseInt(row.dataset.orderId || "", 10);
                if (!Number.isInteger(orderId) || orderId <= 0 || row.dataset.receiptLoading === "true") return;

                row.dataset.receiptLoading = "true";
                row.classList.add("is-loading-receipt");
                try {
                    const response = await fetch(`../pos-history-order-api.php?order_id=${encodeURIComponent(orderId)}`, {
                        credentials: "same-origin",
                        cache: "no-store",
                        headers: { Accept: "application/json" }
                    });
                    const payload = await response.json();
                    if (!response.ok || !payload.success || !payload.order) {
                        throw new Error(payload.error || "Unable to load this order receipt.");
                    }

                    renderHistoryReceipt(payload.order, row.dataset.orderNumber || `ORDER-${orderId}`);
                    orderModal.hidden = false;
                    document.body.style.overflow = "hidden";
                } catch (error) {
                    console.error("Unable to open POS history receipt:", error);
                    window.alert(error.message || "Unable to load this order receipt.");
                } finally {
                    delete row.dataset.receiptLoading;
                    row.classList.remove("is-loading-receipt");
                }
            }

            function closeVoidReceipt() {
                orderModal.hidden = true;
                document.body.style.overflow = "";
            }

            closeModalBtn.addEventListener("click", closeVoidReceipt);
            modalDoneBtn.addEventListener("click", closeVoidReceipt);
            modalPrintBtn.addEventListener("click", () => window.print());

            orderModal.addEventListener("click", (e) => {
                if (e.target === orderModal) closeVoidReceipt();
            });

            voidConfirmBtn.addEventListener("click", async () => {
                if (!currentVoidRowEl || currentVoidOrderId <= 0 || !currentVoidAuthorizationPin) return;

                const items = voidRows.map((row) => ({
                    product_id: Number(row.product_id),
                    quantity: Number(row.quantity),
                    milk: row.milk || "",
                    addons: Array.isArray(row.addons) ? row.addons : [],
                    notes: row.notes || ""
                }));
                if (!items.length || items.some((item) => item.product_id <= 0 || item.quantity <= 0)) return;

                voidConfirmBtn.disabled = true;
                try {
                    await requestVoidApi({
                        action: "void_adjust",
                        order_id: currentVoidOrderId,
                        authorization_pin: currentVoidAuthorizationPin,
                        items
                    });

                    closeVoidModal();
                    window.alert("The order was voided and the adjusted replacement order was saved.");
                    window.location.reload();
                } catch (error) {
                    console.error("Unable to save POS void adjustment:", error);
                    window.alert(error.message || "Unable to save this void adjustment.");
                    if (voidModal.classList.contains("show")) updateVoidTotals();
                } finally {
                    if (voidModal.classList.contains("show")) voidConfirmBtn.disabled = false;
                }
            });

            // Lets the page's 10-second auto-refresh hold off while a void popup is open.
            window.isHistoryVoidFlowActive = function () {
                return pinModal.classList.contains("show")
                    || voidModal.classList.contains("show")
                    || !orderModal.hidden
                    || !!document.querySelector(".row-actions-menu.open");
            };

            initRowActions();
        })();
    </script>

    <script src="pos-responsive.js"></script>
    <script src="order-notify.js"></script>
    <script src="shift-monitor.js"></script>
    <script>
        (function refreshHistoryData() {
            setInterval(() => {
                // Don't wipe the page while a void / PIN / receipt popup is open.
                if (window.isHistoryVoidFlowActive && window.isHistoryVoidFlowActive()) return;
                if (window.isHistoryFilterActive && window.isHistoryFilterActive()) return;
                if (document.visibilityState === 'visible') {
                    window.location.reload();
                }
            }, 10000);
        })();
    </script>
</body>
</html>
