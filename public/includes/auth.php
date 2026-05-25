<?php

declare(strict_types=1);

/**
 * Authentication, authorization and form helpers.
 */

require_once __DIR__ . '/session.php';
require_once dirname(__DIR__, 2) . '/includes/database.php';

/**
 * Escapes text for HTML output.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Normalizes a username for comparisons and lookups.
 */
function normalizeUsername(string $username): string
{
    return trim($username);
}

/**
 * Returns the lookup key used for case-insensitive username comparisons.
 */
function usernameLookupKey(string $username): string
{
    return strtolower(normalizeUsername($username));
}

/**
 * Normalizes an email for storage and comparisons.
 */
function normalizeEmail(string $email): string
{
    return strtolower(trim($email));
}

/**
 * Redirects and stops script execution.
 */
function redirect(string $url): never
{
    header('Location: ' . $url, true, 302);
    exit;
}

/**
 * Returns the currently authenticated user row.
 */
function currentUser(): ?array
{
    static $cachedUser = null;
    static $cachedUserId = null;

    $userId = currentUserId();

    if ($userId === null) {
        $cachedUser = null;
        $cachedUserId = null;
        return null;
    }

    if ($cachedUserId === $userId) {
        return $cachedUser;
    }

    $user = dbQuery(
        'SELECT id, username, name, email, role, active FROM users WHERE id = :id',
        ['id' => $userId]
    )->fetch();

    if (!$user || (int) $user['active'] !== 1) {
        logoutSessionUser();
        $cachedUser = null;
        $cachedUserId = null;
        return null;
    }

    $cachedUser = $user;
    $cachedUserId = $userId;

    return $cachedUser;
}

/**
 * Redirects authenticated users away from guest-only pages.
 */
function requireGuest(): void
{
    if (currentUser() !== null) {
        redirect(pageUrl('home.php'));
    }
}

/**
 * Redirects unauthenticated users to the login page.
 *
 * @return array<string, mixed>
 */
function requireLogin(): array
{
    $user = currentUser();

    if ($user === null) {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? pageUrl('profile.php');
        }

        setFlash('auth_error', 'Tens de iniciar sessão para aceder a esta página.');
        redirect(pageUrl('login.php'));
    }

    return $user;
}

/**
 * Redirects users that do not have one of the required roles.
 *
 * @return array<string, mixed>
 */
function requireRole(string ...$roles): array
{
    $user = requireLogin();

    if ($roles !== [] && !in_array($user['role'], $roles, true)) {
        setFlash('auth_error', 'Não tens permissões para aceder a essa área.');
        redirect(pageUrl('home.php'));
    }

    return $user;
}

/**
 * Retrieves and clears the post-login redirect target.
 */
function consumeRedirectAfterLogin(string $default): string
{
    $target = $_SESSION['redirect_after_login'] ?? null;
    unset($_SESSION['redirect_after_login']);

    if (!is_string($target) || $target === '') {
        return $default;
    }

    if ($target[0] !== '/') {
        return $default;
    }

    if (str_starts_with($target, '//') || str_contains($target, '\\')) {
        return $default;
    }

    return $target;
}

/**
 * Generates a CSRF token for the current session.
 */
function csrfToken(): string
{
    ensureSessionStarted();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

/**
 * Verifies a submitted CSRF token.
 */
function verifyCsrf(): void
{
    $submitted = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals(csrfToken(), $submitted)) {
        http_response_code(403);
        exit('Pedido inválido: CSRF token inválido.');
    }
}

/**
 * Stores validation errors and old input before redirecting.
 *
 * @param array<int, string> $errors
 * @param array<string, mixed> $old
 */
function redirectWithFormErrors(string $page, array $errors, array $old = []): never
{
    setFlash('form_errors', $errors);
    setFlash('form_old', $old);
    redirect(pageUrl($page));
}
