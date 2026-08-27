<?php
/**
 * Get Current User Loyalty Data
 * 
 * Returns the current loyalty beans and stamps for the logged-in user
 */

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/loyalty.php';

header('Content-Type: application/json');

// Session guard
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    syncLoyaltyStampsFromCompletedOrders($connect, (int) $userId);

    // Get current user loyalty data
    $stmt = $connect->prepare("SELECT loyalty_beans, loyalty_stamps FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'loyalty_beans' => (int) $result['loyalty_beans'],
        'loyalty_stamps' => (int) $result['loyalty_stamps']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
