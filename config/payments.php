<?php
require_once __DIR__ . '/paymongo.php';

function boycold_payment_label(string $method, string $status): string
{
    $method = strtolower($method);
    $status = strtolower($status);

    if ($method === 'cod') {
        return $status === 'paid' ? 'COD - PAID' : 'COD - UNPAID';
    }

    if ($method === 'qrph') {
        $map = [
            'paid' => 'QRPh - PAID',
            'pending' => 'QRPh - PENDING',
            'failed' => 'QRPh - FAILED',
            'expired' => 'QRPh - EXPIRED',
        ];
        return $map[$status] ?? ('QRPh - ' . strtoupper($status));
    }

    return strtoupper($method . ' - ' . $status);
}

function boycold_ensure_payment_schema(mysqli $connect): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $col = $connect->query("SHOW COLUMNS FROM orders LIKE 'payment_reference'");
    if ($col && $col->num_rows === 0) {
        $connect->query("ALTER TABLE orders ADD COLUMN payment_reference VARCHAR(80) DEFAULT NULL");
        $connect->query("ALTER TABLE orders ADD INDEX idx_payment_reference (payment_reference)");
    }

    $statusCol = $connect->query("SHOW COLUMNS FROM orders LIKE 'payment_status'");
    $row = $statusCol ? $statusCol->fetch_assoc() : null;
    $type = strtolower((string) ($row['Type'] ?? ''));
    if (strpos($type, 'pending') === false || strpos($type, 'failed') === false || strpos($type, 'expired') === false) {
        $connect->query(
            "ALTER TABLE orders
             MODIFY COLUMN payment_status ENUM('unpaid','pending','paid','failed','expired','cancelled')
             COLLATE utf8mb4_unicode_ci DEFAULT 'unpaid'"
        );
    }

    $connect->query(
        "CREATE TABLE IF NOT EXISTS paymongo_webhook_events (
            event_id VARCHAR(80) NOT NULL,
            event_type VARCHAR(80) NOT NULL,
            order_id INT DEFAULT NULL,
            processed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (event_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $expiryCol = $connect->query("SHOW COLUMNS FROM orders LIKE 'payment_expires_at'");
    if ($expiryCol && $expiryCol->num_rows === 0) {
        $connect->query("ALTER TABLE orders ADD COLUMN payment_expires_at DATETIME NULL DEFAULT NULL AFTER payment_reference");
        $connect->query("ALTER TABLE orders ADD INDEX idx_payment_expires_at (payment_expires_at)");
    }
}

function boycold_create_qrph_for_order(mysqli $connect, int $orderId, float $total): array
{
    $qr = paymongo_create_qrph($orderId, $total, 'BoyCold Cafe Order #' . $orderId);

    $ref = $qr['payment_intent_id'];
    $stmt = $connect->prepare(
        "UPDATE orders
         SET payment_reference = ?, payment_status = 'pending',
             payment_expires_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE)
         WHERE id = ? AND payment_method = 'qrph'"
    );
    $stmt->bind_param('si', $ref, $orderId);
    $stmt->execute();
    $stmt->close();

    return $qr;
}

function boycold_apply_qrph_result(mysqli $connect, string $paymentIntentId, int $paidCentavos, string $eventId, string $resultStatus): bool
{
    boycold_ensure_payment_schema($connect);

    $connect->begin_transaction();
    try {
        $stmt = $connect->prepare(
            "SELECT id, total, payment_method, payment_status, payment_expires_at, status
             FROM orders
             WHERE payment_reference = ?
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->bind_param('s', $paymentIntentId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) {
            $connect->rollback();
            return false;
        }

        $orderId = (int) $order['id'];
        $eventStmt = $connect->prepare(
            "INSERT IGNORE INTO paymongo_webhook_events (event_id, event_type, order_id) VALUES (?, ?, ?)"
        );
        $eventStmt->bind_param('ssi', $eventId, $resultStatus, $orderId);
        $eventStmt->execute();
        $inserted = $eventStmt->affected_rows > 0;
        $eventStmt->close();

        if (!$inserted) {
            $connect->commit();
            return true;
        }

        if (strtolower((string) $order['payment_method']) !== 'qrph') {
            $connect->commit();
            return true;
        }

        $expected = (int) round(((float) $order['total']) * 100);

        if ($resultStatus === 'paid') {
            if (!empty($order['payment_expires_at']) && strtotime($order['payment_expires_at']) <= time()) {
                $expired = $connect->prepare(
                    "UPDATE orders
                     SET payment_status = 'expired', status = IF(status = 'pending', 'cancelled', status)
                     WHERE id = ? AND payment_status <> 'paid'"
                );
                $expired->bind_param('i', $orderId);
                $expired->execute();
                $expired->close();
                $connect->commit();
                return true;
            }

            if ($paidCentavos !== $expected) {
                $fail = $connect->prepare(
                    "UPDATE orders SET payment_status = 'failed'
                     WHERE id = ? AND payment_status IN ('pending','unpaid')"
                );
                $fail->bind_param('i', $orderId);
                $fail->execute();
                $fail->close();
                $connect->commit();
                return true;
            }

            if (strtolower((string) $order['payment_status']) === 'paid') {
                $connect->commit();
                return true;
            }

            $statusSql = "payment_status = 'paid'";
            if (strtolower((string) $order['status']) === 'pending') {
                $statusSql .= ", status = 'confirmed'";
            }
            $paid = $connect->prepare("UPDATE orders SET $statusSql WHERE id = ? AND payment_status <> 'paid'");
            $paid->bind_param('i', $orderId);
            $paid->execute();
            $paid->close();
        } elseif (in_array($resultStatus, ['failed', 'expired'], true)) {
            if (strtolower((string) $order['payment_status']) !== 'paid') {
                $upd = $connect->prepare(
                    "UPDATE orders
                     SET payment_status = ?,
                         status = IF(? = 'expired' AND status = 'pending', 'cancelled', status)
                     WHERE id = ? AND payment_status <> 'paid'"
                );
                $upd->bind_param('ssi', $resultStatus, $resultStatus, $orderId);
                $upd->execute();
                $upd->close();
            }
        }

        $connect->commit();
        return true;
    } catch (Throwable $e) {
        $connect->rollback();
        throw $e;
    }
}

function boycold_confirm_cod_payment(mysqli $connect, int $orderId, int $branchId = 0): array
{
    $sql = "UPDATE orders
            SET payment_status = 'paid'
            WHERE id = ?
              AND payment_method = 'cod'
              AND payment_status <> 'paid'";
    $types = 'i';
    $params = [$orderId];

    if ($branchId > 0) {
        $sql .= ' AND branch_id = ?';
        $types .= 'i';
        $params[] = $branchId;
    }

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    if (!$ok) {
        return ['success' => false, 'error' => 'COD payment could not be confirmed.'];
    }

    return ['success' => true, 'message' => 'COD payment marked as paid.'];
}
