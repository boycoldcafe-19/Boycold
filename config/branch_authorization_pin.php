<?php

/**
 * Branch-scoped authorization PINs used by sensitive POS actions.
 *
 * The two original PINs are seeded only the first time a branch is used:
 * Baliuag (1) = 1234 and Bustos (2) = 4321.  They are stored as hashes and
 * all later checks and changes use the branch's saved value.
 */
const BOYCOLD_AUTHORIZATION_PIN_MAX_ATTEMPTS = 5;

function boycold_ensure_branch_authorization_pin_schema(mysqli $connect): void
{
    $connect->query(
        'CREATE TABLE IF NOT EXISTS branch_authorization_pins (
            branch_id INT NOT NULL,
            pin_hash VARCHAR(255) NOT NULL,
            failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            locked_at DATETIME NULL DEFAULT NULL,
            updated_by INT NULL DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (branch_id),
            KEY idx_branch_authorization_pins_locked_at (locked_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function boycold_default_branch_authorization_pin(int $branchId, string $branchName = ''): ?string
{
    $normalizedBranchName = strtolower(trim($branchName));

    if ($branchId === 1 || str_contains($normalizedBranchName, 'baliuag')) {
        return '1234';
    }

    if ($branchId === 2 || str_contains($normalizedBranchName, 'bustos')) {
        return '4321';
    }

    return null;
}

/** @return array{branch_id:int,pin_hash:string,failed_attempts:int,locked_at:?string}|null */
function boycold_get_branch_authorization_pin_for_update(mysqli $connect, int $branchId): ?array
{
    $statement = $connect->prepare(
        'SELECT branch_id, pin_hash, failed_attempts, locked_at
         FROM branch_authorization_pins
         WHERE branch_id = ?
         LIMIT 1 FOR UPDATE'
    );
    $statement->bind_param('i', $branchId);
    $statement->execute();
    $record = $statement->get_result()->fetch_assoc() ?: null;
    $statement->close();

    if (!$record) {
        return null;
    }

    return [
        'branch_id' => (int) $record['branch_id'],
        'pin_hash' => (string) $record['pin_hash'],
        'failed_attempts' => (int) $record['failed_attempts'],
        'locked_at' => $record['locked_at'] !== null ? (string) $record['locked_at'] : null,
    ];
}

/**
 * Creates a branch's first PIN record without ever overwriting a PIN that an
 * administrator has already configured.
 */
function boycold_seed_branch_authorization_pin(mysqli $connect, int $branchId, string $branchName = ''): bool
{
    $defaultPin = boycold_default_branch_authorization_pin($branchId, $branchName);
    if ($branchId <= 0 || $defaultPin === null) {
        return false;
    }

    boycold_ensure_branch_authorization_pin_schema($connect);
    $pinHash = password_hash($defaultPin, PASSWORD_DEFAULT);
    $statement = $connect->prepare(
        'INSERT INTO branch_authorization_pins (branch_id, pin_hash)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE branch_id = VALUES(branch_id)'
    );
    $statement->bind_param('is', $branchId, $pinHash);
    $statement->execute();
    $statement->close();

    return true;
}

/**
 * Verifies one POS authorization attempt. A locked PIN remains locked until
 * Admin > POS Settings changes that same branch's Authorization PIN.
 *
 * @return array{success:bool,locked:bool,remaining_attempts:int,error?:string}
 */
function boycold_verify_branch_authorization_pin(
    mysqli $connect,
    int $branchId,
    string $submittedPin,
    string $branchName = ''
): array {
    if (!boycold_seed_branch_authorization_pin($connect, $branchId, $branchName)) {
        return [
            'success' => false,
            'locked' => false,
            'remaining_attempts' => 0,
            'error' => 'Authorization PIN is not configured for this branch.',
        ];
    }

    $connect->begin_transaction();
    try {
        $record = boycold_get_branch_authorization_pin_for_update($connect, $branchId);
        if (!$record) {
            $connect->rollback();
            return [
                'success' => false,
                'locked' => false,
                'remaining_attempts' => 0,
                'error' => 'Authorization PIN is not configured for this branch.',
            ];
        }

        if ($record['locked_at'] !== null) {
            $connect->commit();
            return [
                'success' => false,
                'locked' => true,
                'remaining_attempts' => 0,
                'error' => 'Authorization PIN is locked after 5 failed attempts. Change it in Admin POS Settings.',
            ];
        }

        if (preg_match('/^\d{4}$/', $submittedPin) && password_verify($submittedPin, $record['pin_hash'])) {
            $resetStatement = $connect->prepare(
                'UPDATE branch_authorization_pins
                 SET failed_attempts = 0, locked_at = NULL
                 WHERE branch_id = ?'
            );
            $resetStatement->bind_param('i', $branchId);
            $resetStatement->execute();
            $resetStatement->close();
            $connect->commit();

            return [
                'success' => true,
                'locked' => false,
                'remaining_attempts' => BOYCOLD_AUTHORIZATION_PIN_MAX_ATTEMPTS,
            ];
        }

        $attempts = min(BOYCOLD_AUTHORIZATION_PIN_MAX_ATTEMPTS, $record['failed_attempts'] + 1);
        $isLocked = $attempts >= BOYCOLD_AUTHORIZATION_PIN_MAX_ATTEMPTS;
        $failureStatement = $connect->prepare(
            'UPDATE branch_authorization_pins
             SET failed_attempts = ?, locked_at = CASE WHEN ? = 1 THEN NOW() ELSE NULL END
             WHERE branch_id = ?'
        );
        $lockFlag = $isLocked ? 1 : 0;
        $failureStatement->bind_param('iii', $attempts, $lockFlag, $branchId);
        $failureStatement->execute();
        $failureStatement->close();
        $connect->commit();

        return [
            'success' => false,
            'locked' => $isLocked,
            'remaining_attempts' => max(0, BOYCOLD_AUTHORIZATION_PIN_MAX_ATTEMPTS - $attempts),
            'error' => $isLocked
                ? 'Authorization PIN is locked after 5 failed attempts. Change it in Admin POS Settings.'
                : 'Incorrect authorization PIN.',
        ];
    } catch (Throwable $exception) {
        $connect->rollback();
        throw $exception;
    }
}

/**
 * Changes and unlocks one branch's Authorization PIN from Admin POS Settings.
 * The current PIN is checked directly so an already-locked branch can be
 * recovered by an administrator who knows that branch's current PIN.
 *
 * @return array{success:bool,error?:string}
 */
function boycold_change_branch_authorization_pin(
    mysqli $connect,
    int $branchId,
    string $branchName,
    string $currentPin,
    string $newPin,
    int $updatedBy
): array {
    if (!boycold_seed_branch_authorization_pin($connect, $branchId, $branchName)) {
        return ['success' => false, 'error' => 'Authorization PIN is not configured for this branch.'];
    }

    $connect->begin_transaction();
    try {
        $record = boycold_get_branch_authorization_pin_for_update($connect, $branchId);
        if (!$record) {
            $connect->rollback();
            return ['success' => false, 'error' => 'Authorization PIN is not configured for this branch.'];
        }

        if (!preg_match('/^\d{4}$/', $currentPin) || !password_verify($currentPin, $record['pin_hash'])) {
            $connect->commit();
            return ['success' => false, 'error' => 'Current authorization PIN is incorrect.'];
        }

        if (!preg_match('/^\d{4}$/', $newPin)) {
            $connect->commit();
            return ['success' => false, 'error' => 'New authorization PIN must contain exactly 4 digits.'];
        }

        if (password_verify($newPin, $record['pin_hash'])) {
            $connect->commit();
            return ['success' => false, 'error' => 'New authorization PIN must be different from the current PIN.'];
        }

        $newPinHash = password_hash($newPin, PASSWORD_DEFAULT);
        $updateStatement = $connect->prepare(
            'UPDATE branch_authorization_pins
             SET pin_hash = ?, failed_attempts = 0, locked_at = NULL, updated_by = ?
             WHERE branch_id = ?'
        );
        $updateStatement->bind_param('sii', $newPinHash, $updatedBy, $branchId);
        $updateStatement->execute();
        $updated = $updateStatement->affected_rows === 1;
        $updateStatement->close();
        $connect->commit();

        return $updated
            ? ['success' => true]
            : ['success' => false, 'error' => 'Unable to update the authorization PIN. Please try again.'];
    } catch (Throwable $exception) {
        $connect->rollback();
        throw $exception;
    }
}
