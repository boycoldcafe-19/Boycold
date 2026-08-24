<?php
require_once __DIR__ . '/config/db_config.php';
require_once __DIR__ . '/config/google_oauth.php';

startAppSession();

$config = googleOAuthConfig();
$state = $_GET['state'] ?? '';
$savedState = $_SESSION['google_oauth_state'] ?? '';
unset($_SESSION['google_oauth_state']);

if (!$savedState || !$state || !hash_equals($savedState, $state)) {
    googleOAuthError('Google sign-in could not be verified. Please try again.');
}

if (isset($_GET['error'])) {
    googleOAuthError('Google sign-in was cancelled.');
}

$code = $_GET['code'] ?? '';
if ($code === '') {
    googleOAuthError('Google did not return an authorization code. Please try again.');
}

$tokenResponse = googleOAuthRequest($config['token_url'], [
    'code' => $code,
    'client_id' => $config['client_id'],
    'client_secret' => $config['client_secret'],
    'redirect_uri' => $config['redirect_uri'],
    'grant_type' => 'authorization_code',
]);

if (!empty($tokenResponse['error']) || empty($tokenResponse['access_token'])) {
    error_log('Google token exchange failed: ' . json_encode($tokenResponse));
    googleOAuthError('Could not complete Google sign-in. Please try again.');
}

$userInfo = googleOAuthRequest($config['userinfo_url'], [], $tokenResponse['access_token']);
$googleId = trim((string) ($userInfo['sub'] ?? ''));
$email = strtolower(trim((string) ($userInfo['email'] ?? '')));
$firstName = trim((string) ($userInfo['given_name'] ?? ''));
$lastName = trim((string) ($userInfo['family_name'] ?? ''));

if ($googleId === '' || $email === '' || !googleEmailIsVerified($userInfo)) {
    googleOAuthError('Google returned an invalid or unverified email address.');
}

$linked = $connect->prepare('SELECT id, user_name, email FROM users WHERE google_id=? AND is_verified=1 LIMIT 1');
$linked->bind_param('s', $googleId);
$linked->execute();
$linkedUser = $linked->get_result()->fetch_assoc();

if ($linkedUser) {
    $userId = (int) $linkedUser['id'];
    $userName = $linkedUser['user_name'];
} else {
    $local = $connect->prepare('SELECT id, auth_provider FROM users WHERE email=? LIMIT 1');
    $local->bind_param('s', $email);
    $local->execute();
    $localUser = $local->get_result()->fetch_assoc();

    if ($localUser) {
        if (($localUser['auth_provider'] ?? 'local') === 'local') {
            googleOAuthError('This email already has a password account. Please log in with your password.');
        }

        googleOAuthError('This Google account is not linked to an existing user yet. Please contact support.');
    }

    $firstName = $firstName !== '' ? $firstName : 'Google';
    $lastName = $lastName !== '' ? $lastName : 'User';
    $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
    $avatar = trim((string) ($userInfo['picture'] ?? ''));
    if (strlen($avatar) > 255) {
        $avatar = substr($avatar, 0, 255);
    }

    $insert = $connect->prepare(
        "INSERT INTO users (firstname, lastname, email, password, is_verified, avatar, google_id, auth_provider, created_at)
         VALUES (?, ?, ?, ?, 1, ?, ?, 'google', NOW())"
    );
    $insert->bind_param('ssssss', $firstName, $lastName, $email, $randomPassword, $avatar, $googleId);

    if (!$insert->execute()) {
        error_log('Google user insert failed: ' . $connect->error);
        googleOAuthError('Could not create your account. Please try again.');
    }

    $userId = $connect->insert_id;
    $userName = $firstName . ' ' . $lastName;
    $cardNo = 'BY-' . date('Y') . str_pad((string) $userId, 3, '0', STR_PAD_LEFT);
    $card = $connect->prepare('UPDATE users SET card_no=? WHERE id=?');
    $card->bind_param('si', $cardNo, $userId);
    $card->execute();
}

session_regenerate_id(true);
$_SESSION['user_id'] = $userId;
$_SESSION['user_email'] = $email;
$_SESSION['user_name'] = $userName;
unset($_SESSION['google_oauth_return']);

header('Location: User/home.php');
exit;
