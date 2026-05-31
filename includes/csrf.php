<?php

declare(strict_types=1);

/**
 * CSRF token helpers.
 */

require_once __DIR__ . '/session.php';

/**
 * Generates and stores a fresh CSRF token for the current session.
 */
function regenerateCsrfToken(): string
{
    ensureSessionStarted();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return (string) $_SESSION['csrf_token'];
}

/**
 * Returns the current CSRF token, generating one if needed.
 */
function csrfToken(): string
{
    ensureSessionStarted();

    if (empty($_SESSION['csrf_token'])) {
        return regenerateCsrfToken();
    }

    return (string) $_SESSION['csrf_token'];
}

/**
 * Returns true when the client expects a JSON response (e.g. fetch/XHR).
 */
function requestWantsJson(): bool
{
    return str_contains(
        strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')),
        'application/json'
    ) || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

/**
 * Returns true when the submitted CSRF token matches the current session token.
 * Rotates the token after a successful match.
 */
function verifyCsrfToken(bool $rotateAfterSuccess = true): bool
{
    $submitted = (string) ($_POST['csrf_token'] ?? '');
    $currentToken = csrfToken();

    if ($submitted === '' || !hash_equals($currentToken, $submitted)) {
        return false;
    }

    if ($rotateAfterSuccess) {
        regenerateCsrfToken();
    }

    return true;
}

/**
 * Validates the submitted CSRF token and rotates it after a valid POST.
 */
function verifyCsrf(): void
{
    if (verifyCsrfToken()) {
        return;
    }

    $currentToken = csrfToken();

    if (requestWantsJson()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => 'O pedido expirou. Recarrega a página e tenta novamente.',
            'csrfInvalid' => true,
            'csrfToken' => $currentToken,
        ], JSON_THROW_ON_ERROR);
        exit;
    }

    renderErrorPage(
        403,
        'Pedido inválido',
        'O formulário expirou ou o token de segurança é inválido. Volta atrás e tenta novamente.'
    );
}
