<?php
require_once __DIR__ . '/admin_guard.php';
require_once '../config/db_config.php';
require_once '../config/analytics_reporting_service.php';

function analyticsReportPercent(float|int|string|null $value): string
{
    $number = (float) $value;
    return number_format(abs($number), 2, '.', '') . '%';
}

/**
 * @param mixed $value
 */
function analyticsDateOrDefault($value, string $fallback): string
{
    if (!is_string($value)) {
        return $fallback;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
}

// Keep branch and date defaults identical to admin/dashboard.php.
$sessionBranchId = (int) ($_SESSION['branch_id'] ?? 0);
$branchId = isset($_GET['branch_id'])
    ? (string) $_GET['branch_id']
    : ($sessionBranchId > 0 ? (string) $sessionBranchId : 'all');

$startDate = (string) ($_GET['start_date'] ?? '');
$endDate = (string) ($_GET['end_date'] ?? '');
if ($startDate === '' && $endDate === '') {
    $latestSalesDate = boycold_analytics_latest_sale_date($connect, $branchId) ?? '';
    $endDate = $latestSalesDate !== '' ? $latestSalesDate : date('Y-m-d');
    $startDate = date('Y-m-d', strtotime($endDate . ' -6 days'));
}

if ($startDate === '') $startDate = $endDate;
if ($endDate === '') $endDate = $startDate;
$defaultStartDate = $startDate;
$defaultEndDate = $endDate;
if ($startDate > $endDate) {
    [$startDate, $endDate] = [$endDate, $startDate];
}

// Compare to the immediately preceding range of the same length.  This keeps
// both the KPI trend and Sales Overview comparison correct for custom ranges.
$periodDays = (int) ((strtotime($endDate) - strtotime($startDate)) / 86400) + 1;
$previousPeriodEnd = (new DateTimeImmutable($startDate))->modify('-1 day');
$prevEndDate = $previousPeriodEnd->format('Y-m-d');
$prevStartDate = $previousPeriodEnd->modify('-' . max(0, $periodDays - 1) . ' days')->format('Y-m-d');
$selectedPeriodLabel = $startDate === $endDate
    ? date('M j, Y', strtotime($startDate))
    : date('M j', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate));

function analyticsPreviousRange(string $rangeStart, string $rangeEnd): array
{
    $periodDays = (int) ((strtotime($rangeEnd) - strtotime($rangeStart)) / 86400) + 1;
    $previousEnd = (new DateTimeImmutable($rangeStart))->modify('-1 day');
    return [
        $previousEnd->modify('-' . max(0, $periodDays - 1) . ' days')->format('Y-m-d'),
        $previousEnd->format('Y-m-d'),
    ];
}

function analyticsRangeLabel(string $rangeStart, string $rangeEnd): string
{
    return $rangeStart === $rangeEnd
        ? date('M j, Y', strtotime($rangeStart))
        : date('M j', strtotime($rangeStart)) . ' - ' . date('M j, Y', strtotime($rangeEnd));
}

    $previousPeriodLabel = analyticsRangeLabel($prevStartDate, $prevEndDate);

$timeStartDate = analyticsDateOrDefault($_GET['time_start_date'] ?? null, $defaultStartDate);
$timeEndDate = analyticsDateOrDefault($_GET['time_end_date'] ?? null, $defaultEndDate);
if ($timeStartDate > $timeEndDate) {
    [$timeStartDate, $timeEndDate] = [$timeEndDate, $timeStartDate];
}
$peakStartDate = analyticsDateOrDefault($_GET['peak_start_date'] ?? null, $defaultStartDate);
$peakEndDate = analyticsDateOrDefault($_GET['peak_end_date'] ?? null, $defaultEndDate);
if ($peakStartDate > $peakEndDate) {
    [$peakStartDate, $peakEndDate] = [$peakEndDate, $peakStartDate];
}
$timeRangeLabel = analyticsRangeLabel($timeStartDate, $timeEndDate);
$peakRangeLabel = analyticsRangeLabel($peakStartDate, $peakEndDate);
[$timePrevStartDate, $timePrevEndDate] = analyticsPreviousRange($timeStartDate, $timeEndDate);
[$peakPrevStartDate, $peakPrevEndDate] = analyticsPreviousRange($peakStartDate, $peakEndDate);

// Fetch available branches
$branchesQuery = "SELECT id, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name";
$branchesResult = $connect->query($branchesQuery);
$branches = [];
while ($row = $branchesResult->fetch_assoc()) {
    $branches[] = $row;
}

// Fetch analytics data from database
function getLegacyAnalyticsData(mysqli $connect, string $startDate, string $endDate, string $prevStartDate, string $prevEndDate, string $branchId) {
    // Branch filter condition
    $branchCondition = '';
    if ($branchId !== 'all') {
        $branchCondition = 'AND branch_id = ?';
    }
    
    // Total Sales
    $salesQuery = "SELECT
        COALESCE(SUM(total), 0) as total_sales,
        COUNT(*) as total_orders
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND (status IN ('completed', 'delivered') OR payment_status = 'paid')
        AND status != 'cancelled'
        AND payment_status NOT IN ('failed', 'expired', 'cancelled')
        $branchCondition";
    
    $stmt = $connect->prepare($salesQuery);
    if ($branchId !== 'all') {
        $stmt->bind_param('ssi', $startDate, $endDate, $branchId);
    } else {
        $stmt->bind_param('ss', $startDate, $endDate);
    }
    $stmt->execute();
    $currentData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Previous period data for comparison
    $stmt = $connect->prepare($salesQuery);
    if ($branchId !== 'all') {
        $stmt->bind_param('ssi', $prevStartDate, $prevEndDate, $branchId);
    } else {
        $stmt->bind_param('ss', $prevStartDate, $prevEndDate);
    }
    $stmt->execute();
    $prevData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Average Order Value
    $avgOrderValue = $currentData['total_orders'] > 0 
        ? $currentData['total_sales'] / $currentData['total_orders'] 
        : 0;
    $prevAvgOrderValue = $prevData['total_orders'] > 0 
        ? $prevData['total_sales'] / $prevData['total_orders'] 
        : 0;
    
    // New Customers comes from registrations in admin/customers.php.
    $customersQuery = "SELECT COUNT(*) AS new_customers
        FROM users
        WHERE DATE(created_at) BETWEEN ? AND ?";
    
    $stmt = $connect->prepare($customersQuery);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $currentCustomers = $stmt->get_result()->fetch_assoc()['new_customers'];
    $stmt->close();
    
    $stmt = $connect->prepare($customersQuery);
    $stmt->bind_param('ss', $prevStartDate, $prevEndDate);
    $stmt->execute();
    $prevCustomers = $stmt->get_result()->fetch_assoc()['new_customers'];
    $stmt->close();
    
    // Top Selling Items
    $topItemsQuery = "SELECT 
        oi.product_name,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.line_total) as total_revenue
        FROM order_items oi
        INNER JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        AND (o.status IN ('completed', 'delivered') OR o.payment_status = 'paid')
        AND o.status <> 'cancelled'
        AND o.payment_status NOT IN ('failed', 'expired', 'cancelled')
        $branchCondition
        GROUP BY oi.product_name
        ORDER BY total_quantity DESC, oi.product_name ASC
        LIMIT 5";
    
    $stmt = $connect->prepare($topItemsQuery);
    if ($branchId !== 'all') {
        $stmt->bind_param('ssi', $startDate, $endDate, $branchId);
    } else {
        $stmt->bind_param('ss', $startDate, $endDate);
    }
    $stmt->execute();
    $topItems = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $topItems[] = $row;
    }
    $stmt->close();
    
    // Sales by Time of Day
    $timeOfDayQuery = "SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as orders
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND status != 'cancelled'
        $branchCondition
        GROUP BY HOUR(created_at)
        ORDER BY hour";
    
    $stmt = $connect->prepare($timeOfDayQuery);
    if ($branchId !== 'all') {
        $stmt->bind_param('ssi', $startDate, $endDate, $branchId);
    } else {
        $stmt->bind_param('ss', $startDate, $endDate);
    }
    $stmt->execute();
    $timeOfDayData = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $timeOfDayData[] = $row;
    }
    $stmt->close();
    
    // Daily Sales for Chart
    $dailySalesQuery = "SELECT 
        DATE(created_at) as sale_date,
        COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total ELSE 0 END), 0) as daily_sales
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        $branchCondition
        GROUP BY DATE(created_at)
        ORDER BY sale_date";
    
    $stmt = $connect->prepare($dailySalesQuery);
    if ($branchId !== 'all') {
        $stmt->bind_param('ssi', $startDate, $endDate, $branchId);
    } else {
        $stmt->bind_param('ss', $startDate, $endDate);
    }
    $stmt->execute();
    $dailySales = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $dailySales[] = $row;
    }
    $stmt->close();
    
    // Previous Period Daily Sales for Chart
    $stmt = $connect->prepare($dailySalesQuery);
    if ($branchId !== 'all') {
        $stmt->bind_param('ssi', $prevStartDate, $prevEndDate, $branchId);
    } else {
        $stmt->bind_param('ss', $prevStartDate, $prevEndDate);
    }
    $stmt->execute();
    $prevDailySales = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $prevDailySales[] = $row;
    }
    $stmt->close();
    
    // Calculate trends
    $salesTrend = $prevData['total_sales'] > 0 
        ? (($currentData['total_sales'] - $prevData['total_sales']) / $prevData['total_sales']) * 100 
        : 0;
    $avgOrderTrend = $prevAvgOrderValue > 0 
        ? (($avgOrderValue - $prevAvgOrderValue) / $prevAvgOrderValue) * 100 
        : 0;
    $customersTrend = $prevCustomers > 0 
        ? (($currentCustomers - $prevCustomers) / $prevCustomers) * 100 
        : 0;
    
    return [
        'total_sales' => $currentData['total_sales'],
        'total_orders' => $currentData['total_orders'],
        'avg_order_value' => $avgOrderValue,
        'new_customers' => $currentCustomers,
        'top_items' => $topItems,
        'time_of_day' => $timeOfDayData,
        'daily_sales' => $dailySales,
        'prev_daily_sales' => $prevDailySales,
        'sales_trend' => $salesTrend,
        'avg_order_trend' => $avgOrderTrend,
        'customers_trend' => $customersTrend
    ];
}

/**
 * Page-level analytics, built from the same snapshot service used by
 * Forecasting. This prevents a reporting filter/status rule from drifting
 * between the two admin screens.
 */
function getAnalyticsData(mysqli $connect, string $startDate, string $endDate, string $prevStartDate, string $prevEndDate, string $branchId): array
{
    $currentReport = boycold_analytics_period_snapshot($connect, $startDate, $endDate, $branchId);
    $previousReport = boycold_analytics_period_snapshot($connect, $prevStartDate, $prevEndDate, $branchId);
    $currentData = $currentReport['summary'];
    $prevData = $previousReport['summary'];

    $avgOrderValue = $currentData['total_orders'] > 0
        ? $currentData['total_sales'] / $currentData['total_orders']
        : 0;
    $prevAvgOrderValue = $prevData['total_orders'] > 0
        ? $prevData['total_sales'] / $prevData['total_orders']
        : 0;

    // New customers is account-registration data, not a sale, so it remains
    // separate from the shared sales snapshot.
    $customersQuery = "SELECT COUNT(*) AS new_customers
        FROM users
        WHERE DATE(created_at) BETWEEN ? AND ?";
    $statement = $connect->prepare($customersQuery);
    $statement->bind_param('ss', $startDate, $endDate);
    $statement->execute();
    $currentCustomers = (int) ($statement->get_result()->fetch_assoc()['new_customers'] ?? 0);
    $statement->close();

    $statement = $connect->prepare($customersQuery);
    $statement->bind_param('ss', $prevStartDate, $prevEndDate);
    $statement->execute();
    $prevCustomers = (int) ($statement->get_result()->fetch_assoc()['new_customers'] ?? 0);
    $statement->close();

    $salesTrend = $prevData['total_sales'] > 0
        ? (($currentData['total_sales'] - $prevData['total_sales']) / $prevData['total_sales']) * 100
        : 0;
    $avgOrderTrend = $prevAvgOrderValue > 0
        ? (($avgOrderValue - $prevAvgOrderValue) / $prevAvgOrderValue) * 100
        : 0;
    $customersTrend = $prevCustomers > 0
        ? (($currentCustomers - $prevCustomers) / $prevCustomers) * 100
        : 0;

    return [
        'total_sales' => $currentData['total_sales'],
        'total_orders' => $currentData['total_orders'],
        'avg_order_value' => $avgOrderValue,
        'new_customers' => $currentCustomers,
        'top_items' => $currentReport['top_items'],
        'time_of_day' => $currentReport['time_of_day'],
        'daily_sales' => $currentReport['daily_sales'],
        'prev_daily_sales' => $previousReport['daily_sales'],
        'sales_trend' => $salesTrend,
        'avg_order_trend' => $avgOrderTrend,
        'customers_trend' => $customersTrend,
    ];
}

$analytics = getAnalyticsData($connect, $startDate, $endDate, $prevStartDate, $prevEndDate, $branchId);
$timeAnalytics = getAnalyticsData($connect, $timeStartDate, $timeEndDate, $timePrevStartDate, $timePrevEndDate, $branchId);
$peakAnalytics = getAnalyticsData($connect, $peakStartDate, $peakEndDate, $peakPrevStartDate, $peakPrevEndDate, $branchId);

if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode([
        'success' => true,
        'total_sales' => (float) $analytics['total_sales'],
        'total_orders' => (int) $analytics['total_orders'],
        'analytics' => $analytics,
        'time_analytics' => $timeAnalytics,
        'peak_analytics' => $peakAnalytics,
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-css/data-analytics.css">
    <link rel="stylesheet" href="admin-css/dashboard.css">
    <link rel="stylesheet" href="admin-css/admin-sidebar.css">
    <link rel="stylesheet" href="admin-css/admin-responsive.css">
    <link rel="icon" href="../pos/img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <title>BoyCold - Data Analytics</title>
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
                            <a href="data-analytics.php" class="active">
                                <span class="nav-icon2"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.8601 4.39V19.39C15.8601 21.06 17.0001 22 18.2501 22C19.3901 22 20.6401 21.21 20.6401 19.39V4.5C20.6401 2.96 19.5001 2 18.2501 2C17.0001 2 15.8601 3.06 15.8601 4.39ZM9.61011 12V19.39C9.61011 21.07 10.7701 22 12.0001 22C13.1401 22 14.3901 21.21 14.3901 19.39V12.11C14.3901 10.57 13.2501 9.61 12.0001 9.61C10.7501 9.61 9.61011 10.67 9.61011 12ZM5.75011 17.23C7.07011 17.23 8.14011 18.3 8.14011 19.61C8.14011 20.2439 7.88831 20.8518 7.44009 21.3C6.99188 21.7482 6.38398 22 5.75011 22C5.11624 22 4.50833 21.7482 4.06012 21.3C3.61191 20.8518 3.36011 20.2439 3.36011 19.61C3.36011 18.3 4.43011 17.23 5.75011 17.23Z" fill="white"/></svg></span>
                                <span class="nav-label">Data Analytics</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo htmlspecialchars('forecasting.php?' . http_build_query([
                                'branch_id' => $branchId,
                                'demand_start_date' => $startDate,
                                'demand_end_date' => $endDate,
                            ]), ENT_QUOTES); ?>">
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
                            <a href="adminsettings.php">
                                <span class="nav-icon">
                                    <i class="fa-solid fa-gear"></i>
                                </span>
                                <span class="nav-label">Settings</span>
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
                <div class="notif-wrap"><button class="icon-btn" id="notifBtn" type="button" aria-label="Inventory warnings" aria-expanded="false"><i class="fa-solid fa-triangle-exclamation"></i></button></div>
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
            <div class="page-content">

                <div class="page-heading">
                    <h1 class="page-title">Data Analytics</h1>
                    <p class="page-subtitle">Track your cafe's performance and understand your business better</p>
                    
                    <div class="analytics-filters">
                        <div class="branch-filter">
                            <div class="branch-dropdown">
                                <button type="button" class="branch-trigger">
                                    <span class="branch-trigger-label"><?php echo $branchId === 'all' ? 'All Branches' : ($branches[array_search($branchId, array_column($branches, 'id'))]['branch_name'] ?? 'All Branches'); ?></span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                                <div class="branch-menu">
                                    <button type="button" class="branch-option" data-value="all">All Branches</button>
                                    <?php foreach ($branches as $branch): ?>
                                        <button type="button" class="branch-option" data-value="<?php echo $branch['id']; ?>">
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <button class="export-btn" id="exportAnalyticsBtn" type="button">
                            <i class="fa-solid fa-arrow-down-to-line"></i>
                            Export Report
                        </button>
                    </div>
                </div>

                <div class="stats-grid">

                    <div class="stat-card">
                        <span class="stat-icon stat-icon-peach">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M4 18.5V9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M10 18.5V5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M16 18.5V12.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M22 18.5V7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M3 20.5H21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div class="stat-body">
                            <div class="stat-card-top">
                                <span class="stat-label">Total Sales</span>
                            </div>
                            <div class="stat-value" id="totalSalesValue">₱ <?php echo number_format($analytics['total_sales'], 2); ?></div>
                            <div class="stat-trend <?php echo $analytics['sales_trend'] >= 0 ? 'trend-up' : 'trend-down'; ?>">
                                <i class="fa-solid fa-arrow-<?php echo $analytics['sales_trend'] >= 0 ? 'up' : 'down'; ?>"></i>
                                <span class="trend-percent"><?php echo analyticsReportPercent($analytics['sales_trend']); ?></span>
                                <span class="trend-note">vs <?php echo htmlspecialchars($previousPeriodLabel, ENT_QUOTES); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <span class="stat-icon stat-icon-red">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M3 9.5H21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M8 14.5H10.5V16H8V14.5Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <div class="stat-body">
                            <div class="stat-card-top">
                                <span class="stat-label">Average Order Value</span>
                            </div>
                            <div class="stat-value">₱ <?php echo number_format($analytics['avg_order_value'], 2); ?></div>
                            <div class="stat-trend <?php echo $analytics['avg_order_trend'] >= 0 ? 'trend-up' : 'trend-down'; ?>">
                                <i class="fa-solid fa-arrow-<?php echo $analytics['avg_order_trend'] >= 0 ? 'up' : 'down'; ?>"></i>
                                <span class="trend-percent"><?php echo analyticsReportPercent($analytics['avg_order_trend']); ?></span>
                                <span class="trend-note">vs <?php echo htmlspecialchars($previousPeriodLabel, ENT_QUOTES); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <span class="stat-icon stat-icon-purple">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M3.5 18C4.2 15.7 6.4 14 9 14C11.6 14 13.8 15.7 14.5 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M15.5 8.5H19.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M17.5 6.5V10.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div class="stat-body">
                            <div class="stat-card-top">
                                <span class="stat-label">New Customers</span>
                            </div>
                            <div class="stat-value"><?php echo $analytics['new_customers']; ?></div>
                            <div class="stat-trend <?php echo $analytics['customers_trend'] >= 0 ? 'trend-up' : 'trend-down'; ?>">
                                <i class="fa-solid fa-arrow-<?php echo $analytics['customers_trend'] >= 0 ? 'up' : 'down'; ?>"></i>
                                <span class="trend-percent"><?php echo analyticsReportPercent($analytics['customers_trend']); ?></span>
                                <span class="trend-note">vs <?php echo htmlspecialchars($previousPeriodLabel, ENT_QUOTES); ?></span>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="charts-row">

                    <div class="chart-card" id="salesOverviewCard">
                        <div class="chart-card-header">
                            <h2 class="chart-card-title">Sales Overview</h2>
                            <div class="chart-period-select">
                                <div class="date-range-picker">
                                    <div class="period-dropdown" data-range-key="sales">
                                        <button type="button" class="period-trigger">
                                            <span class="period-trigger-label"><?php echo htmlspecialchars($selectedPeriodLabel, ENT_QUOTES); ?></span>
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </button>
                                        <div class="period-menu">
                                            <button type="button" class="period-option" data-value="today">Today</button>
                                            <button type="button" class="period-option" data-value="this-week">This Week</button>
                                            <button type="button" class="period-option" data-value="last-week">Last Week</button>
                                            <button type="button" class="period-option" data-value="this-month">This Month</button>
                                            <button type="button" class="period-option" data-value="last-month">Last Month</button>
                                            <button type="button" class="period-option" data-value="custom">Custom Range</button>
                                        </div>
                                        <div class="date-range-dropdown">
                                            <div class="calendar-header">
                                                <button type="button" class="calendar-nav prev-month" aria-label="Previous month">
                                                    <i class="fa-solid fa-chevron-left"></i>
                                                </button>
                                                <span class="calendar-month"></span>
                                                <button type="button" class="calendar-nav next-month" aria-label="Next month">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </button>
                                            </div>

                                            <div class="calendar-weekdays">
                                                <span>Sun</span><span>Mon</span><span>Tue</span>
                                                <span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                                            </div>

                                            <div class="calendar-days"></div>

                                            <p class="date-range-help">Select a start and end date.</p>

                                            <div class="calendar-actions">
                                                <button type="button" class="calendar-clear">Clear</button>
                                                <button type="button" class="calendar-done">Done</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="chart-legend">
                            <span class="legend-swatch legend-swatch-current"></span>
                            <span class="legend-label">This Week</span>
                        </div>

                        <div class="chart-canvas-wrap">
                            <canvas id="salesOverviewChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card" id="topSellingCard">
                        <div class="chart-card-header">
                            <h2 class="chart-card-title">Top Selling Items</h2>
                            <a href="#" class="view-all-link">View All</a>
                        </div>

                        <div class="top-items-list">
                            <?php 
                            // Image filename mapping for products with different names than their files
                            $imageMapping = [
                                'French Vanilla' => 'Franch Vanilla.png',
                                'Black Forrest' => 'blackforest.png',
                                'Black Forest' => 'blackforest.png',
                                'Messy Tuna Spinach' => 'Messy Tuna Spinach.png',
                                'Chicken Quesadilla' => 'Chicken Quesadilla.png',
                                'Beef Quesadilla' => 'Beef Quesadilla.png',
                                'Messy Tuna Quesadilla' => 'Messy Tuna Spinach.png',
                            ];
                            
                            if (!empty($analytics['top_items'])) {
                                $maxQuantity = max(array_column($analytics['top_items'], 'total_quantity'));
                                foreach ($analytics['top_items'] as $index => $item) {
                                    $progress = $maxQuantity > 0 ? ($item['total_quantity'] / $maxQuantity) * 100 : 0;
                                    $productName = htmlspecialchars($item['product_name']);
                                    $imageName = isset($imageMapping[$item['product_name']]) 
                                        ? $imageMapping[$item['product_name']] 
                                        : $productName . '.png';
                            ?>
                            <div class="top-item">
                                <span class="item-rank"><?php echo $index + 1; ?></span>
                                <span class="item-thumb"><img src="../pos/img/<?php echo $imageName; ?>" alt="" onerror="this.src='../pos/img/icon.png'"></span>
                                <div class="item-content">
                                    <div class="item-top-row">
                                        <span class="item-name"><?php echo $productName; ?></span>
                                        <span class="item-cups"><?php echo $item['total_quantity']; ?> cups</span>
                                    </div>
                                    <div class="item-progress-track">
                                        <div class="item-progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                                    </div>
                                </div>
                                <span class="item-price">₱ <?php echo number_format($item['total_revenue'], 2); ?></span>
                            </div>
                            <?php 
                                }
                            } else {
                            ?>
                            <div class="top-item">
                                <p class="no-data">No sales data available for this period</p>
                            </div>
                            <?php 
                            }
                            ?>
                        </div>
                    </div>
                    <div class="chart-card" id="timeOfDayCard">
                        <div class="chart-card-header">
                            <h2 class="chart-card-title">Sales by Time of Day</h2>
                            <div class="chart-period-select">
                                <div class="date-range-picker">
                                <div class="period-dropdown" data-range-key="time">
                                    <button type="button" class="period-trigger">
                                        <span class="period-trigger-label"><?php echo htmlspecialchars($timeRangeLabel, ENT_QUOTES); ?></span>
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </button>
                                    <div class="period-menu">
                                        <button type="button" class="period-option" data-value="today">Today</button>
                                        <button type="button" class="period-option" data-value="this-week">This Week</button>
                                        <button type="button" class="period-option" data-value="last-week">Last Week</button>
                                        <button type="button" class="period-option" data-value="this-month">This Month</button>
                                        <button type="button" class="period-option" data-value="last-month">Last Month</button>
                                        <button type="button" class="period-option" data-value="custom">Custom Range</button>
                                    </div>
                                    <div class="date-range-dropdown">
                                    <div class="calendar-header">
                                        <button type="button" class="calendar-nav prev-month" aria-label="Previous month">
                                            <i class="fa-solid fa-chevron-left"></i>
                                        </button>
                                        <span class="calendar-month"></span>
                                        <button type="button" class="calendar-nav next-month" aria-label="Next month">
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </button>
                                    </div>

                                    <div class="calendar-weekdays">
                                        <span>Sun</span><span>Mon</span><span>Tue</span>
                                        <span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                                    </div>

                                    <div class="calendar-days"></div>

                                    <p class="date-range-help">Select a start and end date.</p>

                                    <div class="calendar-actions">
                                        <button type="button" class="calendar-clear">Clear</button>
                                        <button type="button" class="calendar-done">Done</button>
                                    </div>
                                </div>
                                </div>

                                
                            </div>
                                <i class="fa-solid fa-chevron-down chart-select-caret"></i>
                            </div>
                        </div>

                        <div class="chart-axis-label">Orders</div>

                        <div class="chart-canvas-wrap">
                            <canvas id="timeOfDayChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card peak-hours-card" id="peakHoursCard">
                        <div class="chart-card-header">
                            <h2 class="chart-card-title">Peak Hours (Orders)</h2>
                            <div class="chart-period-select">
                                <div class="date-range-picker">
                                    <div class="period-dropdown" data-range-key="peak">
                                        <button type="button" class="period-trigger">
                                            <span class="period-trigger-label"><?php echo htmlspecialchars($peakRangeLabel, ENT_QUOTES); ?></span>
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </button>
                                        <div class="period-menu">
                                            <button type="button" class="period-option" data-value="today">Today</button>
                                            <button type="button" class="period-option" data-value="this-week">This Week</button>
                                            <button type="button" class="period-option" data-value="last-week">Last Week</button>
                                            <button type="button" class="period-option" data-value="this-month">This Month</button>
                                            <button type="button" class="period-option" data-value="last-month">Last Month</button>
                                            <button type="button" class="period-option" data-value="custom">Custom Range</button>
                                        </div>
                                        <div class="date-range-dropdown">
                                            <div class="calendar-header">
                                                <button type="button" class="calendar-nav prev-month" aria-label="Previous month">
                                                    <i class="fa-solid fa-chevron-left"></i>
                                                </button>
                                                <span class="calendar-month"></span>
                                                <button type="button" class="calendar-nav next-month" aria-label="Next month">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </button>
                                            </div>

                                            <div class="calendar-weekdays">
                                                <span>Sun</span><span>Mon</span><span>Tue</span>
                                                <span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                                            </div>

                                            <div class="calendar-days"></div>

                                            <p class="date-range-help">Select a start and end date.</p>

                                            <div class="calendar-actions">
                                                <button type="button" class="calendar-clear">Clear</button>
                                                <button type="button" class="calendar-done">Done</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="peak-hours-list">
                            <?php 
                            if (!empty($analytics['time_of_day'])) {
                                // Keep the chronological chart dataset intact; Peak Hours gets
                                // a sorted copy of those same database results.
                                $peakHours = $peakAnalytics['time_of_day'];
                                usort($peakHours, function($a, $b) {
                                    return $b['orders'] - $a['orders'];
                                });
                                
                                $maxOrders = max(array_column($peakHours, 'orders'));
                                $colors = ['#E5383B', '#EB5E55', '#F2994A', '#F5A662', '#F2C94C', '#F5D76E', '#F7E088', '#F9E6A3'];
                                
                                foreach ($peakHours as $index => $timeData) {
                                    $hour = $timeData['hour'];
                                    $orders = $timeData['orders'];
                                    $progress = $maxOrders > 0 ? ($orders / $maxOrders) * 100 : 0;
                                    $color = $colors[$index % count($colors)];
                                    $label = sprintf('%02d:00 - %02d:00', $hour, ($hour + 1) % 24);

                            ?>
                            <div class="peak-hour-item">
                                <span class="peak-hour-icon"><i class="fa-regular fa-clock"></i></span>
                                <span class="peak-hour-label"><?php echo $label; ?></span>
                                <div class="peak-hour-track">
                                    <div class="peak-hour-fill" style="width: <?php echo $progress; ?>%; background: <?php echo $color; ?>;"></div>
                                </div>
                                <span class="peak-hour-value" style="color: <?php echo $color; ?>;"><?php echo $orders; ?></span>
                            </div>
                            <?php 
                                }
                            } else {
                            ?>
                            <div class="peak-hour-item">
                                <p class="no-data">No time data available for this period</p>
                            </div>
                            <?php 
                            }
                            ?>
                        </div>
                    </div>
                    
                </div>
                <div class="insights-card">
                    <h2 class="insights-title">Insights</h2>

                    <div class="insights-grid">

                        <div class="insight-item">
                            <span class="insight-icon <?php echo $analytics['sales_trend'] >= 0 ? 'insight-icon-green' : 'insight-icon-red'; ?>">
                                <i class="fa-solid fa-arrow-<?php echo $analytics['sales_trend'] >= 0 ? 'trend-up' : 'trend-down'; ?>"></i>
                            </span>
                            <div class="insight-content">
                                <p class="insight-heading">Sales <?php echo $analytics['sales_trend'] >= 0 ? 'increase' : 'decrease'; ?> by <?php echo analyticsReportPercent($analytics['sales_trend']); ?></p>
                                <p class="insight-desc"><?php echo $analytics['sales_trend'] >= 0 ? 'Great job! Your sales are higher than last week.' : 'Sales are lower than last week. Consider promotions.'; ?></p>
                            </div>
                        </div>

                        <div class="insight-divider"></div>

                        <div class="insight-item">
                            <span class="insight-icon insight-icon-purple">
                                <i class="fa-regular fa-star"></i>
                            </span>
                            <div class="insight-content">
                                <p class="insight-heading"><?php echo !empty($analytics['top_items']) ? htmlspecialchars($analytics['top_items'][0]['product_name']) : 'No items'; ?> <span class="insight-heading-light">is your top selling item</span></p>
                                <p class="insight-desc"><?php echo !empty($analytics['top_items']) ? $analytics['top_items'][0]['total_quantity'] . ' cups sold this week.' : 'No sales data available.'; ?></p>
                            </div>
                        </div>

                        <div class="insight-divider"></div>

                        <div class="insight-item">
                            <span class="insight-icon insight-icon-orange">
                                <i class="fa-regular fa-clock"></i>
                            </span>
                            <div class="insight-content">
                                <p class="insight-heading">Peak hours at <?php echo !empty($peakAnalytics['time_of_day']) ? sprintf('%02d:00', $peakAnalytics['time_of_day'][0]['hour']) : 'N/A'; ?></p>
                                <p class="insight-desc">Prepare your team and stocks before the rush.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const EXPORT_BRAND_MAROON = [105, 39, 39];

        function formatReportPercent(value) {
            const number = Number(value) || 0;
            return `${Math.abs(number).toFixed(2)}%`;
        }

        function loadLogoDataUrl() {
            return new Promise((resolve) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => {
                    try {
                        const canvas = document.createElement('canvas');
                        canvas.width = img.naturalWidth;
                        canvas.height = img.naturalHeight;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0);
                        resolve(canvas.toDataURL('image/png'));
                    } catch (err) {
                        resolve(null);
                    }
                };
                img.onerror = () => resolve(null);
                img.src = new URL('../img/LOGO.png', document.baseURI).href;
            });
        }

        async function generateAnalyticsPdfReport() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            const pageWidth = doc.internal.pageSize.getWidth();
            const marginX = 15;
            const now = new Date();
            const generatedOn = now.toLocaleString('en-US', {
                year: 'numeric', month: 'long', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });

            const logoDataUrl = await loadLogoDataUrl();
            const logoSize = 18;
            if (logoDataUrl) {
                doc.addImage(logoDataUrl, 'PNG', marginX, 12, logoSize, logoSize);
            }

            doc.setTextColor(20, 20, 20);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(16);
            doc.text('BOYCOLD CAFE', marginX + logoSize + 6, 19);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9.5);
            doc.setTextColor(110, 110, 110);
            doc.text('Administration Panel', marginX + logoSize + 6, 25);

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(12);
            doc.setTextColor(...EXPORT_BRAND_MAROON);
            doc.text('DATA ANALYTICS REPORT', pageWidth - marginX, 18, { align: 'right' });

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            doc.setTextColor(110, 110, 110);
            doc.text(`Generated: ${generatedOn}`, pageWidth - marginX, 24, { align: 'right' });

            doc.setDrawColor(...EXPORT_BRAND_MAROON);
            doc.setLineWidth(0.8);
            doc.line(marginX, 34, pageWidth - marginX, 34);

            const branchLabel = document.querySelector('.branch-trigger-label')?.textContent || 'All Branches';
            const metricRows = [
                ['Total Sales', `₱ ${Number(analyticsData.total_sales || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`],
                ['Average Order Value', `₱ ${Number(analyticsData.avg_order_value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`],
                ['New Customers', Number(analyticsData.new_customers || 0)],
                ['Sales Trend', formatReportPercent(analyticsData.sales_trend)],
                ['Average Order Trend', formatReportPercent(analyticsData.avg_order_trend)],
                ['Customers Trend', formatReportPercent(analyticsData.customers_trend)],
                ['Date Range', `${startDate} to ${endDate}`],
                ['Branch', branchLabel.trim()]
            ];

            const topItemsRows = (analyticsData.top_items || []).map(item => [
                item.product_name,
                `${item.total_quantity} orders`,
                `₱ ${Number(item.total_revenue || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
            ]);

            const timeRows = (analyticsData.time_of_day || []).map(item => [
                `${Number(item.hour)}:00 - ${(Number(item.hour) + 1) % 24}:00`,
                item.orders
            ]);

            doc.setFontSize(9.5);
            doc.setTextColor(60, 60, 60);
            doc.setFont('helvetica', 'bold');
            doc.text('Branch:', marginX, 41);
            doc.setFont('helvetica', 'normal');
            doc.text(branchLabel.trim(), marginX + 18, 41);

            doc.setFont('helvetica', 'bold');
            doc.text('Prepared By:', marginX, 46.5);
            doc.setFont('helvetica', 'normal');
            doc.text('Admin', marginX + 24, 46.5);

            doc.setFont('helvetica', 'bold');
            doc.text('Filter:', pageWidth - marginX - 60, 41);
            doc.setFont('helvetica', 'normal');
            doc.text(`${startDate} to ${endDate}`, pageWidth - marginX - 35, 41);

            doc.autoTable({
                startY: 56,
                margin: { left: marginX, right: marginX },
                head: [['Metric', 'Value']],
                body: metricRows,
                styles: {
                    font: 'helvetica', fontSize: 8.5, cellPadding: 3,
                    lineColor: [196, 193, 193], lineWidth: 0.1, valign: 'middle'
                },
                headStyles: { fillColor: EXPORT_BRAND_MAROON, textColor: [255,255,255], fontStyle: 'bold', halign: 'left' },
                alternateRowStyles: { fillColor: [247, 245, 243] },
                didDrawPage: (data) => {
                    const pageCount = doc.internal.getNumberOfPages();
                    doc.setFontSize(8);
                    doc.setTextColor(140, 140, 140);
                    doc.text(`Page ${doc.internal.getCurrentPageInfo().pageNumber} of ${pageCount}`, pageWidth - marginX, doc.internal.pageSize.getHeight() - 10, { align: 'right' });
                    doc.text('BoyCold Cafe - Internal Document', marginX, doc.internal.pageSize.getHeight() - 10);
                }
            });

            const itemsStartY = doc.lastAutoTable.finalY + 8;
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(11);
            doc.setTextColor(...EXPORT_BRAND_MAROON);
            doc.text('Top Selling Items', marginX, itemsStartY);

            doc.autoTable({
                startY: itemsStartY + 4,
                margin: { left: marginX, right: marginX },
                head: [['Item', 'Orders', 'Revenue']],
                body: topItemsRows.length ? topItemsRows : [['No sales data available', '0', '₱ 0.00']],
                styles: { font: 'helvetica', fontSize: 8.2, cellPadding: 3, lineColor: [196,193,193], lineWidth: 0.1 },
                headStyles: { fillColor: EXPORT_BRAND_MAROON, textColor: [255,255,255], fontStyle: 'bold', halign: 'left' },
                alternateRowStyles: { fillColor: [247, 245, 243] }
            });

            const timeStartY = doc.lastAutoTable.finalY + 8;
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(11);
            doc.setTextColor(...EXPORT_BRAND_MAROON);
            doc.text('Sales by Time of Day', marginX, timeStartY);

            doc.autoTable({
                startY: timeStartY + 4,
                margin: { left: marginX, right: marginX },
                head: [['Time Slot', 'Orders']],
                body: timeRows.length ? timeRows : [['No time data available', '0']],
                styles: { font: 'helvetica', fontSize: 8.2, cellPadding: 3, lineColor: [196,193,193], lineWidth: 0.1 },
                headStyles: { fillColor: EXPORT_BRAND_MAROON, textColor: [255,255,255], fontStyle: 'bold', halign: 'left' },
                alternateRowStyles: { fillColor: [247, 245, 243] }
            });

            const fileDate = now.toISOString().slice(0, 10);
            doc.save(`data-analytics-report_${fileDate}.pdf`);
        }

        document.getElementById('exportAnalyticsBtn')?.addEventListener('click', async () => {
            const btn = document.getElementById('exportAnalyticsBtn');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';

            try {
                await generateAnalyticsPdfReport();
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });

        // PHP analytics data passed to JavaScript
        const analyticsData = <?php echo json_encode($analytics); ?>;
        const startDate = <?php echo json_encode($startDate); ?>;
        const endDate = <?php echo json_encode($endDate); ?>;
        const previousStartDate = <?php echo json_encode($prevStartDate); ?>;
        const previousEndDate = <?php echo json_encode($prevEndDate); ?>;
        const timeAnalyticsData = <?php echo json_encode($timeAnalytics); ?>;
        const timeStartDate = <?php echo json_encode($timeStartDate); ?>;
        const timeEndDate = <?php echo json_encode($timeEndDate); ?>;

        // Branch dropdown functionality
        const branchTrigger = document.querySelector('.branch-trigger');
        const branchMenu = document.querySelector('.branch-menu');
        const branchOptions = document.querySelectorAll('.branch-option');
        
        branchTrigger?.addEventListener('click', (e) => {
            e.stopPropagation();
            branchMenu.classList.toggle('open');
        });
        
        document.addEventListener('click', (e) => {
            if (!branchMenu.contains(e.target) && !branchTrigger.contains(e.target)) {
                branchMenu.classList.remove('open');
            }
        });
        
        branchOptions.forEach(option => {
            option.addEventListener('click', () => {
                const branchId = option.getAttribute('data-value');
                const url = new URL(window.location.href);
                url.searchParams.set('branch_id', branchId);
                window.location.href = url.toString();
            });
        });
        
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') e.preventDefault();
                document.querySelectorAll('.sidebar-nav a').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
        (function () {
            const sidebar = document.getElementById("sidebar");
            const backdrop = document.getElementById("sidebarBackdrop");
            const mq = window.matchMedia("(max-width: 860px)");

            function openSidebar() {
                if (!mq.matches) return;
                sidebar.classList.add("expanded");
                backdrop.classList.add("show");
            }

            function closeSidebar() {
                sidebar.classList.remove("expanded");
                backdrop.classList.remove("show");
            }

            sidebar.addEventListener("click", () => {
                if (mq.matches && !sidebar.classList.contains("expanded")) {
                    openSidebar();
                }
            });

            sidebar.querySelectorAll(".sidebar-nav a").forEach((link) => {
                link.addEventListener("click", () => {
                    if (mq.matches) closeSidebar();
                });
            });

            backdrop.addEventListener("click", closeSidebar);
            mq.addEventListener("change", closeSidebar);
        })();
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('salesOverviewChart');
            if (!canvas || typeof Chart === 'undefined') return;

            const ctx = canvas.getContext('2d');

            // Build both graph lines from their actual database dates.  The
            // previous period is aligned by day position, not by calendar date.
            function parseAnalyticsDate(value) {
                const [year, month, day] = String(value).slice(0, 10).split('-').map(Number);
                return new Date(year, month - 1, day);
            }

            function formatAnalyticsDateKey(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function analyticsDateRange(rangeStart, rangeEnd) {
                const dates = [];
                const current = parseAnalyticsDate(rangeStart);
                const end = parseAnalyticsDate(rangeEnd);
                while (current <= end) {
                    dates.push(new Date(current));
                    current.setDate(current.getDate() + 1);
                }
                return dates;
            }

            const currentDates = analyticsDateRange(startDate, endDate);
            const previousDates = analyticsDateRange(previousStartDate, previousEndDate);
            const labels = currentDates.map(date => date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            const dailySalesMap = Object.fromEntries((analyticsData.daily_sales || []).map(item => [
                String(item.sale_date).slice(0, 10), Number(item.daily_sales) || 0
            ]));
            const previousDailySalesMap = Object.fromEntries((analyticsData.prev_daily_sales || []).map(item => [
                String(item.sale_date).slice(0, 10), Number(item.daily_sales) || 0
            ]));
            const thisWeekData = currentDates.map(date => dailySalesMap[formatAnalyticsDateKey(date)] || 0);
            const lastWeekData = previousDates.map(date => previousDailySalesMap[formatAnalyticsDateKey(date)] || 0);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'This Week',
                            data: thisWeekData,
                            borderColor: '#692727',
                            backgroundColor: '#692727',
                            pointBackgroundColor: '#692727',
                            pointBorderColor: '#692727',
                            borderWidth: 3,
                            pointRadius: 5,
                            pointHoverRadius: 6,
                            tension: 0,
                            fill: false
                        },
                        {
                            label: 'Previous Week',
                            data: lastWeekData,
                            borderColor: '#E3B996',
                            backgroundColor: '#E3B996',
                            pointBackgroundColor: '#E3B996',
                            pointBorderColor: '#E3B996',
                            borderWidth: 2,
                            borderDash: [6, 5],
                            pointRadius: 5,
                            pointHoverRadius: 6,
                            tension: 0,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    resizeDelay: 0,
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutQuart',
                        delay: (context) => {
                            let delay = 0;
                            if (context.type === 'data' && context.mode === 'default') {
                                delay = context.dataIndex * 100;
                            }
                            return delay;
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#322B2B',
                            padding: 10,
                            callbacks: {
                                label: (item) => `₱ ${item.parsed.y.toLocaleString()}`
                            }
                        },
                        datalabels: { display: false}
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#777',
                                font: { family: 'Afacad', size: 13 },
                                callback: (value) => `₱ ${Number(value).toLocaleString('en-US')}`
                            },
                            grid: { color: '#eee' }
                        },
                        x: {
                            ticks: {
                                color: '#777',
                                font: { family: 'Afacad', size: 13 }
                            },
                            grid: { display: false }
                        }
                    }
                }
            });

            const periodSelect = document.getElementById('salesPeriodSelect');
            periodSelect?.addEventListener('change', () => {
                // Hook up real data swapping per period here later
            });
            
        });
        Chart.register(ChartDataLabels);

        const canvas2 = document.getElementById('timeOfDayChart');
        if (canvas2 && typeof Chart !== 'undefined') {
            const ctx2 = canvas2.getContext('2d');

            // Use actual time of day data from analytics
            const timeLabels = timeAnalyticsData.time_of_day.map(t => {
                const hour = t.hour;
                return `${hour}:00 - ${(hour + 1) % 24}:00`;
            });
            const timeData = timeAnalyticsData.time_of_day.map(t => t.orders);

            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: timeLabels.length ? timeLabels : ['No Data'],
                    datasets: [{
                        data: timeData.length ? timeData : [0],
                        backgroundColor: '#692727',
                        borderRadius: 6,
                        maxBarThickness: 48
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    resizeDelay: 0,
                    animation: {
                        duration: 1500,
                        easing: 'easeOutQuart',
                        delay: (context) => {
                            let delay = 0;
                            if (context.type === 'data' && context.mode === 'default') {
                                delay = context.dataIndex * 80;
                            }
                            return delay;
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#322B2B',
                            padding: 10,
                            callbacks: {
                                label: (item) => `${item.parsed.y} orders`
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            offset: 4,
                            color: '#2d2d2d',
                            font: { family: 'Afacad', weight: '700', size: 13 },
                            formatter: (value) => value
                        }
                    },
                    layout: {
                        padding: { top: 20 }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#777',
                                font: { family: 'Afacad', size: 13 },
                                stepSize: 20
                            },
                            grid: { color: '#eee' }
                        },
                        x: {
                            ticks: {
                                color: '#777',
                                font: { family: 'Afacad', size: 13 }
                            },
                            grid: { display: false }
                        }
                    }
                }
            });

            const timeOfDayPeriodSelect = document.getElementById('timeOfDayPeriodSelect');
            timeOfDayPeriodSelect?.addEventListener('change', () => {
                // Hook up real data swapping per period here later
            });
        }
        // ---- Independent period dropdowns for each chart ----
        function formatDateParameter(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function analyticsPeriodRange(period) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const start = new Date(today);
            const end = new Date(today);

            switch (period) {
                case 'this-week':
                    start.setDate(today.getDate() - today.getDay());
                    break;
                case 'last-week':
                    start.setDate(today.getDate() - today.getDay() - 7);
                    end.setDate(today.getDate() - today.getDay() - 1);
                    break;
                case 'this-month':
                    start.setDate(1);
                    break;
                case 'last-month':
                    start.setMonth(today.getMonth() - 1, 1);
                    end.setDate(0);
                    break;
                case 'today':
                default:
                    break;
            }

            return { start: formatDateParameter(start), end: formatDateParameter(end) };
        }

        function reloadAnalyticsRange(rangeStart, rangeEnd, rangeKey) {
            const url = new URL(window.location.href);
            const prefix = rangeKey === 'sales' ? '' : `${rangeKey}_`;
            url.searchParams.set(`${prefix}start_date`, rangeStart);
            url.searchParams.set(`${prefix}end_date`, rangeEnd);
            window.location.assign(url.toString());
        }

        async function refreshTotalSales() {
            const totalSalesValue = document.getElementById('totalSalesValue');
            if (!totalSalesValue || document.hidden) return;

            const url = new URL(window.location.href);
            url.searchParams.set('format', 'json');

            try {
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) return;
                const data = await response.json();
                if (!data.success) return;

                totalSalesValue.textContent = `₱ ${Number(data.total_sales || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })}`;
            } catch (error) {
                console.warn('Unable to refresh analytics sales total.', error);
            }
        }

        setInterval(refreshTotalSales, 15000);

        function closeAllDropdowns(except) {
            document.querySelectorAll('.period-dropdown').forEach(d => {
                if (d === except) return;
                d.querySelector('.period-menu')?.classList.remove('open');
                d.querySelector('.date-range-dropdown')?.classList.remove('show', 'align-left', 'drop-up');
            });
        }

        function openCalendar(calendar) {
            calendar.classList.remove('align-left', 'drop-up');
            calendar.classList.add('show');

            const rect = calendar.getBoundingClientRect();
            if (rect.right > window.innerWidth) calendar.classList.add('align-left');
            if (rect.bottom > window.innerHeight) calendar.classList.add('drop-up');
        }

        document.querySelectorAll('.period-dropdown').forEach(dropdown => {
            const rangeKey = dropdown.dataset.rangeKey || 'sales';
            const trigger = dropdown.querySelector('.period-trigger');
            const triggerLabel = dropdown.querySelector('.period-trigger-label');
            const menu = dropdown.querySelector('.period-menu');
            const calendar = dropdown.querySelector('.date-range-dropdown');

            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                const wasOpen = menu.classList.contains('open') || calendar.classList.contains('show');
                closeAllDropdowns(dropdown);

                if (wasOpen) {
                    menu.classList.remove('open');
                    calendar.classList.remove('show', 'align-left', 'drop-up');
                } else {
                    menu.classList.add('open');
                }
            });

            menu.querySelectorAll('.period-option').forEach(option => {
                option.addEventListener('click', () => {
                    if (option.dataset.value === 'custom') {
                        menu.classList.remove('open');
                        openCalendar(calendar);
                        return;
                    }
                    const range = analyticsPeriodRange(option.dataset.value);
                    reloadAnalyticsRange(range.start, range.end, rangeKey);
                });
            });
        });

        document.addEventListener('click', (e) => {
            document.querySelectorAll('.period-dropdown').forEach(d => {
                if (!d.contains(e.target)) {
                    d.querySelector('.period-menu')?.classList.remove('open');
                    d.querySelector('.date-range-dropdown')?.classList.remove('show', 'align-left', 'drop-up');
                }
            });
        });

        // ---- Custom Range calendar ----
        document.querySelectorAll('.date-range-dropdown').forEach(calendarEl => {
            let current = new Date();
            let rangeStart = null;
            let rangeEnd = null;
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const monthLabel = calendarEl.querySelector('.calendar-month');
            const daysGrid = calendarEl.querySelector('.calendar-days');
            const prevBtn = calendarEl.querySelector('.prev-month');
            const nextBtn = calendarEl.querySelector('.next-month');
            const helpText = calendarEl.querySelector('.date-range-help');
            const clearBtn = calendarEl.querySelector('.calendar-clear');
            const doneBtn = calendarEl.querySelector('.calendar-done');

            function makeDay(num, empty, dateObj) {
                const el = document.createElement('button');
                el.type = 'button';
                el.className = 'calendar-day' + (empty ? ' empty' : '');

                const connector = document.createElement('span');
                connector.className = 'day-connector';
                const number = document.createElement('span');
                number.className = 'day-number';
                number.textContent = num;
                el.append(connector, number);

                if (!empty && dateObj) {
                    if (dateObj.getTime() === today.getTime()) el.classList.add('today');
                    if (rangeStart && dateObj.getTime() === rangeStart.getTime()) el.classList.add('range-start');
                    if (rangeEnd && dateObj.getTime() === rangeEnd.getTime()) el.classList.add('range-end');
                    if (rangeStart && !rangeEnd && dateObj.getTime() === rangeStart.getTime()) el.classList.add('range-end');
                    if (rangeStart && rangeEnd && dateObj > rangeStart && dateObj < rangeEnd) el.classList.add('in-range');

                    el.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (!rangeStart || (rangeStart && rangeEnd)) {
                            rangeStart = dateObj;
                            rangeEnd = null;
                        } else if (dateObj < rangeStart) {
                            rangeEnd = rangeStart;
                            rangeStart = dateObj;
                        } else {
                            rangeEnd = dateObj;
                        }
                        updateHelpText();
                        renderCalendar();
                    });
                }
                return el;
            }

            function updateHelpText() {
                const fmt = (d) => d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                if (rangeStart && rangeEnd) helpText.textContent = `${fmt(rangeStart)} - ${fmt(rangeEnd)}`;
                else if (rangeStart) helpText.textContent = `${fmt(rangeStart)} - Select end date`;
                else helpText.textContent = 'Select a start and end date.';
            }

            function renderCalendar() {
                const year = current.getFullYear();
                const month = current.getMonth();
                monthLabel.textContent = current.toLocaleString('default', { month: 'long', year: 'numeric' });

                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const daysInPrevMonth = new Date(year, month, 0).getDate();

                daysGrid.innerHTML = '';
                for (let i = firstDay - 1; i >= 0; i--) daysGrid.appendChild(makeDay(daysInPrevMonth - i, true));
                for (let d = 1; d <= daysInMonth; d++) daysGrid.appendChild(makeDay(d, false, new Date(year, month, d)));
                const trailing = (7 - ((firstDay + daysInMonth) % 7)) % 7;
                for (let d = 1; d <= trailing; d++) daysGrid.appendChild(makeDay(d, true));
            }

            prevBtn.addEventListener('click', () => { current.setMonth(current.getMonth() - 1); renderCalendar(); });
            nextBtn.addEventListener('click', () => { current.setMonth(current.getMonth() + 1); renderCalendar(); });
            clearBtn.addEventListener('click', () => { rangeStart = null; rangeEnd = null; updateHelpText(); renderCalendar(); });
            doneBtn.addEventListener('click', () => {
                const dropdown = calendarEl.closest('.period-dropdown');
                if (rangeStart && rangeEnd) {
                    const label = dropdown.querySelector('.period-trigger-label');
                    const fmt = (d) => d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    label.textContent = `${fmt(rangeStart)} - ${fmt(rangeEnd)}`;
                    const rangeKey = dropdown.dataset.rangeKey || 'sales';
                    reloadAnalyticsRange(formatDateParameter(rangeStart), formatDateParameter(rangeEnd), rangeKey);
                    return;
                }
                calendarEl.classList.remove('show', 'align-left', 'drop-up');
            });

            renderCalendar();
        });

    </script>
    <script src="admin-js/admin-responsive.js"></script>
    <script src="admin-js/inventory-warning.js"></script>
    <script src="admin-js/logout-modal.js"></script>
</body>

</html>

