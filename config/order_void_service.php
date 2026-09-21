<?php

/**
 * Keeps POS-initiated voids distinct from customer/order cancellations while
 * retaining the existing cancelled status for sales and payment reporting.
 */
function boycold_ensure_order_void_schema(mysqli $connect): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $columns = [
        'voided_at' => 'DATETIME NULL DEFAULT NULL AFTER payment_status',
        'voided_by' => 'INT NULL DEFAULT NULL AFTER voided_at',
        // Links the replacement sale to the original POS void. This lets
        // customer-facing lists exclude only adjustment transactions.
        'void_replacement_for_order_id' => 'INT NULL DEFAULT NULL AFTER voided_by',
    ];

    foreach ($columns as $column => $definition) {
        $safeColumn = $connect->real_escape_string($column);
        $check = $connect->query("SHOW COLUMNS FROM orders LIKE '$safeColumn'");
        if ($check instanceof mysqli_result && $check->num_rows > 0) {
            continue;
        }

        if (!$connect->query("ALTER TABLE orders ADD COLUMN `$column` $definition")) {
            throw new RuntimeException("Unable to add orders.$column: " . $connect->error);
        }
    }

    $ready = true;
}

/**
 * Marks void adjustments created before the void columns were introduced.
 * The activity log contains both order IDs, so normal customer cancellations
 * are never included in this migration.
 */
function boycold_backfill_legacy_pos_voids(mysqli $connect): int
{
    $activityLogTable = $connect->query("SHOW TABLES LIKE 'activity_logs'");
    if (!($activityLogTable instanceof mysqli_result) || $activityLogTable->num_rows === 0) {
        return 0;
    }

    $action = 'order_voided_adjusted';
    $logs = $connect->prepare(
        'SELECT actor_id, entity_id, metadata, created_at
         FROM activity_logs
         WHERE action = ?
         ORDER BY id ASC'
    );
    $logs->bind_param('s', $action);
    $logs->execute();
    $result = $logs->get_result();

    $markVoided = $connect->prepare(
        "UPDATE orders
         SET voided_at = COALESCE(voided_at, ?),
             voided_by = COALESCE(voided_by, NULLIF(?, 0))
         WHERE id = ? AND status = 'cancelled' AND voided_at IS NULL"
    );
    $linkReplacement = $connect->prepare(
        'UPDATE orders
         SET void_replacement_for_order_id = ?
         WHERE id = ? AND void_replacement_for_order_id IS NULL'
    );

    $updated = 0;
    while ($log = $result->fetch_assoc()) {
        $metadata = json_decode((string) ($log['metadata'] ?? ''), true);
        if (!is_array($metadata)) {
            continue;
        }

        $voidedOrderId = (int) ($metadata['voided_order_id'] ?? 0);
        $replacementOrderId = (int) ($metadata['replacement_order_id'] ?? $log['entity_id'] ?? 0);
        if ($voidedOrderId <= 0 || $replacementOrderId <= 0) {
            continue;
        }

        $voidedAt = (string) ($log['created_at'] ?? date('Y-m-d H:i:s'));
        $voidedBy = max(0, (int) ($log['actor_id'] ?? 0));
        $markVoided->bind_param('sii', $voidedAt, $voidedBy, $voidedOrderId);
        $markVoided->execute();
        $updated += max(0, $markVoided->affected_rows);

        $linkReplacement->bind_param('ii', $voidedOrderId, $replacementOrderId);
        $linkReplacement->execute();
    }

    $linkReplacement->close();
    $markVoided->close();
    $logs->close();

    return $updated;
}

function boycold_order_was_voided(array $order): bool
{
    return !empty($order['voided_at']);
}
