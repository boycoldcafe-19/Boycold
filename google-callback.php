<?php
require_once './config/google.php';
require_once './config/db_config.php';

$error = '';
$authAction = $_SESSION['google_auth_action'] ?? 'login';
$expectedState = $_SESSION['google_oauth_state'] ?? '';
unset($_SESSION['google_auth_action'], $_SESSION['google_oauth_state']);

function redirectWithGoogleError(string $message): void
{
    $_SESSION['google_error'] = $message;
    header('Location: login.php');
    exit;
}

function ensureGoogleLoyaltyCard(mysqli $connect, int $userId): void
{
    $getStmt = $connect->prepare("SELECT card_no FROM users WHERE id = ?");
    $getStmt->bind_param("i", $userId);
    $getStmt->execute();
    $existing = $getStmt->get_result()->fetch_assoc();
    $getStmt->close();

    if (!empty($existing['card_no'])) {
        return;
    }

    $cardNo = 'BY-' . date('Y') . str_pad((string) $userId, 3, '0', STR_PAD_LEFT);
    $updateStmt = $connect->prepare("UPDATE users SET card_no = ? WHERE id = ?");
    $updateStmt->bind_param("si", $cardNo, $userId);
    $updateStmt->execute();
    $updateStmt->close();
}

function signInGoogleUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['user_name'];

    header('Location: User/home.php');
    exit;
}

if ($expectedState === '' || !isset($_GET['state']) || !hash_equals($expectedState, (string) $_GET['state'])) {
    redirectWithGoogleError('Invalid Google login request. Please try again.');
}

if (isset($_GET['error'])) {
    redirectWithGoogleError('Google authentication error: ' . (string) $_GET['error']);
}

if (!isset($_GET['code'])) {
    redirectWithGoogleError('No authorization code received from Google.');
}

$tokenResponse = exchangeCodeForToken($_GET['code']);
if (isset($tokenResponse['error']) || empty($tokenResponse['access_token'])) {
    redirectWithGoogleError('Failed to exchange Google code for an access token.');
}

$userInfo = getGoogleUserInfo($tokenResponse['access_token']);
if (isset($userInfo['error'])) {
    redirectWithGoogleError('Failed to get Google account information.');
}

$googleEmail = strtolower(trim((string) ($userInfo['email'] ?? '')));
$googleEmailVerified = filter_var(
    $userInfo['verified_email'] ?? ($userInfo['email_verified'] ?? false),
    FILTER_VALIDATE_BOOLEAN
);
$googleId = trim((string) ($userInfo['id'] ?? ($userInfo['sub'] ?? '')));
$googleName = trim((string) ($userInfo['name'] ?? ''));
$googleGivenName = trim((string) ($userInfo['given_name'] ?? ''));
$googleFamilyName = trim((string) ($userInfo['family_name'] ?? ''));

if ($googleEmail === '' || !filter_var($googleEmail, FILTER_VALIDATE_EMAIL) || $googleId === '') {
    redirectWithGoogleError('Could not retrieve your Google account email. Please try again.');
}

if (!$googleEmailVerified) {
    redirectWithGoogleError('Please verify your email address in Google before signing in.');
}

$existingUser = null;

$googleStmt = $connect->prepare(
    "SELECT id, firstname, lastname, user_name, email, google_id, is_verified
     FROM users
     WHERE google_id = ?
     LIMIT 1"
);
$googleStmt->bind_param("s", $googleId);
$googleStmt->execute();
$existingUser = $googleStmt->get_result()->fetch_assoc();
$googleStmt->close();

if (!$existingUser) {
    $emailStmt = $connect->prepare(
        "SELECT id, firstname, lastname, user_name, email, google_id, is_verified
         FROM users
         WHERE email = ?
         LIMIT 1"
    );
    $emailStmt->bind_param("s", $googleEmail);
    $emailStmt->execute();
    $existingUser = $emailStmt->get_result()->fetch_assoc();
    $emailStmt->close();
}

if ($existingUser) {
    if (!empty($existingUser['google_id']) && $existingUser['google_id'] !== $googleId) {
        redirectWithGoogleError('This email is already linked to a different Google account.');
    }

    $userId = (int) $existingUser['id'];
    $linkStmt = $connect->prepare(
        "UPDATE users
         SET google_id = ?, auth_provider = 'google', is_verified = 1
         WHERE id = ?"
    );
    $linkStmt->bind_param("si", $googleId, $userId);
    $linkStmt->execute();
    $linkStmt->close();

    ensureGoogleLoyaltyCard($connect, $userId);

    $freshStmt = $connect->prepare(
        "SELECT id, user_name, email
         FROM users
         WHERE id = ?
         LIMIT 1"
    );
    $freshStmt->bind_param("i", $userId);
    $freshStmt->execute();
    $freshUser = $freshStmt->get_result()->fetch_assoc();
    $freshStmt->close();

    signInGoogleUser($freshUser ?: $existingUser);
}

if ($authAction !== 'register') {
    redirectWithGoogleError('No BoyCold account is registered with that Google email. Please create an account first.');
}

$firstname = $googleGivenName ?: $googleName ?: strtok($googleEmail, '@');
$lastname = $googleFamilyName;
$randomPassword = bin2hex(random_bytes(16));
$hashedPassword = password_hash($randomPassword, PASSWORD_BCRYPT);

$insertStmt = $connect->prepare(
    "INSERT INTO users (firstname, lastname, email, password, google_id, auth_provider, is_verified, created_at)
     VALUES (?, ?, ?, ?, ?, 'google', 1, NOW())"
);
$insertStmt->bind_param("sssss", $firstname, $lastname, $googleEmail, $hashedPassword, $googleId);

if (!$insertStmt->execute()) {
    $insertStmt->close();
    redirectWithGoogleError('Failed to create your Google account. Please try again.');
}

$userId = (int) $insertStmt->insert_id;
$insertStmt->close();

ensureGoogleLoyaltyCard($connect, $userId);

$newUserStmt = $connect->prepare(
    "SELECT id, user_name, email
     FROM users
     WHERE id = ?
     LIMIT 1"
);
$newUserStmt->bind_param("i", $userId);
$newUserStmt->execute();
$newUser = $newUserStmt->get_result()->fetch_assoc();
$newUserStmt->close();

if (!$newUser) {
    redirectWithGoogleError('Your Google account was created, but sign-in could not be completed. Please try again.');
}

signInGoogleUser($newUser);
