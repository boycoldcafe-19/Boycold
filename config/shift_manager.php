<?php

const POS_BUSINESS_TIMEZONE = 'Asia/Manila';

function pos_business_now(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone(POS_BUSINESS_TIMEZONE));
}

function pos_sales_date(?DateTimeImmutable $now = null): string
{
    $now = $now ?: pos_business_now();
    $cutoff = $now->setTime(2, 0, 0);
    return ($now < $cutoff ? $now->modify('-1 day') : $now)->format('Y-m-d');
}

function pos_shift_event(mysqli $connect, int $shiftId, int $branchId, string $event, ?int $employeeId = null): void
{
    $stmt = $connect->prepare(
        'INSERT INTO shift_events (shift_id, branch_id, employee_id, event_type) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('iiis', $shiftId, $branchId, $employeeId, $event);
    $stmt->execute();
    $stmt->close();
}

function pos_shift_sales(mysqli $connect, int $shiftId): array
{
    $stmt = $connect->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN LOWER(payment_method) IN ('cod', 'cash') THEN total ELSE 0 END), 0) AS cash_sales,
            COALESCE(SUM(CASE WHEN LOWER(payment_method) IN ('qrph', 'gcash') THEN total ELSE 0 END), 0) AS digital_sales,
            COALESCE(SUM(CASE WHEN LOWER(payment_method) IN ('cod', 'cash') THEN 1 ELSE 0 END), 0) AS cash_orders,
            COALESCE(SUM(CASE WHEN LOWER(payment_method) IN ('qrph', 'gcash') THEN 1 ELSE 0 END), 0) AS digital_orders,
            COALESCE(SUM(total), 0) AS total_sales,
            COUNT(*) AS total_orders
         FROM orders
         WHERE shift_id = ? AND (status != 'cancelled' OR status IS NULL)"
    );
    $stmt->bind_param('i', $shiftId);
    $stmt->execute();
    $sales = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    return [
        'cash_sales' => (float) ($sales['cash_sales'] ?? 0),
        'digital_sales' => (float) ($sales['digital_sales'] ?? 0),
        'cash_orders' => (int) ($sales['cash_orders'] ?? 0),
        'digital_orders' => (int) ($sales['digital_orders'] ?? 0),
        'total_sales' => (float) ($sales['total_sales'] ?? 0),
        'total_orders' => (int) ($sales['total_orders'] ?? 0),
    ];
}

function pos_reconcile_branch_shift(mysqli $connect, int $branchId, ?int $employeeId = null, bool $createMissing = true): ?array
{
    if ($branchId <= 0) {
        return null;
    }

    $lockName = 'boycold_shift_branch_' . $branchId;
    $lockStmt = $connect->prepare('SELECT GET_LOCK(?, 10) AS acquired');
    $lockStmt->bind_param('s', $lockName);
    $lockStmt->execute();
    $lockAcquired = (int) ($lockStmt->get_result()->fetch_assoc()['acquired'] ?? 0) === 1;
    $lockStmt->close();
    if (!$lockAcquired) {
        throw new RuntimeException('Could not acquire the branch shift lock.');
    }

    try {
        $salesDate = pos_sales_date();
        $connect->begin_transaction();

        $existingStmt = $connect->prepare(
            'SELECT id, status FROM shift_logs WHERE branch_id = ? AND shift_date = ? ORDER BY id DESC LIMIT 1 FOR UPDATE'
        );
        $existingStmt->bind_param('is', $branchId, $salesDate);
        $existingStmt->execute();
        $existingSalesDayShift = $existingStmt->get_result()->fetch_assoc() ?: null;
        $existingStmt->close();

        $openStmt = $connect->prepare(
            "SELECT * FROM shift_logs
             WHERE branch_id = ? AND status = 'open'
             ORDER BY opened_at ASC, id ASC FOR UPDATE"
        );
        $openStmt->bind_param('i', $branchId);
        $openStmt->execute();
        $openResult = $openStmt->get_result();
        $openShifts = [];
        while ($row = $openResult->fetch_assoc()) {
            $openShifts[] = $row;
        }
        $openStmt->close();

        $currentShift = null;
        foreach ($openShifts as $shift) {
            if ((string) ($shift['shift_date'] ?? '') === $salesDate && $currentShift === null) {
                $currentShift = $shift;
                continue;
            }

            $sales = pos_shift_sales($connect, (int) $shift['id']);
            $closingCash = (float) $shift['opening_cash_float'] + $sales['cash_sales'];
            $closeStmt = $connect->prepare(
                "UPDATE shift_logs SET closing_cash_count = ?, cash_sales = ?, gcash_sales = ?,
                    total_sales = ?, cash_orders = ?, gcash_orders = ?, total_orders = ?,
                    cash_difference = 0, closed_at = NOW(), status = 'auto-closed',
                    close_reason = 'automatic'
                 WHERE id = ? AND branch_id = ? AND status = 'open'"
            );
            $shiftId = (int) $shift['id'];
            $closeStmt->bind_param(
                'ddddiiiii',
                $closingCash,
                $sales['cash_sales'],
                $sales['digital_sales'],
                $sales['total_sales'],
                $sales['cash_orders'],
                $sales['digital_orders'],
                $sales['total_orders'],
                $shiftId,
                $branchId
            );
            $closeStmt->execute();
            $closeStmt->close();
            pos_shift_event($connect, $shiftId, $branchId, 'automatic-close', $employeeId);
        }

        if ($currentShift === null && $existingSalesDayShift === null && $createMissing) {
            $employeeForShift = (int) ($employeeId ?? 0);
            if ($employeeForShift <= 0) {
                $employeeStmt = $connect->prepare(
                    'SELECT id FROM employees WHERE branch_id = ? AND is_active = 1 ORDER BY id LIMIT 1'
                );
                $employeeStmt->bind_param('i', $branchId);
                $employeeStmt->execute();
                $employeeForShift = (int) (($employeeStmt->get_result()->fetch_assoc()['id'] ?? 0));
                $employeeStmt->close();
            }
            if ($employeeForShift <= 0) {
                throw new RuntimeException('No active employee is available for the branch shift.');
            }

            $openInsert = $connect->prepare(
                "INSERT INTO shift_logs
                    (branch_id, employee_id, opening_cash_float, shift_date, status, open_reason)
                 VALUES (?, ?, 0, ?, 'open', 'automatic')"
            );
            $openInsert->bind_param('iis', $branchId, $employeeForShift, $salesDate);
            $openInsert->execute();
            $newShiftId = (int) $openInsert->insert_id;
            $openInsert->close();
            pos_shift_event($connect, $newShiftId, $branchId, 'automatic-open', $employeeForShift);

            $currentStmt = $connect->prepare('SELECT * FROM shift_logs WHERE id = ? FOR UPDATE');
            $currentStmt->bind_param('i', $newShiftId);
            $currentStmt->execute();
            $currentShift = $currentStmt->get_result()->fetch_assoc();
            $currentStmt->close();
        }

        $connect->commit();
        return $currentShift;
    } catch (Throwable $e) {
        $connect->rollback();
        throw $e;
    } finally {
        $releaseStmt = $connect->prepare('SELECT RELEASE_LOCK(?)');
        $releaseStmt->bind_param('s', $lockName);
        $releaseStmt->execute();
        $releaseStmt->close();
    }
}

function pos_get_branch_open_shift(mysqli $connect, int $branchId): ?array
{
    $stmt = $connect->prepare(
        "SELECT * FROM shift_logs WHERE branch_id = ? AND status = 'open'
         AND shift_date = ? ORDER BY opened_at DESC, id DESC LIMIT 1"
    );
    $salesDate = pos_sales_date();
    $stmt->bind_param('is', $branchId, $salesDate);
    $stmt->execute();
    $shift = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $shift;
}
