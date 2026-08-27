<?php
// Loyalty QR Code Scanner Endpoint
// Validates loyalty token and returns customer information
// This endpoint is called when a staff member scans a customer's QR code

require_once '../config/db_config.php';
require_once '../config/loyalty.php';

header('Content-Type: application/json');

// Get token from query parameter
$token = $_GET['t'] ?? '';

if ($token === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No token provided']);
    exit;
}

// Validate token and get customer information
$customer = getLoyaltyCustomerByToken($connect, $token);

if ($customer === null) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Invalid loyalty token or customer not found']);
    exit;
}

// Return customer loyalty information
echo json_encode([
    'success' => true,
    'customer' => [
        'id' => $customer['id'],
        'name' => $customer['name'],
        'user_name' => $customer['user_name'],
        'card_no' => $customer['card_no'],
    ],
    'loyalty' => [
        'stamps' => $customer['stamps'],
        'total_stamps' => $customer['total_stamps'],
        'max_stamps' => $customer['max_stamps'],
        'remaining' => $customer['remaining'],
        'reward_available' => $customer['reward_available'],
        'loyalty_beans' => $customer['loyalty_beans'],
        'reward_status' => $customer['reward_status'],
    ]
]);
