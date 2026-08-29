<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/payments.php';

boycold_ensure_payment_schema($connect);

$raw = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';

if (!paymongo_verify_webhook_signature($raw, $signature)) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid webhook signature.']);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid payload.']);
    exit;
}

$eventId = paymongo_event_id($payload);
$eventType = paymongo_event_type($payload);
$resource = paymongo_payment_resource($payload);
$attrs = is_array($resource['attributes'] ?? null) ? $resource['attributes'] : [];
$intentId = (string) ($attrs['payment_intent_id'] ?? '');
if ($intentId === '' && is_array($attrs['payment_intent'] ?? null)) {
    $intentId = (string) ($attrs['payment_intent']['id'] ?? '');
}
$amount = (int) ($attrs['amount'] ?? 0);

if ($intentId === '' && !empty($resource['id']) && strpos((string) $resource['id'], 'pi_') === 0) {
    $intentId = (string) $resource['id'];
}

try {
    $ok = true;
    if ($eventType === 'payment.paid' && $intentId !== '') {
        $ok = boycold_apply_qrph_result($connect, $intentId, $amount, $eventId, 'paid');
    } elseif ($eventType === 'payment.failed' && $intentId !== '') {
        $ok = boycold_apply_qrph_result($connect, $intentId, $amount, $eventId, 'failed');
    } elseif ($eventType === 'qrph.expired') {
        if ($intentId === '') {
            $intentId = (string) ($attrs['payment_intent_id'] ?? $resource['id'] ?? '');
        }
        if ($intentId !== '') {
            $ok = boycold_apply_qrph_result($connect, $intentId, $amount, $eventId, 'expired');
        }
    }
    if ($ok === false) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Order not found yet.']);
        exit;
    }
} catch (Throwable $e) {
    error_log('PayMongo webhook processing failed: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    exit;
}

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['success' => true]);
