<?php
require_once __DIR__ . '/auth/guard.php';
pos_start_session();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/loyalty.php';
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

$employee = pos_require_employee($connect, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cardPayload = trim($input['card_no'] ?? '');
$action = trim($input['action'] ?? 'lookup');

if ($cardPayload === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Card number is required']);
    exit;
}

 $token = extractLoyaltyTokenFromPayload($cardPayload);
$cardNo = preg_match('/^BY-\d{4}\d{3}$/', $cardPayload) ? $cardPayload : '';

if ($token === '' && $cardNo === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid loyalty card or QR code']);
    exit;
}

$lookupColumn = $token !== '' ? 'loyalty_token' : 'card_no';
$lookupValue = $token !== '' ? $token : $cardNo;
$stmt = $connect->prepare("SELECT id, firstname, lastname, email, phone, card_no, created_at, loyalty_beans, loyalty_stamps FROM users WHERE {$lookupColumn} = ? LIMIT 1");
$stmt->bind_param('s', $lookupValue);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'No customer found for this card']);
    exit;
}

$cardNo = (string) ($user['card_no'] ?? '');

function getRecentLoyaltyTransactions(mysqli $connect, int $userId): array
{
    $stmt = $connect->prepare(
        "SELECT transaction_type, points_awarded, created_at
         FROM loyalty_transactions
         WHERE user_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT 10"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $transactions;
}

function customerPayload(array $user, array $transactions): array
{
    return [
        'id' => (int) $user['id'],
        'name' => trim($user['firstname'] . ' ' . $user['lastname']),
        'email' => (string) ($user['email'] ?? ''),
        'phone' => (string) ($user['phone'] ?? ''),
        'card_no' => (string) ($user['card_no'] ?? ''),
        'member_since' => (string) ($user['created_at'] ?? ''),
        'loyalty_beans' => (int) ($user['loyalty_beans'] ?? 0),
        'loyalty_stamps' => (int) ($user['loyalty_stamps'] ?? 0),
        'transactions' => $transactions,
    ];
}

if ($action === 'award') {
    // Get branch_id, device_id, and employee_id from session
    $branchId = (int) $employee['branch_id'];
    $deviceId = isset($_SESSION['device_id']) ? (int) $_SESSION['device_id'] : 0;
    $employeeId = (int) $employee['id'];

    // Get current balance before update (using direct stamp counting: 1 stamp = 10 points)
    $previousBalance = (int) $user['loyalty_beans'] + ((int) $user['loyalty_stamps'] * 10);

    // Award loyalty stamp directly
    $awardStmt = $connect->prepare("UPDATE users SET loyalty_beans = 0, loyalty_stamps = loyalty_stamps + 1 WHERE id = ?");
    $awardStmt->bind_param('i', $user['id']);
    $awardStmt->execute();
    $awardStmt->close();

    // Get updated balance
    $refreshStmt = $connect->prepare("SELECT loyalty_beans, loyalty_stamps FROM users WHERE id = ? LIMIT 1");
    $refreshStmt->bind_param('i', $user['id']);
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
        'customer' => customerPayload(array_merge($user, $updated), getRecentLoyaltyTransactions($connect, (int) $user['id']))
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'customer' => customerPayload($user, getRecentLoyaltyTransactions($connect, (int) $user['id']))
]);
