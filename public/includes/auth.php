<?php

declare(strict_types=1);

/**
 * Authentication, authorization and form helpers.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once dirname(__DIR__, 2) . '/includes/database.php';

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
function currentUser(bool $forceRefresh = false): ?array
{
    static $cachedUser = null;
    static $cachedUserId = null;

    if ($forceRefresh) {
        $cachedUser = null;
        $cachedUserId = null;
    }

    $userId = currentUserId();

    if ($userId === null) {
        $cachedUser = null;
        $cachedUserId = null;
        return null;
    }

    if ($cachedUserId === $userId && $cachedUser !== null) {
        return $cachedUser;
    }

    $user = dbQuery(
        'SELECT id, username, name, email, role, active, profile_photo, reviews_blocked
         FROM users WHERE id = :id',
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
 * Clears the in-request cache for currentUser() after profile updates.
 */
function clearCurrentUserCache(): void
{
    currentUser(true);
}

/**
 * Returns the display name shown in navigation and greetings.
 *
 * @param array<string, mixed> $user
 */
function userDisplayName(array $user): string
{
    $name = trim((string) ($user['name'] ?? ''));

    if ($name !== '') {
        return $name;
    }

    return (string) $user['username'];
}

/**
 * Two-letter initials for avatar fallback (e.g. "Ana Silva" → AS).
 *
 * @param array<string, mixed>|null $user
 */
function userProfileInitials(?array $user): string
{
    if ($user === null) {
        return 'U';
    }

    $name = userDisplayName($user);
    $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

    if (count($parts) >= 2) {
        $first = utf8Substr($parts[0], 0, 1);
        $last  = utf8Substr($parts[count($parts) - 1], 0, 1);

        return utf8Strtoupper($first . $last);
    }

    $compact = preg_replace('/\s+/u', '', $name);

    return utf8Strtoupper(utf8Substr($compact !== '' ? $compact : 'User', 0, 2));
}

/**
 * Data URI SVG avatar with initials (local, no external service).
 *
 * @param array<string, mixed>|null $user
 */
function profileInitialsAvatarDataUri(?array $user, int $size = 150): string
{
    $initials = userProfileInitials($user);
    $fontSize = max(12, (int) round($size * 0.38));

    $svg = sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 %1$d %1$d">'
        . '<rect width="%1$d" height="%1$d" fill="#D4AF37"/>'
        . '<text x="50%%" y="52%%" dominant-baseline="middle" text-anchor="middle" '
        . 'font-family="Montserrat,Arial,sans-serif" font-size="%2$d" font-weight="700" fill="#333333">%3$s</text>'
        . '</svg>',
        $size,
        $fontSize,
        htmlspecialchars($initials, ENT_XML1 | ENT_QUOTES, 'UTF-8')
    );

    return 'data:image/svg+xml,' . rawurlencode($svg);
}

/**
 * Returns the public URL for a user's profile photo (uploaded file or initials avatar).
 *
 * @param array<string, mixed>|null $user
 */
function profilePhotoUrl(?array $user, int $initialsSize = 150): string
{
    if ($user !== null) {
        $path = trim((string) ($user['profile_photo'] ?? ''));

        if ($path !== '' && is_file(PUBLIC_ROOT . '/' . $path)) {
            return pageUrl($path);
        }
    }

    return profileInitialsAvatarDataUri($user, $initialsSize);
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
