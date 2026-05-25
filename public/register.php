<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';

requireGuest();

$errors = getFlash('form_errors', []);
$old = getFlash('form_old', []);
$success = getFlash('auth_success', '');

$currentPage = 'register';
$pageTitle = 'Registar | Olympus Gold Gym';
$extraStylesheets = ['pages/register.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
<main class="auth-main">
    <div class="auth-card">
        <div class="auth-logo">OLYMPUS<span>GOLD</span></div>
        <h1>Criar Conta</h1>
        <p class="auth-sub">Cria a tua conta para acederes ao teu perfil.</p>

<?php if ($success !== ''): ?>
        <div class="alert alert-success">
            <p><?= e($success) ?></p>
        </div>
<?php endif; ?>

<?php if ($errors !== []): ?>
        <div class="alert alert-error">
<?php foreach ($errors as $error): ?>
            <p><?= e((string) $error) ?></p>
<?php endforeach; ?>
        </div>
<?php endif; ?>

        <form method="POST" action="<?= e(pageUrl('actions/register.php')) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="input-group">
                <label for="name">Nome Completo</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                    autocomplete="name"
                    value="<?= e((string) ($old['name'] ?? '')) ?>"
                >
            </div>

            <div class="input-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    autocomplete="username"
                    value="<?= e((string) ($old['username'] ?? '')) ?>"
                >
            </div>

            <div class="input-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    autocomplete="email"
                    value="<?= e((string) ($old['email'] ?? '')) ?>"
                >
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div class="input-group">
                <label for="confirm">Confirmar Password</label>
                <input
                    type="password"
                    id="confirm"
                    name="confirm"
                    required
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn-primary">Registar</button>
        </form>

        <p class="auth-link">
            Já tens conta?
            <a href="<?= e(pageUrl('login.php')) ?>">Fazer login</a>
        </p>
    </div>
</main>

<?php require INCLUDES_PATH . '/footer.php'; ?>
