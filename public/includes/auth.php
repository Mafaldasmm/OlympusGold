<?php
 
declare(strict_types=1);
 
/**
 * auth.php — Authentication & security helpers.
 *
 * Requires the existing init.php (paths) and database.php to already be loaded.
 * Include this in any page that needs sessions, login, or role checks.
 *
 * Usage:
 *   require_once __DIR__ . '/includes/init.php';   // already in every page
 *   require_once __DIR__ . '/includes/auth.php';   // add this line
 */
 
require_once dirname(__DIR__, 2) . '/includes/database.php';
 
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
/**
 * Sanitizes a string for safe HTML output (XSS protection).
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
 
/**
 * Redirects to a URL and exits.
 */
function redirect(string $url): never
{
    header('Location: ' . $url, true, 302);
    exit;
}
 
/**
 * Returns the currently logged-in user array, or null.
 */
function currentUser(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
 
    static $user = null;
    if ($user === null) {
        $user = dbQuery(
            'SELECT id, username, name, email, role, active FROM users WHERE id = :id',
            ['id' => $_SESSION['user_id']]
        )->fetch();
        if (!$user) {
            session_destroy();
            $user = null;
        }
    }
 
    return $user ?: null;
}
 
/**
 * Requires the user to be authenticated and optionally have one of the given roles.
 * Redirects to login.php if not authenticated, or home.php if wrong role.
 *
 * @return array The current user row
 */
function requireRole(string ...$roles): array
{
    $user = currentUser();
    if (!$user) {
        redirect(pageUrl('login.php'));
    }
    if (!empty($roles) && !in_array($user['role'], $roles, true)) {
        redirect(pageUrl('home.php'));
    }
    return $user;
}
 
/**
 * Generates (or retrieves) a CSRF token for the current session.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
 
/**
 * Validates the submitted CSRF token. Terminates with 403 if invalid.
 */
function verifyCsrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $submitted)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}
 
