<?php

/**
 * Shared audit logger for actions that cannot be reconstructed from the
 * business tables later (for example a deleted product or a report export).
 *
 * The logger intentionally fails silently for the caller: an activity-log
 * write must never stop an order, menu update, logout, or settings update.
 */
function boycold_activity_log_available(mysqli $connect): bool
{
    static $available = null;

    if ($available !== null) {
        return $available;
    }

    try {
        $table = $connect->query("SHOW TABLES LIKE 'activity_logs'");
        $available = $table instanceof mysqli_result && $table->num_rows > 0;
    } catch (Throwable $exception) {
        $available = false;
    }

    return $available;
}

/**
 * @param array{
 *   category: string,
 *   action: string,
 *   summary: string,
 *   details?: string|null,
 *   actor_id?: int|null,
 *   actor_type?: string|null,
 *   branch_id?: int|null,
 *   entity_type?: string|null,
 *   entity_id?: int|null,
 *   metadata?: array|string|null
 * } $event
 */
function boycold_log_activity(mysqli $connect, array $event): bool
{
    if (!boycold_activity_log_available($connect)) {
        return false;
    }

    $allowedCategories = ['exports', 'loyalty', 'menu', 'login', 'orders', 'admin', 'shift', 'inventory', 'system'];
    $category = strtolower(trim((string) ($event['category'] ?? 'system')));
    if (!in_array($category, $allowedCategories, true)) {
        $category = 'system';
    }

    $action = substr(trim((string) ($event['action'] ?? 'recorded')), 0, 80);
    $summary = substr(trim((string) ($event['summary'] ?? 'System activity')), 0, 255);
    if ($action === '' || $summary === '') {
        return false;
    }

    $details = trim((string) ($event['details'] ?? ''));
    $details = $details === '' ? null : substr($details, 0, 500);
    $actorId = array_key_exists('actor_id', $event)
        ? (int) $event['actor_id']
        : (int) ($_SESSION['employee_id'] ?? 0);
    $actorId = $actorId > 0 ? $actorId : null;
    $actorType = strtolower(trim((string) ($event['actor_type'] ?? ($_SESSION['employee_role'] ?? 'system'))));
    $actorType = in_array($actorType, ['admin', 'employee', 'system'], true) ? $actorType : 'system';
    $branchId = array_key_exists('branch_id', $event)
        ? (int) $event['branch_id']
        : (int) ($_SESSION['branch_id'] ?? 0);
    $branchId = $branchId > 0 ? $branchId : null;
    $entityType = trim((string) ($event['entity_type'] ?? ''));
    $entityType = $entityType === '' ? null : substr($entityType, 0, 60);
    $entityId = array_key_exists('entity_id', $event) ? (int) $event['entity_id'] : 0;
    $entityId = $entityId > 0 ? $entityId : null;
    $metadata = $event['metadata'] ?? null;
    if (is_array($metadata)) {
        $metadata = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $metadata = is_string($metadata) && $metadata !== '' ? substr($metadata, 0, 4000) : null;
    $ipAddress = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null;

    try {
        $statement = $connect->prepare(
            'INSERT INTO activity_logs
                (category, action, summary, details, actor_id, actor_type, branch_id, entity_type, entity_id, metadata, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$statement) {
            return false;
        }

        $statement->bind_param(
            'ssssisisiss',
            $category,
            $action,
            $summary,
            $details,
            $actorId,
            $actorType,
            $branchId,
            $entityType,
            $entityId,
            $metadata,
            $ipAddress
        );
        $recorded = $statement->execute();
        $statement->close();

        return $recorded;
    } catch (Throwable $exception) {
        error_log('Unable to write activity log: ' . $exception->getMessage());
        return false;
    }
}
