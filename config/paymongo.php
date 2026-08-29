<?php
require_once __DIR__ . '/env.php';

function paymongo_secret_key(): string
{
    return env('PAYMONGO_SECRET_KEY');
}

function paymongo_webhook_secret(): string
{
    return env('PAYMONGO_WEBHOOK_SECRET');
}

function paymongo_request(string $method, string $path, ?array $body = null): array
{
    $secret = paymongo_secret_key();
    if ($secret === '') {
        throw new RuntimeException('PAYMONGO_SECRET_KEY is not configured.');
    }

    $ch = curl_init('https://api.paymongo.com' . $path);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($secret . ':'),
    ];

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        throw new RuntimeException('Could not reach PayMongo. Please try again.');
    }

    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid response from PayMongo.');
    }

    if ($http < 200 || $http >= 300) {
        $detail = $decoded['errors'][0]['detail'] ?? 'PayMongo request failed.';
        throw new RuntimeException((string) $detail);
    }

    return $decoded;
}

function paymongo_create_qrph(int $orderId, float $amountPhp, string $description): array
{
    $amount = (int) round($amountPhp * 100);
    if ($amount < 100) {
        throw new RuntimeException('QRPh amount must be at least ₱1.00.');
    }

    $intent = paymongo_request('POST', '/v1/payment_intents', [
        'data' => [
            'attributes' => [
                'amount' => $amount,
                'currency' => 'PHP',
                'payment_method_allowed' => ['qrph'],
                'description' => $description,
                'metadata' => [
                    'order_id' => (string) $orderId,
                ],
            ],
        ],
    ]);

    $intentId = (string) ($intent['data']['id'] ?? '');
    if ($intentId === '') {
        throw new RuntimeException('PayMongo did not return a payment intent.');
    }

    $method = paymongo_request('POST', '/v1/payment_methods', [
        'data' => [
            'attributes' => [
                'type' => 'qrph',
                'expiry_seconds' => 1800,
            ],
        ],
    ]);

    $methodId = (string) ($method['data']['id'] ?? '');
    if ($methodId === '') {
        throw new RuntimeException('PayMongo did not return a QRPh payment method.');
    }

    $attached = paymongo_request('POST', '/v1/payment_intents/' . rawurlencode($intentId) . '/attach', [
        'data' => [
            'attributes' => [
                'payment_method' => $methodId,
            ],
        ],
    ]);

    $imageUrl = paymongo_extract_qr_image($attached);
    if ($imageUrl === '') {
        throw new RuntimeException('PayMongo did not return a QR code image.');
    }

    return [
        'payment_intent_id' => $intentId,
        'qr_image_url' => $imageUrl,
        'amount_centavos' => $amount,
    ];
}

function paymongo_retrieve_intent(string $intentId): array
{
    return paymongo_request('GET', '/v1/payment_intents/' . rawurlencode($intentId));
}

function paymongo_extract_qr_image(array $intent): string
{
    $attrs = $intent['data']['attributes'] ?? [];
    $next = is_array($attrs) ? ($attrs['next_action'] ?? []) : [];
    if (!is_array($next)) {
        return '';
    }

    $code = $next['code'] ?? [];
    if (is_array($code) && !empty($code['image_url'])) {
        return (string) $code['image_url'];
    }

    return '';
}

function paymongo_verify_webhook_signature(string $rawBody, string $signatureHeader): bool
{
    $secret = paymongo_webhook_secret();
    if ($secret === '' || $signatureHeader === '') {
        return false;
    }

    $parts = [];
    foreach (explode(',', $signatureHeader) as $piece) {
        $piece = trim($piece);
        if ($piece === '' || strpos($piece, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $piece, 2);
        $parts[trim($key)] = trim($value);
    }

    $timestamp = $parts['t'] ?? '';
    if ($timestamp === '' || !ctype_digit($timestamp)) {
        return false;
    }

    if (abs(time() - (int) $timestamp) > 300) {
        return false;
    }

    $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
    $candidates = [];
    if (!empty($parts['te'])) {
        $candidates[] = $parts['te'];
    }
    if (!empty($parts['li'])) {
        $candidates[] = $parts['li'];
    }

    foreach ($candidates as $candidate) {
        if (hash_equals($expected, $candidate)) {
            return true;
        }
    }

    return false;
}

function paymongo_event_type(array $payload): string
{
    return (string) ($payload['data']['attributes']['type'] ?? '');
}

function paymongo_event_id(array $payload): string
{
    return (string) ($payload['data']['id'] ?? '');
}

function paymongo_payment_resource(array $payload): array
{
    $resource = $payload['data']['attributes']['data'] ?? [];
    return is_array($resource) ? $resource : [];
}
