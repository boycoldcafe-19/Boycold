<?php

/**
 * Persistent login protection for POS cashier accounts.
 *
 * A lock is deliberately permanent: it is cleared only when an administrator
 * changes the matching POS password or POS PIN from Admin > POS Settings.
 */
const POS_LOGIN_MAX_ATTEMPTS = 5;

function pos_login_lockout_columns(string $credential): array
{
    if ($credential === 'password') {
        return ['pos_password_failed_attempts', 'pos_password_locked_at'];
    }

    if ($credential === 'pin') {
        return ['pos_pin_failed_attempts', 'pos_pin_locked_at'];
    }

    throw new InvalidArgumentException('Unsupported POS credential type.');
}

function pos_login_credential_is_locked(array $employee, string $credential): bool
{
    [, $lockedColumn] = pos_login_lockout_columns($credential);
    return !empty($employee[$lockedColumn]);
}

function pos_login_account_is_locked(array $employee): bool
{
    return pos_login_credential_is_locked($employee, 'password')
        || pos_login_credential_is_locked($employee, 'pin');
}

/**
 * Records one failed POS credential attempt and returns its updated state.
 * The fixed column mapping keeps the dynamic SQL identifiers safe.
 */
function pos_login_record_failed_attempt(mysqli $connect, int $employeeId, string $credential): array
{
    [$attemptsColumn, $lockedColumn] = pos_login_lockout_columns($credential);

    $updateStmt = $connect->prepare(
        "UPDATE employees
         SET `$attemptsColumn` = LEAST(COALESCE(`$attemptsColumn`, 0) + 1, ?),
             `$lockedColumn` = CASE
                 WHEN COALESCE(`$attemptsColumn`, 0) + 1 >= ? THEN COALESCE(`$lockedColumn`, NOW())
                 ELSE `$lockedColumn`
             END
         WHERE id = ? AND `$lockedColumn` IS NULL"
    );
    $maxAttempts = POS_LOGIN_MAX_ATTEMPTS;
    $updateStmt->bind_param('iii', $maxAttempts, $maxAttempts, $employeeId);
    $updateStmt->execute();
    $updateStmt->close();

    $stateStmt = $connect->prepare(
        "SELECT `$attemptsColumn` AS failed_attempts, `$lockedColumn` AS locked_at
         FROM employees
         WHERE id = ?
         LIMIT 1"
    );
    $stateStmt->bind_param('i', $employeeId);
    $stateStmt->execute();
    $state = $stateStmt->get_result()->fetch_assoc() ?: [];
    $stateStmt->close();

    return [
        'attempts' => (int) ($state['failed_attempts'] ?? 0),
        'locked' => !empty($state['locked_at']),
    ];
}

function pos_login_reset_credential_lockout(mysqli $connect, int $employeeId, string $credential): void
{
    [$attemptsColumn, $lockedColumn] = pos_login_lockout_columns($credential);
    $resetStmt = $connect->prepare(
        "UPDATE employees
         SET `$attemptsColumn` = 0, `$lockedColumn` = NULL
         WHERE id = ?"
    );
    $resetStmt->bind_param('i', $employeeId);
    $resetStmt->execute();
    $resetStmt->close();
}

function pos_login_lockout_message(string $credential): string
{
    $label = $credential === 'pin' ? 'POS PIN' : 'POS password';
    return "$label is locked after " . POS_LOGIN_MAX_ATTEMPTS
        . ' failed attempts. An administrator must change it in POS Settings.';
}

/**
 * Admin accounts reuse the same employees.pos_password_* lock columns as POS
 * cashiers. Unlike cashiers, an admin lock is cleared only by finishing the
 * Forgot Password flow (User/forgotpass.php -> User/newpassword.php).
 */
function pos_login_admin_lockout_message(): string
{
    return 'This admin account is locked after ' . POS_LOGIN_MAX_ATTEMPTS
        . ' failed attempts. Please use Forgot Password to reset your password.';
}