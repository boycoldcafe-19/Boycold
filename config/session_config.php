<?php
// config/session_config.php
// Single source of truth for session name + cookie settings.
// Every page/API that touches $_SESSION must require this
// BEFORE calling session_start() anywhere else.

function boycold_start_session(string $name = 'PHPSESSID'): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (session_name() === $name) {
            return; // already the right session, don't restart it
        }
        session_write_close();
    }

    session_name($name);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',                 // must cover /User/ AND /api/ AND /POS/
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
