<?php
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/../config/activity_logger.php';

function activityLogEsc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function activityLogHasTable(mysqli $connect, string $table): bool
{
    static $tables = [];
    if (array_key_exists($table, $tables)) {
        return $tables[$table];
    }

    $safeTable = $connect->real_escape_string($table);
    $result = $connect->query("SHOW TABLES LIKE '{$safeTable}'");
    return $tables[$table] = $result instanceof mysqli_result && $result->num_rows > 0;
}

function activityLogIcon(string $category, string $action = ''): array
{
    if ($category === 'login' && $action === 'logout') return ['icon-red', 'fa-right-from-bracket'];
    if ($category === 'login' && $action === 'failed_login') return ['icon-red', 'fa-triangle-exclamation'];

    return match ($category) {
        'exports' => ['icon-purple', 'fa-file-arrow-down'],
        'loyalty' => ['icon-olive', 'fa-award'],
        'menu' => [$action === 'product_deleted' ? 'icon-red' : 'icon-orange', $action === 'product_deleted' ? 'fa-trash' : 'fa-mug-hot'],
        'login' => ['icon-blue', 'fa-right-to-bracket'],
        'orders' => ['icon-green', 'fa-file-lines'],
        'shift' => ['icon-maroon', 'fa-clock'],
        'inventory' => ['icon-orange', 'fa-boxes-stacked'],
        default => ['icon-maroon', 'fa-gear'],
    };
}

function activityLogAdd(array &$activities, string $category, string $action, string $title, string $details, ?string $occurredAt, array $extra = []): void
{
    if (!$occurredAt) {
        return;
    }

    [$iconClass, $iconName] = activityLogIcon($category, $action);
    $activities[] = array_merge([
        'category' => $category,
        'action' => $action,
        'title' => $title,
        'details' => $details,
        'occurred_at' => $occurredAt,
        'sort_time' => strtotime($occurredAt) ?: 0,
        'icon_class' => $iconClass,
        'icon_name' => $iconName,
    ], $extra);
}

function activityLogDateParts(string $dateValue): array
{
    try {
        $date = new DateTimeImmutable($dateValue, new DateTimeZone('Asia/Manila'));
        return [$date->format('M j, Y'), $date->format('g:i A')];
    } catch (Throwable $exception) {
        return ['Unknown date', ''];
    }
}

function activityLogResult(mysqli $connect, string $sql): ?mysqli_result
{
    try {
        $result = $connect->query($sql);
        return $result instanceof mysqli_result ? $result : null;
    } catch (Throwable $exception) {
        error_log('Activity log query failed: ' . $exception->getMessage());
        return null;
    }
}

$activities = [];

if (activityLogHasTable($connect, 'login_logs')) {
    $logins = activityLogResult($connect, "
        SELECT l.id, l.login_status, l.login_datetime, b.branch_name,
               COALESCE(NULLIF(TRIM(e.employee_name), ''), NULLIF(TRIM(CONCAT_WS(' ', e.firstname, e.lastname)), ''), 'Unknown user') AS employee_name
        FROM login_logs l
        LEFT JOIN employees e ON e.id = l.employee_id
        LEFT JOIN branches b ON b.id = l.branch_id
        WHERE l.login_datetime IS NOT NULL
        ORDER BY l.login_datetime DESC, l.id DESC
        LIMIT 250
    ");
    while ($login = $logins?->fetch_assoc()) {
        $successful = ($login['login_status'] ?? '') === 'success';
        $name = (string) ($login['employee_name'] ?? 'Unknown user');
        $branch = trim((string) ($login['branch_name'] ?? ''));
        $place = $branch !== '' ? ' at ' . $branch : '';
        activityLogAdd(
            $activities,
            'login',
            $successful ? 'login' : 'failed_login',
            $successful ? 'Log In' : 'Failed Login',
            $successful ? $name . ' logged in to the system' . $place . '.' : 'An unsuccessful login attempt was recorded' . $place . '.',
            $login['login_datetime'] ?? null
        );
    }
}

if (activityLogHasTable($connect, 'orders')) {
    $orders = activityLogResult($connect, "
        SELECT o.id, o.status, o.order_type, o.total, o.created_at, o.updated_at, b.branch_name,
               COALESCE(NULLIF(TRIM(e.employee_name), ''), NULLIF(TRIM(CONCAT_WS(' ', e.firstname, e.lastname)), ''), '') AS cashier_name
        FROM orders o
        LEFT JOIN branches b ON b.id = o.branch_id
        LEFT JOIN employees e ON e.id = o.cashier_id
        WHERE o.created_at IS NOT NULL
        ORDER BY o.created_at DESC, o.id DESC
        LIMIT 250
    ");
    while ($order = $orders?->fetch_assoc()) {
        $branch = trim((string) ($order['branch_name'] ?? ''));
        $cashier = trim((string) ($order['cashier_name'] ?? ''));
        $actor = $cashier !== '' ? $cashier : 'the customer';
        $location = $branch !== '' ? ' at ' . $branch : '';
        activityLogAdd(
            $activities,
            'orders',
            'order_created',
            'New Order',
            'Order #' . (int) $order['id'] . ' was created by ' . $actor . $location . '.',
            $order['created_at'] ?? null
        );

        if (!empty($order['updated_at']) && strtotime((string) $order['updated_at']) > strtotime((string) $order['created_at'])) {
            activityLogAdd(
                $activities,
                'orders',
                'order_updated',
                'Order Updated',
                'Order #' . (int) $order['id'] . ' is now ' . ucfirst((string) ($order['status'] ?? 'updated')) . '.',
                $order['updated_at']
            );
        }
    }
}

if (activityLogHasTable($connect, 'loyalty_transactions')) {
    $loyaltyTransactions = activityLogResult($connect, "
        SELECT lt.id, lt.user_id, lt.transaction_type, lt.points_awarded, lt.new_balance, lt.order_id,
               lt.redeemed_product_name, lt.created_at,
               COALESCE(NULLIF(TRIM(CONCAT_WS(' ', u.firstname, u.lastname)), ''), CONCAT('Customer #', lt.user_id)) AS customer_name
        FROM loyalty_transactions lt
        LEFT JOIN users u ON u.id = lt.user_id
        WHERE lt.created_at IS NOT NULL
        ORDER BY lt.created_at DESC, lt.id DESC
        LIMIT 250
    ");
    while ($transaction = $loyaltyTransactions?->fetch_assoc()) {
        $customer = (string) ($transaction['customer_name'] ?? ('Customer #' . (int) $transaction['user_id']));
        $isRedemption = ($transaction['transaction_type'] ?? '') === 'redemption';
        $product = trim((string) ($transaction['redeemed_product_name'] ?? ''));
        $details = $isRedemption
            ? $customer . ' redeemed' . ($product !== '' ? ' ' . $product : ' a loyalty reward') . '.'
            : $customer . ' received ' . (int) ($transaction['points_awarded'] ?? 0) . ' loyalty beans.';
        activityLogAdd(
            $activities,
            'loyalty',
            $isRedemption ? 'reward_redeemed' : 'beans_awarded',
            $isRedemption ? 'Loyalty Reward Redeemed' : 'Loyalty Beans Awarded',
            $details,
            $transaction['created_at'] ?? null
        );
    }
}

if (activityLogHasTable($connect, 'products')) {
    $products = activityLogResult($connect, "
        SELECT id, product_name, category, created_at, updated_at
        FROM products
        WHERE created_at IS NOT NULL
        ORDER BY GREATEST(created_at, updated_at) DESC, id DESC
        LIMIT 250
    ");
    while ($product = $products?->fetch_assoc()) {
        $name = trim((string) ($product['product_name'] ?? 'Menu item'));
        $category = trim((string) ($product['category'] ?? ''));
        $categoryText = $category !== '' ? ' in ' . ucwords(str_replace('-', ' ', $category)) : '';
        activityLogAdd(
            $activities,
            'menu',
            'product_created',
            'Menu Item Added',
            $name . ' was added to the menu' . $categoryText . '.',
            $product['created_at'] ?? null
        );

        if (!empty($product['updated_at']) && strtotime((string) $product['updated_at']) > strtotime((string) $product['created_at'])) {
            activityLogAdd(
                $activities,
                'menu',
                'product_updated',
                'Menu Item Updated',
                $name . ' was updated' . $categoryText . '.',
                $product['updated_at']
            );
        }
    }
}

if (activityLogHasTable($connect, 'shift_logs')) {
    $shifts = activityLogResult($connect, "
        SELECT s.id, s.status, s.opening_cash_float, s.cash_sales, s.gcash_sales, s.total_sales,
               s.opened_at, s.closed_at, b.branch_name,
               COALESCE(NULLIF(TRIM(e.employee_name), ''), NULLIF(TRIM(CONCAT_WS(' ', e.firstname, e.lastname)), ''), 'POS employee') AS employee_name
        FROM shift_logs s
        LEFT JOIN branches b ON b.id = s.branch_id
        LEFT JOIN employees e ON e.id = s.employee_id
        WHERE s.opened_at IS NOT NULL
        ORDER BY s.opened_at DESC, s.id DESC
        LIMIT 250
    ");
    while ($shift = $shifts?->fetch_assoc()) {
        $branch = trim((string) ($shift['branch_name'] ?? ''));
        $location = $branch !== '' ? ' at ' . $branch : '';
        $shiftData = [
            'shift_status' => 'open',
            'cash_float' => (float) ($shift['opening_cash_float'] ?? 0),
            'cash_sales' => (float) ($shift['cash_sales'] ?? 0),
            'qr_sales' => (float) ($shift['gcash_sales'] ?? 0),
            'total_sales' => (float) ($shift['total_sales'] ?? 0),
        ];
        activityLogAdd(
            $activities,
            'shift',
            'shift_opened',
            'Shift Opened',
            (string) $shift['employee_name'] . ' opened a POS shift' . $location . ' with ₱' . number_format((float) ($shift['opening_cash_float'] ?? 0), 2) . ' cash float.',
            $shift['opened_at'] ?? null,
            $shiftData
        );

        if (!empty($shift['closed_at'])) {
            $shiftData['shift_status'] = 'closed';
            activityLogAdd(
                $activities,
                'shift',
                'shift_closed',
                'Shift Closed',
                (string) $shift['employee_name'] . ' closed a POS shift' . $location . ' with ₱' . number_format((float) ($shift['total_sales'] ?? 0), 2) . ' total sales.',
                $shift['closed_at'],
                $shiftData
            );
        }
    }
}

if (boycold_activity_log_available($connect)) {
    $recordedActivities = activityLogResult($connect, "
        SELECT id, category, action, summary, details, created_at
        FROM activity_logs
        ORDER BY created_at DESC, id DESC
        LIMIT 250
    ");
    while ($activity = $recordedActivities?->fetch_assoc()) {
        activityLogAdd(
            $activities,
            (string) ($activity['category'] ?? 'system'),
            (string) ($activity['action'] ?? 'recorded'),
            (string) ($activity['summary'] ?? 'System Activity'),
            (string) ($activity['details'] ?? 'A system activity was recorded.'),
            $activity['created_at'] ?? null
        );
    }
}

usort($activities, static function (array $left, array $right): int {
    return ($right['sort_time'] <=> $left['sort_time']) ?: strcmp((string) $right['title'], (string) $left['title']);
});
$activities = array_slice($activities, 0, 250);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-css/activitylog.css">
    <link rel="stylesheet" href="admin-css/dashboard.css">
    <link rel="stylesheet" href="admin-css/admin-sidebar.css">
    <link rel="stylesheet" href="admin-css/admin-responsive.css">
    <link rel="icon" href="../img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Activity Log</title>
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
                                <span class="nav-icon1"><svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.5 5C0.367392 5 0.240215 4.94732 0.146447 4.85355C0.0526785 4.75979 0 4.63261 0 4.5V0.5C0 0.367392 0.0526785 0.240215 0.146447 0.146447C0.240215 0.0526785 0.367392 0 0.5 0H4.5C4.63261 0 4.75979 0.0526785 4.85355 0.146447C4.94732 0.240215 5 0.367392 5 0.5V4.5C5 4.63261 4.94732 4.75979 4.85355 4.85355C4.75979 4.94732 4.63261 5 4.5 5H0.5ZM7.5 5C7.36739 5 7.24021 4.94732 7.14645 4.85355C7.05268 4.75979 7 4.63261 7 4.5V0.5C7 0.367392 7.05268 0.240215 7.14645 0.146447C7.24021 0.0526785 7.36739 0 7.5 0H11.5C11.6326 0 11.7598 0.0526785 11.8536 0.146447C11.9473 0.240215 12 0.367392 12 0.5V4.5C12 4.63261 11.9473 4.75979 11.8536 4.85355C11.7598 4.94732 11.6326 5 11.5 5H7.5ZM0.5 12C0.367392 12 0.240215 11.9473 0.146447 11.8536C0.0526785 11.7598 0 11.6326 0 11.5V7.5C0 7.36739 0.0526785 7.24021 0.146447 7.14645C0.240215 7.05268 0.367392 7 0.5 7H4.5C4.63261 7 4.75979 7.05268 4.85355 7.14645C4.94732 7.24021 5 7.36739 5 7.5V11.5C5 11.6326 4.94732 11.7598 4.85355 11.8536C4.75979 11.9473 4.63261 12 4.5 12H0.5ZM7.5 12C7.36739 12 7.24021 11.9473 7.14645 11.8536C7.05268 11.7598 7 11.6326 7 11.5V7.5C7 7.36739 7.05268 7.24021 7.14645 7.14645C7.24021 7.05268 7.36739 7 7.5 7H11.5C11.6326 7 11.7598 7.05268 11.8536 7.14645C11.9473 7.24021 12 7.36739 12 7.5V11.5C12 11.6326 11.9473 11.7598 11.8536 11.8536C11.7598 11.9473 11.6326 12 11.5 12H7.5Z" fill="currentColor"/></svg></span>
                                <span class="nav-label">Dashboard</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="orders.php">
                                <span class="nav-icon"><svg width="19" height="22" viewBox="0 0 19 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.8882 1H3.31469C2.03632 1 1 2.03632 1 3.31469V18.3602C1 19.6386 2.03632 20.6749 3.31469 20.6749H14.8882C16.1665 20.6749 17.2029 19.6386 17.2029 18.3602V3.31469C17.2029 2.03632 16.1665 1 14.8882 1Z" stroke="currentColor" stroke-width="2"/><path d="M5.62939 6.78662H12.5735M5.62939 11.416H12.5735M5.62939 16.0454H10.2588" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                <span class="nav-label">Orders</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="data-analytics.php">
                                <span class="nav-icon2"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.8601 4.39V19.39C15.8601 21.06 17.0001 22 18.2501 22C19.3901 22 20.6401 21.21 20.6401 19.39V4.5C20.6401 2.96 19.5001 2 18.2501 2C17.0001 2 15.8601 3.06 15.8601 4.39ZM9.61011 12V19.39C9.61011 21.07 10.7701 22 12.0001 22C13.1401 22 14.3901 21.21 14.3901 19.39V12.11C14.3901 10.57 13.2501 9.61 12.0001 9.61C10.7501 9.61 9.61011 10.67 9.61011 12ZM5.75011 17.23C7.07011 17.23 8.14011 18.3 8.14011 19.61C8.14011 20.2439 7.88831 20.8518 7.44009 21.3C6.99188 21.7482 6.38398 22 5.75011 22C5.11624 22 4.50833 21.7482 4.06012 21.3C3.61191 20.8518 3.36011 20.2439 3.36011 19.61C3.36011 18.3 4.43011 17.23 5.75011 17.23Z" fill="white"/></svg></span>
                                <span class="nav-label">Data Analytics</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="forecasting.php">
                                <span class="nav-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.3751 6C21.0698 6.00008 20.7692 6.0747 20.4993 6.21737C20.2294 6.36005 19.9984 6.56647 19.8264 6.81869C19.6545 7.07092 19.5467 7.36132 19.5124 7.66468C19.4782 7.96803 19.5185 8.27516 19.6299 8.55938L15.6845 12.5048C15.2447 12.3317 14.7556 12.3317 14.3157 12.5048L11.4953 9.68438C11.6069 9.40009 11.6475 9.09283 11.6134 8.78931C11.5792 8.48579 11.4715 8.1952 11.2995 7.94281C11.1275 7.69042 10.8964 7.48387 10.6264 7.34113C10.3563 7.19839 10.0555 7.12377 9.75011 7.12377C9.44467 7.12377 9.14386 7.19839 8.87384 7.34113C8.60381 7.48387 8.37274 7.69042 8.20073 7.94281C8.02872 8.1952 7.92096 8.48579 7.88684 8.78931C7.85272 9.09283 7.89327 9.40009 8.00495 9.68438L3.30948 14.3798C2.90848 14.2225 2.46554 14.2081 2.05514 14.339C1.64474 14.4698 1.29192 14.738 1.056 15.0984C0.82007 15.4588 0.715432 15.8895 0.759675 16.3179C0.803918 16.7464 0.994344 17.1466 1.29893 17.4512C1.60352 17.7558 2.0037 17.9462 2.43218 17.9904C2.86065 18.0347 3.2913 17.93 3.6517 17.6941C4.0121 17.4582 4.28028 17.1054 4.41114 16.695C4.542 16.2846 4.52757 15.8416 4.37026 15.4406L9.06573 10.7452C9.50556 10.9183 9.99466 10.9183 10.4345 10.7452L13.2549 13.5656C13.1433 13.8499 13.1027 14.1572 13.1368 14.4607C13.171 14.7642 13.2787 15.0548 13.4507 15.3072C13.6227 15.5596 13.8538 15.7661 14.1238 15.9089C14.3939 16.0516 14.6947 16.1262 15.0001 16.1262C15.3055 16.1262 15.6063 16.0516 15.8764 15.9089C16.1464 15.7661 16.3775 15.5596 16.5495 15.3072C16.7215 15.0548 16.8293 14.7642 16.8634 14.4607C16.8975 14.1572 16.8569 13.8499 16.7453 13.5656L20.6907 9.62016C20.9475 9.72102 21.2233 9.76399 21.4986 9.74601C21.7738 9.72803 22.0417 9.64953 22.2832 9.51613C22.5246 9.38272 22.7336 9.19768 22.8953 8.97421C23.0571 8.75073 23.1675 8.49433 23.2187 8.22329C23.2699 7.95225 23.2607 7.67324 23.1918 7.40616C23.1228 7.13907 22.9957 6.8905 22.8197 6.67816C22.6436 6.46582 22.4228 6.29495 22.1731 6.17773C21.9234 6.0605 21.651 5.99982 21.3751 6Z" fill="white"/></svg></span>
                                <span class="nav-label">Forecasting</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="inventory.php">
                                <span class="nav-icon"><svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.9126 20.6502V9.48774C25.9136 9.34411 25.8793 9.20245 25.8126 9.07524C25.7205 8.87674 25.5611 8.71729 25.3626 8.62524L15.3626 4.15024C15.2409 4.09499 15.1088 4.06641 14.9751 4.06641C14.8414 4.06641 14.7093 4.09499 14.5876 4.15024L4.5876 8.62524C4.42677 8.70617 4.29077 8.82903 4.19397 8.98084C4.09716 9.13265 4.04314 9.30778 4.0376 9.48774V20.5127C4.04694 20.6918 4.10252 20.8653 4.19891 21.0165C4.2953 21.1676 4.42922 21.2912 4.5876 21.3752L14.5876 25.8502C14.7086 25.908 14.841 25.9379 14.9751 25.9379C15.1092 25.9379 15.2416 25.908 15.3626 25.8502L25.3626 21.3752C25.507 21.3091 25.6327 21.2083 25.7287 21.0818C25.8247 20.9553 25.8878 20.8071 25.9126 20.6502ZM5.9126 10.9252L14.0376 14.5752V23.5502L5.9126 19.9127V10.9252ZM15.9126 14.5752L24.0376 10.9252V19.9127L15.9126 23.5502V14.5752ZM15.0001 6.02524L22.7126 9.48774L15.0001 12.9377L7.2876 9.48774L15.0001 6.02524Z" fill="white"/></svg></span>
                                <span class="nav-label">Inventory</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="mapping.php">
                                <span class="nav-icon"><svg width="27" height="27" viewBox="0 0 27 27" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M23.1154 4.13114C22.9044 3.92024 22.6183 3.80176 22.32 3.80176C22.0217 3.80176 21.7356 3.92024 21.5246 4.13114L15.4901 10.1656H2.25V12.4156H2.259C2.50425 18.4119 7.443 23.1988 13.5 23.1988C19.557 23.1988 24.4958 18.4119 24.741 12.4156H24.75V10.1656H18.6716L23.1154 5.72189C23.3263 5.51092 23.4448 5.22483 23.4448 4.92652C23.4448 4.62821 23.3263 4.34211 23.1154 4.13114ZM15.9491 12.4156H22.4888C22.3733 14.7218 21.3759 16.8954 19.7029 18.4869C18.0298 20.0783 15.8091 20.9658 13.5 20.9658C11.1909 20.9658 8.97019 20.0783 7.29713 18.4869C5.62406 16.8954 4.62667 14.7218 4.51125 12.4156H15.9491Z" fill="white"/></svg></span>
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
                                <span class="nav-icon"><svg width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.75 8.75C0.75 4.979 0.75 3.093 1.922 1.922C3.094 0.751 4.979 0.75 8.75 0.75H12.75C16.521 0.75 18.407 0.75 19.578 1.922C20.749 3.094 20.75 4.979 20.75 8.75C20.75 12.521 20.75 14.407 19.578 15.578C18.406 16.749 16.521 16.75 12.75 16.75H8.75C4.979 16.75 3.093 16.75 1.922 15.578C0.751 14.406 0.75 12.521 0.75 8.75Z" stroke="currentColor" stroke-width="1.5"/><path d="M8.75 12.75H4.75M12.75 12.75H11.25M0.75 6.75H20.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span>
                                <span class="nav-label">Loyalty Card</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="activitylog.php" class="active">
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
                        </li></li>
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
                            <a href="pos-settings.php">
                                <span class="nav-icon">
                                    <i class="fa-solid fa-gear"></i>
                                </span>
                                <span class="nav-label">POS Settings</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="adminsettings.php">
                                <span class="nav-icon">
                                    <i class="fa-solid fa-gear"></i>
                                </span>
                                <span class="nav-label">Admin Settings</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="logout.php" class="logout-link">
                                <span class="nav-icon">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                </span>
                                <span class="nav-label">Log Out</span>
                            </a>
                        </li>
                    </ul>               
                </div>
            </nav>
        </aside>
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- MAIN PANEL -->
        <div class="main-panel">

            <div class="top-header">
                <div class="notif-wrap">
                    <button class="icon-btn" id="notifBtn" type="button" aria-label="Inventory warnings">
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

            <!-- ACTIVITY LOGS CONTENT -->
            <div class="activity-workspace">
                <div class="activity-heading">
                    <h1>Activity Log</h1>
                    <p>View recent system activities and actions</p>
                </div>


                <div class="activity-layout">


                    <!-- Filters -->
                    <section class="activity-filters">

                        <button class="filter-item active" data-filter="all">
                            <i class="fa-solid fa-list-ul"></i>
                            <span>All Activities</span>
                        </button>
                        <button class="filter-item" data-filter="exports">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="CurrentColor"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12 15V3M16 11L12 15L8 11M2 17L2.621 19.485C2.72915 19.9177 2.97882 20.3018 3.33033 20.5763C3.68184 20.8508 4.11501 20.9999 4.561 21H19.438C19.884 20.9999 20.3172 20.8508 20.6687 20.5763C21.0202 20.3018 21.2698 19.9177 21.378 19.485L22 17"
                                    stroke="CurrentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <span>File Exports</span>
                        </button>
                        <button class="filter-item" data-filter="loyalty">
                            <svg width="25" height="25" viewBox="0 0 25 25" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M2.0835 12.5003C2.0835 8.5722 2.0835 6.60762 3.30433 5.38783C4.52516 4.16803 6.4887 4.16699 10.4168 4.16699H14.5835C18.5116 4.16699 20.4762 4.16699 21.696 5.38783C22.9158 6.60866 22.9168 8.5722 22.9168 12.5003C22.9168 16.4284 22.9168 18.393 21.696 19.6128C20.4752 20.8326 18.5116 20.8337 14.5835 20.8337H10.4168C6.4887 20.8337 4.52412 20.8337 3.30433 19.6128C2.08454 18.392 2.0835 16.4284 2.0835 12.5003Z"
                                    stroke="CurrentColor" stroke-width="1.5" />
                                <path d="M10.4168 16.667H6.25016M14.5835 16.667H13.021M2.0835 10.417H22.9168"
                                    stroke="CurrentColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>

                            <span>Loyalty Card</span>
                        </button>
                        <button class="filter-item" data-filter="menu">
                            <svg width="27" height="27" viewBox="0 0 27 27" fill="CurrentColor"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M24.7326 12.0973L23.9521 3.85488C23.9126 3.42773 23.5724 3.09023 23.1453 3.04805L14.9029 2.26758H14.8923C14.808 2.26758 14.7421 2.29395 14.692 2.34404L2.3442 14.6918C2.31976 14.7162 2.30036 14.7452 2.28713 14.7771C2.2739 14.809 2.26709 14.8432 2.26709 14.8777C2.26709 14.9122 2.2739 14.9464 2.28713 14.9783C2.30036 15.0102 2.31976 15.0392 2.3442 15.0636L11.9366 24.656C11.9867 24.7061 12.0526 24.7324 12.1238 24.7324C12.195 24.7324 12.2609 24.7061 12.311 24.656L24.6587 12.3082C24.7115 12.2528 24.7379 12.1764 24.7326 12.0973ZM12.1212 22.0087L4.99146 14.879L15.5304 4.34004L22.0431 4.95703L22.6601 11.4697L12.1212 22.0087ZM17.9298 6.75C16.651 6.75 15.6095 7.7915 15.6095 9.07031C15.6095 10.3491 16.651 11.3906 17.9298 11.3906C19.2087 11.3906 20.2502 10.3491 20.2502 9.07031C20.2502 7.7915 19.2087 6.75 17.9298 6.75ZM17.9298 9.91406C17.4631 9.91406 17.0861 9.53701 17.0861 9.07031C17.0861 8.60361 17.4631 8.22656 17.9298 8.22656C18.3965 8.22656 18.7736 8.60361 18.7736 9.07031C18.7736 9.53701 18.3965 9.91406 17.9298 9.91406Z"
                                    fill="CurrentColor" />
                            </svg>

                            <span>Menu Changes</span>
                        </button>
                        <button class="filter-item" data-filter="login">
                            <svg width="25" height="25" viewBox="0 0 25 25" fill="CurrentColor"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M20.9595 18.6426C20.499 17.5517 19.8307 16.5608 18.9918 15.7251C18.1554 14.887 17.1647 14.2187 16.0743 13.7573C16.0645 13.7524 16.0547 13.75 16.045 13.7451C17.566 12.6465 18.5547 10.8569 18.5547 8.83789C18.5547 5.49316 15.8448 2.7832 12.5001 2.7832C9.15533 2.7832 6.44537 5.49316 6.44537 8.83789C6.44537 10.8569 7.43414 12.6465 8.95514 13.7476C8.94537 13.7524 8.93561 13.7549 8.92584 13.7598C7.83209 14.2212 6.85065 14.8828 6.00836 15.7275C5.17024 16.5639 4.50201 17.5546 4.04059 18.645C3.58727 19.7125 3.34279 20.857 3.32037 22.0166C3.31972 22.0427 3.32429 22.0686 3.33382 22.0928C3.34334 22.1171 3.35762 22.1392 3.37582 22.1579C3.39403 22.1765 3.41578 22.1914 3.4398 22.2015C3.46382 22.2116 3.48962 22.2168 3.51569 22.2168H4.98053C5.08795 22.2168 5.1734 22.1313 5.17584 22.0264C5.22467 20.1416 5.98151 18.3765 7.3194 17.0386C8.70367 15.6543 10.5421 14.8926 12.5001 14.8926C14.4581 14.8926 16.2964 15.6543 17.6807 17.0386C19.0186 18.3765 19.7755 20.1416 19.8243 22.0264C19.8267 22.1338 19.9122 22.2168 20.0196 22.2168H21.4844C21.5105 22.2168 21.5363 22.2116 21.5603 22.2015C21.5843 22.1914 21.6061 22.1765 21.6243 22.1579C21.6425 22.1392 21.6568 22.1171 21.6663 22.0928C21.6758 22.0686 21.6804 22.0427 21.6797 22.0166C21.6553 20.8496 21.4136 19.7144 20.9595 18.6426ZM12.5001 13.0371C11.3795 13.0371 10.3248 12.6001 9.53131 11.8066C8.73785 11.0132 8.30084 9.9585 8.30084 8.83789C8.30084 7.71729 8.73785 6.6626 9.53131 5.86914C10.3248 5.07568 11.3795 4.63867 12.5001 4.63867C13.6207 4.63867 14.6754 5.07568 15.4688 5.86914C16.2623 6.6626 16.6993 7.71729 16.6993 8.83789C16.6993 9.9585 16.2623 11.0132 15.4688 11.8066C14.6754 12.6001 13.6207 13.0371 12.5001 13.0371Z"
                                    fill="CurrentColor" />
                            </svg>

                            <span>Log In/Log Out</span>
                        </button>
                        <button class="filter-item" data-filter="orders">
                            <svg width="30" height="30" viewBox="0 0 30 30" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M21.25 5H8.75C7.36929 5 6.25 6.11929 6.25 7.5V23.75C6.25 25.1307 7.36929 26.25 8.75 26.25H21.25C22.6307 26.25 23.75 25.1307 23.75 23.75V7.5C23.75 6.11929 22.6307 5 21.25 5Z"
                                    stroke="CurrentColor" stroke-width="2" />
                                <path d="M11.25 11.25H18.75M11.25 16.25H18.75M11.25 21.25H16.25" stroke="CurrentColor"
                                    stroke-width="2" stroke-linecap="round" />
                            </svg>

                            <span>Orders</span>
                        </button>
                        <button class="filter-item" data-filter="admin">
                            <svg width="25" height="25" viewBox="0 0 25 25" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M11.4583 14.0625C11.0889 14.0729 10.7417 14.0861 10.4167 14.1021C6.5625 14.2906 3.42083 17.5062 3.125 21.3542M18.2292 15.625C19.1979 15.625 20.0417 16.1531 20.4896 16.9375C20.7083 17.3177 20.8333 17.7583 20.8333 18.2292C20.8333 18.7 20.7083 19.1406 20.4906 19.5208C20.2629 19.9196 19.9337 20.251 19.5366 20.4815C19.1394 20.712 18.6884 20.8334 18.2292 20.8333C17.77 20.8334 17.3189 20.712 16.9218 20.4815C16.5246 20.251 16.1955 19.9196 15.9677 19.5208C15.75 19.1406 15.625 18.7 15.625 18.2292C15.625 17.7583 15.75 17.3177 15.9687 16.9375C16.4167 16.1531 17.2604 15.625 18.2292 15.625ZM20.4896 16.9375L21.875 16.1458M20.4906 19.5208L21.875 20.3125M18.2292 20.8333V22.3958M15.9687 16.9375L14.5833 16.1458M15.9677 19.5208L14.5833 20.3125M18.2292 15.625V14.0625"
                                    stroke="CurrentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path
                                    d="M11.4582 10.9378C13.7594 10.9378 15.6248 9.07235 15.6248 6.77116C15.6248 4.46997 13.7594 2.60449 11.4582 2.60449C9.15698 2.60449 7.2915 4.46997 7.2915 6.77116C7.2915 9.07235 9.15698 10.9378 11.4582 10.9378Z"
                                    stroke="CurrentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>

                            <span>POS / Admin Changes</span>
                        </button>
                        <button class="filter-item" data-filter="shift">
                            <svg width="25" height="25" viewBox="0 0 25 25" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12.5002 2.08301C6.75641 2.08301 2.0835 6.75592 2.0835 12.4997C2.0835 18.2434 6.75641 22.9163 12.5002 22.9163C18.2439 22.9163 22.9168 18.2434 22.9168 12.4997C22.9168 6.75592 18.2439 2.08301 12.5002 2.08301ZM12.5002 20.833C7.90537 20.833 4.16683 17.0945 4.16683 12.4997C4.16683 7.90488 7.90537 4.16634 12.5002 4.16634C17.095 4.16634 20.8335 7.90488 20.8335 12.4997C20.8335 17.0945 17.095 20.833 12.5002 20.833Z"
                                    fill="CurrentColor" />
                                <path
                                    d="M13.5418 7.29199H11.4585V12.9316L14.8887 16.3618L16.3616 14.8889L13.5418 12.0691V7.29199Z"
                                    fill="CurrentColor" />
                            </svg>

                            <span>Shift Management</span>
                        </button>
                    </section>
                    <!-- Activity List -->
                    <section class="activity-list-panel">
                        <p class="activity-list-title" id="activityListTitle">All Activities</p>
                        <ul class="activity-list" id="activityList">
                            <?php foreach ($activities as $activity): ?>
                                <?php
                                [$activityDate, $activityTime] = activityLogDateParts((string) $activity['occurred_at']);
                                $isShiftActivity = ($activity['category'] ?? '') === 'shift';
                                ?>
                                <li class="activity-item" data-category="<?php echo activityLogEsc((string) $activity['category']); ?>"
                                    <?php if ($isShiftActivity): ?>
                                        data-status="<?php echo activityLogEsc((string) ($activity['shift_status'] ?? 'closed')); ?>"
                                        data-cashfloat="<?php echo activityLogEsc(number_format((float) ($activity['cash_float'] ?? 0), 2, '.', '')); ?>"
                                        data-cash="<?php echo activityLogEsc(number_format((float) ($activity['cash_sales'] ?? 0), 2, '.', '')); ?>"
                                        data-qrph="<?php echo activityLogEsc(number_format((float) ($activity['qr_sales'] ?? 0), 2, '.', '')); ?>"
                                        data-total-sales="<?php echo activityLogEsc(number_format((float) ($activity['total_sales'] ?? 0), 2, '.', '')); ?>"
                                    <?php endif; ?>>
                                    <div class="activity-icon <?php echo activityLogEsc((string) $activity['icon_class']); ?>">
                                        <i class="fa-solid <?php echo activityLogEsc((string) $activity['icon_name']); ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p class="activity-title"><?php echo activityLogEsc((string) $activity['title']); ?></p>
                                        <p class="activity-sub"><?php echo activityLogEsc((string) $activity['details']); ?></p>
                                    </div>
                                    <div class="activity-time">
                                        <span><?php echo activityLogEsc($activityDate); ?></span>
                                        <span><?php echo activityLogEsc($activityTime); ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                            <li class="activity-empty<?php echo $activities ? '' : ' show'; ?>" id="activityEmpty">
                                <p class="activity-empty-sub">No recorded activities for this category.</p>
                            </li>
                        </ul>

                        <?php if (false): ?>
                        <ul class="activity-list" id="activityListMock">

                            <li class="activity-item" data-category="login">
                                <div class="activity-icon icon-blue"><i class="fa-solid fa-right-to-bracket"></i></div>
                                <div class="activity-content">
                                    <p class="activity-title">Log In</p>
                                    <p class="activity-sub">Alyssa Dionisio logged in to the system</p>
                                </div>
                                <div class="activity-time">
                                    <span>Sep 20, 2025</span>
                                    <span>10:24 AM</span>
                                </div>
                            </li>

                            <li class="activity-item" data-category="login">
                                <div class="activity-icon icon-red"><i class="fa-solid fa-right-from-bracket"></i></div>
                                <div class="activity-content">
                                    <p class="activity-title">Log Out</p>
                                    <p class="activity-sub">Alyssa Dionisio logged out from the system</p>
                                </div>
                                <div class="activity-time">
                                    <span>Sep 20, 2025</span>
                                    <span>10:24 AM</span>
                                </div>
                            </li>

                            <li class="activity-item" data-category="orders">
                                <div class="activity-icon icon-green"><i class="fa-solid fa-file-lines"></i></div>
                                <div class="activity-content">
                                    <p class="activity-title">New Order</p>
                                    <p class="activity-sub">Order #123 was created by POS Baliuag</p>
                                </div>
                                <div class="activity-time">
                                    <span>Sep 20, 2025</span>
                                    <span>10:24 AM</span>
                                </div>
                            </li>

                            <li class="activity-item" data-category="loyalty">
                                <div class="activity-icon icon-olive"><i class="fa-solid fa-award"></i></div>
                                <div class="activity-content">
                                    <p class="activity-title">Loyalty Card Claimed</p>
                                    <p class="activity-sub">Customer (ID: 12345) claimed a free drink</p>
                                </div>
                                <div class="activity-time">
                                    <span>Sep 20, 2025</span>
                                    <span>10:24 AM</span>
                                </div>
                            </li>

                            <li class="activity-item" data-category="menu">
                                <div class="activity-icon icon-orange"><i class="fa-solid fa-mug-hot"></i></div>
                                <div class="activity-content">
                                    <p class="activity-title">Item Added</p>
                                    <p class="activity-sub">Milo (Non - Coffee) was added to the menu</p>
                                </div>
                                <div class="activity-time">
                                    <span>Sep 20, 2025</span>
                                    <span>10:24 AM</span>
                                </div>
                            </li>

                            <li class="activity-item" data-category="exports">
                                <div class="activity-icon icon-purple"><i class="fa-solid fa-file-arrow-down"></i></div>
                                <div class="activity-content">
                                    <p class="activity-title">File Exported</p>
                                    <p class="activity-sub">Sales Report (weekly) was exported by Admin</p>
                                </div>
                                <div class="activity-time">
                                    <span>Sep 20, 2025</span>
                                    <span>10:24 AM</span>
                                </div>
                            </li>

                            <li class="activity-item" data-category="menu">
                                <div class="activity-icon icon-red"><i class="fa-solid fa-trash"></i></div>
                                <div class="activity-content">
                                    <p class="activity-title">Item Deleted</p>
                                    <p class="activity-sub">Caramel Frappe was removed from the menu</p>
                                </div>
                                <div class="activity-time">
                                    <span>Sep 20, 2025</span>
                                    <span>10:24 AM</span>
                                </div>
                            </li>

                            <li class="activity-empty" id="activityEmpty">
                                <p class="activity-empty-sub">Nothing to show</p>
                            </li>
                            <li class="activity-item" data-category="shift" data-status="open"
                                data-cashfloat="1000">
                                <div class="activity-icon icon-maroon"><i class="fa-solid fa-clock"></i></div>
                                <div class="activity-content">
                                    <p class="activity-title">Shift Opened</p>
                                    <p class="activity-sub">Boycold Cafe POS was opened with P1000 cash float</p>
                                </div>
                                <div class="activity-time">
                                    <span>Sep 20, 2025</span>
                                    <span>10:24 AM</span>
                                </div>
                            </li>

                            <li class="activity-item" data-category="shift" data-cashfloat="1000" data-cash="5400"
                                data-qrph="3200" data-payin="500" data-payout="300">
                                <div class="activity-icon icon-maroon"><i class="fa-solid fa-clock"></i></div>
                                <div class="activity-content">
                                    <p class="activity-title">Shift Closed</p>
                                    <p class="activity-sub">Boycold Cafe POS was opened with P1000 cash float</p>
                                </div>
                                <div class="activity-time">
                                    <span>Sep 20, 2025</span>
                                    <span>10:24 AM</span>
                                </div>
                            </li>

                        </ul>
                        <?php endif; ?>
                    </section>
                </div>

            </div>

            <!-- LOG OUT MODAL -->
            <div class="logout-modal" id="logoutModal">

                <div class="logout-modal-box">

                    <button class="logout-close" id="logoutClose" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                    <div class="logout-logo">
                        <img src="/public/assets/icons/LOGO.png" alt="BoyCold Cafe">
                    </div>

                    <h2>
                        Are you sure you want to log<br>
                        out your account?
                    </h2>

                    <div class="logout-actions">

                        <button class="logout-no" id="logoutNo">
                            No
                        </button>

                        <button class="logout-yes" id="logoutYes">
                            Yes
                        </button>

                    </div>

                </div>

            </div>
            <!-- SHIFT SUMMARY MODAL -->
            <div class="shift-modal" id="shiftModal">
                <div class="shift-modal-box">

                    <button class="shift-modal-close" id="shiftModalClose" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                    <h2>Shift Summary</h2>

                    <div class="shift-row">
                        <span class="shift-label">Cash Float</span>
                        <span class="shift-value" id="shiftCashFloat">₱0.00</span>
                    </div>

                    <div class="shift-divider" id="shiftDetailsDivider"></div>

                    <div id="shiftDetails">
                        <div class="shift-row">
                            <span class="shift-label">Cash Sales</span>
                            <span class="shift-value" id="shiftCashSales">₱0.00</span>
                        </div>
                        <div class="shift-row">
                            <span class="shift-label">QR PH Sales</span>
                            <span class="shift-value" id="shiftQrSales">₱0.00</span>
                        </div>
                        <div class="shift-row shift-row-total">
                            <span class="shift-label">Total Sales</span>
                            <span class="shift-value" id="shiftTotalSales">₱0.00</span>
                        </div>

                        <div class="shift-divider"></div>

                        <div class="shift-row">
                            <span class="shift-label">Pay In</span>
                            <span class="shift-value shift-value-positive" id="shiftPayIn">+ ₱0.00</span>
                        </div>
                        <div class="shift-row">
                            <span class="shift-label">Pay Out</span>
                            <span class="shift-value shift-value-negative" id="shiftPayOut">− ₱0.00</span>
                        </div>
                        <div class="shift-row">
                            <span class="shift-label">Less Cash Float</span>
                            <span class="shift-value shift-value-negative" id="shiftLessFloat">− ₱0.00</span>
                        </div>

                        <div class="shift-divider"></div>

                        <div class="shift-row shift-row-net">
                            <span class="shift-label">Net Total</span>
                            <span class="shift-value" id="shiftNetTotal">₱0.00</span>
                        </div>
                    </div>

                    <p class="shift-open-note" id="shiftOpenNote">This shift is still open. Sales will be totaled once it's closed.</p>

                    <div class="shift-actions">
                        <button class="shift-close-btn" id="closeShiftBtn">Close Shift</button>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- SCRIPTS -->
    <?php if (false): ?>
    <script>
        // Logout Modal Elements
        const logoutBtn = document.getElementById("logoutBtn");
        const logoutModal = document.getElementById("logoutModal");
        const logoutNo = document.getElementById("logoutNo");
        const logoutYes = document.getElementById("logoutYes");
        const logoutClose = document.getElementById("logoutClose");

        // Helper function to close the logout modal and clear 'active' from logoutBtn
        function closeLogoutModal() {
            if (logoutModal) {
                logoutModal.classList.remove("show");
                logoutModal.blur();
            }
            if (logoutBtn) {
                logoutBtn.classList.remove("active");
            }
        }

        if (logoutBtn) {
            logoutBtn.addEventListener("click", function (e) {
                e.preventDefault();
                // Optionally toggle visual highlight only while modal is active:
                // logoutBtn.classList.add("active");
                if (logoutModal) {
                    logoutModal.classList.add("show");
                }
            });
        }

        if (logoutNo) {
            logoutNo.addEventListener("click", function () {
                closeLogoutModal();
            });
        }

        if (logoutClose) {
            logoutClose.addEventListener("click", function () {
                closeLogoutModal();
            });
        }

        if (logoutYes) {
            logoutYes.addEventListener("click", function () {
                window.location.href = "adminlogin.html";
            });
        }

        /* Close when clicking outside the popup modal */
        if (logoutModal) {
            logoutModal.addEventListener("click", function (e) {
                if (e.target === logoutModal) {
                    closeLogoutModal();
                }
            });
        }
        const filterBtns = document.querySelectorAll(".filter-item");
        const activityItems = document.querySelectorAll("#activityList .activity-item");
        const activityEmpty = document.getElementById("activityEmpty");
        const activityEmptyTitle = activityEmpty.querySelector(".activity-empty-title")
        const activityListTitle = document.getElementById("activityListTitle");

        filterBtns.forEach(btn => {
            btn.addEventListener("click", () => {
                filterBtns.forEach(b => b.classList.remove("active"));
                btn.classList.add("active");

                const filter = btn.dataset.filter;
                activityItems.forEach(item => {
                    const show = filter === "all" || item.dataset.category === filter;
                    item.style.display = show ? "flex" : "none";
                });
            });
        });
        function applyFilter(btn) {
            filterBtns.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");

            const filter = btn.dataset.filter;
            const label = btn.querySelector("span").textContent;
            let visible = 0;

            activityItems.forEach(item => {
                const show = filter === "all" || item.dataset.category === filter;
                item.style.display = show ? "flex" : "none";
                if (show) visible++;
            });

            activityListTitle.textContent = label;
            activityEmpty.classList.toggle("show", visible === 0);
        }

        filterBtns.forEach(btn => {
            btn.addEventListener("click", () => applyFilter(btn));
        });

        // Set the correct title on page load (defaults to whichever button has .active)
        applyFilter(document.querySelector(".filter-item.active"));
        // SHIFT SUMMARY MODAL
        const shiftModal = document.getElementById("shiftModal");
        const shiftModalClose = document.getElementById("shiftModalClose");
        const closeShiftBtn = document.getElementById("closeShiftBtn");

        const shiftCashFloatEl = document.getElementById("shiftCashFloat");
        const shiftCashSalesEl = document.getElementById("shiftCashSales");
        const shiftQrSalesEl = document.getElementById("shiftQrSales");
        const shiftTotalSalesEl = document.getElementById("shiftTotalSales");
        const shiftPayInEl = document.getElementById("shiftPayIn");
        const shiftPayOutEl = document.getElementById("shiftPayOut");
        const shiftLessFloatEl = document.getElementById("shiftLessFloat");
        const shiftNetTotalEl = document.getElementById("shiftNetTotal");

        function formatPeso(amount) {
            return "₱" + amount.toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function openShiftModal(item) {
            const status = item.dataset.status || "closed";
            const cashFloat = parseFloat(item.dataset.cashfloat) || 0;

            shiftCashFloatEl.textContent = formatPeso(cashFloat);

            const shiftDetails = document.getElementById("shiftDetails");
            const shiftDetailsDivider = document.getElementById("shiftDetailsDivider");
            const shiftOpenNote = document.getElementById("shiftOpenNote");

            if (status === "open") {
                // Only the cash float counts for an open shift — nothing else to total yet
                shiftDetails.style.display = "block";
                shiftDetailsDivider.style.display = "block";
                shiftOpenNote.style.display = "block";
            } else {
                const cashSales = parseFloat(item.dataset.cash) || 0;
                const qrSales = parseFloat(item.dataset.qrph) || 0;
                const payIn = parseFloat(item.dataset.payin) || 0;
                const payOut = parseFloat(item.dataset.payout) || 0;

                const totalSales = cashSales + qrSales;
                const netTotal = totalSales + payIn - payOut - cashFloat;

                shiftCashSalesEl.textContent = formatPeso(cashSales);
                shiftQrSalesEl.textContent = formatPeso(qrSales);
                shiftTotalSalesEl.textContent = formatPeso(totalSales);
                shiftPayInEl.textContent = "+ " + formatPeso(payIn);
                shiftPayOutEl.textContent = "− " + formatPeso(payOut);
                shiftLessFloatEl.textContent = "− " + formatPeso(cashFloat);
                shiftNetTotalEl.textContent = formatPeso(netTotal);

                shiftDetails.style.display = "block";
                shiftDetailsDivider.style.display = "block";
                shiftOpenNote.style.display = "none";
            }

            shiftModal.classList.add("show");
        }

        function closeShiftModal() {
            shiftModal.classList.remove("show");
        }

        activityItems.forEach(item => {
            if (item.dataset.category === "shift") {
                item.addEventListener("click", () => openShiftModal(item));
            }
        });

        if (shiftModalClose) {
            shiftModalClose.addEventListener("click", closeShiftModal);
        }

        if (shiftModal) {
            shiftModal.addEventListener("click", (e) => {
                if (e.target === shiftModal) closeShiftModal();
            });
        }

        if (closeShiftBtn) {
            closeShiftBtn.addEventListener("click", () => {
                // Hook this up to your actual close-shift logic/endpoint later
                closeShiftModal();
            });
        }
    </script>
    <?php endif; ?>
    <script src="admin-js/admin-responsive.js"></script>
    <script src="admin-js/logout-modal.js"></script>
    <script>
        (() => {
            const filterButtons = [...document.querySelectorAll('.filter-item')];
            const activityItems = [...document.querySelectorAll('#activityList .activity-item')];
            const activityEmpty = document.getElementById('activityEmpty');
            const activityListTitle = document.getElementById('activityListTitle');

            function applyFilter(button) {
                if (!button) return;

                const category = button.dataset.filter || 'all';
                const label = button.querySelector('span')?.textContent || 'Activities';
                let visibleCount = 0;

                filterButtons.forEach(item => item.classList.toggle('active', item === button));
                activityItems.forEach(item => {
                    const shouldShow = category === 'all' || item.dataset.category === category;
                    item.hidden = !shouldShow;
                    if (shouldShow) visibleCount++;
                });

                activityListTitle.textContent = label;
                activityEmpty.classList.toggle('show', visibleCount === 0);
            }

            filterButtons.forEach(button => button.addEventListener('click', () => applyFilter(button)));
            applyFilter(document.querySelector('.filter-item.active'));

            const shiftModal = document.getElementById('shiftModal');
            const shiftModalClose = document.getElementById('shiftModalClose');
            const shiftDetails = document.getElementById('shiftDetails');
            const shiftDetailsDivider = document.getElementById('shiftDetailsDivider');
            const shiftOpenNote = document.getElementById('shiftOpenNote');
            const closeShiftActions = shiftModal?.querySelector('.shift-actions');
            const peso = amount => '₱' + Number(amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            if (closeShiftActions) closeShiftActions.hidden = true;

            function openShiftModal(item) {
                if (!shiftModal) return;

                const status = item.dataset.status || 'closed';
                const cashFloat = Number(item.dataset.cashfloat || 0);
                const cashSales = Number(item.dataset.cash || 0);
                const qrSales = Number(item.dataset.qrph || 0);
                const totalSales = Number(item.dataset.totalSales || (cashSales + qrSales));

                document.getElementById('shiftCashFloat').textContent = peso(cashFloat);
                document.getElementById('shiftCashSales').textContent = peso(cashSales);
                document.getElementById('shiftQrSales').textContent = peso(qrSales);
                document.getElementById('shiftTotalSales').textContent = peso(totalSales);
                document.getElementById('shiftPayIn').textContent = '+ ' + peso(0);
                document.getElementById('shiftPayOut').textContent = '− ' + peso(0);
                document.getElementById('shiftLessFloat').textContent = '− ' + peso(cashFloat);
                document.getElementById('shiftNetTotal').textContent = peso(totalSales - cashFloat);

                const isOpen = status === 'open';
                shiftDetails.hidden = isOpen;
                shiftDetailsDivider.hidden = isOpen;
                shiftOpenNote.hidden = !isOpen;
                shiftModal.classList.add('show');
            }

            function closeShiftModal() {
                shiftModal?.classList.remove('show');
            }

            activityItems
                .filter(item => item.dataset.category === 'shift')
                .forEach(item => item.addEventListener('click', () => openShiftModal(item)));
            shiftModalClose?.addEventListener('click', closeShiftModal);
            shiftModal?.addEventListener('click', event => {
                if (event.target === shiftModal) closeShiftModal();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') closeShiftModal();
            });
        })();
    </script>
</body>

</html>
