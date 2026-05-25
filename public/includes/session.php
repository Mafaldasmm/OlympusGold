<?php

declare(strict_types=1);

/**
 * Secure session bootstrap and helpers.
 */

/**
 * Returns the local storage path for PHP session files.
 */
function sessionStoragePath(): string
{
    return dirname(__DIR__, 2) . '/storage/sessions';
}

/**
 * Returns true when the current request is served over HTTPS.
 */
function isHttpsRequest(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    return isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443;
}

/**
 * Starts the PHP session with secure cookie settings.
 */
function ensureSessionStarted(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $sessionPath = sessionStoragePath();

    if (!is_dir($sessionPath) && !mkdir($sessionPath, 0775, true) && !is_dir($sessionPath)) {
        throw new RuntimeException('Não foi possível criar a pasta de sessões.');
    }

    $current = session_get_cookie_params();

    session_name('olympus_session');
    session_save_path($sessionPath);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $current['path'] ?? '/',
        'domain' => $current['domain'] ?? '',
        'secure' => isHttpsRequest(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    if (isHttpsRequest()) {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
}

ensureSessionStarted();

/**
 * Returns the authenticated user id stored in the session, if any.
 */
function currentUserId(): ?int
{
    ensureSessionStarted();

    $userId = $_SESSION['user_id'] ?? null;

    if ($userId === null || $userId === '') {
        return null;
    }

    return is_int($userId) ? $userId : (int) $userId;
}

/**
 * Returns true when a user is logged in.
 */
function isAuthenticated(): bool
{
    return currentUserId() !== null;
}

/**
 * Stores the authenticated user id and regenerates the session id.
 */
function loginSessionUser(int $userId): void
{
    ensureSessionStarted();
    session_regenerate_id(true);

    $_SESSION['user_id'] = $userId;
    $_SESSION['logged_in_at'] = time();
}

/**
 * Completely destroys the current session and cookie.
 */
function logoutSessionUser(): void
{
    ensureSessionStarted();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();
}

/**
 * Stores a flash value for the next request.
 */
function setFlash(string $key, mixed $value): void
{
    ensureSessionStarted();
    $_SESSION['_flash'][$key] = $value;
}

/**
 * Retrieves and removes a flash value.
 */
function getFlash(string $key, mixed $default = null): mixed
{
    ensureSessionStarted();

    if (!isset($_SESSION['_flash']) || !array_key_exists($key, $_SESSION['_flash'])) {
        return $default;
    }

    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    if ($_SESSION['_flash'] === []) {
        unset($_SESSION['_flash']);
    }

    return $value;
}
