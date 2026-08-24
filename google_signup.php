<?php
require_once __DIR__ . '/config/google_oauth.php';

startAppSession();

$config = googleOAuthConfig();
$from = ($_GET['from'] ?? 'register') === 'login' ? 'login' : 'register';
$_SESSION['google_oauth_return'] = googleOAuthReturnPage($from);

if (!googleOAuthConfigured($config)) {
    googleOAuthError('Google sign-in is not configured yet. Add your Google OAuth credentials to the .env file.');
}

$_SESSION['google_oauth_state'] = bin2hex(random_bytes(32));

$query = http_build_query([
    'client_id' => $config['client_id'],
    'redirect_uri' => $config['redirect_uri'],
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'access_type' => 'online',
    'prompt' => 'select_account',
    'state' => $_SESSION['google_oauth_state'],
]);

header('Location: ' . $config['authorization_url'] . '?' . $query);
exit;
