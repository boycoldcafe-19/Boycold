<?php
require_once __DIR__ . '/../db_config.php';

function hasColumn(mysqli $connect, string $table, string $column): bool
{
    $stmt = $connect->prepare(
        'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1'
    );
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $found = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $found;
}

function hasIndex(mysqli $connect, string $table, string $index): bool
{
    $stmt = $connect->prepare(
        'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1'
    );
    $stmt->bind_param('ss', $table, $index);
    $stmt->execute();
    $found = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $found;
}

function runChange(mysqli $connect, string $sql): void
{
    if (!$connect->query($sql)) {
        throw new RuntimeException($connect->error);
    }
}

function generateUniqueToken(mysqli $connect): string
{
    do {
        $token = bin2hex(random_bytes(16));
        $stmt = $connect->prepare('SELECT id FROM users WHERE loyalty_token = ? LIMIT 1');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
    } while ($exists);

    return $token;
}

function generateUniqueCardNumber(mysqli $connect, int $userId): string
{
    $base = 'BY-' . date('Y') . str_pad((string) $userId, 3, '0', STR_PAD_LEFT);
    $cardNo = $base;
    $suffix = 0;

    do {
        $stmt = $connect->prepare('SELECT id FROM users WHERE card_no = ? LIMIT 1');
        $stmt->bind_param('s', $cardNo);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$existing || (int) $existing['id'] === $userId) {
            return $cardNo;
        }

        $suffix++;
        $cardNo = 'BY-' . date('Y') . str_pad((string) ($userId * 1000 + $suffix), 6, '0', STR_PAD_LEFT);
    } while ($suffix < 1000);

    throw new RuntimeException('Unable to generate a unique card number for user ' . $userId . '.');
}

function backfillLoyaltyIdentities(mysqli $connect): void
{
    $users = $connect->query(
        "SELECT id, card_no, loyalty_token FROM users ORDER BY id"
    )->fetch_all(MYSQLI_ASSOC);

    foreach ($users as $user) {
        $userId = (int) $user['id'];
        $cardNo = trim((string) ($user['card_no'] ?? ''));
        $token = trim((string) ($user['loyalty_token'] ?? ''));

        if ($cardNo === '') {
            $cardNo = generateUniqueCardNumber($connect, $userId);
            $stmt = $connect->prepare('UPDATE users SET card_no = ? WHERE id = ?');
            $stmt->bind_param('si', $cardNo, $userId);
            $stmt->execute();
            $stmt->close();
        }

        if ($token === '') {
            $token = generateUniqueToken($connect);
            $stmt = $connect->prepare('UPDATE users SET loyalty_token = ? WHERE id = ?');
            $stmt->bind_param('si', $token, $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

try {
    if (!hasColumn($connect, 'users', 'loyalty_token')) {
        runChange($connect, "ALTER TABLE users ADD COLUMN loyalty_token VARCHAR(64) NULL");
    }

    if (!hasColumn($connect, 'orders', 'user_id')) {
        runChange($connect, "ALTER TABLE orders ADD COLUMN user_id INT NULL");
    }

    if (!hasColumn($connect, 'orders', 'loyalty_awarded')) {
        runChange($connect, "ALTER TABLE orders ADD COLUMN loyalty_awarded TINYINT(1) NOT NULL DEFAULT 0");
    }

    if (!hasIndex($connect, 'users', 'uq_users_card_no')) {
        $duplicates = $connect->query(
            "SELECT card_no FROM users WHERE card_no IS NOT NULL AND card_no <> '' GROUP BY card_no HAVING COUNT(*) > 1 LIMIT 1"
        );
        if ($duplicates->num_rows > 0) {
            throw new RuntimeException('Duplicate card_no values exist; resolve them before adding uq_users_card_no.');
        }
        runChange($connect, "ALTER TABLE users ADD UNIQUE KEY uq_users_card_no (card_no)");
    }

    if (!hasIndex($connect, 'users', 'uq_users_loyalty_token')) {
        $duplicates = $connect->query(
            "SELECT loyalty_token FROM users WHERE loyalty_token IS NOT NULL AND loyalty_token <> '' GROUP BY loyalty_token HAVING COUNT(*) > 1 LIMIT 1"
        );
        if ($duplicates->num_rows > 0) {
            throw new RuntimeException('Duplicate loyalty_token values exist; resolve them before adding uq_users_loyalty_token.');
        }
        runChange($connect, "ALTER TABLE users ADD UNIQUE KEY uq_users_loyalty_token (loyalty_token)");
    }

    if (!hasIndex($connect, 'orders', 'idx_orders_user_id')) {
        runChange($connect, "ALTER TABLE orders ADD KEY idx_orders_user_id (user_id)");
    }

    backfillLoyaltyIdentities($connect);

    echo "Loyalty card database migration completed.\n";
} catch (Throwable $error) {
    http_response_code(500);
    echo "Loyalty card database migration failed: " . $error->getMessage() . "\n";
    exit(1);
}
