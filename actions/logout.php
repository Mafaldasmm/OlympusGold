<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect(pageUrl('home.php'));
}

$wasLoggedIn = isAuthenticated();
$csrfValid = verifyCsrfToken();

if (!$csrfValid && !$wasLoggedIn) {
    renderErrorPage(
        403,
        'Pedido inválido',
        'O pedido expirou ou o token de segurança é inválido. Volta atrás e tenta novamente.'
    );
}

logoutSessionUser();
ensureSessionStarted();
setFlash('auth_success', 'Sessão terminada com sucesso.');

redirect(pageUrl('login.php'));
