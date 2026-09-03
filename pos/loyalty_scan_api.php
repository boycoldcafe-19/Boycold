<?php
require_once __DIR__ . '/auth/guard.php';
pos_start_session();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

$employee = pos_require_employee($connect, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cardNo = trim($input['card_no'] ?? '');
$action = trim($input['action'] ?? 'lookup');

if ($cardNo === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Card number is required']);
    exit;
}

if (!preg_match('/^BY-\d{4}\d{3}$/', $cardNo)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid card format']);
    exit;
}

$stmt = $connect->prepare("SELECT id, firstname, lastname, card_no, loyalty_beans, loyalty_stamps FROM users WHERE card_no = ? LIMIT 1");
$stmt->bind_param('s', $cardNo);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'No customer found for this card']);
    exit;
}

if ($action === 'award') {
    // Get branch_id, device_id, and employee_id from session
    $branchId = (int) $employee['branch_id'];
    $deviceId = isset($_SESSION['device_id']) ? (int) $_SESSION['device_id'] : 0;
    $employeeId = (int) $employee['id'];

    // Get current balance before update (using direct stamp counting: 1 stamp = 10 points)
    $previousBalance = (int) $user['loyalty_beans'] + ((int) $user['loyalty_stamps'] * 10);

    // Award loyalty stamp directly
    $awardStmt = $connect->prepare("UPDATE users SET loyalty_beans = 0, loyalty_stamps = loyalty_stamps + 1 WHERE card_no = ?");
    $awardStmt->bind_param('s', $cardNo);
    $awardStmt->execute();
    $awardStmt->close();

    // Get updated balance
    $refreshStmt = $connect->prepare("SELECT loyalty_beans, loyalty_stamps FROM users WHERE card_no = ? LIMIT 1");
    $refreshStmt->bind_param('s', $cardNo);
    $refreshStmt->execute();
    $updated = $refreshStmt->get_result()->fetch_assoc();
    $refreshStmt->close();

    $newBalance = (int) ($updated['loyalty_beans'] ?? 0) + ((int) ($updated['loyalty_stamps'] ?? 0) * 10);

    // Record transaction in loyalty_transactions table
    $transactionStmt = $connect->prepare("INSERT INTO loyalty_transactions (user_id, card_no, branch_id, device_id, employee_id, transaction_type, points_awarded, previous_balance, new_balance) VALUES (?, ?, ?, ?, ?, 'bean_award', 10, ?, ?)");
    $transactionStmt->bind_param('isiiii', $user['id'], $cardNo, $branchId, $deviceId, $employeeId, $previousBalance, $newBalance);
    $transactionStmt->execute();
    $transactionStmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Loyalty stamp awarded',
        'customer' => [
            'id' => (int) $user['id'],
            'name' => trim($user['firstname'] . ' ' . $user['lastname']),
            'card_no' => $user['card_no'],
            'loyalty_beans' => (int) ($updated['loyalty_beans'] ?? 0),
            'loyalty_stamps' => (int) ($updated['loyalty_stamps'] ?? 0),
            'current_points' => $newBalance
        ]
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'customer' => [
        'id' => (int) $user['id'],
        'name' => trim($user['firstname'] . ' ' . $user['lastname']),
        'card_no' => $user['card_no'],
        'loyalty_beans' => 0, // Always 0 in new system
        'loyalty_stamps' => (int) $user['loyalty_stamps'],
        'current_points' => (int) (((int) $user['loyalty_beans']) + ((int) $user['loyalty_stamps'] * 10))
    ]
]);
