<?php
/**
 * Shared sales-reporting source for Admin Data Analytics and Forecasting.
 *
 * Forecasting intentionally consumes these historical aggregates instead of
 * maintaining separate order queries. This keeps its inputs consistent with
 * the figures visible to administrators in Data Analytics.
 */

function boycold_analytics_successful_sale_condition(string $orderAlias = 'o'): string
{
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $orderAlias)) {
        throw new InvalidArgumentException('Invalid order table alias.');
    }

    return "(
        {$orderAlias}.user_id IS NOT NULL
        AND ({$orderAlias}.status IN ('completed', 'delivered') OR {$orderAlias}.payment_status = 'paid')
        AND {$orderAlias}.status <> 'cancelled'
        AND {$orderAlias}.payment_status NOT IN ('failed', 'expired', 'cancelled')
    )";
}

/**
 * @return array{sql: string, types: string, values: array<int, string|int>}
 */
function boycold_analytics_branch_filter(string $branchId, string $orderAlias = 'o'): array
{
    if ($branchId === 'all') {
        return ['sql' => '', 'types' => '', 'values' => []];
    }

    $numericBranchId = filter_var($branchId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($numericBranchId === false) {
        // An invalid branch must never quietly become an all-branches report.
        return ['sql' => ' AND 1 = 0', 'types' => '', 'values' => []];
    }

    return [
        'sql' => " AND {$orderAlias}.branch_id = ?",
        'types' => 'i',
        'values' => [(int) $numericBranchId],
    ];
}

/**
 * @param array<int, string|int> $values
 */
function boycold_analytics_bind(mysqli_stmt $statement, string $types, array $values): void
{
    if ($types === '') {
        return;
    }

    $references = [];
    foreach ($values as $index => $value) {
        $references[$index] = &$values[$index];
    }
    $statement->bind_param($types, ...$references);
}

function boycold_analytics_latest_sale_date(mysqli $connect, string $branchId): ?string
{
    $branch = boycold_analytics_branch_filter($branchId, 'o');
    $saleCondition = boycold_analytics_successful_sale_condition('o');
    $query = "SELECT MAX(DATE(o.created_at)) AS latest_sale_date
        FROM orders o
        WHERE {$saleCondition}{$branch['sql']}";
    $statement = $connect->prepare($query);
    boycold_analytics_bind($statement, $branch['types'], $branch['values']);
    $statement->execute();
    $date = $statement->get_result()->fetch_assoc()['latest_sale_date'] ?? null;
    $statement->close();

    return is_string($date) && $date !== '' ? $date : null;
}

/**
 * The one historical dataset used by Admin Data Analytics and Forecasting.
 *
 * @return array{
 *   summary: array{total_sales: float, total_orders: int},
 *   top_items: array<int, array{product_name: string, product_image: string, total_quantity: int, total_revenue: float, days_sold: int}>,
 *   time_of_day: array<int, array{hour: int, orders: int}>,
 *   daily_sales: array<int, array{sale_date: string, daily_sales: float, daily_orders: int}>
 * }
 */
function boycold_analytics_period_snapshot(
    mysqli $connect,
    string $startDate,
    string $endDate,
    string $branchId
): array {
    $branch = boycold_analytics_branch_filter($branchId, 'o');
    $saleCondition = boycold_analytics_successful_sale_condition('o');
    $rangeValues = [$startDate, $endDate];
    $rangeTypes = 'ss';

    $summaryQuery = "SELECT
            COALESCE(SUM(o.total), 0) AS total_sales,
            COUNT(*) AS total_orders
        FROM orders o
        WHERE DATE(o.created_at) BETWEEN ? AND ?
          AND {$saleCondition}{$branch['sql']}";
    $statement = $connect->prepare($summaryQuery);
    boycold_analytics_bind(
        $statement,
        $rangeTypes . $branch['types'],
        array_merge($rangeValues, $branch['values'])
    );
    $statement->execute();
    $summaryRow = $statement->get_result()->fetch_assoc() ?: [];
    $statement->close();

    $topItemsQuery = "SELECT
            oi.product_name,
            MAX(NULLIF(oi.product_image, '')) AS product_image,
            SUM(oi.quantity) AS total_quantity,
            SUM(oi.line_total) AS total_revenue,
            COUNT(DISTINCT DATE(o.created_at)) AS days_sold
        FROM order_items oi
        INNER JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
          AND {$saleCondition}{$branch['sql']}
        GROUP BY oi.product_name
        ORDER BY total_quantity DESC, oi.product_name ASC
        LIMIT 5";
    $statement = $connect->prepare($topItemsQuery);
    boycold_analytics_bind(
        $statement,
        $rangeTypes . $branch['types'],
        array_merge($rangeValues, $branch['values'])
    );
    $statement->execute();
    $topItems = [];
    $result = $statement->get_result();
    while ($row = $result->fetch_assoc()) {
        $topItems[] = [
            'product_name' => (string) $row['product_name'],
            'product_image' => (string) ($row['product_image'] ?? ''),
            'total_quantity' => (int) $row['total_quantity'],
            'total_revenue' => (float) $row['total_revenue'],
            'days_sold' => (int) $row['days_sold'],
        ];
    }
    $statement->close();

    $timeOfDayQuery = "SELECT
            HOUR(o.created_at) AS hour,
            COUNT(*) AS orders
        FROM orders o
        WHERE DATE(o.created_at) BETWEEN ? AND ?
          AND {$saleCondition}{$branch['sql']}
        GROUP BY HOUR(o.created_at)
        ORDER BY hour ASC";
    $statement = $connect->prepare($timeOfDayQuery);
    boycold_analytics_bind(
        $statement,
        $rangeTypes . $branch['types'],
        array_merge($rangeValues, $branch['values'])
    );
    $statement->execute();
    $timeOfDay = [];
    $result = $statement->get_result();
    while ($row = $result->fetch_assoc()) {
        $timeOfDay[] = [
            'hour' => (int) $row['hour'],
            'orders' => (int) $row['orders'],
        ];
    }
    $statement->close();

    $dailySalesQuery = "SELECT
            DATE(o.created_at) AS sale_date,
            COALESCE(SUM(o.total), 0) AS daily_sales,
            COUNT(*) AS daily_orders
        FROM orders o
        WHERE DATE(o.created_at) BETWEEN ? AND ?
          AND {$saleCondition}{$branch['sql']}
        GROUP BY DATE(o.created_at)
        ORDER BY sale_date ASC";
    $statement = $connect->prepare($dailySalesQuery);
    boycold_analytics_bind(
        $statement,
        $rangeTypes . $branch['types'],
        array_merge($rangeValues, $branch['values'])
    );
    $statement->execute();
    $dailySales = [];
    $result = $statement->get_result();
    while ($row = $result->fetch_assoc()) {
        $dailySales[] = [
            'sale_date' => (string) $row['sale_date'],
            'daily_sales' => (float) $row['daily_sales'],
            'daily_orders' => (int) $row['daily_orders'],
        ];
    }
    $statement->close();

    return [
        'summary' => [
            'total_sales' => (float) ($summaryRow['total_sales'] ?? 0),
            'total_orders' => (int) ($summaryRow['total_orders'] ?? 0),
        ],
        'top_items' => $topItems,
        'time_of_day' => $timeOfDay,
        'daily_sales' => $dailySales,
    ];
}

/**
 * Product quantity comparison used by Forecasting trends. It uses the same
 * successful-sales and branch rules as the Data Analytics snapshot above.
 *
 * @return array<int, array{product_name: string, product_image: string, recent_quantity: int, previous_quantity: int}>
 */
function boycold_analytics_product_period_comparison(
    mysqli $connect,
    string $recentStartDate,
    string $recentEndDate,
    string $previousStartDate,
    string $previousEndDate,
    string $branchId,
    int $limit = 0
): array {
    $branch = boycold_analytics_branch_filter($branchId, 'o');
    $saleCondition = boycold_analytics_successful_sale_condition('o');
    $limitSql = $limit > 0 ? ' LIMIT ' . max(1, min($limit, 100)) : '';

    $query = "SELECT
            oi.product_name,
            MAX(NULLIF(oi.product_image, '')) AS product_image,
            SUM(CASE WHEN DATE(o.created_at) BETWEEN ? AND ? THEN oi.quantity ELSE 0 END) AS recent_quantity,
            SUM(CASE WHEN DATE(o.created_at) BETWEEN ? AND ? THEN oi.quantity ELSE 0 END) AS previous_quantity
        FROM order_items oi
        INNER JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
          AND {$saleCondition}{$branch['sql']}
        GROUP BY oi.product_name
        HAVING recent_quantity > 0 OR previous_quantity > 0
        ORDER BY recent_quantity DESC, oi.product_name ASC{$limitSql}";

    $statement = $connect->prepare($query);
    $values = [
        $recentStartDate,
        $recentEndDate,
        $previousStartDate,
        $previousEndDate,
        $previousStartDate,
        $recentEndDate,
    ];
    boycold_analytics_bind(
        $statement,
        'ssssss' . $branch['types'],
        array_merge($values, $branch['values'])
    );
    $statement->execute();
    $items = [];
    $result = $statement->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'product_name' => (string) $row['product_name'],
            'product_image' => (string) ($row['product_image'] ?? ''),
            'recent_quantity' => (int) $row['recent_quantity'],
            'previous_quantity' => (int) $row['previous_quantity'],
        ];
    }
    $statement->close();

    return $items;
}

/**
 * Default Data Analytics range: the 7 reporting days ending on the latest
 * successful sale (or today when there are none). Data Analytics and
 * Forecasting both call this, so their default window cannot drift apart.
 *
 * @return array{start_date: string, end_date: string}
 */
function boycold_analytics_default_range(mysqli $connect, string $branchId): array
{
    $endDate = boycold_analytics_latest_sale_date($connect, $branchId) ?? date('Y-m-d');

    return [
        'start_date' => date('Y-m-d', strtotime($endDate . ' -6 days')),
        'end_date' => $endDate,
    ];
}

/**
 * Data Analytics saves the branch and date range it is showing, so Forecasting
 * reads the very same scope (top sellers, trends) instead of choosing its own.
 */
function boycold_analytics_remember_scope(string $branchId, string $startDate, string $endDate): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION['analytics_scope'] = [
        'branch_id' => $branchId,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ];
}

/**
 * @return array{branch_id: string, start_date: string, end_date: string}|null
 */
function boycold_analytics_remembered_scope(): ?array
{
    $scope = $_SESSION['analytics_scope'] ?? null;
    if (!is_array($scope)) {
        return null;
    }

    $validDate = static function (mixed $value): ?string {
        if (!is_string($value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    };

    $startDate = $validDate($scope['start_date'] ?? null);
    $endDate = $validDate($scope['end_date'] ?? null);
    if ($startDate === null || $endDate === null) {
        return null;
    }
    if ($startDate > $endDate) {
        [$startDate, $endDate] = [$endDate, $startDate];
    }

    $branchId = (string) ($scope['branch_id'] ?? 'all');
    if ($branchId !== 'all'
        && filter_var($branchId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        $branchId = 'all';
    }

    return ['branch_id' => $branchId, 'start_date' => $startDate, 'end_date' => $endDate];
}