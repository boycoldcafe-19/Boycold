<?php

function pos_start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('POS_SESSION');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

function pos_clear_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function pos_require_employee(mysqli $connect, bool $json = false): array
{
    pos_start_session();
    $employeeId = (int) ($_SESSION['employee_id'] ?? 0);

    if ($employeeId <= 0) {
        if ($json) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
            exit;
        }
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $loginPath = str_contains($script, '/dashboard/') || str_contains($script, '/auth/')
            ? '../../User/login.php'
            : '../User/login.php';
        header('Location: ' . $loginPath);
        exit;
    }

    $stmt = $connect->prepare(
        "SELECT e.id, e.employee_name, e.email, e.role, e.is_active, e.branch_id,
                b.branch_code, b.branch_name, b.status AS branch_status
         FROM employees e
         LEFT JOIN branches b ON b.id = e.branch_id
         WHERE e.id = ?
         LIMIT 1"
    );
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $accountValid = $employee
        && (int) $employee['is_active'] === 1
        && in_array($employee['role'], ['cashier', 'admin'], true)
        && ((int) $employee['branch_id'] > 0 && $employee['branch_status'] === 'active'
            || $employee['role'] === 'admin' && (int) $employee['branch_id'] === 0)
        && (int) ($_SESSION['branch_id'] ?? 0) === (int) $employee['branch_id'];

    if (!$accountValid) {
        pos_clear_session();
        if ($json) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
            exit;
        }
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $loginPath = str_contains($script, '/dashboard/') || str_contains($script, '/auth/')
            ? '../../User/login.php'
            : '../User/login.php';
        header('Location: ' . $loginPath);
        exit;
    }

    if (empty($_SESSION['pos_authenticated']) || empty($_SESSION['pos_pin_verified'])) {
        if ($json) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'PIN verification required.']);
            exit;
        }
        header('Location: ../auth/flashscreen.php');
        exit;
    }

    $_SESSION['employee_id'] = (int) $employee['id'];
    $_SESSION['employee_name'] = $employee['employee_name'];
    $_SESSION['employee_email'] = $employee['email'];
    $_SESSION['employee_role'] = $employee['role'];
    $_SESSION['branch_id'] = (int) $employee['branch_id'];
    $_SESSION['branch_code'] = $employee['branch_code'] ?? '';
    $_SESSION['branch_name'] = $employee['branch_name'] ?? '';
    $_SESSION['pos_authenticated'] = true;

    return $employee;
}