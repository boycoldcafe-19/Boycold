<?php

require_once __DIR__ . '/env.php';

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function googleOAuthRedirectUri(): string
{
    $configured = env('GOOGLE_REDIRECT_URI');
    if ($configured !== '') {
        return $configured;
    }

    if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/google_callback.php');
        $base = rtrim(dirname($script), '/');

        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $base . '/google_callback.php';
    }

    return 'http://localhost/boycoldv2/boycoldv2/google_callback.php';
}

function googleOAuthConfig(): array
{
    return [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => googleOAuthRedirectUri(),
        'authorization_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
    ];
}

function googleOAuthConfigured(array $config): bool
{
    $clientId = $config['client_id'];
    $clientSecret = $config['client_secret'];

    if ($clientId === '' || $clientSecret === '') {
        return false;
    }

    if (stripos($clientId, 'your-google-client-id') !== false) {
        return false;
    }

    if (stripos($clientSecret, 'your-google-client-secret') !== false) {
        return false;
    }

    return true;
}

function googleOAuthReturnPage(string $from = 'register'): string
{
    return $from === 'login' ? 'login.php' : 'register.php';
}

function googleOAuthError(string $message): never
{
    $returnTo = $_SESSION['google_oauth_return'] ?? 'register.php';
    unset($_SESSION['google_oauth_return']);

    $_SESSION['google_error'] = $message;
    header('Location: ' . $returnTo);
    exit;
}

function googleEmailIsVerified(array $userInfo): bool
{
    return filter_var($userInfo['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
}

function googleOAuthRequest(string $url, array $postFields = [], string $accessToken = ''): array
{
    $headers = $accessToken
        ? ['Authorization: Bearer ' . $accessToken, 'Accept: application/json']
        : ['Accept: application/json'];

    if ($postFields) {
        $headers = ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'];
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($postFields) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postFields));
    }

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false) {
        error_log('Google OAuth curl error: ' . $curlError);
        return ['error' => 'curl_failed', 'error_description' => $curlError];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        error_log('Google OAuth invalid JSON (HTTP ' . $status . '): ' . $response);
        return ['error' => 'invalid_response', 'status' => $status];
    }

    if ($status < 200 || $status >= 300) {
        error_log('Google OAuth HTTP ' . $status . ': ' . $response);
    }

    return $decoded;
}
