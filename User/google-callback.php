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
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['user_name'];

        header('Location: home.php');
        exit;
    } catch (Exception $e) {
        error_log("Sign-in error: " . $e->getMessage());
        redirectWithGoogleError('An error occurred during sign-in. Please try again.');
    }
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

    $googleStmt = $connect->prepare(
        "SELECT id, firstname, lastname, user_name, email, google_id, is_verified
         FROM users
         WHERE google_id = ?
         LIMIT 1"
    );
    if (!$googleStmt) {
        throw new Exception('Database error: ' . $connect->error);
    }
    
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
        if (!$emailStmt) {
            throw new Exception('Database error: ' . $connect->error);
        }
        
        $emailStmt->bind_param("s", $googleEmail);
        $emailStmt->execute();
        $existingUser = $emailStmt->get_result()->fetch_assoc();
        $emailStmt->close();
    }

    if ($existingUser) {
        if (!empty($existingUser['google_id']) && $existingUser['google_id'] !== $googleId) {
            throw new Exception('This email is already linked to a different Google account.');
        }

        $userId = (int) $existingUser['id'];
        $linkStmt = $connect->prepare(
            "UPDATE users
             SET google_id = ?, auth_provider = 'google', is_verified = 1
             WHERE id = ?"
        );
        if (!$linkStmt) {
            throw new Exception('Database error: ' . $connect->error);
        }
        
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
        if (!$freshStmt) {
            throw new Exception('Database error: ' . $connect->error);
        }
        
        $freshStmt->bind_param("i", $userId);
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
        "INSERT INTO users (firstname, lastname, email, password, google_id, auth_provider, is_verified, created_at)
         VALUES (?, ?, ?, ?, ?, 'google', 1, NOW())"
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