<?php
require_once __DIR__ . '/../../config/admin_auth.php';
require_once __DIR__ . '/../../config/db_config.php';

if (!boycold_admin_account($connect)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Admin login required']);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
// Forecasting is a live report. Never let a browser/proxy mix a cached
// response from another reload, login, or selected branch into this view.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

set_exception_handler(function (Throwable $error): void {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Forecast data could not be loaded.',
    ]);
    error_log('Forecast API error: ' . $error->getMessage());
});

require_once __DIR__ . '/../../config/inventory_service.php';
require_once __DIR__ . '/../../config/analytics_reporting_service.php';

boycold_ensure_inventory_schema($connect);

// Match admin/dashboard.php branch scope when the caller does not provide one.
$sessionBranchId = (int) ($_SESSION['branch_id'] ?? 0);
// Data Analytics is the source of truth: without an explicit branch, follow the
// branch and date range it is currently showing.
$analyticsScope = boycold_analytics_remembered_scope();
$requestedBranchId = isset($_GET['branch_id'])
    ? (string) $_GET['branch_id']
    : ($analyticsScope['branch_id'] ?? 'all');
$branchId = $sessionBranchId > 0 ? (string) $sessionBranchId : $requestedBranchId;
if ($branchId !== 'all') {
    $branchNumber = filter_var($branchId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $branchStatement = $connect->prepare(
        "SELECT id FROM branches WHERE id = ? AND status = 'active' LIMIT 1"
    );
    $branchStatement->bind_param('i', $branchNumber);
    $branchStatement->execute();
    $validBranch = $branchStatement->get_result()->fetch_assoc();
    $branchStatement->close();
    if (!$validBranch) {
        $branchId = 'all';
    }
}
$forecastDays = isset($_GET['forecast_days']) ? intval($_GET['forecast_days']) : 14;
$historicalDays = isset($_GET['historical_days']) ? intval($_GET['historical_days']) : 28;
$forecastDays = max(1, min($forecastDays, 90));
$historicalDays = max(3, min($historicalDays, 365));

// Use the same successful-sale source and selected reporting range as Data
// Analytics. Forecasting only calculates future values from this snapshot.
// Same default range as Data Analytics (latest sale date, no PHP-clock check).
$defaultRange = boycold_analytics_default_range($connect, $branchId);
$forecastAnchorDate = $defaultRange['end_date'];

// Demand Forecast and Trending Drinks read the range Data Analytics is showing;
// with no saved Analytics view they use the shared default range.
$demandStartDate = $analyticsScope['start_date'] ?? $defaultRange['start_date'];
$demandEndDate = $analyticsScope['end_date'] ?? $defaultRange['end_date'];
$demandHistoricalDays = (int) ((strtotime($demandEndDate) - strtotime($demandStartDate)) / 86400) + 1;

// ==========================================
// 1. DAILY SALES HISTORY (last N days)
// ==========================================
$historicalStart = (new DateTimeImmutable($forecastAnchorDate))->modify('-' . ($historicalDays - 1) . ' days');
$historicalStartDate = $historicalStart->format('Y-m-d');
$historicalReport = boycold_analytics_period_snapshot(
    $connect,
    $historicalStartDate,
    $forecastAnchorDate,
    $branchId
);

// Keep zero-sales days in the series so the regression and chart use a real
// contiguous Data Analytics date range instead of only dates that had an order.
$historicalByDate = [];
foreach ($historicalReport['daily_sales'] as $sale) {
    $historicalByDate[$sale['sale_date']] = [
        'date' => $sale['sale_date'],
        'sales' => (float) $sale['daily_sales'],
        'orders' => (int) $sale['daily_orders'],
    ];
}
$historicalSales = [];
for ($offset = 0; $offset < $historicalDays; $offset++) {
    $date = $historicalStart->modify('+' . $offset . ' days')->format('Y-m-d');
    $historicalSales[] = $historicalByDate[$date] ?? [
        'date' => $date,
        'sales' => 0.0,
        'orders' => 0,
    ];
}
$historicalDates = array_column($historicalSales, 'date');

// ==========================================
// 2. FORECAST SALES (next N days)
// Uses: Linear Regression + Seasonal Adjustment
// ==========================================
function computeForecast(array $historicalSales, int $forecastDays): array {
    $n = count($historicalSales);
    if ($n < 3) {
        // Not enough history for a trend; use the actual historical average.
        $avg = $n > 0 ? array_sum(array_column($historicalSales, 'sales')) / $n : 0;
        $forecast = [];
        for ($i = 1; $i <= $forecastDays; $i++) {
            $forecast[] = round($avg, 2);
        }
        return $forecast;
    }
    
    // Linear regression on sales
    $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
    foreach ($historicalSales as $i => $day) {
        $x = $i + 1;
        $y = $day['sales'];
        $sumX += $x;
        $sumY += $y;
        $sumXY += $x * $y;
        $sumX2 += $x * $x;
    }
    
    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $n;
    
    // Extract day-of-week patterns for seasonality (if we have enough data)
    $dayOfWeekPattern = [0,0,0,0,0,0,0]; // Sun=0, Mon=1, ...
    $dayOfWeekCount = [0,0,0,0,0,0,0];
    
    foreach ($historicalSales as $day) {
        $dow = date('w', strtotime($day['date']));
        $dayOfWeekPattern[$dow] += $day['sales'];
        $dayOfWeekCount[$dow]++;
    }
    
    $overallAvg = $sumY / $n;
    if ($overallAvg <= 0) {
        return array_fill(0, $forecastDays, 0.0);
    }
    for ($i = 0; $i < 7; $i++) {
        if ($dayOfWeekCount[$i] > 0) {
            $dayOfWeekPattern[$i] = ($dayOfWeekPattern[$i] / $dayOfWeekCount[$i]) / $overallAvg;
        } else {
            $dayOfWeekPattern[$i] = 1.0;
        }
    }
    
    // Generate forecasts
    $forecast = [];
    $lastDate = new DateTime(end($historicalSales)['date']);
    
    for ($i = 1; $i <= $forecastDays; $i++) {
        $x = $n + $i;
        $trendValue = $intercept + $slope * $x;
        if ($trendValue < 0) $trendValue = 0;
        
        // Apply day-of-week seasonality
        $forecastDate = clone $lastDate;
        $forecastDate->modify("+{$i} days");
        $dow = intval($forecastDate->format('w'));
        $seasonalFactor = $dayOfWeekPattern[$dow];
        
        $forecastedValue = $trendValue * $seasonalFactor;
        $forecast[] = round(max($forecastedValue, 0), 2);
    }
    
    return $forecast;
}

$forecastedSales = computeForecast($historicalSales, $forecastDays);

// ==========================================
// 3. DEMAND FORECAST (Top Menu Items)
// The demand candidates are the exact Top Selling Items snapshot shown in
// Data Analytics for the selected range.
// ==========================================
$demandReport = boycold_analytics_period_snapshot(
    $connect,
    $demandStartDate,
    $demandEndDate,
    $branchId
);
$demandItems = [];
$maxOrders = 0;
foreach ($demandReport['top_items'] as $item) {
    $orders = (int) $item['total_quantity'];
    if ($orders > $maxOrders) $maxOrders = $orders;
    $demandItems[] = [
        'product_name' => $item['product_name'],
        'product_image' => $item['product_image'],
        'total_orders' => $orders,
        'total_revenue' => (float) $item['total_revenue'],
        'days_sold' => (int) $item['days_sold'],
    ];
}

// Forecast demand for each top item using the overall sales forecast factor.
// Use the same calendar range as Data Analytics for each item's daily rate.
$totalHistoricalSales = array_sum(array_column($historicalSales, 'sales'));
$totalForecastedSales = array_sum($forecastedSales);

if ($totalHistoricalSales > 0) {
    $salesGrowthFactor = $totalForecastedSales / $totalHistoricalSales;
} else {
    $salesGrowthFactor = 1;
}

$previousTrendEnd = (new DateTimeImmutable($demandStartDate))->modify('-1 day')->format('Y-m-d');
$previousTrendStart = (new DateTimeImmutable($previousTrendEnd))
    ->modify('-' . ($demandHistoricalDays - 1) . ' days')
    ->format('Y-m-d');
$productTrends = boycold_analytics_product_period_comparison(
    $connect,
    $demandStartDate,
    $demandEndDate,
    $previousTrendStart,
    $previousTrendEnd,
    $branchId
);
$productTrendByName = [];
foreach ($productTrends as $productTrend) {
    $productTrendByName[$productTrend['product_name']] = $productTrend;
}

$demandForecast = [];
foreach ($demandItems as $item) {
    $avgDailyOrders = $demandHistoricalDays > 0 ? $item['total_orders'] / $demandHistoricalDays : 0;
    $forecastedOrders = round($avgDailyOrders * $forecastDays * $salesGrowthFactor);
    
    // Determine trend
    $trend = 'stable';
    $trendIcon = 'minus';
    $trendPercent = 0;
    
    $trendResult = $productTrendByName[$item['product_name']] ?? null;
    $recent = (int) ($trendResult['recent_quantity'] ?? 0);
    $previous = (int) ($trendResult['previous_quantity'] ?? 0);
    
    if ($previous > 0) {
        $trendPercent = round((($recent - $previous) / $previous) * 100, 1);
        if ($trendPercent > 5) {
            $trend = 'increasing';
            $trendIcon = 'arrow-up';
        } elseif ($trendPercent < -5) {
            $trend = 'decreasing';
            $trendIcon = 'arrow-down';
        }
    }
    
    $item['forecasted_orders'] = $forecastedOrders;
    $item['forecasted_quantity'] = $forecastedOrders;
    $item['trend'] = $trend;
    $item['trend_icon'] = $trendIcon;
    $item['trend_percent'] = $trendPercent;
    $item['progress'] = $maxOrders > 0 ? round(($item['total_orders'] / $maxOrders) * 100) : 0;
    
    $demandForecast[] = $item;
}

// ==========================================
// 4. PEAK HOURS FORECAST
// ==========================================
$peakHours = [];
$maxOrders = 0;
$hourlyData = [];
foreach ($historicalReport['time_of_day'] as $hour) {
    $orders = (int) $hour['orders'];
    $hourlyData[(int) $hour['hour']] = $orders;
    if ($orders > $maxOrders) $maxOrders = $orders;
}

// Generate all 24 hours with predictions where missing
for ($h = 0; $h < 24; $h++) {
    $orders = isset($hourlyData[$h]) ? $hourlyData[$h] : 0;
    $daysActive = $historicalDays;
    
    // Predict future peak using average orders per day
    $avgPerDay = $daysActive > 0 ? $orders / $daysActive : 0;
    
    // For hours with no data, estimate based on nearby hours
    if ($avgPerDay == 0) {
        // Check adjacent hours
        $neighbors = [];
        for ($offset = -2; $offset <= 2; $offset++) {
            $nh = ($h + $offset + 24) % 24;
            if (isset($hourlyData[$nh]) && $hourlyData[$nh] > 0) {
                $neighbors[] = $hourlyData[$nh];
            }
        }
        if (count($neighbors) > 0) {
            $avgPerDay = array_sum($neighbors) / count($neighbors) / $daysActive * 0.5;
        }
    }
    
    $predictedOrders = round($avgPerDay * $forecastDays);
    
    // Traffic level labels
    if ($maxOrders > 0) {
        $ratio = $orders / $maxOrders;
        if ($ratio >= 0.8) {
            $traffic = 'Very High';
            $color = '#E5383B';
        } elseif ($ratio >= 0.5) {
            $traffic = 'High';
            $color = '#EB7B45';
        } elseif ($ratio >= 0.3) {
            $traffic = 'Medium';
            $color = '#F2994A';
        } elseif ($ratio > 0) {
            $traffic = 'Low';
            $color = '#F5D76E';
        } else {
            $traffic = 'None';
            $color = '#E0E0E0';
        }
    } else {
        $traffic = 'None';
        $color = '#E0E0E0';
    }
    
    $peakHours[] = [
        'hour' => $h,
        'label' => sprintf('%02d:00 - %02d:00', $h, ($h + 1) % 24),
        'orders' => $orders,
        'predicted_orders' => $predictedOrders,
        'traffic' => $traffic,
        'color' => $color,
        'progress' => $maxOrders > 0 ? round(($orders / $maxOrders) * 100) : 0
    ];
}

// Sort peak hours by actual orders descending, keep top hours
usort($peakHours, function($a, $b) {
    $orderComparison = $b['orders'] <=> $a['orders'];
    return $orderComparison !== 0 ? $orderComparison : ($a['hour'] <=> $b['hour']);
});
$peakHours = array_slice($peakHours, 0, 8);

// Sort back by hour for display
usort($peakHours, function($a, $b) {
    return $a['hour'] <=> $b['hour'];
});

// ==========================================
// 5. TRENDING DRINKS PREDICTION
// ==========================================
$trendingItems = [];
foreach ($demandItems as $demandItem) {
    $productTrend = $productTrendByName[$demandItem['product_name']] ?? [
        'product_name' => $demandItem['product_name'],
        'product_image' => $demandItem['product_image'],
        'recent_quantity' => 0,
        'previous_quantity' => 0,
    ];
    $recent = (int) $productTrend['recent_quantity'];
    $previous = (int) $productTrend['previous_quantity'];
    
    if ($previous > 0) {
        $changePercent = round((($recent - $previous) / $previous) * 100, 1);
    } elseif ($recent > 0) {
        $changePercent = 100; // New item, big increase
    } else {
        $changePercent = -100; // Dropped off
    }
    
    $trendingItems[] = [
        'product_name' => $productTrend['product_name'],
        'product_image' => $demandItem['product_image'],
        'recent_quantity' => $recent,
        'previous_quantity' => $previous,
        'change_percent' => $changePercent,
        'is_up' => $changePercent >= 0
    ];
}

// ==========================================
// 6. INGREDIENT RESTOCK WARNINGS
// Uses the same live ingredients.stock values as Admin > Inventory together
// with product_ingredients amounts.  This is intentionally based on current
// complete servings, not forecasted raw-stock depletion or min_stock.
// ==========================================
$restockItems = [];
$criticalCount = 0;
$soonCount = 0;
foreach (boycold_get_ingredient_restock_capacities($connect, $branchId === 'all' ? 0 : (int) $branchId) as $ingredient) {
    // The API itself returns only warning items, so every Forecasting consumer
    // receives the same <= 25-serving rule without needing a second filter.
    if ($ingredient['status'] === 'ok') {
        continue;
    }

    if ($ingredient['status'] === 'critical') {
        $criticalCount++;
    } else {
        $soonCount++;
    }

    $restockItems[] = $ingredient;
}

// ==========================================
// 7. OVERALL STATS & INSIGHTS
// ==========================================

// Current week vs previous week comparison from the same Data Analytics
// snapshots used by the rest of this forecast.
$recentWeekStart = $demandStartDate;
$recentWeekEnd = $demandEndDate;
$previousWeekEnd = (new DateTimeImmutable($recentWeekStart))->modify('-1 day')->format('Y-m-d');
$previousWeekStart = (new DateTimeImmutable($previousWeekEnd))
    ->modify('-' . ($demandHistoricalDays - 1) . ' days')
    ->format('Y-m-d');
$recentWeekReport = boycold_analytics_period_snapshot(
    $connect,
    $recentWeekStart,
    $recentWeekEnd,
    $branchId
);
$previousWeekReport = boycold_analytics_period_snapshot(
    $connect,
    $previousWeekStart,
    $previousWeekEnd,
    $branchId
);

$thisWeekSales = (float) $recentWeekReport['summary']['total_sales'];
$lastWeekSales = (float) $previousWeekReport['summary']['total_sales'];
$salesChangePercent = $lastWeekSales > 0 ? round((($thisWeekSales - $lastWeekSales) / $lastWeekSales) * 100, 1) : 0;

// Predicted sales for next 14 days
$predictedSales14 = round(array_sum($forecastedSales), 2);

// Highest demand item
$highestDemandItem = !empty($demandForecast) ? $demandForecast[0]['product_name'] : 'N/A';
$highestDemandQty = !empty($demandForecast) ? $demandForecast[0]['forecasted_orders'] : 0;

// ==========================================
// BUILD RESPONSE
// ==========================================
$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'forecast_date' => date('Y-m-d'),
    'demand_range' => [
        'start_date' => $demandStartDate,
        'end_date' => $demandEndDate,
        'days' => $demandHistoricalDays,
    ],
    'analytics_source' => 'admin/data-analytics.php',
    'stats' => [
        'predicted_sales_next_14' => $predictedSales14,
        'sales_change_percent' => $salesChangePercent,
        'this_week_sales' => $thisWeekSales,
        'last_week_sales' => $lastWeekSales,
        'this_week_orders' => (int) $recentWeekReport['summary']['total_orders'],
        'last_week_orders' => (int) $previousWeekReport['summary']['total_orders'],
        'critical_restocks' => $criticalCount,
        'soon_restocks' => $soonCount,
        'highest_demand_item' => $highestDemandItem,
        'highest_demand_qty' => $highestDemandQty,
        'total_ingredients' => count($restockItems)
    ],
    'historical_sales' => $historicalSales,
    'forecasted_sales' => $forecastedSales,
    'historical_dates' => $historicalDates,
    'demand_forecast' => $demandForecast,
    'peak_hours' => $peakHours,
    'trending_items' => $trendingItems,
    'restock_items' => $restockItems,
    'insights' => [
        [
            'type' => $salesChangePercent >= 0 ? 'positive' : 'negative',
            'icon' => $salesChangePercent >= 0 ? 'arrow-trend-up' : 'arrow-trend-down',
            'heading' => 'Sales ' . ($salesChangePercent >= 0 ? 'increase' : 'decrease') . ' by ' . ($salesChangePercent > 0 ? '+' : '') . number_format($salesChangePercent, 2, '.', '') . '%',
            'desc' => $salesChangePercent >= 0 ? 'Great job! Your sales are higher than the previous period.' : 'Sales are lower than the previous period. Consider promotions.'
        ],
        [
            'type' => 'info',
            'icon' => 'star',
            'heading' => $highestDemandItem,
            'desc' => 'Top selling item with ~' . $highestDemandQty . ' units forecasted.'
        ],
        [
            'type' => 'warning',
            'icon' => 'clock',
            'heading' => 'Peak hours at ' . (!empty($peakHours) ? $peakHours[0]['label'] : 'N/A'),
            'desc' => 'Prepare your team and stocks before the rush.'
        ]
    ]
];

echo json_encode($response);