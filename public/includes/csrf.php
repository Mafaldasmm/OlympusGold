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
 * Validates the submitted CSRF token and rotates it after a valid POST.
 */
function verifyCsrf(): void
{
    $submitted = (string) ($_POST['csrf_token'] ?? '');
    $currentToken = csrfToken();

    if ($submitted === '' || !hash_equals($currentToken, $submitted)) {
        renderErrorPage(
            403,
            'Pedido inválido',
            'O formulário expirou ou o token de segurança é inválido. Volta atrás e tenta novamente.'
        );
    }

    regenerateCsrfToken();
}
