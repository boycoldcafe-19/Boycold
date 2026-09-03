<?php
require_once '../config/google.php';
require_once '../config/db_config.php';

$error = '';
$authAction = $_SESSION['google_auth_action'] ?? 'login';
$expectedState = $_SESSION['google_oauth_state'] ?? '';
unset($_SESSION['google_auth_action'], $_SESSION['google_oauth_state']);

function redirectWithGoogleError(string $message): void
{
    error_log("Google OAuth Error: $message");
    $_SESSION['google_error'] = $message;
    header('Location: login.php');
    exit;
}

function ensureGoogleLoyaltyCard(mysqli $connect, int $userId): void
{
    try {
        $getStmt = $connect->prepare("SELECT card_no FROM users WHERE id = ?");
        if (!$getStmt) {
            throw new Exception('Failed to prepare query: ' . $connect->error);
        }
        
        $getStmt->bind_param("i", $userId);
        $getStmt->execute();
        $existing = $getStmt->get_result()->fetch_assoc();
        $getStmt->close();

        if (!empty($existing['card_no'])) {
            return;
        }

        $cardNo = 'BY-' . date('Y') . str_pad((string) $userId, 3, '0', STR_PAD_LEFT);
        $updateStmt = $connect->prepare("UPDATE users SET card_no = ? WHERE id = ?");
        if (!$updateStmt) {
            throw new Exception('Failed to prepare query: ' . $connect->error);
        }
        
        $updateStmt->bind_param("si", $cardNo, $userId);
        $updateStmt->execute();
        $updateStmt->close();
    } catch (Exception $e) {
        error_log("Loyalty card error for user $userId: " . $e->getMessage());
    }
}

function signInGoogleUser(array $user): void
{
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
        $_SESSION['user_email'] = $user['email'] ?? '';
        $_SESSION['user_name'] = $user['user_name'] ?? '';

        header('Location: home.php');
        exit;
    } catch (Exception $e) {
        error_log("Sign-in error: " . $e->getMessage());
        redirectWithGoogleError('An error occurred during sign-in. Please try again.');
    }
}

function fetchUsersByGoogleId(mysqli $connect, string $googleId): array
{
    $stmt = $connect->prepare(
        "SELECT id, firstname, lastname, user_name, email, google_id, auth_provider, is_verified
         FROM users
         WHERE google_id = ?
         ORDER BY id"
    );
    if (!$stmt) {
        throw new Exception('Database error: ' . $connect->error);
    }

    $stmt->bind_param('s', $googleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($users) > 1) {
        throw new Exception('Multiple user accounts share the same Google ID. Please contact support.');
    }

    return $users;
}

function fetchUsersByEmail(mysqli $connect, string $email): array
{
    $stmt = $connect->prepare(
        "SELECT id, firstname, lastname, user_name, email, google_id, auth_provider, is_verified
         FROM users
         WHERE email = ?
         ORDER BY id"
    );
    if (!$stmt) {
        throw new Exception('Database error: ' . $connect->error);
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($users) > 1) {
        throw new Exception('Multiple user accounts share the same email address. Please contact support.');
    }

    return $users;
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

try {
    $tokenResponse = exchangeCodeForToken($_GET['code']);
    if (isset($tokenResponse['error']) || empty($tokenResponse['access_token'])) {
        throw new Exception('Failed to exchange Google code for an access token.');
    }

    $userInfo = getGoogleUserInfo($tokenResponse['access_token']);
    if (isset($userInfo['error'])) {
        throw new Exception('Failed to get Google account information.');
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
        throw new Exception('Could not retrieve your Google account email.');
    }

    if (!$googleEmailVerified) {
        redirectWithGoogleError('Please verify your email address in Google before signing in.');
    }

    $existingUser = null;
    $googleUsers = fetchUsersByGoogleId($connect, $googleId);
    $existingUser = $googleUsers[0] ?? null;

    if (!$existingUser) {
        $emailUsers = fetchUsersByEmail($connect, $googleEmail);
        $existingUser = $emailUsers[0] ?? null;
    }

    if ($existingUser) {
        $userId = (int) $existingUser['id'];

        if (!empty($existingUser['google_id']) && $existingUser['google_id'] !== $googleId) {
            throw new Exception('This email is already linked to a different Google account. Please sign in with that Google account or contact support.');
        }

        $linkStmt = $connect->prepare(
            "UPDATE users
             SET google_id = ?, auth_provider = 'google', is_verified = 1
             WHERE id = ?"
        );
        if (!$linkStmt) {
            throw new Exception('Database error: ' . $connect->error);
        }

        $linkStmt->bind_param('si', $googleId, $userId);
        $linkStmt->execute();
        $linkStmt->close();

        ensureGoogleLoyaltyCard($connect, $userId);

        $freshStmt = $connect->prepare(
            "SELECT id, user_name, email
             FROM users
             WHERE id = ?
             LIMIT 1"
        );
        if (!$freshStmt) {
            throw new Exception('Database error: ' . $connect->error);
        }

        $freshStmt->bind_param('i', $userId);
        $freshStmt->execute();
        $freshUser = $freshStmt->get_result()->fetch_assoc();
        $freshStmt->close();

        signInGoogleUser($freshUser ?: $existingUser);
    }

    if ($authAction !== 'register') {
        // Check if this Google account was already redirected to register
        if (isset($_SESSION['google_redirected_to_register']) && $_SESSION['google_redirected_to_register'] === $googleId) {
            // Auto-register the account since they already tried to register
            // Clear the redirect marker
            unset($_SESSION['google_redirected_to_register']);
        } else {
            // First time - redirect to register page with Google account info pre-filled
            $_SESSION['google_register_email'] = $googleEmail;
            $_SESSION['google_register_name'] = $googleName;
            $_SESSION['google_register_given_name'] = $googleGivenName;
            $_SESSION['google_register_family_name'] = $googleFamilyName;
            $_SESSION['google_register_id'] = $googleId;
            $_SESSION['google_register_message'] = 'Account has not yet registered. Please complete your registration.';
            $_SESSION['google_redirected_to_register'] = $googleId;
            header('Location: register.php');
            exit;
        }
    }

    $firstname = $googleGivenName ?: $googleName ?: strtok($googleEmail, '@');
    $lastname = $googleFamilyName;
    $randomPassword = bin2hex(random_bytes(16));
    $hashedPassword = password_hash($randomPassword, PASSWORD_BCRYPT);

    $insertStmt = $connect->prepare(
        "INSERT INTO users (firstname, lastname, email, password, google_id, auth_provider, is_verified)
         VALUES (?, ?, ?, ?, ?, 'google', 1)"
    );
    if (!$insertStmt) {
        throw new Exception('Database error: ' . $connect->error);
    }
    
    $insertStmt->bind_param("sssss", $firstname, $lastname, $googleEmail, $hashedPassword, $googleId);

    if (!$insertStmt->execute()) {
        throw new Exception('Failed to create your Google account: ' . $connect->error);
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
    if (!$newUserStmt) {
        throw new Exception('Database error: ' . $connect->error);
    }
    
    $newUserStmt->bind_param("i", $userId);
    $newUserStmt->execute();
    $newUser = $newUserStmt->get_result()->fetch_assoc();
    $newUserStmt->close();

    if (!$newUser) {
        throw new Exception('Your Google account was created, but sign-in could not be completed.');
    }

    signInGoogleUser($newUser);
} catch (Exception $e) {
    error_log("Google callback exception: " . $e->getMessage());
    redirectWithGoogleError($e->getMessage());
}
