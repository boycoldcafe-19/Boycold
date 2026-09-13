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
$cardPayload = trim((string) ($input['card_no'] ?? $input['payload'] ?? $input['token'] ?? ''));
$action = trim($input['action'] ?? 'lookup');

if ($cardPayload === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Card number is required']);
    exit;
}

$token = extractLoyaltyTokenFromPayload($cardPayload);
$cardNo = '';
$cardQuery = parse_url($cardPayload, PHP_URL_QUERY);
if (is_string($cardQuery) && $cardQuery !== '') {
    parse_str($cardQuery, $cardParams);
    $cardNo = trim((string) ($cardParams['card_no'] ?? $cardParams['card'] ?? ''));
}
if ($cardNo === '') {
    $cardNo = trim($cardPayload);
}
$cardNo = strtoupper($cardNo);
if (!preg_match('/^BY-\d{7,}$/', $cardNo)) {
    $cardNo = '';
}

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
        "SELECT transaction_type, points_awarded, created_at, redeemed_product_name
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
    $awardStmt = $connect->prepare("UPDATE users SET loyalty_beans = 0, loyalty_stamps = LEAST(10, loyalty_stamps + 1) WHERE id = ?");
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
    $transactionStmt->bind_param('isiiiii', $user['id'], $cardNo, $branchId, $deviceId, $employeeId, $previousBalance, $newBalance);
    $transactionStmt->execute();
    $transactionStmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Loyalty stamp awarded',
        'customer' => customerPayload(array_merge($user, $updated), getRecentLoyaltyTransactions($connect, (int) $user['id']))
    ]);
    exit;
}

if ($action === 'redeem') {
    $branchId = (int) $employee['branch_id'];
    $deviceId = isset($_SESSION['device_id']) ? (int) $_SESSION['device_id'] : 0;
    $employeeId = (int) $employee['id'];

    $connect->begin_transaction();

    try {
        // Lock the customer's row so two cashiers can't redeem the same card at once
        $lockStmt = $connect->prepare("SELECT loyalty_beans, loyalty_stamps FROM users WHERE id = ? FOR UPDATE");
        $lockStmt->bind_param('i', $user['id']);
        $lockStmt->execute();
        $locked = $lockStmt->get_result()->fetch_assoc();
        $lockStmt->close();

        $currentStamps = (int) ($locked['loyalty_stamps'] ?? 0);
        if ($currentStamps < 10) {
            throw new Exception("This card is not full yet ({$currentStamps}/10 stamps).");
        }

        // Any drink on the menu can be the reward - validate whatever the cashier picked
        $productId = isset($input['product_id']) ? (int) $input['product_id'] : 0;
        $drink = findRedeemableDrinkProduct($connect, $productId);
        if (!$drink) {
            throw new Exception('Please select a valid drink to redeem.');
        }

        // Same points scale used elsewhere in this file (1 stamp = 10 points)
        $previousBalance = (int) ($locked['loyalty_beans'] ?? 0) + ($currentStamps * 10);
        $newBalance = 0;
        $pointsRedeemed = -$previousBalance;
        $redeemedProductId = (int) $drink['id'];
        $redeemedProductName = (string) $drink['product_name'];

        // Reward claimed - reset the card
        $resetStmt = $connect->prepare("UPDATE users SET loyalty_beans = 0, loyalty_stamps = 0 WHERE id = ?");
        $resetStmt->bind_param('i', $user['id']);
        $resetStmt->execute();
        $resetStmt->close();

        $transactionStmt = $connect->prepare(
            "INSERT INTO loyalty_transactions
                (user_id, card_no, branch_id, device_id, employee_id, transaction_type,
                 points_awarded, previous_balance, new_balance, redeemed_product_id, redeemed_product_name)
             VALUES (?, ?, ?, ?, ?, 'redemption', ?, ?, ?, ?, ?)"
        );
        $transactionStmt->bind_param(
            'isiiiiiiis',
            $user['id'],
            $cardNo,
            $branchId,
            $deviceId,
            $employeeId,
            $pointsRedeemed,
            $previousBalance,
            $newBalance,
            $redeemedProductId,
            $redeemedProductName
        );
        $transactionStmt->execute();
        $transactionStmt->close();

        $connect->commit();
    } catch (Exception $e) {
        $connect->rollback();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }

    $refreshStmt = $connect->prepare("SELECT id, firstname, lastname, email, phone, card_no, created_at, loyalty_beans, loyalty_stamps FROM users WHERE id = ? LIMIT 1");
    $refreshStmt->bind_param('i', $user['id']);
    $refreshStmt->execute();
    $updatedUser = $refreshStmt->get_result()->fetch_assoc();
    $refreshStmt->close();

    echo json_encode([
        'success' => true,
        'message' => "Reward redeemed \u2014 {$redeemedProductName} claimed.",
        'customer' => customerPayload($updatedUser, getRecentLoyaltyTransactions($connect, (int) $user['id']))
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'customer' => customerPayload($user, getRecentLoyaltyTransactions($connect, (int) $user['id']))
]);