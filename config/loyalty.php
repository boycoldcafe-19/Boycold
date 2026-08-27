<?php

const BOYCOLD_LOYALTY_MAX_STAMPS = 10;
const BOYCOLD_LOYALTY_RULE = 'item_quantity'; // Change to 'completed_order' for 1 stamp per completed order.
const BOYCOLD_LOYALTY_STAMPS_PER_QUALIFYING_ITEM = 1;
const BOYCOLD_LOYALTY_RESET_ON_REWARD = false;

function generateLoyaltyToken(mysqli $connect): string
{
    do {
        $token = bin2hex(random_bytes(16));
        $stmt = $connect->prepare("SELECT id FROM users WHERE loyalty_token = ? LIMIT 1");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } while ($exists);

    return $token;
}

function ensureUserLoyaltyToken(mysqli $connect, int $userId): string
{
    if ($userId <= 0) {
        return '';
    }

    $stmt = $connect->prepare("SELECT loyalty_token FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return '';
    }

    $existingToken = trim((string) ($user['loyalty_token'] ?? ''));
    if ($existingToken !== '') {
        return $existingToken;
    }

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $token = generateLoyaltyToken($connect);
        $updateStmt = $connect->prepare(
            "UPDATE users
             SET loyalty_token = ?
             WHERE id = ? AND (loyalty_token IS NULL OR loyalty_token = '')"
        );
        $updateStmt->bind_param('si', $token, $userId);

        if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
            $updateStmt->close();
            return $token;
        }

        $updateStmt->close();

        $refreshStmt = $connect->prepare("SELECT loyalty_token FROM users WHERE id = ? LIMIT 1");
        $refreshStmt->bind_param('i', $userId);
        $refreshStmt->execute();
        $refresh = $refreshStmt->get_result()->fetch_assoc();
        $refreshStmt->close();

        $refreshedToken = trim((string) ($refresh['loyalty_token'] ?? ''));
        if ($refreshedToken !== '') {
            return $refreshedToken;
        }
    }

    return '';
}

function ensureLoyaltyCardNumber(mysqli $connect, int $userId): string
{
    if ($userId <= 0) {
        return '';
    }

    $getStmt = $connect->prepare("SELECT card_no FROM users WHERE id = ? LIMIT 1");
    $getStmt->bind_param('i', $userId);
    $getStmt->execute();
    $existing = $getStmt->get_result()->fetch_assoc();
    $getStmt->close();

    if (!$existing) {
        return '';
    }

    $currentCardNo = trim((string) ($existing['card_no'] ?? ''));
    if ($currentCardNo !== '') {
        return $currentCardNo;
    }

    $cardNo = 'BY-' . date('Y') . str_pad((string) $userId, 3, '0', STR_PAD_LEFT);

    $checkStmt = $connect->prepare("SELECT id FROM users WHERE card_no = ? AND id <> ? LIMIT 1");
    $checkStmt->bind_param('si', $cardNo, $userId);
    $checkStmt->execute();
    $exists = (bool) $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if ($exists) {
        $cardNo = 'BY-' . date('Y') . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    $updateStmt = $connect->prepare("UPDATE users SET card_no = ? WHERE id = ?");
    $updateStmt->bind_param('si', $cardNo, $userId);
    $updateStmt->execute();
    $updateStmt->close();

    return $cardNo;
}

function getLoyaltySummary(int $totalStamps): array
{
    $maxStamps = BOYCOLD_LOYALTY_MAX_STAMPS;
    $safeTotal = max(0, $totalStamps);
    $displayStamps = min($maxStamps, $safeTotal);
    $remaining = max(0, $maxStamps - $displayStamps);
    $rewardAvailable = $safeTotal >= $maxStamps;

    if ($rewardAvailable) {
        $status = 'Reward available';
    } elseif ($displayStamps === 0) {
        $status = 'No stamps yet';
    } else {
        $status = $remaining . ' stamp' . ($remaining === 1 ? '' : 's') . ' until reward';
    }

    return [
        'stamps' => $displayStamps,
        'total_stamps' => $safeTotal,
        'max_stamps' => $maxStamps,
        'remaining' => $remaining,
        'reward_available' => $rewardAvailable,
        'reward_status' => $status,
    ];
}

function buildLoyaltyScanUrl(string $token): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/boycoldv2/index.php'));
    $basePath = preg_replace('#/(User|POS|api|loyalty|config)(/.*)?$#', '', $scriptDir);

    if ($basePath === '' || $basePath === '.' || $basePath === '/') {
        $basePath = '/boycoldv2';
    }

    return rtrim($scheme . '://' . $host . $basePath, '/') . '/loyalty/scan.php?t=' . rawurlencode($token);
}

function extractLoyaltyTokenFromPayload(string $payload): string
{
    $payload = trim($payload);
    if ($payload === '') {
        return '';
    }

    $token = $payload;
    $query = parse_url($payload, PHP_URL_QUERY);
    if (is_string($query) && $query !== '') {
        parse_str($query, $params);
        if (!empty($params['t'])) {
            $token = (string) $params['t'];
        }
    }

    $token = strtolower(trim(rawurldecode($token)));

    return preg_match('/^[a-f0-9]{32,64}$/', $token) ? $token : '';
}

function getLoyaltyCustomerByToken(mysqli $connect, string $payload): ?array
{
    $token = extractLoyaltyTokenFromPayload($payload);
    if ($token === '') {
        return null;
    }

    $stmt = $connect->prepare(
        "SELECT id, firstname, lastname, user_name, card_no, loyalty_beans, loyalty_stamps
         FROM users
         WHERE loyalty_token = ?
         LIMIT 1"
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return null;
    }

    $summary = getLoyaltySummary((int) ($user['loyalty_stamps'] ?? 0));

    return [
        'id' => (int) $user['id'],
        'name' => trim((string) $user['firstname'] . ' ' . (string) $user['lastname']),
        'user_name' => (string) ($user['user_name'] ?? ''),
        'card_no' => (string) ($user['card_no'] ?? ''),
        'loyalty_beans' => (int) ($user['loyalty_beans'] ?? 0),
        'loyalty_stamps' => $summary['stamps'],
        'stamps' => $summary['stamps'],
        'total_stamps' => $summary['total_stamps'],
        'max_stamps' => $summary['max_stamps'],
        'remaining' => $summary['remaining'],
        'reward_available' => $summary['reward_available'],
        'reward_status' => $summary['reward_status'],
    ];
}

function calculateLoyaltyStampsForOrder(mysqli $connect, int $orderId): int
{
    if ($orderId <= 0) {
        return 0;
    }

    if (BOYCOLD_LOYALTY_RULE === 'completed_order') {
        return 1;
    }

    $stmt = $connect->prepare(
        "SELECT COALESCE(SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END), 0) AS qualifying_items
         FROM order_items
         WHERE order_id = ?"
    );
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return max(0, (int) ($row['qualifying_items'] ?? 0)) * BOYCOLD_LOYALTY_STAMPS_PER_QUALIFYING_ITEM;
}

function setOrderLoyaltyAwarded(mysqli $connect, int $orderId, int $userId = 0): void
{
    if ($userId > 0) {
        $stmt = $connect->prepare(
            "UPDATE orders
             SET user_id = COALESCE(user_id, ?),
                 loyalty_awarded = 1
             WHERE id = ?"
        );
        $stmt->bind_param('ii', $userId, $orderId);
    } else {
        $stmt = $connect->prepare("UPDATE orders SET loyalty_awarded = 1 WHERE id = ?");
        $stmt->bind_param('i', $orderId);
    }

    $stmt->execute();
    $stmt->close();
}

function awardLoyaltyForCompletedOrder(
    mysqli $connect,
    int $orderId,
    string $userName = '',
    int $branchId = 0,
    int $deviceId = 0,
    int $employeeId = 0,
    bool $manageTransaction = true
): bool {
    if ($orderId <= 0) {
        return false;
    }

    try {
        if ($manageTransaction) {
            $connect->begin_transaction();
        }

        $orderStmt = $connect->prepare(
            "SELECT id, user_id, user_name, status, branch_id, device_id, cashier_id, loyalty_awarded
             FROM orders
             WHERE id = ?
             LIMIT 1
             FOR UPDATE"
        );
        $orderStmt->bind_param('i', $orderId);
        $orderStmt->execute();
        $order = $orderStmt->get_result()->fetch_assoc();
        $orderStmt->close();

        if (!$order || strtolower((string) $order['status']) !== 'completed') {
            if ($manageTransaction) {
                $connect->commit();
            }
            return false;
        }

        if ((int) ($order['loyalty_awarded'] ?? 0) === 1) {
            if ($manageTransaction) {
                $connect->commit();
            }
            return true;
        }

        $transactionStmt = $connect->prepare(
            "SELECT id
             FROM loyalty_transactions
             WHERE order_id = ? AND transaction_type IN ('bean_award', 'stamp_award')
             LIMIT 1"
        );
        $transactionStmt->bind_param('i', $orderId);
        $transactionStmt->execute();
        $alreadyRecorded = (bool) $transactionStmt->get_result()->fetch_assoc();
        $transactionStmt->close();

        if ($alreadyRecorded) {
            setOrderLoyaltyAwarded($connect, $orderId, (int) ($order['user_id'] ?? 0));
            if ($manageTransaction) {
                $connect->commit();
            }
            return true;
        }

        $orderUserName = trim((string) ($order['user_name'] ?? $userName));
        if ($orderUserName === '' || strcasecmp($orderUserName, 'Walk-in Customer') === 0) {
            setOrderLoyaltyAwarded($connect, $orderId);
            if ($manageTransaction) {
                $connect->commit();
            }
            return true;
        }

        $orderUserId = (int) ($order['user_id'] ?? 0);
        if ($orderUserId > 0) {
            $userStmt = $connect->prepare(
                "SELECT id, card_no, loyalty_beans, loyalty_stamps
                 FROM users
                 WHERE id = ?
                 LIMIT 1
                 FOR UPDATE"
            );
            $userStmt->bind_param('i', $orderUserId);
        } else {
            $userStmt = $connect->prepare(
                "SELECT id, card_no, loyalty_beans, loyalty_stamps
                 FROM users
                 WHERE user_name = ?
                 LIMIT 1
                 FOR UPDATE"
            );
            $userStmt->bind_param('s', $orderUserName);
        }

        $userStmt->execute();
        $user = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();

        if (!$user) {
            if ($manageTransaction) {
                $connect->commit();
            }
            return false;
        }

        $userId = (int) $user['id'];
        $cardNo = ensureLoyaltyCardNumber($connect, $userId);
        ensureUserLoyaltyToken($connect, $userId);

        $stampsToAward = calculateLoyaltyStampsForOrder($connect, $orderId);
        if ($stampsToAward <= 0) {
            setOrderLoyaltyAwarded($connect, $orderId, $userId);
            if ($manageTransaction) {
                $connect->commit();
            }
            return true;
        }

        $previousStamps = max(0, (int) ($user['loyalty_stamps'] ?? 0));
        $newStamps = $previousStamps + $stampsToAward;
        if (BOYCOLD_LOYALTY_RESET_ON_REWARD && $newStamps >= BOYCOLD_LOYALTY_MAX_STAMPS) {
            $newStamps = $newStamps % BOYCOLD_LOYALTY_MAX_STAMPS;
        }

        $updateStmt = $connect->prepare(
            "UPDATE users
             SET loyalty_beans = 0,
                 loyalty_stamps = ?
             WHERE id = ?"
        );
        $updateStmt->bind_param('ii', $newStamps, $userId);
        $updateStmt->execute();
        $updateStmt->close();

        setOrderLoyaltyAwarded($connect, $orderId, $userId);

        $branchId = $branchId > 0 ? $branchId : (int) ($order['branch_id'] ?? 0);
        $deviceId = $deviceId > 0 ? $deviceId : (int) ($order['device_id'] ?? 0);
        $employeeId = $employeeId > 0 ? $employeeId : (int) ($order['cashier_id'] ?? 0);

        $insertStmt = $connect->prepare(
            "INSERT INTO loyalty_transactions
                (user_id, card_no, branch_id, device_id, employee_id, transaction_type,
                 points_awarded, previous_balance, new_balance, order_id)
             VALUES (?, ?, ?, ?, ?, 'stamp_award', ?, ?, ?, ?)"
        );
        $insertStmt->bind_param(
            'isiiiiiii',
            $userId,
            $cardNo,
            $branchId,
            $deviceId,
            $employeeId,
            $stampsToAward,
            $previousStamps,
            $newStamps,
            $orderId
        );
        $insertStmt->execute();
        $insertStmt->close();

        if ($manageTransaction) {
            $connect->commit();
        }

        return true;
    } catch (Throwable $e) {
        if ($manageTransaction) {
            try {
                $connect->rollback();
            } catch (Throwable $rollbackError) {
                // Preserve the original error in the log.
            }

            error_log('Loyalty award failed for order #' . $orderId . ': ' . $e->getMessage());
            return false;
        }

        throw $e;
    }
}

function syncLoyaltyStampsFromCompletedOrders(mysqli $connect, int $userId): void
{
    if ($userId <= 0) {
        return;
    }

    $stmt = $connect->prepare(
        "SELECT o.id, o.user_name
         FROM orders o
         INNER JOIN users u ON (o.user_id = u.id OR (o.user_id IS NULL AND o.user_name = u.user_name))
         WHERE u.id = ?
           AND o.status = 'completed'
           AND o.loyalty_awarded = 0
         ORDER BY o.id ASC"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($orders as $order) {
        awardLoyaltyForCompletedOrder(
            $connect,
            (int) $order['id'],
            (string) ($order['user_name'] ?? '')
        );
    }
}
